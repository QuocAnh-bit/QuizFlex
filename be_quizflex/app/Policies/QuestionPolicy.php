<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
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
    public function view(?User $user, Question $question): bool
    {
        if ($question->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return true; // Admin có quyền xem phục vụ review/moderation
        }

        return (int) $user->id === (int) $question->user_id || ($question->quiz && (int) $user->id === (int) $question->quiz->user_id);
    }

    /**
     * Determine whether the user can create models.
     * Admin KHÔNG ĐƯỢC tạo Question qua generic CRUD.
     */
    public function create(User $user): bool
    {
        $role = strtolower($user->role ?? 'user');
        return $role !== 'admin';
    }

    /**
     * Determine whether the user can update the model.
     * Admin KHÔNG ĐƯỢC sửa trực tiếp nội dung Question.
     */
    public function update(User $user, Question $question): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return false;
        }

        return (int) $user->id === (int) $question->user_id || ($question->quiz && (int) $user->id === (int) $question->quiz->user_id);
    }

    /**
     * Determine whether the user can delete the model.
     * Admin chỉ được xóa Question là bản ghi Question Bank snapshot đã approved.
     */
    public function delete(User $user, Question $question): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return $question->bank_submission_status === 'approved'
                && !empty($question->origin_question_id);
        }

        return (int) $user->id === (int) $question->user_id || ($question->quiz && (int) $user->id === (int) $question->quiz->user_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Question $question): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return $question->bank_submission_status === 'approved'
                && !empty($question->origin_question_id);
        }

        return (int) $user->id === (int) $question->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Question $question): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return $question->bank_submission_status === 'approved'
                && !empty($question->origin_question_id);
        }

        return (int) $user->id === (int) $question->user_id;
    }
}

