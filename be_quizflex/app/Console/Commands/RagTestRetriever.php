<?php

namespace App\Console\Commands;

use App\Services\RAG\Retrieval\CurriculumRetrieverService;
use Illuminate\Console\Command;

class RagTestRetriever extends Command
{
    protected $signature = 'rag:test-retriever
        {query}
        {--subject=Toán}
        {--grade=8}
        {--limit=5}';

    protected $description =
    'Test CurriculumRetrieverService';


    public function handle(
        CurriculumRetrieverService $retriever
    ): int {

        $query =
            $this->argument('query');

        $subject =
            $this->option('subject');

        $grade =
            (int) $this->option('grade');

        $limit =
            (int) $this->option('limit');


        try {

            $results =
                $retriever->retrieve(
                    subject: $subject,
                    grade: $grade,
                    query: $query,
                    limit: $limit
                );


            if (empty($results)) {

                $this->warn(
                    'Không tìm thấy ngữ cảnh phù hợp.'
                );

                return self::SUCCESS;
            }


            foreach (
                $results as $index => $result
            ) {

                $this->newLine();

                $this->info(
                    '#'
                        . ($index + 1)
                        . ' | score='
                        . number_format(
                            $result['score'],
                            6
                        )
                );


                $this->line(
                    'Chunk: '
                        . $result['chunk_id']
                );

                $this->line(
                    'Subject: '
                        . $result['subject']
                );

                $this->line(
                    'Grade: '
                        . $result['grade_min']
                        . '-'
                        . $result['grade_max']
                );

                $this->line(
                    'Domain: '
                        . (
                            $result['domain']
                            ?? 'null'
                        )
                );

                $this->line(
                    'Topic: '
                        . (
                            $result['topic']
                            ?? 'null'
                        )
                );

                $this->line(
                    'Section: '
                        . (
                            $result['section']
                            ?? 'null'
                        )
                );

                $this->newLine();

                $this->line(
                    $result['content']
                );
            }


            $this->newLine();

            $this->warn(
                '========== CONTEXT CHO AI =========='
            );

            $this->newLine();


            $this->line(
                $retriever->buildContext(
                    $results
                )
            );


            return self::SUCCESS;
        } catch (\Throwable $e) {

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
