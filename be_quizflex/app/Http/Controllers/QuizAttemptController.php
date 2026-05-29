<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuestionOrderService;
use App\Services\QuizGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly QuestionOrderService $questionOrderService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = QuizAttempt::query()
            ->with(['quiz:id,title,category,is_public,room_code,time_limit_seconds', 'user:id,name'])
            ->latest('started_at');

        if (strtolower((string) ($user->role ?? 'user')) !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->query('quiz_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $attempts = $query->paginate($perPage)->through(fn (QuizAttempt $attempt) => $this->formatAttempt($attempt));

        return response()->json([
            'success' => true,
            'message' => 'Danh sách lượt làm bài',
            'data' => $attempts,
        ]);
    }

    public function show(Request $request, QuizAttempt $quizAttempt)
    {
        $user = $request->user();
        if (strtolower((string) ($user->role ?? 'user')) !== 'admin' && (int) $quizAttempt->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem lượt làm bài này.',
            ], 403);
        }

        $quizAttempt->load(['quiz.questions.answers', 'user:id,name']);
        $data = $this->formatAttempt($quizAttempt, true);

        if ($quizAttempt->status === 'in_progress' && $quizAttempt->quiz) {
            $data['quiz_for_taking'] = $this->formatQuizForTaking($quizAttempt->quiz, $quizAttempt);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết kết quả làm bài',
            'data' => $data,
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'attempt_id' => ['nullable', 'integer', 'exists:quiz_attempts,id'],
        ]);

        $user = $request->user();

        if (!$this->canStartPractice($user, $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền làm quiz này.',
            ], 403);
        }

        $quiz->load('questions.answers');

        $attempt = $this->findReusablePracticeAttempt($quiz, $user->id, $data['attempt_id'] ?? null);

        if ($attempt) {
            $attempt = $this->questionOrderService->ensureAttemptOrder($attempt, $quiz);
        } else {
            $payload = [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => 0,
                'total_points' => $quiz->questions->sum(fn (Question $question) => (int) ($question->points ?? 0)),
                'time_spent_seconds' => null,
                'answers_snapshot' => [],
                'status' => 'in_progress',
                'started_at' => now(),
            ];

            if (Schema::hasColumn('quiz_attempts', 'question_order')) {
                $payload['question_order'] = $this->questionOrderService->makeForQuiz($quiz);
            }

            if (Schema::hasColumn('quiz_attempts', 'mode')) {
                $payload['mode'] = 'practice';
            }

            $attempt = QuizAttempt::create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bắt đầu làm bài',
            'data' => [
                'attempt' => $this->formatAttempt($attempt),
                'quiz' => $this->formatQuizForTaking($quiz, $attempt),
            ],
        ], 201);
    }

    public function submit(Request $request, Quiz $quiz, QuizGradingService $gradingService)
    {
        $data = $request->validate([
            'attempt_id' => ['required', 'integer', 'exists:quiz_attempts,id'],
            'answers' => ['required', 'array'],
        ]);

        $user = $request->user();

        $result = DB::transaction(function () use ($quiz, $data, $user, $gradingService) {
            $quiz->load('questions.answers');

            $attempt = QuizAttempt::where('quiz_id', $quiz->id)->find($data['attempt_id']);

            if (!$attempt) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy lượt làm bài cho quiz này.',
                ], 404));
            }

            if ((int) $attempt->user_id !== (int) $user->id) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền nộp lượt làm bài này.',
                ], 403));
            }

            if ($attempt->status === 'completed') {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Lượt làm bài này đã được nộp.',
                ], 422));
            }

            $this->questionOrderService->applyOrderToQuiz($quiz, $attempt->question_order ?? []);
            $graded = $gradingService->grade($quiz, $data['answers']);
            $finishedAt = now();
            $timeSpent = 0;
            if ($attempt->started_at) {
                $timeSpent = max(0, $attempt->started_at->diffInSeconds($finishedAt));
            }

            $update = [
                'score' => $graded['score'],
                'total_points' => $graded['total_points'],
                'time_spent_seconds' => $timeSpent,
                'answers_snapshot' => $graded['answers_snapshot'],
                'status' => 'completed',
                'finished_at' => $finishedAt,
            ];

            if (Schema::hasColumn('quiz_attempts', 'submitted_at')) {
                $update['submitted_at'] = $finishedAt;
            }

            $attempt->update($update);

            $attempt->load(['quiz', 'user:id,name']);

            return [
                'attempt' => $this->formatAttempt($attempt, true),
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
            'message' => 'Nộp bài và chấm điểm thành công',
            'data' => $result,
        ]);
    }

    private function canStartPractice($user, Quiz $quiz): bool
    {
        $role = strtolower((string) ($user->role ?? 'user'));
        if ($role === 'admin' || (int) $quiz->user_id === (int) $user->id) {
            return true;
        }

        return (bool) $quiz->is_public && $quiz->status === 'published';
    }

    private function findReusablePracticeAttempt(Quiz $quiz, int $userId, ?int $attemptId = null): ?QuizAttempt
    {
        if (!$attemptId) {
            return null;
        }

        $query = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->where('status', 'in_progress');

        if (Schema::hasColumn('quiz_attempts', 'mode')) {
            $query->where(function ($modeQuery) {
                $modeQuery->whereNull('mode')->orWhere('mode', 'practice');
            });
        }

        return $query->whereKey($attemptId)->first();
    }

    private function formatAttempt(QuizAttempt $attempt, bool $includeSnapshot = false): array
    {
        $scorePercent = $attempt->total_points > 0 ? round($attempt->score * 100 / $attempt->total_points, 2) : 0;
        $quiz = $attempt->quiz;

        $data = [
            'id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'quiz_id' => $attempt->quiz_id,
            'quiz_title' => $quiz?->title,
            'quiz' => $quiz ? [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'visibility' => $quiz->room_code ? 'group' : ($quiz->is_public ? 'public' : 'private'),
            ] : null,
            'user_name' => $attempt->user?->name,
            'score' => $attempt->score,
            'total_points' => $attempt->total_points,
            'score_percent' => $scorePercent,
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
            'finished_at' => $attempt->finished_at,
            'submitted_at' => $attempt->submitted_at ?? null,
            'mode' => $attempt->mode ?? 'practice',
            'question_order' => $attempt->question_order ?? [],
        ];

        if ($includeSnapshot) {
            $data['answers_snapshot'] = $attempt->answers_snapshot ?? [];
        }

        return $data;
    }

    private function formatQuizForTaking(Quiz $quiz, ?QuizAttempt $attempt = null): array
    {
        $questions = $attempt
            ? $this->questionOrderService->questionsForQuiz($quiz, $attempt->question_order ?? [])
            : $quiz->questions->values();

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'category' => $quiz->category,
            'difficulty' => $quiz->difficulty,
            'time_limit_seconds' => $quiz->time_limit_seconds ?? 600,
            'questions' => $questions->map(fn (Question $question) => [
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
        ];
    }
}
