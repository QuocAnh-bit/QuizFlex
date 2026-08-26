<?php

namespace App\Services\RAG\Extract;

use RuntimeException;
use Smalot\PdfParser\Parser;

class PdfExtractorService
{
    public function __construct(
        private Parser $parser
    ) {}

    /**
     * Extract text theo từng trang.
     */
    public function extract(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException(
                "Không tìm thấy PDF: {$filePath}"
            );
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException(
                "Không thể đọc PDF: {$filePath}"
            );
        }

        try {
            $pdf = $this->parser->parseFile(
                $filePath
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Không parse được PDF: '
                    . $e->getMessage(),
                previous: $e
            );
        }

        $pages = [];

        foreach (
            $pdf->getPages()
            as $index => $page
        ) {
            $rawText = $page->getText();

            $pages[] = [
                'page' => $index + 1,

                'raw_text' =>
                $rawText,

                'text' =>
                $this->cleanBasic(
                    $rawText
                ),

                'char_count' =>
                mb_strlen(
                    $rawText,
                    'UTF-8'
                ),
            ];
        }

        $analysis =
            $this->analyzePages(
                $pages
            );

        return [
            'page_count' =>
            count($pages),

            'analysis' =>
            $analysis,

            'pages' =>
            $pages,
        ];
    }

    /**
     * Chỉ clean các lỗi cơ bản.
     * Chưa thay đổi nội dung học thuật.
     */
    private function cleanBasic(
        string $text
    ): string {

        // Windows/Mac newline -> Unix
        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        // Tab -> space
        $text = str_replace(
            "\t",
            ' ',
            $text
        );

        // Nhiều space liên tục
        $text = preg_replace(
            '/[ ]{2,}/u',
            ' ',
            $text
        );

        // Quá nhiều dòng trống
        $text = preg_replace(
            '/\n{3,}/u',
            "\n\n",
            $text
        );

        return trim($text);
    }

    private function analyzePages(
        array $pages
    ): array {

        $emptyPages = 0;

        $totalChars = 0;

        foreach ($pages as $page) {

            $length = mb_strlen(
                trim(
                    $page['text'] ?? ''
                ),
                'UTF-8'
            );

            $totalChars += $length;

            if ($length < 20) {
                $emptyPages++;
            }
        }

        $pageCount =
            count($pages);

        $emptyRatio =
            $pageCount > 0
            ? $emptyPages / $pageCount
            : 1;

        return [
            'empty_pages' =>
            $emptyPages,

            'empty_page_ratio' =>
            $emptyRatio,

            'total_chars' =>
            $totalChars,

            /*
         * Đây chỉ là heuristic.
         */
            'needs_ocr' =>
            $emptyRatio > 0.4
                || $totalChars < 500,
        ];
    }
}
