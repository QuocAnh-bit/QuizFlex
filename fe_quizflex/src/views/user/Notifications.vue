<template>
  <section class="max-w-4xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
          Hộp thư đến
        </p>

        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">
          Thông báo của bạn
        </h1>

        <p class="mt-1 text-sm text-slate-600">
          Xem và quản lý tất cả các hoạt động, thông báo hệ thống và lời mời.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="notifications.length > 0"
          @click="openDeleteConfirmModal"
          :disabled="isDeleting"
          class="btn-danger text-xs px-3.5 py-1.5 inline-flex items-center gap-2"
        >
          <Trash2 v-if="!isDeleting" :size="14" />
          <Loader2 v-else :size="14" class="animate-spin" />

          <span>
            {{ isDeleting ? 'Đang xóa...' : 'Xóa tất cả' }}
          </span>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="isLoading && notifications.length === 0"
      class="card p-10 text-center text-xs font-semibold text-slate-500"
    >
      <Loader2
        :size="24"
        class="mx-auto mb-3 animate-spin text-[#7C3AED]"
      />
      Đang tải thông báo...
    </div>

    <!-- Error -->
    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700 flex items-center gap-2"
    >
      <CircleAlert :size="16" class="shrink-0" />
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Notifications List -->
    <div
      v-if="notifications.length > 0"
      class="grid gap-3"
    >
      <div
        v-for="item in notifications"
        :key="item.id"
        @click="handleNotificationClick(item)"
        class="card p-4 sm:p-5 flex gap-4 items-start card-hover cursor-pointer"
        :class="!item.is_read ? 'border-purple-200 bg-purple-50/40' : ''"
      >
        <!-- Notification Icon -->
        <div
          class="h-10 w-10 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 text-[#7C3AED]"
        >
          <component
            :is="getIcon(item.type)"
            :size="19"
            :stroke-width="2"
          />
        </div>

        <!-- Body -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <h3
              class="text-sm font-bold text-slate-900 leading-snug"
              v-html="item.title"
            ></h3>

            <span
              class="text-[11px] text-slate-400 font-medium shrink-0"
            >
              {{ formatTime(item.created_at) }}
            </span>
          </div>

          <p
            class="mt-1 text-xs text-slate-600 leading-relaxed"
            v-html="item.message"
          ></p>
        </div>

        <!-- Unread Dot -->
        <div
          v-if="!item.is_read"
          class="shrink-0 h-2 w-2 rounded-full bg-[#7C3AED] self-center"
        ></div>
      </div>

      <!-- Load More -->
      <div
        v-if="hasMore"
        class="mt-2 text-center"
      >
        <button
          @click="loadMore"
          :disabled="isLoading"
          class="btn-secondary text-xs px-5 py-2 inline-flex items-center gap-2"
        >
          <Loader2
            v-if="isLoading"
            :size="14"
            class="animate-spin"
          />

          <ChevronDown
            v-else
            :size="14"
          />

          {{ isLoading ? 'Đang tải...' : 'Tải thêm thông báo' }}
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="!isLoading && notifications.length === 0"
      class="card p-12 text-center text-slate-500"
    >
      <div
        class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 border border-slate-100 text-[#7C3AED]"
      >
        <Inbox :size="28" />
      </div>

      <h3 class="text-lg font-bold text-slate-800">
        Không có thông báo nào
      </h3>

      <p class="mt-1 text-xs">
        Hộp thư của bạn hiện đang trống. Mọi cập nhật quan trọng sẽ xuất hiện tại đây.
      </p>
    </div>

    <!-- Confirm Delete Modal -->
    <transition name="fade">
      <div
        v-if="isConfirmModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
      >
        <div
          class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4"
        >
          <div class="flex items-center gap-2.5 text-red-600">
            <div
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50"
            >
              <TriangleAlert :size="19" />
            </div>

            <h3 class="text-lg font-bold text-slate-900">
              Xóa tất cả thông báo?
            </h3>
          </div>

          <p class="text-sm text-slate-600 leading-relaxed">
            Bạn có chắc muốn xóa tất cả thông báo hiện tại không?
            Thao tác này không thể hoàn tác.
          </p>

          <div class="flex justify-end gap-2.5 pt-2">
            <button
              @click="closeDeleteConfirmModal"
              :disabled="isDeleting"
              class="btn-secondary text-xs"
            >
              Hủy
            </button>

            <button
              @click="confirmDeleteAll"
              :disabled="isDeleting"
              class="btn-danger text-xs inline-flex items-center gap-2"
            >
              <Trash2
                v-if="!isDeleting"
                :size="14"
              />

              <Loader2
                v-else
                :size="14"
                class="animate-spin"
              />

              {{ isDeleting ? 'Đang xóa...' : 'Xác nhận xóa' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </section>
</template>

<script setup>
import {
  ref,
  onMounted,
  onBeforeUnmount,
  computed,
  inject,
} from 'vue'

import { useRouter } from 'vue-router'

import {
  Bell,
  BookOpen,
  Check,
  CheckCircle2,
  CircleAlert,
  FileCheck2,
  FileText,
  GraduationCap,
  Hand,
  Inbox,
  KeyRound,
  Lock,
  LockOpen,
  LogOut,
  Mail,
  Megaphone,
  MessageCircle,
  RefreshCw,
  ShieldAlert,
  ShieldCheck,
  Trash2,
  TriangleAlert,
  UserCheck,
  UserMinus,
  UserPlus,
  XCircle,
  ChevronDown,
  Loader2,
} from 'lucide-vue-next'

import { notificationApi } from '@/services/api'

const router = useRouter()
const showToast = inject('showToast')

const notifications = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const isConfirmModalOpen = ref(false)
const isDeleting = ref(false)

const openDeleteConfirmModal = () => {
  isConfirmModalOpen.value = true
}

const closeDeleteConfirmModal = () => {
  isConfirmModalOpen.value = false
}

const confirmDeleteAll = async () => {
  if (isDeleting.value) return

  try {
    isDeleting.value = true

    await notificationApi.deleteAll()

    isConfirmModalOpen.value = false
    notifications.value = []
    currentPage.value = 1
    lastPage.value = 1

    window.dispatchEvent(
      new CustomEvent('notifications-updated')
    )

    showToast('Đã xóa tất cả thông báo.', 'success')
  } catch (error) {
    showToast(
      'Không thể xóa thông báo. Vui lòng thử lại.',
      'error'
    )

    console.error(
      'Lỗi khi xóa thông báo:',
      error
    )
  } finally {
    isDeleting.value = false
  }
}

const hasMore = computed(
  () => currentPage.value < lastPage.value
)

const fetchNotifications = async (page = 1) => {
  try {
    isLoading.value = true
    errorMessage.value = ''

    const {
      items,
      pagination,
    } = await notificationApi.list({
      page,
      per_page: 15,
    })

    if (page === 1) {
      notifications.value = items
    } else {
      items.forEach((item) => {
        if (
          !notifications.value.some(
            (n) => n.id === item.id
          )
        ) {
          notifications.value.push(item)
        }
      })
    }

    if (pagination) {
      currentPage.value = pagination.currentPage
      lastPage.value = pagination.lastPage
    }
  } catch (error) {
    errorMessage.value =
      `Không thể tải thông báo: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const loadMore = () => {
  if (
    hasMore.value &&
    !isLoading.value
  ) {
    fetchNotifications(
      currentPage.value + 1
    )
  }
}

const handleNotificationClick = async (item) => {
  if (!item.is_read) {
    item.is_read = true

    try {
      await notificationApi.markAsRead(item.id)

      window.dispatchEvent(
        new CustomEvent(
          'notifications-updated'
        )
      )
    } catch (error) {
      console.error(
        'Lỗi khi đánh dấu đã đọc',
        error
      )
    }
  }

  if (item.action_link) {
    router.push(item.action_link)
  }
}

const getIcon = (type) => {
  const icons = {
    // Standard Dot-Notation Types
    'report.created': ShieldAlert,
    'report.admin_review_required': ShieldAlert,
    'report.author_updated': ShieldAlert,
    'report.reminder': Bell,
    'report.warning': TriangleAlert,
    'report.auto_privatized': Lock,
    'report.resolved': CheckCircle2,
    'report.dismissed': CheckCircle2,
    'report.hidden': Lock,
    'report.shown': CheckCircle2,
    'question.reported': TriangleAlert,
    'question.deleted': Trash2,
    'question.moderated': FileCheck2,
    'question_review.requested': FileCheck2,
    'question_review.approved': CheckCircle2,
    'question_review.rejected': XCircle,
    'quiz.moderated': FileCheck2,
    'quiz_review.requested': FileCheck2,

    // Legacy Types (Backward Compatibility)
    quiz_moderated: FileCheck2,
    question_moderated: FileCheck2,
    question_review_requested: FileCheck2,
    quiz_review_requested: FileCheck2,
    report_author_updated: ShieldAlert,
    report_created: ShieldAlert,
    report_resolved: CheckCircle2,
    report_action: Megaphone,

    'room.join_request': UserPlus,
    'room.member_approved': UserCheck,
    'room.member_rejected': XCircle,
    'room.member_kicked': UserMinus,
    'room.dissolved': LogOut,
    'room.banned': ShieldAlert,
    'room.unbanned': ShieldCheck,
    room_join_request: UserPlus,
    room_member_approved: UserCheck,
    room_member_rejected: XCircle,
    room_member_kicked: UserMinus,
    room_dissolved: LogOut,
    room_banned: ShieldAlert,
    room_unbanned: ShieldCheck,

    'homework.assigned': BookOpen,
    'homework.submitted': FileText,
    'homework.evaluated': GraduationCap,
    'homework.attempt_reset': RefreshCw,
    homework_assigned: BookOpen,
    homework_submitted: FileText,
    homework_evaluated: GraduationCap,
    homework_attempt_reset: RefreshCw,

    'account.locked': Lock,
    'account.unlocked': LockOpen,
    'unlock_request.created': KeyRound,
    'unlock_request.approved': CheckCircle2,
    'unlock_request.rejected': XCircle,
    account_locked: Lock,
    account_unlocked: LockOpen,
    unlock_request_created: KeyRound,
    unlock_request_approved: CheckCircle2,
    unlock_request_rejected: XCircle,

    'payment.success': CheckCircle2,
    payment_success: CheckCircle2,
    'achievement.unlocked': CheckCircle2,
    achievement_unlocked: CheckCircle2,
    system: Bell,
  }

  return icons[type] || Mail
}

const formatTime = (isoString) => {
  if (!isoString) return ''

  const date = new Date(isoString)
  const now = new Date()

  const diffInSeconds = Math.floor(
    (now - date) / 1000
  )

  if (diffInSeconds < 60) {
    return 'Vừa xong'
  }

  if (diffInSeconds < 3600) {
    return `${Math.floor(diffInSeconds / 60)} phút trước`
  }

  if (diffInSeconds < 86400) {
    return `${Math.floor(diffInSeconds / 3600)} giờ trước`
  }

  return date.toLocaleDateString(
    'vi-VN',
    {
      hour: '2-digit',
      minute: '2-digit',
    }
  )
}

const handleRealtimeNotification = (e) => {
  const notification = e.detail
  if (!notification) return

  const nId = notification.id || `rt-${Date.now()}-${Math.random()}`

  if (notifications.value.some((n) => n.id === nId)) {
    return
  }

  const newNotification = {
    id: nId,
    type: notification.type || 'system',
    title: notification.title || '',
    message: notification.message || '',
    action: notification.action || 'view',
    action_link: notification.action_link || null,
    metadata: notification.metadata || {},
    is_read: false,
    created_at: new Date().toISOString(),
  }

  notifications.value.unshift(newNotification)
}

onMounted(() => {
  fetchNotifications(1)

  window.addEventListener(
    'realtime-notification',
    handleRealtimeNotification
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    'realtime-notification',
    handleRealtimeNotification
  )
})
</script>
