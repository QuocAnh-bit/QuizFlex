<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQuizJob;
use App\Models\AiJob;
use App\Models\AiLog;
use App\Services\AI\PromptQualityValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AIController extends Controller
{
    public function generate(Request $request)
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:5000'],
            'count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard'],
            'language' => ['nullable', 'string', 'in:vi,en'],
            'visibility' => ['nullable', 'string', 'in:private,public,group'],
        ]);

        $prompt = trim((string) $data['prompt']);

        $promptValidation = app(PromptQualityValidator::class)
            ->validate($prompt);

        if (!$promptValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $promptValidation['message'],
                'code' => 'invalid_prompt',
                'data' => [
                    'quota_charged' => false,
                    'job_id' => null,
                    'status' => 'rejected',
                ],
            ], 422);
        }

        $user = auth('api')->user();

        if (($user->ai_quota_remaining ?? 0) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'AI quota exhausted.',
            ], 403);
        }

        $job = AiJob::create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => $prompt,
            'requested_count' => $data['count'] ?? 10,
            'difficulty' => $data['difficulty'] ?? 'medium',
            'language' => $data['language'] ?? 'vi',
            'visibility' => $data['visibility'] ?? 'private',
            'status' => 'pending',
        ]);

        GenerateQuizJob::dispatch($job->uuid);

        return response()->json([
            'success' => true,
            'message' => 'AI job queued.',
            'data' => [
                'job_id' => $job->uuid,
                'status' => $job->status,
                'prompt' => $job->prompt,
                'requested_count' => $job->requested_count,
                'difficulty' => $job->difficulty,
                'language' => $job->language,
                'visibility' => $job->visibility,
            ],
        ], 202);
    }

    public function show(int $id)
    {
        $log = AiLog::query()
            ->where('user_id', auth('api')->id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    public function status(string $jobId)
    {
        $job = AiJob::query()
            ->with([
                'quiz.user:id,name',
                'quiz.questions.answers',
                'log:id,status,questions_generated,error_message',
            ])
            ->where('uuid', $jobId)
            ->where('user_id', auth('api')->id())
            ->firstOrFail();

        $quizData = null;
        if ($job->quiz) {
            /** @var \App\Http\Controllers\QuizController $quizController */
            $quizController = app(\App\Http\Controllers\QuizController::class);
            $quizData = $quizController->formatQuiz($job->quiz, true);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $job->uuid,
                'status' => $job->status,
                'prompt' => $job->prompt,
                'requested_count' => $job->requested_count,
                'difficulty' => $job->difficulty,
                'language' => $job->language,
                'visibility' => $job->visibility,
                'questions_generated' => $job->questions_generated,
                'quiz_id' => $job->quiz_id,
                'log_id' => $job->ai_log_id,
                'error_message' => $job->error_message,
                'started_at' => $job->started_at,
                'finished_at' => $job->finished_at,
                'created_at' => $job->created_at,
                'updated_at' => $job->updated_at,
                'quiz' => $job->quiz,
                'quiz_full' => $quizData,
                'log' => $job->log,
            ],
        ]);
    }
}
