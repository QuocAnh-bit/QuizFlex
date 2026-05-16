<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Reports</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Báo cáo & analytics</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Kết quả theo quiz, report room sau session và export CSV/PDF mock. Export chưa thật, nhưng nút đã biết làm màu.</p></div><div class="flex gap-3"><button class="btn-ghost" type="button">Export CSV</button><button class="btn-primary" type="button">Export PDF</button></div></div>
    </div>
    <div class="grid gap-4 md:grid-cols-3"><article v-for="stat in summary" :key="stat.label" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]"><p class="text-sm font-bold text-[var(--muted)]">{{ stat.label }}</p><b class="mt-2 block text-4xl font-black text-[var(--text)]">{{ stat.value }}</b><span class="mt-2 block text-sm font-black text-[var(--primary)]">{{ stat.hint }}</span></article></div>
    <article class="overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl"><div class="grid grid-cols-[1.3fr_0.7fr_0.7fr_0.7fr] gap-4 border-b border-[var(--border)] bg-[var(--surface-soft)] px-5 py-4 text-xs font-black uppercase tracking-[0.15em] text-[var(--muted)]"><span>Quiz</span><span>Lượt làm</span><span>Điểm TB</span><span>Visibility</span></div><div class="divide-y divide-[var(--border)]"><div v-for="row in reportRows" :key="row.quiz" class="grid gap-4 p-5 md:grid-cols-[1.3fr_0.7fr_0.7fr_0.7fr] md:items-center"><div><b class="text-[var(--text)]">{{ row.quiz }}</b><p class="mt-1 text-sm text-[var(--muted)]">{{ row.owner }} • completion {{ row.completion }}</p></div><span class="font-black text-[var(--text)]">{{ row.attempts }}</span><span class="font-black text-[var(--primary)]">{{ row.avgScore }}%</span><VisibilityBadge :value="row.visibility" /></div></div></article>
  </section>
</template>
<script setup>
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { reportRows } from '@/data/demoData'
const summary = [{ label: 'Lượt làm bài', value: '352', hint: '+18%' }, { label: 'Điểm trung bình', value: '79%', hint: 'Ổn áp' }, { label: 'Room hoàn tất', value: '12', hint: 'Tuần này' }]
</script>
