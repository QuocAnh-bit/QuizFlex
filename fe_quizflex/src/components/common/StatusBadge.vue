<template>
  <span
    class="inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
    :class="meta.className"
  >
    <span class="h-1.5 w-1.5 rounded-full" :class="meta.dotClass"></span>
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
  active: { text: 'Hoạt động', className: 'border-emerald-200 bg-emerald-50 text-emerald-700', dotClass: 'bg-emerald-500' },
  open: { text: 'Đang mở', className: 'border-emerald-200 bg-emerald-50 text-emerald-700', dotClass: 'bg-emerald-500' },
  joined: { text: 'Đã tham gia', className: 'border-emerald-200 bg-emerald-50 text-emerald-700', dotClass: 'bg-emerald-500' },
  public: { text: 'Công khai', className: 'border-emerald-200 bg-emerald-50 text-emerald-700', dotClass: 'bg-emerald-500' },
  published: { text: 'Công khai', className: 'border-emerald-200 bg-emerald-50 text-emerald-700', dotClass: 'bg-emerald-500' },
  approved: { text: 'Công khai', className: 'border-emerald-200 bg-emerald-50 text-emerald-700', dotClass: 'bg-emerald-500' },
  private: { text: 'Riêng tư', className: 'border-slate-200 bg-slate-100 text-slate-700', dotClass: 'bg-slate-500' },
  pending_review: { text: 'Đang chờ kiểm duyệt', className: 'border-amber-200 bg-amber-50 text-amber-700', dotClass: 'bg-amber-500' },
  pending: { text: 'Đang chờ kiểm duyệt', className: 'border-amber-200 bg-amber-50 text-amber-700', dotClass: 'bg-amber-500' },
  rejected: { text: 'Yêu cầu bị từ chối', className: 'border-rose-200 bg-rose-50 text-rose-700', dotClass: 'bg-rose-500' },
  completed: { text: 'Hoàn thành', className: 'border-emerald-200 bg-emerald-50 text-emerald-700', dotClass: 'bg-emerald-500' },
  finished: { text: 'Đã kết thúc', className: 'border-slate-200 bg-slate-100 text-slate-700', dotClass: 'bg-slate-500' },
  playing: { text: 'Đang diễn ra', className: 'border-purple-200 bg-purple-50 text-purple-700', dotClass: 'bg-purple-600' },
  waiting: { text: 'Đang chờ', className: 'border-amber-200 bg-amber-50 text-amber-700', dotClass: 'bg-amber-500' },
  draft: { text: 'Bản nháp', className: 'border-slate-200 bg-slate-100 text-slate-600', dotClass: 'bg-slate-400' },
  closed: { text: 'Đã đóng', className: 'border-red-200 bg-red-50 text-red-700', dotClass: 'bg-red-500' },
  expired: { text: 'Hết hạn', className: 'border-red-200 bg-red-50 text-red-700', dotClass: 'bg-red-500' },
  banned: { text: 'Đã khóa', className: 'border-red-200 bg-red-50 text-red-700', dotClass: 'bg-red-500' },
  in_progress: { text: 'Đang làm', className: 'border-blue-200 bg-blue-50 text-blue-700', dotClass: 'bg-blue-500' },
  host: { text: 'Chủ phòng', className: 'border-purple-200 bg-purple-50 text-purple-700', dotClass: 'bg-purple-600' },
  member: { text: 'Thành viên', className: 'border-blue-200 bg-blue-50 text-blue-700', dotClass: 'bg-blue-500' },
}

const meta = computed(() => {
  const key = String(props.value || '').toLowerCase()
  const fallbackText = props.label || props.value || 'Status'
  return statusMap[key] || {
    text: fallbackText,
    className: 'border-slate-200 bg-slate-100 text-slate-700',
    dotClass: 'bg-slate-400',
  }
})
</script>
