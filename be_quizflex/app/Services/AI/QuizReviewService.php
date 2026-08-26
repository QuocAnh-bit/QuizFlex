<?php

namespace App\Services\AI;

use App\AI\Clients\OpenRouterClient;
use App\AI\DTOs\ChatRequest;
use App\AI\Validation\QuizReviewResponseValidator;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class QuizReviewService
{
    public function __construct(
        private OpenRouterClient $client,
        private QuizReviewResponseValidator $validator,
    ) {}

    public function review(
        string $prompt
    ): array {
        $response = $this->client->generateJson(
            new ChatRequest(
                systemPrompt: 'You review quizzes and return '
                    . 'valid JSON only. '
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
                'AI review JSON structure invalid.'
            );
        }

        return $response->data;
    }

    public function reviewCached(
        string $prompt,
        array $payload,
        $userId = null
    ): array {
        $hash = $this->makeReviewHash(
            payload: $payload,
            userId: $userId,
        );

        $cacheKey =
            'quizflex:ai-review:' . $hash;

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $cached['_cached'] = true;

            return $cached;
        }

        $data = $this->review($prompt);

        Cache::put(
            $cacheKey,
            $data,
            now()->addHours(24)
        );

        $data['_cached'] = false;

        return $data;
    }

    private function makeReviewHash(
        array $payload,
        $userId = null
    ): string {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        return hash(
            'sha256',
            ($userId ?? 'guest')
                . '|'
                . $json
        );
    }
}
