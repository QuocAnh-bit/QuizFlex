<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_4px_16px_rgba(15,23,42,0.05)]"
  >
    <!-- Header -->
    <div
      class="flex items-center justify-between border-b border-slate-100 pb-4"
    >
      <div class="flex items-center gap-2.5">
        <!-- Bell -->
        <span
          class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#F5F3FF] text-[#7C3AED]"
        >
          <svg
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
            <path d="M10 21h4" />
          </svg>
        </span>

        <h3 class="text-sm font-bold tracking-wide text-[#334155]">
          THÔNG BÁO MỚI
        </h3>
      </div>

      <router-link
        to="/notifications"
        class="text-sm font-bold text-[#7C3AED] hover:underline"
      >
        Xem tất cả →
      </router-link>
    </div>

    <!-- Loading -->
    <div
      v-if="isLoading"
      class="py-5 text-center text-xs text-slate-400"
    >
      <span
        class="mx-auto mb-2 block h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-[#7C3AED]"
      ></span>

      <p>Đang tải thông báo...</p>
    </div>

    <!-- Guest -->
    <div
      v-else-if="!isLoggedIn"
      class="py-5 text-center"
    >
      <p class="text-xs leading-relaxed text-slate-500">
        Đăng nhập để nhận thông báo bài tập,
        phòng thi và kết quả mới nhất.
      </p>

      <router-link
        to="/login"
        class="mt-3 inline-flex rounded-lg bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#6D28D9]"
      >
        Đăng nhập ngay
      </router-link>
    </div>

    <!-- Empty -->
    <div
      v-else-if="latestNotifications.length === 0"
      class="py-5 text-center text-xs text-slate-400"
    >
      <div
        class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50"
      >
        <svg
          class="h-5 w-5"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M4 4h16v16H4z" />
          <path d="M8 10h8" />
          <path d="M8 14h5" />
        </svg>
      </div>

      <p>Bạn chưa có thông báo mới nào</p>
    </div>

    <!-- Notifications -->
    <div v-else class="mt-3 space-y-1">
      <div
        v-for="item in latestNotifications"
        :key="item.id"
        @click="handleClick(item)"
        class="group flex cursor-pointer items-center gap-3 rounded-xl px-2.5 py-3 transition duration-150"
        :class="
          !item.is_read
            ? 'bg-[#F5F3FF]/70'
            : 'hover:bg-slate-50'
        "
      >
        <!-- Notification icon -->
        <div
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
          :class="getIconBgClass(item.type)"
        >
          <svg
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <!-- Quiz -->
            <template v-if="item.type === 'quiz_moderated'">
              <path d="M6 3h8l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
              <path d="M14 3v5h5" />
              <path d="M8 12h6" />
              <path d="M8 16h5" />
            </template>

            <!-- Room -->
            <template v-else-if="item.type?.includes('room')">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </template>

            <!-- Homework -->
            <template v-else-if="item.type?.includes('homework')">
              <path d="M5 4h14v16H5z" />
              <path d="M8 8h8" />
              <path d="M8 12h8" />
              <path d="M8 16h5" />
            </template>

            <!-- Achievement -->
            <template v-else-if="item.type === 'achievement_unlocked'">
              <path d="M8 4h8v5a4 4 0 0 1-8 0V4z" />
              <path d="M8 6H5a2 2 0 0 0 2 4" />
              <path d="M16 6h3a2 2 0 0 1-2 4" />
              <path d="M12 13v4" />
              <path d="M9 21h6" />
              <path d="M10 17h4" />
            </template>

            <!-- Default -->
            <template v-else>
              <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
              <path d="M10 21h4" />
            </template>
          </svg>
        </div>

        <!-- Text -->
        <div class="min-w-0 flex-1">
          <p
            class="line-clamp-1 text-sm font-bold leading-snug text-[#0F172A] group-hover:text-[#7C3AED]"
            v-html="item.title"
          ></p>

          <p
            class="mt-0.5 line-clamp-1 text-xs text-[#64748B]"
            v-html="item.message"
          ></p>

          <span
            class="mt-1 block text-[11px] font-medium text-[#94A3B8]"
          >
            {{ formatTime(item.created_at) }}
          </span>
        </div>

        <!-- Unread -->
        <span
          v-if="!item.is_read"
          class="h-2.5 w-2.5 shrink-0 rounded-full bg-[#7C3AED]"
        ></span>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
} from 'vue'
import { useRouter } from 'vue-router'
import {
  notificationApi,
  currentUserStorage,
  tokenStorage,
} from '@/services/api'

const router = useRouter()

const notifications = ref([])
const isLoading = ref(false)
const currentUser = ref(currentUserStorage.get())

const isLoggedIn = computed(() =>
  Boolean(currentUser.value && tokenStorage.get())
)

const latestNotifications = computed(() =>
  notifications.value.slice(0, 3)
)

const fetchNotifications = async () => {
  if (!isLoggedIn.value) {
    notifications.value = []
    return
  }

  try {
    isLoading.value = true

    const { items } = await notificationApi.list({
      per_page: 5,
    })

    notifications.value = items || []
  } catch (error) {
    console.warn(
      'Failed to load notifications for home:',
      error
    )

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

      window.dispatchEvent(
        new CustomEvent('notifications-updated')
      )
    } catch (e) {
      console.warn(
        'Failed to mark notification as read:',
        e
      )
    }
  }

  if (item.action_link) {
    router.push(item.action_link)
  }
}

const getIconBgClass = (type) => {
  if (
    type?.includes('homework') ||
    type?.includes('quiz')
  ) {
    return 'bg-purple-50 text-[#7C3AED]'
  }

  if (type?.includes('room')) {
    return 'bg-blue-50 text-blue-600'
  }

  if (
    type?.includes('achievement') ||
    type?.includes('payment')
  ) {
    return 'bg-amber-50 text-amber-600'
  }

  if (
    type?.includes('locked') ||
    type?.includes('rejected') ||
    type?.includes('banned')
  ) {
    return 'bg-red-50 text-red-600'
  }

  return 'bg-slate-100 text-slate-600'
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

  return date.toLocaleDateString('vi-VN')
}

const syncUser = (event) => {
  currentUser.value =
    event?.detail ?? currentUserStorage.get()

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

  if (
    notifications.value.some(
      (n) => n.id === newNotification.id
    )
  ) {
    return
  }

  notifications.value.unshift(newNotification)
}

onMounted(() => {
  fetchNotifications()

  window.addEventListener(
    'quizflex-user-updated',
    syncUser
  )

  window.addEventListener(
    'notifications-updated',
    fetchNotifications
  )

  window.addEventListener(
    'realtime-notification',
    handleRealtimeNotification
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    'quizflex-user-updated',
    syncUser
  )

  window.removeEventListener(
    'notifications-updated',
    fetchNotifications
  )

  window.removeEventListener(
    'realtime-notification',
    handleRealtimeNotification
  )
})
</script>