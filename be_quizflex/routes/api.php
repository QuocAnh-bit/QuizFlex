<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Services\AI\AIService;
use App\AI\Prompts\QuizPrompt;
use App\Http\Controllers\AIController;


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

Route::post('/ocr/scan', [OcrController::class, 'scan']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/ai/generate', [AIController::class, 'generate']);
    Route::post('/ai/generate-quiz', [AIController::class, 'generate']);
    Route::get('/ai/jobs/{jobId}', [AIController::class, 'status'])->whereUuid('jobId');
    Route::get('/ai/quiz-status/{jobId}', [AIController::class, 'status'])->whereUuid('jobId');
    Route::get('/ai/logs/{id}', [AIController::class, 'show']);


    // Admin Only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:user,vip,admin')->group(function () {
        // Protected Payment Routes
        Route::get('/payments/history', [PaymentController::class, 'history']);

        // Protected Quiz Routes
        Route::post('/quizzes', [QuizController::class, 'store']);
        Route::get('/quizzes/{quiz}/edit-data', [QuizController::class, 'editData']);
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update']);
        Route::patch('/quizzes/{quiz}', [QuizController::class, 'update']);
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);
        Route::post('/ocr/import-quiz', [OcrController::class, 'importQuiz']);


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
        Route::post('/quizzes/{quiz}/attempts/submit', [QuizAttemptController::class, 'submit']);
    });
});

// Payment checkout creation. The controller still checks auth('api')->user(),
// so unauthenticated requests return 401 instead of a confusing 404.
Route::post('/payments/create', [PaymentController::class, 'create']);

// Public Webhooks & Callbacks for Payments
Route::post('/payments/webhook/momo', [PaymentController::class, 'webhookMomo']);
Route::get('/payments/callback', [PaymentController::class, 'callback']);

// Public Quiz Routes
Route::get('/quizzes', [QuizController::class, 'index']);
Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
Route::get('/quizzes/{quiz}/questions', [QuestionController::class, 'index']);
Route::get('/questions/{question}', [QuestionController::class, 'show']);
