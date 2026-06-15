<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;

class QuestionOrderService
{
    public function makeForQuiz(Quiz $quiz): array
    {
        $quiz->loadMissing('questions');

        $questionIds = $quiz->questions
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        shuffle($questionIds);

        return $questionIds;
    }

    public function ensureAttemptOrder(QuizAttempt $attempt, Quiz $quiz): QuizAttempt
    {
        if (!empty($attempt->question_order) && is_array($attempt->question_order)) {
            return $attempt;
        }

        $attempt->forceFill([
            'question_order' => $this->makeForQuiz($quiz),
        ])->save();

        return $attempt->refresh();
    }

    public function questionsForQuiz(Quiz $quiz, array $questionOrder)
    {
        $quiz->loadMissing('questions.answers');

        $questions = $quiz->questions;
        if (empty($questionOrder)) {
            return $questions->values();
        }

        $questionsById = $questions->keyBy('id');
        $orderedQuestions = collect($questionOrder)
            ->map(fn ($questionId) => $questionsById->get((int) $questionId))
            ->filter()
            ->values();

        $missingQuestions = $questions
            ->reject(fn (Question $question) => $orderedQuestions->contains('id', $question->id))
            ->values();

        return $orderedQuestions->concat($missingQuestions)->values();
    }

    public function applyOrderToQuiz(Quiz $quiz, array $questionOrder): Quiz
    {
        $quiz->setRelation('questions', $this->questionsForQuiz($quiz, $questionOrder));

        return $quiz;
    }
}
