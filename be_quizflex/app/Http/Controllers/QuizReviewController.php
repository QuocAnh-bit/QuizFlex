<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizReviewRequest;
use App\Services\QuizReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizReviewController extends Controller
{
    public function __construct(
        protected QuizReviewService $reviewService
    ) {}

    /**
     * User: Gửi yêu cầu duyệt công khai cho bài Quiz của mình
     */
    public function requestReview(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $quiz = Quiz::with('questions')->findOrFail($id);

        if (Gate::forUser($user)->denies('requestReview', $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền gửi yêu cầu duyệt bài Quiz này hoặc bài Quiz đang trong quá trình xét duyệt.',
            ], 403);
        }

        $requestNote = $request->input('request_note') ?? $request->input('note');
        $reviewRequest = $this->reviewService->requestReview($quiz, $user, $requestNote);

        $quizController = new QuizController();

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu công khai bài Quiz thành công! Admin sẽ xem xét và phản hồi sớm nhất.',
            'data' => [
                'quiz' => $quizController->formatQuiz($quiz->fresh(['user', 'educationLevel', 'grade', 'subject'])),
                'review_request' => $reviewRequest,
            ],
        ], 201);
    }

    /**
     * User / Admin: Xem lịch sử các lần gửi duyệt của 1 Quiz
     */
    public function quizReviewHistory($id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $quiz = Quiz::findOrFail($id);
        $isAdmin = strtolower($user->role ?? '') === 'admin';

        if (!$isAdmin && $quiz->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem thông tin này.'], 403);
        }

        $history = QuizReviewRequest::where('quiz_id', $quiz->id)
            ->with(['user:id,name,email,avatar', 'reviewer:id,name,email,avatar'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Admin: Lấy danh sách các yêu cầu duyệt Quiz công khai
     */
    public function adminIndex(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = QuizReviewRequest::query()
            ->with([
                'quiz' => fn($q) => $q->withCount('questions')->with(['educationLevel', 'grade', 'subject']),
                'user:id,name,email,avatar',
                'reviewer:id,name,email,avatar',
            ]);

        $status = $request->query('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('quiz', fn($qz) => $qz->where('title', 'like', "%{$keyword}%"))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%"));
            });
        }

        if ($request->filled('subject_id')) {
            $query->whereHas('quiz', fn($qz) => $qz->where('subject_id', $request->query('subject_id')));
        }

        if ($request->filled('grade_id')) {
            $query->whereHas('quiz', fn($qz) => $qz->where('grade_id', $request->query('grade_id')));
        }

        $pendingCount = QuizReviewRequest::where('status', 'pending')->count();
        $approvedCount = QuizReviewRequest::where('status', 'approved')->count();
        $rejectedCount = QuizReviewRequest::where('status', 'rejected')->count();

        $query->latest();
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Danh sách yêu cầu duyệt công khai Quiz',
            'data' => [
                'items' => $paginated->items(),
                'total' => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'stats' => [
                    'pending' => $pendingCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                    'total' => $pendingCount + $approvedCount + $rejectedCount,
                ],
            ],
        ]);
    }

    /**
     * Admin: Xem chi tiết 1 yêu cầu duyệt Quiz
     */
    public function adminShow($id)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $reviewRequest = QuizReviewRequest::with([
            'quiz.user:id,name,email,avatar',
            'quiz.educationLevel',
            'quiz.grade',
            'quiz.subject',
            'quiz.questions.answers',
            'quiz.questions.user:id,name,email',
            'user:id,name,email,avatar',
            'reviewer:id,name,email,avatar',
        ])->findOrFail($id);

        $quizController = new QuizController();
        $formattedQuiz = $quizController->formatQuiz($reviewRequest->quiz, true);

        // Đánh dấu nguồn gốc của từng câu hỏi (Từ Ngân hàng hay từ Kho của User)
        if (isset($formattedQuiz['questions'])) {
            $formattedQuiz['questions'] = collect($formattedQuiz['questions'])->map(function ($q) {
                $qModel = $q instanceof \App\Models\Question ? $q : \App\Models\Question::find($q['id']);
                $q['source'] = ($qModel && $qModel->is_public) ? 'public_bank' : 'my_bank';
                return $q;
            })->all();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'review_request' => $reviewRequest,
                'quiz' => $formattedQuiz,
            ],
        ]);
    }

    /**
     * Admin: Phê duyệt 1 Quiz
     */
    public function adminApprove(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // $id có thể là review_request_id hoặc quiz_id
        $reviewRequest = QuizReviewRequest::find($id);
        if ($reviewRequest) {
            $quiz = $reviewRequest->quiz;
        } else {
            $quiz = Quiz::findOrFail($id);
        }

        $result = $this->reviewService->approveQuiz($quiz, $user);

        return response()->json([
            'success' => true,
            'message' => "Đã phê duyệt và công khai bài Quiz '{$quiz->title}' thành công!",
            'data' => $result,
        ]);
    }

    /**
     * Admin: Từ chối duyệt 1 Quiz
     */
    public function adminReject(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = trim((string) ($request->input('reason') ?? $request->input('note') ?? ''));
        if ($reason === '') {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập lý do từ chối kiểm duyệt bài Quiz.',
            ], 422);
        }

        // $id có thể là review_request_id hoặc quiz_id
        $reviewRequest = QuizReviewRequest::find($id);
        if ($reviewRequest) {
            $quiz = $reviewRequest->quiz;
        } else {
            $quiz = Quiz::findOrFail($id);
        }

        $result = $this->reviewService->rejectQuiz($quiz, $user, $reason);

        return response()->json([
            'success' => true,
            'message' => "Đã từ chối duyệt bài Quiz '{$quiz->title}'.",
            'data' => $result,
        ]);
    }

    /**
     * Admin: Phê duyệt hàng loạt
     */
    public function adminBulkApprove(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');
        $approvedCount = 0;

        foreach ($ids as $id) {
            $reviewReq = QuizReviewRequest::find($id);
            $quiz = $reviewReq ? $reviewReq->quiz : Quiz::find($id);
            if ($quiz) {
                $this->reviewService->approveQuiz($quiz, $user);
                $approvedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã phê duyệt {$approvedCount} bài Quiz thành công!",
        ]);
    }

    /**
     * Admin: Từ chối hàng loạt
     */
    public function adminBulkReject(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'reason' => 'nullable|string|max:1000',
            'note' => 'nullable|string|max:1000',
        ]);

        $reason = trim((string) ($request->input('reason') ?? $request->input('note') ?? 'Nội dung chưa đạt tiêu chuẩn công khai'));
        $ids = $request->input('ids');
        $rejectedCount = 0;

        foreach ($ids as $id) {
            $reviewReq = QuizReviewRequest::find($id);
            $quiz = $reviewReq ? $reviewReq->quiz : Quiz::find($id);
            if ($quiz) {
                $this->reviewService->rejectQuiz($quiz, $user, $reason);
                $rejectedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã từ chối duyệt {$rejectedCount} bài Quiz.",
        ]);
    }
}
