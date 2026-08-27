<?php

namespace App\Console\Commands;

use App\Models\CurriculumDocument;
use App\Models\CurriculumUnit;
use App\Services\RAG\Parse\CurriculumAIParserService;
use App\Services\RAG\Validation\CurriculumUnitValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RagParseCurriculum extends Command
{
    protected $signature = 'rag:parse
        {file}
        {--document=}
        {--start=0}
        {--limit=}';

    protected $description =
    'Parse curriculum blocks bằng AI và lưu curriculum_units';

    public function handle(
        CurriculumAIParserService $parser,
        CurriculumUnitValidator $validator
    ): int {

        /*
        |--------------------------------------------------------------------------
        | 1. Đọc file blocks
        |--------------------------------------------------------------------------
        */

        $file = $this->argument('file');

        $path = storage_path(
            'app/rag_debug/' . $file
        );

        if (!file_exists($path)) {

            $this->error(
                "Không tìm thấy file: {$path}"
            );

            return self::FAILURE;
        }


        $data = json_decode(
            file_get_contents($path),
            true
        );


        if (
            !is_array($data)
            ||
            !isset($data['blocks'])
            ||
            !is_array($data['blocks'])
        ) {

            $this->error(
                'File blocks không hợp lệ.'
            );

            return self::FAILURE;
        }


        $blocks = $data['blocks'];

        $subject =
            $data['subject']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | 2. Xác định curriculum_document
        |--------------------------------------------------------------------------
        */

        $documentId =
            $this->option('document');

        if ($documentId) {

            $document =
                CurriculumDocument::find(
                    (int) $documentId
                );
        } else {

            /*
             * Tạm thời tìm document gần nhất
             * cùng subject.
             *
             * Sau này sẽ liên kết document_id
             * trực tiếp vào *_blocks.json.
             */
            $document =
                CurriculumDocument::query()
                ->when(
                    $subject,
                    fn($query) =>
                    $query->where(
                        'subject',
                        $subject
                    )
                )
                ->latest('id')
                ->first();
        }


        if (!$document) {

            $this->error(
                'Không tìm thấy curriculum_document.'
            );

            $this->line(
                'Có thể chạy lại với --document=ID'
            );

            return self::FAILURE;
        }


        $this->info(
            "Document ID: {$document->id}"
        );

        $this->info(
            'Subject: '
                . (
                    $subject
                    ?? $document->subject
                )
        );

        $this->info(
            'Blocks: '
                . count($blocks)
        );


        /*
        |--------------------------------------------------------------------------
        | 3. start / limit
        |--------------------------------------------------------------------------
        */

        $start =
            max(
                0,
                (int) $this->option('start')
            );

        $limit =
            $this->option('limit');

        $limit =
            $limit !== null
            ? max(1, (int) $limit)
            : null;


        if ($limit !== null) {

            $indexes =
                range(
                    $start,
                    min(
                        count($blocks) - 1,
                        $start + $limit - 1
                    )
                );
        } else {

            $indexes =
                range(
                    $start,
                    count($blocks) - 1
                );
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Parse từng block
        |--------------------------------------------------------------------------
        */

        $successBlocks = 0;
        $failedBlocks = 0;

        $createdUnits = 0;
        $skippedUnits = 0;


        foreach ($indexes as $index) {

            if (!isset($blocks[$index])) {
                continue;
            }


            $block =
                $blocks[$index];


            /*
             * subject ở root -> copy xuống block
             * nếu block chưa có.
             */
            $block['subject'] =
                $block['subject']
                ?? $subject
                ?? $document->subject;


            $this->newLine();

            $this->info(
                '----------------------------------------'
            );

            $this->info(
                "Block {$index}"
            );

            $this->line(
                'Type: '
                    . (
                        $block['type']
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


            try {

                /*
                |--------------------------------------------------------------------------
                | AI Parser
                |--------------------------------------------------------------------------
                */

                $result =
                    $parser->parse(
                        $block
                    );


                /*
                |--------------------------------------------------------------------------
                | Validator
                |--------------------------------------------------------------------------
                */

                $validated =
                    $validator->validate(
                        $result,
                        $block
                    );


                $units =
                    $validated['units']
                    ?? [];


                $warnings =
                    $validated['warnings']
                    ?? [];


                $this->line(
                    'Units hợp lệ: '
                        . count($units)
                );


                if (!empty($warnings)) {

                    foreach (
                        $warnings
                        as $warning
                    ) {

                        $this->warn(
                            $warning
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | 5. Lưu MySQL
                |--------------------------------------------------------------------------
                */

                DB::transaction(
                    function () use (
                        $units,
                        $block,
                        $document,
                        &$createdUnits,
                        &$skippedUnits
                    ) {

                        foreach ($units as $unit) {

                            $payload = [

                                'document_id' =>
                                $document->id,

                                'type' =>
                                $unit['type'],

                                'subject' =>
                                $unit['subject'],

                                'grade_min' =>
                                $unit['grade_min'],

                                'grade_max' =>
                                $unit['grade_max'],

                                'education_level' =>
                                $this->detectEducationLevel(
                                    $unit['grade_min'],
                                    $unit['grade_max']
                                ),

                                'domain' =>
                                $unit['domain'],

                                'topic' =>
                                $unit['topic'],

                                'section' =>
                                $unit['section'],

                                'subsection' =>
                                $unit['subsection'],

                                'title' =>
                                $unit['title'],

                                'author' =>
                                $unit['author'],

                                'genre' =>
                                $unit['genre'],

                                'selection_type' =>
                                $unit['selection_type'],

                                'content' =>
                                $unit['content'],

                                'learning_outcomes' =>
                                $unit['learning_outcomes'],

                                /*
                                 * Page lấy từ block,
                                 * KHÔNG lấy từ AI.
                                 */
                                'page_start' =>
                                $block['page_start']
                                    ?? null,

                                'page_end' =>
                                $block['page_end']
                                    ?? null,

                                'parser_version' =>
                                'v1',

                                'is_verified' =>
                                false,
                            ];


                            /*
                             * Tạo fingerprint để kiểm tra trùng.
                             */
                            $exists =
                                CurriculumUnit::query()
                                ->where(
                                    'document_id',
                                    $document->id
                                )
                                ->where(
                                    'type',
                                    $payload['type']
                                )
                                ->where(
                                    'subject',
                                    $payload['subject']
                                )
                                ->where(
                                    'grade_min',
                                    $payload['grade_min']
                                )
                                ->where(
                                    'grade_max',
                                    $payload['grade_max']
                                )
                                ->where(
                                    'domain',
                                    $payload['domain']
                                )
                                ->where(
                                    'topic',
                                    $payload['topic']
                                )
                                ->where(
                                    'section',
                                    $payload['section']
                                )
                                ->where(
                                    'content',
                                    $payload['content']
                                )
                                ->exists();


                            if ($exists) {

                                $skippedUnits++;

                                continue;
                            }


                            CurriculumUnit::create(
                                $payload
                            );

                            $createdUnits++;
                        }
                    }
                );


                $successBlocks++;

                $this->info(
                    "Block {$index}: OK"
                );
            } catch (\Throwable $e) {

                $failedBlocks++;

                $this->error(
                    "Block {$index}: FAILED"
                );

                $this->error(
                    $e->getMessage()
                );

                /*
                 * Không stop cả file.
                 * Chuyển sang block tiếp theo.
                 */
                continue;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Update document status
        |--------------------------------------------------------------------------
        */

        if ($failedBlocks === 0) {

            $document->update([
                'status' =>
                'parsed',

                'error_message' =>
                null,
            ]);
        } else {

            $document->update([
                'error_message' =>
                "{$failedBlocks} block parse lỗi",
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '========================================'
        );

        $this->info(
            'PARSE HOÀN TẤT'
        );

        $this->line(
            "Blocks OK: {$successBlocks}"
        );

        $this->line(
            "Blocks lỗi: {$failedBlocks}"
        );

        $this->line(
            "Units tạo mới: {$createdUnits}"
        );

        $this->line(
            "Units bỏ qua do trùng: {$skippedUnits}"
        );

        $this->line(
            'Document ID: '
                . $document->id
        );


        return $failedBlocks === 0
            ? self::SUCCESS
            : self::FAILURE;
    }


    private function detectEducationLevel(
        ?int $gradeMin,
        ?int $gradeMax
    ): ?string {

        if (
            $gradeMin === null
            ||
            $gradeMax === null
        ) {
            return null;
        }


        if (
            $gradeMin >= 1
            &&
            $gradeMax <= 5
        ) {
            return 'Tiểu học';
        }


        if (
            $gradeMin >= 6
            &&
            $gradeMax <= 9
        ) {
            return 'THCS';
        }


        if (
            $gradeMin >= 10
            &&
            $gradeMax <= 12
        ) {
            return 'THPT';
        }


        return null;
    }
}
