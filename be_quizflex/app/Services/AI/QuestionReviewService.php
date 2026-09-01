<?php

namespace App\Services\AI;

use App\AI\Clients\OpenRouterClient;
use App\AI\DTOs\ChatRequest;
use App\AI\Prompts\QuestionReviewPrompt;
use App\AI\Validation\QuestionReviewResponseValidator;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class QuestionReviewService
{
    public function __construct(
        private OpenRouterClient $client,
        private QuestionReviewResponseValidator $validator,
    ) {}

    public function review(array $payload, ?int $userId = null): array
    {
        $cacheKey = 'quizflex:question-review:' . hash('sha256', ($userId ?? 'guest') . '|' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) return [...$cached, '_cached' => true];

        $response = $this->client->generateJson(new ChatRequest(
            systemPrompt: 'You review one quiz question and return valid JSON only.',
            userPrompt: QuestionReviewPrompt::build($payload),
            maxTokens: 5000,
            temperature: 0.3,
        ));

        if (!$this->validator->isValid($response->data)) {
            throw new RuntimeException('AI question review JSON structure invalid.');
        }

        $data = [...$response->data, '_cached' => false];
        Cache::put($cacheKey, $data, now()->addHours(12));
        return $data;
    }
}
