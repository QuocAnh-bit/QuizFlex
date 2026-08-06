<?php

namespace App\Http\Controllers;

use App\Models\UserXp;
use App\Models\UserStreak;
use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\User;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GamificationController extends Controller
{
    // Lấy thống kê người dùng
    public function getUserStats(Request $request)
    {
        $user = $request->user();

        $xp = UserXp::firstOrCreate(['user_id' => $user->id], ['xp' => 0, 'level' => 1]);
        $streak = UserStreak::firstOrCreate(['user_id' => $user->id], ['current_streak' => 0, 'longest_streak' => 0]);
        $badges = UserBadge::with('badge')->where('user_id', $user->id)->get();

        return response()->json([
            'xp' => $xp->xp,
            'level' => $xp->level,
            'xp_to_next_level' => $this->xpToNextLevel($xp->level),
            'current_streak' => $streak->current_streak,
            'longest_streak' => $streak->longest_streak,
            'badges' => $badges,
        ]);
    }

    // Cộng XP sau khi làm quiz
    public function addXp(Request $request)
    {
        $request->validate(['xp' => 'required|integer|min:1']);
        $user = $request->user();

        $userXp = UserXp::firstOrCreate(['user_id' => $user->id], ['xp' => 0, 'level' => 1]);
        $userXp->xp += $request->xp;

        // Tính level mới
        $userXp->level = $this->calculateLevel($userXp->xp);
        $userXp->save();

        // Cập nhật streak
        $this->updateStreak($user->id);

        // Kiểm tra badge mới
        $newBadges = $this->checkBadges($user->id, $userXp);

        return response()->json([
            'xp' => $userXp->xp,
            'level' => $userXp->level,
            'new_badges' => $newBadges,
        ]);
    }

    // Bảng xếp hạng
    public function leaderboard()
    {
        $leaderboard = \Illuminate\Support\Facades\Cache::remember('gamification_leaderboard', 15, function () {
            return UserXp::whereHas('user')
                ->with('user:id,name')
                ->orderByDesc('xp')
                ->take(50)
                ->get()
                ->map(function ($item, $index) {
                    return [
                        'rank' => $index + 1,
                        'user_id' => $item->user_id,
                        'name' => optional($item->user)->name ?? 'Người dùng ẩn danh',
                        'xp' => $item->xp,
                        'level' => $item->level,
                    ];
                });
        });

        return response()->json($leaderboard);
    }

    // Danh sách tất cả badge
    public function badges()
    {
        $badges = \Illuminate\Support\Facades\Cache::remember('gamification_badges', 60, function () {
            return Badge::all();
        });

        return response()->json($badges);
    }

    // Helper: Tính level từ XP (mỗi level cần thêm 100 XP)
    private function calculateLevel(int $xp): int
    {
        return (int) floor($xp / 100) + 1;
    }

    private function xpToNextLevel(int $level): int
    {
        return $level * 100;
    }

    // Helper: Cập nhật streak
   private function updateStreak(int $userId): void
{
    $streak = UserStreak::firstOrCreate(
        ['user_id' => $userId],
        [
            'current_streak' => 0,
            'longest_streak' => 0,
        ]
    );

    $today = Carbon::today();

    // Chưa từng hoạt động
    if (!$streak->last_activity_date) {
        $streak->current_streak = 1;
    } else {
        $lastActivity = Carbon::parse($streak->last_activity_date);

        // Đã hoạt động hôm nay
        if ($lastActivity->isSameDay($today)) {
            return;
        }

        $diff = $lastActivity->diffInDays($today);

        if ($diff == 1) {
            // Liên tục
            $streak->current_streak++;
        } elseif ($diff >= 2) {
            // Nghỉ từ 2 ngày trở lên -> reset
            $streak->current_streak = 1;
        }
    }

    $streak->longest_streak = max(
        $streak->longest_streak,
        $streak->current_streak
    );

    $streak->last_activity_date = $today->toDateString();
    $streak->save();
}

    // Helper: Kiểm tra và trao badge
    private function checkBadges(int $userId, UserXp $userXp): array
    {
        $streak = UserStreak::where('user_id', $userId)->first();
        $earnedBadgeIds = UserBadge::where('user_id', $userId)->pluck('badge_id');
        $newBadges = [];

        Badge::whereNotIn('id', $earnedBadgeIds)->get()->each(function ($badge) use ($userId, $userXp, $streak, &$newBadges) {
            $earned = match ($badge->condition_type) {
                'xp_reached'  => $userXp->xp >= $badge->condition_value,
                'streak_days' => $streak && $streak->current_streak >= $badge->condition_value,

                // Nhà thông thái AI — tạo đủ số quiz bằng AI
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
                UserBadge::create(['user_id' => $userId, 'badge_id' => $badge->id, 'earned_at' => now()]);
                $newBadges[] = $badge;
            }
        });

        return $newBadges;
    }

    // Helper: Kiểm tra N lần gần nhất có đạt 100% không
    private function checkPerfectScoreStreak(int $userId, int $required): bool
    {
        $recent = QuizAttempt::where('user_id', $userId)
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
}
