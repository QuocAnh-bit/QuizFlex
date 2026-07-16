<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserXp;
use App\Models\UserStreak;
use App\Models\UserBadge;
use App\Models\Badge;
use App\Services\QuestionOrderService;
use App\Services\QuizGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly QuestionOrderService $questionOrderService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = QuizAttempt::query()
            ->with(['quiz:id,title,category,is_public,room_code,time_limit_seconds', 'user:id,name', 'evaluation'])
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

        $quizAttempt->load(['quiz.questions.answers', 'user:id,name', 'evaluation']);
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
            'answers' => ['present', 'array'],
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

            $scorePercent = $graded['total_points'] > 0 ? round($graded['score'] * 100 / $graded['total_points'], 2) : 0;
            $xpEarned = $this->calculateXp((int) $scorePercent, $graded['total_questions']);

            $update = [
                'score' => $graded['score'],
                'total_points' => $graded['total_points'],
                'xp_earned' => $xpEarned,
                'time_spent_seconds' => $timeSpent,
                'answers_snapshot' => $graded['answers_snapshot'],
                'status' => 'completed',
                'finished_at' => $finishedAt,
            ];

            if (Schema::hasColumn('quiz_attempts', 'submitted_at')) {
                $update['submitted_at'] = $finishedAt;
            }

            $attempt->update($update);

            // Award XP, update streak, check badges
            $newBadges = $this->awardXp($user->id, $xpEarned);

            $attempt->load(['quiz', 'user:id,name']);

            return [
                'attempt' => $this->formatAttempt($attempt, true),
                'score' => $graded['score'],
                'total_points' => $graded['total_points'],
                'score_percent' => $scorePercent,
                'correct_count' => $graded['correct_count'],
                'total_questions' => $graded['total_questions'],
                'answers_snapshot' => $graded['answers_snapshot'],
                'xp_earned' => $xpEarned,
                'new_badges' => $newBadges,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Nộp bài và chấm điểm thành công',
            'data' => $result,
        ]);
    }

    // GAMIFIED Methods (from Huy's branch)
    public function startGamified(Request $request)
    {
        $request->validate(['quiz_id' => 'required|exists:quizzes,id']);

        $attempt = QuizAttempt::create([
            'user_id'    => $request->user()->id,
            'quiz_id'    => $request->quiz_id,
            'started_at' => now(),
            'status'     => 'in_progress',
        ]);

        return response()->json($attempt);
    }

    public function submitGamified(Request $request, $id)
    {
        $request->validate([
            'answers' => 'required|array',
        ]);

        $attempt = QuizAttempt::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Tính điểm
        $correct = 0;
        $total   = count($request->answers);

        foreach ($request->answers as $questionId => $answerId) {
            $isCorrect = \App\Models\Answer::where('id', $answerId)
                ->where('question_id', $questionId)
                ->where('is_correct', true)
                ->exists();
            if ($isCorrect) $correct++;
        }

        $score = $total > 0 ? round(($correct / $total) * 100) : 0;

        // Cộng XP dựa theo điểm
        $xpEarned = $this->calculateXp($score, $total);

        // Cập nhật attempt
        $attempt->update([
            'score'       => $correct,  // lưu số câu đúng để check badge Chiến thần 100%
            'xp_earned'   => $xpEarned,
            'finished_at' => now(),
            'status'      => 'completed',
        ]);

        // Cộng XP + cập nhật streak + kiểm tra badge
        $newBadges = $this->awardXp($request->user()->id, $xpEarned);

        return response()->json([
            'score'      => $score,
            'correct'    => $correct,
            'total'      => $total,
            'xp_earned'  => $xpEarned,
            'new_badges' => $newBadges,
        ]);
    }

    public function history(Request $request)
    {
        $attempts = QuizAttempt::with('quiz:id')
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->orderByDesc('finished_at')
            ->take(20)
            ->get();

        return response()->json($attempts);
    }

    // Helper: tính XP từ điểm số
    private function calculateXp(int $score, int $total): int
    {
        $base = 10;
        if ($score >= 90) return $base + 20;
        if ($score >= 70) return $base + 10;
        if ($score >= 50) return $base + 5;
        return $base;
    }

    // Helper: cộng XP + streak + badge
    private function awardXp(int $userId, int $xp): array
    {
        // Cộng XP
        $userXp = UserXp::firstOrCreate(
            ['user_id' => $userId],
            ['xp' => 0, 'level' => 1]
        );
        $userXp->xp   += $xp;
        $userXp->level = (int) floor($userXp->xp / 100) + 1;
        $userXp->save();

        // Cập nhật streak
        $streak = UserStreak::firstOrCreate(
            ['user_id' => $userId],
            ['current_streak' => 0, 'longest_streak' => 0]
        );
        $today     = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        if ($streak->last_activity_date !== $today) {
            $streak->current_streak = $streak->last_activity_date === $yesterday
                ? $streak->current_streak + 1
                : 1;
            $streak->longest_streak     = max($streak->longest_streak, $streak->current_streak);
            $streak->last_activity_date = $today;
            $streak->save();
        }

        // Kiểm tra badge mới
        $earnedIds = UserBadge::where('user_id', $userId)->pluck('badge_id');
        $newBadges = [];

        Badge::whereNotIn('id', $earnedIds)->get()->each(function ($badge) use ($userId, $userXp, $streak, &$newBadges) {
            $earned = match ($badge->condition_type) {
                'xp_reached'   => $userXp->xp >= $badge->condition_value,
                'streak_days'  => $streak->current_streak >= $badge->condition_value,
                'quiz_completed' => QuizAttempt::where('user_id', $userId)
                    ->where('status', 'completed')
                    ->count() >= $badge->condition_value,

                // Nhà thông thái AI
                'ai_quiz_created' => DB::table('quizzes')
                    ->where('user_id', $userId)
                    ->where('is_ai_generated', true)
                    ->count() >= $badge->condition_value,

                // Cú đêm — hoàn thành quiz lúc 0h–5h sáng giờ VN
                'night_owl' => QuizAttempt::where('user_id', $userId)
                    ->whereNotNull('finished_at')
                    ->whereRaw("HOUR(CONVERT_TZ(finished_at, '+00:00', '+07:00')) < 5")
                    ->exists(),

                // Chiến thần 100% — N lần liên tiếp đạt điểm tuyệt đối
                'perfect_score_streak' => $this->checkPerfectScoreStreak($userId, $badge->condition_value),

                default => false,
            };

            if ($earned) {
                UserBadge::create([
                    'user_id'   => $userId,
                    'badge_id'  => $badge->id,
                    'earned_at' => now(),
                ]);
                $newBadges[] = $badge;
            }
        });

        return $newBadges;
    }

    // Helper: Kiểm tra N lần gần nhất có đạt 100% không
    private function checkPerfectScoreStreak(int $userId, int $required): bool
    {
        $recent = QuizAttempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->limit($required)
            ->get(['quiz_id', 'score']);

        if ($recent->count() < $required) return false;

        foreach ($recent as $attempt) {
            $total = DB::table('questions')
                ->where('quiz_id', $attempt->quiz_id)
                ->count();

            if ($total === 0 || $attempt->score < $total) return false;
        }

        return true;
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
            'evaluation_comment' => $attempt->evaluation->comment ?? null,
            'evaluation_comment_updated_at' => $attempt->evaluation ? $attempt->evaluation->updated_at->toIso8601String() : null,
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
