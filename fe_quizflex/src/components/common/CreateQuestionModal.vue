<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-md" @click.self="close">
    <div class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-2xl max-h-[90vh] overflow-y-auto text-slate-900">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
        <div>
          <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
            <span>Tạo câu hỏi mới</span>
            <span class="inline-block h-2 w-2 rounded-full bg-[#7C3AED]"></span>
          </h3>
          <p class="text-xs text-slate-500 mt-1">Nhập nội dung, chọn đáp án đúng và đặt phạm vi hiển thị cho câu hỏi</p>
        </div>
        <button type="button" class="grid h-8 w-8 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer" @click="close">✕</button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitForm" class="grid gap-5">
        <!-- Nội dung câu hỏi -->
        <label class="grid gap-1.5 text-xs font-semibold text-slate-900">
          Nội dung câu hỏi *
          <textarea v-model="form.content" required rows="3" class="w-full resize-none rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20" placeholder="Nhập câu hỏi... (VD: Tác giả của Truyện Kiều là ai?)"></textarea>
        </label>

        <!-- Taxonomy Filters (Cấp học, Lớp, Môn, Chủ đề) -->
        <div class="grid grid-cols-2 gap-3">
          <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
            Cấp học
            <select v-model="form.education_level_id" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]" @change="onLevelChange">
              <option value="">Tất cả Cấp học</option>
              <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
            Khối lớp
            <select v-model="form.grade_id" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]">
              <option value="">Tất cả Khối lớp</option>
              <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
            Bộ môn
            <select v-model="form.subject_id" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]">
              <option value="">Tất cả Bộ môn</option>
              <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
            Mức độ khó
            <select v-model="form.difficulty" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]">
              <option value="easy">Dễ (Nhận biết)</option>
              <option value="medium">Vừa (Thông hiểu)</option>
              <option value="hard">Khó (Vận dụng)</option>
            </select>
          </label>
        </div>

        <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
          Chủ đề kiến thức
          <input v-model="form.topic_name" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]" placeholder="VD: Văn học, Hàm số, Hình học..." />
        </label>

        <!-- PHẠM VI HIỂN THỊ CÂU HỎI (PUBLIC / PRIVATE) -->
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 grid gap-3">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Phạm vi hiển thị câu hỏi</span>
          <div class="grid grid-cols-2 gap-3 text-xs">
            <label 
              class="flex items-start gap-2.5 rounded-xl border p-3 cursor-pointer transition duration-150"
              :class="!form.is_public ? 'border-amber-200 bg-amber-50 text-amber-800 font-bold' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
            >
              <input type="radio" v-model="form.is_public" :value="false" class="mt-0.5 accent-amber-500" />
              <div class="grid gap-0.5">
                <span class="font-bold text-xs">🔒 Riêng tư</span>
                <span class="text-[11px] leading-tight text-slate-500 font-normal">Lưu vào Kho cá nhân để soạn Quiz</span>
              </div>
            </label>

            <label 
              class="flex items-start gap-2.5 rounded-xl border p-3 cursor-pointer transition duration-150"
              :class="form.is_public ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
            >
              <input type="radio" v-model="form.is_public" :value="true" class="mt-0.5 accent-[#7C3AED]" />
              <div class="grid gap-0.5">
                <span class="font-bold text-xs">🌐 Công khai</span>
                <span class="text-[11px] leading-tight text-slate-500 font-normal">Đóng góp lên Ngân hàng chung</span>
              </div>
            </label>
          </div>
        </div>

        <!-- DANH SÁCH ĐÁP ÁN -->
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 grid gap-3">
          <span class="text-xs font-bold uppercase text-slate-500">Danh sách Đáp án (Tick chọn đáp án đúng)</span>

          <div v-for="(ans, idx) in form.answers" :key="idx" class="flex items-center gap-2.5">
            <span class="grid h-8 w-8 place-items-center rounded-xl bg-white border border-slate-200 font-bold text-xs shrink-0 text-slate-700">{{ ans.key }}</span>
            
            <input v-model="ans.content" required class="flex-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]" :placeholder="`Nội dung đáp án ${ans.key}`" />

            <label class="flex items-center gap-1.5 text-xs font-bold shrink-0 cursor-pointer px-3 py-2 rounded-xl border transition" :class="ans.is_correct ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'">
              <input type="radio" name="new_question_correct_ans" :checked="ans.is_correct" @change="setCorrectAnswer(idx)" class="accent-emerald-500" />
              <span>{{ ans.is_correct ? '✓ Đúng' : 'Sai' }}</span>
            </label>
          </div>
        </div>

        <!-- Footer Buttons -->
        <div class="flex justify-end gap-3 mt-2 pt-4 border-t border-slate-200">
          <button class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 cursor-pointer" type="button" @click="close">Hủy</button>
          <button class="rounded-xl bg-[#7C3AED] px-5 py-2 text-xs font-bold text-white shadow-md shadow-[#7C3AED]/20 hover:bg-[#6D28D9] cursor-pointer disabled:opacity-50" type="submit" :disabled="isSubmitting">
            <span>{{ isSubmitting ? 'Đang tạo...' : '✓ Lưu câu hỏi' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { myQuestionsApi, taxonomyApi } from '@/services/api'

const props = defineProps({
  isOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'created'])
const showToast = inject('showToast')

const isSubmitting = ref(false)
const taxonomyLevels = ref([])
const allSubjects = ref([])

const form = reactive({
  content: '',
  difficulty: 'medium',
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
  is_public: false, // Mặc định là Riêng tư
  answers: [
    { key: 'A', content: '', is_correct: true },
    { key: 'B', content: '', is_correct: false },
    { key: 'C', content: '', is_correct: false },
    { key: 'D', content: '', is_correct: false },
  ]
})

const availableGrades = computed(() => {
  if (!form.education_level_id) {
    return taxonomyLevels.value.flatMap(l => l.grades || [])
  }
  const level = taxonomyLevels.value.find(l => l.id === Number(form.education_level_id))
  return level ? level.grades || [] : []
})

const availableSubjects = computed(() => {
  if (!form.grade_id) {
    return allSubjects.value
  }
  const grade = availableGrades.value.find(g => g.id === Number(form.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const onLevelChange = () => {
  form.grade_id = ''
}

const setCorrectAnswer = (targetIdx) => {
  form.answers.forEach((ans, idx) => {
    ans.is_correct = idx === targetIdx
  })
}

const resetForm = () => {
  form.content = ''
  form.difficulty = 'medium'
  form.education_level_id = ''
  form.grade_id = ''
  form.subject_id = ''
  form.topic_name = ''
  form.is_public = false
  form.answers = [
    { key: 'A', content: '', is_correct: true },
    { key: 'B', content: '', is_correct: false },
    { key: 'C', content: '', is_correct: false },
    { key: 'D', content: '', is_correct: false },
  ]
}

const close = () => {
  resetForm()
  emit('close')
}

const submitForm = async () => {
  if (!form.content.trim()) return
  isSubmitting.value = true

  try {
    const payload = {
      content: form.content.trim(),
      difficulty: form.difficulty,
      education_level_id: form.education_level_id || null,
      grade_id: form.grade_id || null,
      subject_id: form.subject_id || null,
      topic_name: form.topic_name ? form.topic_name.trim() : null,
      is_public: Boolean(form.is_public),
      answers: form.answers.map(a => ({
        content: a.content.trim(),
        key: a.key,
        is_correct: a.is_correct
      }))
    }

    const created = await myQuestionsApi.createQuestion(payload)
    if (showToast) showToast('Tạo câu hỏi mới thành công!', 'success')
    emit('created', created)
    close()
  } catch (err) {
    if (showToast) showToast(`Tạo thất bại: ${err.message}`, 'error')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  try {
    const data = await taxonomyApi.tree()
    const payload = data?.education_levels ? data : (data?.data ?? data)
    if (payload) {
      taxonomyLevels.value = payload.education_levels || payload.educationLevels || []
      allSubjects.value = payload.subjects || []
    }
  } catch (e) {
    console.error('Không tải được taxonomy trong CreateQuestionModal:', e)
  }
})
</script>
