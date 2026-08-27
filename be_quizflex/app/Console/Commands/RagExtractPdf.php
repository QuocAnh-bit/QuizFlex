<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RAG\Extract\PdfExtractorService;
use App\Models\CurriculumDocument;

class RagExtractPdf extends Command
{
    protected $signature = 'rag:extract
                            {file}
                            {subject}';

    protected $description =
    'Extract curriculum PDF and save document metadata';

    public function handle(
        PdfExtractorService $extractor
    ): int {

        $file = $this->argument('file');
        $subject = $this->argument('subject');

        $filePath = storage_path(
            'app/curriculum/' . $file
        );

        $this->info("Đang đọc file:");
        $this->line($filePath);

        if (!file_exists($filePath)) {
            $this->error(
                "Không tìm thấy file: {$filePath}"
            );

            return self::FAILURE;
        }

        try {

            // =============================
            // 1. Extract PDF
            // =============================

            $result = $extractor->extract(
                $filePath
            );

            // =============================
            // 2. Tạo debug JSON
            // =============================

            $debugDirectory =
                storage_path(
                    'app/rag_debug'
                );

            if (!is_dir($debugDirectory)) {

                mkdir(
                    $debugDirectory,
                    0775,
                    true
                );
            }

            $baseName = pathinfo(
                $file,
                PATHINFO_FILENAME
            );

            $outputFile =
                $debugDirectory
                . DIRECTORY_SEPARATOR
                . $baseName
                . '_extracted.json';

            file_put_contents(
                $outputFile,

                json_encode(
                    $result,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_PRETTY_PRINT
                        | JSON_INVALID_UTF8_SUBSTITUTE
                )
            );

            // =============================
            // 3. Hash file PDF
            // =============================

            $checksum = hash_file(
                'sha256',
                $filePath
            );

            // =============================
            // 4. Lưu curriculum_documents
            // =============================

            $document =
                CurriculumDocument::updateOrCreate(

                    [
                        'checksum' =>
                        $checksum,
                    ],

                    [
                        'subject' =>
                        $subject,

                        'title' =>
                        'Chương trình GDPT môn '
                            . $subject,

                        'file_path' =>
                        $file,

                        'publisher' =>
                        'Bộ Giáo dục và Đào tạo',

                        'legal_document' =>
                        '32/2018/TT-BGDĐT',

                        'curriculum_version' =>
                        'GDPT2018',

                        'page_count' =>
                        $result['page_count'],

                        /*
                         * Chưa parse AI nên
                         * vẫn để pending.
                         */
                        'status' =>
                        'pending',

                        'error_message' =>
                        null,
                    ]
                );

            // =============================
            // 5. Output
            // =============================

            $this->newLine();

            $this->info(
                'Extract PDF thành công.'
            );

            $this->line(
                'Document ID: '
                    . $document->id
            );

            $this->line(
                'Môn: '
                    . $document->subject
            );

            $this->line(
                'Số trang: '
                    . $result['page_count']
            );

            $this->line(
                'Checksum: '
                    . $checksum
            );

            $this->line(
                'Debug JSON: '
                    . $outputFile
            );

            if (
                isset(
                    $result['analysis']['needs_ocr']
                )
            ) {

                $this->line(
                    'Needs OCR: '
                        . (
                            $result['analysis']['needs_ocr']
                            ? 'YES'
                            : 'NO'
                        )
                );
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {

            $this->error(
                'Lỗi: '
                    . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
