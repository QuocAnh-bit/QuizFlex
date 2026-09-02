<template>
  <AppLoading :show="isLoading" :progress="progress" />

  <!-- Overlay tài khoản bị khóa -->
  <Transition name="fade">
    <div v-if="showLockedToast" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div class="w-full max-w-md rounded-2xl border border-red-200 bg-white p-6 shadow-xl text-center">
        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-2xl text-red-600">🔒</div>
        <h2 class="text-xl font-bold text-slate-900">Tài khoản bị khóa</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Tài khoản của bạn đã bị quản trị viên khóa. Bạn sẽ được chuyển đến trang kháng cáo.</p>
        <p class="mt-3 text-xs font-semibold text-slate-500">Tự động chuyển hướng sau <span class="text-red-600 font-bold">{{ lockedCountdown }}</span> giây...</p>
        <button class="mt-5 w-full btn-danger" type="button" @click="goToAppeal">Đến trang kháng cáo ngay</button>
      </div>
    </div>
  </Transition>

  <!-- Global Confirm Modal -->
  <Transition name="fade">
    <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
        <div class="flex items-center gap-3 text-red-600 mb-3">
          <span class="text-xl">⚠️</span>
          <h3 class="text-lg font-bold text-slate-900">{{ confirmModal.title }}</h3>
        </div>
        <p class="text-sm leading-relaxed text-slate-600 mb-6">{{ confirmModal.message }}</p>
        <div class="flex justify-end gap-2.5">
          <button @click="confirmModal.isOpen = false" class="btn-secondary text-xs">Hủy</button>
          <button @click="triggerConfirmAction" class="btn-danger text-xs">
            Xác nhận
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Global Toast Message -->
  <Transition name="slide-up">
    <div v-if="toast.isOpen" class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 rounded-xl border px-4 py-3 shadow-lg bg-white transition-all"
         :class="toast.type === 'success' ? 'border-emerald-200 text-emerald-700' : toast.type === 'warning' ? 'border-amber-200 text-amber-700' : 'border-red-200 text-red-700'">
      <span class="text-base">{{ toast.type === 'success' ? '✅' : toast.type === 'warning' ? '⚠️' : '❌' }}</span>
      <span class="text-sm font-semibold text-slate-800">{{ toast.message }}</span>
    </div>
  </Transition>

  <component :is="layout">
    <router-view />
  </component>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, provide } from "vue";
import { useRoute, useRouter } from "vue-router";

import AdminLayout from "@/layouts/AdminLayout.vue";
import UserLayout from "@/layouts/UserLayout.vue";
import AuthLayout from "@/layouts/AuthLayout.vue";

import AppLoading from "@/components/common/AppLoading.vue";
import { useAppLoading } from '@/composables/useAppLoading'
import { authApi, currentUserStorage } from "@/services/api";
import { getEcho, disconnectEcho } from "@/echo.js";
import { getNotificationPresentation, NOTIFICATION_DELIVERY } from "@/utils/notificationPriority";

const route = useRoute();
const router = useRouter();
const { isLoading, progress, startPageLoading } = useAppLoading();

const showLockedToast = ref(false);
const lockedCountdown = ref(5);
let lockedCountdownInterval = null
let accountChannel = null

// Global Dialog Services
const toast = ref({ isOpen: false, message: '', type: 'success' })
const confirmModal = ref({ isOpen: false, title: '', message: '', action: null })

const showToast = (message, type = 'success') => {
  toast.value.message = message
  toast.value.type = type
  toast.value.isOpen = true
  setTimeout(() => { toast.value.isOpen = false }, 3000)
}

const showConfirm = (title, message, action) => {
  confirmModal.value.title = title
  confirmModal.value.message = message
  confirmModal.value.action = action
  confirmModal.value.isOpen = true
}

const triggerConfirmAction = async () => {
  confirmModal.value.isOpen = false
  if (confirmModal.value.action) {
    await confirmModal.value.action()
  }
}

provide('showToast', showToast)
provide('showConfirm', showConfirm)

const subscribeAccountChannel = () => {
  try {
    const user = currentUserStorage.get()
    if (!user?.id) return

    const echo = getEcho()
    accountChannel = echo.private(`user.${user.id}`)
    
    accountChannel.listen('.account.status.changed', async (data) => {
      if (data.status === 'locked') {
        triggerLockedOverlay()
      } else if (data.status === 'unlocked' || data.status === 'appeal_approved') {
        await authApi.me()
        window.dispatchEvent(new CustomEvent('quizflex-account-unlocked'))
      } else if (data.status === 'appeal_rejected') {
        window.dispatchEvent(new CustomEvent('quizflex-appeal-rejected', { detail: data }))
      } else if (data.is_locked) {
        triggerLockedOverlay()
      } else {
        await authApi.me()
        window.dispatchEvent(new CustomEvent('quizflex-account-unlocked'))
      }
    })

    accountChannel.notification((notification) => {
      // 1. Luôn phát event cho NotificationBell & Notifications lưu vào inbox và cập nhật badge
      window.dispatchEvent(new CustomEvent('realtime-notification', { detail: notification }))

      // 2. Phân loại Toast Delivery Strategy để không spam popup vô tội vạ
      const presentation = getNotificationPresentation(notification)
      if (
        presentation.delivery === NOTIFICATION_DELIVERY.URGENT ||
        presentation.delivery === NOTIFICATION_DELIVERY.TOAST
      ) {
        if (notification?.title || notification?.message) {
          const toastMsg = notification.title ? `${notification.title}: ${notification.message}` : notification.message
          showToast(toastMsg, presentation.toastType || 'info')
        }
      }
    })
  } catch {
    // Reverb không khả dụng, bỏ qua realtime
  }
}

const unsubscribeAccountChannel = () => {
  if (accountChannel) {
    try {
      accountChannel.stopListening('.account.status.changed')
      accountChannel.stopListening('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated')
    } catch (e) {
      console.warn('Không thể hủy đăng ký Echo event:', e)
    }
    accountChannel = null
  }
}

const triggerLockedOverlay = () => {
  if (showLockedToast.value) return
  if (route.path === '/account-locked') return
  showLockedToast.value = true
  lockedCountdown.value = 5
  lockedCountdownInterval = setInterval(() => {
    lockedCountdown.value--
    if (lockedCountdown.value <= 0) {
      clearInterval(lockedCountdownInterval)
      goToAppeal()
    }
  }, 1000)
}

const goToAppeal = () => {
  showLockedToast.value = false
  if (lockedCountdownInterval) clearInterval(lockedCountdownInterval)
  if (route.path !== '/account-locked') {
    router.push('/account-locked')
  }
}

let removeBeforeGuard = null;

const layout = computed(() => {
  const layoutName = route.meta.layout;

  const layouts = {
    admin: AdminLayout,
    user: UserLayout,
    auth: AuthLayout,
  };

  return layouts[layoutName] || UserLayout;
});

onMounted(() => {
  window.addEventListener('quizflex-account-locked', triggerLockedOverlay)
  subscribeAccountChannel()
  window.addEventListener('quizflex-user-updated', (e) => {
    try {
      unsubscribeAccountChannel()
      if (e.detail) {
        subscribeAccountChannel()
      }
    } catch {
      // ignore
    }
  })

  removeBeforeGuard = router.beforeEach((to, from, next) => {
    if (to.fullPath !== from.fullPath) {
      startPageLoading();
    }

    next();
  });

});

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-account-locked', triggerLockedOverlay)
  unsubscribeAccountChannel()
  disconnectEcho()
  if (lockedCountdownInterval) clearInterval(lockedCountdownInterval)
  if (removeBeforeGuard) {
    removeBeforeGuard();
  }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
