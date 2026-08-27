<?php

namespace App\Services\RAG;

use App\Services\RAG\Retrieval\CurriculumRetrieverService;

final class CurriculumContextProvider
{
    public function __construct(
        private CurriculumRetrieverService $retriever
    ) {}

    public function provide(
        string $subject,
        int $grade,
        string $query,
        int $limit = 6,
        ?float $scoreThreshold = null,
        array $curriculumUnitIds = [],
    ): array {
        $results = $this->retriever->retrieve(
            subject: $subject,
            grade: $grade,
            query: $query,
            limit: $limit,
            scoreThreshold: $scoreThreshold,
            unitIds: $curriculumUnitIds,
        );

        $context = $this->retriever
            ->buildContext($results);

        $sources = array_map(
            fn(array $result): array => [
                'chunk_id' =>
                $result['chunk_id'] ?? null,

                'unit_id' =>
                $result['unit_id'] ?? null,

                'document_id' =>
                $result['document_id'] ?? null,

                'score' =>
                $result['score'] ?? null,

                'subject' =>
                $result['subject'] ?? null,

                'grade_min' =>
                $result['grade_min'] ?? null,

                'grade_max' =>
                $result['grade_max'] ?? null,

                'domain' =>
                $result['domain'] ?? null,

                'topic' =>
                $result['topic'] ?? null,

                'section' =>
                $result['section'] ?? null,

                'page_start' =>
                $result['page_start'] ?? null,

                'page_end' =>
                $result['page_end'] ?? null,
            ],
            $results
        );

        return [
            'context' => $context,
            'sources' => array_values($sources),
        ];
    }
}
