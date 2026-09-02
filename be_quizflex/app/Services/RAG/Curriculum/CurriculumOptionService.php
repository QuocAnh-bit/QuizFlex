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
        if ($domain !== null && $domain !== '') {
            /*
             * Lịch sử - Địa lí là môn tích hợp, nhưng PDF được parse thành
             * domain chi tiết như "Lịch sử Việt Nam" hoặc "Địa lí Việt Nam".
             * Lọc theo family domain để không làm rỗng danh sách chủ đề;
             * "Chủ đề chung" được dùng được cho cả hai phân môn.
             */
            if (in_array($domain, ['Lịch sử', 'Địa lí'], true)) {
                $query->where(function ($domainQuery) use ($domain): void {
                    $domainQuery
                        ->where('domain', 'like', $domain . '%')
                        ->orWhere('domain', 'Chủ đề chung');
                });
            } else {
                $query->where('domain', $domain);
            }
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
         * Không thể dùng cùng một thứ tự metadata
         * cho mọi môn:
         *
         * - Toán và các môn nội dung: topic là mạch
         *   kiến thức phù hợp để hiển thị.
         * - Ngữ văn: title thường là tác phẩm hoặc
         *   nội dung cụ thể; topic thường chỉ là
         *   nhãn rộng như "Đọc" hay "Tập làm văn".
         * - Tiếng Anh: unit chủ điểm dùng topic,
         *   còn unit ngữ pháp/kĩ năng dùng title để
         *   tránh nhãn quá rộng như "Ngữ pháp".
         */
        $fields = match ($unit->subject) {
            'Ngữ văn' => [
                $unit->title,
                $unit->subsection,
                $unit->section,
                $unit->topic,
                $unit->domain,
            ],

            'Tiếng Anh' => $this->englishLabelFields(
                $unit
            ),

            default => [
                $unit->topic,
                $unit->domain,
                $unit->section,
                $unit->subsection,
                $unit->title,
            ],
        };

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

    private function englishLabelFields(
        CurriculumUnit $unit
    ): array {
        $domain = mb_strtolower(
            $this->normalizeText(
                (string) $unit->domain
            )
        );

        /*
         * Chủ điểm giao tiếp (Gia đình, Trường học,
         * ...) đã có topic chính xác. Các unit còn
         * lại cần title cụ thể hơn tên kĩ năng/mạch.
         */
        if (
            $domain === 'chủ đề'
            || $domain === 'chủ điểm'
        ) {
            return [
                $unit->topic,
                $unit->title,
                $unit->subsection,
                $unit->section,
                $unit->domain,
            ];
        }

        return [
            $unit->title,
            $unit->topic,
            $unit->subsection,
            $unit->section,
            $unit->domain,
        ];
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
