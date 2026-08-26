<?php

namespace Tests\Unit\RAG;

use App\Services\RAG\Parse\CurriculumAIParserService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CurriculumAIParserServiceTest extends TestCase
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
            'rag.openrouter.parser_model',
            'test/parser-model'
        );
    }

    public function test_it_parses_curriculum_with_shared_client(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'model' =>
                'test/parser-model',

                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'units' => [
                                    [
                                        'type' =>
                                        'curriculum_content',
                                        'domain' =>
                                        'Số học',
                                        'topic' =>
                                        'Số tự nhiên',
                                        'section' =>
                                        null,
                                        'subsection' =>
                                        null,
                                        'title' =>
                                        'Phép cộng',
                                        'author' =>
                                        null,
                                        'genre' =>
                                        null,
                                        'selection_type' =>
                                        null,
                                        'content' =>
                                        'Thực hiện phép cộng.',
                                        'learning_outcomes' => [
                                            'Thực hiện được phép cộng.',
                                        ],

                                        // AI trả metadata sai.
                                        'subject' =>
                                        'Môn sai',
                                        'grade_min' =>
                                        99,
                                        'grade_max' =>
                                        99,
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(
            CurriculumAIParserService::class
        )->parse([
            'subject' => 'Toán',
            'grade_min' => 1,
            'grade_max' => 2,
            'type' => 'curriculum_content',
            'heading' => 'Số và phép tính',
            'text' => 'Thực hiện phép cộng số tự nhiên.',
        ]);

        $this->assertCount(
            1,
            $result['units']
        );

        $unit = $result['units'][0];

        // Metadata phải do backend quyết định.
        $this->assertSame(
            'Toán',
            $unit['subject']
        );

        $this->assertSame(
            1,
            $unit['grade_min']
        );

        $this->assertSame(
            2,
            $unit['grade_max']
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
                    data_get(
                        $payload,
                        'temperature'
                    )
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

    public function test_empty_text_is_rejected_before_calling_ai(): void
    {
        Http::fake();

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Block không có text.'
        );

        try {
            app(
                CurriculumAIParserService::class
            )->parse([
                'subject' => 'Toán',
                'grade_min' => 1,
                'grade_max' => 1,
                'text' => '   ',
            ]);
        } finally {
            Http::assertNothingSent();
        }
    }
}
