<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQuizJob;
use App\Models\AiJob;
use App\Models\AiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

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
            'prompt' => trim((string) $data['prompt']),
            'requested_count' => $data['count'] ?? 10,
            'difficulty' => $data['difficulty'] ?? 'medium',
            'language' => $data['language'] ?? 'vi',
            'visibility' => $data['visibility'] ?? 'private',
            'status' => 'pending',
        ]);

        $runSynchronously = config('queue.default') === 'sync' || $request->boolean('sync');

        try {
            if ($runSynchronously) {
                GenerateQuizJob::dispatchSync($job->uuid);
                $job->refresh();
            } else {
                GenerateQuizJob::dispatch($job->uuid);
            }
        } catch (Throwable $exception) {
            $job->refresh();

            if ($job->status !== 'failed') {
                (new GenerateQuizJob($job->uuid))->failed($exception);
                $job->refresh();
            }
        }

        return response()->json([
            'success' => $job->status !== 'failed',
            'message' => $runSynchronously ? 'AI job processed.' : 'AI job queued.',
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
                'error_message' => $job->error_message,
            ],
        ], $job->status === 'failed' ? 500 : ($runSynchronously ? 200 : 202));
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
