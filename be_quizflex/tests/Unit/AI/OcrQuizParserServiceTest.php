<?php

namespace Tests\Unit\AI;

use App\Services\AI\OcrQuizParserService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OcrQuizParserServiceTest extends TestCase
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

    public function test_it_parses_ocr_quiz_json(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'questions' => [
                                    [
                                        'question' =>
                                        'PHP là gì?',

                                        'options' => [
                                            'A' =>
                                            'Ngôn ngữ lập trình',
                                            'B' =>
                                            'Hệ điều hành',
                                            'C' =>
                                            'Trình duyệt',
                                            'D' =>
                                            'Cơ sở dữ liệu',
                                        ],

                                        'correct_answer' =>
                                        'A',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(
            OcrQuizParserService::class
        )->parse(
            'Parse nội dung OCR này.'
        );

        $this->assertCount(
            1,
            $result['questions']
        );

        $this->assertSame(
            'A',
            $result['questions'][0]['correct_answer']
        );
    }

    public function test_it_rejects_invalid_ocr_quiz_json(): void
    {
        Http::fake([
            'https://openrouter.test/*' =>
            Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'questions' => [
                                    [
                                        'question' =>
                                        'PHP là gì?',

                                        // Thiếu options.
                                    ],
                                ],
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
            'OCR quiz JSON structure invalid.'
        );

        app(
            OcrQuizParserService::class
        )->parse(
            'Invalid OCR prompt.'
        );
    }
}
