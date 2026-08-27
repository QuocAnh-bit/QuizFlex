<?php

namespace App\Console\Commands;

use App\Models\CurriculumDocument;
use App\Models\CurriculumUnit;
use App\Services\RAG\Chunk\CurriculumChunkStorageService;
use Illuminate\Console\Command;

class RagChunkCurriculum extends Command
{
    protected $signature = 'rag:chunk
        {--document= : Chỉ chunk một curriculum document}
        {--unit= : Chỉ chunk một curriculum unit}
        {--limit= : Giới hạn số unit để test}';

    protected $description =
    'Tạo curriculum_chunks từ curriculum_units';


    public function handle(
        CurriculumChunkStorageService $storage
    ): int {

        $documentId =
            $this->option('document');

        $unitId =
            $this->option('unit');

        $limit =
            $this->option('limit');


        /*
        |--------------------------------------------------------------------------
        | Query units
        |--------------------------------------------------------------------------
        */

        $query =
            CurriculumUnit::query()
            ->orderBy('id');


        /*
         * Test riêng một unit.
         */
        if ($unitId) {

            $query->where(
                'id',
                (int) $unitId
            );
        }


        /*
         * Chunk một document.
         */
        if ($documentId) {

            $query->where(
                'document_id',
                (int) $documentId
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


        $totalUnits =
            (clone $query)->count();


        if ($totalUnits === 0) {

            $this->warn(
                'Không tìm thấy curriculum unit nào.'
            );

            return self::SUCCESS;
        }


        $this->info(
            "Units cần xử lý: {$totalUnits}"
        );


        /*
        |--------------------------------------------------------------------------
        | Counters
        |--------------------------------------------------------------------------
        */

        $processedUnits = 0;

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $deleted = 0;
        $failed = 0;


        /*
        |--------------------------------------------------------------------------
        | Process
        |--------------------------------------------------------------------------
        */

        $query->chunkById(
            100,

            function ($units) use (
                $storage,
                &$processedUnits,
                &$created,
                &$updated,
                &$unchanged,
                &$deleted,
                &$failed
            ) {

                foreach ($units as $unit) {

                    try {

                        $result =
                            $storage->storeUnit(
                                $unit
                            );


                        $processedUnits++;

                        $created +=
                            $result['created'];

                        $updated +=
                            $result['updated'];

                        $unchanged +=
                            $result['unchanged'];

                        $deleted +=
                            $result['deleted'];


                        $this->line(
                            "Unit {$unit->id}"
                                . " | chunks={$result['generated']}"
                                . " | created={$result['created']}"
                                . " | updated={$result['updated']}"
                                . " | unchanged={$result['unchanged']}"
                        );
                    } catch (\Throwable $e) {

                        $failed++;

                        $this->error(
                            "Unit {$unit->id}: "
                                . $e->getMessage()
                        );
                    }
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Update document status
        |--------------------------------------------------------------------------
        */

        if (
            $documentId
            &&
            $failed === 0
            &&
            !$unitId
            &&
            !$limit
        ) {

            CurriculumDocument::query()
                ->where(
                    'id',
                    (int) $documentId
                )
                ->update([
                    'status' =>
                    'chunked',

                    'error_message' =>
                    null,
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '==================================='
        );

        $this->info(
            'CHUNK HOÀN TẤT'
        );

        $this->line(
            "Units xử lý: {$processedUnits}"
        );

        $this->line(
            "Chunks tạo mới: {$created}"
        );

        $this->line(
            "Chunks cập nhật: {$updated}"
        );

        $this->line(
            "Chunks không đổi: {$unchanged}"
        );

        $this->line(
            "Chunks xóa: {$deleted}"
        );

        $this->line(
            "Units lỗi: {$failed}"
        );


        return $failed === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
