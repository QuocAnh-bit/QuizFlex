<template>
  <span
    class="inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1 text-xs font-black"
    :class="meta.className"
  >
    <span class="h-2 w-2 rounded-full" :class="meta.dotClass"></span>
    <span>{{ meta.text }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
})

const statusMap = {
  active: { text: 'Active', className: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', dotClass: 'bg-emerald-400' },
  joined: { text: 'Joined', className: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', dotClass: 'bg-emerald-400' },
  published: { text: 'Published', className: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', dotClass: 'bg-emerald-400' },
  completed: { text: 'Completed', className: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', dotClass: 'bg-emerald-400' },
  finished: { text: 'Finished', className: 'border-sky-500/25 bg-sky-500/10 text-sky-300', dotClass: 'bg-sky-300' },
  playing: { text: 'Playing', className: 'border-violet-500/25 bg-violet-500/10 text-violet-300', dotClass: 'bg-violet-300' },
  waiting: { text: 'Waiting', className: 'border-amber-500/25 bg-amber-500/10 text-amber-300', dotClass: 'bg-amber-300' },
  draft: { text: 'Draft', className: 'border-slate-500/25 bg-slate-500/10 text-slate-300', dotClass: 'bg-slate-300' },
  closed: { text: 'Closed', className: 'border-rose-500/25 bg-rose-500/10 text-rose-300', dotClass: 'bg-rose-300' },
  expired: { text: 'Expired', className: 'border-rose-500/25 bg-rose-500/10 text-rose-300', dotClass: 'bg-rose-300' },
  in_progress: { text: 'In progress', className: 'border-violet-500/25 bg-violet-500/10 text-violet-300', dotClass: 'bg-violet-300' },
  owner: { text: 'Chủ room', className: 'border-violet-500/25 bg-violet-500/10 text-violet-300', dotClass: 'bg-violet-300' },
  member: { text: 'Thành viên', className: 'border-sky-500/25 bg-sky-500/10 text-sky-300', dotClass: 'bg-sky-300' },
}

const meta = computed(() => {
  const key = String(props.value || '').toLowerCase()
  const fallbackText = props.label || props.value || 'Status'
  return statusMap[key] || {
    text: fallbackText,
    className: 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]',
    dotClass: 'bg-[var(--muted)]',
  }
})
</script>
