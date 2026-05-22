<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\UserXp;
use App\Models\UserStreak;
use App\Models\UserBadge;
use App\Models\Badge;
use Illuminate\Http\Request;
use Carbon\Carbon;

class QuizAttemptController extends Controller
{
    // Bắt đầu làm quiz
    public function start(Request $request)
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

    // Nộp bài
    public function submit(Request $request, $id)
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
            'score'        => $score,
            'xp_earned'    => $xpEarned,
            'finished_at'  => now(),
            'status'       => 'completed',
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

    // Lịch sử làm quiz
    public function history(Request $request)
    {
        $attempts = QuizAttempt::with('quiz:id,title')
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
        $userXp->xp    += $xp;
        $userXp->level  = (int) floor($userXp->xp / 100) + 1;
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
            $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);
            $streak->last_activity_date = $today;
            $streak->save();
        }

        // Kiểm tra badge mới
        $earnedIds = UserBadge::where('user_id', $userId)->pluck('badge_id');
        $newBadges = [];

        Badge::whereNotIn('id', $earnedIds)->get()->each(function ($badge) use ($userId, $userXp, $streak, &$newBadges) {
            $earned = match ($badge->condition_type) {
                'xp_reached'     => $userXp->xp >= $badge->condition_value,
                'streak_days'    => $streak->current_streak >= $badge->condition_value,
                'quiz_completed' => true,
                default          => false,
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
}
