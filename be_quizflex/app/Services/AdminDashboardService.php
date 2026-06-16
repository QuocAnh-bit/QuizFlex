<?php

namespace App\Services;

use App\Models\LiveRoom;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
{
    public function overview(): array
    {
        return [
            'system' => $this->systemOverview(),
            'revenue' => $this->revenueStats(),
            'quiz' => $this->quizStats(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    private function systemOverview(): array
    {
        $homeworkRooms = Room::count();
        $liveRooms = LiveRoom::count();

        return [
            'total_users' => User::count(),
            'total_quizzes' => Quiz::count(),
            'total_rooms' => $homeworkRooms + $liveRooms,
            'total_homework_rooms' => $homeworkRooms,
            'total_live_rooms' => $liveRooms,
            'total_attempts' => QuizAttempt::count(),
            'total_questions' => Question::count(),
            'total_vip_users' => $this->vipUsersQuery()->count(),
            'total_transactions' => Payment::count(),
            'total_revenue' => (float) Payment::where('status', 'success')->sum('amount'),
        ];
    }

    private function revenueStats(): array
    {
        $successfulPayments = Payment::where('status', 'success');

        return [
            'total_revenue' => (float) (clone $successfulPayments)->sum('amount'),
            'successful_transactions' => Payment::where('status', 'success')->count(),
            'failed_transactions' => Payment::where('status', 'failed')->count(),
            'pending_transactions' => Payment::where('status', 'pending')->count(),
            'paid_users_count' => (clone $successfulPayments)->distinct('user_id')->count('user_id'),
            'revenue_by_day' => $this->revenueByDay(),
            'revenue_by_month' => $this->revenueByMonth(),
            'revenue_by_year' => $this->revenueByYear(),
            'top_paying_user' => $this->payingUser(order: 'desc'),
            'lowest_paying_user' => $this->payingUser(order: 'asc'),
            'revenue_by_user' => $this->revenueByUser(),
        ];
    }

    private function quizStats(): array
    {
        return [
            'top_quiz_creator' => $this->quizCreator(order: 'desc'),
            'lowest_quiz_creator' => $this->quizCreator(order: 'asc'),
            'most_attempted_quiz' => $this->mostAttemptedQuiz(),
            'highest_average_score_quiz' => $this->averageScoreQuiz(order: 'desc'),
            'lowest_average_score_quiz' => $this->averageScoreQuiz(order: 'asc'),
            'public_quizzes' => Quiz::where('is_public', true)->count(),
            'private_quizzes' => Quiz::where('is_public', false)->count(),
            'ai_generated_quizzes' => $this->aiGeneratedQuizCount(),
            'total_questions' => Question::count(),
            'quiz_visibility' => [
                'public' => Quiz::where('is_public', true)->count(),
                'private' => Quiz::where('is_public', false)->count(),
                'ai_generated' => $this->aiGeneratedQuizCount(),
            ],
        ];
    }

    private function vipUsersQuery()
    {
        return User::query()
            ->whereRaw('LOWER(role) = ?', ['vip'])
            ->orWhere(function ($query) {
                $query->whereNotNull('vip_expires_at')
                    ->where('vip_expires_at', '>=', now());
            });
    }

    private function revenueByDay(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        return Payment::query()
            ->selectRaw('DATE(COALESCE(paid_at, created_at)) as period')
            ->selectRaw('SUM(amount) as revenue')
            ->selectRaw('COUNT(*) as transactions')
            ->where('status', 'success')
            ->where('created_at', '>=', $start)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'revenue' => (float) $row->revenue,
                'transactions' => (int) $row->transactions,
            ])
            ->all();
    }

    private function revenueByMonth(): array
    {
        $start = Carbon::now()->subMonths(11)->startOfMonth();

        return Payment::query()
            ->selectRaw("DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') as period")
            ->selectRaw('SUM(amount) as revenue')
            ->selectRaw('COUNT(*) as transactions')
            ->where('status', 'success')
            ->where('created_at', '>=', $start)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'revenue' => (float) $row->revenue,
                'transactions' => (int) $row->transactions,
            ])
            ->all();
    }

    private function revenueByYear(): array
    {
        return Payment::query()
            ->selectRaw('YEAR(COALESCE(paid_at, created_at)) as period')
            ->selectRaw('SUM(amount) as revenue')
            ->selectRaw('COUNT(*) as transactions')
            ->where('status', 'success')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => (string) $row->period,
                'revenue' => (float) $row->revenue,
                'transactions' => (int) $row->transactions,
            ])
            ->all();
    }

    private function revenueByUser(): array
    {
        return Payment::query()
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('SUM(payments.amount) as total_paid')
            ->selectRaw('COUNT(payments.id) as transactions')
            ->where('payments.status', 'success')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_paid')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'total_paid' => (float) $row->total_paid,
                'transactions' => (int) $row->transactions,
            ])
            ->all();
    }

    private function payingUser(string $order): ?array
    {
        $query = Payment::query()
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('SUM(payments.amount) as total_paid')
            ->selectRaw('COUNT(payments.id) as transactions')
            ->where('payments.status', 'success')
            ->groupBy('users.id', 'users.name', 'users.email');

        $row = $order === 'asc'
            ? $query->orderBy('total_paid')->first()
            : $query->orderByDesc('total_paid')->first();

        if (!$row) {
            return null;
        }

        return [
            'user_id' => (int) $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'total_paid' => (float) $row->total_paid,
            'transactions' => (int) $row->transactions,
        ];
    }

    private function quizCreator(string $order): ?array
    {
        $query = User::query()
            ->withCount('quizzes')
            ->has('quizzes');

        $user = $order === 'asc'
            ? $query->orderBy('quizzes_count')->orderBy('id')->first()
            : $query->orderByDesc('quizzes_count')->orderBy('id')->first();

        if (!$user) {
            return null;
        }

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'quizzes_count' => (int) $user->quizzes_count,
        ];
    }

    private function mostAttemptedQuiz(): ?array
    {
        $quiz = Quiz::query()
            ->withCount('attempts')
            ->orderByDesc('attempts_count')
            ->orderBy('id')
            ->first();

        if (!$quiz) {
            return null;
        }

        return [
            'quiz_id' => $quiz->id,
            'title' => $quiz->title,
            'attempts_count' => (int) $quiz->attempts_count,
        ];
    }

    private function averageScoreQuiz(string $order): ?array
    {
        $query = QuizAttempt::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->select('quizzes.id', 'quizzes.title')
            ->selectRaw('AVG(CASE WHEN quiz_attempts.total_points > 0 THEN quiz_attempts.score * 100 / quiz_attempts.total_points ELSE 0 END) as avg_score')
            ->selectRaw('COUNT(quiz_attempts.id) as attempts_count')
            ->where('quiz_attempts.status', 'completed')
            ->groupBy('quizzes.id', 'quizzes.title');

        $row = $order === 'asc'
            ? $query->orderBy('avg_score')->first()
            : $query->orderByDesc('avg_score')->first();

        if (!$row) {
            return null;
        }

        return [
            'quiz_id' => (int) $row->id,
            'title' => $row->title,
            'avg_score' => round((float) $row->avg_score, 2),
            'attempts_count' => (int) $row->attempts_count,
        ];
    }

    private function aiGeneratedQuizCount(): int
    {
        if (Schema::hasColumn('quizzes', 'is_ai_generated')) {
            return Quiz::where('is_ai_generated', true)->count();
        }

        if (!Schema::hasTable('ai_logs')) {
            return 0;
        }

        return DB::table('ai_logs')
            ->where('action_type', 'ai_generate')
            ->whereNotNull('quiz_id')
            ->distinct('quiz_id')
            ->count('quiz_id');
    }
}
