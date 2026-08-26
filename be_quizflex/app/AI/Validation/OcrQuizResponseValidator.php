<?php

namespace App\AI\Validation;

final class OcrQuizResponseValidator
{
    public function isValid(array $data): bool
    {
        if (
            !isset($data['questions'])
            || !is_array($data['questions'])
        ) {
            return false;
        }

        foreach ($data['questions'] as $question) {
            if (!is_array($question)) {
                return false;
            }

            if (
                !isset($question['question'])
                || !is_string($question['question'])
            ) {
                return false;
            }

            if (
                !isset($question['options'])
                || !is_array($question['options'])
            ) {
                return false;
            }

            foreach (
                ['A', 'B', 'C', 'D']
                as $optionKey
            ) {
                if (
                    !array_key_exists(
                        $optionKey,
                        $question['options']
                    )
                ) {
                    return false;
                }
            }

            if (
                isset($question['correct_answer'])
                && $question['correct_answer'] !== null
                && !in_array(
                    $question['correct_answer'],
                    ['A', 'B', 'C', 'D'],
                    true
                )
            ) {
                return false;
            }
        }

        return true;
    }
}
