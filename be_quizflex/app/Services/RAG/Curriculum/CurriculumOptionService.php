<?php

namespace App\Services\RAG\Curriculum;

use App\Models\CurriculumUnit;
use Illuminate\Support\Str;
use RuntimeException;

final class CurriculumOptionService
{
    public function options(
        string $subject,
        int $grade,
        ?string $domain = null,
    ): array {
        $subject = trim($subject);

        $domain = $domain !== null
            ? trim($domain)
            : null;

        if ($subject === '') {
            throw new RuntimeException(
                'Môn học không được để trống.'
            );
        }

        if ($grade < 1 || $grade > 12) {
            throw new RuntimeException(
                'Lớp phải nằm trong khoảng 1 đến 12.'
            );
        }

        $query = CurriculumUnit::query()
            ->where(
                'subject',
                $subject
            )
            ->where(
                'grade_min',
                '<=',
                $grade
            )
            ->where(
                'grade_max',
                '>=',
                $grade
            );

        $hasEmbeddedChunks = (clone $query)->whereHas(
            'chunks',
            function ($query): void {
                $query
                    ->where(
                        'embedding_status',
                        'embedded'
                    )
                    ->whereNotNull(
                        'qdrant_point_id'
                    );
            }
        )->exists();

        if ($hasEmbeddedChunks) {
            $query->whereHas(
                'chunks',
                function ($query): void {
                    $query
                        ->where(
                            'embedding_status',
                            'embedded'
                        )
                        ->whereNotNull(
                            'qdrant_point_id'
                        );
                }
            );
        }

        /*
         * Domain được dùng cho những môn
         * tích hợp như Lịch sử và Địa lí.
         */
        if (
            $domain !== null
            && $domain !== ''
        ) {
            $query->where(
                'domain',
                $domain
            );
        }

        return $query
            ->orderBy('domain')
            ->orderBy('topic')
            ->orderBy('section')
            ->orderBy('subsection')
            ->orderBy('title')

            ->get([
                'id',
                'subject',
                'grade_min',
                'grade_max',
                'domain',
                'topic',
                'section',
                'subsection',
                'title',
                'content',
            ])

            ->map(function (
                CurriculumUnit $unit
            ): array {
                $label = $this->resolveMainTopic(
                    $unit
                );

                return [
                    'curriculum_unit_ids' => [
                        $unit->id,
                    ],
                    'label' => $label,
                ];
            })

            /*
             * Một chủ đề chính có thể gồm nhiều
             * curriculum unit. Gộp các ID để RAG
             * vẫn truy xuất đủ nguồn liên quan.
             */
            ->groupBy(
                fn(array $option): string =>
                mb_strtolower(
                    $this->normalizeText(
                        $option['label']
                    )
                )
            )

            ->map(function ($group): array {
                $first = $group->first();

                /*
                 * Gộp tất cả unit ID để không
                 * làm mất nguồn RAG.
                 */
                $first['curriculum_unit_ids'] =
                    $group
                    ->flatMap(
                        fn(array $option): array =>
                        $option['curriculum_unit_ids']
                    )
                    ->unique()
                    ->values()
                    ->all();

                return $first;
            })

            ->values()
            ->all();
    }

    private function resolveMainTopic(
        CurriculumUnit $unit
    ): string {
        /*
         * Topic là chủ đề chính. Những môn không
         * có topic sẽ lần lượt dùng metadata gần
         * nhất, không gọi AI để tự đặt chủ đề.
         */
        $fields = [
            $unit->topic,
            $unit->domain,
            $unit->section,
            $unit->subsection,
            $unit->title,
        ];

        foreach ($fields as $value) {
            $value = $this->normalizeText(
                (string) $value
            );

            if ($value !== '') {
                return $value;
            }
        }

        $content = $this->normalizeText(
            (string) $unit->content
        );

        if ($content === '') {
            return 'Nội dung chương trình #'
                . $unit->id;
        }

        return Str::limit(
            $content,
            120,
            ''
        );
    }

    private function normalizeText(
        string $value
    ): string {
        return trim(
            (string) preg_replace(
                '/\s+/u',
                ' ',
                $value
            )
        );
    }
}
