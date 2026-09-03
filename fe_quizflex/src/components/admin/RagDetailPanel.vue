<template>
  <div v-if="item" class="fixed inset-0 z-50 flex justify-end bg-slate-950/35 p-0" @click.self="$emit('close')">
    <aside class="flex h-full w-full max-w-xl flex-col bg-white shadow-2xl">
      <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <p class="text-[10px] font-black uppercase tracking-[.16em] text-[#7C3AED]">Dữ liệu chương trình</p>
          <h2 class="mt-1 text-lg font-black text-slate-900">{{ type === 'unit' ? 'Chi tiết đơn vị kiến thức' : 'Chi tiết đoạn truy xuất' }}</h2>
        </div>
        <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" @click="$emit('close')"><X class="h-5 w-5" /></button>
      </header>

      <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5 text-sm">
        <template v-if="type === 'unit'">
          <DetailGrid :rows="unitRows" />
          <DetailSection v-if="item.content" title="Nội dung"><MathText :content="item.content" class="whitespace-pre-wrap leading-7 text-slate-700" /></DetailSection>
          <DetailSection v-if="outcomes.length" title="Yêu cầu cần đạt"><ul class="space-y-2"><li v-for="(outcome, index) in outcomes" :key="index" class="flex gap-2 text-slate-700"><CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />{{ outcome }}</li></ul></DetailSection>
          <DetailSection v-if="item.document" title="Nguồn"><p class="font-semibold text-slate-700">{{ item.document.title }}</p><p v-if="pageLabel" class="mt-1 text-xs text-slate-500">{{ pageLabel }}</p></DetailSection>
        </template>
        <template v-else>
          <DetailGrid :rows="chunkRows" />
          <DetailSection title="Nội dung"><MathText :content="item.content || ''" class="whitespace-pre-wrap leading-7 text-slate-700" /></DetailSection>
          <DetailSection title="Embedding text"><MathText :content="item.embedding_text || 'Chưa có dữ liệu'" class="whitespace-pre-wrap leading-7 text-slate-600" /></DetailSection>
          <div v-if="item.embedding_error" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs leading-5 text-rose-700"><div class="mb-1 flex items-center gap-1.5 font-black"><AlertTriangle class="h-4 w-4" /> Lỗi embedding</div>{{ item.embedding_error }}</div>
        </template>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { computed, defineComponent } from 'vue'
import { AlertTriangle, CheckCircle2, X } from 'lucide-vue-next'
import MathText from '@/components/MathText.vue'

const DetailGrid = defineComponent({
  props: { rows: { type: Array, default: () => [] } },
  template: `<dl class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3"><div v-for="row in rows.filter(row => row.value)" :key="row.label" class="min-w-0"><dt class="text-[10px] font-black uppercase tracking-wide text-slate-400">{{ row.label }}</dt><dd class="mt-1 break-words font-semibold text-slate-700">{{ row.value }}</dd></div></dl>`,
})
const DetailSection = defineComponent({
  props: { title: { type: String, required: true } },
  template: `<section><h3 class="mb-2 text-[10px] font-black uppercase tracking-[.14em] text-slate-500">{{ title }}</h3><div class="rounded-xl border border-slate-200 bg-white p-4"><slot /></div></section>`,
})

const props = defineProps({ item: { type: Object, default: null }, type: { type: String, required: true } })
defineEmits(['close'])
const gradeLabel = (min, max) => !min && !max ? '' : min === max ? `Lớp ${min}` : `Lớp ${min || '?'} - ${max || '?'}`
const pageLabel = computed(() => {
  const { source_page_start: start, source_page_end: end } = props.item || {}
  return !start && !end ? '' : start === end ? `Trang ${start}` : `Trang ${start || '?'} - ${end || '?'}`
})
const outcomes = computed(() => Array.isArray(props.item?.learning_outcomes) ? props.item.learning_outcomes.filter(Boolean) : [])
const unitRows = computed(() => {
  const item = props.item || {}
  return [
    { label: 'Môn', value: item.subject }, { label: 'Lớp', value: gradeLabel(item.grade_min, item.grade_max) },
    { label: 'Cấp học', value: item.education_level }, { label: 'Loại kiến thức', value: item.type },
    { label: 'Lĩnh vực', value: item.domain }, { label: 'Chủ đề', value: item.topic },
    { label: 'Phần', value: item.section }, { label: 'Mục', value: item.subsection },
    { label: 'Tác phẩm', value: item.title }, { label: 'Tác giả', value: item.author },
    { label: 'Thể loại', value: item.genre }, { label: 'Loại ngữ liệu', value: item.selection_type },
  ]
})
const chunkRows = computed(() => {
  const item = props.item || {}; const unit = item.unit || {}
  return [
    { label: 'Chunk ID', value: item.id }, { label: 'Unit ID', value: item.unit_id },
    { label: 'Môn', value: unit.subject }, { label: 'Lớp', value: gradeLabel(unit.grade_min, unit.grade_max) },
    { label: 'Số token', value: item.estimated_tokens }, { label: 'Model', value: item.embedding_model },
    { label: 'Trạng thái', value: item.embedding_status }, { label: 'Qdrant Point', value: item.qdrant_point_id },
  ]
})
</script>
