<?php

namespace App\Services\AI;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;
use Throwable;
use HelgeSverre\Mistral\Mistral;


class AIService
{
    private GuzzleClient $http;
    private string $apiKey;
    private string $baseUri;
    private string $model;
    private bool $usesOpenRouter;

    public function __construct()
    {
        $openrouterKey = trim((string) config('services.openrouter.api_key', ''));
        $deepseekKey = trim((string) config('services.deepseek.api_key', ''));

        $this->usesOpenRouter = $openrouterKey !== '';

        if ($this->usesOpenRouter) {
            $this->apiKey = $openrouterKey;
            $this->baseUri = $this->normalizeBaseUri((string) config('services.openrouter.base_uri', 'https://openrouter.ai/api/v1'));
            $this->model = trim((string) config('services.openrouter.model', 'deepseek/deepseek-chat-v3-0324')) ?: 'deepseek/deepseek-chat-v3-0324';

            $timeout = (int) config('services.openrouter.timeout', config('services.deepseek.timeout', 120));
            $connectTimeout = (int) config('services.openrouter.connect_timeout', config('services.deepseek.connect_timeout', 30));
        } else {
            $this->apiKey = $deepseekKey;
            $this->baseUri = $this->normalizeBaseUri((string) config('services.deepseek.base_uri', 'https://api.deepseek.com'));
            $this->model = trim((string) config('services.deepseek.model', 'deepseek-chat')) ?: 'deepseek-chat';

            $timeout = (int) config('services.deepseek.timeout', 120);
            $connectTimeout = (int) config('services.deepseek.connect_timeout', 30);
        }

        if ($this->apiKey === '') {
            throw new RuntimeException('Missing AI API key. Set OPENROUTER_API_KEY or DEEPSEEK_API_KEY in backend .env, then run php artisan optimize:clear.');
        }

        $this->http = new GuzzleClient([
            'base_uri' => $this->baseUri . '/',
            'verify' => $this->resolveVerifyOption(),
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
        ]);
    }

    public function generateQuiz(string $prompt, int $count = 10): array
    {
        $count = max(1, $count);
        // ~200 tokens/question + 300 overhead
        $maxTokens = max(2000, $count * 250 + 300);
        $lastException = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $result = $this->requestJsonPayload($this->buildPrompt($prompt, $count), $maxTokens);

                if (!$this->isGeneratedQuizValid($result['payload'])) {
                    throw new RuntimeException('AI JSON structure invalid.');
                }

                $quiz = $this->normalizeGeneratedQuiz($result['payload'], $count);
                $quiz['meta'] = [
                    'tokens_used' => $result['tokens_used'],
                    'raw_json' => $result['raw_json'],
                    'requested_count' => $count,
                    'actual_count' => count($quiz['questions']),
                    'provider' => $this->usesOpenRouter ? 'openrouter' : 'deepseek',
                    'model' => $this->model,
                ];

                return $quiz;
            } catch (Throwable $exception) {
                $lastException = $exception;
            }
        }

        throw new RuntimeException($lastException?->getMessage() ?: 'AI generation failed.');
    }

    private function buildPrompt(string $prompt, int $count): string
    {
        return <<<PROMPT
You are a quiz generator. Your ONLY job is to generate exactly {$count} multiple-choice questions.

CRITICAL RULES - YOU MUST FOLLOW EXACTLY:
- Generate EXACTLY {$count} questions. Not more, not less.
- Return JSON only. No markdown. No explanation. No extra text.
- Each question must have exactly 4 answer options labeled A, B, C, D.
- Exactly 1 answer must have "is_correct": true, the other 3 must have "is_correct": false.
- The language of questions and answers must match the language used in the Topic/Request below.
- Do NOT include the number of questions in the title.

JSON format (return this exact structure):
{
  "title": "Short quiz title here",
  "questions": [
    {
      "content": "Question text here",
      "answers": [
        { "content": "Option A text", "is_correct": true },
        { "content": "Option B text", "is_correct": false },
        { "content": "Option C text", "is_correct": false },
        { "content": "Option D text", "is_correct": false }
      ]
    }
  ]
}

Topic/Request (use this for content only, ignore any number of questions mentioned here):
{$prompt}

Remember: generate EXACTLY {$count} questions.
PROMPT;
    }




    private function sendChatCompletion(array $payload): array
    {
        try {
            $headers = $this->buildHeaders();

            if (
                empty($headers['Authorization']) ||
                trim($headers['Authorization']) === 'Bearer' ||
                trim($headers['Authorization']) === 'Bearer '
            ) {
                throw new RuntimeException('Authorization header is empty. Check OPENROUTER_API_KEY in .env.');
            }

            $url = $this->baseUri . '/chat/completions';

            $response = $this->http->post($url, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            $body = (string) $response->getBody();
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            logger()->info('DEEPSEEK RESPONSE', [
                'model' => $decoded['model'] ?? null,
                'finish_reason' => $decoded['choices'][0]['finish_reason'] ?? null,
                'usage' => $decoded['usage'] ?? null,
                'content' => $decoded['choices'][0]['message']['content'] ?? null,
            ]);

            $content = $decoded['choices'][0]['message']['content'] ?? null;

            if (!is_string($content) || trim($content) === '') {
                throw new RuntimeException('AI returned empty response.');
            }

            $json = $this->cleanJson($content);
            $generated = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($generated)) {
                throw new RuntimeException('AI response JSON must decode to an object.');
            }

            return [
                'payload' => $generated,
                'raw_json' => $json,
                'tokens_used' => (int) ($decoded['usage']['total_tokens'] ?? 0),
            ];
        } catch (RequestException $exception) {
            $responseBody = $exception->getResponse() ? (string) $exception->getResponse()->getBody() : '';
            $message = $this->extractApiErrorMessage($responseBody) ?: $exception->getMessage();

            throw new RuntimeException($message, (int) $exception->getCode(), $exception);
        } catch (Throwable $exception) {
            throw new RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    private function buildHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->usesOpenRouter) {
            $referer = trim((string) config(
                'services.openrouter.http_referer',
                config('services.deepseek.openrouter_http_referer', config('app.url', 'http://localhost:8000'))
            ));

            $title = trim((string) config(
                'services.openrouter.title',
                config('services.deepseek.openrouter_title', config('app.name', 'QuizFlex'))
            ));

            if ($referer !== '') {
                $headers['HTTP-Referer'] = $referer;
            }

            if ($title !== '') {
                $headers['X-Title'] = $title;
            }
        }

        return $headers;
    }

    private function extractApiErrorMessage(string $responseBody): ?string
    {
        if (trim($responseBody) === '') {
            return null;
        }

        $decoded = json_decode($responseBody, true);

        if (!is_array($decoded)) {
            return trim($responseBody);
        }

        $message = $decoded['error']['message']
            ?? $decoded['message']
            ?? $decoded['error']
            ?? null;

        if (is_array($message)) {
            return json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        return is_string($message) ? $message : null;
    }

    private function isGeneratedQuizValid(array $data): bool
    {
        if (!isset($data['questions']) || !is_array($data['questions']) || $data['questions'] === []) {
            return false;
        }

        foreach ($data['questions'] as $question) {
            if (!is_array($question) || !isset($question['content']) || !is_string($question['content'])) {
                return false;
            }

            if (!isset($question['answers']) || !is_array($question['answers']) || count($question['answers']) !== 4) {
                return false;
            }

            $correctAnswers = 0;

            foreach ($question['answers'] as $answer) {
                if (!is_array($answer) || !isset($answer['content']) || !is_string($answer['content'])) {
                    return false;
                }

                if (!array_key_exists('is_correct', $answer) || !is_bool($answer['is_correct'])) {
                    return false;
                }

                if ($answer['is_correct'] === true) {
                    $correctAnswers++;
                }
            }

            if ($correctAnswers !== 1) {
                return false;
            }
        }

        return true;
    }

    private function normalizeGeneratedQuiz(array $data, ?int $limit = null): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $questions = collect($data['questions']);

        // Nếu AI trả thừa câu thì cắt bớt, nếu trả thiếu thì giữ nguyên (không thể sinh thêm)
        if ($limit !== null && $questions->count() > $limit) {
            $questions = $questions->take($limit);
        }

        return [
            'title' => $title !== '' ? $title : 'AI Generated Quiz',
            'questions' => $questions
                ->map(function (array $question): array {
                    $answers = collect($question['answers'])
                        ->map(fn(array $answer) => [
                            'content' => trim((string) $answer['content']),
                            'is_correct' => (bool) $answer['is_correct'],
                        ])
                        ->all();

                    shuffle($answers);

                    return [
                        'content' => trim((string) $question['content']),
                        'answers' => $answers,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function isParsedQuizValid(array $data): bool
    {
        if (!isset($data['questions']) || !is_array($data['questions'])) {
            return false;
        }

        foreach ($data['questions'] as $question) {
            if (!is_array($question)) {
                return false;
            }

            if (!isset($question['question']) || !is_string($question['question'])) {
                return false;
            }

            if (!isset($question['options']) || !is_array($question['options'])) {
                return false;
            }

            foreach (['A', 'B', 'C', 'D'] as $optionKey) {
                if (!array_key_exists($optionKey, $question['options'])) {
                    return false;
                }
            }

            if (isset($question['correct_answer']) && $question['correct_answer'] !== null) {
                if (!in_array($question['correct_answer'], ['A', 'B', 'C', 'D'], true)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function cleanJson(string $text): string
    {
        $text = trim((string) preg_replace('/```(?:json)?|```/i', '', $text));

        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');

        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            return substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        return $text;
    }

    private function normalizeBaseUri(string $baseUri): string
    {
        $baseUri = trim($baseUri);

        if ($baseUri === '') {
            return $this->usesOpenRouter ? 'https://openrouter.ai/api/v1' : 'https://api.deepseek.com';
        }

        return rtrim($baseUri, '/');
    }

    private function resolveVerifyOption(): bool|string
    {
        $configuredCaBundle = config('services.deepseek.ca_bundle');

        if (is_string($configuredCaBundle) && $configuredCaBundle !== '' && is_file($configuredCaBundle)) {
            return $configuredCaBundle;
        }

        $detectedCaBundle = $this->detectCaBundle();

        if ($detectedCaBundle !== null) {
            return $detectedCaBundle;
        }

        return (bool) config('services.deepseek.ssl_verify', true);
    }

    private function detectCaBundle(): ?string
    {
        $phpBinaryDir = dirname(PHP_BINARY);
        $laragonRoot = dirname(dirname(dirname(dirname(PHP_BINARY))));

        $candidates = array_filter([
            ini_get('curl.cainfo') ?: null,
            ini_get('openssl.cafile') ?: null,
            base_path('cacert.pem'),
            $phpBinaryDir . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem',
            $laragonRoot . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem',
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }



    public function mistralOcrToQuizJson(string $filePath, string $extension): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('File OCR không tồn tại.');
        }

        $apiKey = trim((string) config('services.mistral.api_key', ''));

        if ($apiKey === '') {
            throw new RuntimeException('Thiếu MISTRAL_API_KEY.');
        }

        $mimeType = $this->getMistralMimeType($extension);

        $content = file_get_contents($filePath);

        if ($content === false || $content === '') {
            throw new RuntimeException('Không đọc được nội dung file.');
        }

        $base64 = base64_encode($content);

        $mistral = new Mistral($apiKey);

        try {
            $ocrResponse = $mistral->ocr()->processBase64(
                base64: $base64,
                mimeType: $mimeType,
                model: config('services.mistral.ocr_model', 'mistral-ocr-latest'),
                includeImageBase64: false,
            );

            $ocrDto = $ocrResponse->dtoOrFail();
        } catch (\Throwable $e) {
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $responseBody = (string) $e->getResponse()->getBody();
                $decoded = json_decode($responseBody, true);
                $message = $decoded['message'] ?? $decoded['error']['message'] ?? $e->getMessage();
                throw new RuntimeException('Mistral OCR thất bại: ' . $message);
            }
            throw new RuntimeException('Mistral OCR thất bại: ' . $e->getMessage());
        }

        $fullText = '';

        foreach ($ocrDto->pages ?? [] as $page) {
            $fullText .= ($page->markdown ?? '') . "\n\n";
        }

        $fullText = trim($fullText);

        if ($fullText === '') {
            throw new RuntimeException('Mistral OCR đọc ra text rỗng.');
        }

        $chunks = $this->splitQuestionsIntoChunks($fullText, 10);

        $allQuestions = [];

        foreach ($chunks as $chunkIndex => $chunkText) {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'Bạn là AI chuyên chuyển OCR đề thi thành JSON quiz. Chỉ trả JSON hợp lệ, không markdown, không giải thích. Không tự giải bài.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildQuizJsonPrompt($chunkText),
                ],
            ];

            try {
                $aiResponse = $mistral->chat()->create(
                    messages: $messages,
                    model: config('services.mistral.chat_model', 'mistral-small-latest'),
                    maxTokens: 3000,
                    responseFormat: ['type' => 'json_object'],
                );
            } catch (\Throwable $e) {
                throw new RuntimeException(
                    'Mistral Chat thất bại ở chunk ' . ($chunkIndex + 1) . ': ' . $e->getMessage()
                );
            }

            $json = $aiResponse->dtoOrFail()->choices[0]->message->content ?? '';

            $data = json_decode($json, true);

            if (!is_array($data)) {
                throw new RuntimeException(
                    'Mistral parse JSON thất bại ở chunk ' . ($chunkIndex + 1) . ': ' . $json
                );
            }

            if (!isset($data['questions']) || !is_array($data['questions'])) {
                continue;
            }

            $allQuestions = array_merge($allQuestions, $data['questions']);
        }

        return [
            'questions' => $allQuestions,
        ];
    }

    private function buildQuizJsonPrompt(string $fullText): string
    {
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

    private function getMistralMimeType(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => throw new RuntimeException("Định dạng file không hỗ trợ: {$extension}"),
        };
    }

    private function splitQuestionsIntoChunks(string $text, int $questionsPerChunk = 10): array
    {
        $parts = preg_split('/(?=Câu\s*\d+[\.:])/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (!$parts || count($parts) <= 1) {
            return [mb_substr($text, 0, 20000)];
        }

        $chunks = [];
        $current = [];

        foreach ($parts as $part) {
            $current[] = trim($part);

            if (count($current) >= $questionsPerChunk) {
                $chunks[] = implode("\n\n", $current);
                $current = [];
            }
        }

        if (!empty($current)) {
            $chunks[] = implode("\n\n", $current);
        }

        return $chunks;
    }
    private function quizResponseFormat(): array
    {
        return [
            'type' => 'object',

            'additionalProperties' => false,

            'properties' => [
                'questions' => [
                    'type' => 'array',

                    'items' => [
                        'type' => 'object',

                        'additionalProperties' => false,

                        'properties' => [
                            'question' => [
                                'type' => 'string',
                            ],

                            'options' => [
                                'type' => [
                                    'object',
                                    'null',
                                ],

                                'additionalProperties' => false,

                                'properties' => [
                                    'A' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],

                                    'B' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],

                                    'C' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],

                                    'D' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],
                                ],
                            ],

                            'correct_answer' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],

                                'enum' => [
                                    'A',
                                    'B',
                                    'C',
                                    'D',
                                    null,
                                ],
                            ],
                        ],

                        'required' => [
                            'question',
                        ],
                    ],
                ],
            ],

            'required' => [
                'questions',
            ],
        ];
    }


    // public function generateQuiz(string $prompt, int $count = 10): array
    // {
    //     $fullPrompt = $this->buildGenerateQuizPrompt($prompt, $count);
    //     $result = $this->requestJsonPayload($fullPrompt);

    //     if (!$this->isGeneratedQuizValid($result['payload'])) {
    //         throw new RuntimeException('AI quiz JSON structure invalid.');
    //     }

    //     $normalized = $this->normalizeGeneratedQuiz($result['payload'], $count);
    //     $normalized['meta'] = ['tokens_used' => $result['tokens_used']];

    //     return $normalized;
    // }

    //     private function buildGenerateQuizPrompt(string $prompt, int $count): string
    //     {
    //         return <<<PROMPT
    // You are a quiz generator. Generate exactly {$count} quiz questions based on the request below.

    // Rules:
    // - Return ONLY valid JSON, no markdown, no explanation.
    // - Each question must have exactly 4 answer options.
    // - Exactly 1 answer must be correct (is_correct: true), others false.
    // - Questions and answers must match the language specified in the request.
    // - Generate a short quiz title.

    // JSON format:
    // {
    //   "title": "...",
    //   "questions": [
    //     {
    //       "content": "Question text here",
    //       "answers": [
    //         { "content": "Option A", "is_correct": true },
    //         { "content": "Option B", "is_correct": false },
    //         { "content": "Option C", "is_correct": false },
    //         { "content": "Option D", "is_correct": false }
    //       ]
    //     }
    //   ]
    // }

    // Request:
    // {$prompt}
    // PROMPT;
    //     }

    public function parseQuiz(string $prompt): array
    {
        $result = $this->requestJsonPayload($prompt);

        if (!$this->isParsedQuizValid($result['payload'])) {
            throw new RuntimeException('OCR quiz JSON structure invalid.');
        }

        return $result['payload'];
    }



    private function requestJsonPayload(string $prompt, int $maxTokens = 2000): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You generate valid JSON only. Do not wrap JSON in markdown.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.4,
            'max_tokens' => $maxTokens,
        ];

        if (!$this->usesOpenRouter) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            return $this->sendChatCompletion($payload);
        } catch (RuntimeException $exception) {
            if (!str_contains(strtolower($exception->getMessage()), 'response_format')) {
                throw $exception;
            }

            unset($payload['response_format']);
            return $this->sendChatCompletion($payload);
        }
    }
}
