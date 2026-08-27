<?php

namespace App\AI\Validation;

final class QuizSuggestionResponseValidator
{
    public function isValid(array $data): bool
    {
        return isset($data['suggestions'])
            && is_array($data['suggestions']);
    }
}
