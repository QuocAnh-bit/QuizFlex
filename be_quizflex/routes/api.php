<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AuthController;
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

Route::middleware('auth_or_mock')->group(function () {

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


    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::post('/rooms/join', [RoomController::class, 'join']);
    Route::get('/rooms/{room}/members', [RoomController::class, 'members']);

    // Room assignments
    Route::get('/rooms/{room}/assignments', [RoomAssignmentController::class, 'index']);
    Route::post('/rooms/{room}/assignments', [RoomAssignmentController::class, 'store']);
    Route::get('/rooms/{room}/assignments/{assignment}', [RoomAssignmentController::class, 'show']);

    // Room assignment submissions
    Route::get('/rooms/{room}/assignments/{assignment}/submissions', [RoomAssignmentSubmissionController::class, 'indexForAssignment']);
    Route::get('/rooms/{room}/assignments/{assignment}/submissions/{submission}', [RoomAssignmentSubmissionController::class, 'showForAssignment']);
    Route::post('/rooms/{room}/assignments/{assignment}/start', [RoomAssignmentSubmissionController::class, 'start']);
    Route::post('/assignment-submissions/{submission}/answer', [RoomAssignmentSubmissionController::class, 'answer']);
    Route::post('/assignment-submissions/{submission}/submit', [RoomAssignmentSubmissionController::class, 'submit']);
});



Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::apiResource('users', UserController::class);

Route::apiResource('quizzes', QuizController::class);



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
