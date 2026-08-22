<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminRoomController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\LiveRoomController;
use App\Http\Controllers\RoomAssignmentController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\HomeworkRoomMemberEvaluationController;
use App\Http\Controllers\HomeworkSubmissionEvaluationController;
use App\Http\Controllers\UnlockRequestController;
use App\Services\AI\AIService;
use App\AI\Prompts\QuizPrompt;
use App\Http\Controllers\AIController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\AdminSubjectController;
use App\Http\Controllers\ReportTicketController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuizReviewController;

Route::get('/taxonomies/tree', [TaxonomyController::class, 'tree']);

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API test success',
        'data' => [
            'name' => 'QuizFlex',
            'version' => '1.1.0',
            'author' => 'QuizFlex Team',
        ],
    ]);
});



Route::get('/ai-direct', function () {

    $service = app(\App\Services\AI\AIService::class);

    return response()->json(
        $service->generateQuiz(
            'Toán lớp 10',
            5
        )
    );
});

Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/auth/forgot-password/send-otp', [AuthController::class, 'forgotPasswordSendOtp']);
Route::post('/auth/forgot-password/reset', [AuthController::class, 'forgotPasswordReset']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/locked-info', [AuthController::class, 'lockedInfo']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/ai/generate', [AIController::class, 'generate']);
    Route::post('/ai/generate-quiz', [AIController::class, 'generate']);
    Route::post('/ocr/scan', [OcrController::class, 'scan']);
    Route::post('/orc/ai/quiz-suggestions', [OcrController::class, 'suggest']);
    Route::post('/orc/ai/review', [OcrController::class, 'review']);

    Route::post('/payments/activate-trial', [PaymentController::class, 'activateTrial']);
    Route::get('/payments/upgrade-costs', [PaymentController::class, 'getUpgradeCosts']);
    Route::post('/unlock-requests', [UnlockRequestController::class, 'store']);
    Route::get('/unlock-requests/latest', [UnlockRequestController::class, 'latest']);
    Route::get('/ai/jobs/{jobId}', [AIController::class, 'status'])->whereUuid('jobId');
    Route::get('/ai/quiz-status/{jobId}', [AIController::class, 'status'])->whereUuid('jobId');
    Route::get('/ai/logs/{id}', [AIController::class, 'show']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

    // Kho câu hỏi cá nhân & Thùng rác câu hỏi (My Questions Repository)
    Route::post('/questions', [QuestionController::class, 'storeQuestion']);
    Route::get('/user/my-questions', [QuestionController::class, 'userBank']);
    Route::get('/user/my-questions/trash', [QuestionController::class, 'userTrash']);
    Route::put('/user/my-questions/{id}', [QuestionController::class, 'updateQuestion']);
    Route::delete('/user/my-questions/{id}', [QuestionController::class, 'softDeleteQuestion']);
    Route::post('/user/my-questions/{id}/restore', [QuestionController::class, 'restoreQuestion']);
    Route::delete('/user/my-questions/{id}/force', [QuestionController::class, 'forceDeleteQuestion']);
    Route::post('/user/my-questions/{id}/submit-to-bank', [QuestionController::class, 'submitToBank']);
    Route::post('/user/my-questions/bulk-submit-to-bank', [QuestionController::class, 'bulkSubmitToBank']);
    Route::get('/user/my-questions/{id}/review-history', [QuestionController::class, 'questionReviewHistory']);

    // Yêu cầu duyệt công khai bài Quiz (User)
    Route::post('/quizzes/{id}/request-review', [QuizReviewController::class, 'requestReview']);
    Route::get('/quizzes/{id}/review-history', [QuizReviewController::class, 'quizReviewHistory']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard/overview', [AdminDashboardController::class, 'overview']);
        Route::get('/admin/rooms/stats', [AdminRoomController::class, 'stats']);
        Route::get('/admin/rooms/homework', [AdminRoomController::class, 'homeworkIndex']);
        Route::get('/admin/rooms/homework/trash', [AdminRoomController::class, 'homeworkTrash']);
        Route::delete('/admin/rooms/homework/{room}', [AdminRoomController::class, 'softDeleteHomework'])->withTrashed();
        Route::delete('/admin/rooms/homework/{id}/force', [AdminRoomController::class, 'forceDeleteHomework']);
        Route::patch('/admin/rooms/homework/{id}/restore', [AdminRoomController::class, 'restoreHomework']);
        Route::get('/admin/rooms/homework/{room}', [AdminRoomController::class, 'homeworkShow'])->withTrashed();
        Route::get('/admin/rooms/live', [AdminRoomController::class, 'liveIndex']);
        Route::get('/admin/rooms/live/trash', [AdminRoomController::class, 'liveTrash']);
        Route::delete('/admin/rooms/live/{liveRoom}', [AdminRoomController::class, 'softDeleteLive'])->withTrashed();
        Route::delete('/admin/rooms/live/{id}/force', [AdminRoomController::class, 'forceDeleteLive']);
        Route::patch('/admin/rooms/live/{id}/restore', [AdminRoomController::class, 'restoreLive']);
        Route::get('/admin/rooms/live/{liveRoom}', [AdminRoomController::class, 'liveShow'])->withTrashed();
        Route::post('/admin/rooms/homework/{room}/ban', [AdminRoomController::class, 'banHomework']);
        Route::post('/admin/rooms/homework/{room}/unban', [AdminRoomController::class, 'unbanHomework']);
        Route::post('/admin/rooms/live/{liveRoom}/ban', [AdminRoomController::class, 'banLive']);
        Route::post('/admin/rooms/live/{liveRoom}/unban', [AdminRoomController::class, 'unbanLive']);
        Route::get('/users/trashed', [UserController::class, 'trashed']);
        Route::patch('/users/{id}/restore', [UserController::class, 'restore']);
        Route::delete('/users/{id}/force', [UserController::class, 'forceDelete']);
        Route::post('/admin/users/{user}/lock', [UserController::class, 'lock']);
        Route::post('/admin/users/{user}/unlock', [UserController::class, 'unlock']);
        Route::get('/admin/unlock-requests/pending-count', [UnlockRequestController::class, 'pendingCount']);
        Route::get('/admin/unlock-requests', [UnlockRequestController::class, 'index']);
        Route::get('/admin/unlock-requests/{unlockRequest}', [UnlockRequestController::class, 'show']);
        Route::post('/admin/unlock-requests/{unlockRequest}/approve', [UnlockRequestController::class, 'approve']);
        Route::post('/admin/unlock-requests/{unlockRequest}/reject', [UnlockRequestController::class, 'reject']);
        Route::apiResource('users', UserController::class);

        Route::get('/admin/report-tickets/count', [ReportTicketController::class, 'countPending']);

        // Quản lý báo cáo vi phạm cho admin
        Route::get('/admin/report-tickets', [ReportTicketController::class, 'index']);
        Route::put('/admin/report-tickets/{id}', [ReportTicketController::class, 'update']);

        // Quản lý ngân hàng câu hỏi toàn hệ thống cho admin
        Route::get('/admin/questions-management', [QuestionController::class, 'adminIndex']);
        Route::get('/admin/questions-trash', [QuestionController::class, 'adminTrash']);
        Route::post('/admin/questions/{id}/restore', [QuestionController::class, 'adminRestore']);
        Route::delete('/admin/questions/{id}/force-delete', [QuestionController::class, 'adminForceDelete']);
        Route::post('/admin/questions/bulk-restore', [QuestionController::class, 'adminBulkRestore']);
        Route::post('/admin/questions/bulk-force-delete', [QuestionController::class, 'adminBulkForceDelete']);
        Route::get('/admin/questions/{id}', [QuestionController::class, 'adminShow']);
        Route::put('/admin/questions/{id}', [QuestionController::class, 'adminUpdate']);
        Route::delete('/admin/questions/{id}', [QuestionController::class, 'adminDelete']);
        Route::patch('/admin/questions/{id}/toggle-visibility', [QuestionController::class, 'adminToggleVisibility']);
        Route::post('/admin/questions/bulk-visibility', [QuestionController::class, 'adminBulkToggleVisibility']);
        Route::post('/admin/questions/bulk-delete', [QuestionController::class, 'adminBulkDelete']);

        // Quản lý yêu cầu duyệt câu hỏi vào Ngân hàng cho admin
        Route::get('/admin/question-bank-requests', [QuestionController::class, 'adminBankRequests']);
        Route::get('/admin/question-bank-requests/{id}', [QuestionController::class, 'adminShowBankRequest']);
        Route::post('/admin/question-bank-requests/{id}/approve', [QuestionController::class, 'adminApproveBankRequest']);
        Route::post('/admin/question-bank-requests/{id}/reject', [QuestionController::class, 'adminRejectBankRequest']);
        Route::post('/admin/question-bank-requests/bulk-approve', [QuestionController::class, 'adminBulkApproveBankRequests']);
        Route::post('/admin/question-bank-requests/bulk-reject', [QuestionController::class, 'adminBulkRejectBankRequests']);

        // Quản lý yêu cầu duyệt Quiz công khai cho Admin
        Route::get('/admin/quiz-review-requests', [QuizReviewController::class, 'adminIndex']);
        Route::get('/admin/quiz-review-requests/{id}', [QuizReviewController::class, 'adminShow']);
        Route::post('/admin/quiz-review-requests/{id}/approve', [QuizReviewController::class, 'adminApprove']);
        Route::post('/admin/quiz-review-requests/{id}/reject', [QuizReviewController::class, 'adminReject']);
        Route::post('/admin/quiz-review-requests/bulk-approve', [QuizReviewController::class, 'adminBulkApprove']);
        Route::post('/admin/quiz-review-requests/bulk-reject', [QuizReviewController::class, 'adminBulkReject']);

        // Quản lý bộ môn (Subjects) cho admin
        Route::get('/admin/subjects', [AdminSubjectController::class, 'index']);
        Route::get('/admin/subjects/trash', [AdminSubjectController::class, 'trash']);
        Route::post('/admin/subjects', [AdminSubjectController::class, 'store']);
        Route::get('/admin/subjects/{id}', [AdminSubjectController::class, 'show']);
        Route::put('/admin/subjects/{id}', [AdminSubjectController::class, 'update']);
        Route::delete('/admin/subjects/{id}', [AdminSubjectController::class, 'destroy']);
        Route::post('/admin/subjects/{id}/restore', [AdminSubjectController::class, 'restore']);
        Route::delete('/admin/subjects/{id}/force-delete', [AdminSubjectController::class, 'forceDelete']);

        // Quản lý quiz cho admin
        Route::middleware('role:admin')->group(function () {

            Route::get(
                '/admin/quizzes/trash',
                [QuizController::class, 'adminTrash']
            );

            Route::get('/admin/quizzes', [QuizController::class, 'adminIndex']);

            Route::get('/admin/quizzes/{id}', [QuizController::class, 'adminShow']);

            Route::delete('/admin/quizzes/{quiz}', [QuizController::class, 'destroy'])
                ->withTrashed();

            Route::patch(
                '/admin/quizzes/{id}/toggle-visibility',
                [QuizController::class, 'toggleVisibility']
            );

            Route::post(
                '/admin/quizzes/{id}/restore',
                [QuizController::class, 'restore']
            );

            Route::delete(
                '/admin/quizzes/{id}/force-delete',
                [QuizController::class, 'forceDelete']
            );
        });
    });

    Route::middleware('role:free,plus,pro,ultra,admin')->group(function () {
        // Gửi báo cáo vi phạm
        Route::post('/report-tickets', [ReportTicketController::class, 'store']);

        // Protected Payment Routes
        Route::get('/payments/history', [PaymentController::class, 'history']);

        // Protected Quiz Routes
        Route::post('/quizzes', [QuizController::class, 'store']);
        Route::get('/quizzes/{quiz}/edit-data', [QuizController::class, 'editData']);
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update']);
        Route::patch('/quizzes/{quiz}', [QuizController::class, 'update']);
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);
        Route::post('/ocr/import-quiz', [OcrController::class, 'importQuiz']);
        // User chỉ quản lý quiz của mình
        Route::get('/quizzes/trash', [QuizController::class, 'trash']);
        Route::patch('/quizzes/{id}/restore', [QuizController::class, 'restore']);
        Route::delete('/quizzes/{id}/force-delete', [QuizController::class, 'forceDelete']);

        // Protected Question & Answer Routes
        Route::post('/quizzes/{quiz}/questions', [QuestionController::class, 'store']);
        Route::put('/questions/{question}', [QuestionController::class, 'update']);
        Route::patch('/questions/{question}', [QuestionController::class, 'update']);
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);
        Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);
        Route::put('/answers/{answer}', [AnswerController::class, 'update']);
        Route::patch('/answers/{answer}', [AnswerController::class, 'update']);
        Route::delete('/answers/{answer}', [AnswerController::class, 'destroy']);

        // Protected Quiz Attempt Routes
        Route::get('/quiz-attempts', [QuizAttemptController::class, 'index']);
        Route::get('/quiz-attempts/{quizAttempt}', [QuizAttemptController::class, 'show']);
        Route::post('/quizzes/{quiz}/attempts/start', [QuizAttemptController::class, 'start']);
        Route::post('/quizzes/{quiz}/attempts/check-answer', [QuizAttemptController::class, 'checkAnswer']);
        Route::post('/quizzes/{quiz}/attempts/submit', [QuizAttemptController::class, 'submit']);


        // Room Homework Routes
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::post('/rooms/join', [RoomController::class, 'joinByCode']);
        Route::get('/rooms/{room}', [RoomController::class, 'show'])->withTrashed();
        Route::patch('/rooms/{room}', [RoomController::class, 'update']);
        Route::post('/rooms/{room}/join', [RoomController::class, 'joinRoom']);
        Route::post('/rooms/{room}/leave', [RoomController::class, 'leave']);
        Route::delete('/rooms/{room}/dissolve', [RoomController::class, 'dissolve']);
        Route::get('/rooms/{room}/members', [RoomController::class, 'members']);
        Route::delete('/rooms/{room}/members/{member}', [RoomController::class, 'destroyMember']);
        Route::post('/rooms/{room}/members/{member}/approve', [RoomController::class, 'approveMember']);
        Route::post('/rooms/{room}/members/{member}/reject', [RoomController::class, 'rejectMember']);
        Route::get('/homework-rooms/{room}/allowed-members', [RoomController::class, 'allowedMembers']);
        Route::post('/homework-rooms/{room}/allowed-members', [RoomController::class, 'storeAllowedMembers']);
        Route::delete('/homework-rooms/{room}/allowed-members', [RoomController::class, 'destroyAllowedMembersBatch']);
        Route::delete('/homework-rooms/{room}/allowed-members/{allowedMember}', [RoomController::class, 'destroyAllowedMember']);
        Route::get('/homework-rooms/{room}/members/{user}/evaluation', [HomeworkRoomMemberEvaluationController::class, 'show']);
        Route::post('/homework-rooms/{room}/members/{user}/evaluation', [HomeworkRoomMemberEvaluationController::class, 'store']);
        Route::get('/homework-rooms/{room}/submissions/{submission}/evaluation', [HomeworkSubmissionEvaluationController::class, 'show']);
        Route::post('/homework-rooms/{room}/submissions/{submission}/evaluation', [HomeworkSubmissionEvaluationController::class, 'store']);

        Route::get('/rooms/{room}/gradebook', [RoomAssignmentController::class, 'gradebook']);
        Route::get('/rooms/{room}/assignments', [RoomAssignmentController::class, 'index']);
        Route::post('/rooms/{room}/assignments', [RoomAssignmentController::class, 'store']);
        Route::get('/room-assignments/{assignment}', [RoomAssignmentController::class, 'show']);
        Route::post('/room-assignments/{assignment}/attempts/start', [RoomAssignmentController::class, 'startAttempt']);
        Route::post('/room-assignments/{assignment}/attempts/{attempt}/answer', [RoomAssignmentController::class, 'answer']);
        Route::post('/room-assignments/{assignment}/attempts/{attempt}/submit', [RoomAssignmentController::class, 'submitAttempt']);
        Route::get('/room-assignments/{assignment}/attempts', [RoomAssignmentController::class, 'attempts']);
        Route::post('/room-assignments/{assignment}/attempts/{attempt}/reset', [RoomAssignmentController::class, 'resetAttempt']);

        // Live Room Routes
        Route::post('/live-rooms', [LiveRoomController::class, 'store']);
        Route::post('/live-rooms/join', [LiveRoomController::class, 'join']);
        Route::get('/live-rooms/{liveRoom}', [LiveRoomController::class, 'show']);
        Route::post('/live-rooms/{liveRoom}/start', [LiveRoomController::class, 'start']);
        Route::get('/live-rooms/{liveRoom}/current-question', [LiveRoomController::class, 'currentQuestion']);
        Route::post('/live-rooms/{liveRoom}/answer', [LiveRoomController::class, 'answer']);
        Route::post('/live-rooms/{liveRoom}/next-question', [LiveRoomController::class, 'nextQuestion']);
        Route::post('/live-rooms/{liveRoom}/finish', [LiveRoomController::class, 'finish']);
        Route::get('/live-rooms/{liveRoom}/leaderboard', [LiveRoomController::class, 'leaderboard']);
    });
});

// Payment checkout creation. The controller still checks auth('api')->user(),
// so unauthenticated requests return 401 instead of a confusing 404.
Route::post('/payments/create', [PaymentController::class, 'create']);

// Public Webhooks & Callbacks for Payments
Route::post('/payments/webhook/momo', [PaymentController::class, 'webhookMomo']);
Route::get('/payments/callback', [PaymentController::class, 'callback']);
Route::get('/payments/check-status/{orderCode}', [PaymentController::class, 'checkStatus']);

// Public Quiz & Question Bank Routes
Route::get('/quizzes', [QuizController::class, 'index']);
Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
Route::get('/quizzes/{quiz}/questions', [QuestionController::class, 'index']);
Route::get('/questions/bank', [QuestionController::class, 'bank']);
Route::get('/questions/topics', [QuestionController::class, 'topics']);
Route::get('/questions/stats', [QuestionController::class, 'stats']);
Route::post('/quizzes/from-bank', [QuestionController::class, 'createQuizFromBank']);
Route::get('/questions/{question}', [QuestionController::class, 'show']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\GamificationController;

// Public routes - không cần đăng nhập
Route::get('/badges', [GamificationController::class, 'badges']);
Route::get('/leaderboard', [GamificationController::class, 'leaderboard']);

// Protected routes - cần đăng nhập
Route::middleware('auth:api')->group(function () {
    Route::get('/user/stats', [GamificationController::class, 'getUserStats']);
    Route::post('/user/xp/add', [GamificationController::class, 'addXp']);
    Route::post('/quiz/start', [QuizAttemptController::class, 'startGamified']);
    Route::post('/quiz/{id}/submit', [QuizAttemptController::class, 'submitGamified']);
    Route::get('/quiz/history', [QuizAttemptController::class, 'history']);
});
