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

  <component :is="layout">
    <router-view />
  </component>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import AdminLayout from "@/layouts/AdminLayout.vue";
import UserLayout from "@/layouts/UserLayout.vue";
import AuthLayout from "@/layouts/AuthLayout.vue";

import AppCursor from "@/components/common/AppCursor.vue";
import AppLoading from "@/components/common/AppLoading.vue";
import { authApi, currentUserStorage } from "@/services/api";
import { getEcho, disconnectEcho } from "@/echo.js";

const route = useRoute();
const router = useRouter();

const isLoading = ref(true);
const showLockedToast = ref(false);
const lockedCountdown = ref(5);
let lockedCountdownInterval = null
let lockPollInterval = null
let accountChannel = null

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
  if (lockPollInterval) { clearInterval(lockPollInterval); lockPollInterval = null }
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

const startLockPolling = () => {
  if (lockPollInterval) return
  lockPollInterval = setInterval(async () => {
    if (showLockedToast.value) return
    if (route.path === '/account-locked') return
    const user = currentUserStorage.get()
    if (!user?.id) return
    try {
      const fresh = await authApi.lockedInfo()
      if (fresh?.is_locked) triggerLockedOverlay()
    } catch {
      // ignore
    }
  }, 30000)
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
      if (lockPollInterval) { clearInterval(lockPollInterval); lockPollInterval = null }
      if (e.detail) {
        subscribeAccountChannel()
        startLockPolling()
      }
    } catch {
      // ignore
    }
  })

  startLockPolling()

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
  if (lockPollInterval) clearInterval(lockPollInterval)
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
</style>
