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
        $request->validate([
            'quiz_id' => 'required_without:question_id|nullable|exists:quizzes,id',
            'question_id' => 'required_without:quiz_id|nullable|exists:questions,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $report = ReportTicket::create([
            'user_id' => Auth::id(),
            'quiz_id' => $request->quiz_id,
            'question_id' => $request->question_id,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        $reporter = Auth::user();
        if ($reporter) {
            // 1. Gửi thông báo cho Admin
            $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ReportCreated($report, $reporter));
            }
        }

        // 2. Gửi thông báo trực tiếp cho Tác giả của Câu hỏi hoặc Bài Quiz bị báo cáo
        $report->load(['question.user', 'question.quiz.user', 'quiz.user']);

        if ($report->question) {
            $questionAuthor = $report->question->user ?? $report->question->quiz?->user;
            if ($questionAuthor && $questionAuthor->id !== Auth::id()) {
                $questionAuthor->notify(new QuestionModerated($report->question, 'reported', $report->reason));
            }
        }

        if ($report->quiz && $report->quiz->user) {
            if ($report->quiz->user->id !== Auth::id()) {
                $report->quiz->user->notify(new QuizModerated($report->quiz, 'reported', $report->reason));
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
            'quiz.user:id,name,email',
            'question.user:id,name,email',
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

        $reports = $query->get();

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

        // Gửi thông báo cho tác giả của Quiz nếu có
        if ($report->quiz && $report->quiz->user) {
            $report->quiz->user->notify(new QuizModerated($report->quiz, $request->status, $report->reason));
        }

        // Gửi thông báo cho tác giả của Câu hỏi nếu có
        if ($report->question) {
            $questionAuthor = $report->question->user ?? $report->question->quiz?->user;
            if ($questionAuthor) {
                $questionAuthor->notify(new QuestionModerated($report->question, $request->status, $report->reason));
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