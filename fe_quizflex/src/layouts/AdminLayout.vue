<template>
  <div class="app-shell min-h-screen bg-[#F8FAFC]">
    <div class="grid min-h-screen lg:grid-cols-[260px_minmax(0,1fr)]">

      <!-- =========================================================
           ADMIN SIDEBAR
      ========================================================== -->
      <aside class="hidden border-r border-slate-200 bg-white p-4 lg:block">
        <div class="sticky top-4 space-y-5">

          <!-- Logo Header -->
          <div class="border-b border-slate-100 px-2 pb-3">
            <BrandLogo to="/admin" />
            <p class="mt-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">
              Management Console
            </p>
          </div>

          <!-- Back To User Page -->
          <router-link
            to="/"
            class="group flex w-full items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700 transition hover:border-purple-200 hover:bg-purple-50 hover:text-[#7C3AED]"
          >
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-white text-slate-500 shadow-sm transition group-hover:bg-[#7C3AED] group-hover:text-white">
              <ArrowLeft class="h-4 w-4" />
            </span>
            <span class="flex-1">Về trang người dùng</span>
          </router-link>

          <!-- MAIN NAVIGATION -->
          <nav class="space-y-3 text-xs font-semibold">
            <!-- Tổng quan -->
            <router-link
              to="/admin"
              :class="[
                'group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-semibold transition',
                route.path === '/admin'
                  ? 'bg-purple-50 font-bold text-[#7C3AED] shadow-sm'
                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <span
                :class="[
                  'grid h-7 w-7 shrink-0 place-items-center rounded-md transition',
                  route.path === '/admin'
                    ? 'bg-[#7C3AED] text-white shadow-sm'
                    : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200 group-hover:text-slate-700'
                ]"
              >
                <LayoutDashboard class="h-4 w-4" :stroke-width="2" />
              </span>
              <span class="min-w-0 flex-1 truncate">Tổng quan</span>
            </router-link>

            <!-- GROUPS -->
            <div v-for="group in menu" :key="group.label" class="overflow-hidden">
              <button
                type="button"
                class="group flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-left transition hover:bg-slate-50"
                @click="toggleGroup(group.label)"
              >
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 transition group-hover:text-[#7C3AED]">
                  {{ group.label }}
                </span>
                <ChevronDown
                  :class="[
                    'h-3.5 w-3.5 text-slate-400 transition-transform duration-200',
                    isGroupOpen(group.label) ? 'rotate-180 text-[#7C3AED]' : ''
                  ]"
                />
              </button>

              <div v-show="isGroupOpen(group.label)" class="grid gap-0.5 mt-0.5">
                <router-link
                  v-for="item in group.items"
                  :key="item.to"
                  :to="item.to"
                  :class="[
                    'group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-semibold transition',
                    isItemActive(item)
                      ? 'bg-purple-50 font-bold text-[#7C3AED] shadow-sm'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                  ]"
                >
                  <span
                    :class="[
                      'grid h-7 w-7 shrink-0 place-items-center rounded-md transition',
                      isItemActive(item)
                        ? 'bg-[#7C3AED] text-white shadow-sm'
                        : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200 group-hover:text-slate-700'
                    ]"
                  >
                    <component :is="item.icon" class="h-4 w-4" :stroke-width="2" />
                  </span>

                  <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>

                  <span
                    v-if="item.badge !== undefined && item.badge !== null && item.badge > 0"
                    class="ml-auto shrink-0 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700"
                  >
                    {{ item.badge }}
                  </span>
                </router-link>
              </div>
            </div>
          </nav>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <section class="min-w-0 p-4 sm:p-6 lg:p-8">
        <!-- MOBILE HEADER -->
        <header class="mb-5 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:hidden">
          <div class="flex items-center justify-between gap-4">
            <BrandLogo to="/admin" />
            <div class="flex items-center gap-2">
              <NotificationBell />
              <ThemeToggle />
              <router-link to="/" class="btn-ghost flex items-center gap-1.5 px-3 py-1.5 text-xs">
                <ArrowLeft class="h-3.5 w-3.5" />
                <span>Trang người dùng</span>
              </router-link>
            </div>
          </div>
        </header>

        <!-- DESKTOP HEADER -->
        <header class="mb-6 hidden items-center justify-between gap-4 lg:flex">
          <div>
            <template v-if="!route.meta?.hideTitle">
              <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">QuizFlex Admin</p>
            </template>
            <template v-else>
              <div class="flex items-center gap-2 text-xs font-bold">
                <span class="text-[#7C3AED] uppercase tracking-wider">QuizFlex Admin</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-600 font-semibold">{{ pageTitle }}</span>
              </div>
            </template>
          </div>

          <div class="flex items-center gap-2.5">
            <NotificationBell />
            
            <router-link to="/" class="btn-ghost flex items-center gap-1.5 text-xs">
              <ArrowLeft class="h-3.5 w-3.5" />
              Trang người dùng
            </router-link>
          </div>
        </header>

        <!-- PAGE CONTENT -->
        <slot />
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { tokenStorage } from '@/services/api'
import {
  ArrowLeft,
  BookOpen,
  ChevronDown,
  CreditCard,
  Database,
  Flag,
  HelpCircle,
  LayoutDashboard,
  Package,
  Settings,
  Shield,
  Users,
  Video,
} from 'lucide-vue-next'

import BrandLogo from '@/components/common/BrandLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import NotificationBell from '@/components/common/NotificationBell.vue'

const route = useRoute()
const pendingQuestionsCount = ref(0)
const pendingQuizzesCount = ref(0)
const pendingReportsCount = ref(0)

const openGroups = ref({
  'Quản lý nội dung': true,
  'Phòng học & thi đấu': false,
  'Hệ thống': false,
})

const toggleGroup = (label) => {
  openGroups.value[label] = !openGroups.value[label]
}

const isGroupOpen = (label) => {
  return !!openGroups.value[label]
}

const fetchPendingCounts = async () => {
  try {
    if (!tokenStorage.get()) return

    const [questionRes, quizRes, reportRes] = await Promise.allSettled([
      api.get('/admin/question-bank-requests', { params: { status: 'pending', per_page: 1 } }),
      api.get('/admin/quiz-review-requests', { params: { status: 'pending', per_page: 1 } }),
      api.get('/admin/report-tickets/count'),
    ])

    if (questionRes.status === 'fulfilled') {
      const p = questionRes.value.data?.data ?? questionRes.value.data
      pendingQuestionsCount.value = p?.stats?.pending ?? 0
    }

    if (quizRes.status === 'fulfilled') {
      const p = quizRes.value.data?.data ?? quizRes.value.data
      pendingQuizzesCount.value = p?.stats?.pending ?? 0
    }

    if (reportRes.status === 'fulfilled') {
      const p = reportRes.value.data
      pendingReportsCount.value = p?.count ?? p?.question_pending ?? 0
    }
  } catch (err) {
    console.error('Không thể lấy số lượng hàng chờ kiểm duyệt:', err)
  }
}

const handleRealtimeNotification = (event) => {
  const notification = event.detail
  if (notification?.type === 'report_created') {
    pendingReportsCount.value++
  }
  if (notification?.type === 'question_submitted') {
    pendingQuestionsCount.value++
  }
  if (notification?.type === 'quiz_submitted') {
    pendingQuizzesCount.value++
  }
  if (notification?.type === 'report_resolved' || notification?.type === 'report_action' || notification?.type === 'question_moderated') {
    fetchPendingCounts()
  }
}

const handleNotificationsUpdated = () => {
  fetchPendingCounts()
}

const menu = computed(() => [
  {
    label: 'Quản lý nội dung',
    items: [
      {
        label: 'Ngân hàng câu hỏi',
        to: '/admin/question-bank',
        icon: HelpCircle,
        badge: pendingQuestionsCount.value,
      },
      {
        label: 'Quiz',
        to: '/admin/quizzes',
        icon: Package,
        badge: pendingQuizzesCount.value,
      },
      {
        label: 'Báo cáo',
        to: '/admin/reports',
        icon: Flag,
        badge: pendingReportsCount.value,
      },
      {
        label: 'Bộ môn',
        to: '/admin/subjects',
        icon: BookOpen,
      },
      {
        label: 'Dữ liệu chương trình',
        to: '/admin/rag',
        icon: Database,
      },
    ],
  },
  {
    label: 'Phòng học & thi đấu',
    items: [
      { label: 'Phòng bài tập', to: '/admin/rooms/homework', icon: BookOpen },
      { label: 'Phòng thi đấu', to: '/admin/rooms/live', icon: Video },
    ],
  },
  {
    label: 'Hệ thống',
    items: [
      { label: 'Người dùng', to: '/admin/users', icon: Users },
      { label: 'Giao dịch & doanh thu', to: '/admin/payments', icon: CreditCard },
      { label: 'Cài đặt', to: '/admin/settings', icon: Settings },
    ],
  },
])

const pageTitle = computed(() => route.meta.title || 'Dashboard')

const isItemActive = (item) => {
  if (item.to === '/admin') return route.path === '/admin'
  return route.path.startsWith(item.to)
}

const openCurrentGroup = () => {
  menu.value.forEach((group) => {
    const hasActiveItem = group.items.some((item) => isItemActive(item))
    if (hasActiveItem) {
      openGroups.value[group.label] = true
    }
  })
}

onMounted(() => {
  fetchPendingCounts()
  openCurrentGroup()
  window.addEventListener('realtime-notification', handleRealtimeNotification)
  window.addEventListener('notifications-updated', handleNotificationsUpdated)
})

onUnmounted(() => {
  window.removeEventListener('realtime-notification', handleRealtimeNotification)
  window.removeEventListener('notifications-updated', handleNotificationsUpdated)
})
</script>
