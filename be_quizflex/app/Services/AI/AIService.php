<?php

namespace App\Services\AI;

use OpenAI;

class AIService
{
    private $client;

    public function __construct()
    {
        $this->client = OpenAI::factory()
            ->withApiKey(env('OPENROUTER_API_KEY'))
            ->withBaseUri('https://api.deepseek.com')
            ->withHttpHeader('HTTP-Referer', 'http://localhost')
            ->withHttpHeader('X-Title', 'QuizFlex')
            ->make();
    }

    public function parseQuiz(string $prompt): array
    {
        $response = $this->client->chat()->create([
            'model' => 'deepseek-v4-flash',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.2,
        ]);

        $content = $response->choices[0]->message->content;

        $decoded = json_decode($content, true);

        if (!$decoded) {
            throw new \Exception('AI trả JSON lỗi');
        }

        return $decoded;
    }
}
