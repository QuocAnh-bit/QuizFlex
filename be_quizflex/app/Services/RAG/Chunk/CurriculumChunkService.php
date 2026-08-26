<?php

namespace App\Services\RAG\Chunk;

use App\Models\CurriculumUnit;

class CurriculumChunkService
{
    /*
     * Mục tiêu khoảng 500 token.
     *
     * Đây mới chỉ là estimate,
     * chưa cần tokenizer chính xác.
     */
    private int $targetTokens = 500;

    private int $maxTokens = 800;


    /**
     * Tạo chunks từ một CurriculumUnit.
     */
    public function chunkUnit(
        CurriculumUnit $unit
    ): array {

        /*
         * Tạo các đoạn semantic nhỏ trước.
         */
        $segments =
            $this->buildSegments(
                $unit
            );

        if (empty($segments)) {
            return [];
        }


        /*
         * Gom segment thành chunk.
         */
        $chunks =
            $this->packSegments(
                $segments
            );


        /*
         * Chuẩn hóa output cuối.
         */
        $result = [];

        foreach ($chunks as $index => $content) {

            $content = trim($content);

            if ($content === '') {
                continue;
            }

            $embeddingText =
                $this->buildEmbeddingText(
                    $unit,
                    $content
                );

            $result[] = [

                'unit_id' =>
                $unit->id,

                'chunk_index' =>
                $index,

                /*
                 * Nội dung gốc để retrieve.
                 */
                'content' =>
                $content,

                /*
                 * Text thực sự gửi cho
                 * embedding model.
                 */
                'embedding_text' =>
                $embeddingText,

                'estimated_tokens' =>
                $this->estimateTokens(
                    $embeddingText
                ),

                'content_hash' =>
                hash(
                    'sha256',
                    $embeddingText
                ),

                /*
                 * Chưa embedding.
                 */
                'embedding_model' =>
                null,

                'embedding_status' =>
                'pending',

                'qdrant_point_id' =>
                null,

                'embedding_error' =>
                null,
            ];
        }

        return $result;
    }


    /**
     * Biến unit thành các segment semantic.
     *
     * Không cắt mù theo ký tự.
     */
    private function buildSegments(
        CurriculumUnit $unit
    ): array {

        $segments = [];

        /*
         * --------------------------------
         * 1. Tác phẩm văn học
         * --------------------------------
         */
        if ($unit->type === 'literary_work') {

            $parts = array_filter([

                $unit->title
                    ? 'Tác phẩm: ' . $unit->title
                    : null,

                $unit->author
                    ? 'Tác giả: ' . $unit->author
                    : null,

                $unit->genre
                    ? 'Thể loại: ' . $unit->genre
                    : null,

                $unit->content,

            ]);

            if (!empty($parts)) {

                $segments[] =
                    implode(
                        "\n",
                        $parts
                    );
            }

            return $segments;
        }


        /*
         * --------------------------------
         * 2. Content
         * --------------------------------
         */

        if ($unit->content) {

            /*
             * Tách theo paragraph trước.
             */
            $paragraphs =
                preg_split(
                    '/\R{2,}/u',
                    trim(
                        $unit->content
                    )
                );

            foreach ($paragraphs as $paragraph) {

                $paragraph =
                    trim($paragraph);

                if ($paragraph === '') {
                    continue;
                }

                $segments[] =
                    $paragraph;
            }
        }


        /*
         * --------------------------------
         * 3. Yêu cầu cần đạt
         * --------------------------------
         */

        $outcomes =
            $unit->learning_outcomes
            ?? [];

        if (is_array($outcomes)) {

            foreach ($outcomes as $outcome) {

                $outcome =
                    trim(
                        (string) $outcome
                    );

                if ($outcome === '') {
                    continue;
                }

                $segments[] =
                    'Yêu cầu cần đạt: '
                    . $outcome;
            }
        }


        /*
         * --------------------------------
         * 4. Unit chỉ có topic/title
         * --------------------------------
         */

        if (empty($segments)) {

            $fallback =
                $unit->topic
                ?? $unit->title
                ?? $unit->section
                ?? $unit->subsection
                ?? $unit->domain;

            if ($fallback) {

                $segments[] =
                    trim(
                        $fallback
                    );
            }
        }


        /*
         * --------------------------------
         * 5. Loại semantic duplicate
         * --------------------------------
         */

        $unique = [];

        foreach ($segments as $segment) {

            $key =
                mb_strtolower(
                    preg_replace(
                        '/\s+/u',
                        ' ',
                        trim($segment)
                    ),
                    'UTF-8'
                );

            if (isset($unique[$key])) {
                continue;
            }

            $unique[$key] =
                trim($segment);
        }

        return array_values(
            $unique
        );
    }


    /**
     * Gom segment thành chunk khoảng
     * targetTokens.
     */
    private function packSegments(
        array $segments
    ): array {

        $chunks = [];

        $current = '';

        foreach ($segments as $segment) {

            /*
             * Nếu chính segment đã quá lớn
             * thì split tiếp.
             */
            if (
                $this->estimateTokens(
                    $segment
                )
                >
                $this->maxTokens
            ) {

                /*
                 * Flush chunk hiện tại.
                 */
                if ($current !== '') {

                    $chunks[] =
                        trim($current);

                    $current = '';
                }


                $largeParts =
                    $this->splitLargeText(
                        $segment
                    );

                foreach ($largeParts as $part) {

                    $chunks[] =
                        trim($part);
                }

                continue;
            }


            $candidate =
                $current === ''
                ? $segment
                : $current
                . "\n\n"
                . $segment;


            /*
             * Nếu thêm segment này khiến
             * chunk vượt target thì đóng chunk.
             */
            if (
                $current !== ''
                &&
                $this->estimateTokens(
                    $candidate
                )
                >
                $this->targetTokens
            ) {

                $chunks[] =
                    trim($current);

                $current =
                    $segment;

                continue;
            }


            $current =
                $candidate;
        }


        if ($current !== '') {

            $chunks[] =
                trim($current);
        }


        return $chunks;
    }


    /**
     * Fallback khi một đoạn semantic
     * tự nó quá lớn.
     *
     * Ưu tiên câu trước, không cắt ký tự ngay.
     */
    private function splitLargeText(
        string $text
    ): array {

        $sentences =
            preg_split(
                '/(?<=[.!?;])\s+/u',
                trim($text)
            );

        if (
            !is_array($sentences)
            ||
            count($sentences) <= 1
        ) {

            return $this->splitByChars(
                $text
            );
        }


        $parts = [];

        $current = '';

        foreach ($sentences as $sentence) {

            $sentence =
                trim($sentence);

            if ($sentence === '') {
                continue;
            }


            $candidate =
                $current === ''
                ? $sentence
                : $current
                . ' '
                . $sentence;


            if (
                $current !== ''
                &&
                $this->estimateTokens(
                    $candidate
                )
                >
                $this->maxTokens
            ) {

                $parts[] =
                    trim($current);

                $current =
                    $sentence;

                continue;
            }


            $current =
                $candidate;
        }


        if ($current !== '') {
            $parts[] = trim($current);
        }


        return $parts;
    }


    /**
     * Fallback cuối cùng.
     */
    private function splitByChars(
        string $text
    ): array {

        /*
         * Khoảng 3 ký tự/token
         * chỉ dùng để estimate.
         */
        $maxChars =
            $this->maxTokens * 3;

        $parts = [];

        $length =
            mb_strlen(
                $text,
                'UTF-8'
            );

        for (
            $offset = 0;
            $offset < $length;
            $offset += $maxChars
        ) {

            $parts[] =
                trim(
                    mb_substr(
                        $text,
                        $offset,
                        $maxChars,
                        'UTF-8'
                    )
                );
        }

        return array_values(
            array_filter($parts)
        );
    }


    /**
     * Text dùng để embedding.
     */
    private function buildEmbeddingText(
        CurriculumUnit $unit,
        string $content
    ): string {

        $parts = [];


        if ($unit->subject) {

            $parts[] =
                'Môn: '
                . $unit->subject;
        }


        /*
         * Grade.
         */
        if (
            $unit->grade_min !== null
            &&
            $unit->grade_max !== null
        ) {

            if (
                $unit->grade_min
                ===
                $unit->grade_max
            ) {

                $parts[] =
                    'Lớp: '
                    . $unit->grade_min;
            } else {

                $parts[] =
                    'Lớp: '
                    . $unit->grade_min
                    . '-'
                    . $unit->grade_max;
            }
        }


        if ($unit->domain) {

            $parts[] =
                'Lĩnh vực: '
                . $unit->domain;
        }


        if ($unit->topic) {

            $parts[] =
                'Chủ đề: '
                . $unit->topic;
        }


        if ($unit->section) {

            $parts[] =
                'Phần: '
                . $unit->section;
        }


        if ($unit->subsection) {

            $parts[] =
                'Mục: '
                . $unit->subsection;
        }


        if ($unit->title) {

            $parts[] =
                'Tiêu đề: '
                . $unit->title;
        }


        $parts[] =
            $content;


        /*
         * Loại dòng trùng.
         */
        $parts =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'trim',
                            $parts
                        )
                    )
                )
            );


        return implode(
            "\n",
            $parts
        );
    }


    /**
     * Estimate token.
     *
     * Không dùng để billing.
     * Chỉ dùng để chunk.
     */
    private function estimateTokens(
        string $text
    ): int {

        $length =
            mb_strlen(
                $text,
                'UTF-8'
            );

        return max(
            1,
            (int) ceil(
                $length / 3
            )
        );
    }
}
