<template>
  <div class="relative notification-container" ref="bellContainer">
    <!-- Nút Quả Chuông -->
    <button
      @click="toggleDropdown"
      class="relative flex h-10 w-10 items-center justify-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] transition duration-200 hover:-translate-y-0.5 hover:bg-[var(--border-light)] hover:text-[var(--text)] hover:shadow-lg active:scale-95"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
      </svg>

      <!-- Chấm đỏ nhấp nháy khi có thông báo chưa đọc -->
      <span v-if="unreadCount > 0" class="absolute right-2 top-2 flex h-2.5 w-2.5">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.8)]"></span>
      </span>
    </button>

    <!-- Khung xổ xuống (Dropdown) -->
    <transition name="dropdown-slide">
      <div
        v-if="isOpen"
        class="absolute right-0 top-full mt-2 w-80 sm:w-96 rounded-2xl border border-[var(--border)] bg-[var(--surface)] shadow-2xl backdrop-blur-xl z-50 overflow-hidden flex flex-col max-h-[85vh]"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-[var(--border)] px-4 py-3 bg-[var(--surface-soft)]/50">
          <h3 class="font-black text-[var(--text)] text-sm">Thông báo</h3>
          <button 
            v-if="unreadCount > 0" 
            @click="markAllAsRead" 
            class="text-xs font-bold text-[var(--primary)] hover:text-[var(--accent)] transition"
          >
            Đánh dấu đã đọc
          </button>
        </div>

        <!-- Danh sách thông báo -->
        <div class="overflow-y-auto overflow-x-hidden p-2 custom-scrollbar flex-1">
          <div v-if="isLoading" class="p-4 text-center text-sm text-[var(--muted)]">
            Đang tải...
          </div>
          
          <div v-else-if="notifications.length === 0" class="p-8 text-center text-sm text-[var(--muted)] flex flex-col items-center gap-2">
            <span class="text-3xl">📭</span>
            Chưa có thông báo nào
          </div>

          <div v-else class="flex flex-col gap-1">
            <div
              v-for="item in notifications"
              :key="item.id"
              @click="handleReadNotification(item)"
              :class="[
                'cursor-pointer rounded-xl p-3 transition duration-200 hover:bg-[var(--surface-soft)]',
                !item.is_read ? 'bg-[var(--primary)]/5 border border-[var(--primary)]/20' : 'bg-transparent'
              ]"
            >
              <div class="flex gap-3 items-start">
                <!-- Icon theo loại thông báo (báo cáo, hệ thống, v.v.) -->
                <div class="mt-0.5 shrink-0 text-xl">
                  {{ getIcon(item.type) }}
                </div>
                <div class="flex-1">
                  <p class="text-sm font-bold text-[var(--text)] leading-tight mb-1" v-html="item.title"></p>
                  <p class="text-xs text-[var(--muted)] line-clamp-2" v-html="item.message"></p>
                  <span class="text-[10px] font-bold text-[var(--primary)] mt-1.5 block">{{ formatTime(item.created_at) }}</span>
                </div>
                <div v-if="!item.is_read" class="shrink-0 h-2 w-2 rounded-full bg-rose-500 mt-2 shadow-[0_0_8px_rgba(244,63,94,0.6)]"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Footer -->
        <router-link to="/notifications" @click="isOpen = false" class="block border-t border-[var(--border)] p-3 text-center text-xs font-black text-[var(--muted)] hover:bg-[var(--surface-soft)] hover:text-[var(--text)] transition">
          Xem tất cả thông báo
        </router-link>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { notificationApi } from '@/services/api'

const router = useRouter()
const isOpen = ref(false)
const isLoading = ref(false)
const bellContainer = ref(null)

// State thông báo
const notifications = ref([])
const unreadCount = ref(0)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value && notifications.value.length === 0) {
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
    } catch (error) {
      console.error('Lỗi khi đánh dấu tất cả đã đọc', error)
    }
  }
}

const getIcon = (type) => {
  const icons = {
    'report_resolved': '✅',
    'report_action': '🚨',
    'system': '🔔',
    'achievement': '🏆'
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

onMounted(() => {
  window.addEventListener('click', closeDropdown)
  fetchNotifications()
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeDropdown)
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