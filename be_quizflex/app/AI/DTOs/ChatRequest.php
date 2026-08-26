<?php

namespace App\AI\DTOs;

final readonly class ChatRequest
{
    public function __construct(
        public string $systemPrompt,
        public string $userPrompt,
        public ?string $model = null,
        public int $maxTokens = 4000,
        public float $temperature = 0.4,
        public ?array $responseFormat = null,
    ) {}
}
