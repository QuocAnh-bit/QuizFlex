<?php

namespace Tests\Unit\AI;

use App\Services\AI\QuizGenerationService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use App\Services\RAG\Retrieval\CurriculumRetrieverService;
use Illuminate\Http\Client\Request as HttpRequest;
use Mockery\MockInterface;

class QuizGenerationServiceTest extends TestCase
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
        config()->set(
            'rag.retrieval.limit',
            6
        );

        config()->set(
            'rag.retrieval.score_threshold',
            null
        );
    }

    public function test_it_generates_normalized_quiz(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'model' => 'test/model',
                'choices' => [
                    [
                        'message' => [
                            'content' =>
                            json_encode(
                                $this->validQuiz()
                            ),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 200,
                ],
            ]),
        ]);

        $result = app(
            QuizGenerationService::class
        )->generate(
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

        $this->assertFalse(
            $result['meta']['rag_enabled']
        );
    }

    public function test_it_rejects_invalid_quiz_json(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'Invalid',
                                'questions' => [],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'AI JSON structure invalid.'
        );

        app(
            QuizGenerationService::class
        )->generate(
            prompt: 'PHP',
            count: 1,
        );
    }

    public function test_it_generates_quiz_with_rag_context(): void
    {
        $results = [
            [
                'chunk_id' => 10,
                'unit_id' => 20,
                'document_id' => 30,
                'score' => 0.95,
                'subject' => 'Toán',
                'grade_min' => 10,
                'grade_max' => 10,
                'domain' => 'Đại số',
                'topic' => 'Hàm số',
                'section' => null,
                'page_start' => 15,
                'page_end' => 16,
                'content' =>
                'Hàm số bậc hai có dạng y = ax^2 + bx + c.',
            ],
        ];

        $this->mock(
            CurriculumRetrieverService::class,
            function (
                MockInterface $mock
            ) use ($results): void {
                $mock->shouldReceive('retrieve')
                    ->once()
                    ->with(
                        'Toán',
                        10,
                        'Hàm số bậc hai',
                        6,
                        null,
                        [20]
                    )
                    ->andReturn($results);

                $mock->shouldReceive('buildContext')
                    ->once()
                    ->with($results)
                    ->andReturn(
                        'Hàm số bậc hai có dạng '
                            . 'y = ax^2 + bx + c.'
                    );
            }
        );

        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'model' => 'test/model',
                'choices' => [
                    [
                        'message' => [
                            'content' =>
                            json_encode(
                                $this->validQuiz()
                            ),
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(
            QuizGenerationService::class
        )->generate(
            prompt: 'Hàm số bậc hai',
            count: 1,
            subject: 'Toán',
            grade: 10,
            curriculumUnitIds: [20],
        );

        $this->assertTrue(
            $result['meta']['rag_enabled']
        );

        $this->assertSame(
            1,
            $result['meta']['rag_source_count']
        );

        $this->assertSame(
            [20],
            $result['meta']['rag_curriculum_unit_ids']
        );

        $this->assertSame(
            10,
            $result['meta']['rag_sources'][0]['chunk_id']
        );

        Http::assertSent(
            function (
                HttpRequest $request
            ): bool {
                $prompt = (string) data_get(
                    $request->data(),
                    'messages.1.content'
                );

                $this->assertStringContainsString(
                    '<curriculum_context>',
                    $prompt
                );

                $this->assertStringContainsString(
                    'y = ax^2 + bx + c',
                    $prompt
                );

                return true;
            }
        );
    }

    private function validQuiz(): array
    {
        return [
            'title' => 'PHP Quiz',
            'questions' => [
                [
                    'content' =>
                    'PHP là ngôn ngữ gì?',
                    'answers' => [
                        [
                            'content' =>
                            'Ngôn ngữ lập trình',
                            'is_correct' => true,
                        ],
                        [
                            'content' =>
                            'Hệ điều hành',
                            'is_correct' => false,
                        ],
                        [
                            'content' =>
                            'Cơ sở dữ liệu',
                            'is_correct' => false,
                        ],
                        [
                            'content' =>
                            'Trình duyệt',
                            'is_correct' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
}
