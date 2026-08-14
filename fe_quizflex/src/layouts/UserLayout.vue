<template>
  <div class="app-shell min-h-screen bg-[#F8FAFC]">
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur-md transition-all">
      <div class="mx-auto flex h-16 w-[min(1240px,calc(100%-32px))] items-center justify-between gap-4">
        <!-- Brand Logo -->
        <router-link to="/" class="flex shrink-0 items-center">
          <BrandLogo />
        </router-link>

        <!-- Desktop Navigation Links -->
        <nav class="hidden items-center gap-1.5 md:flex">
          <router-link
            v-for="item in mainNav"
            :key="item.to"
            :to="item.to"
            :class="[
              'rounded-lg px-3.5 py-1.5 text-sm font-semibold transition',
              isActiveNav(item)
                ? 'bg-purple-50 text-[#7C3AED] font-bold'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            ]"
          >
            {{ item.label }}
          </router-link>
        </nav>

        <!-- Right Utilities & User Profile -->
        <div class="hidden items-center gap-2.5 md:flex">
          <LanguageSwitcher />
          <StreakXpBar />

          <template v-if="!currentUser">
            <router-link
              to="/login"
              class="btn-secondary text-xs px-3.5 py-1.5"
            >
              {{ $t('nav.user.login') }}
            </router-link>

            <router-link
              to="/register"
              class="btn-primary text-xs px-4 py-1.5"
            >
              {{ $t('nav.user.getStarted') }}
            </router-link>
          </template>

          <template v-else>
            <NotificationBell />
            <div class="relative user-dropdown-container">
              <button 
                @click="isUserDropdownOpen = !isUserDropdownOpen" 
                class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-1 pr-2.5 hover:bg-slate-50 transition active:scale-95 shadow-sm"
              >
                <UserAvatar :user="currentUser" size-class="h-7 w-7" text-class="text-xs" />
                <span class="text-xs font-bold text-slate-800 max-w-[100px] truncate">{{ currentUser.name }}</span>
                <span class="text-[9px] text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isUserDropdownOpen }">▼</span>
              </button>
              
              <!-- Dropdown list -->
              <transition name="dropdown-slide">
                <div v-if="isUserDropdownOpen" class="absolute right-0 top-full mt-2 w-60 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl z-50">
                  <!-- User Info Header -->
                  <div class="px-3.5 py-2.5 border-b border-slate-100 mb-1">
                    <p class="text-xs font-bold text-slate-900 truncate">{{ currentUser.name }}</p>
                    <p class="text-[10px] font-semibold text-[#7C3AED] uppercase mt-0.5">{{ currentUser.role_label || currentUser.role }}</p>
                  </div>
                  <!-- Navigation Links -->
                  <router-link @click="isUserDropdownOpen = false" to="/profile" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                    👤 {{ $t('nav.user.profile') }}
                  </router-link>
                  <router-link @click="isUserDropdownOpen = false" to="/profile?tab=subscription" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                    ⚡ Hạn mức & Gói cước
                  </router-link>
                  <router-link @click="isUserDropdownOpen = false" to="/gamification" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                    🏆 {{ $t('nav.user.achievements') }}
                  </router-link>
                  <router-link @click="isUserDropdownOpen = false" to="/results" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                    📊 {{ $t('nav.user.results') }}
                  </router-link>
                  <router-link @click="isUserDropdownOpen = false" to="/analytics" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                    📈 {{ $t('nav.user.analytics') }}
                  </router-link>
                  <router-link @click="isUserDropdownOpen = false" to="/upgrade" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-[#7C3AED] hover:bg-purple-50 transition">
                    👑 {{ $t('nav.user.upgrade') }}
                  </router-link>
                  <div class="border-t border-slate-100 my-1"></div>
                  <button @click="handleLogoutClick" class="flex w-full items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold text-red-600 hover:bg-red-50 transition text-left">
                    🚪 {{ $t('nav.user.logout') }}
                  </button>
                </div>
              </transition>
            </div>
          </template>
        </div>

        <!-- Mobile Controls -->
        <div class="flex items-center gap-2 md:hidden">
          <StreakXpBar />
          <NotificationBell v-if="currentUser" />
          <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm"
            @click="isMenuOpen = !isMenuOpen"
          >
            <span v-if="!isMenuOpen">☰</span>
            <span v-else>✕</span>
          </button>
        </div>
      </div>

      <!-- Mobile Navigation Drawer -->
      <Transition name="fade">
        <div
          v-if="isMenuOpen"
          class="border-t border-slate-200 bg-white px-4 py-4 md:hidden shadow-lg"
        >
          <nav class="grid gap-1.5 text-sm font-semibold">
            <router-link
              v-for="item in mobileNav"
              :key="item.to"
              :to="item.to"
              :class="[
                'flex items-center justify-between rounded-lg px-3 py-2.5 transition',
                isActiveNav(item) ? 'bg-purple-50 text-[#7C3AED] font-bold' : 'text-slate-700 hover:bg-slate-50'
              ]"
              @click="isMenuOpen = false"
            >
              <span>{{ item.label }}</span>
              <span v-if="isActiveNav(item)" class="h-1.5 w-1.5 rounded-full bg-[#7C3AED]"></span>
            </router-link>
          </nav>
        </div>
      </Transition>
    </header>

    <!-- Main Content Container -->
    <main class="app-container pb-16 pt-6">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";

import BrandLogo from '@/components/common/BrandLogo.vue'
import LanguageSwitcher from '@/components/common/LanguageSwitcher.vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import { authApi, currentUserStorage, getDashboardRouteForRole } from '@/services/api'
import StreakXpBar from '@/components/common/StreakXpBar.vue'
import NotificationBell from '@/components/common/NotificationBell.vue'

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const isMenuOpen = ref(false)
const isUserDropdownOpen = ref(false)
const currentUser = ref(currentUserStorage.get())

const syncCurrentUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get();
};

const handleLogout = async () => {
  const user = currentUser.value || currentUserStorage.get()
  const email = user?.email || ''
  
  if (user) {
    localStorage.setItem('quizflex_last_user', JSON.stringify({
      name: user.name,
      email: user.email,
      avatar: user.avatar,
      role_label: user.role_label || user.role || 'user',
    }))
  }
  
  authApi.logout().catch((e) => console.error('Background logout request failed:', e))
  currentUser.value = null
  
  const requiresAuth = route.meta.requiresAuth
  if (requiresAuth) {
    router.push({ path: '/login', query: email ? { email, logout: 'success' } : { logout: 'success' } })
  } else {
    router.push({ path: route.path, query: { _refresh: Date.now() } })
  }
}

const handleLogoutClick = async () => {
  isUserDropdownOpen.value = false
  await handleLogout()
}

const baseNav = computed(() => [
  { label: t('nav.user.home'), to: "/" },
  { label: t('nav.user.quizzes'), to: '/quizzes' },
  { label: t('nav.user.leaderboard'), to: '/leaderboard' },
])

const homeworkNav = computed(() => {
  if (!currentUser.value) return []
  return [{ label: t('nav.user.homeworkRoom'), to: '/homework-rooms' }]
})

const liveRoomNav = computed(() => {
  if (!currentUser.value) return []
  return [{ label: t('nav.user.liveRoom'), to: '/live-rooms' }]
})

const mainNav = computed(() => [
  ...baseNav.value.slice(0, 2),
  ...homeworkNav.value,
  ...liveRoomNav.value,
  ...baseNav.value.slice(2),
])

const mobileNav = computed(() => {
  const items = [
    { label: t('nav.user.home'), to: '/' },
    { label: t('nav.user.quizzes'), to: '/quizzes' },
    { label: t('nav.user.leaderboard'), to: '/leaderboard' },
  ]

  if (currentUser.value) {
    items.push(
      { label: t('nav.user.homeworkRoom'), to: '/homework-rooms' },
      { label: t('nav.user.liveRoom'), to: '/live-rooms' },
      { label: t('nav.user.achievementsShort'), to: '/gamification' },
      { label: t('nav.user.myResults'), to: '/results' },
      { label: '📈 ' + t('nav.user.analytics'), to: '/analytics' },
      { label: t('nav.user.profile'), to: '/profile' },
      { label: '⚡ Hạn mức & Gói cước', to: '/profile?tab=subscription' },
      { label: t('nav.user.upgrade'), to: '/upgrade' },
      {
        label: currentUser.value.role === "admin" ? t('nav.user.adminDashboard') : t('nav.user.myDashboard'),
        to: getDashboardRouteForRole(currentUser.value.role),
      }
    )
  } else {
    items.push(
      { label: t('nav.user.login'), to: '/login' },
      { label: t('nav.user.register'), to: '/register' }
    )
  }

  return items
})

const isActiveNav = (item) => {
  if (item.to === "/") {
    return route.path === "/" && !route.hash;
  }
  if (item.to === "/#quiz-topics") {
    return route.path === "/" && route.hash === "#quiz-topics";
  }
  return route.path === item.to;
};

const closeDropdowns = (e) => {
  if (!e.target.closest('.user-dropdown-container')) {
    isUserDropdownOpen.value = false
  }
}

onMounted(() => {
  currentUser.value = currentUserStorage.get()
  window.addEventListener('quizflex-user-updated', syncCurrentUser)
  window.addEventListener('storage', syncCurrentUser)
  window.addEventListener('click', closeDropdowns)
})

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-user-updated', syncCurrentUser)
  window.removeEventListener('storage', syncCurrentUser)
  window.removeEventListener('click', closeDropdowns)
})
</script>

<style scoped>
.dropdown-slide-enter-active,
.dropdown-slide-leave-active {
  transition: all 0.15s ease-out;
}

.dropdown-slide-enter-from,
.dropdown-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
</style>