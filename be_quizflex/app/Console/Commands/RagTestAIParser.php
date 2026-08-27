<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RAG\Parse\CurriculumAIParserService;

use App\Services\RAG\Validation\CurriculumUnitValidator;

class RagTestAIParser extends Command
{
    protected $signature =
    'rag:test-parser
        {file}
        {--block=0}';

    protected $description =
    'Test curriculum AI parser';


    public function handle(
        CurriculumAIParserService $parser,
        CurriculumUnitValidator $validator
    ): int {

        $file =
            $this->argument(
                'file'
            );

        $index =
            (int) $this->option(
                'block'
            );

        $path =
            storage_path(
                'app/rag_debug/'
                    . $file
            );

        if (!file_exists($path)) {

            $this->error(
                "Không tìm thấy {$path}"
            );

            return self::FAILURE;
        }


        $data =
            json_decode(
                file_get_contents($path),
                true
            );


        if (
            !isset(
                $data['blocks'][$index]
            )
        ) {

            $this->error(
                "Không có block {$index}"
            );

            return self::FAILURE;
        }


        $block =
            $data['blocks'][$index];


        $this->info(
            'Đang parse block '
                . $index
        );

        $this->line(
            'Subject: '
                . (
                    $block['subject']
                    ?? $data['subject']
                    ?? 'null'
                )
        );

        $this->line(
            'Grade: '
                . (
                    $block['grade_min']
                    ?? 'null'
                )
                . '-'
                . (
                    $block['grade_max']
                    ?? 'null'
                )
        );


        /*
         * Nếu block không có subject,
         * lấy từ root JSON.
         */
        $block['subject'] =
            $block['subject']
            ?? $data['subject']
            ?? null;


        try {

            $result =
                $parser->parse(
                    $block
                );

            $validated =
                $validator->validate(
                    $result,
                    $block
                );
        } catch (\Throwable $e) {

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }


        $outputPath =
            storage_path(
                'app/rag_debug/parser_test_validated.json'
            );

        file_put_contents(
            $outputPath,

            json_encode(
                $validated,
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_PRETTY_PRINT
            )
        );

        $this->info(
            'Valid units: '
                . count(
                    $validated['units']
                )
        );

        $this->info(
            'Warnings: '
                . count(
                    $validated['warnings']
                )
        );

        foreach (
            $validated['warnings']
            as $warning
        ) {
            $this->warn(
                $warning
            );
        }


        $this->info(
            'Parse thành công.'
        );

        $this->line(
            'Units: '
                . count(
                    $result['units']
                )
        );

        $this->line(
            'Output: '
                . $outputPath
        );


        return self::SUCCESS;
    }
}
