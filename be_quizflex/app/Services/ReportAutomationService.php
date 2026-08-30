<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\QuestionReviewRequested;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ReportAutomationService
{
    public function __construct(
        protected ?QuestionSnapshotService $snapshotService = null,
        protected ?QuestionReviewService $reviewService = null
    ) {
        $this->snapshotService = $snapshotService ?? app(QuestionSnapshotService::class);
        $this->reviewService = $reviewService ?? app(QuestionReviewService::class);
    }

    /**
     * Kiểm tra xem câu hỏi có thuộc diện cần xử lý tự động hóa Report hay không
     */
    public function shouldTriggerAutoReview(Question $question): bool
    {
        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        return ReportTicket::whereIn('question_id', $allRelatedIds)
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->exists();
    }

    /**
     * Đánh giá chất lượng Revision của Author bằng Deterministic Rule-based Engine
     * Trả về kết quả PASS / FAIL / UNCERTAIN kèm chi tiết từng tiêu chí kiểm tra
     */
    public function evaluateRevisionForAutoApprove(Question $question, QuestionReviewRequest $reviewRequest): array
    {
        $checks = [
            'valid_question_structure' => false,
            'valid_type' => false,
            'valid_answers_count_and_content' => false,
            'valid_correct_answers_by_type' => false,
            'actual_content_change' => false,
            'no_data_corruption' => false,
            'no_critical_report_category' => false,
            'report_threshold_safe' => false,
            'no_disallowed_content' => false,
            'valid_ownership' => false,
        ];

        $errors = [];
        $isUncertain = false;

        $author = $question->user ?? $question->quiz?->user;
        if ($author && $reviewRequest->user_id === $author->id) {
            $checks['valid_ownership'] = true;
        } else {
            $errors[] = 'Người gửi yêu cầu duyệt không phải là chủ sở hữu hợp lệ của câu hỏi.';
        }

        if ($question->trashed()) {
            $errors[] = 'Câu hỏi đã bị xóa trong thùng rác.';
        }

        $content = trim($question->content ?? '');
        $contentLength = mb_strlen($content, 'UTF-8');
        $points = $question->points;

        if ($contentLength >= 5 && $contentLength <= 10000 && is_numeric($points) && $points >= 1 && $points <= 1000) {
            $checks['valid_question_structure'] = true;
        } else {
            $errors[] = 'Độ dài câu hỏi hoặc điểm số không nằm trong giới hạn cho phép.';
        }

        $allowedTypes = ['single_choice', 'multiple_choice', 'true_false', 'short_answer'];
        $qType = $question->type;
        if (in_array($qType, $allowedTypes, true)) {
            $checks['valid_type'] = true;
        } else {
            $errors[] = "Loại câu hỏi '{$qType}' không hợp lệ hoặc chưa được hỗ trợ Auto Review.";
            $isUncertain = true;
        }

        $answers = $question->answers;
        if ($qType === 'short_answer') {
            if ($answers->count() >= 1 && mb_strlen(trim($answers->first()->content ?? ''), 'UTF-8') > 0) {
                $checks['valid_answers_count_and_content'] = true;
                $checks['valid_correct_answers_by_type'] = true;
            } else {
                $errors[] = 'Câu hỏi tự luận ngắn phải có ít nhất một đáp án mẫu.';
            }
        } elseif ($qType === 'true_false') {
            if ($answers->count() === 2) {
                $correctCount = $answers->where('is_correct', true)->count();
                $allNonEmpty = $answers->every(fn($a) => mb_strlen(trim($a->content ?? ''), 'UTF-8') > 0);
                if ($allNonEmpty) {
                    $checks['valid_answers_count_and_content'] = true;
                } else {
                    $errors[] = 'Đáp án Đúng/Sai không được để trống.';
                }
                if ($correctCount === 1) {
                    $checks['valid_correct_answers_by_type'] = true;
                } else {
                    $errors[] = 'Câu hỏi Đúng/Sai phải có chính xác 1 đáp án đúng.';
                }
            } else {
                $errors[] = 'Câu hỏi Đúng/Sai phải có đúng 2 phương án lựa chọn.';
            }
        } else {
            $answersCount = $answers->count();
            if ($answersCount >= 2 && $answersCount <= 10) {
                $allNonEmpty = $answers->every(fn($a) => mb_strlen(trim($a->content ?? ''), 'UTF-8') > 0);
                $uniqueTexts = $answers->map(fn($a) => mb_strtolower(trim($a->content ?? ''), 'UTF-8'))->unique()->count();
                
                if ($allNonEmpty && $uniqueTexts === $answersCount) {
                    $checks['valid_answers_count_and_content'] = true;
                } else {
                    $errors[] = 'Các phương án trả lời bị trùng lặp nội dung hoặc có phương án để trống.';
                }
            } else {
                $errors[] = "Số lượng đáp án ({$answersCount}) không hợp lệ (yêu cầu từ 2 đến 10 đáp án).";
            }

            $correctCount = $answers->where('is_correct', true)->count();
            if ($qType === 'single_choice') {
                if ($correctCount === 1) {
                    $checks['valid_correct_answers_by_type'] = true;
                } else {
                    $errors[] = "Câu hỏi trắc nghiệm một đáp án phải có duy nhất 1 đáp án đúng (hiện có: {$correctCount}).";
                }
            } elseif ($qType === 'multiple_choice') {
                if ($correctCount >= 1 && $correctCount <= $answers->count()) {
                    $checks['valid_correct_answers_by_type'] = true;
                } else {
                    $errors[] = 'Câu hỏi nhiều đáp án phải có ít nhất 1 đáp án đúng.';
                }
            }
        }

        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);
        $currentFingerprint = $this->snapshotService->computeFingerprint($question);

        if ($bankSnapshot) {
            $bankFingerprint = $this->snapshotService->computeFingerprint($bankSnapshot);
            if ($currentFingerprint !== $bankFingerprint) {
                $checks['actual_content_change'] = true;
            } else {
                $errors[] = 'Nội dung câu hỏi chưa có sự thay đổi thực sự so với bản bị báo cáo trong Ngân hàng.';
            }
        } else {
            $checks['actual_content_change'] = true;
        }

        if (!empty($question->id) && !empty($question->content) && $answers->isNotEmpty()) {
            $checks['no_data_corruption'] = true;
        } else {
            $errors[] = 'Dữ liệu câu hỏi hoặc đáp án bị thiếu hoặc có dấu hiệu lỗi cấu trúc.';
        }

        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        $activeReports = ReportTicket::whereIn('question_id', $allRelatedIds)
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->get();

        $hasCriticalReason = false;
        foreach ($activeReports as $r) {
            if (ReportTicket::isCriticalReason($r->reason)) {
                $hasCriticalReason = true;
                break;
            }
        }

        if (!$hasCriticalReason) {
            $checks['no_critical_report_category'] = true;
        } else {
            $errors[] = 'Câu hỏi có báo cáo thuộc danh mục vi phạm nghiêm trọng (Critical), yêu cầu Quản trị viên thẩm định.';
        }

        if ($activeReports->count() < ReportTicket::MULTI_REPORT_THRESHOLD) {
            $checks['report_threshold_safe'] = true;
        } else {
            $errors[] = "Câu hỏi nhận từ " . ReportTicket::MULTI_REPORT_THRESHOLD . " báo cáo trở lên, yêu cầu Quản trị viên thẩm định.";
        }

        $allText = $question->content . ' ' . $answers->pluck('content')->implode(' ');
        $disallowedKeywords = ['hack', 'cheat', 'crack', 'dcm', 'đcm', 'vcl', 'clgt', 'sex', 'porn', 'chết tiệt'];
        $hasDisallowed = false;
        foreach ($disallowedKeywords as $kw) {
            if (mb_stripos($allText, $kw) !== false) {
                $hasDisallowed = true;
                break;
            }
        }

        if (!$hasDisallowed) {
            $checks['no_disallowed_content'] = true;
        } else {
            $errors[] = 'Nội dung chứa từ khóa nhạy cảm hoặc không phù hợp chuẩn mực.';
            $isUncertain = true;
        }

        $allPassed = !in_array(false, $checks, true);

        $res = [
            'pass' => $allPassed,
            'uncertain' => $isUncertain,
            'reason' => $allPassed ? 'Vượt qua toàn bộ quy tắc kiểm định an toàn tự động.' : implode(' ', $errors),
            'checks' => $checks,
            'errors' => $errors,
        ];

        return $res;
    }

    /**
     * Kích hoạt quy trình Auto Review và tự động Phê duyệt (Auto Approve) nếu đạt chuẩn
     * Được bảo vệ bởi Atomic Cache Lock và DB Transaction chống xung đột đồng thời.
     */
    public function processAutoReviewForQuestion(Question $question, ?QuestionReviewRequest $reviewRequest = null): array
    {
        $lockKey = "quizflex_auto_review_q_{$question->id}";
        $lock = Cache::lock($lockKey, 30);

        return $lock->get(function () use ($question, $reviewRequest) {
            $targetQuestionIds = array_filter(array_unique([
                $question->id,
                $question->origin_question_id,
            ]));
            $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
            $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

            $req = $reviewRequest ?? QuestionReviewRequest::where('question_id', $question->id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            if (!$req || $req->status !== 'pending') {
                return [
                    'auto_approved' => false,
                    'status' => 'no_pending_request',
                    'reason' => 'Không tìm thấy yêu cầu xét duyệt pending cho câu hỏi này.',
                ];
            }

            // Đánh giá Revision
            $evalResult = $this->evaluateRevisionForAutoApprove($question, $req);

            if ($evalResult['pass'] === true) {
                
                return DB::transaction(function () use ($question, $req, $evalResult, $allRelatedIds) {
                    // Tái sử dụng logic approve của QuestionReviewService với System Actor
                    $approvedReq = $this->reviewService->approveQuestion($question, null, true);

                    // Cập nhật metadata ghi nhận Auto Approved
                    $metadata = $approvedReq->snapshot_metadata ?? [];
                    $metadata['auto_approved'] = true;
                    $metadata['auto_review_checks'] = $evalResult['checks'];
                    $metadata['auto_approved_at'] = now()->toIso8601String();
                    $approvedReq->update(['snapshot_metadata' => $metadata]);

                    Log::info("Câu hỏi #{$question->id} Revision #{$approvedReq->revision_number} đã được AUTO APPROVED thành công.");

                    return [
                        'auto_approved' => true,
                        'status' => 'approved',
                        'reason' => $evalResult['reason'],
                        'review_request' => $approvedReq,
                        'details' => $evalResult,
                    ];
                });
            }

            // =========================================================
            // TRƯỜNG HỢP FAIL HOẶC UNCERTAIN: Chuyển sang ADMIN_REVIEW_REQUIRED
            // =========================================================
            return DB::transaction(function () use ($question, $req, $evalResult, $allRelatedIds) {
                // Chuyển toàn bộ ReportTicket active sang admin_review_required qua transitionTo
                $activeTickets = ReportTicket::whereIn('question_id', $allRelatedIds)
                    ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
                    ->get();
                foreach ($activeTickets as $ticket) {
                    $ticket->transitionTo(ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED);
                }

                // Đánh dấu ReviewRequest là Priority cho Admin
                $metadata = $req->snapshot_metadata ?? [];
                $metadata['auto_review_failed'] = true;
                $metadata['auto_review_reason'] = $evalResult['reason'];
                $metadata['auto_review_checks'] = $evalResult['checks'];

                $req->update([
                    'is_priority' => true,
                    'review_priority' => 'high',
                    'snapshot_metadata' => $metadata,
                ]);

                // Thông báo tới Quản trị viên về việc cần can thiệp xử lý ngoại lệ
                $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
                if ($admins->isNotEmpty()) {
                    try {
                        Notification::send($admins, new QuestionReviewRequested($question, $question->user ?? $req->user, $req->revision_number, true));
                    } catch (\Throwable $e) {
                        Log::warning('Không thể gửi thông báo QuestionReviewRequested cho Admin: ' . $e->getMessage());
                    }
                }

                Log::info("Câu hỏi #{$question->id} Revision #{$req->revision_number} Auto Review FAIL: {$evalResult['reason']}. Đã chuyển sang ADMIN_REVIEW_REQUIRED.");

                return [
                    'auto_approved' => false,
                    'status' => ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED,
                    'reason' => $evalResult['reason'],
                    'review_request' => $req,
                    'details' => $evalResult,
                ];
            });
        }) ?: [
            'auto_approved' => false,
            'status' => 'concurrent_lock',
            'reason' => 'Yêu cầu đang được xử lý đồng thời bởi một tiến trình khác.',
        ];
    }

    /**
     * Xử lý vòng đời nhắc nhở và tự động gỡ công khai (Auto Private) cho các câu hỏi bị báo cáo:
     * - DAY 3: Gửi Reminder tới Author nếu chưa sửa
     * - DAY 5: Gửi Warning tới Author nếu chưa sửa
     * - DAY 7: Tự động chuyển Question và Bank Snapshot sang Riêng tư (is_public = false) nếu chưa sửa
     *
     * Được bảo vệ bởi Atomic Cache Lock và xử lý transaction an toàn chống trùng lặp.
     */
    public function processLifecycleRemindersAndAutoPrivate(?\Carbon\CarbonInterface $now = null): array
    {
        $now = $now ?? now();
        $lockKey = 'quizflex_report_lifecycle_processor';
        $lock = Cache::lock($lockKey, 120);

        return $lock->get(function () use ($now) {
            $results = [
                'total_active_reports' => 0,
                'total_question_cases' => 0,
                'reminders_sent' => 0,
                'warnings_sent' => 0,
                'auto_privatized' => 0,
                'skipped' => 0,
                'details' => [],
            ];

            // 1. Lấy tất cả các báo cáo đang ở trạng thái active (pending / author_updated)
            $activeReports = ReportTicket::with(['question.user', 'question.quiz.user'])
                ->whereIn('status', [ReportTicket::STATUS_PENDING, ReportTicket::STATUS_AUTHOR_UPDATED])
                ->get();

            $results['total_active_reports'] = $activeReports->count();

            // 2. Nhóm theo Question Case (question_id) để xử lý ở cấp Case
            $groupedCases = $activeReports->groupBy('question_id');
            $results['total_question_cases'] = $groupedCases->count();

            foreach ($groupedCases as $questionId => $tickets) {
                $earliestReport = $tickets->sortBy('created_at')->first();
                $question = $earliestReport->question;

                // Loại trừ câu hỏi không tồn tại hoặc đã ở trong Thùng rác
                if (!$question || $question->trashed()) {
                    $results['skipped']++;
                    continue;
                }

                // Nếu câu hỏi có review request đã được approve tạo sau thời điểm report đầu tiên -> Tự động resolve
                $hasApprovedRevisionAfterReport = QuestionReviewRequest::where('question_id', $question->id)
                    ->where('status', 'approved')
                    ->where('created_at', '>', $earliestReport->created_at)
                    ->exists();

                if ($hasApprovedRevisionAfterReport) {
                    foreach ($tickets as $t) {
                        $t->update(['status' => ReportTicket::STATUS_RESOLVED]);
                    }
                    $results['skipped']++;
                    continue;
                }

                // Nếu tác giả vừa submit revision và đang pending (đang chờ duyệt) -> Tạm hoãn auto-private / reminder
                $hasPendingRevisionUnderReview = QuestionReviewRequest::where('question_id', $question->id)
                    ->where('status', 'pending')
                    ->where('created_at', '>', $earliestReport->created_at)
                    ->exists();

                if ($hasPendingRevisionUnderReview) {
                    // Đang chờ Admin / Auto Review thẩm định, tạm hoãn xử lý tự động
                    $results['skipped']++;
                    continue;
                }

                $author = $question->user ?? $question->quiz?->user;
                if (!$author) {
                    $results['skipped']++;
                    continue;
                }

                // Tính số ngày trôi qua từ report đầu tiên của case
                $days = (int) round($earliestReport->created_at->diffInDays($now, false));

                // 1. DAY 7: AUTO PRIVATE (>= 7 ngày)
                if ($days >= 7) {
                    // Kiểm tra xem đã có ticket nào trong case được đánh dấu auto_privatized_at chưa
                    $alreadyPrivatized = $tickets->contains(fn($t) => $t->auto_privatized_at !== null);

                    if (!$alreadyPrivatized) {
                        DB::transaction(function () use ($question, $tickets, $earliestReport, $author, $now, $days, &$results) {
                            $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);

                            if ($bankSnapshot && $bankSnapshot->is_public) {
                                $bankSnapshot->update(['is_public' => false]);
                            }
                            if ($question->is_public) {
                                $question->update(['is_public' => false]);
                            }

                            // Gửi thông báo Auto Private cho Author DUY NHẤT 1 LẦN cho toàn bộ Question Case
                            $author->notify(new QuestionModerated($question, 'auto_privatized', $earliestReport->reason, $earliestReport->description));

                            // Đánh dấu marker cho TẤT CẢ các tickets trong case để đảm bảo Idempotent
                            foreach ($tickets as $t) {
                                $t->update(['auto_privatized_at' => $now]);
                            }

                            Log::info("Question Case #{$question->id} ({$tickets->count()} tickets) đã bị AUTO PRIVATE sau {$days} ngày.");

                            $results['auto_privatized']++;
                            $results['details'][] = [
                                'question_id' => $question->id,
                                'tickets_count' => $tickets->count(),
                                'action' => 'auto_privatized',
                                'days' => $days,
                            ];
                        });
                    } else {
                        $results['skipped']++;
                    }
                    continue;
                }

                // 2. DAY 5: WARNING (>= 5 ngày và < 7 ngày)
                if ($days >= 5) {
                    $alreadyWarned = $tickets->contains(fn($t) => $t->warning_sent_at !== null);

                    if (!$alreadyWarned) {
                        // Gửi thông báo WARNING cho Author DUY NHẤT 1 LẦN cho toàn bộ Question Case
                        $author->notify(new QuestionModerated($question, 'warning', $earliestReport->reason, $earliestReport->description));

                        foreach ($tickets as $t) {
                            $t->update(['warning_sent_at' => $now]);
                        }

                        Log::info("Đã gửi WARNING cho Author Question Case #{$question->id} ({$tickets->count()} tickets) sau {$days} ngày.");

                        $results['warnings_sent']++;
                        $results['details'][] = [
                            'question_id' => $question->id,
                            'tickets_count' => $tickets->count(),
                            'action' => 'warning',
                            'days' => $days,
                        ];
                    } else {
                        $results['skipped']++;
                    }
                    continue;
                }

                // 3. DAY 3: REMINDER (>= 3 ngày và < 5 ngày)
                if ($days >= 3) {
                    $alreadyReminded = $tickets->contains(fn($t) => $t->reminder_sent_at !== null);

                    if (!$alreadyReminded) {
                        // Gửi thông báo REMINDER cho Author DUY NHẤT 1 LẦN cho toàn bộ Question Case
                        $author->notify(new QuestionModerated($question, 'reminder', $earliestReport->reason, $earliestReport->description));

                        foreach ($tickets as $t) {
                            $t->update(['reminder_sent_at' => $now]);
                        }

                        Log::info("Đã gửi REMINDER cho Author Question Case #{$question->id} ({$tickets->count()} tickets) sau {$days} ngày.");

                        $results['reminders_sent']++;
                        $results['details'][] = [
                            'question_id' => $question->id,
                            'tickets_count' => $tickets->count(),
                            'action' => 'reminder',
                            'days' => $days,
                        ];
                    } else {
                        $results['skipped']++;
                    }
                    continue;
                }

                $results['skipped']++;
            }

            return $results;
        }) ?: [
            'total_active_reports' => 0,
            'total_question_cases' => 0,
            'reminders_sent' => 0,
            'warnings_sent' => 0,
            'auto_privatized' => 0,
            'skipped' => 0,
            'details' => [],
            'locked' => true,
        ];
    }
}
