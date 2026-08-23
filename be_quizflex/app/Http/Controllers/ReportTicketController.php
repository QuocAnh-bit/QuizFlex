<?php

namespace App\Http\Controllers;

use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuizModerated;
use App\Notifications\QuestionModerated;
use App\Notifications\ReportCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ReportTicketController extends Controller
{
    /**
     * Người dùng gửi báo cáo vi phạm bài Quiz hoặc Câu hỏi
     */
    public function store(Request $request)
    {
        $user = auth('api')->user() ?? $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để thực hiện gửi báo cáo.'
            ], 401);
        }

        $request->validate([
            'quiz_id' => 'required_without:question_id|nullable|exists:quizzes,id',
            'question_id' => 'required_without:quiz_id|nullable|exists:questions,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $questionSnapshot = null;
        if ($request->filled('question_id')) {
            $targetQ = \App\Models\Question::with(['answers', 'educationLevel', 'grade', 'subject'])->find($request->question_id);
            if ($targetQ) {
                $questionSnapshot = [
                    'id' => $targetQ->id,
                    'content' => $targetQ->content,
                    'image_url' => $targetQ->image_url,
                    'type' => $targetQ->type,
                    'difficulty' => $targetQ->difficulty ?? 'medium',
                    'education_level_name' => $targetQ->educationLevel?->name,
                    'grade_name' => $targetQ->grade?->name,
                    'subject_name' => $targetQ->subject?->name,
                    'topic_name' => $targetQ->topic_name,
                    'is_public' => (bool) $targetQ->is_public,
                    'created_at' => $targetQ->created_at?->toIso8601String(),
                    'answers' => $targetQ->answers->map(function ($ans, $index) {
                        return [
                            'id' => $ans->id,
                            'key' => chr(65 + ($ans->order ?? $index)),
                            'content' => $ans->content,
                            'text' => $ans->content,
                            'is_correct' => (bool) $ans->is_correct,
                        ];
                    })->values()->toArray(),
                ];
            }
        }

        $report = ReportTicket::create([
            'user_id' => $user->id,
            'quiz_id' => $request->quiz_id,
            'question_id' => $request->question_id,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
            'question_snapshot' => $questionSnapshot,
        ]);

        // 1. Gửi thông báo cho Admin
        $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
        if ($admins->isNotEmpty()) {
            try {
                Notification::send($admins, new ReportCreated($report, $user));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Không thể gửi thông báo cho Admin: ' . $e->getMessage());
            }
        }

        // 2. Gửi thông báo trực tiếp cho Tác giả của Câu hỏi hoặc Bài Quiz bị báo cáo
        $report->load(['question.user', 'question.quiz.user', 'quiz.user']);
        $reportReason = $report->reason . (!empty($report->description) ? " ({$report->description})" : "");

        if ($report->question) {
            $questionAuthor = $report->question->user ?? $report->question->quiz?->user;
            if ($questionAuthor && $questionAuthor->id !== $user->id) {
                try {
                    $questionAuthor->notify(new QuestionModerated($report->question, 'reported', $reportReason));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Không thể gửi thông báo cho tác giả câu hỏi: ' . $e->getMessage());
                }
            }
        }

        if ($report->quiz && $report->quiz->user) {
            if ($report->quiz->user->id !== $user->id) {
                try {
                    $report->quiz->user->notify(new QuizModerated($report->quiz, 'reported', $reportReason));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Không thể gửi thông báo cho tác giả quiz: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Báo cáo đã được gửi thành công và đang chờ quản trị viên xử lý.',
            'data' => $report
        ], 201);
    }

    /**
     * Lấy danh sách tất cả các báo cáo cho Admin Console (Quiz + Question)
     */
    public function index(Request $request)
    {
        $query = ReportTicket::with([
            'user:id,name,email,avatar',
            'quiz.user:id,name,email,avatar',
            'question.user:id,name,email,avatar',
            'question.answers',
            'question.educationLevel',
            'question.subject',
            'question.grade'
        ])->latest();

        if ($request->filled('type')) {
            $type = $request->query('type');
            if ($type === 'quiz') {
                $query->whereNotNull('quiz_id');
            } elseif ($type === 'question') {
                $query->whereNotNull('question_id');
            }
        }

        $reports = $query->get()->map(function ($r) {
            $data = $r->toArray();
            if ($r->question) {
                $q = $r->question;
                $hasUpdated = (bool) ($r->has_author_updated || ($q->updated_at && $q->updated_at > $r->created_at));
                $data['has_author_updated'] = $hasUpdated;
                $data['question']['author_name'] = $q->user?->name ?? $q->quiz?->user?->name ?? 'Vô danh';
                $data['question']['author_email'] = $q->user?->email ?? $q->quiz?->user?->email;
                $data['question']['author_avatar'] = $q->user?->avatar ?? $q->quiz?->user?->avatar;
                $data['question']['answers'] = $q->answers->map(function ($ans, $index) {
                    return [
                        'id' => $ans->id,
                        'key' => chr(65 + ($ans->order ?? $index)),
                        'content' => $ans->content,
                        'text' => $ans->content,
                        'is_correct' => (bool) $ans->is_correct,
                    ];
                })->values();
            } else {
                $data['has_author_updated'] = false;
            }
            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Admin cập nhật trạng thái báo cáo (resolved: Đã giải quyết / dismissed: Bác bỏ)
     */
    public function update(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,resolved,dismissed']);

        $report = ReportTicket::with(['quiz.user', 'question.user', 'question.quiz.user', 'user'])->findOrFail($id);
        
        $report->update(['status' => $request->status]);
        $reportReason = $report->reason . (!empty($report->description) ? " ({$report->description})" : "");

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
            $report->user->notify(new \App\Notifications\ReportResolved($report, $request->status, $action));
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái báo cáo thành công.',
        ]);
    }

    public function countPending()
    {
        $quizPending = ReportTicket::whereNotNull('quiz_id')->where('status', 'pending')->count();
        $questionPending = ReportTicket::whereNotNull('question_id')->where('status', 'pending')->count();
        $totalPending = ReportTicket::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'count' => $totalPending,
            'quiz_pending' => $quizPending,
            'question_pending' => $questionPending,
        ]);
    }
}