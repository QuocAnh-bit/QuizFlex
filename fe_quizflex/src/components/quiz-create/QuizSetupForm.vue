<template>
  <form class="space-y-5" @submit.prevent="submit">
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="field-label sm:col-span-2">Tên Quiz <span class="text-rose-500">*</span><input v-model="form.title" class="field" :class="{ 'field-invalid': validationField === 'title' }" maxlength="255" placeholder="Ví dụ: Ôn tập Toán lớp 8" /><span v-if="validationField === 'title'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label">Cấp học <span class="text-rose-500">*</span><select v-model="form.education_level_id" class="field" :class="{ 'field-invalid': validationField === 'education_level_id' }" :disabled="isSubmitting || taxonomyLoading" @change="changeLevel"><option value="">Chọn cấp học</option><option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option></select><span v-if="validationField === 'education_level_id'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label">Lớp <span class="text-rose-500">*</span><select v-model="form.grade_id" class="field" :class="{ 'field-invalid': validationField === 'grade_id' }" :disabled="isSubmitting || !form.education_level_id" @change="changeGrade"><option value="">Chọn lớp</option><option v-for="grade in grades" :key="grade.id" :value="grade.id">{{ grade.name }}</option></select><span v-if="validationField === 'grade_id'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label">Môn học <span class="text-rose-500">*</span><select v-model="form.subject_id" class="field" :class="{ 'field-invalid': validationField === 'subject_id' }" :disabled="isSubmitting || !form.grade_id" @change="changeSubject"><option value="">Chọn môn học</option><option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option></select><span v-if="validationField === 'subject_id'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label">Độ khó mặc định<select v-model="form.difficulty" class="field" :disabled="isSubmitting"><option value="easy">Dễ</option><option value="medium">Trung bình</option><option value="hard">Khó</option></select></label>
      <div class="field-label sm:col-span-2">
        <span>Chủ đề <span v-if="requireTopic" class="text-rose-500">*</span></span>
        <div v-if="curriculumLoading" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-xs font-semibold text-slate-500">Đang tải chủ đề từ dữ liệu RAG...</div>
        <template v-else-if="curriculumOptions.length">
          <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-[11px] font-semibold text-slate-500">{{ topicMode === 'rag' ? 'Chọn một hoặc nhiều chủ đề theo chương trình.' : 'Mô tả yêu cầu riêng; AI vẫn nhận lớp và môn của Quiz.' }}</p><button type="button" class="topic-mode-button" :disabled="isSubmitting" @click="toggleTopicMode">{{ topicMode === 'rag' ? 'Nhập yêu cầu khác' : 'Chọn từ chương trình' }}</button></div>
          <template v-if="topicMode === 'rag'">
            <div class="topic-filter mt-2">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" /></svg>
              <input v-model="curriculumSearch" type="search" placeholder="Lọc chủ đề theo tên..." :disabled="isSubmitting" />
              <button v-if="curriculumSearch" type="button" aria-label="Xóa bộ lọc chủ đề" @click="curriculumSearch = ''">×</button>
            </div>
            <div class="mt-1 flex items-center justify-between text-[10px] font-bold text-slate-400"><span>{{ filteredCurriculumOptions.length }} chủ đề phù hợp</span><button v-if="selectedCurriculumKeys.length" type="button" class="clear-selection" :disabled="isSubmitting" @click="clearCurriculumSelection">Bỏ chọn tất cả</button></div>
            <div class="topic-picker mt-2" :class="{ 'field-invalid': validationField === 'topic_name' }">
              <button v-for="option in filteredCurriculumOptions" :key="option._key" type="button" class="topic-option" :class="{ selected: selectedCurriculumKeys.includes(option._key) }" :disabled="isSubmitting" @click="toggleCurriculum(option)"><span class="topic-checkbox">{{ selectedCurriculumKeys.includes(option._key) ? '✓' : '' }}</span><span>{{ option.label }}</span></button>
              <p v-if="!filteredCurriculumOptions.length" class="col-span-full px-3 py-5 text-center text-xs font-semibold text-slate-400">Không tìm thấy chủ đề phù hợp.</p>
            </div>
          </template>
          <input v-else v-model="form.topic_name" class="field mt-2" :class="{ 'field-invalid': validationField === 'topic_name' }" maxlength="150" placeholder="Ví dụ: Câu hỏi vận dụng thực tế, ôn tập tổng hợp hoặc dạng bài riêng" @input="clearCurriculumSelection" />
        </template>
        <div v-else><input v-model="form.topic_name" class="field" :class="{ 'field-invalid': validationField === 'topic_name' }" maxlength="150" :placeholder="curriculumError || (requireTopic ? 'Nhập chủ đề hoặc yêu cầu tạo câu hỏi' : 'Có thể bổ sung sau')" /><p class="mt-1 text-[11px] font-semibold text-slate-400">Môn này chưa có chủ đề RAG chuẩn. AI sẽ dùng lớp, môn và nội dung bạn nhập.</p></div>
        <div v-if="selectedCurriculumKeys.length" class="flex flex-wrap gap-1.5"><span v-for="option in selectedCurriculumOptions" :key="option._key" class="rounded-lg bg-violet-100 px-2.5 py-1 text-[10px] font-black text-violet-700">{{ option.label }}</span></div>
        <span v-if="validationField === 'topic_name'" class="field-error">{{ validationError }}</span>
      </div>
      <label class="field-label sm:col-span-2">Mô tả<textarea v-model="form.description" class="field resize-y" rows="3" placeholder="Mục tiêu hoặc phạm vi kiến thức của Quiz" /></label>
    </div>

    <div v-if="taxonomyError || externalError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-700">{{ externalError || taxonomyError }}</div>
    <div class="flex flex-wrap justify-end gap-2"><button v-if="showCancel" type="button" class="rounded-xl border border-slate-200 px-5 py-3 text-xs font-black text-slate-700" :disabled="isSubmitting" @click="$emit('cancel')">Hủy</button><button type="submit" class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-violet-200 disabled:cursor-not-allowed disabled:opacity-50" :disabled="isSubmitting || taxonomyLoading">{{ isSubmitting ? 'Đang tạo Quiz...' : submitLabel }}</button></div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { curriculumApi, taxonomyApi } from '@/services/api.js'

const props = defineProps({ initialData: { type: Object, default: () => ({}) }, isSubmitting: Boolean, externalError: { type: String, default: '' }, submitLabel: { type: String, default: 'Tiếp tục' }, showCancel: { type: Boolean, default: true }, requireTopic: Boolean })
const emit = defineEmits(['submit', 'cancel'])
const levels = ref([])
const taxonomyLoading = ref(true)
const taxonomyError = ref('')
const validationError = ref('')
const validationField = ref('')
const curriculumOptions = ref([])
const selectedCurriculumKeys = ref([])
const curriculumLoading = ref(false)
const curriculumError = ref('')
const topicMode = ref('rag')
const curriculumSearch = ref('')
const form = reactive({ title: '', description: '', education_level_id: '', grade_id: '', subject_id: '', topic_name: '', curriculum_unit_ids: [], difficulty: 'medium' })
const hydrate = (value = {}) => Object.assign(form, { title: value.title || '', description: value.description || '', education_level_id: value.education_level_id || '', grade_id: value.grade_id || '', subject_id: value.subject_id || '', topic_name: value.topic_name || '', curriculum_unit_ids: Array.isArray(value.curriculum_unit_ids) ? value.curriculum_unit_ids.map(Number) : [], difficulty: value.difficulty || 'medium' })
hydrate(props.initialData)
watch(() => props.initialData, hydrate, { deep: true })
const grades = computed(() => levels.value.find((level) => Number(level.id) === Number(form.education_level_id))?.grades || [])
const subjects = computed(() => grades.value.find((grade) => Number(grade.id) === Number(form.grade_id))?.subjects || [])
const selectedCurriculumOptions = computed(() => curriculumOptions.value.filter((option) => selectedCurriculumKeys.value.includes(option._key)))
const filteredCurriculumOptions = computed(() => {
  const keyword = curriculumSearch.value.trim().toLocaleLowerCase('vi')
  if (!keyword) return curriculumOptions.value
  return curriculumOptions.value.filter((option) => String(option.label || '').toLocaleLowerCase('vi').includes(keyword))
})
const resetCurriculum = () => { curriculumOptions.value = []; selectedCurriculumKeys.value = []; curriculumSearch.value = ''; form.topic_name = ''; form.curriculum_unit_ids = []; curriculumError.value = ''; topicMode.value = 'rag' }
const changeLevel = () => { form.grade_id = ''; form.subject_id = ''; resetCurriculum() }
const changeGrade = () => { form.subject_id = ''; resetCurriculum() }
const syncCurriculumSelection = () => {
  const selected = selectedCurriculumOptions.value
  form.topic_name = selected.map((option) => option.label).join(', ').slice(0, 150)
  form.curriculum_unit_ids = [...new Set(selected.flatMap((option) => option.curriculum_unit_ids || []).map(Number).filter(Number.isInteger))]
}
const toggleCurriculum = (option) => {
  selectedCurriculumKeys.value = selectedCurriculumKeys.value.includes(option._key) ? selectedCurriculumKeys.value.filter((key) => key !== option._key) : [...selectedCurriculumKeys.value, option._key]
  syncCurriculumSelection()
}
const clearCurriculumSelection = () => { selectedCurriculumKeys.value = []; form.curriculum_unit_ids = []; if (topicMode.value === 'rag') form.topic_name = '' }
const toggleTopicMode = () => {
  topicMode.value = topicMode.value === 'rag' ? 'custom' : 'rag'
  selectedCurriculumKeys.value = []
  form.curriculum_unit_ids = []
  form.topic_name = ''
}
const loadCurriculum = async () => {
  curriculumOptions.value = []; selectedCurriculumKeys.value = []; curriculumSearch.value = ''; curriculumError.value = ''
  if (!form.education_level_id || !form.grade_id || !form.subject_id) return
  const initialIds = new Set((form.curriculum_unit_ids || []).map(Number))
  const initialNames = new Set(String(form.topic_name || '').split(',').map((name) => name.trim()).filter(Boolean))
  curriculumLoading.value = true
  try {
    const result = await curriculumApi.fetchOptions({ education_level_id: form.education_level_id, grade_id: form.grade_id, subject_id: form.subject_id })
    curriculumOptions.value = (result.options || []).map((option, index) => ({ ...option, _key: `${index}-${option.label}` }))
    selectedCurriculumKeys.value = curriculumOptions.value.filter((option) => initialNames.has(option.label) || (option.curriculum_unit_ids || []).some((id) => initialIds.has(Number(id)))).map((option) => option._key)
    topicMode.value = curriculumOptions.value.length && (!initialNames.size || selectedCurriculumKeys.value.length) ? 'rag' : 'custom'
    if (selectedCurriculumKeys.value.length) syncCurriculumSelection()
  } catch (error) { curriculumError.value = error?.message || 'Không tải được chủ đề RAG.' } finally { curriculumLoading.value = false }
}
const changeSubject = () => { form.topic_name = ''; form.curriculum_unit_ids = []; loadCurriculum() }
const submit = () => {
  validationError.value = ''
  validationField.value = ''
  if (!form.title.trim()) { validationField.value = 'title'; validationError.value = 'Vui lòng nhập tên Quiz.' }
  else if (!form.education_level_id) { validationField.value = 'education_level_id'; validationError.value = 'Vui lòng chọn cấp học.' }
  else if (!form.grade_id) { validationField.value = 'grade_id'; validationError.value = 'Vui lòng chọn lớp.' }
  else if (!form.subject_id) { validationField.value = 'subject_id'; validationError.value = 'Vui lòng chọn môn học.' }
  else if (props.requireTopic && !form.topic_name.trim()) { validationField.value = 'topic_name'; validationError.value = 'Vui lòng nhập chủ đề.' }
  if (validationError.value) return
  emit('submit', JSON.parse(JSON.stringify(form)))
}
onMounted(async () => { try { const data = await taxonomyApi.tree(); levels.value = data?.education_levels || data?.educationLevels || []; await loadCurriculum() } catch (error) { taxonomyError.value = error?.message || 'Không tải được dữ liệu cấp học, lớp và môn học.' } finally { taxonomyLoading.value = false } })
</script>

<style scoped>
.field-label { @apply grid gap-1.5 text-xs font-black text-slate-700; }
.field { @apply w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100 disabled:bg-slate-100 disabled:text-slate-400; }
.field-invalid { @apply border-rose-400 focus:border-rose-400 focus:ring-rose-100; }
.field-error { @apply text-[11px] font-bold text-rose-600; }
.topic-picker { @apply grid max-h-52 gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2 sm:grid-cols-2; }
.topic-option { @apply flex items-start gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-left text-xs font-bold text-slate-700 transition hover:border-violet-300 hover:bg-violet-50 disabled:opacity-50; }
.topic-option.selected { @apply border-violet-400 bg-violet-50 text-violet-800 ring-1 ring-violet-100; }
.topic-checkbox { @apply mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded border border-slate-300 text-[10px] font-black text-white; }
.topic-option.selected .topic-checkbox { @apply border-violet-600 bg-violet-600; }
.topic-mode-button { @apply rounded-lg border border-violet-200 bg-white px-2.5 py-1.5 text-[11px] font-black text-violet-700 transition hover:bg-violet-50 disabled:opacity-50; }
.topic-filter { @apply flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-400 transition focus-within:border-violet-400 focus-within:ring-2 focus-within:ring-violet-100; }
.topic-filter svg { @apply h-4 w-4 shrink-0; fill: none; stroke: currentColor; stroke-linecap: round; stroke-width: 2; }
.topic-filter input { @apply min-w-0 flex-1 bg-transparent text-xs font-semibold text-slate-700 outline-none placeholder:text-slate-400 disabled:cursor-not-allowed; }
.topic-filter button { @apply grid h-5 w-5 place-items-center rounded text-base leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700; }
.clear-selection { @apply text-violet-600 hover:text-violet-800 disabled:opacity-50; }
</style>
