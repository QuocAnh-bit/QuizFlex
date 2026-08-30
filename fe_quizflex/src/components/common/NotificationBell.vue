<template>
  <div class="relative notification-container" ref="bellContainer">
    <!-- Nút Quả Chuông -->
    <button
      @click="toggleDropdown"
      class="relative flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 active:scale-95 shadow-sm"
      title="Thông báo"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
      </svg>

      <!-- Chấm đỏ khi có thông báo chưa đọc -->
      <span v-if="unreadCount > 0" class="absolute right-1.5 top-1.5 flex h-2 w-2">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
        <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
      </span>
    </button>

    <!-- Khung xổ xuống (Dropdown) -->
    <transition name="dropdown-slide">
      <div
        v-if="isOpen"
        class="absolute right-0 top-full mt-2 w-80 sm:w-96 rounded-xl border border-slate-200 bg-white shadow-xl z-50 overflow-hidden flex flex-col max-h-[85vh]"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 bg-slate-50">
          <h3 class="font-bold text-slate-900 text-sm">Thông báo</h3>
          <button 
            v-if="unreadCount > 0" 
            @click="markAllAsRead" 
            class="text-xs font-semibold text-[#7C3AED] hover:underline transition"
          >
            Đánh dấu đã đọc
          </button>
        </div>

        <!-- Danh sách thông báo -->
        <div class="overflow-y-auto overflow-x-hidden p-2 custom-scrollbar flex-1">
          <div v-if="isLoading" class="p-4 text-center text-sm text-slate-500">
            Đang tải...
          </div>
          
          <div v-else-if="notifications.length === 0" class="p-8 text-center text-sm text-slate-500 flex flex-col items-center gap-2">
            <span class="text-3xl">📭</span>
            Chưa có thông báo nào
          </div>

          <div v-else class="flex flex-col gap-1">
            <div
              v-for="item in notifications"
              :key="item.id"
              @click="handleReadNotification(item)"
              :class="[
                'cursor-pointer rounded-lg p-3 transition duration-150',
                !item.is_read ? 'bg-purple-50/70 border border-purple-100' : 'hover:bg-slate-50'
              ]"
            >
              <div class="flex gap-3 items-start">
                <div class="mt-0.5 shrink-0 text-lg">
                  {{ getIcon(item.type) }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-slate-900 leading-snug mb-0.5" v-html="item.title"></p>
                  <p class="text-xs text-slate-600 line-clamp-2" v-html="item.message"></p>
                  <span class="text-[10px] font-medium text-slate-400 mt-1 block">{{ formatTime(item.created_at) }}</span>
                </div>
                <div v-if="!item.is_read" class="shrink-0 h-2 w-2 rounded-full bg-[#7C3AED] mt-1.5"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Footer -->
        <router-link to="/notifications" @click="isOpen = false" class="block border-t border-slate-100 p-2.5 text-center text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-[#7C3AED] transition">
          Xem tất cả thông báo
        </router-link>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { notificationApi, currentUserStorage } from '@/services/api'

const router = useRouter()
const isOpen = ref(false)
const isLoading = ref(false)
const bellContainer = ref(null)

// State thông báo
const notifications = ref([])
const unreadCount = ref(0)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    fetchNotifications()
  }
}

const closeDropdown = (e) => {
  if (bellContainer.value && !bellContainer.value.contains(e.target)) {
    isOpen.value = false
  }
}

// Gọi API lấy danh sách
const fetchNotifications = async () => {
  try {
    isLoading.value = true
    const { items, unreadCount: count } = await notificationApi.list()
    notifications.value = items
    unreadCount.value = count
  } catch (error) {
    console.error('Lỗi khi tải thông báo', error)
  } finally {
    isLoading.value = false
  }
}

const handleReadNotification = async (item) => {
  if (!item.is_read) {
    item.is_read = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
    try {
      await notificationApi.markAsRead(item.id)
      window.dispatchEvent(new CustomEvent('notifications-updated'))
    } catch (error) {
      console.error('Lỗi khi đánh dấu đã đọc', error)
    }
  }
  
  isOpen.value = false
  
  if (item.action_link) {
    router.push(item.action_link)
  }
}

const markAllAsRead = async () => {
  const hadUnread = notifications.value.some(n => !n.is_read)
  notifications.value.forEach(n => n.is_read = true)
  unreadCount.value = 0

  if (hadUnread) {
    try {
      await notificationApi.markAllAsRead()
      window.dispatchEvent(new CustomEvent('notifications-updated'))
    } catch (error) {
      console.error('Lỗi khi đánh dấu tất cả đã đọc', error)
    }
  }
}

const getIcon = (type) => {
  const icons = {
    // Quiz & Question & Report
    'quiz_moderated': '📖',
    'question_moderated': '❓',
    'question_review_requested': '📩',
    'question_review': '📝',
    'report_author_updated': '🛠️',
    'report_created': '🚨',
    'report_resolved': '✅',
    'report_action': '🚨',
    'report': '🚨',
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
    'achievement_unlocked': '🏆',
    'system': '🔔'
  }
  return icons[type] || '📩'
}

const formatTime = (isoString) => {
  const date = new Date(isoString)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)
  
  if (diffInSeconds < 60) return 'Vừa xong'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} phút trước`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} giờ trước`
  return date.toLocaleDateString('vi-VN')
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
  if (notifications.value.length > 20) {
    notifications.value.pop()
  }

  unreadCount.value++
}

const handleNotificationsUpdated = () => {
  fetchNotifications()
}

onMounted(() => {
  window.addEventListener('click', closeDropdown)
  window.addEventListener('notifications-updated', handleNotificationsUpdated)
  window.addEventListener('realtime-notification', handleRealtimeNotification)
  fetchNotifications()
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeDropdown)
  window.removeEventListener('notifications-updated', handleNotificationsUpdated)
  window.removeEventListener('realtime-notification', handleRealtimeNotification)
})
</script>

<style scoped>
.dropdown-slide-enter-active,
.dropdown-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.dropdown-slide-enter-from,
.dropdown-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.96);
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: var(--border-strong);
  border-radius: 4px;
}
</style>
