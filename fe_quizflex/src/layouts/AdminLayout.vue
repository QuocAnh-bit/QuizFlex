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
            
          </div>

          <nav class="mt-5 space-y-4 text-sm font-bold">
            <div v-for="group in menu" :key="group.label" class="rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface)]">
              <button type="button" @click="toggleGroup(group.label)" class="flex w-full items-center justify-between gap-3 px-4 py-4 text-left text-sm font-black tracking-[0.1em] text-[var(--primary)]">
                <span>{{ group.label }}</span>
                <span :class="['transition-transform duration-300', expandedGroups.includes(group.label) ? 'rotate-180' : 'rotate-0']">▼</span>
              </button>

              <div :class="['overflow-hidden transition-all duration-300', expandedGroups.includes(group.label) ? 'max-h-[1000px] py-3' : 'max-h-0']">
                <div class="grid gap-2 px-2">
                  <router-link v-for="item in group.items" :key="item.to" :to="item.to" :class="[getLinkClass(item), 'group transition-all duration-200']">
                    <span :class="['grid h-9 w-9 place-items-center rounded-2xl text-xs font-black transition-colors duration-300', isItemActive(item) ? 'bg-[var(--primary)] text-white' : 'bg-[var(--surface-soft)] text-[var(--primary)]', 'group-hover:bg-[var(--primary)]', 'group-hover:text-white']">
                      <template v-if="item.icon === 'homework'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                        </svg>
                      </template>
                      <template v-else-if="item.icon === 'live'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9" />
                          <path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5" />
                          <circle cx="12" cy="12" r="2" />
                          <path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5" />
                          <path d="M19.1 4.9c3.9 3.9 3.9 10.3 0 14.2" />
                        </svg>
                      </template>
                      <template v-else>{{ item.icon }}</template>
                    </span>
                    <span :class="['transition-colors duration-300', isItemActive(item) ? 'text-[var(--text)]' : 'text-[var(--muted)]', 'group-hover:text-[var(--text)]']">{{ item.label }}</span>
                    <span v-if="(item.label === 'Quản lý báo cáo' && reportCount > 0) || (item.badge !== undefined && item.badge !== null && item.badge !== 0)" class="ml-auto rounded-full border border-[var(--border)] bg-[var(--primary)]/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-[var(--primary)]">
                      {{ item.label === 'Quản lý báo cáo' ? reportCount : item.badge }}
                    </span>
                  </router-link>
                </div>
              </div>
            </div>
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
            <p class="text-sm font-black uppercase tracking-[0.2em] text-[var(--primary)]">QuizFlex admin</p>
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
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import BrandLogo from '@/components/common/BrandLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'

const route = useRoute()
const expandedGroups = ref(['Quản lý nội dung'])

const reportCount = ref(0);
const reportBadge = computed(() => reportCount.value)

const fetchReportCount = async () => {
  try {
    const token = localStorage.getItem('quizflex_access_token');
    const { data } = await axios.get('/api/admin/report-tickets/count', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
    reportCount.value = data.count || 0;
  } catch (e) {
    console.error('Lỗi khi lấy số lượng báo cáo:', e);
  }
}

const handleRealtimeNotification = (e) => {
  const notification = e.detail
  if (notification?.type === 'report_created') {
    reportCount.value++
  } else if (notification?.type === 'report_resolved' || notification?.type === 'report_action') {
    fetchReportCount()
  }
}

const handleNotificationsUpdated = () => {
  fetchReportCount()
}

onMounted(() => {
  fetchReportCount()
  window.addEventListener('realtime-notification', handleRealtimeNotification)
  window.addEventListener('notifications-updated', handleNotificationsUpdated)
})

onUnmounted(() => {
  window.removeEventListener('realtime-notification', handleRealtimeNotification)
  window.removeEventListener('notifications-updated', handleNotificationsUpdated)
})

const menu = computed(() => [
  {
    label: 'Quản lý nội dung',
    items: [
      { label: 'Tổng quan', to: '/admin', icon: 'DB' },
      { label: 'Quản lý Quiz', to: '/admin/quizzes', icon: 'QL' },
      { label: 'Kho quiz', to: '/admin/questions', icon: 'QZ' },
      { label: 'Tạo quiz', to: '/admin/questions/create', icon: '+' },
      { label: 'AI Generator', to: '/admin/questions/ai', icon: 'AI' },
      { label: 'OCR Upload', to: '/admin/questions/ocr', icon: 'OC' },
      { 
        label: 'Quản lý báo cáo', 
        to: '/admin/report-tickets', 
        icon: '🚩', 
        badge: reportBadge.value
      },
    ],
  },
  {
    label: 'Phòng học',
    items: [
      { label: 'Phòng bài tập', to: '/admin/rooms/homework', icon: 'homework' },
      { label: 'Phòng thi đấu', to: '/admin/rooms/live', icon: 'live' },
    ],
  },
  {
    label: 'Phân tích & chăm sóc',
    items: [
      { label: 'Report', to: '/admin/reports', icon: 'RP' },
      { label: 'Payment', to: '/admin/payments', icon: '$' },
      { label: 'Users', to: '/admin/users', icon: 'US' },
      { label: 'Settings', to: '/admin/settings', icon: 'ST' },
    ],
  },
])

const pageTitle = computed(() => route.meta.title || 'Dashboard')

const toggleGroup = (groupLabel) => {
  if (expandedGroups.value.includes(groupLabel)) {
    expandedGroups.value = expandedGroups.value.filter((label) => label !== groupLabel)
  } else {
    expandedGroups.value.push(groupLabel)
  }
}

const isItemActive = (item) => route.path === item.to || (item.to === '/admin/questions' && route.path === '/admin/questions')

const getLinkClass = (item) => {
  const active = isItemActive(item)
  const base = ['flex', 'items-center', 'gap-3', 'rounded-2xl', 'border', 'px-3', 'py-3', 'transition', 'duration-300', 'hover:-translate-y-0.5']
  if (!active) return [...base, 'border-transparent', 'text-[var(--muted)]', 'hover:border-[var(--border)]', 'hover:bg-[var(--surface)]', 'hover:text-[var(--text)]']
  return [...base, 'border-[var(--border-strong)]', 'bg-[var(--surface)]', 'text-[var(--text)]']
}
</script>
