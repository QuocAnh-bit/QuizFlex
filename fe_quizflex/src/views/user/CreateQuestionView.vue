<template>
  <section class="mx-auto grid w-full max-w-[1400px] gap-6 min-w-0 xl:grid-cols-[minmax(0,1fr)_380px]">
    <!-- MAIN FORM -->
    <form
      class="relative min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
      @submit.prevent="submitForm(false)"
    >
      <div class="grid gap-6">
        <!-- Back + Title -->
        <div>
          <button
            type="button"
            class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-[#7C3AED]"
            @click="goBack"
          >
            <ArrowLeft class="h-4 w-4" />
            Quay lại kho câu hỏi
          </button>

          <h1 class="flex items-center gap-2.5 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Tạo câu hỏi mới
            <span class="inline-block h-2 w-2 rounded-full bg-[#7C3AED]"></span>
          </h1>
          <p class="mt-2 text-sm leading-6 text-slate-500">
            Soạn thảo câu hỏi trắc nghiệm, các lựa chọn đáp án và thiết lập thông tin phân loại cho ngân hàng câu hỏi.
          </p>
        </div>

        <!-- 1. Nội dung câu hỏi -->
        <div class="grid gap-3 pt-1">
          <label for="question-content-input" class="flex items-center gap-2 text-base font-semibold text-slate-900">
            <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
            1. Nội dung câu hỏi
          </label>
          <textarea
            id="question-content-input"
            v-model="form.content"
            required
            rows="5"
            class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium leading-relaxed text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
            placeholder="Nhập nội dung câu hỏi tại đây..."
          ></textarea>
        </div>

        <!-- 2. Phân loại -->
        <div class="grid gap-4 border-t border-slate-200 pt-5">
          <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
            <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
            2. Phân loại
          </h2>

          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-medium text-slate-600">
              Cấp học
              <select
                v-model="form.education_level_id"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                @change="onLevelChange"
              >
                <option value="">Tất cả cấp học</option>
                <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">
                  {{ level.name }}
                </option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-medium text-slate-600">
              Khối lớp
              <select
                v-model="form.grade_id"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                @change="onTaxonomyChange"
              >
                <option value="">Tất cả khối lớp</option>
                <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">
                  {{ grade.name }}
                </option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-medium text-slate-600">
              Bộ môn
              <select
                v-model="form.subject_id"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                @change="onTaxonomyChange"
              >
                <option value="">Tất cả bộ môn</option>
                <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                  {{ subject.name }}
                </option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-medium text-slate-600">
              Độ khó
              <select
                v-model="form.difficulty"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
              >
                <option value="easy">Dễ (Nhận biết)</option>
                <option value="medium">Vừa (Thông hiểu)</option>
                <option value="hard">Khó (Vận dụng)</option>
              </select>
            </label>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-medium text-slate-600">
              Chọn Chủ đề có sẵn (từ Ngân hàng)
              <select
                v-model="selectedBankTopic"
                class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                @change="onBankTopicSelect"
              >
                <option value="">-- Chọn chủ đề từ kho --</option>
                <option
                  v-for="top in topicsList"
                  :key="top.topic_name || top"
                  :value="top.topic_name || top"
                >
                  {{ top.topic_name || top }}{{ top.total_questions ? ` (${top.total_questions} câu)` : '' }}
                </option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-medium text-slate-600">
              Tên Chủ đề (hoặc nhập chủ đề mới)
              <input
                v-model="form.topic_name"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                placeholder="VD: Hàm số, Tiếng Anh B1, Lịch sử Việt Nam..."
                @input="onTopicInputChange"
              />
            </label>
          </div>
        </div>

        <!-- 3. Đáp án -->
        <div id="answers-section" class="grid gap-4 border-t border-slate-200 pt-5">
          <div class="flex items-center justify-between gap-4">
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
              <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
              3. Đáp án
            </h2>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-[#7C3AED] transition hover:bg-[#F5F3FF]"
              @click="addAnswerChoice"
            >
              <Plus class="h-3.5 w-3.5" />
              Thêm đáp án
            </button>
          </div>

          <div class="grid gap-3">
            <div
              v-for="(ans, idx) in form.answers"
              :key="idx"
              class="flex items-center gap-3 rounded-xl border p-2.5 transition"
              :class="ans.is_correct
                ? 'border-emerald-200 bg-emerald-50'
                : 'border-slate-200 bg-slate-50 hover:border-slate-300'"
            >
              <span
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-xs font-bold transition"
                :class="ans.is_correct
                  ? 'bg-emerald-500 text-white'
                  : 'bg-white text-slate-700 border border-slate-200'"
              >
                {{ ans.key }}
              </span>

              <input
                :id="'answer-input-' + idx"
                v-model="ans.content"
                required
                class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                :placeholder="`Nội dung đáp án ${ans.key}...`"
              />

              <label
                class="flex shrink-0 cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium transition"
                :class="ans.is_correct
                  ? 'border-emerald-200 bg-emerald-100 text-emerald-700'
                  : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'"
              >
                <input
                  type="radio"
                  name="correct_answer_choice"
                  :checked="ans.is_correct"
                  class="h-4 w-4 cursor-pointer accent-emerald-500"
                  @change="setCorrectAnswer(idx)"
                />
                <span>{{ ans.is_correct ? 'Đáp án đúng' : 'Đánh dấu đúng' }}</span>
              </label>

              <button
                v-if="form.answers.length > 2"
                type="button"
                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 cursor-pointer"
                aria-label="Xóa đáp án"
                title="Xóa đáp án"
                @click="removeAnswerChoice(idx)"
              >
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- 4. Phạm vi hiển thị & Quy trình Ngân hàng câu hỏi -->
        <div class="grid gap-4 border-t border-slate-200 pt-5">
          <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
            <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
            4. Lưu trữ & Kiểm duyệt
          </h2>

          <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
            <div class="flex items-start gap-3">
              <div class="rounded-lg bg-amber-500/10 p-2 text-amber-600">
                <Lock class="h-5 w-5" />
              </div>
              <div class="grid gap-1">
                <span class="text-sm font-bold text-slate-900">
                  Lưu vào Kho câu hỏi cá nhân (Riêng tư)
                </span>
                <span class="text-xs leading-relaxed text-slate-600">
                  Câu hỏi mới sẽ được lưu vào kho cá nhân của bạn. Để đưa câu hỏi vào <strong>Ngân hàng câu hỏi chung</strong> của hệ thống, bạn có thể bấm nút <strong>"Gửi duyệt vào Ngân hàng"</strong> trong trang Kho câu hỏi của tôi sau khi tạo xong.
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer actions (in-form) -->
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            @click="goBack"
          >
            Hủy
          </button>

          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
              :disabled="isSubmitting"
              @click="submitForm(true)"
            >
              Lưu & tạo câu khác
            </button>

            <button
              type="submit"
              class="rounded-lg bg-[#7C3AED] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#6D28D9] disabled:opacity-50"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Đang lưu...' : 'Lưu câu hỏi' }}
            </button>
          </div>
        </div>
      </div>
    </form>

    <!-- Sticky bottom actions (mobile-friendly) -->
    <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-4px_20px_rgba(15,23,42,0.06)] xl:hidden">
      <div class="mx-auto flex max-w-[1400px] flex-wrap items-center justify-end gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600"
          @click="goBack"
        >
          Hủy
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 disabled:opacity-50"
          :disabled="isSubmitting"
          @click="submitForm(true)"
        >
          Lưu & tạo câu khác
        </button>
        <button
          type="button"
          class="rounded-lg bg-[#7C3AED] px-4 py-2 text-xs font-semibold text-white disabled:opacity-50"
          :disabled="isSubmitting"
          @click="submitForm(false)"
        >
          {{ isSubmitting ? 'Đang lưu...' : 'Lưu câu hỏi' }}
        </button>
      </div>
    </div>

    <!-- LIVE PREVIEW -->
    <aside class="grid content-start gap-5">
      <article class="sticky top-6 grid gap-5 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
          <h3 class="text-base font-semibold text-slate-900">Live Preview</h3>
          <span class="inline-flex items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            Live
          </span>
        </div>

        <!-- Meta path -->
        <div v-if="selectedLevelName || selectedGradeName || selectedSubjectName" class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-slate-500">
          <span v-if="selectedLevelName" class="font-semibold text-slate-800">{{ selectedLevelName }}</span>
          <span v-if="selectedLevelName && (selectedGradeName || selectedSubjectName)" class="text-slate-300">•</span>
          <span v-if="selectedGradeName" class="font-semibold text-slate-800">{{ selectedGradeName }}</span>
          <span v-if="selectedGradeName && selectedSubjectName" class="text-slate-300">•</span>
          <span v-if="selectedSubjectName" class="font-semibold text-[#7C3AED]">{{ selectedSubjectName }}</span>
        </div>

        <!-- Badges -->
        <div class="flex flex-wrap items-center gap-2">
          <span
            class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold"
            :class="{
              'border-emerald-200 bg-emerald-50 text-emerald-700': form.difficulty === 'easy',
              'border-amber-200 bg-amber-50 text-amber-700': form.difficulty === 'medium',
              'border-rose-200 bg-rose-50 text-rose-700': form.difficulty === 'hard'
            }"
          >
            <span
              class="h-1.5 w-1.5 rounded-full"
              :class="{
                'bg-emerald-500': form.difficulty === 'easy',
                'bg-amber-500': form.difficulty === 'medium',
                'bg-rose-500': form.difficulty === 'hard'
              }"
            ></span>
            {{ difficultyText(form.difficulty) }}
          </span>

          <span
            class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold"
            :class="form.is_public
              ? 'border-[#7C3AED]/30 bg-[#F5F3FF] text-[#7C3AED]'
              : 'border-amber-200 bg-amber-50 text-amber-700'"
          >
            <component :is="form.is_public ? Globe : Lock" class="h-3.5 w-3.5" />
            {{ form.is_public ? 'Công khai' : 'Riêng tư' }}
          </span>

          <span
            v-if="form.topic_name"
            class="inline-flex max-w-[200px] items-center gap-1 truncate rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600"
            :title="form.topic_name"
          >
            <span class="font-semibold text-[#7C3AED]">#</span>
            <span class="truncate">{{ form.topic_name }}</span>
          </span>
        </div>

        <!-- Question content -->
        <div class="border-y border-slate-200 py-4">
          <p class="text-sm font-semibold leading-relaxed text-slate-900 break-words">
            <span v-if="form.content.trim()">{{ form.content }}</span>
            <span v-else class="text-sm font-normal italic text-slate-400">
              [Nội dung câu hỏi sẽ xuất hiện tại đây khi bạn nhập...]
            </span>
          </p>
        </div>

        <!-- Answers preview -->
        <div class="grid gap-2.5">
          <div class="flex items-center justify-between px-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            <span>Phương án đáp án</span>
            <span>{{ form.answers.length }} phương án</span>
          </div>

          <div
            v-for="ans in form.answers"
            :key="ans.key"
            class="flex items-center gap-3 rounded-xl border p-3 text-xs transition"
            :class="ans.is_correct
              ? 'border-emerald-200 bg-emerald-50'
              : 'border-slate-200 bg-slate-50'"
          >
            <span
              class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs font-bold"
              :class="ans.is_correct
                ? 'bg-emerald-500 text-white'
                : 'border border-slate-200 bg-white text-slate-700'"
            >
              {{ ans.key }}
            </span>

            <span class="min-w-0 flex-1 truncate text-xs font-medium">
              <span
                v-if="ans.content.trim()"
                :class="ans.is_correct ? 'font-semibold text-emerald-800' : 'text-slate-700'"
              >
                {{ ans.content }}
              </span>
              <span v-else class="italic text-slate-400">Nhập nội dung đáp án {{ ans.key }}...</span>
            </span>

            <span
              v-if="ans.is_correct"
              class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-200 bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700"
            >
              <Check class="h-3 w-3" />
              Đáp án đúng
            </span>
          </div>
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeft,
  Check,
  Globe,
  Lock,
  Plus,
  X
} from 'lucide-vue-next'
import { formatApiErrorMessage, myQuestionsApi, questionsBankApi, taxonomyApi } from '@/services/api'

const router = useRouter()
const showToast = inject('showToast')

const isSubmitting = ref(false)
const taxonomyLevels = ref([])
const allSubjects = ref([])
const topicsList = ref([])
const selectedBankTopic = ref('')

const form = reactive({
  content: '',
  difficulty: 'medium',
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
  is_public: false,
  answers: [
    { key: 'A', content: '', is_correct: true },
    { key: 'B', content: '', is_correct: false },
    { key: 'C', content: '', is_correct: false },
    { key: 'D', content: '', is_correct: false }
  ]
})

const fetchTopicsList = async () => {
  try {
    const params = {
      education_level_id: form.education_level_id || undefined,
      grade_id: form.grade_id || undefined,
      subject_id: form.subject_id || undefined
    }
    const data = await questionsBankApi.fetchTopics(params)
    topicsList.value = Array.isArray(data)
      ? data
      : data?.data && Array.isArray(data.data)
        ? data.data
        : []
  } catch (e) {
    console.error('Không tải được danh sách Chủ đề từ kho:', e)
  }
}

const onBankTopicSelect = () => {
  if (selectedBankTopic.value) {
    form.topic_name = selectedBankTopic.value
  }
}

const onTopicInputChange = () => {
  const text = (form.topic_name || '').trim().toLowerCase()
  if (!text) {
    selectedBankTopic.value = ''
  } else {
    const found = topicsList.value.find(
      (t) => (t.topic_name || t || '').toString().toLowerCase() === text
    )
    selectedBankTopic.value = found ? (found.topic_name || found) : ''
  }
}

const onTaxonomyChange = () => {
  fetchTopicsList()
}

const onLevelChange = () => {
  form.grade_id = ''
  fetchTopicsList()
}

const setCorrectAnswer = (targetIdx) => {
  form.answers.forEach((ans, idx) => {
    ans.is_correct = idx === targetIdx
  })
}

const difficultyText = (diff) => {
  switch (diff) {
    case 'easy': return 'Dễ'
    case 'hard': return 'Khó'
    default: return 'Vừa'
  }
}

const availableGrades = computed(() => {
  if (!form.education_level_id) {
    return taxonomyLevels.value.flatMap((l) => l.grades || [])
  }
  const level = taxonomyLevels.value.find((l) => l.id === Number(form.education_level_id))
  return level ? level.grades || [] : []
})

const availableSubjects = computed(() => {
  if (!form.grade_id) return allSubjects.value
  const grade = availableGrades.value.find((g) => g.id === Number(form.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const selectedLevelName = computed(() => {
  if (!form.education_level_id) return ''
  const item = taxonomyLevels.value.find((l) => l.id === Number(form.education_level_id))
  return item ? item.name : ''
})

const selectedGradeName = computed(() => {
  if (!form.grade_id) return ''
  const item = availableGrades.value.find((g) => g.id === Number(form.grade_id))
  return item ? item.name : ''
})

const selectedSubjectName = computed(() => {
  if (!form.subject_id) return ''
  const item = availableSubjects.value.find((s) => s.id === Number(form.subject_id))
  return item ? item.name : ''
})

const chrKey = (idx) => String.fromCharCode(65 + idx)

const addAnswerChoice = () => {
  const nextKey = chrKey(form.answers.length)
  form.answers.push({ key: nextKey, content: '', is_correct: false })
}

const removeAnswerChoice = (targetIdx) => {
  if (form.answers.length <= 2) return
  form.answers.splice(targetIdx, 1)
  form.answers.forEach((ans, idx) => {
    ans.key = chrKey(idx)
  })
  if (!form.answers.some((a) => a.is_correct)) {
    form.answers[0].is_correct = true
  }
}

const goBack = () => {
  router.back()
}

const focusAndHighlightElement = (targetId) => {
  if (!targetId) return
  const el = document.getElementById(targetId)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    if (typeof el.focus === 'function') el.focus()
    el.classList.add('ring-4', 'ring-rose-500/60', 'border-rose-500')
    setTimeout(() => {
      el.classList.remove('ring-4', 'ring-rose-500/60', 'border-rose-500')
    }, 2500)
  }
}

const validateBeforeSubmit = () => {
  if (!form.content.trim()) {
    return { message: 'Vui lòng nhập nội dung câu hỏi.', targetId: 'question-content-input' }
  }
  for (let i = 0; i < form.answers.length; i++) {
    if (!form.answers[i].content.trim()) {
      return {
        message: `Vui lòng nhập nội dung cho Đáp án ${form.answers[i].key}.`,
        targetId: `answer-input-${i}`
      }
    }
  }
  if (!form.answers.some((a) => a.is_correct)) {
    return { message: 'Vui lòng đánh dấu chọn 1 đáp án đúng.', targetId: 'answers-section' }
  }
  return null
}

const submitForm = async (createAnother = false) => {
  const errorInfo = validateBeforeSubmit()
  if (errorInfo) {
    if (showToast) showToast(errorInfo.message, 'error')
    focusAndHighlightElement(errorInfo.targetId)
    return
  }

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
      answers: form.answers.map((a) => ({
        content: a.content.trim(),
        key: a.key,
        is_correct: a.is_correct
      }))
    }

    await myQuestionsApi.createQuestion(payload)
    if (showToast) showToast('Tạo câu hỏi mới thành công!', 'success')

    if (createAnother) {
      form.content = ''
      form.answers = [
        { key: 'A', content: '', is_correct: true },
        { key: 'B', content: '', is_correct: false },
        { key: 'C', content: '', is_correct: false },
        { key: 'D', content: '', is_correct: false }
      ]
    } else {
      router.push('/dashboard/my-questions')
    }
  } catch (err) {
    const msg = formatApiErrorMessage(err, 'Tạo câu hỏi thất bại. Vui lòng kiểm tra lại.')
    if (showToast) showToast(msg, 'error')
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
    console.error('Không tải được taxonomy:', e)
  }
  fetchTopicsList()
})
</script>