<template>
  <Teleport to="body">
    <div class="picker-overlay fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/45 p-3 backdrop-blur-sm" @click.self="$emit('close')">
      <section class="picker-panel flex max-h-[90dvh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
          <div>
            <div class="flex items-center gap-2"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase tracking-wider" :class="source === 'bank' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700'">{{ source === 'bank' ? 'Ngân hàng câu hỏi' : 'Kho cá nhân' }}</span><span class="text-xs text-slate-400">{{ source === 'bank' ? 'Dữ liệu mock' : 'Dữ liệu của bạn' }}</span></div>
            <h2 class="mt-2 text-xl font-black text-slate-900">{{ sourceConfig.title }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ sourceConfig.description }}</p>
          </div>
          <button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng" @click="$emit('close')"><X class="h-5 w-5" /></button>
        </header>

        <div class="border-b border-slate-200 bg-slate-50/70 p-4">
          <div class="relative"><Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-violet-500" /><input v-model="searchTerm" class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold shadow-sm outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100" placeholder="Tìm kiếm câu hỏi..." /></div>
          <div class="mt-2.5 flex flex-wrap items-center gap-2">
            <select v-model="filters.subject" class="filter-field"><option value="">Môn học</option><option v-for="option in filterOptions.subjects" :key="option.value" :value="option.value">{{ option.label }}</option></select>
            <select v-model="filters.grade" class="filter-field"><option value="">Lớp</option><option v-for="option in filterOptions.grades" :key="option.value" :value="option.value">{{ option.label }}</option></select>
            <select v-model="filters.topic" class="filter-field"><option value="">Chủ đề</option><option v-for="option in filterOptions.topics" :key="option.value" :value="option.value">{{ option.label }}</option></select>
            <select v-model="filters.type" class="filter-field disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" :disabled="source === 'personal'" :title="source === 'personal' ? 'API kho cá nhân hiện chưa hỗ trợ lọc loại câu hỏi' : ''"><option value="">Loại câu hỏi</option><option value="single_choice">Một đáp án</option><option value="multi_choice">Nhiều đáp án</option><option value="true_false">Đúng / Sai</option></select>
            <select v-model="filters.difficulty" class="filter-field"><option value="">Độ khó</option><option value="easy">Dễ</option><option value="medium">Trung bình</option><option value="hard">Khó</option></select>
            <button v-if="hasActiveFilters" type="button" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-[11px] font-black text-slate-500 transition hover:bg-white hover:text-violet-700" @click="clearFilters"><FilterX class="h-3.5 w-3.5" /> Xóa bộ lọc</button>
          </div>
          <div v-if="source === 'bank'" class="mt-3 flex flex-wrap items-center gap-3">
            <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-bold text-slate-600"><input v-model="approvedOnly" type="checkbox" class="h-4 w-4 rounded accent-violet-600" /> Chỉ hiện câu đã kiểm duyệt</label>
            <select v-model="reviewStatus" class="filter-field max-w-[190px]" :disabled="approvedOnly" aria-label="Trạng thái kiểm duyệt">
              <option value="">Trạng thái kiểm duyệt</option>
              <option value="approved">Đã kiểm duyệt</option>
              <option value="pending">Chờ duyệt</option>
              <option value="rejected">Không đạt</option>
            </select>
          </div>
        </div>

        <div class="flex-1 space-y-2 overflow-y-auto p-4">
          <div v-if="isLoading" class="grid min-h-[260px] place-items-center" role="status"><div class="text-center"><span class="mx-auto block h-8 w-8 animate-spin rounded-full border-4 border-violet-100 border-t-violet-600"></span><p class="mt-3 text-xs font-bold text-slate-500">Đang tải kho câu hỏi cá nhân...</p></div></div>
          <div v-else-if="loadError" class="grid min-h-[260px] place-items-center"><div class="text-center"><p class="text-sm font-black text-slate-800">Không thể tải kho câu hỏi cá nhân</p><p class="mt-1 text-xs text-slate-500">{{ loadError }}</p><button type="button" class="mt-4 rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white" @click="loadPersonalQuestions">Thử lại</button></div></div>
          <template v-else>
          <article v-for="item in displayItems" :key="item.question.id" class="question-row cursor-pointer" :class="selectedIds.includes(item.question.id) ? 'border-violet-400 bg-violet-50 ring-1 ring-violet-100' : 'border-slate-200 bg-white'" @click="toggleQuestion(item)">
            <input :checked="selectedIds.includes(item.question.id)" type="checkbox" class="mt-1 h-4 w-4 shrink-0 rounded accent-violet-600" @click.stop @change="toggleQuestion(item)" />
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-2"><p class="line-clamp-2 min-w-0 flex-1 text-sm font-bold leading-5 text-slate-900"><MathText :content="item.question.content" compact /></p><span v-if="source === 'bank'" class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-[10px] font-black" :class="reviewStatusConfig[item.meta.reviewStatus].class"><component :is="reviewStatusConfig[item.meta.reviewStatus].icon" class="h-3 w-3" /> {{ reviewStatusConfig[item.meta.reviewStatus].label }}</span></div>
              <p class="mt-1.5 text-[11px] font-bold text-slate-600">{{ item.meta.subject }} · {{ item.meta.grade }}</p>
              <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ item.meta.topic }}</p>
              <div class="mt-2 flex flex-wrap items-center gap-1.5"><span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">{{ typeLabels[item.question.type] }}</span><span class="rounded-md bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-700">{{ difficultyLabels[item.meta.difficulty] }}</span><span class="rounded-md bg-violet-50 px-2 py-1 text-[10px] font-semibold text-violet-700">{{ item.question.points }} điểm</span><span v-if="source === 'bank'" class="rounded-md bg-sky-50 px-2 py-1 text-[10px] font-semibold text-sky-700">Đã sử dụng {{ item.meta.usageCount }} lần</span><span v-if="source === 'bank' && item.meta.contributor" class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">Nguồn: {{ item.meta.contributor }}</span></div>
            </div>
            <button type="button" class="inline-flex shrink-0 items-center gap-1.5 self-end rounded-lg border border-slate-200 px-2.5 py-2 text-[10px] font-black text-slate-600 hover:border-violet-200 hover:text-violet-700" @click.stop="previewItem = item"><Eye class="h-3.5 w-3.5" /> Xem trước</button>
          </article>
          <div v-if="!isLoading && !loadError && displayItems.length === 0" class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-xs text-slate-500"><p class="font-black text-slate-700">{{ source === 'personal' && !hasActiveFilters && !searchTerm ? 'Kho câu hỏi cá nhân chưa có câu hỏi' : 'Không có câu hỏi phù hợp với bộ lọc' }}</p><p v-if="source === 'personal' && !hasActiveFilters && !searchTerm" class="mt-1">Tạo hoặc lưu câu hỏi để sử dụng lại sau.</p></div>
          <div v-if="source === 'personal' && !isLoading && !loadError && pagination.lastPage > 1" class="flex items-center justify-center gap-3 pt-3"><button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black disabled:opacity-40" :disabled="pagination.currentPage <= 1" @click="changePage(pagination.currentPage - 1)">Trước</button><span class="text-xs font-bold text-slate-500">Trang {{ pagination.currentPage }} / {{ pagination.lastPage }} · {{ pagination.total }} câu</span><button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black disabled:opacity-40" :disabled="pagination.currentPage >= pagination.lastPage" @click="changePage(pagination.currentPage + 1)">Sau</button></div>
          </template>
        </div>

        <footer class="flex shrink-0 items-center justify-between border-t border-slate-200 bg-white px-5 py-4"><span class="text-xs font-bold text-slate-500">Đã chọn: <b class="text-violet-700">{{ selectedIds.length }}</b> câu</span><div class="flex gap-2"><button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50" @click="$emit('close')">Hủy</button><button type="button" class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-violet-200 disabled:cursor-not-allowed disabled:opacity-50" :disabled="selectedIds.length === 0" @click="confirmSelection">Thêm {{ selectedIds.length }} câu</button></div></footer>
      </section>

      <div v-if="previewItem" class="absolute inset-0 z-10 grid place-items-center bg-slate-950/25 p-4" @click.self="previewItem = null"><div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><div class="flex flex-wrap gap-1.5"><span class="rounded-md bg-violet-50 px-2 py-1 text-[10px] font-black text-violet-700">{{ typeLabels[previewItem.question.type] }}</span><span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">{{ previewItem.meta.subject }} · {{ previewItem.meta.grade }}</span><span v-if="source === 'bank'" class="rounded-md px-2 py-1 text-[10px] font-black" :class="reviewStatusConfig[previewItem.meta.reviewStatus].class">{{ reviewStatusConfig[previewItem.meta.reviewStatus].label }}</span></div><h3 class="mt-3 text-base font-black leading-7 text-slate-900"><MathText :content="previewItem.question.content" /></h3><img v-if="previewItem.question.image_url" :src="previewItem.question.image_url" alt="Hình minh họa câu hỏi" class="mt-3 max-h-52 w-full rounded-xl border border-slate-200 object-contain" /><div v-if="source === 'bank'" class="mt-2 flex flex-wrap gap-2 text-[10px] font-semibold text-slate-500"><span>Chủ đề: {{ previewItem.meta.topic }}</span><span>•</span><span>{{ difficultyLabels[previewItem.meta.difficulty] }}</span><span>•</span><span>{{ previewItem.meta.usageCount }} lượt sử dụng</span><span v-if="previewItem.meta.contributor">• {{ previewItem.meta.contributor }}</span></div></div><button type="button" class="text-slate-400" @click="previewItem = null"><X class="h-5 w-5" /></button></div><div class="mt-4 space-y-2"><div v-for="(answer, index) in previewItem.question.type === 'fill_in' ? previewItem.question.accepted_answers : previewItem.question.answers" :key="answer.id" class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold" :class="answer.is_correct || previewItem.question.type === 'fill_in' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 text-slate-700'"><span class="font-black">{{ String.fromCharCode(65 + index) }}.</span><span class="min-w-0 flex-1"><MathText :content="answer.content" /></span><span v-if="answer.is_correct || previewItem.question.type === 'fill_in'" class="text-[10px] font-black uppercase">Đáp án đúng</span></div></div><div v-if="previewItem.meta.explanation" class="mt-4 rounded-xl border border-sky-100 bg-sky-50 p-3"><p class="text-[10px] font-black uppercase tracking-wider text-sky-700">Giải thích</p><p class="mt-1 text-xs leading-5 text-sky-900"><MathText :content="previewItem.meta.explanation" /></p></div></div></div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { BadgeCheck, CircleX, Clock3, Eye, FilterX, Search, X } from 'lucide-vue-next'
import { getPersonalQuestions } from '@/services/question-library/personalQuestionApi.js'
import MathText from '../MathText.vue'

const props = defineProps({ source: { type: String, required: true, validator: (value) => ['personal', 'bank'].includes(value) } })
const emit = defineEmits(['select', 'close'])

const answerSet = (prefix, values, correctIndexes = [0]) => values.map((content, index) => ({ id: `${prefix}-a${index}`, content, is_correct: correctIndexes.includes(index) }))
const bankQuestions = [
  { question: { id: 'bank-1', content: 'Tính giá trị của $\\frac{1}{2} + \\sqrt{4}$.', type: 'single_choice', points: 1, answers: answerSet('b1', ['$\\frac{3}{2}$', '$\\frac{5}{2}$', '$3$', '$4$'], [1]), accepted_answers: [] }, meta: { subject: 'Toán', grade: 'Lớp 8', topic: 'Biểu thức đại số', difficulty: 'easy', reviewStatus: 'approved', usageCount: 128, contributor: 'GV. Nguyễn Minh', explanation: '$\\sqrt{4}=2$, do đó $\\frac{1}{2}+2=\\frac{5}{2}$.' } },
  { question: { id: 'bank-2', content: 'Chọn các đặc điểm của văn bản nghị luận.', type: 'multi_choice', points: 1, answers: answerSet('b2', ['Có luận điểm', 'Có luận cứ', 'Chỉ dùng hình ảnh', 'Có lập luận'], [0, 1, 3]), accepted_answers: [] }, meta: { subject: 'Ngữ văn', grade: 'Lớp 12', topic: 'Đọc hiểu', difficulty: 'medium', reviewStatus: 'approved', usageCount: 86, contributor: 'Cộng đồng QuizFlex' } },
  { question: { id: 'bank-3', content: 'Đạo hàm của một hằng số luôn bằng 0.', type: 'true_false', points: 1, answers: answerSet('b3', ['Đúng', 'Sai'], [0]), accepted_answers: [] }, meta: { subject: 'Toán', grade: 'Lớp 11', topic: 'Đạo hàm', difficulty: 'medium', reviewStatus: 'pending', usageCount: 14, contributor: 'Trần Hoàng Anh' } },
  { question: { id: 'bank-4', content: 'Vue.js là một hệ quản trị cơ sở dữ liệu quan hệ.', type: 'true_false', points: 1, answers: answerSet('b4', ['Đúng', 'Sai'], [1]), accepted_answers: [] }, meta: { subject: 'Tin học', grade: 'Lớp 11', topic: 'Vue.js', difficulty: 'easy', reviewStatus: 'rejected', usageCount: 3, contributor: 'Người dùng ẩn danh' } },
  { question: { id: 'bank-5', content: 'Những yếu tố nào góp phần tạo nên tính liên kết của một đoạn văn?', type: 'multi_choice', points: 1, answers: answerSet('b5', ['Phép lặp', 'Phép thế', 'Phép nối', 'Màu chữ'], [0, 1, 2]), accepted_answers: [] }, meta: { subject: 'Ngữ văn', grade: 'Lớp 9', topic: 'Liên kết văn bản', difficulty: 'hard', reviewStatus: 'approved', usageCount: 64, contributor: 'Tổ Ngữ văn QuizFlex' } },
]
const sourceConfigs = {
  personal: { title: 'Kho câu hỏi cá nhân', description: 'Chọn những câu hỏi bạn đã tạo trước đây để thêm vào quiz.' },
  bank: { title: 'Ngân hàng câu hỏi', description: 'Tìm và chọn các câu hỏi đã được phân loại và kiểm duyệt để thêm vào quiz.' },
}
const reviewStatusConfig = {
  approved: { label: 'Đã kiểm duyệt', class: 'bg-emerald-50 text-emerald-700', icon: BadgeCheck },
  pending: { label: 'Chờ duyệt', class: 'bg-amber-50 text-amber-700', icon: Clock3 },
  rejected: { label: 'Không đạt', class: 'bg-rose-50 text-rose-700', icon: CircleX },
}

const searchTerm = ref('')
const approvedOnly = ref(props.source === 'bank')
const reviewStatus = ref('')
const selectedIds = ref([])
const selectedItems = ref(new Map())
const previewItem = ref(null)
const filters = reactive({ subject: '', grade: '', topic: '', type: '', difficulty: '' })
const personalItems = ref([])
const isLoading = ref(props.source === 'personal')
const loadError = ref('')
const pagination = reactive({ currentPage: 1, lastPage: 1, total: 0, perPage: 20 })
const personalOptions = reactive({ subjects: new Map(), grades: new Map(), topics: new Map() })
let searchTimer
let requestSequence = 0
const sourceItems = computed(() => props.source === 'personal' ? personalItems.value : bankQuestions)
const sourceConfig = computed(() => sourceConfigs[props.source])
const asOptions = (values) => [...new Set(values.filter(Boolean))].map((value) => ({ value, label: value }))
const filterOptions = computed(() => ({
  subjects: props.source === 'personal' ? [...personalOptions.subjects].map(([value, label]) => ({ value, label })) : asOptions(sourceItems.value.map((item) => item.meta.subject)),
  grades: props.source === 'personal' ? [...personalOptions.grades].map(([value, label]) => ({ value, label })) : asOptions(sourceItems.value.map((item) => item.meta.grade)),
  topics: props.source === 'personal' ? [...personalOptions.topics].map(([value, label]) => ({ value, label })) : asOptions(sourceItems.value.map((item) => item.meta.topic)),
}))
const hasActiveFilters = computed(() => Object.values(filters).some(Boolean) || Boolean(reviewStatus.value))
const filteredItems = computed(() => sourceItems.value.filter((item) => {
  const matchesSearch = item.question.content.toLowerCase().includes(searchTerm.value.trim().toLowerCase())
  const matchesFilters = (!filters.subject || item.meta.subject === filters.subject) && (!filters.grade || item.meta.grade === filters.grade) && (!filters.topic || item.meta.topic === filters.topic) && (!filters.type || item.question.type === filters.type) && (!filters.difficulty || item.meta.difficulty === filters.difficulty)
  const matchesApproval = props.source !== 'bank' || (!approvedOnly.value || item.meta.reviewStatus === 'approved')
  const matchesReviewStatus = props.source !== 'bank' || !reviewStatus.value || item.meta.reviewStatus === reviewStatus.value
  return matchesSearch && matchesFilters && matchesApproval && matchesReviewStatus
}))
const displayItems = computed(() => props.source === 'personal' ? personalItems.value : filteredItems.value)
const typeLabels = { single_choice: 'Một đáp án', multi_choice: 'Nhiều đáp án', fill_in: 'Điền đáp án', true_false: 'Đúng / Sai' }
const difficultyLabels = { easy: 'Dễ', medium: 'Trung bình', hard: 'Khó' }
const clearFilters = () => { Object.keys(filters).forEach((key) => { filters[key] = '' }); reviewStatus.value = '' }
const rememberPersonalOptions = (items) => items.forEach((item) => {
  if (item.meta.subjectId) personalOptions.subjects.set(String(item.meta.subjectId), item.meta.subject)
  if (item.meta.gradeId) personalOptions.grades.set(String(item.meta.gradeId), item.meta.grade)
  if (item.meta.topic && item.meta.topic !== 'Chưa có chủ đề') personalOptions.topics.set(item.meta.topic, item.meta.topic)
})
const loadPersonalQuestions = async () => {
  if (props.source !== 'personal') return
  const currentRequest = ++requestSequence
  isLoading.value = true
  loadError.value = ''
  try {
    const result = await getPersonalQuestions({
      page: pagination.currentPage,
      perPage: pagination.perPage,
      search: searchTerm.value.trim(),
      subjectId: filters.subject,
      gradeId: filters.grade,
      topic: filters.topic,
      difficulty: filters.difficulty,
    })
    if (currentRequest !== requestSequence) return
    personalItems.value = result.items
    rememberPersonalOptions(result.items)
    pagination.currentPage = result.currentPage
    pagination.lastPage = result.lastPage
    pagination.total = result.total
    pagination.perPage = result.perPage
  } catch (error) {
    if (currentRequest !== requestSequence) return
    personalItems.value = []
    loadError.value = error?.response?.data?.message || 'Vui lòng kiểm tra kết nối hoặc thử lại.'
  } finally {
    if (currentRequest === requestSequence) isLoading.value = false
  }
}
const changePage = (page) => {
  if (page < 1 || page > pagination.lastPage || page === pagination.currentPage) return
  pagination.currentPage = page
  loadPersonalQuestions()
}
const toggleQuestion = (item) => {
  const id = item.question.id
  const nextItems = new Map(selectedItems.value)
  if (nextItems.has(id)) nextItems.delete(id)
  else nextItems.set(id, JSON.parse(JSON.stringify(item)))
  selectedItems.value = nextItems
  selectedIds.value = [...nextItems.keys()]
}
const confirmSelection = () => {
  const selectedQuestions = [...selectedItems.value.values()].map((item) => JSON.parse(JSON.stringify({
    ...item.question,
    difficulty: item.question.difficulty || item.meta.difficulty || 'medium',
    source_type: props.source,
    source_question_id: item.meta.sourceQuestionId ?? item.question.id,
  })))
  emit('select', selectedQuestions)
}

watch(searchTerm, () => {
  if (props.source !== 'personal') return
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { pagination.currentPage = 1; loadPersonalQuestions() }, 400)
})
watch(() => [filters.subject, filters.grade, filters.topic, filters.difficulty], () => {
  if (props.source !== 'personal') return
  pagination.currentPage = 1
  loadPersonalQuestions()
})
onMounted(loadPersonalQuestions)
onBeforeUnmount(() => { clearTimeout(searchTimer); requestSequence += 1 })
</script>

<style scoped>
.filter-field { @apply min-w-[130px] flex-1 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[11px] font-bold text-slate-700 outline-none focus:border-violet-400; }
.question-row { @apply flex items-start gap-3 rounded-xl border px-3.5 py-3 transition hover:border-violet-200; }
@media (max-width: 639px) {
  .picker-overlay { align-items: stretch; padding: 0; }
  .picker-panel { max-height: 100dvh; height: 100dvh; border: 0; border-radius: 0; }
  .picker-panel > header { padding: .875rem; }
  .picker-panel > header h2 { font-size: 1rem; line-height: 1.5rem; }
  .picker-panel > header p { line-height: 1rem; }
  .picker-panel > header + div { padding: .75rem; }
  .filter-field { min-width: calc(50% - .25rem); flex-basis: calc(50% - .25rem); }
  .picker-panel > div.flex-1 { padding: .75rem; }
  .question-row { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .5rem; padding: .75rem; }
  .question-row > button { grid-column: 2; justify-self: end; }
  .picker-panel > footer { gap: .5rem; padding: .75rem; }
  .picker-panel > footer > div { gap: .375rem; }
  .picker-panel > footer button { padding: .625rem .75rem; }
  .picker-overlay > div.absolute { align-items: stretch; padding: .5rem; }
  .picker-overlay > div.absolute > div { max-height: calc(100dvh - 1rem); overflow-y: auto; padding: 1rem; }
}
</style>
