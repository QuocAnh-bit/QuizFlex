<?php

namespace App\Console\Commands;

use App\Models\CurriculumChunk;
use App\Services\RAG\Embedding\OpenRouterEmbeddingService;
use App\Services\RAG\Qdrant\QdrantService;

use Illuminate\Console\Command;

class RagEmbedCurriculum extends Command
{
    protected $signature = 'rag:embed
        {--document= : Chỉ embedding một document}
        {--limit= : Giới hạn số chunk để test}
        {--batch=10 : Số chunk mỗi batch}
        {--retry-failed : Chạy lại các chunk failed}';

    protected $description =
    'Embedding curriculum chunks và lưu vào Qdrant';


    public function handle(
        OpenRouterEmbeddingService $embeddingService,
        QdrantService $qdrant
    ): int {

        $documentId =
            $this->option('document');

        $limit =
            $this->option('limit');

        $batchSize =
            max(
                1,
                min(
                    25,
                    (int) $this->option('batch')
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Chỉ lấy chunk cần embedding
        |--------------------------------------------------------------------------
        */

        $query =
            CurriculumChunk::query()
            ->with('unit')
            ->orderBy('id');


        if ($this->option('retry-failed')) {

            $query->whereIn(
                'embedding_status',
                [
                    'pending',
                    'failed',
                ]
            );
        } else {

            $query->where(
                'embedding_status',
                'pending'
            );
        }


        if ($documentId) {

            $query->whereHas(
                'unit',
                fn($q) =>
                $q->where(
                    'document_id',
                    (int) $documentId
                )
            );
        }


        if ($limit) {

            $query->limit(
                max(
                    1,
                    (int) $limit
                )
            );
        }


        $chunks =
            $query->get();


        if ($chunks->isEmpty()) {

            $this->info(
                'Không có chunk nào cần embedding.'
            );

            return self::SUCCESS;
        }


        $this->info(
            'Chunks cần embedding: '
                . $chunks->count()
        );

        $this->line(
            'Batch size: '
                . $batchSize
        );


        $success = 0;
        $failed = 0;
        $totalCost = 0;


        /*
        |--------------------------------------------------------------------------
        | Chia batch
        |--------------------------------------------------------------------------
        */

        foreach (
            $chunks->chunk($batchSize)
            as $batchNumber => $batch
        ) {

            $this->newLine();

            $this->info(
                'Batch #'
                    . ($batchNumber + 1)
                    . ' | '
                    . $batch->count()
                    . ' chunks'
            );


            try {

                /*
                |--------------------------------------------------------------------------
                | 1. Embed cả batch
                |--------------------------------------------------------------------------
                */

                $texts =
                    $batch
                    ->pluck('embedding_text')
                    ->all();


                $embeddingResult =
                    $embeddingService
                    ->embedBatch($texts);


                $vectors =
                    $embeddingResult['vectors'];


                /*
                |--------------------------------------------------------------------------
                | 2. Build Qdrant points
                |--------------------------------------------------------------------------
                */

                $points = [];


                foreach (
                    $batch->values()
                    as $index => $chunk
                ) {

                    $unit = $chunk->unit;

                    if (!$unit) {
                        throw new \RuntimeException(
                            "Chunk {$chunk->id} không có unit."
                        );
                    }


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


                    $payload =
                        array_filter(
                            $payload,
                            fn($value) =>
                            $value !== null
                                && $value !== ''
                        );


                    $points[] = [

                        'id' =>
                        $chunk->id,

                        'vector' =>
                        $vectors[$index],

                        'payload' =>
                        $payload,
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | 3. Qdrant batch upsert
                |--------------------------------------------------------------------------
                */

                $qdrant->upsertPoints(
                    $points
                );


                /*
                |--------------------------------------------------------------------------
                | 4. Chỉ đánh dấu embedded SAU KHI Qdrant thành công
                |--------------------------------------------------------------------------
                */

                foreach ($batch as $chunk) {

                    $chunk->update([

                        'embedding_model' =>
                        $embeddingResult['model'],

                        'embedding_status' =>
                        'embedded',

                        'qdrant_point_id' =>
                        (string) $chunk->id,

                        'embedding_error' =>
                        null,
                    ]);

                    $success++;
                }


                /*
                 * Nếu OpenRouter trả cost.
                 */
                $cost =
                    data_get(
                        $embeddingResult,
                        'usage.cost'
                    );

                if (is_numeric($cost)) {
                    $totalCost +=
                        (float) $cost;
                }


                $this->info(
                    '✓ Batch thành công'
                );
            } catch (\Throwable $e) {

                $failed +=
                    $batch->count();


                /*
                 * Cho phép chạy lại sau.
                 */
                foreach ($batch as $chunk) {

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
                }


                $this->error(
                    'Batch lỗi: '
                        . $e->getMessage()
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '=============================='
        );

        $this->info(
            'EMBEDDING HOÀN TẤT'
        );

        $this->line(
            "Success: {$success}"
        );

        $this->line(
            "Failed: {$failed}"
        );

        $this->line(
            'Estimated API cost returned: $'
                . number_format(
                    $totalCost,
                    8
                )
        );


        return $failed === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
