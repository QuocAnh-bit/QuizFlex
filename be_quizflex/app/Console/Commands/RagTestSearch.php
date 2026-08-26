<?php

namespace App\Console\Commands;

use App\Models\CurriculumChunk;
use App\Services\RAG\Embedding\OpenRouterEmbeddingService;
use App\Services\RAG\Qdrant\QdrantService;
use Illuminate\Console\Command;

class RagTestSearch extends Command
{
    protected $signature = 'rag:test-search
        {query}
        {--subject=Tiếng Anh}
        {--grade=3}
        {--limit=5}';

    protected $description =
    'Test semantic retrieval từ Qdrant';


    public function handle(
        OpenRouterEmbeddingService $embeddingService,
        QdrantService $qdrant
    ): int {

        $queryText =
            trim(
                $this->argument('query')
            );

        $subject =
            trim(
                $this->option('subject')
            );

        $grade =
            (int) $this->option('grade');

        $limit =
            max(
                1,
                (int) $this->option('limit')
            );


        if ($queryText === '') {

            $this->error(
                'Query không được rỗng.'
            );

            return self::FAILURE;
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Embed câu tìm kiếm
            |--------------------------------------------------------------------------
            */

            $this->info(
                'Đang embedding query...'
            );

            $embedding =
                $embeddingService->embed(
                    $queryText
                );

            $this->line(
                'Dimension: '
                    . $embedding['dimension']
            );


            /*
            |--------------------------------------------------------------------------
            | 2. Search Qdrant
            |--------------------------------------------------------------------------
            */

            $this->info(
                'Đang tìm trong Qdrant...'
            );

            $points =
                $qdrant->search(
                    vector: $embedding['vector'],
                    subject: $subject,
                    grade: $grade,
                    limit: $limit
                );


            if (empty($points)) {

                $this->warn(
                    'Không tìm thấy kết quả.'
                );

                return self::SUCCESS;
            }


            /*
            |--------------------------------------------------------------------------
            | 3. Hiển thị kết quả + lấy content từ MySQL
            |--------------------------------------------------------------------------
            */

            foreach (
                $points as $index => $point
            ) {

                $pointId =
                    $point['id']
                    ?? null;

                $score =
                    $point['score']
                    ?? 0;

                $payload =
                    $point['payload']
                    ?? [];


                $chunkId =
                    $payload['chunk_id']
                    ?? $pointId;


                $chunk =
                    CurriculumChunk::query()
                    ->with('unit')
                    ->find($chunkId);


                $this->newLine();

                $this->info(
                    '#'
                        . ($index + 1)
                        . ' | Chunk '
                        . $chunkId
                );

                $this->line(
                    'Score: '
                        . number_format(
                            (float) $score,
                            6
                        )
                );

                $this->line(
                    'Subject: '
                        . (
                            $payload['subject']
                            ?? 'null'
                        )
                );

                $this->line(
                    'Grade: '
                        . (
                            $payload['grade_min']
                            ?? '?'
                        )
                        . '-'
                        . (
                            $payload['grade_max']
                            ?? '?'
                        )
                );

                $this->line(
                    'Domain: '
                        . (
                            $payload['domain']
                            ?? 'null'
                        )
                );

                $this->line(
                    'Topic: '
                        . (
                            $payload['topic']
                            ?? 'null'
                        )
                );

                $this->line(
                    'Section: '
                        . (
                            $payload['section']
                            ?? 'null'
                        )
                );


                if ($chunk) {

                    $this->newLine();

                    $this->line(
                        'CONTENT:'
                    );

                    $this->line(
                        $chunk->content
                    );
                }
            }


            return self::SUCCESS;
        } catch (\Throwable $e) {

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
