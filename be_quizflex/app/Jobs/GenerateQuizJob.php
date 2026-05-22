<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\AiLog;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AI\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class GenerateQuizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public string $jobUuid)
    {
    }

    public function handle(AIService $aiService): void
    {
        $job = AiJob::query()
            ->where('uuid', $this->jobUuid)
            ->first();

        if (!$job || $job->status === 'completed') {
            return;
        }

        $job->update([
            'status' => 'processing',
            'error_message' => null,
            'started_at' => $job->started_at ?? now(),
        ]);

        $generatedQuiz = $aiService->generateQuiz(
            $this->buildPromptFromJob($job),
            $job->requested_count
        );

        DB::transaction(function () use ($job, $generatedQuiz) {
            $user = User::query()->lockForUpdate()->findOrFail($job->user_id);

            if (($user->ai_quota_remaining ?? 0) <= 0) {
                throw new \RuntimeException('AI quota exhausted.');
            }

            $quiz = $this->storeQuiz($job, $generatedQuiz);

            $log = AiLog::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'action_type' => 'ai_generate',
                'tokens_used' => (int) ($generatedQuiz['meta']['tokens_used'] ?? 0),
                'questions_generated' => count($generatedQuiz['questions']),
                'status' => 'success',
                'response_json' => $generatedQuiz,
            ]);

            $user->decrement('ai_quota_remaining');

            $job->update([
                'ai_log_id' => $log->id,
                'quiz_id' => $quiz->id,
                'questions_generated' => count($generatedQuiz['questions']),
                'status' => 'completed',
                'response_json' => $generatedQuiz,
                'finished_at' => now(),
            ]);
        });
    }

    public function failed(Throwable $exception): void
    {
        $job = AiJob::query()
            ->where('uuid', $this->jobUuid)
            ->first();

        if (!$job || $job->status === 'completed') {
            return;
        }

        $logId = $job->ai_log_id;

        if ($logId) {
            AiLog::query()->whereKey($logId)->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        } else {
            $log = AiLog::create([
                'user_id' => $job->user_id,
                'quiz_id' => null,
                'action_type' => 'ai_generate',
                'tokens_used' => 0,
                'questions_generated' => 0,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            $logId = $log->id;
        }

        $job->update([
            'ai_log_id' => $logId,
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ]);
    }

    private function storeQuiz(AiJob $job, array $generatedQuiz): Quiz
    {
        $questions = $generatedQuiz['questions'];

        $quiz = Quiz::create([
            'user_id' => $job->user_id,
            'title' => Str::limit(trim((string) ($generatedQuiz['title'] ?? $job->prompt)), 255, ''),
            'description' => $job->prompt,
            'category' => 'AI Generated',
            'tag' => 'AI',
            'difficulty' => $this->normalizeDifficulty($job->difficulty),
            'status' => $job->visibility === 'public' ? 'published' : 'draft',
            'is_public' => $job->visibility === 'public',
            'room_code' => $job->visibility === 'group' ? $this->generateRoomCode() : null,
            'time_limit_seconds' => max(300, count($questions) * 60),
            'cover' => null,
            'icon' => 'AI',
            'badge' => 'AI',
        ]);

        foreach ($questions as $questionIndex => $questionData) {
            $correctAnswers = collect($questionData['answers'])
                ->filter(fn (array $answer): bool => !empty($answer['is_correct']))
                ->count();

            $question = Question::create([
                'quiz_id' => $quiz->id,
                'content' => trim((string) $questionData['content']),
                'image_url' => null,
                'type' => $correctAnswers > 1 ? 'multiple_choice' : 'single_choice',
                'order' => $questionIndex,
                'points' => 1,
            ]);

            foreach ($questionData['answers'] as $answerIndex => $answerData) {
                Answer::create([
                    'question_id' => $question->id,
                    'content' => trim((string) $answerData['content']),
                    'is_correct' => (bool) $answerData['is_correct'],
                    'order' => $answerIndex,
                ]);
            }
        }

        return $quiz;
    }

    private function buildPromptFromJob(AiJob $job): string
    {
        $language = match (strtolower((string) $job->language)) {
            'en' => 'English',
            default => 'Vietnamese',
        };

        $difficulty = match (strtolower((string) $job->difficulty)) {
            'easy' => 'easy',
            'hard' => 'hard',
            default => 'medium',
        };

        return trim(implode("\n", [
            "Language: {$language}",
            "Difficulty: {$difficulty}",
            "Request: {$job->prompt}",
        ]));
    }

    private function normalizeDifficulty(?string $difficulty): string
    {
        return match (strtolower((string) $difficulty)) {
            'easy' => 'easy',
            'hard' => 'hard',
            default => 'medium',
        };
    }

    private function generateRoomCode(): string
    {
        return 'AI' . random_int(1000, 9999);
    }
}
