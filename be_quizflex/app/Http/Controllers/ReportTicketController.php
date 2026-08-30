<?php

namespace App\Http\Controllers;

use App\Models\ReportTicket;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportTicketController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Người dùng gửi báo cáo vi phạm Câu hỏi (Question Snapshot hoặc Public Question)
     */
    public function store(Request $request)
    {
        $user = $request->user('api') ?? auth('api')->setRequest($request)->user() ?? auth('api')->user() ?? $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để thực hiện gửi báo cáo.',
            ], 401);
        }

        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ], [
            'question_id.required' => 'Vui lòng cung cấp ID câu hỏi cần báo cáo.',
            'question_id.exists' => 'Câu hỏi không tồn tại trên hệ thống.',
            'reason.required' => 'Vui lòng chọn lý do báo cáo vi phạm.',
        ]);

        try {
            $report = $this->reportService->createReport(
                reporter: $user,
                questionId: (int) $request->question_id,
                reason: $request->reason,
                description: $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Báo cáo câu hỏi đã được gửi thành công.',
                'data' => $report,
            ], 201);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstMessage = collect($errors)->flatten()->first() ?? $e->getMessage();
            $statusCode = isset($errors['report']) ? 409 : (isset($errors['question']) ? 422 : 404);

            return response()->json([
                'success' => false,
                'message' => $firstMessage,
                'errors' => $errors,
            ], $statusCode);
        }
    }

    /**
     * Lấy danh sách báo cáo vi phạm do chính người dùng hiện tại gửi (My Reports)
     */
    public function userReports(Request $request)
    {
        $user = $request->user('api') ?? auth('api')->setRequest($request)->user() ?? auth('api')->user() ?? $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để xem lịch sử báo cáo.',
            ], 401);
        }

        $filters = [
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ];

        $result = $this->reportService->getUserReports($user, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['reports'],
            'reports' => $result['reports'],
            'stats' => $result['stats'],
        ]);
    }

    /**
     * Lấy danh sách tất cả các báo cáo câu hỏi (Audit Log & Moderation)
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->query('status'),
            'stage' => $request->query('stage'),
            'question_id' => $request->query('question_id'),
            'search' => $request->query('search'),
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', 10),
        ];

        $result = $this->reportService->getReportsIndex($filters);

        return response()->json([
            'success' => true,
            'data' => [
                'cases' => $result['cases'],
                'all_cases' => $result['all_cases'] ?? $result['cases'],
                'reports' => $result['reports'],
                'all_reports' => $result['all_reports'] ?? $result['reports'],
                'stats' => $result['stats'],
                'pagination' => $result['pagination'],
            ],
            'cases' => $result['cases'],
            'all_cases' => $result['all_cases'] ?? $result['cases'],
            'reports' => $result['reports'],
            'all_reports' => $result['all_reports'] ?? $result['reports'],
            'stats' => $result['stats'],
            'pagination' => $result['pagination'],
            'current_page' => $result['current_page'],
            'last_page' => $result['last_page'],
            'per_page' => $result['per_page'],
            'total' => $result['total'],
        ]);
    }

    /**
     * Lấy chi tiết một lượt báo cáo hoặc toàn bộ báo cáo của 1 câu hỏi
     */
    public function show($id)
    {
        $ticket = ReportTicket::with([
            'user:id,name,email,avatar',
            'resolver:id,name,email,avatar',
            'question.user:id,name,email,avatar',
            'question.subject',
            'question.grade',
            'question.educationLevel',
            'question.answers',
            'question.quizzes:id,title,is_public,status',
            'question.quiz:id,title,is_public,status',
            'question.reports.user:id,name,email,avatar',
            'question.reports.resolver:id,name,email,avatar',
        ])->find($id);

        if (!$ticket) {
            $ticket = ReportTicket::where('question_id', $id)->with([
                'user:id,name,email,avatar',
                'resolver:id,name,email,avatar',
                'question.user:id,name,email,avatar',
                'question.subject',
                'question.grade',
                'question.educationLevel',
                'question.answers',
                'question.quizzes:id,title,is_public,status',
                'question.quiz:id,title,is_public,status',
                'question.reports.user:id,name,email,avatar',
                'question.reports.resolver:id,name,email,avatar',
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
        $user = $request->user('api') ?? auth('api')->setRequest($request)->user() ?? auth('api')->user() ?? $request->user();

        try {
            $updatedTicket = $this->reportService->updateSingleTicketStatus(
                ticket: $ticket,
                targetStatus: $request->status,
                actor: $user,
                note: $request->admin_note
            );

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái báo cáo thành công.',
                'data' => $updatedTicket,
            ]);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return response()->json([
                'success' => false,
                'message' => $firstMessage,
            ], 422);
        }
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

        $admin = $request->user('api') ?? auth('api')->setRequest($request)->user() ?? auth('api')->user() ?? $request->user();

        try {
            $result = $this->reportService->resolveQuestionReports(
                questionId: (int) $request->question_id,
                status: $request->status,
                action: $request->action ?? 'keep',
                admin: $admin,
                adminNote: $request->admin_note
            );

            return response()->json($result);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return response()->json([
                'success' => false,
                'message' => $firstMessage,
            ], 422);
        }
    }

    /**
     * Thống kê số lượng báo cáo câu hỏi chờ xử lý
     */
    public function countPending()
    {
        $counts = $this->reportService->getPendingCount();

        return response()->json(array_merge(['success' => true], $counts));
    }
}