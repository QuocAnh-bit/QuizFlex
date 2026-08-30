<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\ReportCreated;
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

        $targetQuestion = Question::with(['user', 'quiz.user', 'quizzes'])->find($request->question_id);
        if (!$targetQuestion) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy câu hỏi được báo cáo.',
            ], 404);
        }

        // Kiểm tra câu hỏi có hợp lệ để báo cáo không (phải là Public Question / Snapshot Ngân hàng / thuộc Quiz công khai)
        $isPublicQuestion = (bool) $targetQuestion->is_public;
        $isBankSnapshot = !empty($targetQuestion->origin_question_id);
        $isAttachedToPublicQuiz = ($targetQuestion->quiz && $targetQuestion->quiz->is_public && $targetQuestion->quiz->status === 'published')
            || $targetQuestion->quizzes()->where('is_public', true)->where('status', 'published')->exists();

        if (!$isPublicQuestion && !$isBankSnapshot && !$isAttachedToPublicQuiz) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể báo cáo câu hỏi công khai hoặc trong bài thi công khai.',
            ], 422);
        }

        // Phân giải Snapshot về Question gốc và Owner
        $originQuestion = $targetQuestion->origin_question_id
            ? (Question::with(['user', 'quiz.user'])->find($targetQuestion->origin_question_id) ?? $targetQuestion)
            : $targetQuestion;

        // Chống duplicate report: Kiểm tra xem User đã có ticket chưa giải quyết cho câu hỏi này chưa
        $existingPending = ReportTicket::where('user_id', $user->id)
            ->whereIn('question_id', array_unique([$targetQuestion->id, $originQuestion->id]))
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->first();

        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã báo cáo câu hỏi này và báo cáo đang được xử lý.',
            ], 409);
        }

        // Xác định trạng thái ban đầu: Ưu tiên đưa vào hàng đợi Admin nếu lý do nghiêm trọng hoặc tích lũy nhiều report (>= 3 reports)
        $reasonText = trim($request->reason);
        $unresolvedCount = ReportTicket::where('question_id', $originQuestion->id)
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->count();

        $initialStatus = ReportTicket::determineInitialStatus($reasonText, $unresolvedCount);

        // Tạo bản ghi ReportTicket gắn trực tiếp với Question gốc
        $report = ReportTicket::create([
            'user_id' => $user->id,
            'question_id' => $originQuestion->id,
            'reason' => $reasonText,
            'description' => $request->filled('description') ? trim($request->description) : null,
            'status' => $initialStatus,
        ]);

        // Nếu vượt ngưỡng >= 3 reports, đồng bộ tất cả report pending trước đó của câu hỏi sang admin_review_required
        if ($unresolvedCount >= (ReportTicket::MULTI_REPORT_THRESHOLD - 1)) {
            ReportTicket::where('question_id', $originQuestion->id)
                ->where('status', ReportTicket::STATUS_PENDING)
                ->update(['status' => ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED]);
        }

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
     * Lấy danh sách tất cả các báo cáo câu hỏi (Audit Log & Moderation)
     */
    public function index(Request $request)
    {
        // 1. Phân loại Question Case theo thứ bậc ưu tiên nghiệp vụ
        // Exception Case: Có ít nhất 1 ticket đang ở trạng thái admin_review_required
        $adminReviewQuestionIds = ReportTicket::where('status', ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED)
            ->distinct()->pluck('question_id')->all();

        // Author Updated Case: Có ticket ở author_updated và không có ticket ở admin_review_required
        $authorUpdatedQuestionIds = ReportTicket::where('status', ReportTicket::STATUS_AUTHOR_UPDATED)
            ->whereNotIn('question_id', $adminReviewQuestionIds)
            ->distinct()->pluck('question_id')->all();

        // Pending Only Case: Tất cả active tickets đều ở trạng thái pending (chờ tác giả sửa)
        $pendingOnlyQuestionIds = ReportTicket::where('status', ReportTicket::STATUS_PENDING)
            ->whereNotIn('question_id', array_merge($adminReviewQuestionIds, $authorUpdatedQuestionIds))
            ->distinct()->pluck('question_id')->all();

        $activeQuestionIds = array_values(array_unique(array_merge($adminReviewQuestionIds, $authorUpdatedQuestionIds, $pendingOnlyQuestionIds)));

        // Auto Privatized Cases: Có ticket bị auto privatized và thuộc active cases
        $autoPrivatizedQuestionIds = ReportTicket::whereNotNull('auto_privatized_at')
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->distinct()->pluck('question_id')->all();

        // Resolved Cases: Toàn bộ ticket của câu hỏi đã được giải quyết (không còn active ticket nào)
        $resolvedQuestionIds = ReportTicket::where('status', ReportTicket::STATUS_RESOLVED)
            ->whereNotIn('question_id', $activeQuestionIds)
            ->distinct()->pluck('question_id')->all();

        // Dismissed Cases: Toàn bộ ticket của câu hỏi đã bị bác bỏ/bỏ qua
        $dismissedQuestionIds = ReportTicket::where('status', ReportTicket::STATUS_DISMISSED)
            ->whereNotIn('question_id', array_merge($activeQuestionIds, $resolvedQuestionIds))
            ->distinct()->pluck('question_id')->all();

        // Auto Resolved Cases vs Manual Resolved Cases trong nhóm Resolved Cases
        $autoResolvedQuestionIds = [];
        $manualResolvedQuestionIds = [];
        if (!empty($resolvedQuestionIds)) {
            $questionsWithReview = \App\Models\Question::with('latestReviewRequest')
                ->whereIn('id', $resolvedQuestionIds)
                ->get();
            foreach ($questionsWithReview as $q) {
                if ($q->latestReviewRequest?->snapshot_metadata['auto_approved'] ?? false) {
                    $autoResolvedQuestionIds[] = $q->id;
                } else {
                    $manualResolvedQuestionIds[] = $q->id;
                }
            }
        }

        $query = ReportTicket::with([
            'user:id,name,email,avatar',
            'question.user:id,name,email,avatar',
            'question.subject',
            'question.grade',
            'question.educationLevel',
            'question.answers',
            'question.quizzes:id,title,is_public,status',
            'question.quiz:id,title,is_public,status',
            'question.reports.user:id,name,email,avatar',
            'question.latestReviewRequest',
            'question.pendingReviewRequest',
        ])->latest();

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $statusFilter = $request->query('status');
            if ($statusFilter === 'needs_admin_review' || $statusFilter === 'admin_review_required') {
                $query->whereIn('question_id', $adminReviewQuestionIds);
            } elseif ($statusFilter === 'author_updated') {
                $query->whereIn('question_id', $authorUpdatedQuestionIds);
            } elseif ($statusFilter === 'pending') {
                $query->whereIn('question_id', $pendingOnlyQuestionIds);
            } elseif ($statusFilter === 'auto_privatized') {
                $query->whereIn('question_id', $autoPrivatizedQuestionIds);
            } elseif ($statusFilter === 'auto_resolved') {
                $query->whereIn('question_id', $autoResolvedQuestionIds);
            } elseif ($statusFilter === 'resolved') {
                $query->whereIn('question_id', $resolvedQuestionIds);
            } elseif ($statusFilter === 'dismissed') {
                $query->whereIn('question_id', $dismissedQuestionIds);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if ($request->filled('question_id')) {
            $query->where('question_id', $request->query('question_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $cleanKeyword = ltrim($search, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($search, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('question_id', $numericId)
                      ->orWhere('reason', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('question', function ($qq) use ($search, $numericId) {
                          $qq->where('content', 'like', "%{$search}%")
                             ->orWhere('id', $numericId);
                      });
                } else {
                    $q->where('reason', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('question', function ($qq) use ($search) {
                          $qq->where('content', 'like', "%{$search}%");
                      });
                }
            });
        }

        $reports = $query->get();

        // Calculate KPI stats cho Exception Queue và Case Management
        $stats = [
            'total' => ReportTicket::count(),
            'total_cases' => ReportTicket::distinct('question_id')->count('question_id'),
            
            // Case-based counts (chỉ số chính cho giao diện Quản lý Case)
            'needs_admin_review' => count($adminReviewQuestionIds),
            'admin_review_required' => count($adminReviewQuestionIds),
            'admin_review_required_cases' => count($adminReviewQuestionIds),
            'author_updated' => count($authorUpdatedQuestionIds),
            'author_updated_cases' => count($authorUpdatedQuestionIds),
            'pending' => count($pendingOnlyQuestionIds),
            'pending_cases' => count($pendingOnlyQuestionIds),
            'auto_privatized' => count($autoPrivatizedQuestionIds),
            'auto_privatized_cases' => count($autoPrivatizedQuestionIds),
            'resolved' => count($resolvedQuestionIds),
            'resolved_cases' => count($resolvedQuestionIds),
            'auto_resolved' => count($autoResolvedQuestionIds),
            'auto_resolved_cases' => count($autoResolvedQuestionIds),
            'manual_resolved_cases' => count($manualResolvedQuestionIds),
            'dismissed' => count($dismissedQuestionIds),
            'dismissed_cases' => count($dismissedQuestionIds),
            'exception_cases_count' => count($adminReviewQuestionIds),
            'questions_count' => count($activeQuestionIds),

            // Ticket-based counts (chỉ số phụ cho Log view)
            'admin_review_required_tickets' => ReportTicket::where('status', ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED)->count(),
            'author_updated_tickets' => ReportTicket::where('status', ReportTicket::STATUS_AUTHOR_UPDATED)->count(),
            'pending_tickets' => ReportTicket::where('status', ReportTicket::STATUS_PENDING)->count(),
            'auto_privatized_tickets' => ReportTicket::whereNotNull('auto_privatized_at')->count(),
            'resolved_tickets' => ReportTicket::where('status', ReportTicket::STATUS_RESOLVED)->count(),
            'dismissed_tickets' => ReportTicket::where('status', ReportTicket::STATUS_DISMISSED)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $reports,
            'stats' => $stats,
        ]);
    }

    /**
     * Lấy chi tiết một lượt báo cáo hoặc toàn bộ báo cáo của 1 câu hỏi
     */
    public function show($id)
    {
        $ticket = ReportTicket::with([
            'user:id,name,email,avatar',
            'question.user:id,name,email,avatar',
            'question.subject',
            'question.grade',
            'question.educationLevel',
            'question.answers',
            'question.quizzes:id,title,is_public,status',
            'question.quiz:id,title,is_public,status',
            'question.reports.user:id,name,email,avatar',
        ])->find($id);

        if (!$ticket) {
            $ticket = ReportTicket::where('question_id', $id)->with([
                'user:id,name,email,avatar',
                'question.user:id,name,email,avatar',
                'question.subject',
                'question.grade',
                'question.educationLevel',
                'question.answers',
                'question.quizzes:id,title,is_public,status',
                'question.quiz:id,title,is_public,status',
                'question.reports.user:id,name,email,avatar',
            ])->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin báo cáo.',
                ], 404);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Cập nhật trạng thái của một báo cáo vi phạm đơn lẻ
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', ReportTicket::ALL_STATUSES),
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $ticket = ReportTicket::findOrFail($id);
        $targetStatus = $request->status;

        if (!$ticket->canTransitionTo($targetStatus)) {
            return response()->json([
                'success' => false,
                'message' => "Không thể chuyển trạng thái báo cáo từ '{$ticket->status}' sang '{$targetStatus}' do vi phạm quy tắc chuyển trạng thái.",
            ], 422);
        }

        $ticket->status = $targetStatus;
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái báo cáo thành công.',
            'data' => $ticket
        ]);
    }

    /**
     * Xử lý toàn bộ các báo cáo vi phạm của một Question
     */
    public function resolveQuestionReports(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'status' => 'required|in:' . implode(',', [ReportTicket::STATUS_RESOLVED, ReportTicket::STATUS_DISMISSED, ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED]),
            'action' => 'nullable|in:keep,hide,delete',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $questionId = $request->question_id;
        $status = $request->status;
        $action = $request->action ?? 'keep';

        // 1. Cập nhật tất cả các ticket chưa giải quyết của câu hỏi này và gửi thông báo cho Reporter
        $activeTickets = ReportTicket::with('user')
            ->where('question_id', $questionId)
            ->whereIn('status', ReportTicket::ACTIVE_STATUSES)
            ->get();

        foreach ($activeTickets as $ticket) {
            $ticket->transitionTo($status);

            // Gửi thông báo cho Reporter khi vé báo cáo được giải quyết hoặc bác bỏ
            if (in_array($status, [ReportTicket::STATUS_RESOLVED, ReportTicket::STATUS_DISMISSED], true) && $ticket->user) {
                try {
                    $ticket->user->notify(new \App\Notifications\ReportResolved($ticket, $status, $action));
                } catch (\Throwable $e) {
                    Log::warning('Không thể gửi thông báo ReportResolved cho reporter: ' . $e->getMessage());
                }
            }
        }

        // 2. Thực hiện hành động nếu có trên Question
        $question = Question::withTrashed()->find($questionId);
        if ($question) {
            if ($action === 'hide') {
                $question->is_public = false;
                $question->save();
            } elseif ($action === 'delete') {
                if (!$question->trashed()) {
                    $question->delete();
                }
            }

            // Thông báo cho tác giả nếu được giải quyết / bác bỏ
            if (in_array($status, [ReportTicket::STATUS_RESOLVED, ReportTicket::STATUS_DISMISSED], true) && $question->user) {
                try {
                    $question->user->notify(new QuestionModerated(
                        $question,
                        $action === 'delete' ? 'deleted' : ($action === 'hide' ? 'hidden' : ($status === ReportTicket::STATUS_DISMISSED ? 'dismissed' : 'resolved')),
                        $request->admin_note ?? ($status === ReportTicket::STATUS_DISMISSED ? 'Báo cáo vi phạm đã được quản trị viên kiểm tra và bỏ qua (không có vi phạm).' : 'Báo cáo vi phạm đã được quản trị viên xử lý.')
                    ));
                } catch (\Throwable $e) {
                    Log::warning('Không thể gửi thông báo xử lý report cho tác giả: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $status === ReportTicket::STATUS_RESOLVED ? 'Đã giải quyết tất cả báo cáo cho câu hỏi này.' : ($status === ReportTicket::STATUS_DISMISSED ? 'Đã bỏ qua các báo cáo.' : 'Đã cập nhật trạng thái báo cáo.'),
        ]);
    }

    /**
     * Thống kê số lượng báo cáo câu hỏi chờ xử lý
     */
    public function countPending()
    {
        $questionPending = ReportTicket::whereIn('status', ReportTicket::ACTIVE_STATUSES)->count();
        $uniqueQuestions = ReportTicket::whereIn('status', ReportTicket::ACTIVE_STATUSES)->distinct('question_id')->count('question_id');

        return response()->json([
            'success' => true,
            'count' => $questionPending,
            'question_pending' => $questionPending,
            'unique_questions_pending' => $uniqueQuestions,
        ]);
    }
}