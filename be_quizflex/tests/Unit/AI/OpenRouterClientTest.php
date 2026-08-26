<?php

namespace Tests\Unit\AI;

use App\AI\Clients\OpenRouterClient;
use App\AI\DTOs\ChatRequest;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterClientTest extends TestCase
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

    public function test_it_returns_decoded_json(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'model' => 'test/model',

                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'Test quiz',
                                'questions' => [],
                            ]),
                        ],
                    ],
                ],

                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                ],
            ]),
        ]);

        $response = app(
            OpenRouterClient::class
        )->generateJson(
            new ChatRequest(
                systemPrompt: 'Return JSON.',
                userPrompt: 'Generate a quiz.',
            )
        );

        $this->assertSame(
            'Test quiz',
            $response->data['title']
        );

        $this->assertSame(
            30,
            $response->totalTokens()
        );
    }

    public function test_it_removes_json_code_fence(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' =>
                            "```json\n"
                                . '{"title":"Quiz"}'
                                . "\n```",
                        ],
                    ],
                ],
            ]),
        ]);

        $response = app(
            OpenRouterClient::class
        )->generateJson(
            new ChatRequest(
                systemPrompt: 'Return JSON.',
                userPrompt: 'Generate.',
            )
        );

        $this->assertSame(
            'Quiz',
            $response->data['title']
        );
    }

    public function test_it_sends_json_schema_response_format(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'model' => 'test/model',

                'choices' => [
                    [
                        'message' => [
                            'content' =>
                            '{"units":[]}',
                        ],
                    ],
                ],
            ]),
        ]);

        app(OpenRouterClient::class)
            ->generateJson(
                new ChatRequest(
                    systemPrompt: 'Parse curriculum.',

                    userPrompt: 'Curriculum source.',

                    model: 'test/parser-model',

                    temperature: 0,

                    responseFormat: [
                        'type' => 'json_schema',

                        'json_schema' => [
                            'name' =>
                            'curriculum_units',

                            'strict' =>
                            true,

                            'schema' => [
                                'type' =>
                                'object',

                                'properties' => [
                                    'units' => [
                                        'type' =>
                                        'array',
                                    ],
                                ],

                                'required' => [
                                    'units',
                                ],

                                'additionalProperties' =>
                                false,
                            ],
                        ],
                    ],
                )
            );

        Http::assertSent(
            function (
                HttpRequest $request
            ): bool {
                $payload = $request->data();

                $this->assertSame(
                    'test/parser-model',
                    data_get($payload, 'model')
                );

                $this->assertEquals(
                    0,
                    data_get($payload, 'temperature')
                );

                $this->assertSame(
                    'json_schema',
                    data_get(
                        $payload,
                        'response_format.type'
                    )
                );

                $this->assertTrue(
                    data_get(
                        $payload,
                        'response_format.json_schema.strict'
                    )
                );

                return true;
            }
        );
    }
}
