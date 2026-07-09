<?php

namespace App\Http\Controllers;

use App\Models\ReportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $request->validate([
            'status' => 'required|in:resolved,dismissed',
        ]);

        $report = ReportTicket::findOrFail($id);
        $report->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái báo cáo thành công.',
            'data' => $report
        ]);
    }
}