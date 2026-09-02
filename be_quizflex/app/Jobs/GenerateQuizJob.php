<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\AiLog;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\AI\PromptQualityValidator;
use App\Services\RAG\Curriculum\CurriculumSubjectResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class GenerateQuizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const BATCH_SIZE = 10;
    private const MAX_RETRIES_PER_BATCH = 3;

    public int $tries = 3;

    public array $backoff = [30, 120];

    private string $batchTitle = 'AI Generated Quiz';
    private array $batchMeta = [];

    public function __construct(public string $jobUuid) {}

    public function handle(
        AIService $aiService,
        CurriculumSubjectResolver $subjectResolver,
    ): void {
        $job = AiJob::query()
            ->where('uuid', $this->jobUuid)
            ->first();

        if (!$job || $job->status === 'completed') {
            return;
        }

        $job->update([
            'status' => 'processing',
            'current_step' => 'validate_prompt', // <-- Thêm dòng này
            'error_message' => null,
            'started_at' => $job->started_at ?? now(),
        ]);
        sleep(1); // 💡 THÊM VÀO ĐÂY: Dừng 1s để sáng đèn ô 1

        $promptValidation = app(PromptQualityValidator::class)
            ->validate((string) $job->prompt);

        if (!$promptValidation['valid']) {
            throw new \RuntimeException(
                $promptValidation['message']
            );
        }

        $job->update(['current_step' => 'calling_ai_api']); // <-- Thêm dòng này
        sleep(2); // 💡 THÊM VÀO ĐÂY: Dừng 2s để sáng đèn ô 2 (giả vờ AI đang nghĩ lâu)

        $ragScope = $this->resolveRagScopeFromJob(
            job: $job,
            subjectResolver: $subjectResolver,
        );

        // Gọi AI nhiều lần theo batch, ghép kết quả trước khi lưu
        $allQuestions = $this->collectAllQuestions(
            $aiService,
            $this->buildPromptFromJob(
                job: $job,
                subject: $ragScope['subject'],
                grade: $ragScope['grade'],
            ),
            (int) $job->requested_count,
            $ragScope['subject'],
            $ragScope['grade'],
            $ragScope['curriculum_unit_ids'],
        );

        $job->update(['current_step' => 'parsing_ai_response']); // <-- Thêm dòng này
        sleep(1); // 💡 THÊM VÀO ĐÂY: Dừng 1s để sáng đèn ô 3

        // Lấy title từ batch đầu tiên (đã được lưu trong collectAllQuestions)
        $generatedQuiz = [
            'title' => $this->batchTitle,
            'questions' => $allQuestions,
            'meta' => $this->batchMeta,
        ];

        if (empty($generatedQuiz['questions'])) {
            throw new \RuntimeException('AI trả về dữ liệu không hợp lệ.');
        }

        $job->update(['current_step' => 'saving_to_database']); // <-- Thêm dòng này
        sleep(1); // 💡 THÊM VÀO ĐÂY: Dừng 1s để sáng đèn ô 4
        
        DB::transaction(function () use ($job, $generatedQuiz) {
            $user = User::query()->lockForUpdate()->findOrFail($job->user_id);

            if (($user->ai_quota_remaining ?? 0) <= 0) {
                throw new \RuntimeException('AI quota exhausted.');
            }

            $questionsOnly = ($job->response_json['output_mode'] ?? 'quiz') === 'questions_only';
            $quiz = $questionsOnly ? null : $this->storeQuiz($job, $generatedQuiz);

            $log = AiLog::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz?->id,
                'action_type' => 'ai_generate',
                'tokens_used' => (int) ($generatedQuiz['meta']['tokens_used'] ?? 0),
                'questions_generated' => count($generatedQuiz['questions']),
                'status' => 'success',
                'response_json' => $generatedQuiz,
            ]);

            $user->decrement('ai_quota_remaining');

            $job->update([
                'ai_log_id' => $log->id,
                'quiz_id' => $quiz?->id,
                'questions_generated' => count($generatedQuiz['questions']),
                'status' => 'completed',
                'current_step' => 'completed', // <-- Thêm dòng này
                'response_json' => [
                    ...$generatedQuiz,
                    'output_mode' => $questionsOnly ? 'questions_only' : 'quiz',
                ],
                'finished_at' => now(),
            ]);
        });
    }

    public function failed(Throwable $exception): void
    {
        $job = AiJob::query()
            ->where('uuid', $this->jobUuid)
            ->first();

        if (!$job || $job->status === 'completed') {
            return;
        }

        $logId = $job->ai_log_id;

        if ($logId) {
            AiLog::query()->whereKey($logId)->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        } else {
            $log = AiLog::create([
                'user_id' => $job->user_id,
                'quiz_id' => null,
                'action_type' => 'ai_generate',
                'tokens_used' => 0,
                'questions_generated' => 0,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            $logId = $log->id;
        }

        $job->update([
            'ai_log_id' => $logId,
            'status' => 'failed',
            'current_step' => 'failed', // <-- Thêm dòng này
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ]);
    }

    /**
     * Gọi AI nhiều lần theo batch (mỗi lần tối đa BATCH_SIZE câu),
     * chống trùng nội dung, retry nếu một lần thất bại.
     * Chỉ trả về mảng questions khi đã đủ số lượng yêu cầu.
     */
    private function collectAllQuestions(
        AIService $aiService,
        string $prompt,
        int $requestedCount,
        ?string $subject = null,
        ?int $grade = null,
        array $curriculumUnitIds = [],
    ): array {
        $allQuestions = [];
        $seenContents = [];

        while (count($allQuestions) < $requestedCount) {
            $remaining = $requestedCount - count($allQuestions);
            $batchCount = min(self::BATCH_SIZE, $remaining);

            $batchResult = null;
            $lastError = null;

            // Retry tối đa MAX_RETRIES_PER_BATCH lần nếu batch thất bại
            for ($retry = 1; $retry <= self::MAX_RETRIES_PER_BATCH; $retry++) {
                try {
                    $batchResult = $aiService->generateQuiz(
                        prompt: $prompt,
                        count: $batchCount,
                        subject: $subject,
                        grade: $grade,
                        curriculumUnitIds: $curriculumUnitIds,
                    );
                    break;
                } catch (\Throwable $e) {
                    $lastError = $e;
                    if ($retry < self::MAX_RETRIES_PER_BATCH) {
                        sleep(2);
                    }
                }
            }

            if ($batchResult === null) {
                throw new \RuntimeException(
                    'AI batch thất bại sau ' . self::MAX_RETRIES_PER_BATCH . ' lần thử: ' . $lastError?->getMessage()
                );
            }

            // Lưu title và meta từ batch đầu tiên
            if (empty($allQuestions)) {
                $this->batchTitle = $batchResult['title'] ?? 'AI Generated Quiz';
                $this->batchMeta = $batchResult['meta'] ?? [];
            } else {
                // Cộng dồn tokens_used từ các batch sau
                $this->batchMeta['tokens_used'] =
                    ($this->batchMeta['tokens_used'] ?? 0) + ($batchResult['meta']['tokens_used'] ?? 0);
            }

            // Thêm câu hỏi vào danh sách, bỏ qua câu trùng nội dung
            foreach ($batchResult['questions'] as $question) {
                $normalizedContent = mb_strtolower(trim((string) $question['content']));

                if (isset($seenContents[$normalizedContent])) {
                    continue; // bỏ qua câu trùng
                }

                $seenContents[$normalizedContent] = true;
                $allQuestions[] = $question;

                if (count($allQuestions) >= $requestedCount) {
                    break;
                }
            }
        }

        return $allQuestions;
    }

    private function storeQuiz(AiJob $job, array $generatedQuiz): Quiz
    {
        $questions = $generatedQuiz['questions'];

        $quiz = Quiz::create([
            'user_id' => $job->user_id,
            'title' => Str::limit(trim((string) ($generatedQuiz['title'] ?? $job->prompt)), 255, ''),
            'description' => $job->prompt,
            'category' => 'AI Generated',
            'education_level_id' => $job->education_level_id,
            'grade_id' => $job->grade_id,
            'subject_id' => $job->subject_id,
            'topic_name' => $job->topic_name,
            'tag' => 'AI',
            'difficulty' => $this->normalizeDifficulty($job->difficulty),
            'status' => $job->visibility === 'public' ? 'published' : 'draft',
            'is_public' => $job->visibility === 'public',
            'room_code' => $job->visibility === 'group' ? $this->generateRoomCode() : null,
            'time_limit_seconds' => max(300, count($questions) * 60),
            'cover' => null,
            'icon' => 'AI',
            'badge' => 'AI',
        ]);

        foreach ($questions as $questionIndex => $questionData) {
            $correctAnswers = collect($questionData['answers'])
                ->filter(fn(array $answer): bool => !empty($answer['is_correct']))
                ->count();

            $question = Question::create([
                'quiz_id' => $quiz->id,
                'user_id' => $job->user_id,
                'content' => trim((string) $questionData['content']),
                'image_url' => null,
                'type' => $correctAnswers > 1 ? 'multiple_choice' : 'single_choice',
                'difficulty' => $this->normalizeDifficulty($job->difficulty),
                'education_level_id' => $job->education_level_id,
                'grade_id' => $job->grade_id,
                'subject_id' => $job->subject_id,
                'topic_name' => $job->topic_name,
                'is_public' => $job->visibility === 'public',
                'order' => $questionIndex,
                'points' => 1,
            ]);

            $quiz->questions()->syncWithoutDetaching([
                $question->id => [
                    'order' => $questionIndex,
                    'points' => 1,
                ],
            ]);

            foreach ($questionData['answers'] as $answerIndex => $answerData) {
                Answer::create([
                    'question_id' => $question->id,
                    'content' => trim((string) $answerData['content']),
                    'is_correct' => (bool) $answerData['is_correct'],
                    'order' => $answerIndex,
                ]);
            }
        }

        return $quiz->fresh(['questions.answers']);
    }

    private function buildPromptFromJob(
        AiJob $job,
        ?string $subject = null,
        ?int $grade = null,
    ): string {
        $language = match (strtolower((string) $job->language)) {
            'en' => 'English',
            default => 'Vietnamese',
        };

        $difficulty = match (strtolower((string) $job->difficulty)) {
            'easy' => 'easy',
            'hard' => 'hard',
            default => 'medium',
        };

        // Loại bỏ các cụm từ chỉ số câu hỏi trong prompt của user
        // để tránh xung đột với số câu đã được xác định từ settings
        $cleanPrompt = preg_replace(
            '/\b(tạo|sinh|viết|generate|create)\s+\d+\s+(câu|questions?|câu hỏi)\b/iu',
            '',
            (string) $job->prompt
        );
        $cleanPrompt = trim((string) preg_replace('/\s{2,}/', ' ', $cleanPrompt));

        $topic = trim((string) $job->topic_name);

        $lines = [
            "Language: {$language}",
            "Difficulty: {$difficulty}",
            'Topic: ' . ($topic !== '' ? $topic : $cleanPrompt),
        ];

        if ($topic !== '' && $cleanPrompt !== '') {
            $lines[] = "User request: {$cleanPrompt}";
        }

        if ($subject !== null && $grade !== null) {
            $lines[] = "Subject: {$subject}";
            $lines[] = "Grade: {$grade}";
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Ưu tiên taxonomy đã được controller xác thực.
     * Chỉ dùng nhận diện từ prompt cho caller cũ.
     *
     * @return array{
     *     subject: ?string,
     *     grade: ?int,
     *     curriculum_unit_ids: array<int>
     * }
     */
    private function resolveRagScopeFromJob(
        AiJob $job,
        CurriculumSubjectResolver $subjectResolver,
    ): array {
        if ($job->subject_id && $job->grade_id) {
            $job->loadMissing([
                'subject',
                'grade',
            ]);

            if (!$job->subject || !$job->grade) {
                throw new \RuntimeException(
                    'Không tìm thấy môn hoặc lớp của AI job.'
                );
            }

            $scope = $subjectResolver->resolve(
                subject: $job->subject,
                grade: $job->grade,
            );

            /*
             * Không phải mọi môn đều đã được nạp curriculum_chunks.
             * RAG là nguồn bổ trợ, không phải điều kiện để AI có thể
             * tạo câu hỏi. Vẫn truyền tên lớp/môn vào prompt để AI bám
             * ngữ cảnh, nhưng không truyền unit nào cho retriever.
             */
            if ($scope === null) {
                return [
                    'subject' => (string) $job->subject->name,
                    'grade' => (int) $job->grade->level_number,
                    'curriculum_unit_ids' => [],
                ];
            }

            return [
                'subject' => $scope['subject'],
                'grade' => (int) $job->grade->level_number,
                'curriculum_unit_ids' => array_values(
                    array_map(
                        'intval',
                        $job->curriculum_unit_ids ?? []
                    )
                ),
            ];
        }

        $scope = $this->resolveRagScopeFromPrompt(
            (string) $job->prompt
        );

        return [
            ...$scope,
            'curriculum_unit_ids' => [],
        ];
    }

    /**
     * AiJob chưa lưu subject/grade riêng nên tạm thời
     * nhận diện scope RAG từ prompt người dùng.
     *
     * @return array{subject: ?string, grade: ?int}
     */
    private function resolveRagScopeFromPrompt(
        string $prompt
    ): array {
        $normalizedPrompt = mb_strtolower(
            trim($prompt)
        );

        $subject = $this->detectRagSubject(
            $normalizedPrompt
        );

        $grade = $this->detectRagGrade(
            $normalizedPrompt
        );

        /*
         * QuizGenerationService yêu cầu có đủ cả hai.
         * Không nhận diện đủ thì giữ luồng không RAG.
         */
        if ($subject === null || $grade === null) {
            return [
                'subject' => null,
                'grade' => null,
            ];
        }

        return [
            'subject' => $subject,
            'grade' => $grade,
        ];
    }

    private function detectRagSubject(
        string $normalizedPrompt
    ): ?string {
        $subjectAliases = [
            'Tiếng Anh' => [
                'tiếng anh',
                'english',
            ],
            'Ngữ văn' => [
                'ngữ văn',
                'văn học',
                'môn văn',
            ],
            'Vật lý' => [
                'vật lý',
                'vật lí',
                'physics',
            ],
            'Hóa học' => [
                'hóa học',
                'hoá học',
                'chemistry',
            ],
            'Sinh học' => [
                'sinh học',
                'biology',
            ],
            'Lịch sử' => [
                'lịch sử',
                'history',
            ],
            'Địa lý' => [
                'địa lý',
                'địa lí',
                'geography',
            ],
            'Tin học' => [
                'tin học',
                'informatics',
            ],
            'Toán' => [
                'toán học',
                'môn toán',
                'toán',
                'math',
            ],
        ];

        foreach ($subjectAliases as $subject => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains(
                    $normalizedPrompt,
                    $alias
                )) {
                    return $subject;
                }
            }
        }

        return null;
    }

    private function detectRagGrade(
        string $normalizedPrompt
    ): ?int {
        $matched = preg_match(
            '/\b(?:lớp|lop|khối|khoi|grade)\s*[:\-]?\s*([1-9]|1[0-2])\b/iu',
            $normalizedPrompt,
            $matches
        );

        if ($matched !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function normalizeDifficulty(?string $difficulty): string
    {
        return match (strtolower((string) $difficulty)) {
            'easy' => 'easy',
            'hard' => 'hard',
            default => 'medium',
        };
    }

    private function generateRoomCode(): string
    {
        return 'AI' . random_int(1000, 9999);
    }
}
