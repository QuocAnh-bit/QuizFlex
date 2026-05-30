<template>
  <div class="app-shell">
    <div class="grid-bg"></div>
    <div class="orb orb-one"></div>
    <div class="orb orb-two"></div>
    <div class="orb orb-three"></div>

    <header class="sticky top-0 z-50 mx-auto w-[calc(100%-24px)] max-w-[1720px] pt-4">
      <div
        :class="[
          'relative overflow-hidden rounded-[1.75rem] border px-4 py-3 backdrop-blur-2xl transition duration-300',
          isScrolled
            ? 'border-[var(--border-strong)] bg-[var(--surface)]/90 shadow-[0_22px_70px_rgba(0,0,0,0.24)]'
            : 'border-[var(--border)] bg-[var(--surface)]/70 shadow-[var(--shadow-card)]',
        ]"
      >
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[var(--border-strong)] to-transparent"></div>
        <div class="pointer-events-none absolute -left-20 -top-24 h-44 w-44 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-20 -top-24 h-44 w-44 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

        <div class="relative z-10 flex items-center justify-between gap-4">
          <router-link
            to="/"
            class="flex shrink-0 items-center transition hover:-translate-y-0.5"
          >
            <BrandLogo />
          </router-link>

          <nav
            class="hidden shrink-0 items-center gap-1 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] p-1 text-sm font-bold shadow-inner xl:flex"
          >
            <router-link
              v-for="item in mainNav"
              :key="item.to"
              :to="item.to"
              :class="getNavLinkClass(item)"
            >
              <span class="relative z-10 whitespace-nowrap">
                {{ item.label }}
              </span>
            </router-link>
          </nav>

          <div class="hidden shrink-0 items-center gap-3 xl:flex">
            <ThemeToggle />

            <template v-if="!currentUser">
              <router-link
                to="/login"
                class="inline-flex h-11 shrink-0 items-center justify-center whitespace-nowrap rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-5 text-sm font-black text-[var(--text)] transition duration-300 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)] hover:shadow-[0_14px_35px_rgba(155,44,255,0.16)] active:scale-95"
              >
                Đăng nhập
              </router-link>

              <router-link
                to="/register"
                class="group relative inline-flex h-11 shrink-0 items-center justify-center overflow-hidden whitespace-nowrap rounded-full bg-gradient-to-br from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] px-6 text-sm font-black text-white shadow-[0_18px_38px_rgba(155,44,255,0.28)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_22px_48px_rgba(155,44,255,0.38)] active:scale-95"
              >
                <span class="absolute inset-0 translate-x-[-120%] bg-gradient-to-r from-transparent via-white/30 to-transparent transition duration-700 group-hover:translate-x-[120%]"></span>
                <span class="relative z-10">
                  Bắt đầu
                </span>
              </router-link>
            </template>
            <template v-else>
              <div class="flex items-center gap-3 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] pl-1.5 pr-4 py-1.5">
                 <UserAvatar :user="currentUser" size-class="h-8 w-8" text-class="text-xs" ring-class="ring-2 ring-white/10" />
                 <div class="grid leading-tight"><span class="text-xs font-black text-[var(--text)]">{{ currentUser.name }}</span><span class="text-[10px] font-bold text-[var(--primary)] uppercase">{{ currentUser.role }}</span></div>
                 <button @click="handleLogout" class="ml-2 text-xs font-black text-rose-500 hover:text-rose-400">Đăng xuất</button>
              </div>
            </template>
          </div>

          <div class="flex shrink-0 items-center gap-2 xl:hidden">
            <ThemeToggle />

            <button
              type="button"
              class="inline-flex h-11 items-center justify-center whitespace-nowrap rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 text-sm font-black text-[var(--text)] shadow-[var(--shadow-card)] transition duration-300 hover:border-[var(--border-strong)] active:scale-95"
              @click="isMenuOpen = !isMenuOpen"
            >
              {{ isMenuOpen ? 'Đóng' : 'Menu' }}
            </button>
          </div>
        </div>
      </div>

      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="-translate-y-2 opacity-0 scale-[0.98]"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100 scale-100"
        leave-to-class="-translate-y-2 opacity-0 scale-[0.98]"
      >
        <div
          v-if="isMenuOpen"
          class="relative mt-3 overflow-hidden rounded-[1.75rem] border border-[var(--border-strong)] bg-[var(--surface)]/95 p-3 shadow-[0_24px_80px_rgba(0,0,0,0.28)] backdrop-blur-2xl xl:hidden"
        >
          <div class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>

          <nav class="relative z-10 grid gap-2 text-sm font-bold">
            <router-link
              v-for="item in mobileNav"
              :key="item.to"
              :to="item.to"
              :class="getMobileNavLinkClass(item)"
              @click="isMenuOpen = false"
            >
              <span class="whitespace-nowrap">
                {{ item.label }}
              </span>

              <span
                v-if="isActiveNav(item)"
                class="h-2 w-2 rounded-full bg-[var(--primary)] shadow-[0_0_18px_var(--primary)]"
              ></span>
            </router-link>
          </nav>
        </div>
      </Transition>
    </header>

    <main class="app-container pb-16 pt-8">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import BrandLogo from '@/components/common/BrandLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import { authApi, currentUserStorage, getDashboardRouteForRole } from '@/services/api'

const route = useRoute()
const router = useRouter()

const isMenuOpen = ref(false)
const isScrolled = ref(false)
const currentUser = ref(currentUserStorage.get())

const syncCurrentUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get()
}

const handleLogout = async () => {
  const email = currentUser.value?.email || ''
  await authApi.logout()
  currentUser.value = null
  router.push({ path: '/login', query: email ? { email } : {} })
}

const baseNav = [
  {
    label: 'Trang chủ',
    to: '/',
  },
  {
    label: 'Chủ đề',
    to: '/#quiz-topics',
  },
  {
    label: 'Làm quiz',
    to: '/quizzes',
  },
  {
    label: 'Kết quả',
    to: '/results',
  },
  {
    label: 'Hồ sơ',
    to: '/profile',
  },
  {
    label: 'Nâng cấp',
    to: '/upgrade',
  },
]

const homeworkNav = computed(() => {
  if (!currentUser.value) return []

  const role = String(currentUser.value.role || 'user').toLowerCase()
  const items = [
    {
      label: 'Room Homework',
      to: '/homework-rooms',
    },
  ]

  if (['vip', 'admin'].includes(role)) {
    items.push({
      label: 'Tạo room',
      to: '/homework-rooms/create',
    })
  }

  return items
})

const liveRoomNav = computed(() => {
  if (!currentUser.value) return []

  const role = String(currentUser.value.role || 'user').toLowerCase()
  const items = [
    {
      label: 'Live Room',
      to: '/live-rooms',
    },
  ]

  if (role === 'vip') {
    items.push({
      label: 'Tạo Live Room',
      to: '/live-rooms/create',
    })
  }


  return items
})

const mainNav = computed(() => [
  ...baseNav.slice(0, 3),
  ...homeworkNav.value,
  ...liveRoomNav.value,
  ...baseNav.slice(3),
])

const mobileNav = computed(() => {
  if (currentUser.value) {
    return [
      ...mainNav.value,
      {
        label: currentUser.value.role === 'admin' ? 'Admin dashboard' : 'Dashboard của tôi',
        to: getDashboardRouteForRole(currentUser.value.role),
      },
    ]
  }

  return [
    ...mainNav.value,
    {
      label: 'Đăng nhập',
      to: '/login',
    },
    {
      label: 'Đăng ký',
      to: '/register',
    },
  ]
})

const handleScroll = () => {
  isScrolled.value = window.scrollY > 12
}

const isActiveNav = (item) => {
  if (item.to === '/') {
    return route.path === '/' && !route.hash
  }

  if (item.to === '/#quiz-topics') {
    return route.path === '/' && route.hash === '#quiz-topics'
  }

  return route.path === item.to
}

const getNavLinkClass = (item) => {
  const baseClass = [
    'relative',
    'shrink-0',
    'overflow-hidden',
    'rounded-full',
    'px-4',
    'py-2.5',
    'text-[var(--muted)]',
    'transition',
    'duration-300',
    'hover:-translate-y-0.5',
    'hover:text-[var(--text)]',
    'active:scale-95',
  ]

  if (!isActiveNav(item)) {
    return baseClass
  }

  return [
    ...baseClass,
    'bg-[var(--chip-active)]',
    'text-[var(--text)]',
    'shadow-[0_10px_28px_rgba(155,44,255,0.16)]',
    'before:absolute',
    'before:inset-0',
    'before:bg-gradient-to-r',
    'before:from-[var(--primary)]/15',
    'before:via-[var(--primary-2)]/15',
    'before:to-[var(--accent)]/15',
    'after:absolute',
    'after:bottom-1',
    'after:left-1/2',
    'after:h-1',
    'after:w-1',
    'after:-translate-x-1/2',
    'after:rounded-full',
    'after:bg-[var(--primary)]',
    'after:shadow-[0_0_18px_var(--primary)]',
  ]
}

const getMobileNavLinkClass = (item) => {
  const baseClass = [
    'flex',
    'items-center',
    'justify-between',
    'rounded-2xl',
    'border',
    'px-4',
    'py-3.5',
    'transition',
    'duration-300',
    'active:scale-[0.98]',
  ]

  if (!isActiveNav(item)) {
    return [
      ...baseClass,
      'border-transparent',
      'text-[var(--muted)]',
      'hover:border-[var(--border)]',
      'hover:bg-[var(--surface-soft)]',
      'hover:text-[var(--text)]',
    ]
  }

  return [
    ...baseClass,
    'border-[var(--border-strong)]',
    'bg-[var(--chip-active)]',
    'text-[var(--text)]',
    'shadow-[0_14px_34px_rgba(155,44,255,0.14)]',
  ]
}

onMounted(() => {
  handleScroll()
  currentUser.value = currentUserStorage.get()
  window.addEventListener('scroll', handleScroll, { passive: true })
  window.addEventListener('quizflex-user-updated', syncCurrentUser)
  window.addEventListener('storage', syncCurrentUser)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('quizflex-user-updated', syncCurrentUser)
  window.removeEventListener('storage', syncCurrentUser)
})
</script>
