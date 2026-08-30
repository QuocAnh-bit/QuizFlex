<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;

class QuizGradingService
{
    public function grade(Quiz $quiz, array $answers): array
    {
        if ($quiz->exists && !$quiz->relationLoaded('questions')) {
            $quiz->loadMissing('questions.answers');
        }

        $snapshot = [];
        $score = 0;
        $totalPoints = 0;
        $correctCount = 0;

        foreach ($quiz->questions as $question) {
            $rawPoints = (isset($question->pivot) && isset($question->pivot->points) && $question->pivot->points !== null && $question->pivot->points !== '')
                ? $question->pivot->points
                : ($question->points ?? 0);
            $points = max(0, (float) $rawPoints);
            $totalPoints += $points;

            $selectedRaw = $answers[$question->id] ?? $answers[(string) $question->id] ?? [];
            $selectedIds = $this->resolveSelectedAnswerIds($question, $selectedRaw);
            $correctIds = $question->answers
                ->where('is_correct', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $isCorrect = $selectedIds === $correctIds && count($correctIds) > 0;
            $earnedPoints = $isCorrect ? $points : 0.0;

            if ($isCorrect) {
                $score += $earnedPoints;
                $correctCount++;
            }

            $allAnswers = $question->answers->values()->map(function (Answer $answer, int $index) {
                $key = chr(65 + (int) ($answer->order ?? $index));
                return [
                    'id' => $answer->id,
                    'key' => $key,
                    'answer_key' => $key,
                    'content' => $answer->content,
                    'text' => $answer->content,
                    'is_correct' => (bool) $answer->is_correct,
                ];
            })->all();

            $snapshot[] = [
                'question_id' => $question->id,
                'question_content' => $question->content,
                'question' => $question->content,
                'type' => $question->type ?? 'single_choice',
                'selected_answer_ids' => $selectedIds,
                'selected_answer_keys' => $this->answerKeysFromIds($question, $selectedIds),
                'correct_answer_ids' => $correctIds,
                'correct_answer_keys' => $this->answerKeysFromIds($question, $correctIds),
                'answers' => $allAnswers,
                'is_correct' => $isCorrect,
                'points' => round($points, 2),
                'earned_points' => round($earnedPoints, 2),
            ];
        }

        $totalQuestions = $quiz->questions->count();
        $roundedScore = round($score, 2);
        $roundedTotalPoints = round($totalPoints, 2);
        $scaledScore10 = $roundedTotalPoints > 0 ? round(($score / $totalPoints) * 10, 2) : 0.0;
        $accuracyPercentage = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 1) : 0.0;

        return [
            'score' => $roundedScore,
            'total_points' => $roundedTotalPoints,
            'scaled_score_10' => $scaledScore10,
            'accuracy_percentage' => $accuracyPercentage,
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'answers_snapshot' => $snapshot,
        ];
    }

    private function resolveSelectedAnswerIds(Question $question, mixed $raw): array
    {
        $values = is_array($raw) ? $raw : [$raw];
        $answerIds = [];

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                $answerId = (int) $value;
                if ($question->answers->contains('id', $answerId)) {
                    $answerIds[] = $answerId;
                }
                continue;
            }

            $key = strtoupper(trim((string) $value));
            $answer = $question->answers->values()->first(function (Answer $answer, int $index) use ($key) {
                return chr(65 + (int) ($answer->order ?? $index)) === $key;
            });

            if ($answer) {
                $answerIds[] = (int) $answer->id;
            }
        }

        return collect($answerIds)->unique()->sort()->values()->all();
    }

    private function answerKeysFromIds(Question $question, array $ids): array
    {
        return $question->answers
            ->values()
            ->filter(fn (Answer $answer) => in_array((int) $answer->id, $ids, true))
            ->map(fn (Answer $answer, int $index) => chr(65 + (int) ($answer->order ?? $index)))
            ->values()
            ->all();
    }
}
