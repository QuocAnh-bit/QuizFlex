<template>
  <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div><h2 class="text-base font-black text-slate-900">Kiểm tra truy xuất RAG</h2><p class="mt-1 text-xs leading-5 text-slate-500">Chạy truy xuất thật qua embedding, Qdrant và dữ liệu MySQL. Thao tác này không thay đổi dữ liệu.</p></div>
    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_150px_2fr_auto]" @submit.prevent="submit">
      <select v-model="form.subject" class="field"><option value="">Chọn môn học</option><option v-for="subject in subjects" :key="subject" :value="subject">{{ subject }}</option></select>
      <select v-model.number="form.grade" class="field"><option :value="null">Lớp</option><option v-for="grade in grades" :key="grade" :value="grade">Lớp {{ grade }}</option></select>
      <input v-model="form.query" class="field" placeholder="Ví dụ: phương trình bậc nhất" maxlength="2000" />
      <button type="submit" :disabled="isLoading || !canSubmit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7C3AED] px-4 py-2 text-xs font-black text-white shadow-sm transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"><Search :class="['h-4 w-4', { 'animate-pulse': isLoading }]" />{{ isLoading ? 'Đang truy xuất' : 'Kiểm tra truy xuất' }}</button>
    </form>
    <p v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">{{ errorMessage }}</p>
    <div v-if="hasSearched && !isLoading && !results.length" class="rounded-xl border border-dashed border-slate-200 p-10 text-center text-xs"><Inbox class="mx-auto mb-2 h-7 w-7 text-slate-300" /><p class="font-bold text-slate-600">Không tìm thấy ngữ cảnh phù hợp</p><p class="mt-1 text-slate-400">Hãy thử thay đổi từ khóa, môn học hoặc lớp.</p></div>
    <div v-else-if="results.length" class="space-y-3"><p class="text-xs font-bold text-slate-500">Top {{ results.length }} kết quả. Điểm là similarity score của retrieval, không phải tỷ lệ chính xác.</p><article v-for="(result, index) in results" :key="result.chunk_id" class="rounded-xl border border-slate-200 p-4"><div class="flex items-start justify-between gap-3"><div class="flex items-center gap-2"><span class="grid h-7 min-w-7 place-items-center rounded-lg bg-purple-50 px-1 text-xs font-black text-[#7C3AED]">#{{ index + 1 }}</span><p class="font-bold text-slate-800">{{ result.subject }} · {{ gradeLabel(result) }}</p></div><span class="rounded-lg bg-emerald-50 px-2 py-1 text-xs font-black text-emerald-700">{{ scoreLabel(result.score) }}</span></div><p class="mt-3 text-xs font-semibold text-slate-600">{{ [result.domain, result.topic, result.section].filter(Boolean).join(' · ') }}</p><p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ result.content }}</p><div class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500"><span class="font-bold text-slate-600">Nguồn: </span>{{ result.document_title || 'Chưa xác định tài liệu' }}<span v-if="pageLabel(result)"> · {{ pageLabel(result) }}</span></div></article></div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Inbox, Search } from 'lucide-vue-next'
import { adminRagApi } from '@/services/api'

const props = defineProps({ subjects: { type: Array, default: () => [] } })
const grades = Array.from({ length: 12 }, (_, index) => index + 1)
const form = ref({ subject: '', grade: null, query: '' }); const results = ref([]); const isLoading = ref(false); const errorMessage = ref(''); const hasSearched = ref(false)
const canSubmit = computed(() => form.value.subject && form.value.grade && form.value.query.trim())
const gradeLabel = item => item.grade_min === item.grade_max ? `Lớp ${item.grade_min}` : `Lớp ${item.grade_min || '?'} - ${item.grade_max || '?'}`
const pageLabel = item => item.page_start === item.page_end ? `Trang ${item.page_start || ''}` : item.page_start || item.page_end ? `Trang ${item.page_start || '?'} - ${item.page_end || '?'}` : ''
const scoreLabel = score => `${(Math.max(0, Math.min(1, Number(score) || 0)) * 100).toFixed(1)}%`
const submit = async () => { if (!canSubmit.value || isLoading.value) return; isLoading.value = true; errorMessage.value = ''; results.value = []; hasSearched.value = true; try { results.value = await adminRagApi.testRetrieval({ subject: form.value.subject, grade: form.value.grade, query: form.value.query.trim() }) } catch (error) { errorMessage.value = error.response?.data?.message || 'Không thể kiểm tra truy xuất. Hãy kiểm tra kết nối RAG và thử lại.' } finally { isLoading.value = false } }
</script>

<style scoped>.field { @apply w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white; }</style>
