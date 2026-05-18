<template>
  <section class="grid gap-6 py-8">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">My results</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Lịch sử bài làm</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Xem lại điểm, số câu đúng, thời gian làm và visibility của quiz đã tham gia.</p></div>
    </div>
    <div class="grid gap-4 md:grid-cols-4"><article v-for="stat in stats" :key="stat.label" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]"><p class="text-sm font-bold text-[var(--muted)]">{{ stat.label }}</p><b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ stat.value }}</b></article></div>
    <article class="grid gap-4"><div v-for="item in resultHistory" :key="item.id" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl"><div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="text-xl font-black text-[var(--text)]">{{ item.quiz }}</h2><p class="mt-1 text-sm text-[var(--muted)]">{{ item.date }} • {{ item.correct }}/{{ item.total }} câu đúng • {{ item.time }}</p></div><VisibilityBadge :value="item.visibility" /></div><div class="mt-4 h-3 overflow-hidden rounded-full bg-[var(--surface-soft)]"><div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)]" :style="{ width: `${item.score}%` }"></div></div><div class="mt-2 text-right text-sm font-black text-[var(--primary)]">{{ item.score }} điểm</div></div></article>
  </section>
</template>
<script setup>
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { resultHistory } from '@/data/demoData'
const average = Math.round(resultHistory.reduce((sum, item) => sum + item.score, 0) / resultHistory.length)
const stats = [{ label: 'Bài đã làm', value: resultHistory.length }, { label: 'Điểm TB', value: `${average}%` }, { label: 'Tốt nhất', value: `${Math.max(...resultHistory.map((item) => item.score))}%` }, { label: 'Room quiz', value: resultHistory.filter((item) => item.visibility === 'group').length }]
</script>
