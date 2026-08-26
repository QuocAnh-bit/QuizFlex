<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RAG\Parse\CurriculumBlockSplitter;

class RagSplitCurriculum extends Command
{
    protected $signature =
    'rag:split
        {file}
        {subject}
        {--grade-min=}
        {--grade-max=}';

    protected $description =
    'Split extracted curriculum JSON into AI parser blocks';


    public function handle(
        CurriculumBlockSplitter $splitter
    ): int {

        $file =
            $this->argument(
                'file'
            );

        $subject =
            $this->argument(
                'subject'
            );

        $gradeMin =
            $this->option(
                'grade-min'
            );

        $gradeMax =
            $this->option(
                'grade-max'
            );

        $gradeMin =
            $gradeMin !== null
            ? (int) $gradeMin
            : null;

        $gradeMax =
            $gradeMax !== null
            ? (int) $gradeMax
            : null;


        /*
         * Ví dụ input:
         *
         * ngu_van_extracted.json
         */
        $inputPath =
            storage_path(
                'app/rag_debug/'
                    . $file
            );

        if (!file_exists($inputPath)) {

            $this->error(
                "Không tìm thấy file:"
            );

            $this->line(
                $inputPath
            );

            return self::FAILURE;
        }


        /*
         * Đọc JSON.
         */
        $json =
            file_get_contents(
                $inputPath
            );

        $data =
            json_decode(
                $json,
                true
            );

        if (
            !is_array($data)
            || empty($data['pages'])
        ) {

            $this->error(
                'JSON không có pages.'
            );

            return self::FAILURE;
        }


        $this->info(
            'Đang split...'
        );

        $this->line(
            "Môn: {$subject}"
        );

        $this->line(
            'Pages: '
                . count($data['pages'])
        );


        /*
         * Split.
         */
        $blocks =
            $splitter->split(
                $data['pages'],
                $subject,
                $gradeMin,
                $gradeMax
            );


        /*
         * Output filename.
         */

        $base =
            str_replace(
                '_extracted.json',
                '',
                $file
            );

        $outputPath =
            storage_path(
                'app/rag_debug/'
                    . $base
                    . '_blocks.json'
            );


        file_put_contents(
            $outputPath,

            json_encode(
                [
                    'subject' =>
                    $subject,

                    'document_grade_min' =>
                    $gradeMin,

                    'document_grade_max' =>
                    $gradeMax,

                    'block_count' =>
                    count($blocks),

                    'blocks' =>
                    $blocks,
                ],

                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_PRETTY_PRINT
                    | JSON_INVALID_UTF8_SUBSTITUTE
            )
        );


        /*
         * Terminal summary.
         */

        $this->newLine();

        $this->info(
            'Split thành công.'
        );

        $this->line(
            'Số block: '
                . count($blocks)
        );

        $this->line(
            'Output: '
                . $outputPath
        );


        /*
         * Hiển thị preview.
         */
        foreach (
            array_slice(
                $blocks,
                0,
                15
            )
            as $index => $block
        ) {

            $this->line(
                sprintf(
                    '#%d | %s | grade=%s-%s | pages=%s-%s | chars=%d',
                    $index + 1,

                    $block['type'],

                    $block['grade_min']
                        ?? 'null',

                    $block['grade_max']
                        ?? 'null',

                    $block['page_start'],

                    $block['page_end'],

                    $block['char_count']
                        ?? mb_strlen(
                            $block['text'],
                            'UTF-8'
                        )
                )
            );
        }


        return self::SUCCESS;
    }
}
