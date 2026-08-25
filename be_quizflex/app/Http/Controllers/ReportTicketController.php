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
            ->whereIn('status', ['pending', 'author_updated', 'admin_review_required'])
            ->first();

        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã báo cáo câu hỏi này và báo cáo đang được xử lý.',
            ], 409);
        }

        // Xác định trạng thái ban đầu: Ưu tiên đưa vào hàng đợi Admin nếu lý do nghiêm trọng hoặc tích lũy nhiều report (>= 3 reports)
        $reasonText = trim($request->reason);
        $isCriticalReason = (bool) preg_match('/(nhạy cảm|xúc phạm|bản quyền|chính sách|nghiêm trọng|phản động|khiêu dâm)/ui', $reasonText);

        $unresolvedCount = ReportTicket::where('question_id', $originQuestion->id)
            ->whereIn('status', ['pending', 'author_updated', 'admin_review_required'])
            ->count();

        // Ngưỡng: nếu đã có >= 2 báo cáo chưa xử lý (thêm report mới này là >= 3) hoặc lý do nghiêm trọng
        $initialStatus = ($isCriticalReason || $unresolvedCount >= 2) ? 'admin_review_required' : 'pending';

        // Tạo bản ghi ReportTicket gắn trực tiếp với Question gốc
        $report = ReportTicket::create([
            'user_id' => $user->id,
            'question_id' => $originQuestion->id,
            'reason' => $reasonText,
            'description' => $request->filled('description') ? trim($request->description) : null,
            'status' => $initialStatus,
        ]);

        // Nếu vượt ngưỡng >= 3 reports, đồng bộ tất cả report pending trước đó của câu hỏi sang admin_review_required
        if ($unresolvedCount >= 2) {
            ReportTicket::where('question_id', $originQuestion->id)
                ->where('status', 'pending')
                ->update(['status' => 'admin_review_required']);
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
        $query = ReportTicket::with([
            'user:id,name,email,avatar',
            'question.user:id,name,email,avatar',
            'question.subject',
            'question.grade',
            'question.educationLevel',
            'question.answers',
            'question.quizzes:id,title,is_public,status',
            'question.quiz:id,title,is_public,status',
            'question.reports.user:id,name,email,avatar'
        ])->latest();

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('question_id')) {
            $query->where('question_id', $request->query('question_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('question_id', $search)
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('question', function ($qq) use ($search) {
                      $qq->where('content', 'like', "%{$search}%");
                  });
            });
        }

        $reports = $query->get();

        // Calculate KPI stats
        $stats = [
            'total' => ReportTicket::count(),
            'pending' => ReportTicket::where('status', 'pending')->count(),
            'author_updated' => ReportTicket::where('status', 'author_updated')->count(),
            'admin_review_required' => ReportTicket::where('status', 'admin_review_required')->count(),
            'resolved' => ReportTicket::where('status', 'resolved')->count(),
            'dismissed' => ReportTicket::where('status', 'dismissed')->count(),
            'questions_count' => ReportTicket::whereIn('status', ['pending', 'author_updated', 'admin_review_required'])->distinct('question_id')->count('question_id'),
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
            'status' => 'required|in:pending,author_updated,admin_review_required,resolved,dismissed',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $ticket = ReportTicket::findOrFail($id);
        $ticket->status = $request->status;
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
            'status' => 'required|in:resolved,dismissed,admin_review_required',
            'action' => 'nullable|in:keep,hide,delete',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $questionId = $request->question_id;
        $status = $request->status;
        $action = $request->action ?? 'keep';

        // 1. Cập nhật tất cả các ticket chưa giải quyết của câu hỏi này
        ReportTicket::where('question_id', $questionId)
            ->whereIn('status', ['pending', 'author_updated', 'admin_review_required'])
            ->update(['status' => $status]);

        // 2. Thực hiện hành động nếu có
        $question = Question::find($questionId);
        if ($question) {
            if ($action === 'hide') {
                $question->is_public = false;
                $question->save();
            } elseif ($action === 'delete') {
                $question->delete();
            }

            // Thông báo cho tác giả nếu được giải quyết / xử lý
            if ($status === 'resolved' && $question->user) {
                try {
                    $question->user->notify(new QuestionModerated(
                        $question,
                        $action === 'delete' ? 'deleted' : ($action === 'hide' ? 'hidden' : 'resolved'),
                        $request->admin_note ?? 'Báo cáo vi phạm đã được quản trị viên xử lý.'
                    ));
                } catch (\Throwable $e) {
                    Log::warning('Không thể gửi thông báo xử lý report cho tác giả: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $status === 'resolved' ? 'Đã giải quyết tất cả báo cáo cho câu hỏi này.' : ($status === 'dismissed' ? 'Đã bỏ qua các báo cáo.' : 'Đã cập nhật trạng thái báo cáo.'),
        ]);
    }

    /**
     * Thống kê số lượng báo cáo câu hỏi chờ xử lý
     */
    public function countPending()
    {
        $questionPending = ReportTicket::whereIn('status', ['pending', 'author_updated', 'admin_review_required'])->count();
        $uniqueQuestions = ReportTicket::whereIn('status', ['pending', 'author_updated', 'admin_review_required'])->distinct('question_id')->count('question_id');

        return response()->json([
            'success' => true,
            'count' => $questionPending,
            'question_pending' => $questionPending,
            'unique_questions_pending' => $uniqueQuestions,
        ]);
    }
}