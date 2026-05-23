<?php

namespace App\Services\AI;

class PromptQualityValidator
{
    public const INVALID_PROMPT_MESSAGE =
        'Prompt chưa rõ nội dung. Vui lòng nhập đúng prompt, ví dụ: "Tạo 10 câu hỏi Toán lớp 10 về hàm số bậc nhất". Token AI của bạn chưa bị trừ.';

    public function validate(string $prompt): array
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            return $this->invalid();
        }

        if (mb_strlen($prompt) < 3) {
            return $this->invalid();
        }

        if (!preg_match('/[a-zA-ZÀ-ỹ]/u', $prompt)) {
            return $this->invalid();
        }

        $lower = mb_strtolower($prompt);

        $badPatterns = [
            'asdf',
            'qwerty',
            'zxcv',
            'adasd',
            'asdasd',
            'lorem',
        ];

        foreach ($badPatterns as $bad) {
            if (str_contains($lower, $bad)) {
                return $this->invalid();
            }
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $lower, $matches);

        $tokens = $matches[0] ?? [];

        $ignoreWords = [
            'tao',
            'tạo',
            'quiz',
            'question',
            'questions',
            'generate',
            'create',
            'cau',
            'câu',
            'hoi',
            'hỏi',
            'hay',
            'hãy',
        ];

        $meaningfulTokens = [];

        foreach ($tokens as $token) {
            if (!in_array($token, $ignoreWords)) {
                $meaningfulTokens[] = $token;
            }
        }

        if (count($meaningfulTokens) === 0) {
            return $this->invalid();
        }

        if ($this->hasDomainSignal($lower)) {
            return [
                'valid' => true,
                'message' => 'OK',
            ];
        }

        if (count($meaningfulTokens) >= 2) {
            return [
                'valid' => true,
                'message' => 'OK',
            ];
        }

        return $this->invalid();
    }

    private function invalid(): array
    {
        return [
            'valid' => false,
            'message' => self::INVALID_PROMPT_MESSAGE,
        ];
    }

    private function hasDomainSignal(string $text): bool
    {
        $signals = [
            'toán',
            'math',
            'python',
            'english',
            'marketing',
            'finance',
            'hàm số',
            'lớp',
            'java',
            'php',
            'react',
            'laravel',
            'sql',
            'hệ mặt trời',
            'vật lý',
            'hóa học',
            'sinh học',
        ];

        foreach ($signals as $signal) {
            if (str_contains($text, $signal)) {
                return true;
            }
        }

        return false;
    }
}