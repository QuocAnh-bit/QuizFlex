<?php

namespace App\Console\Commands;

use App\Models\CurriculumChunk;
use App\Services\RAG\Embedding\OpenRouterEmbeddingService;
use App\Services\RAG\Qdrant\QdrantService;
use Illuminate\Console\Command;

class RagEmbedChunk extends Command
{
    protected $signature =
    'rag:embed-chunk {chunk_id}';

    protected $description =
    'Embedding một curriculum chunk và lưu vector vào Qdrant';


    public function handle(
        OpenRouterEmbeddingService $embeddingService,
        QdrantService $qdrant
    ): int {

        $chunkId =
            (int) $this->argument(
                'chunk_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Lấy chunk + unit
        |--------------------------------------------------------------------------
        */

        $chunk =
            CurriculumChunk::query()
            ->with('unit')
            ->find($chunkId);


        if (!$chunk) {

            $this->error(
                "Không tìm thấy chunk ID {$chunkId}"
            );

            return self::FAILURE;
        }


        if (!$chunk->unit) {

            $this->error(
                'Chunk không có curriculum unit.'
            );

            return self::FAILURE;
        }


        if (!$chunk->embedding_text) {

            $this->error(
                'Chunk không có embedding_text.'
            );

            return self::FAILURE;
        }


        $unit = $chunk->unit;


        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Embedding
            |--------------------------------------------------------------------------
            */

            $this->info(
                "Embedding chunk {$chunk->id}..."
            );


            $embedding =
                $embeddingService->embed(
                    $chunk->embedding_text
                );


            $this->line(
                'Dimension: '
                    . $embedding['dimension']
            );


            /*
            |--------------------------------------------------------------------------
            | 2. Payload Qdrant
            |--------------------------------------------------------------------------
            */

            $payload = [

                'chunk_id' =>
                $chunk->id,

                'unit_id' =>
                $unit->id,

                'document_id' =>
                $unit->document_id,

                'subject' =>
                $unit->subject,

                'grade_min' =>
                $unit->grade_min,

                'grade_max' =>
                $unit->grade_max,

                'type' =>
                $unit->type,

                'domain' =>
                $unit->domain,

                'topic' =>
                $unit->topic,

                'section' =>
                $unit->section,

                'subsection' =>
                $unit->subsection,

                'title' =>
                $unit->title,
            ];


            /*
             * Bỏ những metadata null.
             */
            $payload =
                array_filter(
                    $payload,
                    fn($value) =>
                    $value !== null
                        && $value !== ''
                );


            /*
            |--------------------------------------------------------------------------
            | 3. Upsert Qdrant
            |--------------------------------------------------------------------------
            */

            $this->info(
                'Đang upsert Qdrant...'
            );


            $qdrant->upsertPoint(

                /*
                 * Point ID = chunk ID.
                 */
                $chunk->id,

                $embedding['vector'],

                $payload
            );


            /*
            |--------------------------------------------------------------------------
            | 4. Update MySQL
            |--------------------------------------------------------------------------
            */

            $chunk->update([

                'embedding_model' =>
                $embedding['model'],

                'embedding_status' =>
                'embedded',

                'qdrant_point_id' =>
                (string) $chunk->id,

                'embedding_error' =>
                null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Done
            |--------------------------------------------------------------------------
            */

            $this->newLine();

            $this->info(
                'EMBED + QDRANT OK'
            );

            $this->line(
                'Chunk ID: '
                    . $chunk->id
            );

            $this->line(
                'Point ID: '
                    . $chunk->id
            );

            $this->line(
                'Model: '
                    . $embedding['model']
            );

            $this->line(
                'Dimension: '
                    . $embedding['dimension']
            );

            $this->line(
                'Subject: '
                    . ($unit->subject ?? 'null')
            );

            $this->line(
                'Grade: '
                    . ($unit->grade_min ?? 'null')
                    . '-'
                    . ($unit->grade_max ?? 'null')
            );

            $this->line(
                'Topic: '
                    . ($unit->topic ?? 'null')
            );


            return self::SUCCESS;
        } catch (\Throwable $e) {

            /*
             * Ghi nhận lỗi để sau này retry.
             */
            $chunk->update([

                'embedding_status' =>
                'failed',

                'embedding_error' =>
                mb_substr(
                    $e->getMessage(),
                    0,
                    2000
                ),
            ]);


            $this->error(
                $e->getMessage()
            );


            return self::FAILURE;
        }
    }
}
