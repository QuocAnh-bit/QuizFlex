<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class QuizPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Quiz $quiz): bool
    {
        Log::info('QuizPolicy@view check', [
            'quiz_id' => $quiz->id,
            'quiz_is_public' => $quiz->is_public,
            'room_code' => $quiz->room_code,
            'user_id' => $user ? $user->id : 'null',
            'user_role' => $user ? $user->role : 'null',
            'quiz_user_id' => $quiz->user_id,
        ]);

        if ($quiz->is_public || !empty($quiz->room_code)) {
            return true;
        }
        
        if (!$user) {
            Log::info('QuizPolicy@view failed: no user');
            return false;
        }
        
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return true;
        }

        $result = ($user->id == $quiz->user_id);
        Log::info('QuizPolicy@view user match result: ' . ($result ? 'true' : 'false'));
        return $result;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return true;
        }
        return $user->id == $quiz->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Quiz $quiz): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return true;
        }
        return $user->id == $quiz->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Quiz $quiz): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return true;
        }
        return $user->id == $quiz->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return strtolower($user->role ?? 'user') === 'admin';
    }
}
