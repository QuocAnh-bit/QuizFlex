<?php

namespace App\Services\RAG\Qdrant;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QdrantService
{
    private string $baseUrl;
    private ?string $apiKey;
    private string $collection;


    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('rag.qdrant.url'),
            '/'
        );

        $this->apiKey = config('rag.qdrant.api_key');
        $this->collection = config('rag.qdrant.collection');
    }
    public function upsertPoint(
        int|string $id,
        array $vector,
        array $payload
    ): array {

        $url = rtrim(
            config('rag.qdrant.url'),
            '/'
        );

        $collection =
            config('rag.qdrant.collection');

        $expectedDimension =
            (int) config(
                'rag.qdrant.dimension',
                4096
            );


        /*
     * Chặn vector sai dimension
     * trước khi gửi sang Qdrant.
     */
        if (count($vector) !== $expectedDimension) {

            throw new \RuntimeException(
                'Vector dimension không đúng. '
                    . 'Expected='
                    . $expectedDimension
                    . ', actual='
                    . count($vector)
            );
        }


        $response =
            $this->client()
            ->put(
                $url
                    . '/collections/'
                    . $collection
                    . '/points?wait=true',
                [
                    'points' => [
                        [
                            'id' => $id,

                            'vector' =>
                            $vector,

                            'payload' =>
                            $payload,
                        ],
                    ],
                ]
            );


        if (!$response->successful()) {

            throw new \RuntimeException(
                'Qdrant upsert HTTP '
                    . $response->status()
                    . ': '
                    . $response->body()
            );
        }


        return $response->json();
    }
    private function client(): PendingRequest
    {
        $request = Http::acceptJson()
            ->timeout(30);

        $apiKey = config(
            'rag.qdrant.api_key'
        );

        if ($apiKey) {
            $request = $request->withHeaders([
                'api-key' => $apiKey,
            ]);
        }

        return $request;
    }

    public function health(): array
    {
        return $this->client()
            ->get($this->baseUrl)
            ->throw()
            ->json();
    }

    public function collections(): array
    {
        return $this->client()
            ->get("{$this->baseUrl}/collections")
            ->throw()
            ->json();
    }

    public function collectionExists(): bool
    {
        $url =
            rtrim(
                config('rag.qdrant.url'),
                '/'
            );

        $collection =
            config(
                'rag.qdrant.collection'
            );

        $response =
            $this->client()
            ->get(
                $url
                    . '/collections/'
                    . $collection
            );

        if ($response->successful()) {
            return true;
        }

        if ($response->status() === 404) {
            return false;
        }

        throw new RuntimeException(
            'Qdrant HTTP '
                . $response->status()
                . ': '
                . $response->body()
        );
    }

    public function createCollection(): array
    {
        if ($this->collectionExists()) {

            return [
                'created' => false,
                'message' =>
                'Collection đã tồn tại.',
            ];
        }

        $url =
            rtrim(
                config('rag.qdrant.url'),
                '/'
            );

        $collection =
            config(
                'rag.qdrant.collection'
            );

        $dimension =
            (int) config(
                'rag.qdrant.dimension'
            );


        $response =
            $this->client()
            ->put(
                $url
                    . '/collections/'
                    . $collection,
                [
                    'vectors' => [
                        'size' =>
                        $dimension,

                        'distance' =>
                        'Cosine',
                    ],
                ]
            );


        if (!$response->successful()) {

            throw new RuntimeException(
                'Không tạo được Qdrant collection. HTTP '
                    . $response->status()
                    . ': '
                    . $response->body()
            );
        }


        return [
            'created' => true,
            'collection' => $collection,
            'dimension' => $dimension,
            'distance' => 'Cosine',
            'response' => $response->json(),
        ];
    }

    public function search(
        array $vector,
        string $subject,
        int $grade,
        int $limit = 5,
        array $unitIds = [],
    ): array {

        $url = rtrim(
            config('rag.qdrant.url'),
            '/'
        );

        $collection =
            config('rag.qdrant.collection');

        $expectedDimension =
            (int) config(
                'rag.qdrant.dimension',
                4096
            );

        if (count($vector) !== $expectedDimension) {
            throw new \RuntimeException(
                'Query vector dimension không đúng. '
                    . 'Expected='
                    . $expectedDimension
                    . ', actual='
                    . count($vector)
            );
        }

        $unitIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $unitIds),
                    fn(int $id): bool => $id > 0
                )
            )
        );

        $must = [
            [
                'key' => 'subject',
                'match' => [
                    'value' => $subject,
                ],
            ],
            [
                'key' => 'grade_min',
                'range' => [
                    'lte' => $grade,
                ],
            ],
            [
                'key' => 'grade_max',
                'range' => [
                    'gte' => $grade,
                ],
            ],
        ];

        if ($unitIds !== []) {
            $must[] = [
                'key' => 'unit_id',
                'match' => [
                    'any' => $unitIds,
                ],
            ];
        }

        $response =
            $this->client()
            ->post(
                $url
                    . '/collections/'
                    . $collection
                    . '/points/query',
                [
                    'query' => $vector,

                    'filter' => [
                        'must' => $must,
                    ],

                    'limit' => $limit,

                    'with_payload' => true,

                    'with_vector' => false,
                ]
            );

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Qdrant search HTTP '
                    . $response->status()
                    . ': '
                    . $response->body()
            );
        }

        return $response->json('result.points')
            ?? [];
    }
    public function upsertPoints(
        array $points
    ): array {

        if (empty($points)) {
            return [];
        }

        $expectedDimension =
            (int) config(
                'rag.qdrant.dimension',
                4096
            );

        foreach ($points as $point) {

            if (
                !isset($point['vector'])
                ||
                count($point['vector'])
                !== $expectedDimension
            ) {
                throw new \RuntimeException(
                    'Có point chứa vector sai dimension.'
                );
            }
        }

        $url = rtrim(
            config('rag.qdrant.url'),
            '/'
        );

        $collection =
            config(
                'rag.qdrant.collection'
            );

        $response =
            $this->client()
            ->timeout(120)
            ->put(
                $url
                    . '/collections/'
                    . $collection
                    . '/points?wait=true',
                [
                    'points' => $points,
                ]
            );

        if (!$response->successful()) {

            throw new \RuntimeException(
                'Qdrant batch upsert HTTP '
                    . $response->status()
                    . ': '
                    . $response->body()
            );
        }

        return $response->json();
    }
}
