<?php

namespace App\Services\RAG\Embedding;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterEmbeddingService
{
    public function embed(
        string $text
    ): array {

        $text = trim($text);

        if ($text === '') {

            throw new RuntimeException(
                'Embedding text đang rỗng.'
            );
        }


        $apiKey =
            config(
                'rag.openrouter.api_key'
            );

        $baseUrl =
            rtrim(
                config(
                    'rag.openrouter.base_url'
                ),
                '/'
            );

        $model =
            config(
                'rag.openrouter.embedding_model'
            );


        if (!$apiKey) {

            throw new RuntimeException(
                'Thiếu OPENROUTER_API_KEY.'
            );
        }


        if (!$model) {

            throw new RuntimeException(
                'Thiếu OPENROUTER_EMBEDDING_MODEL.'
            );
        }


        $response =
            Http::withToken(
                $apiKey
            )
            ->acceptJson()
            ->timeout(120)
            ->retry(
                3,
                1500,
                throw: false
            )
            ->post(
                $baseUrl . '/embeddings',
                [
                    'model' =>
                    $model,

                    'input' =>
                    $text,

                    /*
                     * Float là dạng phù hợp
                     * để đưa trực tiếp sang Qdrant.
                     */
                    'encoding_format' =>
                    'float',
                ]
            );


        if (!$response->successful()) {

            throw new RuntimeException(
                'OpenRouter Embedding HTTP '
                    . $response->status()
                    . ': '
                    . $response->body()
            );
        }


        $vector =
            $response->json(
                'data.0.embedding'
            );


        if (
            !is_array($vector)
            ||
            empty($vector)
        ) {

            throw new RuntimeException(
                'OpenRouter không trả embedding vector.'
            );
        }


        /*
         * Đảm bảo vector chỉ chứa số.
         */
        foreach ($vector as $value) {

            if (!is_numeric($value)) {

                throw new RuntimeException(
                    'Embedding chứa giá trị không phải số.'
                );
            }
        }


        return [

            'vector' =>
            array_map(
                'floatval',
                $vector
            ),

            'dimension' =>
            count($vector),

            'model' =>
            $response->json('model')
                ?? $model,

            'usage' =>
            $response->json('usage')
                ?? [],
        ];
    }
    public function embedBatch(array $texts): array
    {
        $texts = array_values(
            array_filter(
                array_map(
                    fn($text) => trim((string) $text),
                    $texts
                ),
                fn($text) => $text !== ''
            )
        );

        if (empty($texts)) {
            throw new \RuntimeException(
                'Danh sách embedding text đang rỗng.'
            );
        }

        $apiKey = config('rag.openrouter.api_key');

        $baseUrl = rtrim(
            config('rag.openrouter.base_url'),
            '/'
        );

        $model = config(
            'rag.openrouter.embedding_model'
        );

        if (!$apiKey) {
            throw new \RuntimeException(
                'Thiếu OPENROUTER_API_KEY.'
            );
        }

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(180)
            ->retry(
                3,
                1500,
                throw: false
            )
            ->post(
                $baseUrl . '/embeddings',
                [
                    'model' => $model,
                    'input' => $texts,
                    'encoding_format' => 'float',
                ]
            );

        if (!$response->successful()) {
            throw new \RuntimeException(
                'OpenRouter Embedding HTTP '
                    . $response->status()
                    . ': '
                    . $response->body()
            );
        }

        $data = $response->json('data');

        if (!is_array($data)) {
            throw new \RuntimeException(
                'OpenRouter không trả danh sách embedding.'
            );
        }

        /*
     * OpenRouter có index cho từng input.
     */
        usort(
            $data,
            fn($a, $b) => ($a['index'] ?? 0)
                <=>
                ($b['index'] ?? 0)
        );

        if (count($data) !== count($texts)) {
            throw new \RuntimeException(
                'Số vector trả về không khớp số text gửi đi.'
            );
        }

        $vectors = [];

        foreach ($data as $item) {

            $vector =
                $item['embedding']
                ?? null;

            if (
                !is_array($vector)
                ||
                count($vector) !== 4096
            ) {
                throw new \RuntimeException(
                    'Embedding vector không đúng dimension 4096.'
                );
            }

            $vectors[] =
                array_map(
                    'floatval',
                    $vector
                );
        }

        return [
            'vectors' => $vectors,

            'model' =>
            $response->json('model')
                ?? $model,

            'usage' =>
            $response->json('usage')
                ?? [],
        ];
    }
}
