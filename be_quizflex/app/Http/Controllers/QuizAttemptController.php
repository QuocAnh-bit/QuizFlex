<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizAttemptController extends Controller
{
    public function index(Request $request)
    {
        $query = QuizAttempt::query()
            ->with(['quiz:id,title,category,is_public,time_limit_seconds', 'user:id,name'])
            ->latest('started_at');

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

    public function show(QuizAttempt $quizAttempt)
    {
        $quizAttempt->load(['quiz.questions.answers', 'user:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết kết quả làm bài',
            'data' => $this->formatAttempt($quizAttempt, true),
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'player_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $this->resolveUser($request, $request->input('player_name'));

        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => 0,
            'total_points' => $quiz->questions()->sum('points'),
            'time_spent_seconds' => null,
            'answers_snapshot' => [],
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $quiz->load('questions.answers');

        return response()->json([
            'success' => true,
            'message' => 'Bắt đầu làm bài',
            'data' => [
                'attempt' => $this->formatAttempt($attempt),
                'quiz' => $this->formatQuizForTaking($quiz),
            ],
        ], 201);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'attempt_id' => ['nullable', 'integer', 'exists:quiz_attempts,id'],
            'answers' => ['required', 'array'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
            'player_name' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = $this->resolveUser($request, $data['player_name'] ?? null);

        $result = DB::transaction(function () use ($quiz, $data, $user) {
            $quiz->load('questions.answers');

            $attempt = null;
            if (!empty($data['attempt_id'])) {
                $attempt = QuizAttempt::where('quiz_id', $quiz->id)->find($data['attempt_id']);
            }

            if (!$attempt) {
                $attempt = QuizAttempt::create([
                    'user_id' => $user->id,
                    'quiz_id' => $quiz->id,
                    'score' => 0,
                    'total_points' => 0,
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);
            }

            $snapshot = [];
            $score = 0;
            $totalPoints = 0;
            $correctCount = 0;

            foreach ($quiz->questions as $question) {
                $points = (int) ($question->points ?? 10);
                $totalPoints += $points;

                $selectedRaw = $data['answers'][$question->id] ?? $data['answers'][(string) $question->id] ?? [];
                $selectedIds = $this->resolveSelectedAnswerIds($question, $selectedRaw);
                $correctIds = $question->answers->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

                $isCorrect = $selectedIds === $correctIds && count($correctIds) > 0;
                if ($isCorrect) {
                    $score += $points;
                    $correctCount++;
                }

                $snapshot[] = [
                    'question_id' => $question->id,
                    'question' => $question->content,
                    'selected_answer_ids' => $selectedIds,
                    'selected_answer_keys' => $this->answerKeysFromIds($question, $selectedIds),
                    'correct_answer_ids' => $correctIds,
                    'correct_answer_keys' => $this->answerKeysFromIds($question, $correctIds),
                    'is_correct' => $isCorrect,
                    'points' => $points,
                    'earned_points' => $isCorrect ? $points : 0,
                ];
            }

            $timeSpent = $data['time_spent_seconds'] ?? null;
            if ($timeSpent === null && $attempt->started_at) {
                $timeSpent = max(0, $attempt->started_at->diffInSeconds(now()));
            }

            $attempt->update([
                'user_id' => $user->id,
                'score' => $score,
                'total_points' => $totalPoints,
                'time_spent_seconds' => $timeSpent,
                'answers_snapshot' => $snapshot,
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            $attempt->load(['quiz', 'user:id,name']);

            return [
                'attempt' => $this->formatAttempt($attempt, true),
                'score' => $score,
                'total_points' => $totalPoints,
                'score_percent' => $totalPoints > 0 ? round($score * 100 / $totalPoints, 2) : 0,
                'correct_count' => $correctCount,
                'total_questions' => $quiz->questions->count(),
                'answers_snapshot' => $snapshot,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Nộp bài và chấm điểm thành công',
            'data' => $result,
        ]);
    }

    private function resolveSelectedAnswerIds(Question $question, mixed $raw): array
    {
        $values = is_array($raw) ? $raw : [$raw];
        $answerIds = [];

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                $answerIds[] = (int) $value;
                continue;
            }

            $key = strtoupper((string) $value);
            $answer = $question->answers->first(function (Answer $answer, int $index) use ($key) {
                return chr(65 + ($answer->order ?? $index)) === $key;
            });

            if ($answer) {
                $answerIds[] = (int) $answer->id;
            }
        }

        return collect($answerIds)->unique()->sort()->values()->all();
    }

    private function answerKeysFromIds(Question $question, array $ids): array
    {
        return $question->answers
            ->filter(fn (Answer $answer) => in_array((int) $answer->id, $ids, true))
            ->map(fn (Answer $answer, int $index) => chr(65 + ($answer->order ?? $index)))
            ->values()
            ->all();
    }

    private function resolveUser(Request $request, ?string $playerName = null): User
    {
        if ($request->user()) {
            return $request->user();
        }

        if ($request->filled('user_id')) {
            $user = User::find((int) $request->input('user_id'));
            if ($user) {
                return $user;
            }
        }

        $user = User::firstOrCreate(
            ['email' => 'guest@quizflex.local'],
            ['name' => $playerName ?: 'Guest User', 'password' => bcrypt('password')]
        );

        if ($playerName && $user->name !== $playerName) {
            $user->update(['name' => $playerName]);
        }

        return $user;
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
                'visibility' => $quiz->is_public ? 'public' : 'private',
            ] : null,
            'user_name' => $attempt->user?->name,
            'score' => $attempt->score,
            'total_points' => $attempt->total_points,
            'score_percent' => $scorePercent,
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
            'finished_at' => $attempt->finished_at,
        ];

        if ($includeSnapshot) {
            $data['answers_snapshot'] = $attempt->answers_snapshot ?? [];
        }

        return $data;
    }

    private function formatQuizForTaking(Quiz $quiz): array
    {
        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'category' => $quiz->category,
            'difficulty' => $quiz->difficulty,
            'time_limit_seconds' => $quiz->time_limit_seconds ?? 600,
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
        ];
    }
}
