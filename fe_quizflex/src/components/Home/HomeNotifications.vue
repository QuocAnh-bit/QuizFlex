<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_4px_16px_rgba(15,23,42,0.06)] space-y-3.5">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div class="flex items-center gap-2">
        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-purple-50 text-xs text-[#7C3AED]">🔔</span>
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Thông báo mới</h3>
      </div>
      <router-link
        to="/notifications"
        class="text-xs font-bold text-[#7C3AED] hover:underline"
      >
        Xem tất cả →
      </router-link>
    </div>

    <!-- Content: Loading State -->
    <div v-if="isLoading" class="py-4 text-center text-xs text-slate-400 space-y-1">
      <span class="inline-block animate-spin">⏳</span>
      <p>Đang tải thông báo...</p>
    </div>

    <!-- Content: Guest State -->
    <div v-else-if="!isLoggedIn" class="rounded-xl bg-slate-50 p-3.5 text-center border border-slate-100 space-y-2">
      <p class="text-xs text-slate-600 leading-relaxed">
        Đăng nhập để nhận thông báo bài tập, phòng thi và kết quả mới nhất.
      </p>
      <router-link
        to="/login"
        class="inline-block rounded-lg bg-[#7C3AED] px-3.5 py-1.5 text-xs font-bold text-white transition hover:bg-[#6D28D9]"
      >
        Đăng nhập ngay
      </router-link>
    </div>

    <!-- Content: Empty State -->
    <div v-else-if="latestNotifications.length === 0" class="py-4 text-center text-xs text-slate-400 space-y-1">
      <span class="text-xl block">📭</span>
      <p>Bạn chưa có thông báo mới nào</p>
    </div>

    <!-- Content: Notification Items -->
    <div v-else class="space-y-2">
      <div
        v-for="item in latestNotifications"
        :key="item.id"
        @click="handleClick(item)"
        class="group flex items-start gap-2.5 rounded-xl p-2.5 transition duration-150 cursor-pointer"
        :class="!item.is_read ? 'bg-[#F5F3FF]/70 border border-purple-100 hover:bg-[#F5F3FF]' : 'hover:bg-slate-50 border border-transparent'"
      >
        <!-- Icon -->
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm" :class="getIconBgClass(item.type)">
          {{ getIcon(item.type) }}
        </div>

        <!-- Text details -->
        <div class="flex-1 min-w-0">
          <p class="text-xs font-bold text-slate-900 leading-snug line-clamp-1 group-hover:text-[#7C3AED] transition-colors" v-html="item.title"></p>
          <p class="text-[11px] text-slate-600 line-clamp-1 mt-0.5" v-html="item.message"></p>
          <span class="text-[10px] text-slate-400 mt-1 block font-medium">{{ formatTime(item.created_at) }}</span>
        </div>

        <!-- Unread dot -->
        <span v-if="!item.is_read" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#7C3AED]"></span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { notificationApi, currentUserStorage, tokenStorage } from '@/services/api'

const router = useRouter()
const notifications = ref([])
const isLoading = ref(false)
const currentUser = ref(currentUserStorage.get())

const isLoggedIn = computed(() => Boolean(currentUser.value && tokenStorage.get()))
const latestNotifications = computed(() => notifications.value.slice(0, 3))

const fetchNotifications = async () => {
  if (!isLoggedIn.value) {
    notifications.value = []
    return
  }

  try {
    isLoading.value = true
    const { items } = await notificationApi.list({ per_page: 5 })
    notifications.value = items || []
  } catch (error) {
    console.warn('Failed to load notifications for home:', error)
    notifications.value = []
  } finally {
    isLoading.value = false
  }
}

const handleClick = async (item) => {
  if (!item.is_read) {
    item.is_read = true
    try {
      await notificationApi.markAsRead(item.id)
      window.dispatchEvent(new CustomEvent('notifications-updated'))
    } catch (e) {
      console.warn('Failed to mark notification as read:', e)
    }
  }

  if (item.action_link) {
    router.push(item.action_link)
  }
}

const getIcon = (type) => {
  const icons = {
    quiz_moderated: '📝',
    room_join_request: '👋',
    room_member_approved: '✅',
    room_member_rejected: '❌',
    room_member_kicked: '🚷',
    room_dissolved: '🏚️',
    room_banned: '🚫',
    room_unbanned: '🔓',
    homework_assigned: '📚',
    homework_submitted: '📤',
    homework_evaluated: '💯',
    homework_attempt_reset: '🔄',
    account_locked: '🔒',
    account_unlocked: '🔓',
    unlock_request_created: '🔑',
    unlock_request_approved: '✔️',
    unlock_request_rejected: '❌',
    payment_success: '💳',
    report_created: '🚨',
    report_resolved: '✅',
    report_action: '🚨',
    achievement_unlocked: '🏆',
    system: '🔔',
  }
  return icons[type] || '📩'
}

const getIconBgClass = (type) => {
  if (type?.includes('homework') || type?.includes('quiz')) return 'bg-purple-50 text-[#7C3AED]'
  if (type?.includes('room')) return 'bg-blue-50 text-blue-600'
  if (type?.includes('achievement') || type?.includes('payment')) return 'bg-amber-50 text-amber-600'
  if (type?.includes('locked') || type?.includes('rejected') || type?.includes('banned')) return 'bg-red-50 text-red-600'
  return 'bg-slate-100 text-slate-600'
}

const formatTime = (isoString) => {
  if (!isoString) return ''
  const date = new Date(isoString)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)

  if (diffInSeconds < 60) return 'Vừa xong'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} phút trước`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} giờ trước`
  return date.toLocaleDateString('vi-VN')
}

const syncUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get()
  fetchNotifications()
}

const handleRealtimeNotification = (e) => {
  const notification = e.detail
  if (!notification) return

  const newNotification = {
    id: notification.id,
    type: notification.type || 'system',
    title: notification.title || '',
    message: notification.message || '',
    action_link: notification.action_link || null,
    is_read: false,
    created_at: new Date().toISOString(),
  }

  if (notifications.value.some((n) => n.id === newNotification.id)) return
  notifications.value.unshift(newNotification)
}

onMounted(() => {
  fetchNotifications()
  window.addEventListener('quizflex-user-updated', syncUser)
  window.addEventListener('notifications-updated', fetchNotifications)
  window.addEventListener('realtime-notification', handleRealtimeNotification)
})

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-user-updated', syncUser)
  window.removeEventListener('notifications-updated', fetchNotifications)
  window.removeEventListener('realtime-notification', handleRealtimeNotification)
})
</script>
