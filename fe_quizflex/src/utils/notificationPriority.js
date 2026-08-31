/**
 * Phân loại mức độ ưu tiên hiển thị thông báo (Toast Delivery Strategy).
 *
 * Cấp độ:
 * - 'urgent': Thông báo khẩn cấp (Cảnh báo gỡ công khai, câu hỏi bị khóa, tài khoản, lỗi nghiêm trọng).
 *             Hiển thị Toast cảnh báo/lỗi nổi bật (5-6s) + badge chuông.
 * - 'toast': Thông báo tương tác tích cực (Báo cáo đã gửi, câu hỏi được duyệt, thanh toán thành công).
 *            Hiển thị Toast bình thường (Success/Info, 3-4s) + badge chuông.
 * - 'inbox_only': Thông báo mang tính thông tin / định kỳ / kết quả xử lý bình thường.
 *                 KHÔNG hiển thị Toast popup xâm lấn, chỉ tăng badge chuông và lưu vào Hộp thư.
 * - 'silent': Cập nhật ngầm, không hiện thông báo.
 */

export const NOTIFICATION_DELIVERY = {
  URGENT: 'urgent',
  TOAST: 'toast',
  INBOX_ONLY: 'inbox_only',
  SILENT: 'silent',
}

export function getNotificationPresentation(notification) {
  if (!notification) {
    return {
      delivery: NOTIFICATION_DELIVERY.INBOX_ONLY,
      toastType: null,
      duration: 0,
    }
  }

  const type = notification.type || notification.metadata?.type || ''
  const category = notification.category || notification.metadata?.category || ''
  const action = notification.action || notification.metadata?.action || ''

  // 1. CẤP ĐỘ URGENT (Critical / High Priority Alerts)
  if (
    type === 'account.locked' ||
    type === 'account_locked' ||
    type === 'report.warning' ||
    type === 'report.auto_privatized' ||
    type === 'report.admin_review_required' ||
    action === 'auto_privatized' ||
    action === 'warning'
  ) {
    return {
      delivery: NOTIFICATION_DELIVERY.URGENT,
      toastType: 'warning',
      duration: 6000,
    }
  }

  if (
    type === 'question.deleted' ||
    type === 'room.banned' ||
    type === 'room.member_rejected' ||
    type === 'unlock_request.rejected' ||
    type === 'question_review.rejected' ||
    action === 'rejected' ||
    action === 'deleted'
  ) {
    return {
      delivery: NOTIFICATION_DELIVERY.URGENT,
      toastType: 'error',
      duration: 5000,
    }
  }

  // 2. CẤP ĐỘ TOAST (Normal Active Updates)
  if (
    type === 'question.reported' ||
    type === 'report.created' ||
    type === 'question_review.approved' ||
    type === 'achievement.unlocked' ||
    type === 'payment.success' ||
    type === 'room.member_approved' ||
    type === 'homework.assigned' ||
    action === 'reported' ||
    action === 'approved'
  ) {
    return {
      delivery: NOTIFICATION_DELIVERY.TOAST,
      toastType: 'success',
      duration: 4000,
    }
  }

  // 3. CẤP ĐỘ INBOX_ONLY (Informational / Background Resolution / Reminders)
  // Không hiện popup Toast gây phiền hà, người dùng xem trong Quả chuông hoặc Trang thông báo
  if (
    type === 'report.reminder' ||
    type === 'report.resolved' ||
    type === 'report.dismissed' ||
    type === 'report.hidden' ||
    type === 'report.shown' ||
    type === 'report.author_updated' ||
    type === 'homework.evaluated' ||
    type === 'homework.attempt_reset' ||
    action === 'reminder' ||
    action === 'resolved' ||
    action === 'dismissed' ||
    category === 'system'
  ) {
    return {
      delivery: NOTIFICATION_DELIVERY.INBOX_ONLY,
      toastType: null,
      duration: 0,
    }
  }

  // Default: Inbox only để tránh spam Toast
  return {
    delivery: NOTIFICATION_DELIVERY.INBOX_ONLY,
    toastType: null,
    duration: 0,
  }
}
