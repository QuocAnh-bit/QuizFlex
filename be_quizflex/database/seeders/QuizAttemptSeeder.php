<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizAttemptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            return;
        }

        $quizzes = Quiz::with('questions.answers')->get();
        if ($quizzes->isEmpty()) {
            return;
        }

        foreach ($quizzes as $quiz) {
            $questions = $quiz->questions;
            if ($questions->isEmpty()) {
                continue;
            }

            // Create 3-5 realistic attempts per quiz from different users
            $attemptCount = rand(3, 5);
            for ($i = 0; $i < $attemptCount; $i++) {
                $user = $users->random();
                $correctCount = 0;
                $answersSnapshot = [];
                $totalPoints = $questions->count() * 10;

                foreach ($questions as $q) {
                    $answers = $q->answers;
                    $correctAnswer = $answers->firstWhere('is_correct', true);
                    
                    // 75% chance user answered correctly
                    $isCorrect = (rand(1, 100) <= 75);
                    if ($isCorrect && $correctAnswer) {
                        $selectedId = $correctAnswer->id;
                        $correctCount++;
                    } else {
                        $wrongAnswer = $answers->firstWhere('is_correct', false);
                        $selectedId = $wrongAnswer ? $wrongAnswer->id : ($answers->first()?->id);
                    }

                    $answersSnapshot[] = [
                        'question_id' => $q->id,
                        'selected_answer_ids' => $selectedId ? [$selectedId] : [],
                        'is_correct' => $isCorrect,
                    ];
                }

                $score = (int) round(($correctCount / max(1, $questions->count())) * 100);
                $timeSpent = rand(120, 480);
                $startedAt = now()->subDays(rand(1, 14))->subHours(rand(1, 23));

                QuizAttempt::create([
                    'user_id' => $user->id,
                    'quiz_id' => $quiz->id,
                    'score' => $score,
                    'total_points' => $totalPoints,
                    'time_spent_seconds' => $timeSpent,
                    'answers_snapshot' => $answersSnapshot,
                    'status' => 'completed',
                    'started_at' => $startedAt,
                    'finished_at' => (clone $startedAt)->addSeconds($timeSpent),
                ]);
            }
        }
    }
}
