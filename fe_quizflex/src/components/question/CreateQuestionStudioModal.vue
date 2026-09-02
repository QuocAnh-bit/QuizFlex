<template>
  <Teleport to="body">
    <Transition name="studio-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] overflow-y-auto bg-slate-900/70 backdrop-blur-md p-3 sm:p-6 lg:p-8"
        @click.self="closeModal"
      >
        <!-- MAIN STUDIO CONTAINER -->
        <div class="mx-auto flex min-h-full max-w-[1600px] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl">
          
          <!-- 1. TOP NAVBAR -->
          <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="grid h-10 w-10 place-items-center rounded-2xl bg-[#F5F3FF] text-[#7C3AED] font-black text-lg">
                🎨
              </div>
              <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                  <span>Question Studio</span>
                  <span class="inline-block h-2 w-2 rounded-full bg-[#7C3AED]"></span>
                </h2>
                <p class="text-xs text-slate-500">
                  Biên soạn câu hỏi, đáp án và phân loại trực tiếp trên 1 Tab duy nhất.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100 cursor-pointer"
                @click="closeModal"
              >
                Hủy / Đóng
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-[#7C3AED] px-5 py-2 text-xs font-bold text-white shadow-lg shadow-[#7C3AED]/25 transition hover:bg-[#6D28D9] disabled:opacity-50 cursor-pointer"
                :disabled="isSubmitting"
                @click="submitQuestion"
              >
                <span>{{ isSubmitting ? 'Đang lưu...' : '✓ Lưu câu hỏi vào Bài Quiz' }}</span>
              </button>

              <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer"
                @click="closeModal"
              >
                <X :size="20" :stroke-width="2.5" />
              </button>
            </div>
          </div>

          <!-- 2. STUDIO CONTENT GRID -->
          <div class="grid flex-1 gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            
            <!-- LEFT COLUMN: MAIN EDITOR -->
            <div class="space-y-6">
              
              <!-- SECTION 1: NỘI DUNG CÂU HỎI -->
              <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-3">
                <label for="studio-content-input" class="flex items-center gap-2 text-base font-bold text-slate-900">
                  <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
                  1. Nội dung câu hỏi *
                </label>
                <textarea
                  id="studio-content-input"
                  v-model="form.content"
                  rows="4"
                  required
                  class="w-full resize-none rounded-xl border border-slate-200 bg-white p-4 text-sm font-medium leading-relaxed text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                  placeholder="Nhập nội dung câu hỏi tại đây..."
                ></textarea>

                <!-- Upload hình ảnh câu hỏi -->
                <QuestionImageUploader
                  v-model="form.image_url"
                  label="Hình ảnh minh họa cho câu hỏi (Tùy chọn)"
                />
              </div>

              <!-- SECTION 2: PHÂN LOẠI & THUỘC TÍNH -->
              <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h3 class="flex items-center gap-2 text-base font-bold text-slate-900">
                  <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
                  2. Phân loại & Phạm vi
                </h3>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                  <!-- Cấp học -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Cấp học
                    <select
                      v-model="form.education_level_id"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="onLevelChange"
                    >
                      <option value="">Tất cả Cấp học</option>
                      <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">
                        {{ level.name }}
                      </option>
                    </select>
                  </label>

                  <!-- Khối lớp -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Khối lớp
                    <select
                      v-model="form.grade_id"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="onTaxonomyChange"
                    >
                      <option value="">Tất cả Khối lớp</option>
                      <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">
                        {{ grade.name }}
                      </option>
                    </select>
                  </label>

                  <!-- Bộ môn -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Bộ môn
                    <select
                      v-model="form.subject_id"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="onTaxonomyChange"
                    >
                      <option value="">Tất cả Bộ môn</option>
                      <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                        {{ subject.name }}
                      </option>
                    </select>
                  </label>

                  <!-- Độ khó -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Độ khó
                    <select
                      v-model="form.difficulty"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                    >
                      <option value="easy">Dễ (Nhận biết)</option>
                      <option value="medium">Vừa (Thông hiểu)</option>
                      <option value="hard">Khó (Vận dụng)</option>
                    </select>
                  </label>
                </div>

                <!-- Tên Chủ đề -->
                <div class="grid gap-4 sm:grid-cols-2 pt-2">
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Chủ đề gợi ý
                    <select
                      v-model="selectedBankTopic"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="onBankTopicSelect"
                    >
                      <option value="">-- Chọn từ Ngân hàng --</option>
                      <option v-for="top in topicsList" :key="top.topic_name || top" :value="top.topic_name || top">
                        {{ top.topic_name || top }}{{ top.total_questions ? ` (${top.total_questions} câu)` : '' }}
                      </option>
                    </select>
                  </label>

                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Tên Chủ đề hiển thị
                    <input
                      v-model="form.topic_name"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      placeholder="VD: Hàm số bậc hai, Unit 3 Grammar..."
                      @input="onTopicInputChange"
                    />
                  </label>
                </div>
              </div>

              <!-- SECTION 3: ĐÁP ÁN -->
              <div id="studio-answers-section" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-4">
                  <h3 class="flex items-center gap-2 text-base font-bold text-slate-900">
                    <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
                    3. Phương án đáp án *
                  </h3>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-[#7C3AED] hover:bg-[#F5F3FF] transition cursor-pointer"
                    @click="addAnswerChoice"
                  >
                    <Plus :size="14" />
                    Thêm đáp án
                  </button>
                </div>

                <div class="grid gap-3">
                  <div
                    v-for="(ans, idx) in form.answers"
                    :key="idx"
                    class="flex items-center gap-3 rounded-xl border p-3 transition"
                    :class="ans.is_correct ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50'"
                  >
                    <span
                      class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-xs font-black transition"
                      :class="ans.is_correct ? 'bg-emerald-500 text-white' : 'bg-white text-slate-700 border border-slate-200'"
                    >
                      {{ ans.key }}
                    </span>

                    <input
                      :id="'studio-answer-' + idx"
                      v-model="ans.content"
                      required
                      class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      :placeholder="`Nội dung đáp án ${ans.key}...`"
                    />

                    <label
                      class="flex shrink-0 cursor-pointer select-none items-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold transition"
                      :class="ans.is_correct ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'"
                    >
                      <input
                        type="radio"
                        name="studio_correct_choice"
                        :checked="ans.is_correct"
                        class="h-4 w-4 cursor-pointer accent-emerald-500"
                        @change="setCorrectAnswer(idx)"
                      />
                      <span>{{ ans.is_correct ? '✓ Đáp án đúng' : 'Đánh dấu đúng' }}</span>
                    </label>

                    <button
                      v-if="form.answers.length > 2"
                      type="button"
                      class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer"
                      @click="removeAnswerChoice(idx)"
                    >
                      <X :size="16" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- SECTION 4: PHẠM VI HIỂN THỊ -->
              <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h3 class="flex items-center gap-2 text-base font-bold text-slate-900">
                  <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
                  4. Phạm vi hiển thị
                </h3>

                <div class="grid gap-4 sm:grid-cols-2">
                  <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                    :class="!form.is_public ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50'"
                  >
                    <input type="radio" v-model="form.is_public" :value="false" class="mt-1 h-4 w-4 accent-amber-500" />
                    <div class="grid gap-1">
                      <span class="flex items-center gap-1.5 text-xs font-bold text-amber-800">
                        <Lock :size="14" />
                        Riêng tư
                      </span>
                      <span class="text-[11px] text-slate-500">Lưu vào Kho cá nhân của bạn để sử dụng khi soạn quiz.</span>
                    </div>
                  </label>

                  <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                    :class="form.is_public ? 'border-slate-300 bg-slate-100' : 'border-slate-200 bg-slate-50'"
                  >
                    <input type="radio" v-model="form.is_public" :value="true" class="mt-1 h-4 w-4 accent-[#7C3AED]" />
                    <div class="grid gap-1">
                      <span class="flex items-center gap-1.5 text-xs font-bold text-slate-900">
                        <Globe :size="14" />
                        Công khai
                      </span>
                      <span class="text-[11px] text-slate-500">Đóng góp lên Ngân hàng câu hỏi dùng chung cho cộng đồng.</span>
                    </div>
                  </label>
                </div>
              </div>

            </div>

            <!-- RIGHT COLUMN: LIVE PREVIEW SIDEBAR -->
            <div class="space-y-6">
              <div class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                  <h4 class="text-sm font-bold text-slate-900">Live Preview</h4>
                  <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Realtime
                  </span>
                </div>

                <!-- Badges preview -->
                <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                  <span
                    class="rounded px-2 py-0.5"
                    :class="{
                      'bg-emerald-50 text-emerald-700': form.difficulty === 'easy',
                      'bg-amber-50 text-amber-700': form.difficulty === 'medium',
                      'bg-rose-50 text-rose-700': form.difficulty === 'hard'
                    }"
                  >
                    {{ form.difficulty === 'easy' ? 'Dễ' : form.difficulty === 'hard' ? 'Khó' : 'Vừa' }}
                  </span>

                  <span
                    class="inline-flex items-center gap-1 rounded px-2 py-0.5"
                    :class="form.is_public ? 'bg-purple-50 text-[#7C3AED]' : 'bg-amber-50 text-amber-700'"
                  >
                    <component :is="form.is_public ? Globe : Lock" :size="11" />
                    {{ form.is_public ? 'Công khai' : 'Riêng tư' }}
                  </span>

                  <span v-if="form.topic_name" class="rounded bg-slate-100 px-2 py-0.5 text-slate-600 truncate max-w-[150px]">
                    #{{ form.topic_name }}
                  </span>
                </div>

                <!-- Content Preview -->
                <div class="border-y border-slate-100 py-3 text-xs leading-relaxed font-bold text-slate-900 break-words space-y-2">
                  <div>
                    <span v-if="form.content.trim()">{{ form.content }}</span>
                    <span v-else class="italic text-slate-400 font-normal">[Nội dung câu hỏi sẽ hiển thị ở đây...]</span>
                  </div>

                  <!-- Image in Preview (Centered & clean framing) -->
                  <div v-if="form.image_url" class="pt-2 pb-1 flex justify-center w-full">
                    <QuestionImage
                      :src="form.image_url"
                      size="compact"
                      max-height="max-h-36 sm:max-h-44 max-w-full"
                      rounded="rounded-xl"
                      container-bg-class="bg-slate-50/70"
                      container-border-class="border border-slate-200/80 shadow-2xs hover:border-purple-300"
                      allow-zoom
                    />
                  </div>
                </div>

                <!-- Answers Preview -->
                <div class="space-y-2">
                  <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Phương án đáp án</div>
                  <div
                    v-for="ans in form.answers"
                    :key="ans.key"
                    class="flex items-center gap-2 rounded-lg border p-2 text-xs"
                    :class="ans.is_correct ? 'border-emerald-200 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 text-slate-600'"
                  >
                    <span
                      class="grid h-6 w-6 place-items-center rounded text-[11px] font-bold"
                      :class="ans.is_correct ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600'"
                    >
                      {{ ans.key }}
                    </span>
                    <span class="truncate min-w-0 flex-1">{{ ans.content || `Đáp án ${ans.key}...` }}</span>
                  </div>
                </div>

                <!-- Action Button in Sidebar -->
                <button
                  type="button"
                  class="w-full rounded-xl bg-[#7C3AED] py-3 text-xs font-bold text-white shadow-lg shadow-[#7C3AED]/20 transition hover:bg-[#6D28D9] disabled:opacity-50 cursor-pointer mt-4"
                  :disabled="isSubmitting"
                  @click="submitQuestion"
                >
                  {{ isSubmitting ? 'Đang lưu...' : '✓ Lưu câu hỏi vào Bài Quiz' }}
                </button>
              </div>
            </div>

          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, watch } from 'vue'
import { Globe, Lock, Plus, Search, X } from 'lucide-vue-next'
import { formatApiErrorMessage, myQuestionsApi, questionsBankApi, taxonomyApi } from '@/services/api'
import QuestionImageUploader from '@/components/question/QuestionImageUploader.vue'
import QuestionImage from '@/components/question/QuestionImage.vue'

const props = defineProps({
  isOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'created'])

const showToast = inject('showToast')
const isSubmitting = ref(false)
const taxonomyLevels = ref([])
const allSubjects = ref([])
const topicsList = ref([])
const selectedBankTopic = ref('')

const form = reactive({
  content: '',
  image_url: '',
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

const closeModal = () => {
  emit('close')
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
    console.error('Không tải được danh sách Chủ đề:', e)
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

const setCorrectAnswer = (targetIdx) => {
  form.answers.forEach((ans, idx) => {
    ans.is_correct = idx === targetIdx
  })
}

const validateBeforeSubmit = () => {
  if (!form.content.trim()) {
    return { message: 'Vui lòng nhập nội dung câu hỏi.', targetId: 'studio-content-input' }
  }
  for (let i = 0; i < form.answers.length; i++) {
    if (!form.answers[i].content.trim()) {
      return {
        message: `Vui lòng nhập nội dung cho Đáp án ${form.answers[i].key}.`,
        targetId: `studio-answer-${i}`
      }
    }
  }
  if (!form.answers.some((a) => a.is_correct)) {
    return { message: 'Vui lòng đánh dấu chọn 1 đáp án đúng.', targetId: 'studio-answers-section' }
  }
  return null
}

const submitQuestion = async () => {
  const errorInfo = validateBeforeSubmit()
  if (errorInfo) {
    if (showToast) showToast(errorInfo.message, 'error')
    return
  }

  isSubmitting.value = true
  try {
    const payload = {
      content: form.content.trim(),
      image_url: form.image_url ? form.image_url.trim() : null,
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

    const res = await myQuestionsApi.createQuestion(payload)
    const createdQuestion = res?.data ?? res

    if (showToast) showToast('Đã tạo câu hỏi thành công và thêm vào bài Quiz!', 'success')
    emit('created', createdQuestion)
    closeModal()

    // Reset form
    form.content = ''
    form.image_url = ''
    form.answers = [
      { key: 'A', content: '', is_correct: true },
      { key: 'B', content: '', is_correct: false },
      { key: 'C', content: '', is_correct: false },
      { key: 'D', content: '', is_correct: false }
    ]
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

<style scoped>
.studio-fade-enter-active,
.studio-fade-leave-active {
  transition: opacity 0.25s ease;
}
.studio-fade-enter-from,
.studio-fade-leave-to {
  opacity: 0;
}
</style>
