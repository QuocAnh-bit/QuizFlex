<?php

namespace App\Services\AI;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;
use Throwable;

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
        $count = max(1, min(50, $count));
        $lastException = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $result = $this->requestJsonPayload($this->buildPrompt($prompt, $count));

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

    public function parseQuiz(string $prompt): array
    {
        $result = $this->requestJsonPayload($prompt);

        if (!$this->isParsedQuizValid($result['payload'])) {
            throw new RuntimeException('OCR quiz JSON structure invalid.');
        }

        return $result['payload'];
    }

    private function buildPrompt(string $prompt, int $count): string
    {
        return <<<PROMPT
Create exactly {$count} multiple-choice quiz questions for the topic below.

Topic:
{$prompt}

Rules:
- Return JSON only
- No markdown
- No explanation
- Each question must have exactly 4 answers
- Exactly 1 answer must have "is_correct": true
- Keep the language consistent with the topic request

JSON format:
{
  "title": "Quiz title",
  "questions": [
    {
      "content": "Question text",
      "answers": [
        { "content": "Answer A", "is_correct": true },
        { "content": "Answer B", "is_correct": false },
        { "content": "Answer C", "is_correct": false },
        { "content": "Answer D", "is_correct": false }
      ]
    }
  ]
}
PROMPT;
    }

    private function requestJsonPayload(string $prompt): array
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

    private function sendChatCompletion(array $payload): array
{
    try {
        $headers = $this->buildHeaders();

        if (
            empty($headers['Authorization']) ||
            trim($headers['Authorization']) === 'Bearer' ||
            trim($headers['Authorization']) === 'Bearer '
        ) {
            throw new RuntimeException('Authorization header is empty. Check DEEPSEEK_API_KEY or OPENROUTER_API_KEY in backend .env.');
        }

        $url = $this->baseUri . '/chat/completions';

        $response = $this->http->post($url, [
            'headers' => $headers,
            'json' => $payload,
        ]);

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

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

        if ($limit !== null) {
            $questions = $questions->take($limit);
        }

        return [
            'title' => $title !== '' ? $title : 'AI Generated Quiz',
            'questions' => $questions
                ->map(fn (array $question) => [
                    'content' => trim((string) $question['content']),
                    'answers' => collect($question['answers'])
                        ->map(fn (array $answer) => [
                            'content' => trim((string) $answer['content']),
                            'is_correct' => (bool) $answer['is_correct'],
                        ])
                        ->values()
                        ->all(),
                ])
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
}
