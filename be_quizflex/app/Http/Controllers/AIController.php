<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQuizJob;
use App\Models\AiJob;
use App\Models\AiLog;
use App\Models\CurriculumUnit;
use App\Models\Grade;
use App\Models\Subject;
use App\Services\AI\PromptQualityValidator;
use App\Services\RAG\Curriculum\CurriculumSubjectResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AIController extends Controller
{
    public function generate(Request $request)
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:5000'],
            'count' => ['nullable', 'integer', 'min:1', 'max:60'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard'],
            'language' => ['nullable', 'string', 'in:vi,en'],
            'visibility' => ['nullable', 'string', 'in:private,public,group'],
            'education_level_id' => [
                'nullable',
                'required_with:grade_id,subject_id,topic_name,curriculum_unit_ids',
                'integer',
                'exists:education_levels,id',
            ],
            'grade_id' => [
                'nullable',
                'required_with:education_level_id,subject_id,topic_name,curriculum_unit_ids',
                'integer',
                'exists:grades,id',
            ],
            'subject_id' => [
                'nullable',
                'required_with:education_level_id,grade_id,topic_name,curriculum_unit_ids',
                'integer',
                'exists:subjects,id',
            ],
            'topic_name' => [
                'nullable',
                'required_with:education_level_id,grade_id,subject_id,curriculum_unit_ids',
                'string',
                'max:150',
            ],
            'curriculum_unit_ids' => [
                'nullable',
                'required_with:education_level_id,grade_id,subject_id,topic_name',
                'array',
                'min:1',
                'max:100',
            ],
            'curriculum_unit_ids.*' => [
                'integer',
                'distinct',
                'exists:curriculum_units,id',
            ],
        ]);

        $data = $this->validateTaxonomy($data);

        $prompt = trim((string) $data['prompt']);

        $promptValidation = app(PromptQualityValidator::class)
            ->validate($prompt);

        if (!$promptValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $promptValidation['message'],
                'code' => 'invalid_prompt',
                'data' => [
                    'quota_charged' => false,
                    'job_id' => null,
                    'status' => 'rejected',
                ],
            ], 422);
        }

        $user = auth('api')->user();

        if ($user && strtolower($user->role ?? '') === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Admin không được tạo Quiz bằng AI.',
            ], 403);
        }

        if (($user->ai_quota_remaining ?? 0) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'AI quota exhausted.',
            ], 403);
        }

        $job = AiJob::create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => $prompt,
            'education_level_id' => $data['education_level_id'] ?? null,
            'grade_id' => $data['grade_id'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'topic_name' => $data['topic_name'] ?? null,
            'curriculum_unit_ids' => $data['curriculum_unit_ids'] ?? null,
            'requested_count' => $data['count'] ?? 10,
            'difficulty' => $data['difficulty'] ?? 'medium',
            'language' => $data['language'] ?? 'vi',
            'visibility' => $data['visibility'] ?? 'private',
            'status' => 'pending',
        ]);

        $runSynchronously = config('queue.default') === 'sync' || $request->boolean('sync');

        try {
            if ($runSynchronously) {
                GenerateQuizJob::dispatchSync($job->uuid);
                $job->refresh();
            } else {
                GenerateQuizJob::dispatch($job->uuid);
            }
        } catch (Throwable $exception) {
            $job->refresh();

            if ($job->status !== 'failed') {
                (new GenerateQuizJob($job->uuid))->failed($exception);
                $job->refresh();
            }
        }

        return response()->json([
            'success' => $job->status !== 'failed',
            'message' => $runSynchronously ? 'AI job processed.' : 'AI job queued.',
            'data' => [
                'job_id' => $job->uuid,
                'status' => $job->status,
                'prompt' => $job->prompt,
                'education_level_id' => $job->education_level_id,
                'grade_id' => $job->grade_id,
                'subject_id' => $job->subject_id,
                'topic_name' => $job->topic_name,
                'curriculum_unit_ids' => $job->curriculum_unit_ids,
                'requested_count' => $job->requested_count,
                'difficulty' => $job->difficulty,
                'language' => $job->language,
                'visibility' => $job->visibility,
                'questions_generated' => $job->questions_generated,
                'quiz_id' => $job->quiz_id,
                'error_message' => $job->error_message,
            ],
        ], $job->status === 'failed' ? 500 : ($runSynchronously ? 200 : 202));
    }

    private function validateTaxonomy(array $data): array
    {
        if (empty($data['subject_id'])) {
            return $data;
        }

        $grade = Grade::query()
            ->whereKey($data['grade_id'])
            ->where(
                'education_level_id',
                $data['education_level_id']
            )
            ->first();

        if (!$grade) {
            throw ValidationException::withMessages([
                'grade_id' => 'Lớp không thuộc cấp học đã chọn.',
            ]);
        }

        $subject = Subject::query()
            ->whereKey($data['subject_id'])
            ->first();

        if (
            !$subject
            || !$grade->subjects()
                ->where('subjects.id', $subject->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'subject_id' => 'Môn học không thuộc lớp đã chọn.',
            ]);
        }

        $scope = app(CurriculumSubjectResolver::class)
            ->resolve(
                subject: $subject,
                grade: $grade,
            );

        if ($scope === null) {
            throw ValidationException::withMessages([
                'subject_id' => 'Môn học chưa có dữ liệu RAG.',
            ]);
        }

        $unitIds = collect($data['curriculum_unit_ids'])
            ->map(fn($id): int => (int) $id)
            ->unique()
            ->values();

        $validUnits = CurriculumUnit::query()
            ->whereIn('id', $unitIds)
            ->where('subject', $scope['subject'])
            ->where('grade_min', '<=', $grade->level_number)
            ->where('grade_max', '>=', $grade->level_number)
            ->when(
                !empty($scope['domain']),
                fn($query) => $query->where(
                    'domain',
                    $scope['domain']
                )
            )
            ->whereHas(
                'chunks',
                fn($query) => $query
                    ->where('embedding_status', 'embedded')
                    ->whereNotNull('qdrant_point_id')
            )
            ->count();

        if ($validUnits !== $unitIds->count()) {
            throw ValidationException::withMessages([
                'curriculum_unit_ids' =>
                    'Nguồn RAG không thuộc môn, lớp hoặc chủ đề đã chọn.',
            ]);
        }

        $data['topic_name'] = trim($data['topic_name']);
        $data['curriculum_unit_ids'] = $unitIds->all();

        return $data;
    }

    public function show(int $id)
    {
        $log = AiLog::query()
            ->where('user_id', auth('api')->id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    public function status(string $jobId)
    {
        $job = AiJob::query()
            ->with([
                'quiz.user:id,name',
                'quiz.questions.answers',
                'log:id,status,questions_generated,error_message',
            ])
            ->where('uuid', $jobId)
            ->where('user_id', auth('api')->id())
            ->firstOrFail();

        $quizData = null;
        if ($job->quiz) {
            /** @var \App\Http\Controllers\QuizController $quizController */
            $quizController = app(\App\Http\Controllers\QuizController::class);
            $quizData = $quizController->formatQuiz($job->quiz, true);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $job->uuid,
                'status' => $job->status,
                'prompt' => $job->prompt,
                'education_level_id' => $job->education_level_id,
                'grade_id' => $job->grade_id,
                'subject_id' => $job->subject_id,
                'topic_name' => $job->topic_name,
                'curriculum_unit_ids' => $job->curriculum_unit_ids,
                'requested_count' => $job->requested_count,
                'difficulty' => $job->difficulty,
                'language' => $job->language,
                'visibility' => $job->visibility,
                'current_step' => $job->current_step, // <-- THÊM DÒNG NÀY
                'questions_generated' => $job->questions_generated,
                'quiz_id' => $job->quiz_id,
                'log_id' => $job->ai_log_id,
                'error_message' => $job->error_message,
                'started_at' => $job->started_at,
                'finished_at' => $job->finished_at,
                'created_at' => $job->created_at,
                'updated_at' => $job->updated_at,
                'quiz' => $job->quiz,
                'quiz_full' => $quizData,
                'log' => $job->log,
            ],
        ]);
    }
}
