<?php

namespace App\Services\RAG\Validation;

class CurriculumUnitValidator
{
    private const ALLOWED_TYPES = [
        'curriculum_content',
        'literary_work',
        'curriculum_rule',
    ];

    private const ALLOWED_SELECTION_TYPES = [
        'mandatory',
        'mandatory_selection',
        'suggested',
        null,
    ];

    public function validate(
        array $result,
        array $block
    ): array {

        $units = $result['units'] ?? [];

        if (!is_array($units)) {
            return [
                'units' => [],
                'warnings' => [
                    'AI result không có units hợp lệ.'
                ],
            ];
        }

        $validated = [];
        $warnings = [];
        $seen = [];

        foreach ($units as $index => $unit) {

            if (!is_array($unit)) {
                $warnings[] =
                    "Unit {$index}: không phải object.";

                continue;
            }

            $unit =
                $this->normalizeUnit(
                    $unit,
                    $block
                );

            /*
             * ----------------------------
             * 1. Validate type
             * ----------------------------
             */
            if (
                !in_array(
                    $unit['type'],
                    self::ALLOWED_TYPES,
                    true
                )
            ) {

                $warnings[] =
                    "Unit {$index}: type không hợp lệ.";

                continue;
            }


            /*
             * ----------------------------
             * 2. Validate grade
             * ----------------------------
             */
            if (
                $unit['grade_min'] !== null
                &&
                (
                    $unit['grade_min'] < 1
                    ||
                    $unit['grade_min'] > 12
                )
            ) {

                $warnings[] =
                    "Unit {$index}: grade_min không hợp lệ.";

                continue;
            }

            if (
                $unit['grade_max'] !== null
                &&
                (
                    $unit['grade_max'] < 1
                    ||
                    $unit['grade_max'] > 12
                )
            ) {

                $warnings[] =
                    "Unit {$index}: grade_max không hợp lệ.";

                continue;
            }

            if (
                $unit['grade_min'] !== null
                &&
                $unit['grade_max'] !== null
                &&
                $unit['grade_min']
                >
                $unit['grade_max']
            ) {

                $warnings[] =
                    "Unit {$index}: grade_min > grade_max.";

                continue;
            }


            /*
             * ----------------------------
             * 3. Validate selection_type
             * ----------------------------
             */
            if (
                !in_array(
                    $unit['selection_type'],
                    self::ALLOWED_SELECTION_TYPES,
                    true
                )
            ) {

                $warnings[] =
                    "Unit {$index}: selection_type không hợp lệ.";

                $unit['selection_type'] =
                    null;
            }


            /*
             * ----------------------------
             * 4. Unit phải có dữ liệu
             * ----------------------------
             */
            if (
                $this->isEmptyUnit(
                    $unit
                )
            ) {

                $warnings[] =
                    "Unit {$index}: unit rỗng.";

                continue;
            }


            /*
             * ----------------------------
             * 5. Literary work
             * ----------------------------
             */
            if (
                $unit['type']
                ===
                'literary_work'
                &&
                $unit['title'] === null
            ) {

                $warnings[] =
                    "Unit {$index}: literary_work thiếu title.";

                continue;
            }


            /*
             * ----------------------------
             * 6. Chống duplicate
             * ----------------------------
             */
            $hash =
                $this->makeUnitHash(
                    $unit
                );

            if (isset($seen[$hash])) {

                $warnings[] =
                    "Unit {$index}: duplicate, đã bỏ.";

                continue;
            }

            $seen[$hash] = true;


            $validated[] =
                $unit;
        }


        return [
            'units' =>
            array_values(
                $validated
            ),

            'warnings' =>
            $warnings,
        ];
    }


    private function normalizeUnit(
        array $unit,
        array $block
    ): array {

        /*
         * Metadata backend là source of truth.
         */
        $subject =
            $block['subject']
            ?? null;

        $gradeMin =
            $block['grade_min']
            ?? null;

        $gradeMax =
            $block['grade_max']
            ?? null;


        $learningOutcomes =
            $unit['learning_outcomes']
            ?? [];

        if (!is_array($learningOutcomes)) {
            $learningOutcomes = [];
        }


        /*
         * Làm sạch YCCĐ.
         */
        $learningOutcomes =
            array_map(
                fn($value) =>
                $this->normalizeString(
                    $value
                ),
                $learningOutcomes
            );

        $learningOutcomes =
            array_values(
                array_filter(
                    $learningOutcomes,
                    fn($value) =>
                    $value !== null
                )
            );

        /*
         * Chống YCCĐ trùng trong cùng unit.
         */
        $learningOutcomes =
            array_values(
                array_unique(
                    $learningOutcomes
                )
            );


        return [

            'type' =>
            $this->normalizeString(
                $unit['type']
                    ?? null
            ),

            'subject' =>
            $subject,

            /*
             * Không tin grade do AI trả.
             */
            'grade_min' =>
            $gradeMin,

            'grade_max' =>
            $gradeMax,

            'domain' =>
            $this->normalizeString(
                $unit['domain']
                    ?? null
            ),

            'topic' =>
            $this->normalizeString(
                $unit['topic']
                    ?? null
            ),

            'section' =>
            $this->normalizeString(
                $unit['section']
                    ?? null
            ),

            'subsection' =>
            $this->normalizeString(
                $unit['subsection']
                    ?? null
            ),

            'title' =>
            $this->normalizeString(
                $unit['title']
                    ?? null
            ),

            'author' =>
            $this->normalizeString(
                $unit['author']
                    ?? null
            ),

            'genre' =>
            $this->normalizeString(
                $unit['genre']
                    ?? null
            ),

            'selection_type' =>
            $this->normalizeString(
                $unit['selection_type']
                    ?? null
            ),

            'content' =>
            $this->normalizeString(
                $unit['content']
                    ?? null
            ),

            'learning_outcomes' =>
            $learningOutcomes,
        ];
    }


    private function normalizeString(
        mixed $value
    ): ?string {

        if (!is_string($value)) {
            return null;
        }

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                trim($value)
            );

        return $value === ''
            ? null
            : $value;
    }


    private function isEmptyUnit(
        array $unit
    ): bool {

        return
            $unit['domain'] === null
            &&
            $unit['topic'] === null
            &&
            $unit['section'] === null
            &&
            $unit['subsection'] === null
            &&
            $unit['title'] === null
            &&
            $unit['content'] === null
            &&
            empty($unit['learning_outcomes']);
    }


    private function makeUnitHash(
        array $unit
    ): string {

        /*
         * Không dùng author/genre...
         * làm key chính cho curriculum_content.
         */
        $data = [
            $unit['type'],
            $unit['subject'],
            $unit['grade_min'],
            $unit['grade_max'],
            $unit['domain'],
            $unit['topic'],
            $unit['section'],
            $unit['subsection'],
            $unit['title'],
            $unit['content'],
            $unit['learning_outcomes'],
        ];

        return hash(
            'sha256',
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE
            )
        );
    }
}
