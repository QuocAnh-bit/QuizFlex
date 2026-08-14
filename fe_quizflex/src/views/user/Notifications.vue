<template>
  <section class="max-w-4xl mx-auto py-4 space-y-6">
    <!-- Header Card -->
    <div class="card p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Hộp thư đến</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Thông báo của bạn</h1>
        <p class="mt-1 text-sm text-slate-600">Xem và quản lý tất cả các hoạt động, thông báo hệ thống và lời mời.</p>
      </div>
      <div class="flex items-center gap-2">
        <button 
          v-if="notifications.length > 0" 
          @click="openDeleteConfirmModal" 
          :disabled="isDeleting"
          class="btn-danger text-xs px-3.5 py-1.5"
        >
          <span v-if="isDeleting">Đang xóa...</span>
          <span v-else>Xóa tất cả</span>
        </button>
      </div>
    </div>

    <!-- Error/Loading states -->
    <div v-if="isLoading && notifications.length === 0" class="card p-10 text-center text-xs font-semibold text-slate-500">
      Đang tải thông báo...
    </div>
    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">
      {{ errorMessage }}
    </div>

    <!-- Notifications List -->
    <div v-if="notifications.length > 0" class="grid gap-3">
      <div 
        v-for="item in notifications" 
        :key="item.id" 
        @click="handleNotificationClick(item)" 
        class="card p-4 sm:p-5 flex gap-4 items-start card-hover cursor-pointer"
        :class="!item.is_read ? 'border-purple-200 bg-purple-50/40' : ''"
      >
        <!-- Icon -->
        <div class="h-10 w-10 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center text-xl border border-slate-100">
          {{ getIcon(item.type) }}
        </div>

        <!-- Body -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm font-bold text-slate-900 leading-snug" v-html="item.title"></h3>
            <span class="text-[11px] text-slate-400 font-medium shrink-0">{{ formatTime(item.created_at) }}</span>
          </div>
          <p class="mt-1 text-xs text-slate-600 leading-relaxed" v-html="item.message"></p>
        </div>

        <!-- Unread Dot -->
        <div v-if="!item.is_read" class="shrink-0 h-2 w-2 rounded-full bg-[#7C3AED] self-center"></div>
      </div>

      <!-- Load More Button -->
      <div v-if="hasMore" class="mt-2 text-center">
        <button 
          @click="loadMore" 
          :disabled="isLoading"
          class="btn-secondary text-xs px-5 py-2"
        >
          {{ isLoading ? 'Đang tải...' : 'Tải thêm thông báo' }}
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!isLoading && notifications.length === 0" class="card p-12 text-center text-slate-500">
      <span class="text-4xl block mb-2">📭</span>
      <h3 class="text-lg font-bold text-slate-800">Không có thông báo nào</h3>
      <p class="mt-1 text-xs">Hộp thư của bạn hiện đang trống. Mọi cập nhật quan trọng sẽ xuất hiện tại đây.</p>
    </div>

    <!-- Confirm Delete Modal -->
    <transition name="fade">
      <div v-if="isConfirmModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
          <div class="flex items-center gap-2.5 text-red-600">
            <span class="text-xl">⚠️</span>
            <h3 class="text-lg font-bold text-slate-900">Xóa tất cả thông báo?</h3>
          </div>
          <p class="text-sm text-slate-600 leading-relaxed">
            Bạn có chắc muốn xóa tất cả thông báo hiện tại không? Thao tác này không thể hoàn tác.
          </p>
          <div class="flex justify-end gap-2.5 pt-2">
            <button @click="closeDeleteConfirmModal" :disabled="isDeleting" class="btn-secondary text-xs">Hủy</button>
            <button @click="confirmDeleteAll" :disabled="isDeleting" class="btn-danger text-xs">
              {{ isDeleting ? 'Đang xóa...' : 'Xác nhận xóa' }}
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
    window.dispatchEvent(new CustomEvent('notifications-updated'))
    showToast('Đã xóa tất cả thông báo.', 'success')
  } catch (error) {
    showToast('Không thể xóa thông báo. Vui lòng thử lại.', 'error')
    console.error('Lỗi khi xóa thông báo:', error)
  } finally {
    isDeleting.value = false
  }
}

const hasMore = computed(() => currentPage.value < lastPage.value)

const fetchNotifications = async (page = 1) => {
  try {
    isLoading.value = true
    errorMessage.value = ''
    const { items, pagination } = await notificationApi.list({ page, per_page: 15 })
    
    if (page === 1) {
      notifications.value = items
    } else {
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

const getIcon = (type) => {
  const icons = {
    'quiz_moderated': '📝',
    'room_join_request': '👋',
    'room_member_approved': '✅',
    'room_member_rejected': '❌',
    'room_member_kicked': '🚷',
    'room_dissolved': '🏚️',
    'room_banned': '🚫',
    'room_unbanned': '🔓',
    'homework_assigned': '📚',
    'homework_submitted': '📤',
    'homework_evaluated': '💯',
    'homework_attempt_reset': '🔄',
    'account_locked': '🔒',
    'account_unlocked': '🔓',
    'unlock_request_created': '🔑',
    'unlock_request_approved': '✔️',
    'unlock_request_rejected': '❌',
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
