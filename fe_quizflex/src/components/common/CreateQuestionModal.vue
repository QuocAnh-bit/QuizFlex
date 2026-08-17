<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-md" @click.self="close">
    <div class="w-full max-w-2xl rounded-[2.5rem] border border-[var(--border-strong)] bg-[var(--surface)] p-6 shadow-2xl backdrop-blur-2xl max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-[var(--border)] pb-4 mb-4">
        <div>
          <h3 class="text-xl font-black text-[var(--text)] flex items-center gap-2">
            <span>✨ Tạo câu hỏi mới</span>
          </h3>
          <p class="text-xs text-[var(--muted)]">Nhập nội dung, chọn đáp án đúng và đặt phạm vi hiển thị cho câu hỏi</p>
        </div>
        <button type="button" class="text-xl font-black text-[var(--muted)] hover:text-rose-500 transition" @click="close">✕</button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitForm" class="grid gap-4">
        <!-- Nội dung câu hỏi -->
        <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
          Nội dung câu hỏi *
          <textarea v-model="form.content" required class="field min-h-24 text-sm" placeholder="Nhập câu hỏi... (VD: Tác giả của Truyện Kiều là ai?)"></textarea>
        </label>

        <!-- Taxonomy Filters (Cấp học, Lớp, Môn, Chủ đề) -->
        <div class="grid grid-cols-2 gap-3">
          <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
            Cấp học
            <select v-model="form.education_level_id" class="field text-xs" @change="onLevelChange">
              <option value="">Tất cả Cấp học</option>
              <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
            Khối lớp
            <select v-model="form.grade_id" class="field text-xs">
              <option value="">Tất cả Khối lớp</option>
              <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
            Bộ môn
            <select v-model="form.subject_id" class="field text-xs">
              <option value="">Tất cả Bộ môn</option>
              <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
          </label>

          <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
            Mức độ khó
            <select v-model="form.difficulty" class="field text-xs">
              <option value="easy">Dễ (Nhận biết)</option>
              <option value="medium">Vừa (Thông hiểu)</option>
              <option value="hard">Khó (Vận dụng)</option>
            </select>
          </label>
        </div>

        <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
          Chủ đề kiến thức
          <input v-model="form.topic_name" class="field text-xs" placeholder="VD: Văn học, Hàm số, Hình học..." />
        </label>

        <!-- PHẠM VI HIỂN THỊ CÂU HỎI (PUBLIC / PRIVATE) -->
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3.5 grid gap-2">
          <span class="text-xs font-black uppercase tracking-wider text-[var(--primary)]">Phạm vi hiển thị câu hỏi</span>
          <div class="grid grid-cols-2 gap-2.5 text-xs">
            <label 
              class="flex items-start gap-2.5 rounded-xl border p-3 cursor-pointer transition duration-150"
              :class="!form.is_public ? 'border-amber-500 bg-amber-500/10 text-amber-300 font-bold' : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:border-[var(--border-strong)]'"
            >
              <input type="radio" v-model="form.is_public" :value="false" class="mt-0.5 accent-amber-500" />
              <div class="grid gap-0.5">
                <span class="font-black text-xs">🔒 Riêng tư (Private)</span>
                <span class="text-[10px] leading-tight opacity-80 font-normal">Chỉ xuất hiện trong Kho của bạn. Bạn vẫn có thể chèn vào mọi Quiz</span>
              </div>
            </label>

            <label 
              class="flex items-start gap-2.5 rounded-xl border p-3 cursor-pointer transition duration-150"
              :class="form.is_public ? 'border-[var(--primary)] bg-[var(--primary)]/10 text-[var(--primary)] font-bold' : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:border-[var(--border-strong)]'"
            >
              <input type="radio" v-model="form.is_public" :value="true" class="mt-0.5 accent-[var(--primary)]" />
              <div class="grid gap-0.5">
                <span class="font-black text-xs">🌐 Công khai (Public)</span>
                <span class="text-[10px] leading-tight opacity-80 font-normal">Hiển thị lên Ngân hàng chung cho cộng đồng chọn tái sử dụng</span>
              </div>
            </label>
          </div>
        </div>

        <!-- DANH SÁCH ĐÁP ÁN -->
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 grid gap-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-black uppercase text-[var(--primary)]">Danh sách Đáp án (Tick radio chọn đáp án đúng)</span>
          </div>

          <div v-for="(ans, idx) in form.answers" :key="idx" class="flex items-center gap-2">
            <span class="grid h-7 w-7 place-items-center rounded-xl bg-black/40 font-black text-xs shrink-0 text-[var(--text)]">{{ ans.key }}</span>
            
            <input v-model="ans.content" required class="field text-xs flex-1" :placeholder="`Nội dung đáp án ${ans.key}`" />

            <label class="flex items-center gap-1.5 text-xs font-bold shrink-0 cursor-pointer px-3 py-2 rounded-xl border transition" :class="ans.is_correct ? 'border-emerald-500 bg-emerald-500/20 text-emerald-300' : 'border-[var(--border)] text-[var(--muted)]'">
              <input type="radio" name="new_question_correct_ans" :checked="ans.is_correct" @change="setCorrectAnswer(idx)" class="accent-emerald-500" />
              <span>{{ ans.is_correct ? '✓ Đúng' : 'Sai' }}</span>
            </label>
          </div>
        </div>

        <!-- Footer Buttons -->
        <div class="flex justify-end gap-3 mt-2 pt-4 border-t border-[var(--border)]">
          <button class="btn-ghost text-xs" type="button" @click="close">Hủy</button>
          <button class="btn-primary text-xs flex items-center gap-1.5" type="submit" :disabled="isSubmitting">
            <span>{{ isSubmitting ? 'Đang tạo...' : 'Lưu câu hỏi' }}</span>
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
