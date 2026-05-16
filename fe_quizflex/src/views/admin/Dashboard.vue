<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ roleLabels[currentRole] }} Dashboard</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tổng quan hệ thống</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Dashboard mock theo role: Admin xem hệ thống, VIP xem quiz/room của mình, user thường xem tiến độ học và lời mời nâng cấp.</p>
        </div>
        <div class="flex flex-wrap gap-3"><router-link class="btn-ghost" to="/admin/questions">Kho quiz</router-link><router-link class="btn-primary" to="/admin/questions/create">Tạo quiz</router-link></div>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article v-for="stat in stats" :key="stat.label" class="group relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl transition duration-300 hover:-translate-y-1 hover:border-[var(--border-strong)] hover:shadow-[0_24px_70px_rgba(0,0,0,0.24)]">
        <div class="absolute -right-12 -top-12 h-32 w-32 rounded-full bg-[var(--primary)]/15 blur-3xl transition group-hover:scale-125"></div>
        <p class="relative z-10 text-sm font-black text-[var(--muted)]">{{ stat.label }}</p>
        <strong class="relative z-10 mt-3 block text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ stat.value }}</strong>
        <span class="relative z-10 mt-2 block text-sm font-bold text-[var(--primary)]">{{ stat.hint }}</span>
      </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="mb-5 flex items-center justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Analytics</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">Hiệu suất quiz</h2></div><router-link class="btn-ghost" to="/admin/reports">Report</router-link></div>
        <div class="grid gap-4">
          <div v-for="row in reportRows" :key="row.quiz" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3"><div><b class="text-[var(--text)]">{{ row.quiz }}</b><p class="mt-1 text-sm text-[var(--muted)]">Owner: {{ row.owner }} • {{ row.attempts }} lượt làm</p></div><VisibilityBadge :value="row.visibility" /></div>
            <div class="h-3 overflow-hidden rounded-full bg-[var(--surface)]"><div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)]" :style="{ width: `${row.avgScore}%` }"></div></div>
            <div class="mt-2 flex justify-between text-xs font-bold text-[var(--muted)]"><span>Avg score {{ row.avgScore }}%</span><span>Completion {{ row.completion }}</span></div>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quick actions</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Luồng FE chính</h2>
        <div class="mt-5 grid gap-3">
          <router-link v-for="item in quickLinks" :key="item.to" :to="item.to" class="group rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition duration-300 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]"><b class="text-[var(--text)]">{{ item.title }}</b><p class="mt-1 text-sm leading-6 text-[var(--muted)]">{{ item.desc }}</p></router-link>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { currentRole, dashboardStats, reportRows, roleLabels } from '@/data/demoData'
const stats = dashboardStats[currentRole]
const quickLinks = [
  { title: 'Tạo quiz thủ công', desc: 'Có visibility Public / Private / Group', to: '/admin/questions/create' },
  { title: 'AI Generator', desc: 'Sinh quiz từ prompt và chuyển sang editor', to: '/admin/questions/ai' },
  { title: 'OCR Upload', desc: 'Upload PDF/Image, preview text và tạo quiz', to: '/admin/questions/ocr' },
  { title: 'Room host/member', desc: 'Hai layout riêng cho chủ phòng và thành viên', to: '/admin/rooms' },
]
</script>
