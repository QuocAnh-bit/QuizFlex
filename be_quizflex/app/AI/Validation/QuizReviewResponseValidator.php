<?php

namespace App\AI\Validation;

final class QuizReviewResponseValidator
{
    public function isValid(array $data): bool
    {
        if (
            !isset($data['summary'])
            || !is_string($data['summary'])
        ) {
            return false;
        }

        if (
            !isset($data['topics'])
            || !is_array($data['topics'])
        ) {
            return false;
        }

        if (
            !isset($data['issues'])
            || !is_array($data['issues'])
        ) {
            return false;
        }

        if (
            !isset($data['suggestions'])
            || !is_array($data['suggestions'])
        ) {
            return false;
        }

        return true;
    }
}
