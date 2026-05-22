<?php

namespace App\Services\AI;

use GuzzleHttp\Client as GuzzleClient;
use OpenAI;
use OpenAI\Client;
use RuntimeException;
use Throwable;

class AIService
{
    private Client $client;

    public function __construct()
    {
        $apiKey = (string) config('services.deepseek.api_key', '');

        if ($apiKey === '') {
            throw new RuntimeException(
                'DeepSeek API key is missing. Set DEEPSEEK_API_KEY or OPENROUTER_API_KEY.'
            );
        }

        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri((string) config('services.deepseek.base_uri', 'https://api.deepseek.com'))
            ->withHttpClient(new GuzzleClient([
                'verify' => $this->resolveVerifyOption(),
                'timeout' => (int) config('services.deepseek.timeout', 120),
                'connect_timeout' => (int) config('services.deepseek.connect_timeout', 30),
            ]))
            ->make();
    }

    public function generateQuiz(string $prompt, int $count = 10): array
    {
        $result = $this->requestJsonPayload($this->buildPrompt($prompt, $count));

        if (!$this->isGeneratedQuizValid($result['payload'])) {
            throw new RuntimeException('AI JSON structure invalid.');
        }

        if (count($result['payload']['questions']) !== $count) {
            throw new RuntimeException("AI returned " . count($result['payload']['questions']) . " questions, expected {$count}.");
        }

        $quiz = $this->normalizeGeneratedQuiz($result['payload']);
        $quiz['meta'] = [
            'tokens_used' => $result['tokens_used'],
            'raw_json' => $result['raw_json'],
        ];

        return $quiz;
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
        try {
            $response = $this->client->chat()->create([
                'model' => (string) config('services.deepseek.model', 'deepseek-chat'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
            ]);

            $content = $response->choices[0]->message->content ?? null;

            if (!is_string($content) || trim($content) === '') {
                throw new RuntimeException('AI returned empty response.');
            }

            $json = $this->cleanJson($content);
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($payload)) {
                throw new RuntimeException('AI response JSON must decode to an object.');
            }

            return [
                'payload' => $payload,
                'raw_json' => $json,
                'tokens_used' => (int) ($response->usage->totalTokens ?? 0),
            ];
        } catch (Throwable $exception) {
            throw new RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
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

    private function normalizeGeneratedQuiz(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));

        return [
            'title' => $title !== '' ? $title : 'AI Generated Quiz',
            'questions' => collect($data['questions'])
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
        $text = preg_replace('/```json|```/', '', $text);

        return trim((string) $text);
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
