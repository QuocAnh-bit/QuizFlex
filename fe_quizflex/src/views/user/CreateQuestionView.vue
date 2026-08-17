<template>
  <section class="grid gap-6 min-w-0 mx-auto w-full max-w-[1400px] xl:grid-cols-[minmax(0,1fr)_380px]">
    <!-- MAIN FORM COLUMN -->
    <form
      class="relative min-w-0 overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl grid gap-6"
      @submit.prevent="submitForm(false)"
    >
      <!-- Subtle Background Glows -->
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="pointer-events-none absolute -left-24 top-1/2 h-64 w-64 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

      <div class="relative z-10 grid gap-6">
        <!-- Top Navigation & Page Title -->
        <div>
          <button 
            type="button"
            class="inline-flex items-center gap-2 text-xs font-bold text-[var(--muted)] hover:text-[var(--primary)] transition duration-200 mb-3"
            @click="goBack"
          >
            <span>← Quay lại kho câu hỏi</span>
          </button>

          <h1 class="text-3xl sm:text-4xl font-black tracking-[-0.06em] text-[var(--text)] flex items-center gap-3">
            <span>Tạo câu hỏi mới</span>
            <span class="inline-block h-2.5 w-2.5 rounded-full bg-[var(--primary)] shadow-[0_0_12px_var(--primary)]"></span>
          </h1>
          <p class="mt-2 text-sm leading-6 text-[var(--muted)]">
            Soạn thảo câu hỏi trắc nghiệm, các lựa chọn đáp án và thiết lập thông tin phân loại cho ngân hàng câu hỏi.
          </p>
        </div>

        <!-- 1. Nội dung câu hỏi -->
        <div class="grid gap-3 pt-2">
          <label for="question-content-input" class="text-lg font-black tracking-[-0.04em] text-[var(--text)] flex items-center">
            <span class="h-4 w-1 rounded-full bg-[var(--primary)] inline-block mr-2.5 shadow-[0_0_8px_var(--primary)]"></span>
            <span>1. Nội dung câu hỏi</span>
          </label>
          <textarea 
            id="question-content-input"
            v-model="form.content" 
            required 
            class="field min-h-36 text-sm font-medium leading-relaxed" 
            placeholder="Nhập nội dung câu hỏi tại đây..."
          ></textarea>
        </div>

        <!-- 2. Phân loại -->
        <div class="grid gap-4 pt-5 border-t border-[var(--border)]">
          <h2 class="text-lg font-black tracking-[-0.04em] text-[var(--text)] flex items-center">
            <span class="h-4 w-1 rounded-full bg-[var(--primary)] inline-block mr-2.5 shadow-[0_0_8px_var(--primary)]"></span>
            <span>2. Phân loại</span>
          </h2>

          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Cấp học
              <select v-model="form.education_level_id" class="field" @change="onLevelChange">
                <option value="">Tất cả cấp học</option>
                <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Khối lớp
              <select v-model="form.grade_id" class="field" @change="onTaxonomyChange">
                <option value="">Tất cả khối lớp</option>
                <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Bộ môn
              <select v-model="form.subject_id" class="field" @change="onTaxonomyChange">
                <option value="">Tất cả bộ môn</option>
                <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Độ khó
              <select v-model="form.difficulty" class="field">
                <option value="easy">Dễ (Nhận biết)</option>
                <option value="medium">Vừa (Thông hiểu)</option>
                <option value="hard">Khó (Vận dụng)</option>
              </select>
            </label>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Chọn Chủ đề có sẵn (từ Ngân hàng)
              <select v-model="selectedBankTopic" class="field cursor-pointer" @change="onBankTopicSelect">
                <option value="">-- Chọn chủ đề từ kho --</option>
                <option v-for="top in topicsList" :key="top.topic_name || top" :value="top.topic_name || top">
                  {{ top.topic_name || top }}{{ top.total_questions ? ' (' + top.total_questions + ' câu)' : '' }}
                </option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Tên Chủ đề (hoặc nhập chủ đề mới)
              <input 
                v-model="form.topic_name" 
                class="field" 
                placeholder="VD: Hàm số, Tiếng Anh B1, Lịch sử Việt Nam..." 
                @input="onTopicInputChange"
              />
            </label>
          </div>
        </div>

        <!-- 3. Đáp án -->
        <div id="answers-section" class="grid gap-4 pt-5 border-t border-[var(--border)]">
          <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-black tracking-[-0.04em] text-[var(--text)] flex items-center">
              <span class="h-4 w-1 rounded-full bg-[var(--primary)] inline-block mr-2.5 shadow-[0_0_8px_var(--primary)]"></span>
              <span>3. Đáp án</span>
            </h2>

            <button 
              type="button" 
              class="btn-ghost !px-3 !py-1 text-xs font-bold text-[var(--primary)] hover:bg-[var(--primary)]/10"
              @click="addAnswerChoice"
            >
              + Thêm đáp án
            </button>
          </div>

          <div class="grid gap-3">
            <div 
              v-for="(ans, idx) in form.answers" 
              :key="idx" 
              class="flex items-center gap-3 rounded-2xl border p-2.5 transition duration-200"
              :class="ans.is_correct ? 'border-emerald-500/50 bg-emerald-500/10 shadow-[0_0_20px_rgba(16,185,129,0.08)]' : 'border-[var(--border)] bg-[var(--surface-soft)] hover:border-[var(--border-strong)]'"
            >
              <!-- Key Label A, B, C, D -->
              <span 
                class="grid h-9 w-9 place-items-center rounded-xl font-black text-xs shrink-0 transition"
                :class="ans.is_correct ? 'bg-emerald-500 text-white shadow-md' : 'bg-[var(--chip-active)] text-[var(--text)]'"
              >
                {{ ans.key }}
              </span>
              
              <!-- Input Content -->
              <input 
                :id="'answer-input-' + idx"
                v-model="ans.content" 
                required 
                class="field text-xs flex-1 !py-2.5" 
                :placeholder="`Nội dung đáp án ${ans.key}...`" 
              />

              <!-- Radio selector for correct answer -->
              <label 
                class="flex items-center gap-2 text-xs font-bold shrink-0 cursor-pointer px-3.5 py-2 rounded-xl border transition select-none"
                :class="ans.is_correct ? 'border-emerald-500/60 bg-emerald-500/20 text-emerald-300 font-black' : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:border-[var(--border-strong)]'"
              >
                <input 
                  type="radio" 
                  name="correct_answer_choice" 
                  :checked="ans.is_correct" 
                  @change="setCorrectAnswer(idx)" 
                  class="accent-emerald-500 h-4 w-4 cursor-pointer" 
                />
                <span>{{ ans.is_correct ? 'Đáp án đúng' : 'Đánh dấu đúng' }}</span>
              </label>

              <!-- Remove button -->
              <button 
                v-if="form.answers.length > 2" 
                type="button" 
                class="h-8 w-8 grid place-items-center rounded-xl text-xs text-[var(--muted)] hover:text-rose-400 hover:bg-rose-500/10 transition shrink-0"
                title="Xóa đáp án"
                @click="removeAnswerChoice(idx)"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- 4. Phạm vi hiển thị -->
        <div class="grid gap-4 pt-5 border-t border-[var(--border)]">
          <h2 class="text-lg font-black tracking-[-0.04em] text-[var(--text)] flex items-center">
            <span class="h-4 w-1 rounded-full bg-[var(--primary)] inline-block mr-2.5 shadow-[0_0_8px_var(--primary)]"></span>
            <span>4. Phạm vi hiển thị</span>
          </h2>

          <div class="grid md:grid-cols-2 gap-4">
            <label 
              class="flex items-start gap-3 rounded-2xl border p-4 cursor-pointer transition duration-200"
              :class="!form.is_public ? 'border-amber-500/60 bg-amber-500/10 text-[var(--text)] shadow-sm' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] hover:border-[var(--border-strong)]'"
            >
              <input type="radio" v-model="form.is_public" :value="false" class="mt-1 accent-amber-500 h-4 w-4" />
              <div class="grid gap-1">
                <span class="font-black text-sm text-amber-300 flex items-center gap-1.5">
                  <span>🔒</span> Riêng tư
                </span>
                <span class="text-xs leading-relaxed text-[var(--muted)]">Lưu vào kho cá nhân của bạn để sử dụng khi tạo quiz.</span>
              </div>
            </label>

            <label 
              class="flex items-start gap-3 rounded-2xl border p-4 cursor-pointer transition duration-200"
              :class="form.is_public ? 'border-[var(--primary)] bg-[var(--primary)]/10 text-[var(--text)] shadow-sm' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] hover:border-[var(--border-strong)]'"
            >
              <input type="radio" v-model="form.is_public" :value="true" class="mt-1 accent-[var(--primary)] h-4 w-4" />
              <div class="grid gap-1">
                <span class="font-black text-sm text-[var(--primary)] flex items-center gap-1.5">
                  <span>🌐</span> Công khai
                </span>
                <span class="text-xs leading-relaxed text-[var(--muted)]">Chia sẻ lên ngân hàng câu hỏi dùng chung cho mọi người.</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Footer Actions Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-[var(--border)]">
          <button 
            type="button" 
            class="btn-ghost text-xs font-bold text-[var(--muted)] hover:text-[var(--text)]" 
            @click="goBack"
          >
            Hủy
          </button>

          <div class="flex flex-wrap items-center gap-3">
            <button 
              type="button" 
              class="btn-ghost !px-5 !py-2.5 text-xs font-bold hover:bg-[var(--chip-active)]"
              :disabled="isSubmitting"
              @click="submitForm(true)"
            >
              Lưu & tạo câu khác
            </button>

            <button 
              type="submit" 
              class="btn-primary !px-8 !py-3 text-xs font-black shadow-lg shadow-[var(--primary)]/25 hover:shadow-[var(--primary)]/40 transition duration-200 hover:-translate-y-0.5" 
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? "Đang lưu..." : "Lưu câu hỏi" }}
            </button>
          </div>
        </div>
      </div>
    </form>

    <!-- FLOATING SAVING WIDGET (TẠO QUIZ THỦ CÔNG STYLE) -->
    <div class="fixed bottom-6 right-6 z-[60] flex items-center gap-3 rounded-[1.6rem] border border-[var(--border)] bg-[var(--surface)]/95 p-2.5 shadow-[0_20px_50px_rgba(0,0,0,0.35)] backdrop-blur-xl transition duration-200">
      <button 
        type="button" 
        class="btn-ghost !px-4 !py-2 text-xs font-bold text-[var(--muted)] hover:text-[var(--text)]" 
        @click="goBack"
      >
        Hủy
      </button>

      <button 
        type="button" 
        class="btn-ghost !px-4 !py-2 text-xs font-bold hover:bg-[var(--chip-active)]"
        :disabled="isSubmitting"
        @click="submitForm(true)"
      >
        Lưu & tạo câu khác
      </button>

      <button 
        type="button" 
        class="btn-primary !px-6 !py-2.5 text-xs font-black shadow-lg shadow-[var(--primary)]/25 hover:shadow-[var(--primary)]/40 transition duration-200 hover:-translate-y-0.5" 
        :disabled="isSubmitting"
        @click="submitForm(false)"
      >
        <span>{{ isSubmitting ? "Đang lưu..." : "Lưu câu hỏi" }}</span>
      </button>
    </div>

    <!-- RIGHT ASIDE: LUXURY LIVE PREVIEW -->
    <aside class="grid content-start gap-5">
      <article class="overflow-hidden rounded-[2.2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-7 shadow-[var(--shadow-card)] backdrop-blur-2xl grid gap-5 sticky top-6">
        
        <!-- 1. Header Bar -->
        <div class="flex items-center justify-between border-b border-[var(--border)] pb-4">
          <h3 class="text-base font-black tracking-[-0.03em] text-[var(--text)]">
            Live Preview
          </h3>

          <span class="flex items-center gap-1.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 px-3 py-1 text-[11px] font-black text-emerald-400">
            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Live
          </span>
        </div>

        <!-- 2. Tag Meta Section (Không bị chèn lẹm, thoáng đạt) -->
        <div class="grid gap-3 pt-1">
          <!-- Level, Grade, Subject Breadcrumb Path -->
          <div v-if="selectedLevelName || selectedGradeName || selectedSubjectName" class="flex flex-wrap items-center gap-2 text-xs font-bold text-[var(--muted)]">
            <span v-if="selectedLevelName" class="text-[var(--text)] font-black">{{ selectedLevelName }}</span>
            <span v-if="selectedLevelName && (selectedGradeName || selectedSubjectName)" class="opacity-30">•</span>
            
            <span v-if="selectedGradeName" class="text-[var(--text)] font-black">{{ selectedGradeName }}</span>
            <span v-if="selectedGradeName && selectedSubjectName" class="opacity-30">•</span>

            <span v-if="selectedSubjectName" class="text-[var(--primary)] font-black">{{ selectedSubjectName }}</span>
          </div>

          <!-- Sleek Badges Grid (Difficulty + Privacy + Topic Tag) -->
          <div class="flex flex-wrap items-center gap-2.5">
            <!-- Difficulty Badge -->
            <span 
              class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1 text-xs font-black border transition"
              :class="{
                'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 shadow-[0_0_10px_rgba(16,185,129,0.1)]': form.difficulty === 'easy',
                'border-amber-500/30 bg-amber-500/10 text-amber-400 shadow-[0_0_10px_rgba(245,158,11,0.1)]': form.difficulty === 'medium',
                'border-rose-500/30 bg-rose-500/10 text-rose-400 shadow-[0_0_10px_rgba(244,63,94,0.1)]': form.difficulty === 'hard'
              }"
            >
              <span 
                class="h-2 w-2 rounded-full"
                :class="{
                  'bg-emerald-400 shadow-[0_0_6px_rgb(52,211,153)]': form.difficulty === 'easy',
                  'bg-amber-400 shadow-[0_0_6px_rgb(251,191,36)]': form.difficulty === 'medium',
                  'bg-rose-400 shadow-[0_0_6px_rgb(251,113,133)]': form.difficulty === 'hard'
                }"
              ></span>
              <span>{{ difficultyText(form.difficulty) }}</span>
            </span>

            <!-- Privacy Scope Badge -->
            <span 
              class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1 text-xs font-black border transition"
              :class="form.is_public ? 'border-[var(--primary)]/30 bg-[var(--primary)]/10 text-[var(--primary)]' : 'border-amber-500/30 bg-amber-500/10 text-amber-400'"
            >
              <span>{{ form.is_public ? '🌐 Công khai' : '🔒 Riêng tư' }}</span>
            </span>

            <!-- Topic Hashtag Pill -->
            <span 
              v-if="form.topic_name" 
              class="inline-flex items-center gap-1 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-bold text-[var(--muted)] truncate max-w-[200px]"
              title="Chủ đề"
            >
              <span class="text-[var(--primary)] font-black">#</span>
              <span class="truncate">{{ form.topic_name }}</span>
            </span>
          </div>
        </div>

        <!-- 3. Question Content (Tối giản tinh tế, không ô hộp thô cứng) -->
        <div class="border-t border-b border-[var(--border)] py-4 my-1">
          <p class="text-base font-extrabold text-[var(--text)] leading-relaxed break-words">
            <span v-if="form.content.trim()">{{ form.content }}</span>
            <span v-else class="text-[var(--muted)] italic font-normal text-sm opacity-60">[Nội dung câu hỏi sẽ xuất hiện tại đây khi bạn nhập...]</span>
          </p>
        </div>

        <!-- 4. Answers Preview Grid -->
        <div class="grid gap-2.5">
          <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-wider text-[var(--muted)] px-0.5">
            <span>Phương án đáp án</span>
            <span>{{ form.answers.length }} phương án</span>
          </div>
          
          <div 
            v-for="ans in form.answers" 
            :key="ans.key"
            class="flex items-center gap-3 rounded-2xl border p-3 text-xs font-bold transition duration-200"
            :class="ans.is_correct ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-300 shadow-[0_0_15px_rgba(16,185,129,0.08)]' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]'"
          >
            <span 
              class="grid h-7 w-7 place-items-center rounded-xl font-black text-xs shrink-0 transition"
              :class="ans.is_correct ? 'bg-emerald-500 text-white shadow-sm' : 'bg-[var(--chip-active)] text-[var(--text)]'"
            >
              {{ ans.key }}
            </span>

            <span class="flex-1 truncate text-xs font-semibold">
              <span v-if="ans.content.trim()" :class="{ 'text-[var(--text)] font-bold': ans.is_correct }">{{ ans.content }}</span>
              <span v-else class="italic opacity-50 font-normal">Nhập nội dung đáp án {{ ans.key }}...</span>
            </span>

            <span v-if="ans.is_correct" class="text-emerald-400 text-xs font-black shrink-0 flex items-center gap-1 bg-emerald-500/20 px-2.5 py-1 rounded-lg border border-emerald-500/30">
              ✓ Đáp án đúng
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
    { key: 'D', content: '', is_correct: false },
  ]
})

const fetchTopicsList = async () => {
  try {
    const params = {
      education_level_id: form.education_level_id || undefined,
      grade_id: form.grade_id || undefined,
      subject_id: form.subject_id || undefined,
    }
    const data = await questionsBankApi.fetchTopics(params)
    topicsList.value = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : [])
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

const selectedLevelName = computed(() => {
  if (!form.education_level_id) return ''
  const item = taxonomyLevels.value.find(l => l.id === Number(form.education_level_id))
  return item ? item.name : ''
})

const selectedGradeName = computed(() => {
  if (!form.grade_id) return ''
  const item = availableGrades.value.find(g => g.id === Number(form.grade_id))
  return item ? item.name : ''
})

const selectedSubjectName = computed(() => {
  if (!form.subject_id) return ''
  const item = availableSubjects.value.find(s => s.id === Number(form.subject_id))
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
  if (!form.answers.some(a => a.is_correct)) {
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
    if (typeof el.focus === 'function') {
      el.focus()
    }
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

  if (!form.answers.some(a => a.is_correct)) {
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
      answers: form.answers.map(a => ({
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
        { key: 'D', content: '', is_correct: false },
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
