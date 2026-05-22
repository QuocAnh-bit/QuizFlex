<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\RoomAssignmentAnswer;
use App\Models\RoomAssignmentSubmission;
use App\Models\RoomMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomAssignmentSubmissionController extends Controller
{
    public function indexForAssignment(Request $request, Room $room, RoomAssignment $assignment)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((int) $assignment->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'BÃ i táº­p khÃ´ng thuá»™c room nÃ y.',
            ], 404);
        }

        if (!$this->canManageRoomSubmissions($room, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Báº¡n khÃ´ng cÃ³ quyá»n xem bÃ i ná»™p cá»§a assignment nÃ y.',
            ], 403);
        }

        $submissions = RoomAssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->with('user:id,name,email,role')
            ->withCount('answers')
            ->latest('submitted_at')
            ->latest('started_at')
            ->get()
            ->map(fn (RoomAssignmentSubmission $submission) => $this->formatManagedSubmissionSummary($submission));

        return response()->json([
            'success' => true,
            'message' => 'Danh sÃ¡ch bÃ i ná»™p cá»§a assignment',
            'data' => $submissions,
        ]);
    }

    public function showForAssignment(Request $request, Room $room, RoomAssignment $assignment, RoomAssignmentSubmission $submission)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((int) $assignment->room_id !== (int) $room->id || (int) $submission->assignment_id !== (int) $assignment->id) {
            return response()->json([
                'success' => false,
                'message' => 'KhÃ´ng tÃ¬m tháº¥y bÃ i ná»™p trong assignment nÃ y.',
            ], 404);
        }

        if (!$this->canManageRoomSubmissions($room, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Báº¡n khÃ´ng cÃ³ quyá»n xem káº¿t quáº£ bÃ i ná»™p nÃ y.',
            ], 403);
        }

        $submission->load([
            'user:id,name,email,role',
            'answers.answer',
            'answers.question.answers',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiáº¿t bÃ i ná»™p cá»§a thÃ nh viÃªn',
            'data' => $this->formatManagedSubmissionResult($submission),
        ]);
    }

    public function start(Request $request, Room $room, RoomAssignment $assignment)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((int) $assignment->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bài tập không thuộc room này.',
            ], 404);
        }

        if (!$this->isRoomMemberOrHost($room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thuộc room này.',
            ], 403);
        }

        if ($assignment->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Bài tập chưa được mở.',
            ], 403);
        }

        $now = now();

        if ($assignment->starts_at && $now->lt($assignment->starts_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đến thời gian làm bài.',
            ], 403);
        }

        if ($assignment->deadline_at && $now->gt($assignment->deadline_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Đã quá hạn làm bài.',
            ], 403);
        }

        $currentSubmission = RoomAssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($currentSubmission) {
            $assignment->load('quiz.questions.answers');

            return response()->json([
                'success' => true,
                'message' => 'Bạn đang có một lượt làm chưa nộp.',
                'data' => [
                    'submission' => $this->formatSubmission($currentSubmission),
                    'assignment' => $this->formatAssignmentForTaking($assignment),
                ],
            ]);
        }

        $usedAttempts = RoomAssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'late'])
            ->count();

        if ($usedAttempts >= $assignment->max_attempts) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã hết số lần làm bài.',
            ], 403);
        }

        $assignment->load('quiz.questions.answers');

        if (!$assignment->quiz || $assignment->quiz->questions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz của bài tập này chưa có câu hỏi.',
            ], 422);
        }

        $submission = RoomAssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'attempt_no' => $usedAttempts + 1,
            'status' => 'in_progress',
            'score' => 0,
            'correct_count' => 0,
            'wrong_count' => 0,
            'total_questions' => $assignment->quiz->questions->count(),
            'started_at' => now(),
            'submitted_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bắt đầu làm bài thành công.',
            'data' => [
                'submission' => $this->formatSubmission($submission),
                'assignment' => $this->formatAssignmentForTaking($assignment),
            ],
        ], 201);
    }

    public function answer(Request $request, RoomAssignmentSubmission $submission)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((int) $submission->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền trả lời lượt làm này.',
            ], 403);
        }

        if ($submission->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Lượt làm này đã kết thúc.',
            ], 422);
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'answer_id' => ['nullable', 'integer', 'exists:answers,id'],
            'selected_answer_ids' => ['nullable', 'array'],
            'selected_answer_ids.*' => ['integer', 'exists:answers,id'],
            'response_time_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $submission->load('assignment.quiz.questions.answers');

        $assignment = $submission->assignment;

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài tập của lượt làm này.',
            ], 404);
        }

        $timeCheck = $this->checkTimeLimit($submission, $assignment);

        if ($timeCheck !== true) {
            return $timeCheck;
        }

        $question = Question::where('id', $data['question_id'])
            ->where('quiz_id', $assignment->quiz_id)
            ->with('answers')
            ->first();

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Câu hỏi không thuộc quiz của bài tập này.',
            ], 422);
        }

        $selectedAnswerIds = $this->normalizeSelectedAnswerIds($data);

        if (count($selectedAnswerIds) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa chọn đáp án.',
            ], 422);
        }

        $validAnswerCount = Answer::where('question_id', $question->id)
            ->whereIn('id', $selectedAnswerIds)
            ->count();

        if ($validAnswerCount !== count($selectedAnswerIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Có đáp án không thuộc câu hỏi này.',
            ], 422);
        }

        $correctAnswerIds = $question->answers
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $selectedAnswerIds = collect($selectedAnswerIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $isCorrect = $selectedAnswerIds === $correctAnswerIds && count($correctAnswerIds) > 0;

        $points = (int) ($question->points ?? 10);

        $answer = RoomAssignmentAnswer::updateOrCreate(
            [
                'submission_id' => $submission->id,
                'question_id' => $question->id,
            ],
            [
                'answer_id' => $selectedAnswerIds[0] ?? null,
                'selected_answer_ids' => $selectedAnswerIds,
                'is_correct' => $isCorrect,
                'score' => $isCorrect ? $points : 0,
                'response_time_ms' => $data['response_time_ms'] ?? null,
                'answered_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Lưu câu trả lời thành công.',
            'data' => [
                'id' => $answer->id,
                'submission_id' => $answer->submission_id,
                'question_id' => $answer->question_id,
                'answer_id' => $answer->answer_id,
                'selected_answer_ids' => $answer->selected_answer_ids,
                'is_correct' => $answer->is_correct,
                'score' => $answer->score,
                'answered_at' => optional($answer->answered_at)->toISOString(),
            ],
        ]);
    }

    public function submit(Request $request, RoomAssignmentSubmission $submission)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((int) $submission->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền nộp lượt làm này.',
            ], 403);
        }

        if ($submission->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Lượt làm này đã được nộp hoặc đã kết thúc.',
            ], 422);
        }

        return DB::transaction(function () use ($submission) {
            $submission->load('assignment.quiz.questions.answers');

            $assignment = $submission->assignment;

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy bài tập.',
                ], 404);
            }

            $timeCheck = $this->checkTimeLimit($submission, $assignment);

            if ($timeCheck !== true) {
                return $timeCheck;
            }

            $totalQuestions = $assignment->quiz->questions->count();

            $correctCount = RoomAssignmentAnswer::where('submission_id', $submission->id)
                ->where('is_correct', true)
                ->count();

            $answeredQuestionCount = RoomAssignmentAnswer::where('submission_id', $submission->id)
                ->count();

            $wrongCount = max(0, $answeredQuestionCount - $correctCount);

            $score = RoomAssignmentAnswer::where('submission_id', $submission->id)
                ->sum('score');

            $submission->update([
                'status' => 'submitted',
                'score' => $score,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'total_questions' => $totalQuestions,
                'submitted_at' => now(),
            ]);

            $submission->load(['answers.question', 'answers.answer']);

            return response()->json([
                'success' => true,
                'message' => 'Nộp bài thành công.',
                'data' => $this->formatSubmissionResult($submission),
            ]);
        });
    }

    private function checkTimeLimit(RoomAssignmentSubmission $submission, RoomAssignment $assignment)
    {
        $now = now();

        if ($assignment->deadline_at && $now->gt($assignment->deadline_at)) {
            $submission->update([
                'status' => 'late',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Đã quá hạn làm bài.',
            ], 403);
        }

        if ($assignment->duration_minutes && $submission->started_at) {
            $endTime = $submission->started_at->copy()->addMinutes($assignment->duration_minutes);

            if ($now->gt($endTime)) {
                $submission->update([
                    'status' => 'late',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Đã hết thời gian làm bài.',
                ], 403);
            }
        }

        return true;
    }

    private function isRoomMemberOrHost(Room $room, int $userId): bool
    {
        if ((int) $room->host_id === (int) $userId) {
            return true;
        }

        return RoomMember::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    private function canManageRoomSubmissions(Room $room, $user): bool
    {
        if (!$user) {
            return false;
        }

        if (strtolower((string) $user->role) === 'admin') {
            return true;
        }

        if ((int) $room->host_id === (int) $user->id) {
            return true;
        }

        return RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereIn('role', ['owner', 'teacher'])
            ->exists();
    }

    private function normalizeSelectedAnswerIds(array $data): array
    {
        if (isset($data['selected_answer_ids']) && is_array($data['selected_answer_ids'])) {
            return collect($data['selected_answer_ids'])
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if (isset($data['answer_id'])) {
            return [(int) $data['answer_id']];
        }

        return [];
    }

    private function formatAssignmentForTaking(RoomAssignment $assignment): array
    {
        $quiz = $assignment->quiz;

        return [
            'id' => $assignment->id,
            'room_id' => $assignment->room_id,
            'quiz_id' => $assignment->quiz_id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'starts_at' => optional($assignment->starts_at)->toISOString(),
            'deadline_at' => optional($assignment->deadline_at)->toISOString(),
            'duration_minutes' => $assignment->duration_minutes,
            'max_attempts' => $assignment->max_attempts,
            'show_result_mode' => $assignment->show_result_mode,
            'status' => $assignment->status,
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'category' => $quiz->category,
                'difficulty' => $quiz->difficulty,
                'time_limit_seconds' => $quiz->time_limit_seconds,
                'questions' => $quiz->questions->map(fn (Question $question) => [
                    'id' => $question->id,
                    'content' => $question->content,
                    'text' => $question->content,
                    'type' => $question->type,
                    'points' => $question->points,
                    'answers' => $question->answers->map(fn (Answer $answer, int $index) => [
                        'id' => $answer->id,
                        'content' => $answer->content,
                        'text' => $answer->content,
                        'answer_key' => chr(65 + ($answer->order ?? $index)),
                        'key' => chr(65 + ($answer->order ?? $index)),
                        'order' => $answer->order,
                    ])->values(),
                ])->values(),
            ],
        ];
    }

    private function formatSubmission(RoomAssignmentSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'user_id' => $submission->user_id,
            'attempt_no' => $submission->attempt_no,
            'status' => $submission->status,
            'score' => $submission->score,
            'correct_count' => $submission->correct_count,
            'wrong_count' => $submission->wrong_count,
            'total_questions' => $submission->total_questions,
            'started_at' => optional($submission->started_at)->toISOString(),
            'submitted_at' => optional($submission->submitted_at)->toISOString(),
        ];
    }

    private function formatSubmissionResult(RoomAssignmentSubmission $submission): array
    {
        return [
            ...$this->formatSubmission($submission),
            'answers' => $submission->answers->map(fn (RoomAssignmentAnswer $answer) => [
                'id' => $answer->id,
                'question_id' => $answer->question_id,
                'question' => $answer->question?->content,
                'answer_id' => $answer->answer_id,
                'answer' => $answer->answer?->content,
                'selected_answer_ids' => $answer->selected_answer_ids,
                'is_correct' => $answer->is_correct,
                'score' => $answer->score,
                'answered_at' => optional($answer->answered_at)->toISOString(),
            ])->values(),
        ];
    }

    private function formatManagedSubmissionSummary(RoomAssignmentSubmission $submission): array
    {
        $totalQuestions = (int) ($submission->total_questions ?: 0);
        $scorePercent = $totalQuestions > 0
            ? round(((int) $submission->correct_count / $totalQuestions) * 100)
            : null;

        return [
            ...$this->formatSubmission($submission),
            'score_percent' => $scorePercent,
            'answers_count' => $submission->answers_count ?? null,
            'user' => [
                'id' => $submission->user?->id,
                'name' => $submission->user?->name,
                'email' => $submission->user?->email,
                'role' => $submission->user?->role,
            ],
        ];
    }

    private function formatManagedSubmissionResult(RoomAssignmentSubmission $submission): array
    {
        return [
            ...$this->formatManagedSubmissionSummary($submission),
            'answers' => $submission->answers->map(fn (RoomAssignmentAnswer $answer) => [
                'id' => $answer->id,
                'question_id' => $answer->question_id,
                'question' => $answer->question?->content,
                'answer_id' => $answer->answer_id,
                'answer' => $answer->answer?->content,
                'selected_answer_ids' => $answer->selected_answer_ids,
                'selected_answers' => $answer->question?->answers
                    ? $answer->question->answers
                        ->whereIn('id', $answer->selected_answer_ids ?? [])
                        ->map(fn (Answer $selectedAnswer) => [
                            'id' => $selectedAnswer->id,
                            'content' => $selectedAnswer->content,
                        ])
                        ->values()
                    : [],
                'correct_answers' => $answer->question?->answers
                    ? $answer->question->answers
                        ->where('is_correct', true)
                        ->map(fn (Answer $correctAnswer) => [
                            'id' => $correctAnswer->id,
                            'content' => $correctAnswer->content,
                        ])
                        ->values()
                    : [],
                'is_correct' => $answer->is_correct,
                'score' => $answer->score,
                'answered_at' => optional($answer->answered_at)->toISOString(),
            ])->values(),
        ];
    }
}
