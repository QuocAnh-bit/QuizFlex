<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

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
        if ($quiz->is_public || !empty($quiz->room_code)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return true; // Admin có quyền xem để phục vụ thẩm định/kiểm duyệt
        }

        return (int) $user->id === (int) $quiz->user_id;
    }

    /**
     * Determine whether the user can create models.
     * Admin KHÔNG ĐƯỢC tạo Quiz.
     */
    public function create(User $user): bool
    {
        $role = strtolower($user->role ?? 'user');
        return $role !== 'admin';
    }

    /**
     * Determine whether the user can update the model.
     * Admin KHÔNG ĐƯỢC sửa Quiz của User.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return false;
        }

        return (int) $user->id === (int) $quiz->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Admin KHÔNG ĐƯỢC xóa Quiz qua generic CRUD.
     */
    public function delete(User $user, Quiz $quiz): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return false;
        }

        return (int) $user->id === (int) $quiz->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Quiz $quiz): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return false;
        }

        return (int) $user->id === (int) $quiz->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return false;
    }

    /**
     * Determine whether the user can request review for public release.
     */
    public function requestReview(User $user, Quiz $quiz): bool
    {
        $role = strtolower($user->role ?? 'user');
        if ($role === 'admin') {
            return false; // Admin không thể gửi review request
        }

        // Tác giả chỉ được gửi duyệt nếu là chủ sở hữu và không đang trong trạng thái pending_review
        return ((int) $user->id === (int) $quiz->user_id) && ($quiz->review_status !== 'pending_review');
    }

    /**
     * Determine whether the user can moderate (approve/reject) quiz reviews.
     */
    public function moderate(User $user): bool
    {
        return strtolower($user->role ?? 'user') === 'admin';
    }
}
