<?php

namespace App\AI\Validation;

final class QuestionReviewResponseValidator
{
    public function isValid(array $data): bool
    {
        return in_array($data['overall_status'] ?? null, ['good', 'needs_attention'], true)
            && is_numeric($data['score'] ?? null)
            && is_string($data['reasoning_summary'] ?? null)
            && is_array($data['issues'] ?? null)
            && is_array($data['suggestions'] ?? null)
            && is_array($data['suggested_question'] ?? null);
    }
}
