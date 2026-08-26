<?php

namespace App\Services\AI;

use HelgeSverre\Mistral\Mistral;
use JsonException;
use RuntimeException;
use Throwable;

final class MistralOcrQuizService
{
    private const DEFAULT_QUESTIONS_PER_CHUNK = 10;
    private const MAX_CHUNK_CHARACTERS = 20000;

    public function convertToQuiz(
        string $filePath,
        string $extension
    ): array {
        if (!is_file($filePath)) {
            throw new RuntimeException(
                'File OCR không tồn tại.'
            );
        }

        $apiKey = trim(
            (string) config(
                'services.mistral.api_key',
                ''
            )
        );

        if ($apiKey === '') {
            throw new RuntimeException(
                'Thiếu MISTRAL_API_KEY.'
            );
        }

        $mimeType = $this
            ->getMistralMimeType($extension);

        $content = file_get_contents($filePath);

        if (
            $content === false
            || $content === ''
        ) {
            throw new RuntimeException(
                'Không đọc được nội dung file.'
            );
        }

        $mistral = new Mistral($apiKey);

        $fullText = $this->extractText(
            mistral: $mistral,
            content: $content,
            mimeType: $mimeType,
        );

        return $this->convertTextToQuiz(
            mistral: $mistral,
            fullText: $fullText,
        );
    }

    private function extractText(
        Mistral $mistral,
        string $content,
        string $mimeType
    ): string {
        try {
            $ocrResponse =
                $mistral->ocr()->processBase64(
                    base64: base64_encode($content),

                    mimeType: $mimeType,

                    model: (string) config(
                        'services.mistral.ocr_model',
                        'mistral-ocr-latest'
                    ),

                    includeImageBase64: false,
                );

            $ocrDto =
                $ocrResponse->dtoOrFail();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Mistral OCR thất bại: '
                    . $this->extractMistralError(
                        $exception
                    ),
                previous: $exception
            );
        }

        $fullText = '';

        foreach (
            $ocrDto->pages ?? []
            as $page
        ) {
            $fullText .= (
                $page->markdown ?? ''
            ) . "\n\n";
        }

        $fullText = trim($fullText);

        if ($fullText === '') {
            throw new RuntimeException(
                'Mistral OCR đọc ra text rỗng.'
            );
        }

        return $fullText;
    }

    private function convertTextToQuiz(
        Mistral $mistral,
        string $fullText
    ): array {
        $questionsPerChunk = max(
            1,
            (int) config(
                'services.mistral.questions_per_chunk',
                self::DEFAULT_QUESTIONS_PER_CHUNK
            )
        );

        $chunks = $this
            ->splitQuestionsIntoChunks(
                text: $fullText,
                questionsPerChunk: $questionsPerChunk,
            );

        $allQuestions = [];

        foreach (
            $chunks
            as $chunkIndex => $chunkText
        ) {
            $questions = $this->parseChunk(
                mistral: $mistral,
                chunkText: $chunkText,
                chunkIndex: $chunkIndex,
            );

            $allQuestions = array_merge(
                $allQuestions,
                $questions
            );
        }

        return [
            'questions' => $allQuestions,
        ];
    }

    private function parseChunk(
        Mistral $mistral,
        string $chunkText,
        int $chunkIndex
    ): array {
        $messages = [
            [
                'role' => 'system',

                'content' =>
                'Bạn là AI chuyên chuyển OCR '
                    . 'đề thi thành JSON quiz. '
                    . 'Chỉ trả JSON hợp lệ, '
                    . 'không markdown, '
                    . 'không giải thích. '
                    . 'Không tự giải bài.',
            ],
            [
                'role' => 'user',

                'content' =>
                $this->buildQuizJsonPrompt(
                    $chunkText
                ),
            ],
        ];

        try {
            $aiResponse =
                $mistral->chat()->create(
                    messages: $messages,

                    model: (string) config(
                        'services.mistral.chat_model',
                        'mistral-small-latest'
                    ),

                    maxTokens: (int) config(
                        'services.mistral.chat_max_tokens',
                        3000
                    ),

                    responseFormat: [
                        'type' =>
                        'json_object',
                    ],
                );

            $json = (string) (
                $aiResponse
                    ->dtoOrFail()
                    ->choices[0]
                ->message
                ->content
                ?? ''
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Mistral Chat thất bại ở chunk '
                    . ($chunkIndex + 1)
                    . ': '
                    . $this->extractMistralError(
                        $exception
                    ),
                previous: $exception
            );
        }

        $json = $this->cleanJson($json);

        try {
            $data = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Mistral parse JSON thất bại '
                    . 'ở chunk '
                    . ($chunkIndex + 1)
                    . ': '
                    . $json,
                previous: $exception
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'Mistral trả dữ liệu không hợp lệ '
                    . 'ở chunk '
                    . ($chunkIndex + 1)
            );
        }

        if (
            !isset($data['questions'])
            || !is_array($data['questions'])
        ) {
            return [];
        }

        return $data['questions'];
    }

    private function extractMistralError(
        Throwable $exception
    ): string {
        if (
            method_exists(
                $exception,
                'getResponse'
            )
            && $exception->getResponse()
        ) {
            $responseBody = (string)
            $exception
                ->getResponse()
                ->getBody();

            $decoded = json_decode(
                $responseBody,
                true
            );

            if (is_array($decoded)) {
                $message =
                    $decoded['message']
                    ?? $decoded['error']['message']
                    ?? null;

                if (is_string($message)) {
                    return $message;
                }
            }
        }

        return $exception->getMessage();
    }

    private function buildQuizJsonPrompt(
        string $fullText
    ): string {
        return <<<PROMPT
Chuyển nội dung OCR sau thành JSON quiz.

QUY TẮC:
- Trả về object JSON có key questions.
- questions là mảng câu hỏi.
- Mỗi câu gồm question, options, correct_answer.
- options là object A/B/C/D nếu có đáp án.
- Nếu câu hỏi không có đáp án A/B/C/D thì options = null.
- Không tự giải bài.
- Không tự sinh đáp án nếu OCR không có.
- correct_answer chỉ là A/B/C/D hoặc null.

QUY TẮC TOÁN HỌC:
- Nếu gặp biểu thức toán học, phải chuyển thành LaTeX inline và bọc bằng \$...\$.
- Chỉ bọc phần công thức, không bọc cả câu văn.
- Text thường giữ nguyên tiếng Việt.
- Các ký hiệu sau phải coi là toán học:
  + phân số: 1/2, a/b, \\frac{}{}
  + căn: √x, sqrt(x)
  + lũy thừa: x2, x^2, x²
  + chỉ số dưới: A1, x_1
  + phương trình: x + 1 = 0
  + bất phương trình: <, >, ≤, ≥
  + hình học: ABC, A'B'C', S.ABCD, ABC.A'B'C'
  + ký hiệu: π, ∞, ∈, ∉, ∪, ∩
  + sin, cos, tan, log, ln

FORMAT:
{
  "questions": [
    {
      "question": "...",
      "options": {
        "A": "...",
        "B": "...",
        "C": "...",
        "D": "..."
      },
      "correct_answer": null
    }
  ]
}

OCR:
{$fullText}
PROMPT;
    }

    private function getMistralMimeType(
        string $extension
    ): string {
        $extension = ltrim(
            strtolower(trim($extension)),
            '.'
        );

        return match ($extension) {
            'jpg', 'jpeg' =>
            'image/jpeg',

            'png' =>
            'image/png',

            'webp' =>
            'image/webp',

            'pdf' =>
            'application/pdf',

            default =>
            throw new RuntimeException(
                'Định dạng file không hỗ trợ: '
                    . $extension
            ),
        };
    }

    private function splitQuestionsIntoChunks(
        string $text,
        int $questionsPerChunk =
        self::DEFAULT_QUESTIONS_PER_CHUNK
    ): array {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $questionsPerChunk = max(
            1,
            $questionsPerChunk
        );

        $parts = preg_split(
            '/(?=Câu\s*\d+[\.:])/iu',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        /*
         * Không tìm thấy cấu trúc "Câu 1",
         * chia theo độ dài thay vì cắt mất
         * phần sau 20.000 ký tự.
         */
        if (
            !$parts
            || count($parts) <= 1
        ) {
            return $this->splitTextByLength(
                $text,
                self::MAX_CHUNK_CHARACTERS
            );
        }

        $chunks = [];
        $current = [];

        foreach ($parts as $part) {
            $current[] = trim($part);

            if (
                count($current)
                >= $questionsPerChunk
            ) {
                $chunks[] = implode(
                    "\n\n",
                    $current
                );

                $current = [];
            }
        }

        if ($current !== []) {
            $chunks[] = implode(
                "\n\n",
                $current
            );
        }

        return $chunks;
    }

    private function splitTextByLength(
        string $text,
        int $maxCharacters
    ): array {
        $maxCharacters = max(
            1,
            $maxCharacters
        );

        $length = mb_strlen($text);

        if ($length <= $maxCharacters) {
            return [$text];
        }

        $chunks = [];

        for (
            $offset = 0;
            $offset < $length;
            $offset += $maxCharacters
        ) {
            $chunk = trim(
                mb_substr(
                    $text,
                    $offset,
                    $maxCharacters
                )
            );

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    private function cleanJson(
        string $json
    ): string {
        $json = trim(
            (string) preg_replace(
                '/```(?:json)?|```/i',
                '',
                $json
            )
        );

        $firstBrace = strpos($json, '{');
        $lastBrace = strrpos($json, '}');

        if (
            $firstBrace !== false
            && $lastBrace !== false
            && $lastBrace > $firstBrace
        ) {
            return substr(
                $json,
                $firstBrace,
                $lastBrace - $firstBrace + 1
            );
        }

        return $json;
    }
}
