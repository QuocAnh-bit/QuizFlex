<?php

namespace App\Services\RAG\Parse;

class CurriculumBlockSplitter
{
    private array $profile = [];

    private string $subject = '';

    private string $mode = 'curriculum_content';


    private function detectPrintedPageNumber(
        string $text
    ): ?int {

        $lines = preg_split(
            '/\R/u',
            $text
        );

        $lines = array_values(
            array_filter(
                array_map(
                    'trim',
                    $lines
                ),
                fn($line) => $line !== ''
            )
        );

        if (empty($lines)) {
            return null;
        }

        /*
     * Chỉ kiểm tra vài dòng đầu/cuối.
     *
     * Không tìm số bất kỳ ở giữa nội dung
     * vì dễ nhầm với bảng/số liệu.
     */
        $candidates = array_merge(
            array_slice($lines, 0, 4),
            array_slice($lines, -4)
        );

        foreach ($candidates as $line) {

            if (
                preg_match(
                    '/^\s*(\d{1,3})\s*$/u',
                    $line,
                    $match
                )
            ) {

                return (int) $match[1];
            }
        }

        return null;
    }
    private function buildPrintedPageMap(
        array $pages,
        int $afterPdfPage = 0
    ): array {

        $map = [];

        foreach ($pages as $page) {

            $pdfPage =
                (int) ($page['page'] ?? 0);

            /*
         * Không scan vùng mục lục.
         */
            if ($pdfPage <= $afterPdfPage) {
                continue;
            }

            $printedPage =
                $this->detectPrintedPageNumber(
                    $page['text'] ?? ''
                );

            if ($printedPage === null) {
                continue;
            }

            /*
         * Chỉ lấy occurrence đầu tiên.
         */
            if (!isset($map[$printedPage])) {

                $map[$printedPage] =
                    $pdfPage;
            }
        }

        return $map;
    }
    private function detectGradeToc(
        array $pages
    ): array {

        $entries = [];

        foreach ($pages as $page) {

            $text = $page['text'] ?? '';

            $lines = preg_split(
                '/\R/u',
                $text
            );

            foreach ($lines as $line) {

                $line = trim($line);

                /*
             * Chỉ cần nhận:
             *
             * LỚP 10: ....
             * LỚP 11: ....
             * LỚP 12: ....
             *
             * Không phụ thuộc dấu chấm mục lục.
             */
                if (
                    !preg_match(
                        '/^LỚP\s+(1[0-2]|[1-9])\s*:\s*(.+)$/iu',
                        $line,
                        $match
                    )
                ) {
                    continue;
                }

                $grade =
                    (int) $match[1];

                $rest =
                    trim($match[2]);

                /*
             * Lấy số trang ở cuối nếu có.
             */
                $printedPage = null;

                if (
                    preg_match(
                        '/(\d+)\s*$/u',
                        $rest,
                        $pageMatch
                    )
                ) {

                    $printedPage =
                        (int) $pageMatch[1];

                    /*
                 * Xóa:
                 * ......... 9
                 * ........ 19
                 * ... 29
                 */
                    $rest = preg_replace(
                        '/[\s\.\x{2026}\x{00B7}\-_]*\d+\s*$/u',
                        '',
                        $rest
                    );
                }

                /*
             * Xóa dấu leader còn thừa.
             */
                $title = preg_replace(
                    '/[\s\.\x{2026}\x{00B7}\-_]+$/u',
                    '',
                    $rest
                );

                $title = trim($title);

                if ($title === '') {
                    continue;
                }

                $entries[] = [

                    'grade' =>
                    $grade,

                    'title' =>
                    $title,

                    'printed_page' =>
                    $printedPage,

                    'toc_pdf_page' =>
                    (int) $page['page'],
                ];
            }
        }

        /*
     * Tránh duplicate lớp do mục lục xuất hiện lại.
     */
        $unique = [];

        foreach ($entries as $entry) {

            $grade =
                $entry['grade'];

            if (!isset($unique[$grade])) {
                $unique[$grade] = $entry;
            }
        }

        ksort($unique);

        return array_values(
            $unique
        );
    }
    private function normalizeSearchText(
        string $text
    ): string {

        $text = mb_strtoupper(
            $text,
            'UTF-8'
        );

        /*
     * Các dấu / : - . ...
     * đều chuyển về space.
     */
        $text = preg_replace(
            '/[^\p{L}\p{N}]+/u',
            ' ',
            $text
        );

        $text = preg_replace(
            '/\s+/u',
            ' ',
            $text
        );

        return trim($text);
    }
    private function resolveTocGradePages(
        array $pages,
        array $tocEntries
    ): array {

        $resolved = [];

        /*
     * Trang mục lục cuối cùng.
     */
        $lastTocPage = 0;

        foreach ($tocEntries as $entry) {

            $lastTocPage = max(
                $lastTocPage,
                $entry['toc_pdf_page']
            );
        }

        foreach ($tocEntries as $entry) {

            $targetTitle =
                $this->normalizeSearchText(
                    $entry['title']
                );

            $foundPage = null;

            foreach ($pages as $page) {

                $pageNumber =
                    (int) $page['page'];

                /*
             * Không search trong mục lục.
             */
                if ($pageNumber <= $lastTocPage) {
                    continue;
                }

                $pageText =
                    $this->normalizeSearchText(
                        $page['text'] ?? ''
                    );

                /*
             * Exact normalized match.
             */
                if (
                    $targetTitle !== ''
                    &&
                    str_contains(
                        $pageText,
                        $targetTitle
                    )
                ) {

                    $foundPage =
                        $pageNumber;

                    break;
                }

                /*
             * Fallback:
             * so khớp các từ quan trọng.
             */
                if (
                    $this->similarHeading(
                        $targetTitle,
                        $pageText
                    )
                ) {

                    $foundPage =
                        $pageNumber;

                    break;
                }
            }

            if ($foundPage === null) {
                continue;
            }

            $resolved[] = [

                'grade' =>
                $entry['grade'],

                'title' =>
                $entry['title'],

                'page_start' =>
                $foundPage,

                'printed_page' =>
                $entry['printed_page'],
            ];
        }

        return $resolved;
    }

    private function splitByTocGrade(
        array $pages
    ): array {

        /*
     * ------------------------------------
     * 1. Đọc lớp từ mục lục
     * ------------------------------------
     */

        $toc =
            $this->detectGradeToc(
                $pages
            );

        if (count($toc) < 2) {
            return [];
        }


        /*
     * Trang PDF cuối cùng chứa mục lục.
     */
        $lastTocPdfPage = 0;

        foreach ($toc as $entry) {

            $lastTocPdfPage = max(
                $lastTocPdfPage,
                (int) $entry['toc_pdf_page']
            );
        }


        /*
     * ------------------------------------
     * 2. Map printed page -> PDF page
     * ------------------------------------
     */

        $pageMap =
            $this->buildPrintedPageMap(
                $pages,
                $lastTocPdfPage
            );


        /*
     * ------------------------------------
     * 3. Resolve từng lớp
     * ------------------------------------
     */

        $ranges = [];

        /*
     * Nếu tìm được ít nhất một page,
     * ta còn tính được offset cho các page
     * bị thiếu.
     */
        $knownOffset = null;

        foreach ($toc as $entry) {

            $printedPage =
                $entry['printed_page']
                ?? null;

            if ($printedPage === null) {
                continue;
            }

            if (isset($pageMap[$printedPage])) {

                $pdfPage =
                    $pageMap[$printedPage];

                $knownOffset =
                    $pdfPage
                    - $printedPage;

                $ranges[] = [

                    'grade' =>
                    $entry['grade'],

                    'title' =>
                    $entry['title'],

                    'printed_page' =>
                    $printedPage,

                    'page_start' =>
                    $pdfPage,
                ];

                continue;
            }


            /*
         * Nếu page number đó không được
         * extract nhưng đã biết offset.
         *
         * Ví dụ:
         *
         * printed = 9
         * PDF     = 11
         *
         * offset = +2
         */
            if ($knownOffset !== null) {

                $ranges[] = [

                    'grade' =>
                    $entry['grade'],

                    'title' =>
                    $entry['title'],

                    'printed_page' =>
                    $printedPage,

                    'page_start' =>
                    $printedPage
                        + $knownOffset,
                ];
            }
        }


        /*
     * Không đủ lớp -> không dùng strategy này.
     */
        if (count($ranges) < 2) {
            return [];
        }


        /*
     * ------------------------------------
     * 4. Sort theo page start
     * ------------------------------------
     */

        usort(
            $ranges,
            fn($a, $b) =>
            $a['page_start']
                <=>
                $b['page_start']
        );


        /*
     * ------------------------------------
     * 5. Tạo grade blocks
     * ------------------------------------
     */

        $blocks = [];

        $lastPdfPage =
            max(
                array_column(
                    $pages,
                    'page'
                )
            );

        foreach (
            $ranges as $index => $range
        ) {

            $start =
                $range['page_start'];

            $end =
                isset($ranges[$index + 1])
                ? $ranges[$index + 1]['page_start'] - 1
                : $lastPdfPage;

            /*
         * Range bất thường thì bỏ.
         */
            if ($start > $end) {
                continue;
            }

            $texts = [];

            $pageNumbers = [];

            foreach ($pages as $page) {

                $pageNumber =
                    (int) $page['page'];

                if (
                    $pageNumber < $start
                    ||
                    $pageNumber > $end
                ) {
                    continue;
                }

                $pageNumbers[] =
                    $pageNumber;

                $texts[] =
                    "[[PAGE:{$pageNumber}]]\n"
                    . trim(
                        $page['text'] ?? ''
                    );
            }

            if (empty($texts)) {
                continue;
            }

            $text =
                implode(
                    "\n\n",
                    $texts
                );

            $blocks[] = [

                'type' =>
                'grade_content',

                'subject' =>
                $this->subject,

                'grade_min' =>
                (int) $range['grade'],

                'grade_max' =>
                (int) $range['grade'],

                'grade_detection' =>
                'toc_page_mapping',

                'heading' =>
                'LỚP '
                    . $range['grade']
                    . ': '
                    . $range['title'],

                'page_start' =>
                $start,

                'page_end' =>
                $end,

                'pages' =>
                $pageNumbers,

                'char_count' =>
                mb_strlen(
                    $text,
                    'UTF-8'
                ),

                'text' =>
                $text,
            ];
        }

        return $blocks;
    }

    /**
     * Entry point.
     */
    public function split(
        array $pages,
        string $subject,
        ?int $documentGradeMin = null,
        ?int $documentGradeMax = null
    ): array {

        $this->subject = $subject;

        $this->profile =
            config(
                "rag_subjects.{$subject}",
                config('rag_subjects.default')
            );

        $this->mode =
            'curriculum_content';


        // ============================
        // 1. Heading LỚP X
        // ============================

        $blocks =
            $this->splitByGrade(
                $pages
            );

        if (
            $this->hasUsefulGradeBlocks(
                $blocks
            )
        ) {

            return $this->splitOversizedBlocks(
                $blocks
            );
        }


        // ============================
        // 2. Grade từ mục lục
        // ============================

        $blocks =
            $this->splitByTocGrade(
                $pages
            );
        dump(
            'TOC BLOCKS',
            array_map(
                fn($block) => [
                    'grade' =>
                    $block['grade_min']
                        ?? null,

                    'page_start' =>
                    $block['page_start']
                        ?? null,

                    'page_end' =>
                    $block['page_end']
                        ?? null,
                ],
                $blocks
            )
        );

        if (count($blocks) >= 2) {

            return $this->splitOversizedBlocks(
                $blocks
            );
        }


        // ============================
        // 3. Heading học thuật
        // ============================

        $blocks =
            $this->splitBySubjectHeadings(
                $pages,
                $documentGradeMin,
                $documentGradeMax
            );

        if (count($blocks) >= 2) {

            return $this->splitOversizedBlocks(
                $blocks
            );
        }


        // ============================
        // 4. Fallback cuối
        // ============================

        return $this->splitByPageWindow(
            $pages,
            $documentGradeMin,
            $documentGradeMax
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Grade splitting
    |--------------------------------------------------------------------------
    */

    private function splitByGrade(
        array $pages
    ): array {

        $blocks = [];

        $currentBlock = null;

        foreach ($pages as $page) {

            $pageNumber =
                (int) ($page['page'] ?? 0);

            $text =
                trim($page['text'] ?? '');

            if ($text === '') {
                continue;
            }



            /*
             * Kiểm tra special section.
             *
             * Ví dụ Ngữ văn:
             * IX. DANH MỤC VĂN BẢN...
             */
            $this->detectSpecialSection(
                $text
            );

            /*
             * Một trang có thể chứa:
             *
             * ... phần cuối Lớp 7
             *
             * LỚP 8
             *
             * ...
             *
             * Vì vậy cần tách ngay trong page.
             */
            $segments =
                $this->splitPageByGradeHeading(
                    $text
                );

            foreach ($segments as $segment) {

                /*
                 * Segment không bắt đầu bằng
                 * grade heading.
                 */
                if ($segment['grade'] === null) {

                    if ($currentBlock !== null) {

                        $currentBlock['text']
                            .= "\n\n"
                            . $segment['text'];

                        $currentBlock['page_end']
                            = $pageNumber;

                        $this->addPage(
                            $currentBlock,
                            $pageNumber
                        );
                    }

                    continue;
                }

                /*
                 * Có grade heading mới
                 * -> đóng block trước.
                 */
                if ($currentBlock !== null) {

                    $blocks[] =
                        $this->finalizeBlock(
                            $currentBlock
                        );
                }

                $gradeMin =
                    $segment['grade']['min'];

                $gradeMax =
                    $segment['grade']['max'];

                $type =
                    $this->mode ===
                    'literary_suggestions'
                    ? 'literary_suggestions'
                    : 'grade_content';

                $currentBlock = [

                    'type' =>
                    $type,

                    'subject' =>
                    $this->subject,

                    'grade_min' =>
                    $gradeMin,

                    'grade_max' =>
                    $gradeMax,

                    'heading' =>
                    $segment['heading'],

                    'page_start' =>
                    $pageNumber,

                    'page_end' =>
                    $pageNumber,

                    'pages' => [
                        $pageNumber
                    ],

                    'text' =>
                    trim(
                        $segment['text']
                    ),
                ];
            }
        }

        if ($currentBlock !== null) {

            $blocks[] =
                $this->finalizeBlock(
                    $currentBlock
                );
        }

        return $blocks;
    }


    /**
     * Tách một page theo heading:
     *
     * LỚP 8
     *
     * LỚP 8 VÀ LỚP 9
     *
     * LỚP 1, LỚP 2 VÀ LỚP 3
     */
    private function splitPageByGradeHeading(
        string $text
    ): array {

        /*
     * Chuẩn hóa newline.
     */
        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        /*
     * Một số PDF sinh ra:
     *
     * NBSP
     * narrow NBSP
     * zero width space
     * BOM
     *
     * Nhìn bằng mắt giống space bình thường
     * nhưng regex không match.
     */
        $text = preg_replace(
            '/[\x{00A0}\x{2007}\x{202F}]/u',
            ' ',
            $text
        );

        $text = str_replace(
            [
                "\u{200B}",
                "\u{FEFF}",
            ],
            '',
            $text
        );

        /*
     * \h = horizontal whitespace.
     *
     * Match:
     *
     * LỚP 10
     *
     * LỚP 8 VÀ LỚP 9
     *
     * LỚP 10, LỚP 11 VÀ LỚP 12
     */
        $pattern =
            '/^\h*'
            . '(LỚP\h+'
            . '(?:1[0-2]|[1-9])'
            . '(?:\h*,\h*LỚP\h+(?:1[0-2]|[1-9]))?'
            . '(?:\h+VÀ\h+LỚP\h+(?:1[0-2]|[1-9]))?'
            . ')'
            . '\h*$/imu';

        preg_match_all(
            $pattern,
            $text,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        /*
     * Không tìm thấy heading lớp.
     */
        if (empty($matches[0])) {

            return [[
                'grade' => null,
                'heading' => null,
                'text' => $text,
            ]];
        }

        $segments = [];

        /*
     * Nội dung đứng trước heading lớp đầu tiên.
     */
        $firstOffset =
            $matches[0][0][1];

        if ($firstOffset > 0) {

            $before = trim(
                substr(
                    $text,
                    0,
                    $firstOffset
                )
            );

            if ($before !== '') {

                $segments[] = [
                    'grade' => null,
                    'heading' => null,
                    'text' => $before,
                ];
            }
        }

        $count =
            count($matches[0]);

        for (
            $i = 0;
            $i < $count;
            $i++
        ) {

            $heading =
                trim(
                    $matches[1][$i][0]
                );

            $start =
                $matches[0][$i][1];

            $end =
                isset(
                    $matches[0][$i + 1]
                )
                ? $matches[0][$i + 1][1]
                : strlen($text);

            $segmentText =
                trim(
                    substr(
                        $text,
                        $start,
                        $end - $start
                    )
                );

            $grade =
                $this->parseGradeHeading(
                    $heading
                );

            $segments[] = [
                'grade' => $grade,
                'heading' => $heading,
                'text' => $segmentText,
            ];
        }

        return $segments;
    }


    /**
     * "LỚP 8"
     *
     * -> 8 - 8
     *
     * "LỚP 8 VÀ LỚP 9"
     *
     * -> 8 - 9
     *
     * "LỚP 10, LỚP 11 VÀ LỚP 12"
     *
     * -> 10 - 12
     */
    private function parseGradeHeading(
        string $heading
    ): ?array {

        /*
     * Chuẩn hóa khoảng trắng PDF.
     */
        $heading = preg_replace(
            '/[\x{00A0}\x{2007}\x{202F}]/u',
            ' ',
            $heading
        );

        preg_match_all(
            '/LỚP\h+(1[0-2]|[1-9])\b/iu',
            $heading,
            $matches
        );

        if (empty($matches[1])) {
            return null;
        }

        $grades = array_map(
            'intval',
            $matches[1]
        );

        return [
            'min' => min($grades),
            'max' => max($grades),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Special section
    |--------------------------------------------------------------------------
    */

    private function detectSpecialSection(
        string $text
    ): void {

        $sections =
            $this->profile['special_sections'] ?? [];

        foreach (
            $sections
            as $heading => $mode
        ) {

            if (
                mb_stripos(
                    $text,
                    $heading
                ) !== false
            ) {

                $this->mode =
                    $mode;

                return;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Subject heading splitting
    |--------------------------------------------------------------------------
    */

    private function splitBySubjectHeadings(
        array $pages,
        ?int $documentGradeMin,
        ?int $documentGradeMax
    ): array {

        $headings =
            $this->profile['headings']
            ?? [];

        if (empty($headings)) {
            return [];
        }

        $blocks = [];

        $currentBlock = null;

        foreach ($pages as $page) {

            $pageNumber =
                (int) ($page['page'] ?? 0);

            $text =
                trim(
                    $page['text'] ?? ''
                );

            if ($text === '') {
                continue;
            }

            $heading =
                $this->findHeading(
                    $text,
                    $headings
                );

            /*
             * Không có heading.
             */
            if ($heading === null) {

                if ($currentBlock !== null) {

                    $currentBlock['text']
                        .= "\n\n"
                        . $text;

                    $currentBlock['page_end']
                        = $pageNumber;

                    $this->addPage(
                        $currentBlock,
                        $pageNumber
                    );
                }

                continue;
            }

            /*
             * Đóng block trước.
             */
            if ($currentBlock !== null) {

                $blocks[] =
                    $this->finalizeBlock(
                        $currentBlock
                    );
            }

            $currentBlock = [

                'type' =>
                'heading_content',

                'subject' =>
                $this->subject,

                /*
                 * Lưu ý:
                 *
                 * đây là range của DOCUMENT,
                 * không khẳng định unit thuộc
                 * từng lớp cụ thể.
                 */
                'document_grade_min' =>
                $documentGradeMin,

                'document_grade_max' =>
                $documentGradeMax,

                'grade_min' =>
                null,

                'grade_max' =>
                null,

                'heading' =>
                $heading,

                'page_start' =>
                $pageNumber,

                'page_end' =>
                $pageNumber,

                'pages' => [
                    $pageNumber
                ],

                'text' =>
                $text,
            ];
        }

        if ($currentBlock !== null) {

            $blocks[] =
                $this->finalizeBlock(
                    $currentBlock
                );
        }

        return $blocks;
    }


    private function findHeading(
        string $text,
        array $headings
    ): ?string {

        $lines =
            preg_split(
                '/\R/u',
                $text
            );

        foreach ($lines as $line) {

            $normalized =
                $this->normalizeHeading(
                    $line
                );

            foreach ($headings as $heading) {

                if (
                    $normalized ===
                    $this->normalizeHeading(
                        $heading
                    )
                ) {

                    return trim($line);
                }
            }
        }

        return null;
    }


    private function normalizeHeading(
        string $text
    ): string {

        $text = trim($text);

        $text = preg_replace(
            '/\s+/u',
            ' ',
            $text
        );

        return mb_strtoupper(
            $text,
            'UTF-8'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Page-window fallback
    |--------------------------------------------------------------------------
    */

    private function splitByPageWindow(
        array $pages,
        ?int $documentGradeMin,
        ?int $documentGradeMax
    ): array {

        $pagesPerBlock =
            (int) (
                $this->profile['fallback_pages'] ?? 3
            );

        $groups =
            array_chunk(
                $pages,
                $pagesPerBlock
            );

        $blocks = [];

        foreach (
            $groups
            as $index => $group
        ) {

            if (empty($group)) {
                continue;
            }

            $texts = [];

            $pageNumbers = [];

            foreach ($group as $page) {

                $number =
                    (int) $page['page'];

                $pageNumbers[] =
                    $number;

                $texts[] =
                    "[[PAGE:{$number}]]\n"
                    . trim(
                        $page['text'] ?? ''
                    );
            }

            $blocks[] = [

                'type' =>
                'generic_content',

                'subject' =>
                $this->subject,

                'document_grade_min' =>
                $documentGradeMin,

                'document_grade_max' =>
                $documentGradeMax,

                'grade_min' =>
                null,

                'grade_max' =>
                null,

                'heading' =>
                null,

                'page_start' =>
                min($pageNumbers),

                'page_end' =>
                max($pageNumbers),

                'pages' =>
                $pageNumbers,

                'text' =>
                implode(
                    "\n\n",
                    $texts
                ),
            ];
        }

        return $this->splitOversizedBlocks(
            $blocks
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Oversized blocks
    |--------------------------------------------------------------------------
    */

    private function splitOversizedBlocks(
        array $blocks
    ): array {

        $maxChars =
            (int) (
                $this->profile['max_block_chars'] ?? 12000
            );

        $result = [];

        foreach ($blocks as $block) {

            $length =
                mb_strlen(
                    $block['text'],
                    'UTF-8'
                );

            if ($length <= $maxChars) {

                $result[] =
                    $block;

                continue;
            }

            /*
             * Tách theo paragraph.
             */
            $parts =
                preg_split(
                    '/\n\s*\n/u',
                    $block['text']
                );

            $buffer = '';

            $partIndex = 0;

            foreach ($parts as $part) {

                $part =
                    trim($part);

                if ($part === '') {
                    continue;
                }

                $candidate =
                    $buffer === ''
                    ? $part
                    : $buffer
                    . "\n\n"
                    . $part;

                if (
                    mb_strlen(
                        $candidate,
                        'UTF-8'
                    ) > $maxChars
                    && $buffer !== ''
                ) {

                    $newBlock = $block;

                    $newBlock['text'] =
                        trim($buffer);

                    $newBlock['sub_block_index'] =
                        $partIndex++;

                    /*
 * Quan trọng:
 * tính lại char_count cho block con.
 */
                    $newBlock['char_count'] =
                        mb_strlen(
                            $newBlock['text'],
                            'UTF-8'
                        );

                    $result[] =
                        $newBlock;
                    $buffer =
                        $part;
                } else {

                    $buffer =
                        $candidate;
                }
            }

            if ($buffer !== '') {

                $newBlock = $block;

                $newBlock['text'] =
                    trim($buffer);

                $newBlock['sub_block_index'] =
                    $partIndex;

                $newBlock['char_count'] =
                    mb_strlen(
                        $newBlock['text'],
                        'UTF-8'
                    );

                $result[] =
                    $newBlock;
            }
        }

        return array_values(
            $result
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function similarHeading(
        string $target,
        string $pageText
    ): bool {

        $words = preg_split(
            '/\s+/u',
            $target
        );

        $words = array_values(
            array_filter(
                $words,
                fn($word) =>
                mb_strlen(
                    $word,
                    'UTF-8'
                ) >= 3
            )
        );

        if (count($words) < 2) {
            return false;
        }

        $matched = 0;

        foreach ($words as $word) {

            if (
                str_contains(
                    $pageText,
                    $word
                )
            ) {
                $matched++;
            }
        }

        $ratio =
            $matched / count($words);

        return $ratio >= 0.7;
    }
    private function hasUsefulGradeBlocks(
        array $blocks
    ): bool {

        /*
         * Tránh trường hợp vô tình match
         * đúng một heading nào đó.
         */

        $gradeBlocks = array_filter(
            $blocks,
            fn($block) =>
            $block['grade_min']
                !== null
        );

        return count($gradeBlocks) >= 2;
    }


    private function addPage(
        array &$block,
        int $page
    ): void {

        if (
            !in_array(
                $page,
                $block['pages'],
                true
            )
        ) {

            $block['pages'][] =
                $page;
        }
    }


    private function finalizeBlock(
        array $block
    ): array {

        $block['text'] =
            trim(
                $block['text']
            );

        $block['char_count'] =
            mb_strlen(
                $block['text'],
                'UTF-8'
            );

        $block['pages'] =
            array_values(
                array_unique(
                    $block['pages']
                )
            );

        return $block;
    }
}
