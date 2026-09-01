<template>
  <form class="space-y-5" @submit.prevent="submit">
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="field-label sm:col-span-2">Tên Quiz <span class="text-rose-500">*</span><input v-model="form.title" class="field" :class="{ 'field-invalid': validationField === 'title' }" maxlength="255" placeholder="Ví dụ: Ôn tập Toán lớp 8" /><span v-if="validationField === 'title'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label">Cấp học <span class="text-rose-500">*</span><select v-model="form.education_level_id" class="field" :class="{ 'field-invalid': validationField === 'education_level_id' }" :disabled="isSubmitting || taxonomyLoading" @change="changeLevel"><option value="">Chọn cấp học</option><option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option></select><span v-if="validationField === 'education_level_id'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label">Lớp <span class="text-rose-500">*</span><select v-model="form.grade_id" class="field" :class="{ 'field-invalid': validationField === 'grade_id' }" :disabled="isSubmitting || !form.education_level_id" @change="changeGrade"><option value="">Chọn lớp</option><option v-for="grade in grades" :key="grade.id" :value="grade.id">{{ grade.name }}</option></select><span v-if="validationField === 'grade_id'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label">Môn học <span class="text-rose-500">*</span><select v-model="form.subject_id" class="field" :class="{ 'field-invalid': validationField === 'subject_id' }" :disabled="isSubmitting || !form.grade_id"><option value="">Chọn môn học</option><option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option></select><span v-if="validationField === 'subject_id'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label">Độ khó mặc định<select v-model="form.difficulty" class="field" :disabled="isSubmitting"><option value="easy">Dễ</option><option value="medium">Trung bình</option><option value="hard">Khó</option></select></label>
      <label class="field-label sm:col-span-2">Chủ đề <span v-if="requireTopic" class="text-rose-500">*</span><input v-model="form.topic_name" class="field" :class="{ 'field-invalid': validationField === 'topic_name' }" maxlength="150" :placeholder="requireTopic ? 'Ví dụ: Phương trình bậc nhất' : 'Có thể bổ sung sau'" /><span v-if="validationField === 'topic_name'" class="field-error">{{ validationError }}</span></label>
      <label class="field-label sm:col-span-2">Mô tả<textarea v-model="form.description" class="field resize-y" rows="3" placeholder="Mục tiêu hoặc phạm vi kiến thức của Quiz" /></label>
    </div>

    <div v-if="taxonomyError || externalError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-700">{{ externalError || taxonomyError }}</div>
    <div class="flex flex-wrap justify-end gap-2"><button v-if="showCancel" type="button" class="rounded-xl border border-slate-200 px-5 py-3 text-xs font-black text-slate-700" :disabled="isSubmitting" @click="$emit('cancel')">Hủy</button><button type="submit" class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-violet-200 disabled:cursor-not-allowed disabled:opacity-50" :disabled="isSubmitting || taxonomyLoading">{{ isSubmitting ? 'Đang tạo Quiz...' : submitLabel }}</button></div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { taxonomyApi } from '@/services/api.js'

const props = defineProps({ initialData: { type: Object, default: () => ({}) }, isSubmitting: Boolean, externalError: { type: String, default: '' }, submitLabel: { type: String, default: 'Tiếp tục' }, showCancel: { type: Boolean, default: true }, requireTopic: Boolean })
const emit = defineEmits(['submit', 'cancel'])
const levels = ref([])
const taxonomyLoading = ref(true)
const taxonomyError = ref('')
const validationError = ref('')
const validationField = ref('')
const form = reactive({ title: '', description: '', education_level_id: '', grade_id: '', subject_id: '', topic_name: '', difficulty: 'medium' })
const hydrate = (value = {}) => Object.assign(form, { title: value.title || '', description: value.description || '', education_level_id: value.education_level_id || '', grade_id: value.grade_id || '', subject_id: value.subject_id || '', topic_name: value.topic_name || '', difficulty: value.difficulty || 'medium' })
hydrate(props.initialData)
watch(() => props.initialData, hydrate, { deep: true })
const grades = computed(() => levels.value.find((level) => Number(level.id) === Number(form.education_level_id))?.grades || [])
const subjects = computed(() => grades.value.find((grade) => Number(grade.id) === Number(form.grade_id))?.subjects || [])
const changeLevel = () => { form.grade_id = ''; form.subject_id = '' }
const changeGrade = () => { form.subject_id = '' }
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
onMounted(async () => { try { const data = await taxonomyApi.tree(); levels.value = data?.education_levels || data?.educationLevels || [] } catch (error) { taxonomyError.value = error?.message || 'Không tải được dữ liệu cấp học, lớp và môn học.' } finally { taxonomyLoading.value = false } })
</script>

<style scoped>
.field-label { @apply grid gap-1.5 text-xs font-black text-slate-700; }
.field { @apply w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100 disabled:bg-slate-100 disabled:text-slate-400; }
.field-invalid { @apply border-rose-400 focus:border-rose-400 focus:ring-rose-100; }
.field-error { @apply text-[11px] font-bold text-rose-600; }
</style>
