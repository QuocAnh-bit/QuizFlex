<?php

namespace App\AI\Clients;

use App\AI\DTOs\ChatRequest;
use App\AI\DTOs\ChatResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

final class OpenRouterClient
{
    public function generateJson(
        ChatRequest $request
    ): ChatResponse {
        $apiKey = trim(
            (string) config(
                'services.openrouter.api_key'
            )
        );

        if ($apiKey === '') {
            throw new RuntimeException(
                'Missing OPENROUTER_API_KEY.'
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'services.openrouter.base_uri',
                'https://openrouter.ai/api/v1'
            ),
            '/'
        );

        $model = trim(
            (string) (
                $request->model
                ?: config('services.openrouter.model')
            )
        );

        if ($model === '') {
            throw new RuntimeException(
                'Missing OpenRouter model.'
            );
        }

        $payload = [
            'model' => $model,

            'temperature' =>
            $request->temperature,

            'max_tokens' =>
            $request->maxTokens,

            'messages' => [
                [
                    'role' => 'system',
                    'content' =>
                    $request->systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' =>
                    $request->userPrompt,
                ],
            ],
        ];

        if ($request->responseFormat !== null) {
            $payload['response_format'] =
                $request->responseFormat;
        }

        $response = Http::withHeaders(
            $this->buildHeaders($apiKey)
        )
            ->acceptJson()
            ->timeout(
                (int) config(
                    'services.openrouter.timeout',
                    120
                )
            )
            ->connectTimeout(
                (int) config(
                    'services.openrouter.connect_timeout',
                    30
                )
            )
            ->post(
                $baseUrl . '/chat/completions',
                $payload
            );

        if (!$response->successful()) {
            $message = $this
                ->extractApiErrorMessage($response);

            throw new RuntimeException(
                $message
                    ?: 'OpenRouter HTTP '
                    . $response->status()
            );
        }

        $content = $response->json(
            'choices.0.message.content'
        );

        if (
            !is_string($content)
            || trim($content) === ''
        ) {
            throw new RuntimeException(
                'OpenRouter returned empty content.'
            );
        }

        $rawJson = $this->cleanJson($content);

        try {
            $data = json_decode(
                $rawJson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'OpenRouter returned invalid JSON: '
                    . $exception->getMessage(),
                previous: $exception
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'OpenRouter JSON must be an object.'
            );
        }

        return new ChatResponse(
            data: $data,
            rawJson: $rawJson,
            provider: 'openrouter',
            model: (string) (
                $response->json('model')
                ?? $model
            ),
            inputTokens: (int) $response->json(
                'usage.prompt_tokens',
                0
            ),
            outputTokens: (int) $response->json(
                'usage.completion_tokens',
                0
            ),
        );
    }

    private function buildHeaders(
        string $apiKey
    ): array {
        $headers = [
            'Authorization' =>
            'Bearer ' . $apiKey,

            'Content-Type' =>
            'application/json',

            'Accept' =>
            'application/json',
        ];

        $referer = trim(
            (string) config(
                'services.openrouter.http_referer',
                config('app.url')
            )
        );

        $title = trim(
            (string) config(
                'services.openrouter.title',
                config('app.name')
            )
        );

        if ($referer !== '') {
            $headers['HTTP-Referer'] = $referer;
        }

        if ($title !== '') {
            $headers['X-Title'] = $title;
        }

        return $headers;
    }

    private function extractApiErrorMessage(
        Response $response
    ): ?string {
        $decoded = $response->json();

        if (!is_array($decoded)) {
            $body = trim($response->body());

            return $body !== ''
                ? $body
                : null;
        }

        $message = $decoded['error']['message']
            ?? $decoded['message']
            ?? $decoded['error']
            ?? null;

        if (is_array($message)) {
            return json_encode(
                $message,
                JSON_UNESCAPED_UNICODE
            );
        }

        return is_string($message)
            ? $message
            : null;
    }

    private function cleanJson(
        string $text
    ): string {
        $text = trim(
            (string) preg_replace(
                '/```(?:json)?|```/i',
                '',
                $text
            )
        );

        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');

        if (
            $firstBrace !== false
            && $lastBrace !== false
            && $lastBrace > $firstBrace
        ) {
            return substr(
                $text,
                $firstBrace,
                $lastBrace - $firstBrace + 1
            );
        }

        return $text;
    }
}
