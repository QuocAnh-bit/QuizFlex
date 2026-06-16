<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;

class QuizGradingService
{
    public function grade(Quiz $quiz, array $answers): array
    {
        $quiz->loadMissing('questions.answers');

        $snapshot = [];
        $score = 0;
        $totalPoints = 0;
        $correctCount = 0;

        foreach ($quiz->questions as $question) {
            $points = max(0, (int) ($question->points ?? 0));
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
            $earnedPoints = $isCorrect ? $points : 0;

            if ($isCorrect) {
                $score += $earnedPoints;
                $correctCount++;
            }

            $snapshot[] = [
                'question_id' => $question->id,
                'question_content' => $question->content,
                'question' => $question->content,
                'selected_answer_ids' => $selectedIds,
                'selected_answer_keys' => $this->answerKeysFromIds($question, $selectedIds),
                'correct_answer_ids' => $correctIds,
                'correct_answer_keys' => $this->answerKeysFromIds($question, $correctIds),
                'is_correct' => $isCorrect,
                'points' => $points,
                'earned_points' => $earnedPoints,
            ];
        }

        return [
            'score' => $score,
            'total_points' => $totalPoints,
            'correct_count' => $correctCount,
            'total_questions' => $quiz->questions->count(),
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
