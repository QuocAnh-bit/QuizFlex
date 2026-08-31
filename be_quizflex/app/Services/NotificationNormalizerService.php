<?php

namespace App\Services;

class NotificationNormalizerService
{
    /**
     * Chuẩn hóa bất kỳ Notification Model instance hoặc raw notification array nào
     * sang Standard Notification DTO contract.
     */
    public function normalize($notification): array
    {
        $raw = is_array($notification) ? $notification : ($notification->data ?? []);
        $id = is_object($notification) ? ($notification->id ?? null) : ($raw['id'] ?? null);
        $readAt = is_object($notification) ? ($notification->read_at ?? null) : ($raw['read_at'] ?? null);
        $createdAt = is_object($notification) ? ($notification->created_at ?? null) : ($raw['created_at'] ?? null);

        // 1. Phân giải & Chuẩn hóa Type
        $rawType = $raw['type'] ?? ($raw['action'] ?? 'system');
        $rawAction = $raw['action'] ?? ($raw['metadata']['action'] ?? null);
        $rawCategory = $raw['category'] ?? ($raw['metadata']['category'] ?? 'system');

        $type = $this->resolveStandardType($rawType, $rawAction, $raw);
        $category = $this->resolveStandardCategory($type, $rawCategory, $raw);

        // 2. Chuẩn hóa Title & Message
        $title = $raw['title'] ?? 'Thông báo hệ thống';
        $message = $raw['message'] ?? '';

        // 3. Chuẩn hóa Action & ActionLink (Audit & Fix Canonical Route)
        $action = $raw['action'] ?? 'view';
        $actionLink = $this->resolveStandardActionLink($type, $raw['action_link'] ?? null, $raw);

        // 4. Chuẩn hóa Metadata
        $metadata = $this->resolveStandardMetadata($raw, $type, $action);

        return [
            'id' => $id,
            'type' => $type,
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'action' => $action,
            'action_link' => $actionLink,
            'metadata' => (object) $metadata,
            'is_read' => !is_null($readAt),
            'created_at' => $createdAt,
        ];
    }

    /**
     * Chuyển đổi tên type legacy sang Standard Dot-Notation
     */
    protected function resolveStandardType(string $rawType, ?string $rawAction, array $raw): string
    {
        // 1. Nếu đã ở dạng dot-notation thì giữ nguyên
        if (str_contains($rawType, '.')) {
            return $rawType;
        }

        // 2. Xử lý QuestionModerated quá tải nhiều nghiệp vụ
        if ($rawType === 'question_moderated' || $rawType === 'question') {
            return match ($rawAction) {
                'reported' => 'question.reported',
                'reminder' => 'report.reminder',
                'warning' => 'report.warning',
                'auto_privatized' => 'report.auto_privatized',
                'hidden' => 'report.hidden',
                'shown' => 'report.shown',
                'resolved' => 'report.resolved',
                'dismissed' => 'report.dismissed',
                'approved' => 'question_review.approved',
                'rejected' => 'question_review.rejected',
                'deleted' => 'question.deleted',
                default => 'question.moderated',
            };
        }

        // 3. Xử lý Legacy Report Notifications
        return match ($rawType) {
            'report_created' => 'report.created',
            'report_resolved' => 'report.resolved',
            'report_author_updated' => 'report.author_updated',
            'report_action' => 'report.action',
            'question_review_requested' => 'question_review.requested',
            'quiz_review_requested' => 'quiz_review.requested',
            'quiz_moderated' => 'quiz.moderated',
            'room_join_request' => 'room.join_request',
            'room_member_approved' => 'room.member_approved',
            'room_member_rejected' => 'room.member_rejected',
            'room_member_kicked' => 'room.member_kicked',
            'room_dissolved' => 'room.dissolved',
            'room_banned' => 'room.banned',
            'room_unbanned' => 'room.unbanned',
            'homework_assigned' => 'homework.assigned',
            'homework_submitted' => 'homework.submitted',
            'homework_evaluated' => 'homework.evaluated',
            'homework_attempt_reset' => 'homework.attempt_reset',
            'account_locked' => 'account.locked',
            'account_unlocked' => 'account.unlocked',
            'unlock_request_created' => 'unlock_request.created',
            'unlock_request_approved' => 'unlock_request.approved',
            'unlock_request_rejected' => 'unlock_request.rejected',
            'payment_success' => 'payment.success',
            'achievement_unlocked' => 'achievement.unlocked',
            default => $rawType,
        };
    }

    /**
     * Phân giải category chuẩn
     */
    protected function resolveStandardCategory(string $type, string $rawCategory, array $raw): string
    {
        if (str_starts_with($type, 'report.') || $type === 'question.reported') {
            return 'report';
        }
        if (str_starts_with($type, 'question_review.')) {
            return 'question_review';
        }
        if (str_starts_with($type, 'quiz.') || str_starts_with($type, 'quiz_review.')) {
            return 'quiz';
        }
        if (str_starts_with($type, 'room.')) {
            return 'room';
        }
        if (str_starts_with($type, 'homework.')) {
            return 'homework';
        }
        if (str_starts_with($type, 'account.') || str_starts_with($type, 'unlock_request.')) {
            return 'account';
        }

        return $rawCategory ?: 'system';
    }

    /**
     * Chuẩn hóa canonical action_link cho frontend router
     */
    protected function resolveStandardActionLink(string $type, ?string $rawLink, array $raw): ?string
    {
        $metadata = $raw['metadata'] ?? [];
        $questionId = $metadata['question_id'] ?? ($raw['question_id'] ?? null);
        $quizId = $metadata['quiz_id'] ?? ($raw['quiz_id'] ?? null);
        $reportId = $metadata['report_id'] ?? ($raw['report_id'] ?? null);
        $recipientRole = $metadata['recipient_role'] ?? ($raw['recipient_role'] ?? null);

        // 1. Reporter Notifications -> trỏ về trang lịch sử Báo cáo của tôi (/my-reports)
        if ($recipientRole === 'reporter' || (!empty($reportId) && in_array($type, ['report.resolved', 'report.dismissed'], true))) {
            return "/my-reports";
        }

        // 2. Report Admin Notifications -> luôn trỏ về /admin/reports
        if (in_array($type, ['report.created', 'report.author_updated', 'report.admin_review_required'], true)) {
            return $questionId ? "/admin/reports?question_id={$questionId}" : "/admin/reports";
        }

        // 3. Question Review Requested (Priority -> /admin/reports, Normal -> /admin/question-bank)
        if ($type === 'question_review.requested') {
            $isPriority = !empty($metadata['is_priority']);
            if ($isPriority && $questionId) {
                return "/admin/reports?question_id={$questionId}";
            }
            return $questionId ? "/admin/question-bank?question_id={$questionId}" : "/admin/question-bank";
        }

        // 4. Report/Question Author Notifications -> trỏ về kho câu hỏi của tác giả (/dashboard/my-questions)
        if ($type === 'question_review.approved' || $type === 'report.shown') {
            return $questionId ? "/dashboard/my-questions?question_id={$questionId}&status=approved" : "/dashboard/my-questions";
        }
        if ($type === 'question_review.rejected') {
            return $questionId ? "/dashboard/my-questions?question_id={$questionId}&status=rejected" : "/dashboard/my-questions";
        }
        if (in_array($type, ['question.reported', 'report.reminder', 'report.warning', 'report.hidden'], true)) {
            return $questionId ? "/dashboard/my-questions?question_id={$questionId}&status=action_required" : "/dashboard/my-questions";
        }
        if (in_array($type, ['report.auto_privatized', 'report.resolved', 'report.dismissed'], true)) {
            return $questionId ? "/dashboard/my-questions?question_id={$questionId}" : "/dashboard/my-questions";
        }

        // 5. Payment & Subscription
        if ($type === 'payment.success') {
            return "/profile?tab=subscription";
        }

        // 6. Gamification & Achievement
        if ($type === 'achievement.unlocked') {
            return "/gamification";
        }

        // 7. Room & Homework
        if (str_starts_with($type, 'room.')) {
            $roomId = $metadata['room_id'] ?? ($raw['room_id'] ?? null);
            return $roomId ? "/rooms/{$roomId}" : "/dashboard/rooms";
        }
        if (str_starts_with($type, 'homework.')) {
            $assignmentId = $metadata['assignment_id'] ?? ($raw['assignment_id'] ?? null);
            return $assignmentId ? "/dashboard/homework/{$assignmentId}" : "/dashboard/rooms";
        }

        // 8. Account & Security
        if (str_starts_with($type, 'account.') || str_starts_with($type, 'unlock_request.')) {
            return "/profile";
        }

        // 9. Nếu link cũ chứa /admin/report-tickets, chuyển thành canonical /admin/reports
        if ($rawLink && str_starts_with($rawLink, '/admin/report-tickets')) {
            return str_replace('/admin/report-tickets', '/admin/reports', $rawLink);
        }

        // 10. Nếu link cũ chứa /admin/questions?question_id, chuyển sang /admin/question-bank
        if ($rawLink && str_starts_with($rawLink, '/admin/questions')) {
            return str_replace('/admin/questions', '/admin/question-bank', $rawLink);
        }

        // 11. Legacy Quiz fallback
        if (is_null($rawLink) && $quizId) {
            return "/quizzes/{$quizId}";
        }

        return $rawLink;
    }

    /**
     * Chuẩn hóa cấu trúc Metadata
     */
    protected function resolveStandardMetadata(array $raw, string $type, string $action): array
    {
        $metadata = $raw['metadata'] ?? [];

        // Trích xuất các trường từ raw nếu metadata thiếu
        if (!isset($metadata['question_id']) && isset($raw['question_id'])) {
            $metadata['question_id'] = $raw['question_id'];
        }
        if (!isset($metadata['report_id']) && isset($raw['report_id'])) {
            $metadata['report_id'] = $raw['report_id'];
        }
        if (!isset($metadata['quiz_id']) && isset($raw['quiz_id'])) {
            $metadata['quiz_id'] = $raw['quiz_id'];
        }
        if (!isset($metadata['status']) && isset($raw['status'])) {
            $metadata['status'] = $raw['status'];
        }

        $metadata['type'] = $type;
        $metadata['action'] = $metadata['action'] ?? $action;

        return $metadata;
    }
}
