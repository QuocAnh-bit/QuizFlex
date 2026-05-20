<template>
  <div class="app-shell">
    <div class="grid-bg"></div>
    <div class="orb orb-one"></div>
    <div class="orb orb-two"></div>

    <div class="relative z-10 grid min-h-screen lg:grid-cols-[290px_minmax(0,1fr)]">
      <aside class="hidden border-r border-[var(--border)] bg-[var(--surface-soft)] p-5 backdrop-blur-2xl lg:block">
        <div class="sticky top-5">
          <div class="rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface)] p-4 shadow-[var(--shadow-card)]">
            <BrandLogo to="/admin" />
            <div class="mt-5 rounded-3xl border border-[var(--border-strong)] bg-[var(--chip-active)] p-4">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ isAdmin ? 'Admin Console' : 'Creator Workspace' }}</p>
              <h3 class="mt-1 text-2xl font-black tracking-[-0.05em] text-[var(--text)]">QuizFlex Console</h3>
              <p class="mt-2 text-xs font-bold leading-5 text-[var(--muted)]">Quản lý không gian sáng tạo quiz và các tính năng nâng cao của bạn.</p>
            </div>
          </div>

          <nav class="mt-5 grid gap-2 text-sm font-bold">
            <router-link v-for="item in menu" :key="item.to" :to="item.to" :class="getLinkClass(item)">
              <span class="grid h-9 w-9 place-items-center rounded-2xl bg-[var(--surface-soft)] text-xs font-black text-[var(--primary)]">{{ item.icon }}</span>
              <span>{{ item.label }}</span>
            </router-link>
          </nav>
        </div>
      </aside>

      <section class="min-w-0 p-4 sm:p-6 lg:p-8">
        <header class="mb-6 flex items-center justify-between gap-4 lg:hidden">
          <BrandLogo to="/admin" />
          <ThemeToggle />
        </header>

        <header class="mb-6 hidden items-center justify-between gap-4 lg:flex">
          <div>
            <p class="text-sm font-black uppercase tracking-[0.2em] text-[var(--primary)]">QuizFlex console</p>
            <h1 class="mt-1 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ pageTitle }}</h1>
          </div>
          <div class="flex items-center gap-3">
            <ThemeToggle />
            <router-link class="btn-ghost" to="/">Trang chủ</router-link>
            <router-link class="btn-primary" to="/admin/questions/create">Tạo quiz</router-link>
          </div>
        </header>

        <slot />
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import BrandLogo from '@/components/common/BrandLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'

import { currentUserStorage } from '@/services/api'

const route = useRoute()
const currentUser = computed(() => currentUserStorage.get())
const isAdmin = computed(() => currentUser.value?.role === 'admin')

const menu = computed(() => {
  const base = [
    { label: 'Kho quiz', to: '/admin/questions', icon: 'QZ' },
    { label: 'Tạo quiz', to: '/admin/questions/create', icon: '+' },
    { label: 'AI Generator', to: '/admin/questions/ai', icon: 'AI' },
    { label: 'OCR Upload', to: '/admin/questions/ocr', icon: 'OC' },
    { label: 'Room', to: '/admin/rooms', icon: 'RT' },
  ]
  
  if (isAdmin.value) {
    base.unshift({ label: 'Tổng quan', to: '/admin', icon: 'DB' })
    base.push(
      { label: 'Report', to: '/admin/reports', icon: 'RP' },
      { label: 'Payment', to: '/admin/payments', icon: '$' },
      { label: 'Users', to: '/admin/users', icon: 'US' },
      { label: 'Settings', to: '/admin/settings', icon: 'ST' },
    )
  }
  
  return base
})

const pageTitle = computed(() => route.meta.title || 'Dashboard')

const getLinkClass = (item) => {
  const active = route.path === item.to || (item.to === '/admin/questions' && route.path === '/admin/questions')
  const base = ['flex', 'items-center', 'gap-3', 'rounded-2xl', 'border', 'px-3', 'py-3', 'transition', 'duration-300', 'hover:-translate-y-0.5']
  if (!active) return [...base, 'border-transparent', 'text-[var(--muted)]', 'hover:border-[var(--border)]', 'hover:bg-[var(--surface)]', 'hover:text-[var(--text)]']
  return [...base, 'border-[var(--border-strong)]', 'bg-[var(--chip-active)]', 'text-[var(--text)]', 'shadow-[0_14px_34px_rgba(155,44,255,0.14)]']
}
</script>
