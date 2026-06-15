<template>
  <div class="app-shell">
    <div class="grid-bg"></div>
    <div class="orb orb-one"></div>
    <div class="orb orb-two"></div>

    <div class="relative z-10 grid min-h-screen lg:grid-cols-[290px_minmax(0,1fr)]">
      <!-- Sidebar -->
      <aside
        class="hidden border-r border-[var(--border)] bg-[var(--surface-soft)] p-5 backdrop-blur-2xl lg:block"
      >
        <div class="sticky top-5">
          <!-- Header -->
          <div
            class="rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface)] p-4 shadow-[var(--shadow-card)]"
          >
            <BrandLogo to="/admin" />

            <div
              class="mt-5 rounded-3xl border border-[var(--border-strong)] bg-[var(--chip-active)] p-4"
            >
              <p
                class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
              >
                Admin Console
              </p>

              <h3
                class="mt-1 text-2xl font-black tracking-[-0.05em] text-[var(--text)]"
              >
                Admin Dashboard
              </h3>

              <p
                class="mt-2 text-xs font-bold leading-5 text-[var(--muted)]"
              >
                Quản lý hệ thống, quiz, người dùng, báo cáo và thanh toán.
              </p>
            </div>
          </div>

          <!-- Sidebar Menu -->
          <nav class="mt-5 text-sm font-bold">
            <!-- Tổng quan -->
            <button
              @click="toggleMenu"
              class="flex w-full items-center justify-between rounded-2xl border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-4 text-[var(--text)] transition hover:opacity-90"
            >
              <div class="flex items-center gap-3">
                <span
                  class="grid h-9 w-9 place-items-center rounded-2xl bg-[var(--surface-soft)] text-xs font-black text-[var(--primary)]"
                >
                  DB
                </span>

                <span>Tổng quan</span>
              </div>

              <span class="transition duration-300" :class="isOpen ? 'rotate-180' : ''">
                ▼
              </span>
            </button>

            <!-- Dropdown -->
            <transition name="dropdown">
              <div v-show="isOpen" class="mt-3 grid gap-2">
                <router-link
                  v-for="item in menu"
                  :key="item.to"
                  :to="item.to"
                  :class="getLinkClass(item)"
                >
                  <span
                    class="grid h-9 w-9 place-items-center rounded-2xl bg-[var(--surface-soft)] text-xs font-black text-[var(--primary)]"
                  >
                    {{ item.icon }}
                  </span>

                  <span>{{ item.label }}</span>
                </router-link>
              </div>
            </transition>
          </nav>
        </div>
      </aside>

      <!-- Main Content -->
      <section class="min-w-0 p-4 sm:p-6 lg:p-8">
        <!-- Mobile -->
        <header class="mb-6 flex items-center justify-between gap-4 lg:hidden">
          <BrandLogo to="/admin" />
          <ThemeToggle />
        </header>

        <!-- Desktop -->
        <header class="mb-6 hidden items-center justify-between gap-4 lg:flex">
          <div>
            <p
              class="text-sm font-black uppercase tracking-[0.2em] text-[var(--primary)]"
            >
              QuizFlex admin
            </p>

            <h1
              class="mt-1 text-4xl font-black tracking-[-0.06em] text-[var(--text)]"
            >
              {{ pageTitle }}
            </h1>
          </div>

          <div class="flex items-center gap-3">
            <ThemeToggle />

            <router-link class="btn-ghost" to="/">
              Trang chủ
            </router-link>

            <router-link class="btn-primary" to="/admin/questions/create">
              Tạo quiz
            </router-link>
          </div>
        </header>

        <slot />
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import BrandLogo from '@/components/common/BrandLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'

const route = useRoute()

const isOpen = ref(true)

const toggleMenu = () => {
  isOpen.value = !isOpen.value
}

const menu = computed(() => [
  {
    label: 'Kho quiz',
    to: '/admin/questions',
    icon: 'QZ'
  },
  {
    label: 'Tạo quiz',
    to: '/admin/questions/create',
    icon: '+'
  },
  {
    label: 'AI Generator',
    to: '/admin/questions/ai',
    icon: 'AI'
  },
  {
    label: 'OCR Upload',
    to: '/admin/questions/ocr',
    icon: 'OC'
  },
  {
    label: 'Room',
    to: '/admin/rooms',
    icon: 'RT'
  },
  {
    label: 'Report',
    to: '/admin/reports',
    icon: 'RP'
  },
  {
    label: 'Payment',
    to: '/admin/payments',
    icon: '$'
  },
  {
    label: 'Users',
    to: '/admin/users',
    icon: 'US'
  },
  {
    label: 'Settings',
    to: '/admin/settings',
    icon: 'ST'
  }
])

const pageTitle = computed(
  () => route.meta.title || 'Dashboard'
)

const getLinkClass = (item) => {
  const active = route.path === item.to

  const base = [
    'flex',
    'items-center',
    'gap-3',
    'rounded-2xl',
    'border',
    'px-3',
    'py-3',
    'transition',
    'duration-300',
    'hover:-translate-y-0.5'
  ]

  if (!active) {
    return [
      ...base,
      'border-transparent',
      'text-[var(--muted)]',
      'hover:border-[var(--border)]',
      'hover:bg-[var(--surface)]',
      'hover:text-[var(--text)]'
    ]
  }

  return [
    ...base,
    'border-[var(--border-strong)]',
    'bg-[var(--chip-active)]',
    'text-[var(--text)]',
    'shadow-[0_14px_34px_rgba(155,44,255,0.14)]'
  ]
}
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.3s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>