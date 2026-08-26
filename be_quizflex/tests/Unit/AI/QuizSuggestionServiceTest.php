<?php

namespace Tests\Unit\AI;

use App\Services\AI\QuizSuggestionService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuizSuggestionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_it_generates_suggestions(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'suggestions' => [
                                    [
                                        'id' =>
                                        'suggestion_1',
                                        'type' =>
                                        'analysis',
                                        'summary' =>
                                        'Quiz hợp lệ.',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(
            QuizSuggestionService::class
        )->suggest([
            'action' => 'analyze_quiz',

            'quiz' => [
                'title' => 'PHP Quiz',
            ],

            'selected_questions' => [],

            'options' => [],

            'local_report' => [
                'total_questions' => 5,
            ],
        ]);

        $this->assertCount(
            1,
            $result['suggestions']
        );

        Http::assertSent(
            function (
                HttpRequest $request
            ): bool {
                $userPrompt = (string) data_get(
                    $request->data(),
                    'messages.1.content'
                );

                $this->assertStringContainsString(
                    'LOCAL_REPORT:',
                    $userPrompt
                );

                $this->assertStringContainsString(
                    '"total_questions":5',
                    $userPrompt
                );

                return true;
            }
        );
    }
}
