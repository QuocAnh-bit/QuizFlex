<?php

namespace App\Services\AI;

use App\AI\Clients\OpenRouterClient;
use App\AI\DTOs\ChatRequest;
use App\AI\Prompts\QuizSuggestionPrompt;
use App\AI\Validation\QuizSuggestionResponseValidator;
use RuntimeException;

final class QuizSuggestionService
{
    public function __construct(
        private OpenRouterClient $client,
        private QuizSuggestionPrompt $promptBuilder,
        private QuizSuggestionResponseValidator $validator,
    ) {}

    public function suggest(array $data): array
    {
        $prompt = $this->promptBuilder->build(
            action: (string) $data['action'],
            quiz: $data['quiz'],
            selectedQuestions: $data['selected_questions'] ?? [],
            options: $data['options'] ?? [],
            localReport: $data['local_report'] ?? [],
        );

        $response = $this->client->generateJson(
            new ChatRequest(
                systemPrompt: 'You generate quiz suggestions '
                    . 'as valid JSON only. '
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
                'AI suggestion JSON structure invalid.'
            );
        }

        return $response->data;
    }
}
