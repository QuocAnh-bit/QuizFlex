<?php

namespace App\Services\RAG\Retrieval;

use App\Models\CurriculumChunk;
use App\Services\RAG\Embedding\OpenRouterEmbeddingService;
use App\Services\RAG\Qdrant\QdrantService;
use RuntimeException;

class CurriculumRetrieverService
{
    public function __construct(
        private OpenRouterEmbeddingService $embeddingService,
        private QdrantService $qdrant
    ) {}


    /**
     * Tìm nội dung chương trình phù hợp.
     */
    public function retrieve(
        string $subject,
        int $grade,
        string $query,
        int $limit = 5,
        ?float $scoreThreshold = null,
        array $unitIds = [],
    ): array {

        $subject = trim($subject);
        $query = trim($query);

        if ($subject === '') {
            throw new RuntimeException(
                'Subject không được rỗng.'
            );
        }

        if ($grade < 1 || $grade > 12) {
            throw new RuntimeException(
                'Grade phải từ 1 đến 12.'
            );
        }

        if ($query === '') {
            throw new RuntimeException(
                'Query không được rỗng.'
            );
        }

        $limit = max(
            1,
            min($limit, 20)
        );

        $unitIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $unitIds),
                    fn(int $id): bool => $id > 0
                )
            )
        );

        $unitIdLookup = array_fill_keys(
            $unitIds,
            true
        );


        /*
        |--------------------------------------------------------------------------
        | 1. Embedding câu truy vấn
        |--------------------------------------------------------------------------
        */

        $embedding =
            $this->embeddingService->embed(
                $query
            );


        /*
        |--------------------------------------------------------------------------
        | 2. Search Qdrant
        |--------------------------------------------------------------------------
        */

        $points =
            $this->qdrant->search(
                vector: $embedding['vector'],
                subject: $subject,
                grade: $grade,
                limit: $limit,
                unitIds: $unitIds,
            );


        if (empty($points)) {
            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Lấy chunk IDs theo đúng thứ tự Qdrant
        |--------------------------------------------------------------------------
        */

        $chunkIds = [];

        foreach ($points as $point) {

            $payload =
                $point['payload']
                ?? [];

            $chunkId =
                $payload['chunk_id']
                ?? $point['id']
                ?? null;

            if ($chunkId === null) {
                continue;
            }

            $chunkIds[] =
                (int) $chunkId;
        }


        if (empty($chunkIds)) {
            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | 4. MySQL là source of truth
        |--------------------------------------------------------------------------
        */

        $chunks =
            CurriculumChunk::query()
            ->with('unit')
            ->whereIn(
                'id',
                $chunkIds
            )
            ->when(
                $unitIds !== [],
                fn($query) => $query->whereIn(
                    'unit_id',
                    $unitIds
                )
            )
            ->get()
            ->keyBy('id');


        /*
        |--------------------------------------------------------------------------
        | 5. Build kết quả theo ranking Qdrant
        |--------------------------------------------------------------------------
        */

        $results = [];

        $usedHashes = [];


        foreach ($points as $point) {

            $score =
                isset($point['score'])
                ? (float) $point['score']
                : 0.0;


            /*
             * Có threshold thì loại
             * các kết quả similarity quá thấp.
             */
            if (
                $scoreThreshold !== null
                &&
                $score < $scoreThreshold
            ) {
                continue;
            }


            $payload =
                $point['payload']
                ?? [];


            $chunkId =
                $payload['chunk_id']
                ?? $point['id']
                ?? null;


            if ($chunkId === null) {
                continue;
            }


            $chunk =
                $chunks->get(
                    (int) $chunkId
                );


            if (!$chunk || !$chunk->unit) {
                continue;
            }


            /*
             * Loại duplicate content.
             */
            if (
                $chunk->content_hash
                &&
                isset(
                    $usedHashes[$chunk->content_hash]
                )
            ) {
                continue;
            }


            if ($chunk->content_hash) {

                $usedHashes[$chunk->content_hash] = true;
            }


            $unit = $chunk->unit;

            if (
                $unitIds !== []
                && !isset($unitIdLookup[$unit->id])
            ) {
                continue;
            }


            /*
             * Safety check lại metadata
             * từ MySQL.
             *
             * Không chỉ tin payload Qdrant.
             */
            if ($unit->subject !== $subject) {
                continue;
            }


            if (
                $unit->grade_min !== null
                &&
                $unit->grade_min > $grade
            ) {
                continue;
            }


            if (
                $unit->grade_max !== null
                &&
                $unit->grade_max < $grade
            ) {
                continue;
            }


            $results[] = [

                'score' =>
                $score,

                'chunk_id' =>
                $chunk->id,

                'unit_id' =>
                $unit->id,

                'document_id' =>
                $unit->document_id,

                'subject' =>
                $unit->subject,

                'grade_min' =>
                $unit->grade_min,

                'grade_max' =>
                $unit->grade_max,

                'type' =>
                $unit->type,

                'domain' =>
                $unit->domain,

                'topic' =>
                $unit->topic,

                'section' =>
                $unit->section,

                'subsection' =>
                $unit->subsection,

                'title' =>
                $unit->title,

                /*
                 * Đây mới là nội dung
                 * sẽ dùng làm context AI.
                 */
                'content' =>
                $chunk->content,

                'page_start' =>
                $unit->page_start,

                'page_end' =>
                $unit->page_end,
            ];
        }


        return $results;
    }


    /**
     * Build context để đưa thẳng vào prompt AI.
     */
    public function buildContext(
        array $results
    ): string {

        if (empty($results)) {
            return '';
        }


        $contexts = [];


        foreach (
            $results as $index => $result
        ) {

            $lines = [];

            $lines[] =
                '[NGỮ CẢNH '
                . ($index + 1)
                . ']';


            if (!empty($result['subject'])) {

                $lines[] =
                    'Môn: '
                    . $result['subject'];
            }


            if (
                isset($result['grade_min'])
                &&
                isset($result['grade_max'])
            ) {

                if (
                    $result['grade_min']
                    ===
                    $result['grade_max']
                ) {

                    $lines[] =
                        'Lớp: '
                        . $result['grade_min'];
                } else {

                    $lines[] =
                        'Lớp: '
                        . $result['grade_min']
                        . '-'
                        . $result['grade_max'];
                }
            }


            if (!empty($result['domain'])) {

                $lines[] =
                    'Lĩnh vực: '
                    . $result['domain'];
            }


            if (!empty($result['topic'])) {

                $lines[] =
                    'Chủ đề: '
                    . $result['topic'];
            }


            if (!empty($result['section'])) {

                $lines[] =
                    'Phần: '
                    . $result['section'];
            }


            if (!empty($result['title'])) {

                $lines[] =
                    'Tiêu đề: '
                    . $result['title'];
            }


            if (
                $result['page_start'] !== null
            ) {

                $pageText =
                    'Trang: '
                    . $result['page_start'];

                if (
                    $result['page_end'] !== null
                    &&
                    $result['page_end']
                    !==
                    $result['page_start']
                ) {

                    $pageText .=
                        '-'
                        . $result['page_end'];
                }

                $lines[] =
                    $pageText;
            }


            $lines[] = '';

            $lines[] =
                $result['content'];


            $contexts[] =
                implode(
                    "\n",
                    $lines
                );
        }


        return implode(
            "\n\n--------------------\n\n",
            $contexts
        );
    }


    /**
     * Shortcut:
     * retrieve + build context.
     */
    public function retrieveContext(
        string $subject,
        int $grade,
        string $query,
        int $limit = 5,
        ?float $scoreThreshold = null,
        array $unitIds = [],
    ): string {

        $results =
            $this->retrieve(
                subject: $subject,
                grade: $grade,
                query: $query,
                limit: $limit,
                scoreThreshold: $scoreThreshold,
                unitIds: $unitIds,
            );


        return $this->buildContext(
            $results
        );
    }
}
