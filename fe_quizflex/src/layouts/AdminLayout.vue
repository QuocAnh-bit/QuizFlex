<template>
  <div class="app-shell min-h-screen bg-[#F8FAFC]">
    <div class="grid min-h-screen lg:grid-cols-[260px_minmax(0,1fr)]">
      <!-- Admin Sidebar -->
      <aside class="hidden border-r border-slate-200 bg-white p-4 lg:block">
        <div class="sticky top-4 space-y-5">
          <!-- Logo Header -->
          <div class="pb-3 border-b border-slate-100 px-2">
            <BrandLogo to="/admin" />
            <p class="mt-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Management Console</p>
          </div>

          <!-- Navigation Groups -->
          <nav class="space-y-4 text-xs font-semibold">
            <div v-for="group in menu" :key="group.label" class="space-y-1">
              <div class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                {{ group.label }}
              </div>

              <div class="grid gap-0.5">
                <router-link
                  v-for="item in group.items"
                  :key="item.to"
                  :to="item.to"
                  :class="[
                    'flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-semibold transition',
                    isItemActive(item)
                      ? 'bg-purple-50 text-[#7C3AED] font-bold shadow-sm'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                  ]"
                >
                  <span
                    :class="[
                      'grid h-6 w-6 place-items-center rounded-md text-xs transition',
                      isItemActive(item) ? 'bg-[#7C3AED] text-white' : 'bg-slate-100 text-slate-600'
                    ]"
                  >
                    <template v-if="item.icon === 'homework'">
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                      </svg>
                    </template>
                    <template v-else-if="item.icon === 'live'">
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9" />
                        <path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5" />
                        <circle cx="12" cy="12" r="2" />
                        <path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5" />
                        <path d="M19.1 4.9c3.9 3.9 3.9 10.3 0 14.2" />
                      </svg>
                    </template>
                    <template v-else>{{ item.icon }}</template>
                  </span>
                  <span>{{ item.label }}</span>
                  <span
                    v-if="(item.label === 'Quản lý báo cáo' && reportCount > 0) || (item.badge !== undefined && item.badge !== null && item.badge !== 0)"
                    class="ml-auto rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700"
                  >
                    {{ item.label === 'Quản lý báo cáo' ? reportCount : item.badge }}
                  </span>
                </router-link>
              </div>
            </div>
          </nav>
        </div>
      </aside>

      <!-- Main Content Area -->
      <section class="min-w-0 p-4 sm:p-6 lg:p-8">
        <!-- Mobile Header -->
        <header class="mb-5 flex items-center justify-between gap-4 border-b border-slate-200 bg-white p-4 rounded-xl shadow-sm lg:hidden">
          <BrandLogo to="/admin" />
          <router-link class="btn-ghost text-xs py-1.5 px-3" to="/">Về trang chủ</router-link>
        </header>

        <!-- Desktop Header -->
        <header class="mb-6 hidden items-center justify-between gap-4 lg:flex">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">QuizFlex Admin</p>
            <h1 class="mt-0.5 text-2xl font-black text-slate-900">{{ pageTitle }}</h1>
          </div>
          <div class="flex items-center gap-2.5">
            <router-link class="btn-ghost text-xs" to="/">Trang chủ</router-link>
            <router-link class="btn-primary text-xs" to="/admin/questions/create">+ Tạo quiz mới</router-link>
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

const route = useRoute()
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
      { label: 'Tổng quan', to: '/admin', icon: '📊' },
      { label: 'Quản lý Quiz', to: '/admin/quizzes', icon: '📑' },
      { label: 'Kho quiz', to: '/admin/questions', icon: '📦' },
      { label: 'Tạo quiz', to: '/admin/questions/create', icon: '➕' },
      { label: 'AI Generator', to: '/admin/questions/ai', icon: '✨' },
      { label: 'OCR Upload', to: '/admin/questions/ocr', icon: '📷' },
      { 
        label: 'Quản lý báo cáo', 
        to: '/admin/report-tickets', 
        icon: '🚩', 
        badge: reportBadge.value
      },
    ],
  },
  {
    label: 'Phòng học & thi đấu',
    items: [
      { label: 'Phòng bài tập', to: '/admin/rooms/homework', icon: 'homework' },
      { label: 'Phòng thi đấu', to: '/admin/rooms/live', icon: 'live' },
    ],
  },
  {
    label: 'Phân tích & hệ thống',
    items: [
      { label: 'Báo cáo doanh thu', to: '/admin/reports', icon: '📈' },
      { label: 'Giao dịch thanh toán', to: '/admin/payments', icon: '💳' },
      { label: 'Người dùng', to: '/admin/users', icon: '👥' },
      { label: 'Cài đặt hệ thống', to: '/admin/settings', icon: '⚙️' },
    ],
  },
])

const pageTitle = computed(() => route.meta.title || 'Dashboard')

const isItemActive = (item) => route.path === item.to || (item.to === '/admin/questions' && route.path === '/admin/questions')
</script>
