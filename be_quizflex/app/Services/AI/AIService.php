<?php

namespace App\Services\AI;

class AIService
{
    public function __construct(
        private QuizGenerationService $quizGenerationService,
        private MistralOcrQuizService $mistralOcrQuizService,
        private OcrQuizParserService $ocrQuizParserService,
        private QuizReviewService $quizReviewService,
        private QuizSuggestionService $quizSuggestionService,
    ) {}

    public function generateQuiz(
        string $prompt,
        int $count = 10,
        ?string $subject = null,
        ?int $grade = null,
        array $curriculumUnitIds = [],
    ): array {
        return $this->quizGenerationService->generate(
            prompt: $prompt,
            count: $count,
            subject: $subject,
            grade: $grade,
            curriculumUnitIds: $curriculumUnitIds,
        );
    }

    public function mistralOcrToQuizJson(
        string $filePath,
        string $extension,
    ): array {
        return $this->mistralOcrQuizService
            ->convertToQuiz(
                filePath: $filePath,
                extension: $extension,
            );
    }

    public function parseQuiz(
        string $prompt,
    ): array {
        return $this->ocrQuizParserService
            ->parse($prompt);
    }

    public function reviewQuiz(
        string $prompt,
    ): array {
        return $this->quizReviewService
            ->review($prompt);
    }

    public function reviewQuizCached(
        string $prompt,
        array $payload,
        $userId = null,
    ): array {
        return $this->quizReviewService
            ->reviewCached(
                prompt: $prompt,
                payload: $payload,
                userId: $userId,
            );
    }

    public function suggestQuiz(
        array $data,
    ): array {
        return $this->quizSuggestionService
            ->suggest($data);
    }
}
