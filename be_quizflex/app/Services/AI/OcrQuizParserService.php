<?php

namespace App\Services\AI;

use App\AI\Clients\OpenRouterClient;
use App\AI\DTOs\ChatRequest;
use App\AI\Validation\OcrQuizResponseValidator;
use RuntimeException;

final class OcrQuizParserService
{
    public function __construct(
        private OpenRouterClient $client,
        private OcrQuizResponseValidator $validator,
    ) {}

    public function parse(
        string $prompt
    ): array {
        $response = $this->client->generateJson(
            new ChatRequest(
                systemPrompt: 'You parse OCR quiz text and '
                    . 'return valid JSON only. '
                    . 'Do not wrap JSON in markdown.',

                userPrompt: $prompt,

                maxTokens: 8000,

                temperature: 0.4,
            )
        );

        if (!$this->validator->isValid(
            $response->data
        )) {
            throw new RuntimeException(
                'OCR quiz JSON structure invalid.'
            );
        }

        return $response->data;
    }
}
