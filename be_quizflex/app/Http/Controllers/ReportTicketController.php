<?php

namespace App\Http\Controllers;

use App\Models\ReportTicket;
use App\Notifications\QuizModerated;
use App\Models\User;
use App\Notifications\ReportCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ReportTicketController extends Controller
{
    /**
     * Người dùng gửi báo cáo vi phạm bài Quiz
     */
    public function store(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $report = ReportTicket::create([
            'user_id' => Auth::id(), // Lấy ID của người dùng đang đăng nhập
            'quiz_id' => $request->quiz_id,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        $reporter = Auth::user();
        if ($reporter) {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ReportCreated($report, $reporter));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Báo cáo đã được gửi thành công và đang chờ quản trị viên xử lý.',
            'data' => $report
        ], 201);
    }

    /**
     * Lấy danh sách tất cả các báo cáo (Dành cho Admin Console)
     */
    public function index()
    {
        // Eager load quan hệ 'user' và 'quiz' để lấy luôn thông tin người báo cáo và bài quiz bị báo cáo
        $reports = ReportTicket::with(['user', 'quiz'])->latest()->get();
        
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
        $request->validate(['status' => 'required|in:resolved,dismissed']);

        $report = ReportTicket::with('quiz.user')->findOrFail($id);
        
        // Cập nhật trạng thái
        $report->update(['status' => $request->status]);

        // Gửi thông báo cho chủ quiz
        if ($report->quiz && $report->quiz->user) {
            $report->quiz->user->notify(new QuizModerated($report->quiz, $request->status));
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công và đã gửi thông báo cho chủ quiz.',
        ]);
    }
    /**
     * Admin yêu cầu chủ quiz tự chỉnh sửa nội dung bị báo cáo
     */
    public function requestFix($id)
    {
        $report = ReportTicket::with('quiz.user')->findOrFail($id);

        if ($report->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể yêu cầu sửa với báo cáo đang chờ xử lý.',
            ], 422);
        }

        $report->update(['status' => 'needs_fix']);

        if ($report->quiz && $report->quiz->user) {
            $report->quiz->user->notify(
                new QuizModerated($report->quiz, 'needs_fix', $report->reason)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu chỉnh sửa tới chủ quiz.',
        ]);
    }

    public function countPending()
    {
        $count = ReportTicket::where('status', 'pending')->count();
        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }
}