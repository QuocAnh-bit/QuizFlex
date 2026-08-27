<?php

namespace Tests\Unit\RAG;

use App\Services\RAG\Qdrant\QdrantService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QdrantServiceTest extends TestCase
{
    public function test_it_filters_search_by_selected_curriculum_units(): void
    {
        config()->set('rag.qdrant.url', 'https://qdrant.test');
        config()->set('rag.qdrant.collection', 'curriculum');
        config()->set('rag.qdrant.dimension', 2);

        Http::fake([
            'https://qdrant.test/*' => Http::response([
                'result' => [
                    'points' => [],
                ],
            ]),
        ]);

        app(QdrantService::class)->search(
            vector: [0.1, 0.2],
            subject: 'Tiếng Anh',
            grade: 10,
            limit: 6,
            unitIds: [101, 102, 101],
        );

        Http::assertSent(function (Request $request): bool {
            $must = data_get(
                $request->data(),
                'filter.must',
                []
            );

            return in_array([
                'key' => 'unit_id',
                'match' => [
                    'any' => [101, 102],
                ],
            ], $must, true);
        });
    }
}
