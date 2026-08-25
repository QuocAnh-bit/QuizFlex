<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\QuizModerated;
use App\Notifications\ReportCreated;
use App\Notifications\ReportResolved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ReportTicketController extends Controller
{
    /**
     * Người dùng gửi báo cáo vi phạm Câu hỏi (Question Snapshot hoặc Public Question)
     */
    public function store(Request $request)
    {
        $user = $request->user('api') ?? auth('api')->setRequest($request)->user() ?? auth('api')->user() ?? $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để thực hiện gửi báo cáo.'
            ], 401);
        }

        // Loại bỏ hoàn toàn quiz_id, chỉ nhận question_id
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ], [
            'question_id.required' => 'Vui lòng cung cấp ID câu hỏi cần báo cáo.',
            'question_id.exists' => 'Câu hỏi không tồn tại trên hệ thống.',
            'reason.required' => 'Vui lòng chọn lý do báo cáo vi phạm.',
        ]);

        $targetQuestion = Question::with(['user', 'quiz.user', 'quizzes', 'answers', 'educationLevel', 'grade', 'subject'])->find($request->question_id);
        if (!$targetQuestion) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy câu hỏi được báo cáo.',
            ], 404);
        }

        // Tạo Question Snapshot
        $questionSnapshot = [
            'id' => $targetQuestion->id,
            'content' => $targetQuestion->content ?? $targetQuestion->question,
            'image_url' => $targetQuestion->image_url,
            'type' => $targetQuestion->type,
            'difficulty' => $targetQuestion->difficulty ?? 'medium',
            'education_level_name' => $targetQuestion->educationLevel?->name,
            'grade_name' => $targetQuestion->grade?->name,
            'subject_name' => $targetQuestion->subject?->name,
            'topic_name' => $targetQuestion->topic_name,
            'is_public' => (bool) $targetQuestion->is_public,
            'created_at' => $targetQuestion->created_at?->toIso8601String(),
            'answers' => $targetQuestion->answers ? $targetQuestion->answers->map(function ($ans, $index) {
                return [
                    'id' => $ans->id,
                    'key' => chr(65 + ($ans->order ?? $index)),
                    'content' => $ans->content,
                    'text' => $ans->content,
                    'is_correct' => (bool) $ans->is_correct,
                ];
            })->values()->toArray() : [],
        ];

        // Phân giải Snapshot về Question gốc và Owner
        $originQuestion = $targetQuestion->origin_question_id
            ? (Question::with(['user', 'quiz.user'])->find($targetQuestion->origin_question_id) ?? $targetQuestion)
            : $targetQuestion;

        // Chống duplicate report: Kiểm tra xem User đã có ticket PENDING cho câu hỏi này chưa
        $existingPending = ReportTicket::where('user_id', $user->id)
            ->whereIn('question_id', array_unique([$targetQuestion->id, $originQuestion->id]))
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã báo cáo câu hỏi này và báo cáo đang được xử lý.',
            ], 409);
        }

        // Tạo bản ghi ReportTicket gắn trực tiếp với Question gốc
        $report = ReportTicket::create([
            'user_id' => $user->id,
            'question_id' => $originQuestion->id,
            'reason' => trim($request->reason),
            'description' => $request->filled('description') ? trim($request->description) : null,
            'status' => 'pending',
            'question_snapshot' => $questionSnapshot,
        ]);

        // 1. Gửi thông báo cho Admin (để theo dõi/thống kê hệ thống)
        $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
        if ($admins->isNotEmpty()) {
            try {
                Notification::send($admins, new ReportCreated($report, $user));
            } catch (\Throwable $e) {
                Log::warning('Không thể gửi thông báo ReportCreated cho Admin: ' . $e->getMessage());
            }
        }

        // 2. Gửi thông báo trực tiếp cho đúng Owner của Question gốc
        $questionOwner = $originQuestion->user ?? $originQuestion->quiz?->user;
        if ($questionOwner && $questionOwner->id !== $user->id) {
            try {
                $questionOwner->notify(new QuestionModerated($originQuestion, 'reported', $report->reason, $report->description));
            } catch (\Throwable $e) {
                Log::warning('Không thể gửi thông báo QuestionModerated cho tác giả: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Báo cáo câu hỏi đã được gửi thành công.',
            'data' => $report,
        ], 201);
    }

    /**
     * Lấy danh sách tất cả các báo cáo câu hỏi (Audit Log)
     */
    public function index(Request $request)
    {
        $query = ReportTicket::with([
            'user:id,name,email,avatar',
            'question.user:id,name,email,avatar',
            'question.answers',
            'question.educationLevel',
            'question.subject',
            'question.grade'
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $reports = $query->get()->map(function ($r) {
            $data = $r->toArray();
            if ($r->question) {
                $q = $r->question;
                $originQuestion = $q->origin_question_id
                    ? Question::with(['user', 'answers', 'educationLevel', 'subject', 'grade'])->find($q->origin_question_id)
                    : null;

                $targetQuestion = $originQuestion ?? $q;

                $hasUpdated = (bool) (
                    $r->has_author_updated ||
                    ($q->updated_at && $q->created_at && $q->updated_at->timestamp > $q->created_at->timestamp + 2) ||
                    ($originQuestion && $originQuestion->updated_at && $originQuestion->created_at && $originQuestion->updated_at->timestamp > $originQuestion->created_at->timestamp + 2)
                );

                $data['has_author_updated'] = $hasUpdated;
                $data['question'] = [
                    'id' => $targetQuestion->id,
                    'content' => $targetQuestion->content,
                    'text' => $targetQuestion->content,
                    'type' => $targetQuestion->type,
                    'difficulty' => $targetQuestion->difficulty,
                    'is_public' => (bool)$targetQuestion->is_public,
                    'status' => $targetQuestion->status,
                    'updated_at' => $targetQuestion->updated_at?->toIso8601String(),
                    'author_name' => $targetQuestion->user?->name ?? $q->user?->name ?? 'Vô danh',
                    'author_email' => $targetQuestion->user?->email ?? $q->user?->email,
                    'author_avatar' => $targetQuestion->user?->avatar ?? $q->user?->avatar,
                    'subject' => $targetQuestion->subject ? ['name' => $targetQuestion->subject->name] : null,
                    'grade' => $targetQuestion->grade ? ['name' => $targetQuestion->grade->name] : null,
                    'education_level' => $targetQuestion->educationLevel ? ['name' => $targetQuestion->educationLevel->name] : null,
                    'topic_name' => $targetQuestion->topic_name,
                    'answers' => $targetQuestion->answers ? $targetQuestion->answers->map(function ($ans, $index) {
                        return [
                            'id' => $ans->id,
                            'key' => chr(65 + ($ans->order ?? $index)),
                            'content' => $ans->content,
                            'text' => $ans->content,
                            'is_correct' => (bool) $ans->is_correct,
                        ];
                    })->values() : [],
                ];
            } else {
                $data['has_author_updated'] = false;
            }
            return $data;
        });

        // Bổ sung các câu hỏi bị Admin gỡ / bị từ chối / chờ duyệt đính chính mà CHƯA có ReportTicket
        $existingQuestionIds = [];
        foreach ($reports as $rep) {
            if (isset($rep['question_id'])) {
                $existingQuestionIds[] = $rep['question_id'];
            }
            if (isset($rep['question']['id'])) {
                $existingQuestionIds[] = $rep['question']['id'];
            }
        }
        $existingQuestionIds = array_values(array_filter(array_unique($existingQuestionIds)));

        $relatedDbIds = Question::whereIn('id', $existingQuestionIds)
            ->orWhereIn('origin_question_id', $existingQuestionIds)
            ->pluck('id')
            ->merge(Question::whereIn('id', $existingQuestionIds)->pluck('origin_question_id'))
            ->filter()
            ->all();

        $allExcludedIds = array_values(array_unique(array_merge($existingQuestionIds, $relatedDbIds)));

        $orphanedQuestions = Question::with(['user', 'answers', 'educationLevel', 'subject', 'grade'])
            ->whereNotIn('id', $allExcludedIds)
            ->where(function ($q) {
                $q->where('status', 'rejected')
                  ->orWhere('status', 'pending')
                  ->orWhere('is_public', false);
            })
            ->whereHas('answers')
            ->get();

        foreach ($orphanedQuestions as $oq) {
            $originId = $oq->origin_question_id ?? $oq->id;
            if (in_array($originId, $allExcludedIds)) continue;

            $hasUpdated = (bool) ($oq->updated_at && $oq->created_at && ($oq->updated_at->timestamp > $oq->created_at->timestamp + 2));

            // Chỉ đưa vào danh sách nếu có dấu hiệu từng bị gỡ/từ chối hoặc đã được tác giả chỉnh sửa
            if ($hasUpdated || $oq->status === 'rejected' || !$oq->is_public) {
                $reports->push([
                    'id' => 900000 + $oq->id,
                    'question_id' => $oq->id,
                    'user_id' => $oq->user_id,
                    'reason' => 'Admin gỡ công khai / Yêu cầu đính chính',
                    'description' => 'Câu hỏi đang ở trạng thái chờ kiểm duyệt đính chính nội dung.',
                    'status' => 'pending',
                    'has_author_updated' => $hasUpdated,
                    'created_at' => $oq->created_at?->toIso8601String(),
                    'question_snapshot' => [
                        'id' => $oq->id,
                        'content' => $oq->content,
                        'type' => $oq->type,
                        'difficulty' => $oq->difficulty,
                        'subject_name' => $oq->subject?->name,
                        'grade_name' => $oq->grade?->name,
                        'answers' => $oq->answers ? $oq->answers->map(fn($ans, $idx) => [
                            'id' => $ans->id,
                            'key' => chr(65 + ($ans->order ?? $idx)),
                            'content' => $ans->content,
                            'is_correct' => (bool)$ans->is_correct,
                        ])->values()->toArray() : [],
                    ],
                    'question' => [
                        'id' => $oq->id,
                        'content' => $oq->content,
                        'text' => $oq->content,
                        'type' => $oq->type,
                        'difficulty' => $oq->difficulty,
                        'is_public' => (bool)$oq->is_public,
                        'status' => $oq->status,
                        'updated_at' => $oq->updated_at?->toIso8601String(),
                        'author_name' => $oq->user?->name ?? 'Vô danh',
                        'author_email' => $oq->user?->email,
                        'author_avatar' => $oq->user?->avatar,
                        'subject' => $oq->subject ? ['name' => $oq->subject->name] : null,
                        'grade' => $oq->grade ? ['name' => $oq->grade->name] : null,
                        'education_level' => $oq->educationLevel ? ['name' => $oq->educationLevel->name] : null,
                        'topic_name' => $oq->topic_name,
                        'answers' => $oq->answers ? $oq->answers->map(fn($ans, $idx) => [
                            'id' => $ans->id,
                            'key' => chr(65 + ($ans->order ?? $idx)),
                            'content' => $ans->content,
                            'text' => $ans->content,
                            'is_correct' => (bool)$ans->is_correct,
                        ])->values() : [],
                    ]
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Cập nhật trạng thái báo cáo câu hỏi
     */
    public function update(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,resolved,dismissed']);

        if ($id >= 900000) {
            $realQuestionId = $id - 900000;
            $question = Question::find($realQuestionId);
            if ($question) {
                $relatedIds = array_filter(array_unique([
                    $question->id,
                    $question->origin_question_id,
                    ...Question::where('origin_question_id', $question->id)->pluck('id')->all(),
                ]));
                Question::whereIn('id', $relatedIds)->update([
                    'is_public' => true,
                    'status' => 'approved',
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái báo cáo thành công.',
            ]);
        }

        $report = ReportTicket::with(['question.user', 'question.quiz.user', 'user'])->findOrFail($id);

        $report->update([
            'status' => $request->status,
            'has_author_updated' => false,
        ]);
        $reportReason = $report->reason . (!empty($report->description) ? " ({$report->description})" : "");

        if ($request->status === 'resolved' && $report->question) {
            $q = $report->question;
            $relatedIds = array_filter(array_unique([
                $q->id,
                $q->origin_question_id,
                ...Question::where('origin_question_id', $q->id)->pluck('id')->all(),
                ...($q->origin_question_id ? Question::where('origin_question_id', $q->origin_question_id)->pluck('id')->all() : [])
            ]));

            // Mở công khai lại tất cả các bản ghi liên quan đến câu hỏi này
            Question::whereIn('id', $relatedIds)->update([
                'is_public' => true,
                'status' => 'approved',
            ]);

            // Cập nhật tất cả các vé báo cáo pending khác của câu hỏi này thành resolved
            ReportTicket::whereIn('question_id', $relatedIds)
                ->where('status', 'pending')
                ->update([
                    'status' => 'resolved',
                    'has_author_updated' => false,
                ]);
        }

        // Gửi thông báo cho tác giả của Quiz nếu có
        if ($report->quiz && $report->quiz->user) {
            $report->quiz->user->notify(new QuizModerated($report->quiz, $request->status, $reportReason));
        }

        // Gửi thông báo cho tác giả của Câu hỏi nếu có
        if ($report->question) {
            $questionAuthor = $report->question->user ?? $report->question->quiz?->user;
            if ($questionAuthor) {
                $questionAuthor->notify(new QuestionModerated($report->question, $request->status, $reportReason));
            }
        }

        $action = $request->input('action');

        // Gửi thông báo cảm ơn & phản hồi kết quả cho NGƯỜI ĐÃ BÁO CÁO (Reporter)
        if ($report->user && in_array($request->status, ['resolved', 'dismissed'])) {
            $report->user->notify(new ReportResolved($report, $request->status, $action));
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái báo cáo thành công.',
        ]);
    }

    /**
     * Thống kê số lượng báo cáo câu hỏi chờ xử lý
     */
    public function countPending()
    {
        $questionPending = ReportTicket::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'count' => $questionPending,
            'question_pending' => $questionPending,
        ]);
    }
}