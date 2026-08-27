<?php

namespace App\Console\Commands;

use App\Models\CurriculumUnit;
use App\Services\RAG\Chunk\CurriculumChunkService;
use Illuminate\Console\Command;

class RagTestChunk extends Command
{
    protected $signature =
    'rag:test-chunk {unit_id}';

    protected $description =
    'Test semantic chunk cho một curriculum unit';


    public function handle(
        CurriculumChunkService $chunker
    ): int {

        $unitId =
            (int) $this->argument(
                'unit_id'
            );


        $unit =
            CurriculumUnit::find(
                $unitId
            );


        if (!$unit) {

            $this->error(
                "Không tìm thấy unit ID {$unitId}"
            );

            return self::FAILURE;
        }


        $chunks =
            $chunker->chunkUnit(
                $unit
            );


        $this->info(
            'Subject: '
                . $unit->subject
        );

        $this->line(
            'Grade: '
                . (
                    $unit->grade_min
                    ?? 'null'
                )
        );

        $this->line(
            'Domain: '
                . (
                    $unit->domain
                    ?? 'null'
                )
        );

        $this->line(
            'Section: '
                . (
                    $unit->section
                    ?? 'null'
                )
        );

        $this->newLine();

        $this->info(
            'Chunks: '
                . count($chunks)
        );


        foreach ($chunks as $chunk) {

            $this->newLine();

            $this->warn(
                'CHUNK #'
                    . $chunk['chunk_index']
            );

            $this->line(
                'Estimated tokens: '
                    . $chunk['estimated_tokens']
            );

            $this->line(
                'Hash: '
                    . $chunk['content_hash']
            );

            $this->newLine();

            $this->line(
                $chunk['embedding_text']
            );
        }


        return self::SUCCESS;
    }
}
