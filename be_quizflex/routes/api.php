<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LiveSessionController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\RoomAssignmentController;
use App\Http\Controllers\RoomAssignmentSubmissionController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;

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

Route::get('/quizzes', [QuizController::class, 'index']);
Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);

Route::middleware('auth_or_mock')->group(function () {

    Route::get('/user', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Current user',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => strtolower($user->role ?? 'user'),
                'role_label' => match (strtoupper($user->role ?? 'USER')) {
                    'ADMIN' => 'Admin',
                    'VIP' => 'VIP',
                    default => 'Thuong',
                },
                'ai_quota_remaining' => $user->ai_quota_remaining,
                'vip_expires_at' => $user->vip_expires_at,
                'created_at' => $user->created_at,
            ],
        ]);
    });

    Route::post('/ocr/scan', [OcrController::class, 'scan']);

    Route::get('/quizzes/{quiz}/questions', [QuestionController::class, 'index']);
    Route::post('/quizzes/{quiz}/questions', [QuestionController::class, 'store']);
    Route::get('/questions/{question}', [QuestionController::class, 'show']);
    Route::put('/questions/{question}', [QuestionController::class, 'update']);
    Route::patch('/questions/{question}', [QuestionController::class, 'update']);
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy']);

    Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);
    Route::put('/answers/{answer}', [AnswerController::class, 'update']);
    Route::patch('/answers/{answer}', [AnswerController::class, 'update']);
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy']);

    Route::get('/quiz-attempts', [QuizAttemptController::class, 'index']);
    Route::get('/quiz-attempts/{quizAttempt}', [QuizAttemptController::class, 'show']);
    Route::post('/quizzes/{quiz}/attempts/start', [QuizAttemptController::class, 'start']);
    Route::post('/quizzes/{quiz}/attempts/submit', [QuizAttemptController::class, 'submit']);

    Route::post('/quizzes', [QuizController::class, 'store']);
    Route::put('/quizzes/{quiz}', [QuizController::class, 'update']);
    Route::patch('/quizzes/{quiz}', [QuizController::class, 'update']);
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);


    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store'])->middleware('is_vip');
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::patch('/rooms/{room}/code', [RoomController::class, 'updateCode']);
    Route::post('/rooms/join', [RoomController::class, 'join']);
    Route::get('/rooms/{room}/members', [RoomController::class, 'members']);

    // Room assignments
    Route::get('/rooms/{room}/assignments', [RoomAssignmentController::class, 'index']);
    Route::post('/rooms/{room}/assignments', [RoomAssignmentController::class, 'store'])->middleware('is_vip');
    Route::get('/rooms/{room}/assignments/{assignment}', [RoomAssignmentController::class, 'show']);
    Route::get('/rooms/{room}/assignments/{assignment}/my-submission', [RoomAssignmentSubmissionController::class, 'mySubmission']);

    // Room assignment submissions
    Route::get('/rooms/{room}/assignments/{assignment}/submissions', [RoomAssignmentSubmissionController::class, 'indexForAssignment']);
    Route::get('/rooms/{room}/assignments/{assignment}/submissions/{submission}', [RoomAssignmentSubmissionController::class, 'showForAssignment']);
    Route::post('/rooms/{room}/assignments/{assignment}/start', [RoomAssignmentSubmissionController::class, 'start']);
    Route::get('/assignment-submissions/{submission}', [RoomAssignmentSubmissionController::class, 'show']);
    Route::post('/assignment-submissions/{submission}/answer', [RoomAssignmentSubmissionController::class, 'answer']);
    Route::post('/assignment-submissions/{submission}/submit', [RoomAssignmentSubmissionController::class, 'submit']);
    Route::get('/submissions/{submission}', [RoomAssignmentSubmissionController::class, 'show']);
    Route::post('/submissions/{submission}/answers', [RoomAssignmentSubmissionController::class, 'answer']);
    Route::post('/submissions/{submission}/submit', [RoomAssignmentSubmissionController::class, 'submit']);

    // Live sessions
    Route::get('/rooms/{room}/live-sessions/current', [LiveSessionController::class, 'currentForRoom']);
    Route::post('/rooms/{room}/live-sessions', [LiveSessionController::class, 'create'])->middleware('is_vip');
    Route::post('/live-sessions/{session}/join', [LiveSessionController::class, 'join']);
    Route::post('/live-sessions/{session}/start', [LiveSessionController::class, 'start'])->middleware('is_vip');
    Route::get('/live-sessions/{session}/current-question', [LiveSessionController::class, 'currentQuestion']);
    Route::post('/live-sessions/{session}/answer', [LiveSessionController::class, 'answer']);
    Route::post('/live-sessions/{session}/next-question', [LiveSessionController::class, 'nextQuestion'])->middleware('is_vip');
    Route::get('/live-sessions/{session}/leaderboard', [LiveSessionController::class, 'leaderboard']);

    Route::middleware('is_admin')->apiResource('users', UserController::class);
});



Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
