<?php

namespace Tests\Unit\AI;

use App\Services\AI\QuizReviewService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuizReviewServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config()->set(
            'services.openrouter.api_key',
            'test-key'
        );

        config()->set(
            'services.openrouter.base_uri',
            'https://openrouter.test/api/v1'
        );

        config()->set(
            'services.openrouter.model',
            'test/model'
        );
    }

    public function test_it_reviews_and_caches_result(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'model' => 'test/model',

                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'summary' =>
                                'Quiz PHP căn bản.',

                                'topics' =>
                                [],

                                'issues' =>
                                [],

                                'suggestions' => [
                                    'Bổ sung câu vận dụng.',
                                ],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $service = app(
            QuizReviewService::class
        );

        $payload = [
            'quiz' => [
                'title' => 'PHP Quiz',
            ],
            'questions' => [],
        ];

        $first = $service->reviewCached(
            prompt: 'Review quiz này.',
            payload: $payload,
            userId: 10,
        );

        $second = $service->reviewCached(
            prompt: 'Review quiz này.',
            payload: $payload,
            userId: 10,
        );

        $this->assertFalse(
            $first['_cached']
        );

        $this->assertTrue(
            $second['_cached']
        );

        $this->assertSame(
            'Quiz PHP căn bản.',
            $second['summary']
        );

        // Lần thứ hai phải lấy cache.
        Http::assertSentCount(1);
    }
}
