<?php

namespace App\AI\Validation;

final class QuizResponseValidator
{
    public function isValid(array $data): bool
    {
        if (
            !isset($data['questions'])
            || !is_array($data['questions'])
            || $data['questions'] === []
        ) {
            return false;
        }

        foreach ($data['questions'] as $question) {
            if (
                !is_array($question)
                || !isset($question['content'])
                || !is_string($question['content'])
            ) {
                return false;
            }

            if (
                !isset($question['answers'])
                || !is_array($question['answers'])
                || count($question['answers']) !== 4
            ) {
                return false;
            }

            $correctAnswers = 0;

            foreach ($question['answers'] as $answer) {
                if (
                    !is_array($answer)
                    || !isset($answer['content'])
                    || !is_string($answer['content'])
                ) {
                    return false;
                }

                if (
                    !array_key_exists(
                        'is_correct',
                        $answer
                    )
                    || !is_bool(
                        $answer['is_correct']
                    )
                ) {
                    return false;
                }

                if ($answer['is_correct'] === true) {
                    $correctAnswers++;
                }
            }

            if ($correctAnswers !== 1) {
                return false;
            }
        }

        return true;
    }
}
