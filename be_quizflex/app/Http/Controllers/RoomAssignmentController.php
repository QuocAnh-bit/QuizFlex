<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\RoomMember;
use App\Models\User;
use App\Notifications\HomeworkAssigned;
use App\Notifications\HomeworkSubmitted;
use App\Notifications\HomeworkAttemptReset;
use App\Services\QuestionOrderService;
use App\Services\QuizGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class RoomAssignmentController extends Controller
{
    public function __construct(
        private readonly QuestionOrderService $questionOrderService,
        private readonly QuizGradingService $gradingService,
    ) {}

    public function index(Request $request, Room $room)
    {
        Gate::forUser($request->user())->authorize('view', $room);

        $assignments = RoomAssignment::query()
            ->with(['quiz:id,title,description,time_limit_seconds', 'assigner:id,name'])
            ->withCount('attempts')
            ->where('room_id', $room->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Danh sách bài tập.',
            'data' => $assignments->map(fn(RoomAssignment $assignment) => $this->formatAssignment($assignment, $request->user())),
        ]);
    }

    public function store(Request $request, Room $room)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $user = $request->user();
        Gate::forUser($user)->authorize('assignHomework', $room);

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
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_answers' => ['nullable', 'boolean'],
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
            'shuffle_questions' => $data['shuffle_questions'] ?? false,
            'shuffle_answers' => $data['shuffle_answers'] ?? false,
        ])->load(['quiz:id,title,description,time_limit_seconds', 'assigner:id,name', 'room']);

        if ($assignment->status === 'published') {
            $activeMembers = RoomMember::with('user')
                ->where('room_id', $room->id)
                ->where('status', 'active')
                ->where('user_id', '!=', $room->host_id)
                ->get();

            $users = $activeMembers->map(fn($m) => $m->user)->filter();
            if ($users->isNotEmpty()) {
                Notification::send($users, new HomeworkAssigned($room, $assignment));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Giao bài thành công.',
            'data' => $this->formatAssignment($assignment, $user),
        ], 201);
    }

    public function show(Request $request, RoomAssignment $assignment)
    {
        $assignment->load(['room', 'quiz.questions.answers', 'assigner:id,name']);

        Gate::forUser($request->user())->authorize('view', $assignment->room);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết bài tập.',
            'data' => $this->formatAssignment($assignment, $request->user(), true),
        ]);
    }

    public function startAttempt(Request $request, RoomAssignment $assignment)
    {
        $assignment->load(['room']);
        if ($assignment->room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $data = $request->validate([
            'attempt_id' => ['nullable', 'integer', 'exists:quiz_attempts,id'],
        ]);

        $assignment->load(['quiz.questions.answers']);
        $user = $request->user();

        $takeAuthorization = Gate::forUser($user)->inspect('takeAssignment', $assignment->room);
        if ($takeAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $takeAuthorization->message(),
            ], 403);
        }

        $availabilityError = $this->assignmentAvailabilityError($assignment);
        if ($availabilityError) {
            return response()->json(['success' => false, 'message' => $availabilityError], 422);
        }

        $attempt = $this->findReusableHomeworkAttempt($assignment, $user->id, $data['attempt_id'] ?? null);

        if ($attempt) {
            $attempt = $this->questionOrderService->ensureAttemptOrder(
                $attempt,
                $assignment->quiz,
                (bool) $assignment->shuffle_questions
            );
        } else {
            $attemptCount = QuizAttempt::where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->where('mode', 'homework')
                ->count();

            if ($attemptCount >= (int) $assignment->max_attempts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã hết số lần làm bài này.',
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
                'total_points' => $assignment->quiz->questions->sum(fn(Question $question) => (int) ($question->points ?? 0)),
                'time_spent_seconds' => null,
                'answers_snapshot' => [],
                'question_order' => $this->questionOrderService->makeForQuiz(
                    $assignment->quiz,
                    (bool) $assignment->shuffle_questions
                ),
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bắt đầu làm bài.',
            'data' => [
                'attempt' => $this->formatAttempt($attempt),
                'assignment' => $this->formatAssignment($assignment, $user),
                'quiz' => $this->formatQuizForTaking($assignment->quiz, $attempt, (bool) $assignment->shuffle_answers),
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
            'message' => 'Đã ghi nhận câu trả lời tạm từ phía người chơi.',
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
                'message' => 'Lượt làm bài này đã được nộp.',
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

        $host = User::find($assignment->room->host_id);
        $student = $request->user();
        if ($host) {
            $host->notify(new HomeworkSubmitted($assignment->room, $assignment, $attempt, $student));
        }

        return response()->json([
            'success' => true,
            'message' => $expired ? 'Bài nộp đã quá hạn.' : 'Nộp bài thành công',
            'data' => $result,
        ]);
    }

    public function attempts(Request $request, RoomAssignment $assignment)
    {
        $assignment->load('room');
        $user = $request->user();

        Gate::forUser($user)->authorize('view', $assignment->room);

        $query = QuizAttempt::query()
            ->with(['user:id,name,email', 'quiz:id,title', 'evaluation'])
            ->where('assignment_id', $assignment->id)
            ->latest('started_at');

        if (Gate::forUser($user)->allows('viewAssignmentAttempts', $assignment->room)) {
            $query->where('user_id', '!=', $assignment->room->host_id);
        } else {
            $query->where('user_id', $user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Danh sách lượt làm bài được giao',
            'data' => $query->get()->map(fn(QuizAttempt $attempt) => $this->formatAttemptSummary($attempt)),
        ]);
    }

    public function resetAttempt(Request $request, RoomAssignment $assignment, QuizAttempt $attempt)
    {
        $assignment->loadMissing('room');
        if ($assignment->room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $user = $request->user();
        Gate::forUser($user)->authorize('resetAttempt', $assignment->room);

        if ((int) $attempt->assignment_id !== (int) $assignment->id) {
            return response()->json([
                'success' => false,
                'message' => 'Lượt làm bài không thuộc bài tập này.',
            ], 422);
        }

        $recipient = User::find($attempt->user_id);
        $attemptId = $attempt->id;

        DB::transaction(function () use ($attempt) {
            $attempt->evaluation()->delete();
            $attempt->delete();
        });

        if ($recipient) {
            $recipient->notify(new HomeworkAttemptReset($assignment->room, $assignment, $attemptId));
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã reset lượt làm bài thành công. Học viên có thể làm lại từ đầu.',
        ]);
    }

    private function authorizeHomeworkAttempt(Request $request, RoomAssignment $assignment, QuizAttempt $attempt): void
    {
        $assignment->loadMissing(['room']);
        abort_if($assignment->room->status === 'banned', 403, 'Phòng học này đã bị khóa bởi quản trị viên.');

        $user = $request->user();

        abort_if((int) $attempt->assignment_id !== (int) $assignment->id, 404, 'Không tìm thấy lượt làm bài.');

        $attemptAuthorization = Gate::forUser($user)->inspect('useHomeworkAttempt', [$assignment->room, $attempt]);
        abort_if($attemptAuthorization->denied(), 403, $attemptAuthorization->message());
    }

    private function assignmentAvailabilityError(RoomAssignment $assignment): ?string
    {
        if ($assignment->status !== 'published') {
            return 'Bài giao chưa được mở.';
        }

        if ($assignment->starts_at && now()->lt($assignment->starts_at)) {
            return 'Bài giao chưa đến thời gian bắt đầu.';
        }

        if ($assignment->deadline_at && now()->gt($assignment->deadline_at)) {
            return 'Bài giao đã hết hạn.';
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

    private function canViewResult(RoomAssignment $assignment, QuizAttempt $attempt, $user): bool
    {
        return Gate::forUser($user)->allows('viewAssignmentAttemptResult', [$assignment->room, $assignment, $attempt]);
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
            'shuffle_questions' => $assignment->shuffle_questions,
            'shuffle_answers' => $assignment->shuffle_answers,
        ];

        $myAttempts = Gate::forUser($user)->denies('takeAssignment', $assignment->room)
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
            'evaluation' => $attempt->evaluation ? [
                'id' => $attempt->evaluation->id,
                'comment' => $attempt->evaluation->comment,
                'created_at' => $attempt->evaluation->created_at,
                'updated_at' => $attempt->evaluation->updated_at,
            ] : null,
        ];
    }

    private function formatQuizForTaking(Quiz $quiz, QuizAttempt $attempt,bool $shuffleAnswers = false): array
    {
        $questions = $this->questionOrderService->questionsForQuiz($quiz, $attempt->question_order ?? []);

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'time_limit_seconds' => $quiz->time_limit_seconds,
            // 'questions' => $questions->map(fn(Question $question) => [
            //     'id' => $question->id,
            //     'content' => $question->content,
            //     'text' => $question->content,
            //     'type' => $question->type,
            //     'points' => $question->points,
            //     'answers' => $question->answers->map(fn($answer, int $index) => [
            //         'id' => $answer->id,
            //         'content' => $answer->content,
            //         'text' => $answer->content,
            //         'answer_key' => chr(65 + ($answer->order ?? $index)),
            //         'key' => chr(65 + ($answer->order ?? $index)),
            //         'order' => $answer->order,
            //     ])->values(),
            // ])->values(),
            'questions' => $questions->map(function (Question $question) use ($shuffleAnswers) {

    $answers = $question->answers->values();

    if ($shuffleAnswers) {
        $answers = $answers->shuffle()->values();
    }

    return [
        'id' => $question->id,
        'content' => $question->content,
        'text' => $question->content,
        'type' => $question->type,
        'points' => $question->points,

        'answers' => $answers->map(function ($answer, int $index) {

            return [
                'id' => $answer->id,
                'content' => $answer->content,
                'text' => $answer->content,

                // Gán lại A, B, C, D theo vị trí sau khi trộn
                'answer_key' => chr(65 + $index),
                'key' => chr(65 + $index),

                'order' => $index,
            ];

        })->values(),
    ];

})->values(),
        ];
    }

    public function gradebook(Request $request, Room $room)
    {
        $currentUser = $request->user();
        Gate::forUser($currentUser)->authorize('viewGradebook', $room);

        // Lấy danh sách Assignments theo starts_at hoặc created_at asc
        $assignments = RoomAssignment::where('room_id', $room->id)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'title']);

        // Lấy danh sách học sinh (active members, exclude host)
        $members = RoomMember::with('user:id,name,email')
            ->where('room_id', $room->id)
            ->where('status', 'active')
            ->where('user_id', '!=', $room->host_id)
            ->get();

        // Lấy toàn bộ attempts đã nộp/hoàn thành của các assignments thuộc room
        $assignmentIds = $assignments->pluck('id')->all();
        $attempts = QuizAttempt::whereIn('assignment_id', $assignmentIds)
            ->whereIn('status', ['completed', 'expired'])
            ->get(['user_id', 'assignment_id', 'score', 'total_points']);

        $attemptsGrouped = $attempts->groupBy('user_id');

        $studentsData = [];

        foreach ($members as $member) {
            $user = $member->user;
            if (!$user) {
                continue;
            }

            $scores = [];
            $totalScore10Sum = 0;
            $attemptedAssignmentsCount = 0;

            $userAttempts = $attemptsGrouped->get($user->id, collect());

            foreach ($assignments as $assignment) {
                $assignmentAttempts = $userAttempts->where('assignment_id', $assignment->id);

                if ($assignmentAttempts->isNotEmpty()) {
                    // Tìm attempt có score cao nhất
                    $bestAttempt = $assignmentAttempts->sortByDesc('score')->first();

                    $scores[$assignment->id] = [
                        'score' => (int) $bestAttempt->score,
                        'total_points' => (int) $bestAttempt->total_points,
                    ];

                    if ($bestAttempt->total_points > 0) {
                        $score10 = ($bestAttempt->score / $bestAttempt->total_points) * 10;
                        $totalScore10Sum += $score10;
                        $attemptedAssignmentsCount++;
                    }
                } else {
                    $scores[$assignment->id] = null;
                }
            }

            $totalAssignmentsCount = count($assignments);

            $averageScore10 = $attemptedAssignmentsCount > 0
                ? round($totalScore10Sum / $attemptedAssignmentsCount, 1)
                : null;

            $averageScore10All = $totalAssignmentsCount > 0
                ? round($totalScore10Sum / $totalAssignmentsCount, 1)
                : ($attemptedAssignmentsCount > 0 ? 0.0 : null);

            $studentsData[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'scores' => $scores,
                'average_score10' => $averageScore10,
                'average_score10_all' => $averageScore10All,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assignments' => $assignments,
                'students' => $studentsData,
            ],
        ]);
    }
}
