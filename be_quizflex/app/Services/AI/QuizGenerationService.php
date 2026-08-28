<?php

namespace App\Services\AI;

use App\AI\Clients\OpenRouterClient;
use App\AI\DTOs\ChatRequest;
use App\AI\Normalization\QuizResponseNormalizer;
use App\AI\Prompts\GenerateQuizPrompt;
use App\AI\Validation\QuizResponseValidator;
use App\Services\RAG\CurriculumContextProvider;
use RuntimeException;

final class QuizGenerationService
{
    public function __construct(
        private OpenRouterClient $client,
        private GenerateQuizPrompt $promptBuilder,
        private QuizResponseValidator $validator,
        private QuizResponseNormalizer $normalizer,
        private CurriculumContextProvider $contextProvider,
    ) {}

    public function generate(
        string $prompt,
        int $count = 10,
        ?string $subject = null,
        ?int $grade = null,
        array $curriculumUnitIds = [],
    ): array {
        $prompt = trim($prompt);
        $count = max(1, $count);

        $subject = $subject !== null
            ? trim($subject)
            : null;

        $hasSubject =
            $subject !== null
            && $subject !== '';

        $hasGrade = $grade !== null;

        $curriculumUnitIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $curriculumUnitIds),
                    fn(int $id): bool => $id > 0
                )
            )
        );

        /*
         * RAG chỉ chạy khi có đủ subject và grade.
         */
        if ($hasSubject !== $hasGrade) {
            throw new RuntimeException(
                'RAG yêu cầu đầy đủ subject và grade.'
            );
        }

        if ($curriculumUnitIds !== [] && !$hasSubject) {
            throw new RuntimeException(
                'Nguồn RAG yêu cầu đầy đủ subject và grade.'
            );
        }

        $curriculumContext = '';
        $ragSources = [];
        $ragEnabled = false;
        $retrievalLimit = max(
            1,
            (int) config(
                'rag.retrieval.limit',
                6
            )
        );

        $configuredScoreThreshold = config(
            'rag.retrieval.score_threshold'
        );

        $scoreThreshold = is_numeric(
            $configuredScoreThreshold
        )
            ? (float) $configuredScoreThreshold
            : null;

        if ($hasSubject && $hasGrade && !empty($curriculumUnitIds)) {
            try {
                $rag = $this->contextProvider->provide(
                    subject: $subject,
                    grade: $grade,
                    query: $prompt,
                    limit: $retrievalLimit,
                    scoreThreshold: $scoreThreshold,
                    curriculumUnitIds: $curriculumUnitIds,
                );

                $curriculumContext = trim(
                    (string) (
                        $rag['context'] ?? ''
                    )
                );

                $ragSources = is_array(
                    $rag['sources'] ?? null
                )
                    ? $rag['sources']
                    : [];

                if ($curriculumContext !== '') {
                    $ragEnabled = true;
                }
            } catch (\Throwable $e) {
                $curriculumContext = '';
                $ragEnabled = false;
            }
        }

        $finalPrompt = $this->promptBuilder->build(
            prompt: $prompt,
            count: $count,
            curriculumContext: $curriculumContext,
            subject: $subject,
            grade: $grade,
        );

        $response = $this->client->generateJson(
            new ChatRequest(
                systemPrompt: 'You generate valid JSON only. '
                    . 'Do not wrap JSON in markdown.',

                userPrompt: $finalPrompt,

                maxTokens: max(
                    2000,
                    $count * 250 + 300
                ),

                temperature: 0.4,
            )
        );

        if (!$this->validator->isValid(
            $response->data
        )) {
            throw new RuntimeException(
                'AI JSON structure invalid.'
            );
        }

        $quiz = $this->normalizer->normalize(
            $response->data,
            $count
        );

        $quiz['meta'] = [
            'tokens_used' =>
            $response->totalTokens(),

            'raw_json' =>
            $response->rawJson,

            'requested_count' =>
            $count,

            'actual_count' =>
            count($quiz['questions']),

            'provider' =>
            $response->provider,

            'model' =>
            $response->model,

            'rag_enabled' =>
            $ragEnabled,

            'rag_subject' =>
            $subject,

            'rag_grade' =>
            $grade,

            'rag_curriculum_unit_ids' =>
            $curriculumUnitIds,

            'rag_source_count' =>
            count($ragSources),

            'rag_sources' =>
            $ragSources,
        ];

        return $quiz;
    }
}
