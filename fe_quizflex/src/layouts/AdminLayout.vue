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

          <nav class="mt-5 grid gap-2 text-sm font-bold">
            <router-link v-for="item in mainMenu" :key="item.to" :to="item.to" :class="getLinkClass(item)">
              <span class="grid h-9 w-9 place-items-center rounded-2xl bg-[var(--surface-soft)] text-xs font-black text-[var(--primary)]">{{ item.icon }}</span>
              <span>{{ item.label }}</span>
            </router-link>

            <div class="mt-4 border-t border-[var(--border)] pt-4">
              <p class="px-3 text-[11px] font-black uppercase tracking-[0.22em] text-[var(--muted)]">PHÒNG HỌC</p>
              <div class="mt-2 grid gap-2">
                <router-link v-for="item in roomMenu" :key="item.label" :to="item.to" :class="getLinkClass(item)">
                  <span class="grid h-9 w-9 place-items-center rounded-2xl bg-[var(--surface-soft)] text-[var(--primary)]">
                    <NavIcon :name="item.icon" />
                  </span>
                  <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                  <span
                    v-if="item.badge !== null"
                    class="rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-2 py-0.5 text-[11px] font-black text-[var(--primary)]"
                  >
                    {{ item.badge }}
                  </span>
                  <span
                    v-if="item.live"
                    class="h-2.5 w-2.5 rounded-full bg-[var(--danger)] shadow-[0_0_14px_var(--danger)]"
                    title="Có live room đang diễn ra"
                  ></span>
                </router-link>
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
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import BrandLogo from '@/components/common/BrandLogo.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import { adminRoomApi } from '@/services/api'

const route = useRoute()
const homeworkRoomCount = ref(0)
const activeLiveRoomCount = ref(0)

const mainMenu = computed(() => [
  { label: 'Tổng quan', to: '/admin', icon: 'DB' },
  { label: 'Kho quiz', to: '/admin/questions', icon: 'QZ' },
  { label: 'Tạo quiz', to: '/admin/questions/create', icon: '+' },
  { label: 'AI Generator', to: '/admin/questions/ai', icon: 'AI' },
  { label: 'OCR Upload', to: '/admin/questions/ocr', icon: 'OC' },
  { label: 'Report', to: '/admin/reports', icon: 'RP' },
  { label: 'Payment', to: '/admin/payments', icon: '$' },
  { label: 'Users', to: '/admin/users', icon: 'US' },
  { label: 'Settings', to: '/admin/settings', icon: 'ST' },
])

const roomMenu = computed(() => [
  {
    label: 'Homework rooms',
    to: '/admin/rooms/homework',
    icon: 'home',
    badge: homeworkRoomCount.value,
  },
  {
    label: 'Live rooms',
    to: '/admin/rooms/live',
    icon: 'broadcast',
    badge: null,
    live: activeLiveRoomCount.value > 0,
  },
])

const pageTitle = computed(() => route.meta.title || 'Dashboard')

const NavIcon = defineComponent({
  props: { name: { type: String, required: true } },
  setup(props) {
    return () => {
      const common = {
        class: 'h-4 w-4',
        viewBox: '0 0 24 24',
        fill: 'none',
        stroke: 'currentColor',
        'stroke-width': '2',
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
      }

      if (props.name === 'broadcast') {
        return h('svg', common, [
          h('path', { d: 'M4.9 19.1a10 10 0 0 1 0-14.2' }),
          h('path', { d: 'M7.8 16.2a6 6 0 0 1 0-8.4' }),
          h('circle', { cx: '12', cy: '12', r: '2' }),
          h('path', { d: 'M16.2 7.8a6 6 0 0 1 0 8.4' }),
          h('path', { d: 'M19.1 4.9a10 10 0 0 1 0 14.2' }),
        ])
      }

      return h('svg', common, [
        h('path', { d: 'M3 10.5 12 3l9 7.5' }),
        h('path', { d: 'M5 10v10h14V10' }),
        h('path', { d: 'M9 20v-6h6v6' }),
      ])
    }
  },
})

const getLinkClass = (item) => {
  const active = route.path === item.to || (item.to === '/admin/questions' && route.path === '/admin/questions')
  const base = ['flex', 'items-center', 'gap-3', 'rounded-2xl', 'border', 'px-3', 'py-3', 'transition', 'duration-300', 'hover:-translate-y-0.5']
  if (!active) return [...base, 'border-transparent', 'text-[var(--muted)]', 'hover:border-[var(--border)]', 'hover:bg-[var(--surface)]', 'hover:text-[var(--text)]']
  return [...base, 'border-[var(--border-strong)]', 'bg-[var(--chip-active)]', 'text-[var(--text)]', 'shadow-[0_14px_34px_rgba(155,44,255,0.14)]']
}

const loadRoomBadges = async () => {
  try {
    const [homework, live] = await Promise.all([
      adminRoomApi.getHomeworkRooms({ per_page: 5 }),
      adminRoomApi.getLiveRooms({ status: 'playing', per_page: 5 }),
    ])

    homeworkRoomCount.value = Number(homework?.meta?.total ?? homework?.items?.length ?? 0)
    activeLiveRoomCount.value = Number(live?.meta?.total ?? live?.items?.length ?? 0)
  } catch {
    homeworkRoomCount.value = 0
    activeLiveRoomCount.value = 0
  }
}

onMounted(loadRoomBadges)
</script>
