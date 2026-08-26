<?php

namespace App\Services\RAG\Chunk;

use App\Models\CurriculumChunk;
use App\Models\CurriculumUnit;
use Illuminate\Support\Facades\DB;

class CurriculumChunkStorageService
{
    public function __construct(
        private CurriculumChunkService $chunker
    ) {}

    public function storeUnit(
        CurriculumUnit $unit
    ): array {

        $generatedChunks =
            $this->chunker->chunkUnit(
                $unit
            );

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $deleted = 0;

        DB::transaction(function () use (
            $unit,
            $generatedChunks,
            &$created,
            &$updated,
            &$unchanged,
            &$deleted
        ) {

            /*
             * Những index hiện tại mà
             * chunker vừa tạo.
             */
            $validIndexes =
                collect($generatedChunks)
                ->pluck('chunk_index')
                ->all();


            foreach ($generatedChunks as $chunkData) {

                $existing =
                    CurriculumChunk::query()
                    ->where(
                        'unit_id',
                        $unit->id
                    )
                    ->where(
                        'chunk_index',
                        $chunkData['chunk_index']
                    )
                    ->first();


                /*
                 * Chưa có -> tạo mới.
                 */
                if (!$existing) {

                    CurriculumChunk::create(
                        $chunkData
                    );

                    $created++;

                    continue;
                }


                /*
                 * Nội dung không đổi.
                 *
                 * RẤT QUAN TRỌNG:
                 * không reset embedded
                 * về pending.
                 */
                if (
                    $existing->content_hash
                    ===
                    $chunkData['content_hash']
                ) {

                    $unchanged++;

                    continue;
                }


                /*
                 * Nội dung đã thay đổi.
                 *
                 * Phải embedding lại.
                 */
                $existing->update([

                    'content' =>
                    $chunkData['content'],

                    'embedding_text' =>
                    $chunkData['embedding_text'],

                    'estimated_tokens' =>
                    $chunkData['estimated_tokens'],

                    'content_hash' =>
                    $chunkData['content_hash'],

                    'embedding_model' =>
                    null,

                    'embedding_status' =>
                    'pending',

                    'qdrant_point_id' =>
                    null,

                    'embedding_error' =>
                    null,
                ]);

                $updated++;
            }


            /*
             * Nếu trước đây unit có 3 chunk
             * nhưng thuật toán mới chỉ còn 2,
             * xóa chunk thừa.
             */
            $staleQuery =
                CurriculumChunk::query()
                ->where(
                    'unit_id',
                    $unit->id
                );

            if (!empty($validIndexes)) {

                $staleQuery->whereNotIn(
                    'chunk_index',
                    $validIndexes
                );
            }

            $staleChunks =
                $staleQuery->get();


            foreach ($staleChunks as $staleChunk) {

                /*
                 * Hiện tại mới chưa đẩy Qdrant
                 * nên có thể delete trực tiếp.
                 *
                 * Sau này khi đã có Qdrant,
                 * ta sẽ bổ sung xóa point
                 * trong Qdrant trước.
                 */
                $staleChunk->delete();

                $deleted++;
            }
        });


        return [
            'generated' =>
            count($generatedChunks),

            'created' =>
            $created,

            'updated' =>
            $updated,

            'unchanged' =>
            $unchanged,

            'deleted' =>
            $deleted,
        ];
    }
}
