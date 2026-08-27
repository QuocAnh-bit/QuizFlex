<?php

namespace Tests\Unit\AI;

use App\Services\AI\AIService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIServiceCompatibilityTest extends TestCase
{
    public function test_generate_quiz_keeps_legacy_contract(): void
    {
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

        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'model' => 'test/model',
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'PHP Quiz',
                                'questions' => [
                                    [
                                        'content' =>
                                        'PHP là gì?',
                                        'answers' => [
                                            [
                                                'content' =>
                                                'Ngôn ngữ lập trình',
                                                'is_correct' =>
                                                true,
                                            ],
                                            [
                                                'content' =>
                                                'Hệ điều hành',
                                                'is_correct' =>
                                                false,
                                            ],
                                            [
                                                'content' =>
                                                'Trình duyệt',
                                                'is_correct' =>
                                                false,
                                            ],
                                            [
                                                'content' =>
                                                'Cơ sở dữ liệu',
                                                'is_correct' =>
                                                false,
                                            ],
                                        ],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 200,
                ],
            ]),
        ]);

        $result = app(AIService::class)
            ->generateQuiz(
                prompt: 'PHP căn bản',
                count: 1,
            );

        $this->assertSame(
            'PHP Quiz',
            $result['title']
        );

        $this->assertCount(
            1,
            $result['questions']
        );

        $this->assertSame(
            300,
            $result['meta']['tokens_used']
        );

        $this->assertSame(
            'openrouter',
            $result['meta']['provider']
        );

        $this->assertArrayHasKey(
            'content',
            $result['questions'][0]
        );

        $this->assertArrayHasKey(
            'answers',
            $result['questions'][0]
        );
    }
}
