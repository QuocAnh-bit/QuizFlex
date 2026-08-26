<?php

namespace App\AI\DTOs;

final readonly class ChatResponse
{
    public function __construct(
        public array $data,
        public string $rawJson,
        public string $provider,
        public string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens
            + $this->outputTokens;
    }
}
