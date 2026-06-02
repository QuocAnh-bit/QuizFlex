<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\RoomMember;
use App\Services\QuestionOrderService;
use App\Services\QuizGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RoomAssignmentController extends Controller
{
    public function __construct(
        private readonly QuestionOrderService $questionOrderService,
        private readonly QuizGradingService $gradingService,
    ) {
    }

    public function index(Request $request, Room $room)
    {
        if (!$this->canViewRoom($request->user(), $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem bai giao trong phong nay.',
            ], 403);
        }

        $assignments = RoomAssignment::query()
            ->with(['quiz:id,title,description,time_limit_seconds', 'assigner:id,name'])
            ->withCount('attempts')
            ->where('room_id', $room->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Danh sach bai duoc giao',
            'data' => $assignments->map(fn (RoomAssignment $assignment) => $this->formatAssignment($assignment, $request->user())),
        ]);
    }

    public function store(Request $request, Room $room)
    {
        $user = $request->user();
        if (!$this->canManageRoom($user, $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Chi chu phong hoac admin moi duoc giao bai.',
            ], 403);
        }

        $data = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'show_result_mode' => ['nullable', Rule::in(['immediately', 'after_deadline', 'manual'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'closed'])],
        ]);

        $quiz = Quiz::findOrFail($data['quiz_id']);

        $assignment = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $user->id,
            'title' => $data['title'] ?? $quiz->title,
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'max_attempts' => $data['max_attempts'] ?? 1,
            'show_result_mode' => $data['show_result_mode'] ?? 'immediately',
            'status' => $data['status'] ?? 'published',
        ])->load(['quiz:id,title,description,time_limit_seconds', 'assigner:id,name', 'room']);

        return response()->json([
            'success' => true,
            'message' => 'Giao bai thanh cong',
            'data' => $this->formatAssignment($assignment, $user),
        ], 201);
    }

    public function show(Request $request, RoomAssignment $assignment)
    {
        $assignment->load(['room', 'quiz.questions.answers', 'assigner:id,name']);

        if (!$this->canViewRoom($request->user(), $assignment->room)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem bai giao nay.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chi tiet bai duoc giao',
            'data' => $this->formatAssignment($assignment, $request->user(), true),
        ]);
    }

    public function startAttempt(Request $request, RoomAssignment $assignment)
    {
        $data = $request->validate([
            'attempt_id' => ['nullable', 'integer', 'exists:quiz_attempts,id'],
        ]);

        $assignment->load(['room', 'quiz.questions.answers']);
        $user = $request->user();

        if ($this->isRoomOwner($assignment->room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Chủ room không thể làm bài trong room của mình.',
            ], 403);
        }

        if (!$this->isActiveMember($assignment->room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban chua la thanh vien cua phong nay.',
            ], 403);
        }

        $availabilityError = $this->assignmentAvailabilityError($assignment);
        if ($availabilityError) {
            return response()->json(['success' => false, 'message' => $availabilityError], 422);
        }

        $attempt = $this->findReusableHomeworkAttempt($assignment, $user->id, $data['attempt_id'] ?? null);

        if ($attempt) {
            $attempt = $this->questionOrderService->ensureAttemptOrder($attempt, $assignment->quiz);
        } else {
            $attemptCount = QuizAttempt::where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->where('mode', 'homework')
                ->count();

            if ($attemptCount >= (int) $assignment->max_attempts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ban da het so lan lam bai nay.',
                ], 422);
            }

            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $assignment->quiz_id,
                'room_id' => $assignment->room_id,
                'assignment_id' => $assignment->id,
                'mode' => 'homework',
                'attempt_number' => $attemptCount + 1,
                'score' => 0,
                'total_points' => $assignment->quiz->questions->sum(fn (Question $question) => (int) ($question->points ?? 0)),
                'time_spent_seconds' => null,
                'answers_snapshot' => [],
                'question_order' => $this->questionOrderService->makeForQuiz($assignment->quiz),
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bat dau lam bai duoc giao',
            'data' => [
                'attempt' => $this->formatAttempt($attempt),
                'assignment' => $this->formatAssignment($assignment, $user),
                'quiz' => $this->formatQuizForTaking($assignment->quiz, $attempt),
            ],
        ], 201);
    }

    public function answer(Request $request, RoomAssignment $assignment, QuizAttempt $attempt)
    {
        $this->authorizeHomeworkAttempt($request, $assignment, $attempt);

        $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'answer' => ['nullable'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Da ghi nhan cau tra loi tam tren client.',
            'data' => $this->formatAttempt($attempt),
        ]);
    }

    public function submitAttempt(Request $request, RoomAssignment $assignment, QuizAttempt $attempt)
    {
        $data = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $this->authorizeHomeworkAttempt($request, $assignment, $attempt);

        if ($attempt->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Luot lam bai nay da duoc nop.',
            ], 422);
        }

        $assignment->load(['room', 'quiz.questions.answers']);
        $finishedAt = now();
        $expired = $this->isAttemptExpired($assignment, $attempt, $finishedAt);

        $result = DB::transaction(function () use ($assignment, $attempt, $data, $finishedAt, $expired) {
            $quiz = $this->questionOrderService->applyOrderToQuiz($assignment->quiz, $attempt->question_order ?? []);
            $graded = $this->gradingService->grade($quiz, $data['answers']);

            $timeSpent = $attempt->started_at ? max(0, $attempt->started_at->diffInSeconds($finishedAt)) : 0;
            if ($assignment->duration_minutes) {
                $timeSpent = min($timeSpent, (int) $assignment->duration_minutes * 60);
            }

            $attempt->update([
                'score' => $graded['score'],
                'total_points' => $graded['total_points'],
                'time_spent_seconds' => $timeSpent,
                'answers_snapshot' => $graded['answers_snapshot'],
                'status' => $expired ? 'expired' : 'completed',
                'finished_at' => $finishedAt,
                'submitted_at' => $finishedAt,
            ]);

            return [
                'attempt' => $this->formatAttempt($attempt->fresh(['quiz', 'room', 'assignment'])),
                'score' => $graded['score'],
                'total_points' => $graded['total_points'],
                'score_percent' => $graded['total_points'] > 0 ? round($graded['score'] * 100 / $graded['total_points'], 2) : 0,
                'correct_count' => $graded['correct_count'],
                'total_questions' => $graded['total_questions'],
                'answers_snapshot' => $graded['answers_snapshot'],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $expired ? 'Bai nop da qua han.' : 'Nop bai thanh cong',
            'data' => $result,
        ]);
    }

    public function attempts(Request $request, RoomAssignment $assignment)
    {
        $assignment->load('room');
        $user = $request->user();

        if (!$this->canViewRoom($user, $assignment->room)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem ket qua bai giao nay.',
            ], 403);
        }

        $query = QuizAttempt::query()
            ->with(['user:id,name,email', 'quiz:id,title'])
            ->where('assignment_id', $assignment->id)
            ->latest('started_at');

        if (!$this->canManageRoom($user, $assignment->room)) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('user_id', '!=', $assignment->room->owner_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Danh sach luot lam bai duoc giao',
            'data' => $query->get()->map(fn (QuizAttempt $attempt) => $this->formatAttemptSummary($attempt)),
        ]);
    }

    private function authorizeHomeworkAttempt(Request $request, RoomAssignment $assignment, QuizAttempt $attempt): void
    {
        $assignment->loadMissing(['room']);
        $user = $request->user();

        abort_if((int) $attempt->assignment_id !== (int) $assignment->id, 404, 'Khong tim thay luot lam bai.');
        abort_if($this->isRoomOwner($assignment->room, $user->id), 403, 'Chủ room không thể làm bài trong room của mình.');
        abort_if((int) $attempt->user_id !== (int) $user->id, 403, 'Ban khong co quyen thao tac luot lam bai nay.');
        abort_if(!$this->isActiveMember($assignment->room, $user->id), 403, 'Ban chua la thanh vien cua phong nay.');
    }

    private function assignmentAvailabilityError(RoomAssignment $assignment): ?string
    {
        if ($assignment->status !== 'published') {
            return 'Bai giao chua duoc mo.';
        }

        if ($assignment->starts_at && now()->lt($assignment->starts_at)) {
            return 'Bai giao chua den thoi gian bat dau.';
        }

        if ($assignment->deadline_at && now()->gt($assignment->deadline_at)) {
            return 'Bai giao da het han.';
        }

        return null;
    }

    private function isAttemptExpired(RoomAssignment $assignment, QuizAttempt $attempt, $finishedAt): bool
    {
        if ($assignment->deadline_at && $finishedAt->gt($assignment->deadline_at)) {
            return true;
        }

        if ($assignment->duration_minutes && $attempt->started_at) {
            return $finishedAt->gt($attempt->started_at->copy()->addMinutes((int) $assignment->duration_minutes));
        }

        return false;
    }

    private function findReusableHomeworkAttempt(RoomAssignment $assignment, int $userId, ?int $attemptId = null): ?QuizAttempt
    {
        $query = QuizAttempt::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $userId)
            ->where('mode', 'homework')
            ->where('status', 'in_progress');

        if ($attemptId) {
            return $query->whereKey($attemptId)->first();
        }

        return $query->latest('started_at')->first();
    }

    private function canViewRoom($user, Room $room): bool
    {
        return $this->canManageRoom($user, $room) || $this->isActiveMember($room, $user->id);
    }

    private function canManageRoom($user, Room $room): bool
    {
        return strtolower((string) ($user->role ?? 'user')) === 'admin'
            || (int) $room->owner_id === (int) $user->id;
    }

    private function isActiveMember(Room $room, int $userId): bool
    {
        if ($this->isRoomOwner($room, $userId)) {
            return false;
        }

        return RoomMember::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->where('role', 'member')
            ->where('status', 'active')
            ->exists();
    }

    private function isRoomOwner(Room $room, int $userId): bool
    {
        return (int) $room->owner_id === (int) $userId;
    }

    private function canViewResult(RoomAssignment $assignment, QuizAttempt $attempt, $user): bool
    {
        if ($this->canManageRoom($user, $assignment->room)) {
            return true;
        }

        if ((int) $attempt->user_id !== (int) $user->id) {
            return false;
        }

        if (!in_array($attempt->status, ['completed', 'expired'], true)) {
            return false;
        }

        return match ($assignment->show_result_mode) {
            'immediately' => true,
            'after_deadline' => $assignment->deadline_at && now()->gte($assignment->deadline_at),
            default => false,
        };
    }

    private function formatAssignment(RoomAssignment $assignment, $user, bool $includeQuiz = false): array
    {
        $assignment->loadMissing(['room', 'quiz:id,title,description,time_limit_seconds', 'assigner:id,name']);

        $data = [
            'id' => $assignment->id,
            'room_id' => $assignment->room_id,
            'quiz_id' => $assignment->quiz_id,
            'assigned_by' => $assignment->assigned_by,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'starts_at' => $assignment->starts_at,
            'deadline_at' => $assignment->deadline_at,
            'duration_minutes' => $assignment->duration_minutes,
            'max_attempts' => $assignment->max_attempts,
            'show_result_mode' => $assignment->show_result_mode,
            'status' => $assignment->status,
            'quiz' => $assignment->quiz,
            'assigner' => $assignment->assigner,
            'attempts_count' => $assignment->attempts_count ?? null,
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at,
        ];

        $myAttempts = $this->isRoomOwner($assignment->room, $user->id)
            ? collect()
            : QuizAttempt::where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->latest('started_at')
                ->get();

        $data['my_attempts_count'] = $myAttempts->count();
        $data['my_latest_attempt'] = $myAttempts->first() ? $this->formatAttempt($myAttempts->first()) : null;

        if ($includeQuiz) {
            $data['quiz_full'] = $assignment->quiz;
        }

        return $data;
    }

    private function formatAttempt(QuizAttempt $attempt, bool $includeSnapshot = false, ?RoomAssignment $assignment = null, $user = null): array
    {
        $data = [
            'id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'quiz_id' => $attempt->quiz_id,
            'room_id' => $attempt->room_id,
            'assignment_id' => $attempt->assignment_id,
            'mode' => $attempt->mode,
            'attempt_number' => $attempt->attempt_number,
            'score' => $attempt->score,
            'total_points' => $attempt->total_points,
            'score_percent' => $attempt->total_points > 0 ? round($attempt->score * 100 / $attempt->total_points, 2) : 0,
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
            'finished_at' => $attempt->finished_at,
            'submitted_at' => $attempt->submitted_at,
            'question_order' => $attempt->question_order ?? [],
            'user' => $attempt->user ?? null,
            'quiz' => $attempt->quiz ?? null,
        ];

        if ($includeSnapshot && $assignment && $user && $this->canViewResult($assignment, $attempt, $user)) {
            $data['answers_snapshot'] = $attempt->answers_snapshot ?? [];
        }

        return $data;
    }

    private function formatAttemptSummary(QuizAttempt $attempt): array
    {
        $snapshot = collect($attempt->answers_snapshot ?? []);
        $questionOrder = is_array($attempt->question_order) ? $attempt->question_order : [];
        $totalQuestions = $snapshot->count() ?: count($questionOrder);

        return [
            'id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'quiz_id' => $attempt->quiz_id,
            'room_id' => $attempt->room_id,
            'assignment_id' => $attempt->assignment_id,
            'mode' => $attempt->mode,
            'attempt_number' => $attempt->attempt_number,
            'score' => $attempt->score,
            'total_points' => $attempt->total_points,
            'score_percent' => $attempt->total_points > 0 ? round($attempt->score * 100 / $attempt->total_points, 2) : 0,
            'correct_count' => $snapshot->where('is_correct', true)->count(),
            'total_questions' => $totalQuestions,
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
            'finished_at' => $attempt->finished_at,
            'submitted_at' => $attempt->submitted_at,
            'user' => $attempt->user ?? null,
            'quiz' => $attempt->quiz ?? null,
        ];
    }

    private function formatQuizForTaking(Quiz $quiz, QuizAttempt $attempt): array
    {
        $questions = $this->questionOrderService->questionsForQuiz($quiz, $attempt->question_order ?? []);

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'time_limit_seconds' => $quiz->time_limit_seconds,
            'questions' => $questions->map(fn (Question $question) => [
                'id' => $question->id,
                'content' => $question->content,
                'text' => $question->content,
                'type' => $question->type,
                'points' => $question->points,
                'answers' => $question->answers->map(fn ($answer, int $index) => [
                    'id' => $answer->id,
                    'content' => $answer->content,
                    'text' => $answer->content,
                    'answer_key' => chr(65 + ($answer->order ?? $index)),
                    'key' => chr(65 + ($answer->order ?? $index)),
                    'order' => $answer->order,
                ])->values(),
            ])->values(),
        ];
    }
}
