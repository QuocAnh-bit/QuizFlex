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

        $quiz = Quiz::with('questions.answers')->findOrFail($id);

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
                'review_request' => $this->reviewService->formatRevision($reviewRequest),
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

        if (!$isAdmin && (int) $quiz->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem thông tin này.'], 403);
        }

        $diffData = $this->reviewService->getReviewDetailsWithDiff($quiz);

        return response()->json([
            'success' => true,
            'data' => $diffData['history'],
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
            $cleanKeyword = ltrim($keyword, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($keyword, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('quiz_id', $numericId)
                      ->orWhere('snapshot_title', 'like', "%{$keyword}%")
                      ->orWhereHas('quiz', fn($qz) => $qz->where('title', 'like', "%{$keyword}%"))
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
                } else {
                    $q->where('snapshot_title', 'like', "%{$keyword}%")
                      ->orWhereHas('quiz', fn($qz) => $qz->where('title', 'like', "%{$keyword}%"))
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
                }
            });
        }

        if ($request->filled('subject_id')) {
            $subjectId = $request->query('subject_id');
            $query->where(function ($q) use ($subjectId) {
                $q->where('snapshot_subject_id', $subjectId)
                  ->orWhereHas('quiz', fn($qz) => $qz->where('subject_id', $subjectId));
            });
        }

        if ($request->filled('grade_id')) {
            $gradeId = $request->query('grade_id');
            $query->where(function ($q) use ($gradeId) {
                $q->where('snapshot_grade_id', $gradeId)
                  ->orWhereHas('quiz', fn($qz) => $qz->where('grade_id', $gradeId));
            });
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
     * Admin: Xem chi tiết 1 yêu cầu duyệt Quiz kèm Diff (Current vs Previous)
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
        ])->find($id);

        if (!$reviewRequest) {
            $quiz = Quiz::with([
                'user:id,name,email,avatar',
                'educationLevel',
                'grade',
                'subject',
                'questions.answers',
                'questions.user:id,name,email',
            ])->findOrFail($id);
            $reviewRequest = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();
        }

        if (!$reviewRequest) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy yêu cầu duyệt.'], 404);
        }

        $diffData = $this->reviewService->getReviewDetailsWithDiff($reviewRequest);

        $quizController = new QuizController();
        $formattedQuiz = $reviewRequest->quiz ? $quizController->formatQuiz($reviewRequest->quiz, true) : null;

        if ($formattedQuiz && isset($formattedQuiz['questions'])) {
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
                'current_revision' => $diffData['current_revision'],
                'previous_revision' => $diffData['previous_revision'],
                'previous_rejection_reason' => $diffData['previous_rejection_reason'],
                'diff' => $diffData['diff'],
                'history' => $diffData['history'],
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

        $reviewRequest = QuizReviewRequest::find($id);
        if ($reviewRequest) {
            $target = $reviewRequest;
        } else {
            $target = Quiz::findOrFail($id);
        }

        $result = $this->reviewService->approveQuiz($target, $user);

        return response()->json([
            'success' => true,
            'message' => "Đã phê duyệt và công khai bài Quiz thành công!",
            'data' => [
                'review_request' => $this->reviewService->formatRevision($result),
            ],
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

        $reviewRequest = QuizReviewRequest::find($id);
        if ($reviewRequest) {
            $target = $reviewRequest;
        } else {
            $target = Quiz::findOrFail($id);
        }

        $result = $this->reviewService->rejectQuiz($target, $user, $reason);

        return response()->json([
            'success' => true,
            'message' => "Đã từ chối duyệt bài Quiz.",
            'data' => [
                'review_request' => $this->reviewService->formatRevision($result),
            ],
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
            $target = $reviewReq ?: Quiz::find($id);
            if ($target) {
                $this->reviewService->approveQuiz($target, $user);
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
            $target = $reviewReq ?: Quiz::find($id);
            if ($target) {
                $this->reviewService->rejectQuiz($target, $user, $reason);
                $rejectedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Đã từ chối duyệt {$rejectedCount} bài Quiz.",
        ]);
    }
}
