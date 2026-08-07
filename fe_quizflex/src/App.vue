<template>
  <AppLoading :show="isLoading" />

  <!-- Overlay tài khoản bị khóa -->
  <Transition name="fade">
    <div v-if="showLockedToast" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm">
      <div class="mx-4 w-full max-w-md rounded-[2rem] border border-rose-500/30 bg-[var(--surface)] p-8 shadow-2xl text-center">
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-rose-500/15 text-4xl">🔒</div>
        <h2 class="text-2xl font-black text-rose-400">Tài khoản bị khóa!</h2>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Tài khoản của bạn đã bị quản trị viên khóa. Bạn sẽ được chuyển đến trang kháng cáo.</p>
        <p class="mt-4 text-xs font-bold text-[var(--muted)]">Tự động chuyển hướng sau <span class="text-rose-400">{{ lockedCountdown }}</span> giây...</p>
        <button class="mt-6 w-full rounded-2xl border border-rose-500/30 bg-rose-500/10 py-3 text-sm font-black text-rose-400 transition hover:bg-rose-500/20" type="button" @click="goToAppeal">Đến trang kháng cáo ngay</button>
      </div>
    </div>
  </Transition>

  <!-- Global Confirm Modal -->
  <Transition name="fade">
    <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
      <div class="w-full max-w-md rounded-[2.5rem] border border-[var(--border-strong)] bg-[var(--surface)] p-6 shadow-[0_24px_70px_rgba(0,0,0,0.5)] backdrop-blur-2xl transition-all scale-100">
        <div class="flex items-center gap-3 text-rose-400 mb-4">
          <span class="text-2xl">⚠️</span>
          <h3 class="text-xl font-black text-[var(--text)]">{{ confirmModal.title }}</h3>
        </div>
        <p class="text-sm leading-6 text-[var(--muted)] mb-6">{{ confirmModal.message }}</p>
        <div class="flex justify-end gap-3">
          <button @click="confirmModal.isOpen = false" class="rounded-full bg-[var(--surface-soft)] hover:bg-[var(--border-light)] px-5 py-2 text-xs font-black text-[var(--text)] transition">Hủy</button>
          <button @click="triggerConfirmAction" class="rounded-full bg-rose-500 hover:bg-rose-600 px-5 py-2 text-xs font-black text-white transition hover:-translate-y-0.5">
            Xác nhận
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Global Toast Message -->
  <Transition name="slide-up">
    <div v-if="toast.isOpen" class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 rounded-2xl border px-4 py-3 shadow-lg backdrop-blur-xl transition-all"
         :class="toast.type === 'success' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 shadow-[0_12px_40px_rgba(16,185,129,0.1)]' : toast.type === 'warning' ? 'border-amber-500/30 bg-amber-500/10 text-amber-400 shadow-[0_12px_40px_rgba(245,158,11,0.1)]' : 'border-rose-500/30 bg-rose-500/10 text-rose-400 shadow-[0_12px_40px_rgba(244,63,94,0.1)]'">
      <span class="text-base">{{ toast.type === 'success' ? '✅' : toast.type === 'warning' ? '⚠️' : '❌' }}</span>
      <span class="text-sm font-bold">{{ toast.message }}</span>
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
import { authApi, currentUserStorage } from "@/services/api";
import { getEcho, disconnectEcho } from "@/echo.js";

const route = useRoute();
const router = useRouter();

const isLoading = ref(true);
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
      .listen('.account.status.changed', async (data) => {
        if (data.is_locked) {
          triggerLockedOverlay()
        } else {
          await authApi.me()
          window.dispatchEvent(new CustomEvent('quizflex-account-unlocked'))
        }
      })
  } catch {
    // Reverb không khả dụng, bỏ qua realtime
  }
}

const unsubscribeAccountChannel = () => {
  if (accountChannel) {
    accountChannel.stopListening('.account.status.changed')
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

let loadingTimer = null;
let forceCloseTimer = null;
let removeBeforeGuard = null;
let removeAfterGuard = null;

const MIN_LOADING_TIME = 150;

const layout = computed(() => {
  const layoutName = route.meta.layout;

  const layouts = {
    admin: AdminLayout,
    user: UserLayout,
    auth: AuthLayout,
  };

  return layouts[layoutName] || UserLayout;
});

const startLoading = () => {
  clearTimeout(loadingTimer);
  clearTimeout(forceCloseTimer);

  isLoading.value = true;

  forceCloseTimer = setTimeout(() => {
    isLoading.value = false;
  }, MIN_LOADING_TIME + 700);
};

const stopLoading = () => {
  clearTimeout(loadingTimer);
  clearTimeout(forceCloseTimer);

  loadingTimer = setTimeout(() => {
    isLoading.value = false;
  }, MIN_LOADING_TIME);
};

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
      startLoading();
    }

    next();
  });

  removeAfterGuard = router.afterEach(() => {
    stopLoading();
  });

  stopLoading();
});

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-account-locked', triggerLockedOverlay)
  unsubscribeAccountChannel()
  disconnectEcho()
  if (lockedCountdownInterval) clearInterval(lockedCountdownInterval)
  clearTimeout(loadingTimer);
  clearTimeout(forceCloseTimer);

  if (removeBeforeGuard) {
    removeBeforeGuard();
  }

  if (removeAfterGuard) {
    removeAfterGuard();
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
