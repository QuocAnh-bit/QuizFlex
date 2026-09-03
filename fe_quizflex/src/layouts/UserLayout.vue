<template>
  <div class="app-shell min-h-screen bg-[#F8FAFC]">
    <header
      class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur-md transition-all"
    >
      <div
        class="mx-auto flex h-16 w-[min(1240px,calc(100%-32px))] items-center justify-between gap-4"
      >
        <!-- Brand Logo -->
        <router-link to="/" class="flex shrink-0 items-center">
          <BrandLogo />
        </router-link>

        <!-- Desktop Navigation Links -->
        <nav class="hidden min-w-0 items-center gap-0.5 md:flex">
          <router-link
            v-for="item in mainNav"
            :key="item.to"
            :to="item.to"
            :class="[
              'shrink-0 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm font-semibold transition',
              isActiveNav(item)
                ? 'bg-purple-50 text-[#7C3AED] font-bold'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            ]"
          >
            {{ item.label }}
          </router-link>
        </nav>

        <!-- Right Utilities & User Profile -->
        <div class="hidden shrink-0 items-center gap-2.5 md:flex">
         
          <StreakXpBar />

          <!-- Guest -->
          <template v-if="!currentUser">
            <router-link
              to="/login"
              class="btn-secondary px-3.5 py-1.5 text-xs"
            >
              {{ $t('nav.user.login') }}
            </router-link>

            <router-link
              to="/register"
              class="btn-primary px-4 py-1.5 text-xs"
            >
              {{ $t('nav.user.getStarted') }}
            </router-link>
          </template>

          <!-- Logged In -->
          <template v-else>
            <!-- Admin Dashboard -->
            <router-link
              v-if="currentUser.role === 'admin'"
              :to="getDashboardRouteForRole(currentUser.role)"
              class="flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-bold text-[#7C3AED] transition hover:bg-purple-100"
              title="Quay về trang quản trị"
            >
              <LayoutDashboard class="h-4 w-4" />
            </router-link>

            <NotificationBell />

            <div class="relative user-dropdown-container">
              <!-- User Button -->
              <button
                type="button"
                @click="isUserDropdownOpen = !isUserDropdownOpen"
                class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-1 pr-2.5 shadow-sm transition hover:bg-slate-50 active:scale-95"
              >
                <UserAvatar
                  :user="currentUser"
                  size-class="h-7 w-7"
                  text-class="text-xs"
                />

                <span
                  class="max-w-[100px] truncate text-xs font-bold text-slate-800"
                >
                  {{ currentUser.name }}
                </span>

                <ChevronDown
                  class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                  :class="{ 'rotate-180': isUserDropdownOpen }"
                />
              </button>

              <!-- Dropdown -->
              <transition name="dropdown-slide">
                <div
                  v-if="isUserDropdownOpen"
                  class="absolute right-0 top-full z-50 mt-2 w-60 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl"
                >
                  <!-- User Info -->
                  <div class="mb-1 border-b border-slate-100 px-3.5 py-2.5">
                    <p class="truncate text-xs font-bold text-slate-900">
                      {{ currentUser.name }}
                    </p>

                    <p
                      class="mt-0.5 text-[10px] font-semibold uppercase text-[#7C3AED]"
                    >
                      {{ currentUser.role_label || currentUser.role }}
                    </p>
                  </div>

                  <!-- Profile -->
                  <router-link
                    to="/profile"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                  >
                    <UserRound class="h-4 w-4 shrink-0 text-slate-500" />
                    <span>{{ $t('nav.user.profile') }}</span>
                  </router-link>

                  <!-- Kho câu hỏi cá nhân -->
                  <router-link
                    to="/dashboard/my-questions"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                  >
                    <HelpCircle class="h-4 w-4 shrink-0 text-slate-500" />
                    <span>Kho câu hỏi của tôi</span>
                  </router-link>

                  <!-- Kho Quiz cá nhân -->
                  <router-link
                    to="/dashboard/questions"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                  >
                    <BookOpen class="h-4 w-4 shrink-0 text-slate-500" />
                    <span>Kho Quiz của tôi</span>
                  </router-link>

                  <!-- Báo cáo của tôi -->
                  <router-link
                    to="/my-reports"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                  >
                    <Flag class="h-4 w-4 shrink-0 text-slate-500" />
                    <span>Báo cáo của tôi</span>
                  </router-link>

                  <!-- Subscription -->
                  <router-link
                    to="/profile?tab=subscription"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                  >
                    <CreditCard class="h-4 w-4 shrink-0 text-slate-500" />
                    <span>Hạn mức &amp; Gói cước</span>
                  </router-link>

                  <!-- Achievements -->
                  <router-link
                    to="/gamification"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                  >
                    <Trophy class="h-4 w-4 shrink-0 text-slate-500" />
                    <span>{{ $t('nav.user.achievements') }}</span>
                  </router-link>

                  <!-- Results -->
                  <router-link
                    to="/results"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                  >
                    <BarChart3 class="h-4 w-4 shrink-0 text-slate-500" />
                    <span>{{ $t('nav.user.results') }}</span>
                  </router-link>

                  <!-- Analytics -->
                  <router-link
                    to="/analytics"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                  >
                    <ChartNoAxesCombined
                      class="h-4 w-4 shrink-0 text-slate-500"
                    />
                    <span>{{ $t('nav.user.analytics') }}</span>
                  </router-link>

                  <!-- Upgrade -->
                  <router-link
                    to="/upgrade"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-semibold text-[#7C3AED] transition hover:bg-purple-50"
                  >
                    <Crown class="h-4 w-4 shrink-0" />
                    <span>{{ $t('nav.user.upgrade') }}</span>
                  </router-link>

                  <!-- Admin Dashboard in Dropdown -->
                  <router-link
                    v-if="currentUser.role === 'admin'"
                    :to="getDashboardRouteForRole(currentUser.role)"
                    @click="isUserDropdownOpen = false"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-semibold text-[#7C3AED] transition hover:bg-purple-50"
                  >
                    <LayoutDashboard class="h-4 w-4 shrink-0" />
                    <span>Quay về trang quản trị</span>
                  </router-link>

                  <div class="my-1 border-t border-slate-100"></div>

                  <!-- Logout -->
                  <button
                    type="button"
                    @click="handleLogoutClick"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-bold text-red-600 transition hover:bg-red-50"
                  >
                    <LogOut class="h-4 w-4 shrink-0" />
                    <span>{{ $t('nav.user.logout') }}</span>
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
            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 active:scale-95"
            @click="isMenuOpen = !isMenuOpen"
            :aria-label="isMenuOpen ? 'Đóng menu' : 'Mở menu'"
          >
            <X v-if="isMenuOpen" class="h-5 w-5" />
            <Menu v-else class="h-5 w-5" />
          </button>
        </div>
      </div>

      <!-- Mobile Navigation Drawer -->
      <Transition name="fade">
        <div
          v-if="isMenuOpen"
          class="border-t border-slate-200 bg-white px-4 py-4 shadow-lg md:hidden"
        >
          <nav class="grid gap-1.5 text-sm font-semibold">
            <router-link
              v-for="item in mobileNav"
              :key="item.to"
              :to="item.to"
              :class="[
                'flex items-center justify-between rounded-lg px-3 py-2.5 transition',
                isActiveNav(item)
                  ? 'bg-purple-50 text-[#7C3AED] font-bold'
                  : 'text-slate-700 hover:bg-slate-50'
              ]"
              @click="isMenuOpen = false"
            >
              <span>{{ item.label }}</span>

              <span
                v-if="isActiveNav(item)"
                class="h-1.5 w-1.5 rounded-full bg-[#7C3AED]"
              ></span>
            </router-link>

            <!-- Admin Dashboard Mobile -->
            <router-link
              v-if="currentUser?.role === 'admin'"
              :to="getDashboardRouteForRole(currentUser.role)"
              class="flex items-center justify-between rounded-lg bg-purple-50 px-3 py-2.5 text-[#7C3AED] transition hover:bg-purple-100"
              @click="isMenuOpen = false"
            >
              <span class="flex items-center gap-2">
                <LayoutDashboard class="h-4 w-4" />
                <span>Quay về trang quản trị</span>
              </span>
            </router-link>
          </nav>
        </div>
      </Transition>
    </header>

    <!-- Main Content -->
    <main class="app-container pb-16 pt-6">
      <slot />
    </main>
  </div>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import {
  useRoute,
  useRouter,
} from 'vue-router'

import {
  useI18n,
} from 'vue-i18n'

import {
  HelpCircle,
  BookOpen,
  UserRound,
  CreditCard,
  Trophy,
  BarChart3,
  ChartNoAxesCombined,
  Crown,
  LogOut,
  Menu,
  X,
  ChevronDown,
  LayoutDashboard,
  Flag,
} from 'lucide-vue-next'

import BrandLogo from '@/components/common/BrandLogo.vue'
import LanguageSwitcher from '@/components/common/LanguageSwitcher.vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import StreakXpBar from '@/components/common/StreakXpBar.vue'
import NotificationBell from '@/components/common/NotificationBell.vue'

import {
  authApi,
  currentUserStorage,
  getDashboardRouteForRole,
} from '@/services/api'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const isMenuOpen = ref(false)
const isUserDropdownOpen = ref(false)

const currentUser = ref(
  currentUserStorage.get()
)

/* --------------------------------------------------
 * SYNC USER
 * -------------------------------------------------- */

const syncCurrentUser = (event) => {
  currentUser.value =
    event?.detail ??
    currentUserStorage.get()
}

/* --------------------------------------------------
 * LOGOUT
 * -------------------------------------------------- */

const handleLogout = async () => {
  const user =
    currentUser.value ||
    currentUserStorage.get()

  if (user) {
    localStorage.setItem(
      'quizflex_last_user',
      JSON.stringify({
        name: user.name,
        email: user.email,
        avatar: user.avatar,
        role_label:
          user.role_label ||
          user.role ||
          'user',
      })
    )
  }

  authApi
    .logout()
    .catch((error) => {
      console.error(
        'Background logout request failed:',
        error
      )
    })

  currentUser.value = null
  isUserDropdownOpen.value = false
  isMenuOpen.value = false

  router.push({
    path: '/login',
    query: {
      logout: 'success',
    },
  })
}

const handleLogoutClick = async () => {
  isUserDropdownOpen.value = false

  await handleLogout()
}

/* --------------------------------------------------
 * DESKTOP NAV
 * -------------------------------------------------- */

const baseNav = computed(() => [
  {
    label: t('nav.user.home'),
    to: '/',
  },
  {
    label: t('nav.user.quizzes'),
    to: '/quizzes',
  },
  {
    label: t('nav.user.leaderboard'),
    to: '/leaderboard',
  },
])

const homeworkNav = computed(() => {
  if (!currentUser.value) {
    return []
  }

  return [
    {
      label: t('nav.user.homeworkRoom'),
      to: '/homework-rooms',
    },
  ]
})

const liveRoomNav = computed(() => {
  if (!currentUser.value) {
    return []
  }

  return [
    {
      label: t('nav.user.liveRoom'),
      to: '/live-rooms',
    },
  ]
})

const mainNav = computed(() => [
  ...baseNav.value.slice(0, 2),
  ...homeworkNav.value,
  ...liveRoomNav.value,
  ...baseNav.value.slice(2),
])

/* --------------------------------------------------
 * MOBILE NAV
 * -------------------------------------------------- */

const mobileNav = computed(() => {
  const items = [
    {
      label: t('nav.user.home'),
      to: '/',
    },
    {
      label: t('nav.user.quizzes'),
      to: '/quizzes',
    },
    {
      label: t('nav.user.leaderboard'),
      to: '/leaderboard',
    },
  ]

  if (currentUser.value) {
    items.push(
      {
        label: 'Kho câu hỏi của tôi',
        to: '/dashboard/my-questions',
      },
      {
        label: 'Kho Quiz của tôi',
        to: '/dashboard/questions',
      },
      {
        label: t('nav.user.homeworkRoom'),
        to: '/homework-rooms',
      },
      {
        label: t('nav.user.liveRoom'),
        to: '/live-rooms',
      },
      {
        label: t('nav.user.achievementsShort'),
        to: '/gamification',
      },
      {
        label: t('nav.user.myResults'),
        to: '/results',
      },
      {
        label: t('nav.user.analytics'),
        to: '/analytics',
      },
      {
        label: t('nav.user.profile'),
        to: '/profile',
      },
      {
        label: 'Hạn mức & Gói cước',
        to: '/profile?tab=subscription',
      },
      {
        label: t('nav.user.upgrade'),
        to: '/upgrade',
      },
      {
        label:
          currentUser.value.role === 'admin'
            ? t('nav.user.adminDashboard')
            : t('nav.user.myDashboard'),
        to: getDashboardRouteForRole(
          currentUser.value.role
        ),
      }
    )
  } else {
    items.push(
      {
        label: t('nav.user.login'),
        to: '/login',
      },
      {
        label: t('nav.user.register'),
        to: '/register',
      }
    )
  }

  return items
})

/* --------------------------------------------------
 * ACTIVE NAV
 * -------------------------------------------------- */

const isActiveNav = (item) => {
  if (item.to === '/') {
    return (
      route.path === '/' &&
      !route.hash
    )
  }

  if (item.to === '/#quiz-topics') {
    return (
      route.path === '/' &&
      route.hash === '#quiz-topics'
    )
  }

  return route.path === item.to
}

/* --------------------------------------------------
 * CLOSE DROPDOWN WHEN CLICKING OUTSIDE
 * -------------------------------------------------- */

const closeDropdowns = (event) => {
  const target = event.target

  if (
    target instanceof Element &&
    !target.closest(
      '.user-dropdown-container'
    )
  ) {
    isUserDropdownOpen.value = false
  }
}

/* --------------------------------------------------
 * MOUNT
 * -------------------------------------------------- */

onMounted(() => {
  currentUser.value =
    currentUserStorage.get()

  window.addEventListener(
    'quizflex-user-updated',
    syncCurrentUser
  )

  window.addEventListener(
    'storage',
    syncCurrentUser
  )

  window.addEventListener(
    'click',
    closeDropdowns
  )
})

/* --------------------------------------------------
 * UNMOUNT
 * -------------------------------------------------- */

onBeforeUnmount(() => {
  window.removeEventListener(
    'quizflex-user-updated',
    syncCurrentUser
  )

  window.removeEventListener(
    'storage',
    syncCurrentUser
  )

  window.removeEventListener(
    'click',
    closeDropdowns
  )
})
</script>

<style scoped>
.dropdown-slide-enter-active,
.dropdown-slide-leave-active {
  transition:
    opacity 0.15s ease-out,
    transform 0.15s ease-out;
}

.dropdown-slide-enter-from,
.dropdown-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
</style>