<template>
  <section class="grid gap-6 py-8 max-w-4xl mx-auto px-4">
    <!-- Header Card -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Thông báo</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Thông báo của tôi</h1>
          <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Xem và quản lý tất cả các hoạt động, thông báo hệ thống và lời mời.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
          <!-- <button 
            v-if="hasUnread" 
            @click="markAllAsRead" 
            class="rounded-xl border border-[var(--primary)] bg-[var(--primary)]/10 px-4 py-2 text-xs font-black text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white active:scale-95 flex items-center gap-1.5"
          >
            <span>Đánh dấu tất cả đã đọc</span>
          </button> -->
          
          <button 
            v-if="notifications.length > 0" 
            @click="openDeleteConfirmModal" 
            :disabled="isDeleting"
            class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-400 transition hover:bg-rose-500 hover:text-white active:scale-95 flex items-center gap-1.5 disabled:opacity-50"
          >
            <span v-if="isDeleting">⏳ Đang xóa...</span>
            <span v-else class="flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"></path>
                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
              </svg>
              Xóa tất cả
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Error/Loading states -->
    <div v-if="isLoading && notifications.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
      Đang tải thông báo...
    </div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">
      {{ errorMessage }}
    </div>

    <!-- Notifications List -->
    <div v-if="notifications.length > 0" class="grid gap-3">
      <div 
        v-for="item in notifications" 
        :key="item.id" 
        @click="handleNotificationClick(item)" 
        :class="[
          'relative overflow-hidden rounded-2xl border transition duration-200 cursor-pointer p-4 md:p-5 flex gap-4 items-start shadow-sm',
          !item.is_read 
            ? 'border-[var(--primary)]/30 bg-[var(--primary)]/5 hover:bg-[var(--primary)]/10' 
            : 'border-[var(--border)] bg-[var(--surface)] hover:bg-[var(--surface-soft)]'
        ]"
      >
        <!-- Icon -->
        <div class="h-10 w-10 shrink-0 rounded-xl bg-[var(--surface-soft)] flex items-center justify-center text-xl shadow-inner">
          {{ getIcon(item.type) }}
        </div>

        <!-- Body -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm md:text-base font-bold text-[var(--text)] leading-snug" v-html="item.title"></h3>
            <span class="text-[10px] md:text-xs text-[var(--muted)] font-medium shrink-0">{{ formatTime(item.created_at) }}</span>
          </div>
          <p class="mt-1 text-xs md:text-sm text-[var(--muted)] leading-relaxed" v-html="item.message"></p>
        </div>

        <!-- Unread Indicator Dot -->
        <div v-if="!item.is_read" class="shrink-0 h-2.5 w-2.5 rounded-full bg-rose-500 self-center shadow-[0_0_8px_rgba(244,63,94,0.6)]"></div>
      </div>

      <!-- Load More Button -->
      <div v-if="hasMore" class="mt-4 text-center">
        <button 
          @click="loadMore" 
          :disabled="isLoading"
          class="rounded-xl border border-[var(--border)] bg-[var(--surface)] px-6 py-2.5 text-xs font-bold text-[var(--text)] transition hover:bg-[var(--surface-soft)] active:scale-95 disabled:opacity-50"
        >
          {{ isLoading ? 'Đang tải...' : 'Tải thêm thông báo' }}
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!isLoading && notifications.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-12 text-center shadow-[var(--shadow-card)] flex flex-col items-center justify-center gap-4">
      <span class="text-5xl">📭</span>
      <h3 class="text-2xl font-black text-[var(--text)]">Không có thông báo nào</h3>
      <p class="text-sm text-[var(--muted)] max-w-sm">Hộp thư của bạn hiện đang trống. Mọi cập nhật quan trọng sẽ xuất hiện tại đây.</p>
    </div>

    <!-- Confirm Delete Modal -->
    <transition name="fade">
      <div v-if="isConfirmModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
        <div class="w-full max-w-md rounded-[2.5rem] border border-[var(--border-strong)] bg-[var(--surface)] p-6 shadow-[0_24px_70px_rgba(0,0,0,0.5)] backdrop-blur-2xl transition-all scale-100">
          <div class="flex items-center gap-3 text-rose-400 mb-4">
            <span class="text-2xl">⚠️</span>
            <h3 class="text-xl font-black text-[var(--text)]">Xóa tất cả thông báo?</h3>
          </div>
          <p class="text-sm leading-6 text-[var(--muted)] mb-6">Bạn có chắc muốn xóa tất cả thông báo hiện tại không? Thao tác này sẽ xóa toàn bộ thông báo khỏi danh sách của bạn.</p>
          <div class="flex justify-end gap-3">
            <button @click="closeDeleteConfirmModal" :disabled="isDeleting" class="rounded-full bg-[var(--surface-soft)] hover:bg-[var(--border-light)] px-5 py-2 text-xs font-black text-[var(--text)] transition">Hủy</button>
            <button @click="confirmDeleteAll" :disabled="isDeleting" class="rounded-full bg-rose-500 hover:bg-rose-600 px-5 py-2 text-xs font-black text-white transition hover:-translate-y-0.5 disabled:opacity-50">
              <span v-if="isDeleting">Đang xóa...</span>
              <span v-else>Xóa tất cả</span>
            </button>
          </div>
        </div>
      </div>
    </transition>

  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, inject } from 'vue'
import { useRouter } from 'vue-router'
import { notificationApi, currentUserStorage } from '@/services/api'

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
    window.dispatchEvent(new CustomEvent('notifications-updated'))
    showToast('Đã xóa tất cả thông báo.', 'success')
  } catch (error) {
    showToast('Không thể xóa thông báo. Vui lòng thử lại.', 'error')
    console.error('Lỗi khi xóa thông báo:', error)
  } finally {
    isDeleting.value = false
  }
}

const hasUnread = computed(() => notifications.value.some(n => !n.is_read))
const hasMore = computed(() => currentPage.value < lastPage.value)

const fetchNotifications = async (page = 1) => {
  try {
    isLoading.value = true
    errorMessage.value = ''
    const { items, pagination } = await notificationApi.list({ page, per_page: 15 })
    
    if (page === 1) {
      notifications.value = items
    } else {
      // Append non-duplicate items
      items.forEach(item => {
        if (!notifications.value.some(n => n.id === item.id)) {
          notifications.value.push(item)
        }
      })
    }

    if (pagination) {
      currentPage.value = pagination.currentPage
      lastPage.value = pagination.lastPage
    }
  } catch (error) {
    errorMessage.value = `Không thể tải thông báo: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const loadMore = () => {
  if (hasMore.value && !isLoading.value) {
    fetchNotifications(currentPage.value + 1)
  }
}

const handleNotificationClick = async (item) => {
  if (!item.is_read) {
    item.is_read = true
    try {
      await notificationApi.markAsRead(item.id)
      window.dispatchEvent(new CustomEvent('notifications-updated'))
    } catch (error) {
      console.error('Lỗi khi đánh dấu đã đọc', error)
    }
  }

  if (item.action_link) {
    router.push(item.action_link)
  }
}

const markAllAsRead = async () => {
  notifications.value.forEach(n => n.is_read = true)
  try {
    await notificationApi.markAllAsRead()
    window.dispatchEvent(new CustomEvent('notifications-updated'))
  } catch (error) {
    console.error('Lỗi khi đánh dấu tất cả đã đọc', error)
  }
}

const getIcon = (type) => {
  const icons = {
    // Quiz & Question
    'quiz_moderated': '📖',
    'question_moderated': '❓',
    'report_author_updated': '🛠️',
    // Room Member status
    'room_join_request': '👋',
    'room_member_approved': '✅',
    'room_member_rejected': '❌',
    'room_member_kicked': '🚷',
    'room_dissolved': '🏚️',
    'room_banned': '🚫',
    'room_unbanned': '🔓',
    // Homework
    'homework_assigned': '📚',
    'homework_submitted': '📤',
    'homework_evaluated': '💯',
    'homework_attempt_reset': '🔄',
    // Account / Security
    'account_locked': '🔒',
    'account_unlocked': '🔓',
    'unlock_request_created': '🔑',
    'unlock_request_approved': '✔️',
    'unlock_request_rejected': '❌',
    // Others
    'payment_success': '💳',
    'report_created': '🚨',
    'report_resolved': '✅',
    'report_action': '🚨',
    'achievement_unlocked': '🏆',
    'system': '🔔'
  }
  return icons[type] || '📩'
}

const formatTime = (isoString) => {
  if (!isoString) return ''
  const date = new Date(isoString)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)
  
  if (diffInSeconds < 60) return 'Vừa xong'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} phút trước`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} giờ trước`
  return date.toLocaleDateString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

const handleRealtimeNotification = (e) => {
  const notification = e.detail
  const newNotification = {
    id: notification.id,
    type: notification.type || 'system',
    title: notification.title || '',
    message: notification.message || '',
    action: notification.action || 'view',
    action_link: notification.action_link || null,
    metadata: notification.metadata || {},
    is_read: false,
    created_at: new Date().toISOString()
  }

  // Tránh duplicate thông báo
  if (notifications.value.some(n => n.id === newNotification.id)) {
    return
  }

  notifications.value.unshift(newNotification)
}

onMounted(() => {
  fetchNotifications(1)
  window.addEventListener('realtime-notification', handleRealtimeNotification)
})

onBeforeUnmount(() => {
  window.removeEventListener('realtime-notification', handleRealtimeNotification)
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

</style>
