<?php

namespace App\Console\Commands;

use App\Models\CurriculumChunk;
use App\Services\RAG\Embedding\OpenRouterEmbeddingService;
use Illuminate\Console\Command;

class RagTestEmbedding extends Command
{
    protected $signature =
    'rag:test-embedding {chunk_id}';

    protected $description =
    'Test embedding một curriculum chunk';


    public function handle(
        OpenRouterEmbeddingService $embeddingService
    ): int {

        $chunkId =
            (int) $this->argument(
                'chunk_id'
            );


        $chunk =
            CurriculumChunk::find(
                $chunkId
            );


        if (!$chunk) {

            $this->error(
                "Không tìm thấy chunk ID {$chunkId}"
            );

            return self::FAILURE;
        }


        if (!$chunk->embedding_text) {

            $this->error(
                'Chunk không có embedding_text.'
            );

            return self::FAILURE;
        }


        $this->info(
            "Embedding chunk {$chunk->id}..."
        );

        $this->line(
            'Estimated tokens: '
                . $chunk->estimated_tokens
        );


        try {

            $result =
                $embeddingService->embed(
                    $chunk->embedding_text
                );
        } catch (\Throwable $e) {

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }


        $this->newLine();

        $this->info(
            'EMBEDDING OK'
        );

        $this->line(
            'Model: '
                . $result['model']
        );

        $this->line(
            'Dimension: '
                . $result['dimension']
        );

        $this->line(
            'Vector values: '
                . count(
                    $result['vector']
                )
        );


        /*
         * Chỉ in 10 số đầu.
         *
         * Không cần dump cả vector dài.
         */
        $this->line(
            'Preview: '
                . json_encode(
                    array_slice(
                        $result['vector'],
                        0,
                        10
                    )
                )
        );


        if (!empty($result['usage'])) {

            $this->line(
                'Usage: '
                    . json_encode(
                        $result['usage']
                    )
            );
        }


        /*
         * CHƯA lưu vector MySQL.
         *
         * MySQL của mình chỉ lưu metadata.
         * Vector sẽ vào Qdrant.
         */


        return self::SUCCESS;
    }
}
