<template>
  <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-28 -top-28 h-80 w-80 rounded-full bg-[var(--accent-2)]/20 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">AI Generator</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tạo quiz bằng AI</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Nhập yêu cầu, AI sẽ sinh câu hỏi, lưu thành quiz trong backend, rồi bạn có thể làm bài ngay hoặc chuyển sang editor để sửa.</p>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <router-link 
            v-for="stat in quotaStats" 
            :key="stat.label" 
            to="/profile?tab=subscription"
            class="group relative overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition duration-300 hover:border-[var(--primary)] hover:bg-[var(--chip-active)] hover:shadow-md active:scale-95"
            title="Nhấp để xem chi tiết hạn mức & quản lý gói"
          >
            <div class="flex items-center justify-between">
              <p class="text-xs font-bold text-[var(--muted)] group-hover:text-[var(--primary)] transition-colors">{{ stat.label }}</p>
              <span class="text-[10px] font-black text-[var(--primary)] opacity-0 group-hover:opacity-100 transition-opacity">Hạn mức ➔</span>
            </div>
            <b class="mt-1 block text-2xl font-black text-[var(--text)]">{{ stat.value }}</b>
          </router-link>
        </div>

        <div class="mt-8 grid gap-4">
          <textarea v-model="prompt" class="field min-h-44" placeholder="Ví dụ: Tạo 10 câu hỏi toán lớp 10 về hàm số bậc nhất, mức trung bình, 4 đáp án mỗi câu."></textarea>

          <div class="grid gap-4 md:grid-cols-3">
            <select v-model.number="settings.count" class="quiz-select">
              <option :value="5">5 câu</option>
              <option :value="10">10 câu</option>
              <option :value="15">15 câu</option>
              <option :value="20">20 câu</option>
              <option :value="25">25 câu</option>
              <option :value="30">30 câu</option>
              <option :value="35">35 câu</option>
              <option :value="40">40 câu</option>
              <option :value="45">45 câu</option>
              <option :value="50">50 câu</option>
              <option :value="55">55 câu</option>
              <option :value="60">60 câu</option>
            </select>

            <select v-model="settings.difficulty" class="quiz-select">
              <option value="easy">Dễ</option>
              <option value="medium">Vừa</option>
              <option value="hard">Khó</option>
            </select>

            <select v-model="settings.visibility" class="quiz-select">
              <option value="private">Private</option>
              <option value="public">Public</option>
              <option value="group">Group</option>
            </select>
          </div>

          <router-link class="rounded-[1.5rem] border border-dashed border-[var(--border-strong)] bg-[var(--chip-active)] p-5 text-center transition hover:-translate-y-1" :to="`${questionBase}/ocr`">
            <p class="font-black text-[var(--text)]">Upload PDF / Image OCR</p>
            <p class="mt-1 text-sm text-[var(--muted)]">Đi tới giao diện kéo thả file và preview OCR.</p>
          </router-link>

          <div class="flex flex-wrap items-center gap-3">
            <button class="btn-primary" type="button" :disabled="isGenerating || isPolling" @click="generateQuiz">
              {{ actionLabel }}
            </button>
            <span v-if="jobStatus" class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-[var(--muted)]">
              {{ statusLabel }}
            </span>
          </div>
          <!-- KHU VỰC HIỂN THỊ TIẾN ĐỘ NGẦM (CHỈ HIỆN KHI ĐANG CHẠY) -->
          <!-- <div v-if="isGenerating || isPolling" class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6">
            <h3 class="text-xs font-black uppercase tracking-[0.16em] text-[var(--text)] mb-5">Tiến trình xử lý AI</h3>

            
            <div class="w-full bg-[var(--surface)] rounded-full h-1.5 mb-6 overflow-hidden border border-[var(--border)]">
              <div class="bg-[var(--primary)] h-1.5 transition-all duration-500 ease-out" :style="{ width: progressPercentage + '%' }"></div>
            </div>

            
            <div class="space-y-5 relative before:absolute before:inset-0 before:left-[15px] before:bg-[var(--border)] before:w-[1px]">
              
              <div class="flex items-start space-x-4 relative">
                <div :class="getStepClass('validate_prompt')" class="w-8 h-8 rounded-full flex items-center justify-center z-10 font-bold border-2 text-xs transition-colors duration-300">1</div>
                <div class="flex-1 pt-1.5">
                  <p class="text-sm transition-colors duration-300" :class="getTextClass('validate_prompt')">Kiểm tra và tối ưu câu lệnh</p>
                  <span v-if="currentStep === 'validate_prompt'" class="text-xs text-[var(--primary)] mt-1 block">Đang phân tích...</span>
                </div>
              </div>
              
              <div class="flex items-start space-x-4 relative">
                <div :class="getStepClass('calling_ai_api')" class="w-8 h-8 rounded-full flex items-center justify-center z-10 font-bold border-2 text-xs transition-colors duration-300">2</div>
                <div class="flex-1 pt-1.5">
                  <p class="text-sm transition-colors duration-300" :class="getTextClass('calling_ai_api')">Gửi dữ liệu và chờ AI phản hồi</p>
                  <span v-if="currentStep === 'calling_ai_api'" class="text-xs text-[var(--primary)] mt-1 block">AI đang suy nghĩ (Có thể mất 10-20s)...</span>
                </div>
              </div>
              
              <div class="flex items-start space-x-4 relative">
                <div :class="getStepClass('parsing_ai_response')" class="w-8 h-8 rounded-full flex items-center justify-center z-10 font-bold border-2 text-xs transition-colors duration-300">3</div>
                <div class="flex-1 pt-1.5">
                  <p class="text-sm transition-colors duration-300" :class="getTextClass('parsing_ai_response')">Bóc tách dữ liệu câu hỏi</p>
                </div>
              </div>
              
              <div class="flex items-start space-x-4 relative">
                <div :class="getStepClass('saving_to_database')" class="w-8 h-8 rounded-full flex items-center justify-center z-10 font-bold border-2 text-xs transition-colors duration-300">4</div>
                <div class="flex-1 pt-1.5">
                  <p class="text-sm transition-colors duration-300" :class="getTextClass('saving_to_database')">Đồng bộ đáp án vào hệ thống</p>
                </div>
              </div>
            </div>
          </div> -->
          <div v-if="isGenerating || isPolling" class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm font-bold text-[var(--text)]">{{ currentLabel }}</p>
              <span class="rounded-full bg-emerald-500/10 border border-emerald-500/30 px-3 py-1 text-xs font-black text-emerald-300">
                {{ Math.round(displayPercent) }}%
              </span>
            </div>

            <div class="w-full bg-[var(--surface)] rounded-full h-2 overflow-hidden border border-[var(--border)]">
              <div
                class="h-2 rounded-full bg-gradient-to-r from-[var(--primary)] to-fuchsia-400 transition-[width] duration-150 ease-linear"
                :style="{ width: displayPercent + '%' }"
              ></div>
            </div>
          </div>
          <!-- KẾT THÚC KHU VỰC HIỂN THỊ TIẾN ĐỘ -->
        </div>

        <div v-if="errorMessage" class="mt-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
          <span>{{ errorMessage }}</span>
          <router-link 
            v-if="errorMessage.includes('token') || errorMessage.includes('quota') || errorMessage.includes('hết')"
            to="/profile?tab=subscription" 
            class="shrink-0 rounded-full border border-rose-500/40 bg-rose-500/20 px-4 py-1.5 text-xs font-black text-rose-200 hover:bg-rose-500/30 transition"
          >
            ⚡ Kiểm Tra Hạn Mức & Nâng Cấp ➔
          </router-link>
        </div>

        <div v-if="successMessage" class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">
          {{ successMessage }}
        </div>
      </div>
    </article>

    <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Preview</p>
      <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Bản sinh từ AI</h2>

      <div v-if="!generatedQuiz" class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6 text-center text-sm font-bold text-[var(--muted)]">
        Chưa có nội dung. Nhập prompt rồi bấm tạo quiz.
      </div>

      <div v-else class="mt-6 grid gap-4">
        <div class="rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
          <b class="text-[var(--text)]">{{ generatedQuiz.title }}</b>
          <p class="mt-2 text-sm text-[var(--muted)]">{{ generatedQuiz.description || prompt }}</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ generatedQuiz.questions?.length || 0 }} câu</span>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ Math.ceil((generatedQuiz.time_limit_seconds || 600) / 60) }} phút</span>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--muted)]">Tag {{ generatedQuiz.tag || 'AI' }}</span>
          </div>
        </div>

        <div class="max-h-[380px] overflow-auto rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 scrollbar-soft">
          <article v-for="(question, index) in generatedQuiz.questions" :key="question.id || index" class="mb-4 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 last:mb-0">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--primary)]">Câu {{ index + 1 }} • {{ question.points || 1 }} điểm</p>
            <p class="mt-2 text-sm font-bold leading-6 text-[var(--text)]">{{ question.text || question.content }}</p>
            <div class="mt-3 grid gap-2">
              <div v-for="answer in question.answers" :key="answer.id || answer.key" class="rounded-xl border px-3 py-2 text-sm font-semibold" :class="answer.is_correct ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]'">
                {{ answer.answer_key || answer.key }}. {{ answer.text || answer.content }}
              </div>
            </div>
          </article>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
          <router-link class="btn-primary text-center" :to="`/quizzes/${generatedQuiz.id}/play`">Làm quiz ngay</router-link>
          <button class="btn-ghost" type="button" @click="openInEditor">Chỉnh sửa editor</button>
        </div>
      </div>
    </aside>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { aiApi, authApi, currentUserStorage } from '@/services/api'

const route = useRoute()
const router = useRouter()
const questionBase = computed(() => route.path.startsWith('/dashboard') ? '/dashboard/questions' : '/admin/questions')

const prompt = ref('')
const settings = ref({
  count: 10,
  difficulty: 'medium',
  language: 'vi',
  visibility: 'private',
})

const user = computed(() => currentUserStorage.get())
const ocrLimit = computed(() => {
  const role = String(user.value?.role || 'free').toLowerCase()
  if (['admin', 'ultra'].includes(role)) return 'Không giới hạn'
  if (role === 'pro') return '50 lượt/tháng'
  if (role === 'plus') return '10 lượt/tháng'
  return 'Yêu cầu gói Plus'
})
const quotaStats = computed(() => [
  { label: 'Role', value: user.value?.role_label || user.value?.role || 'Guest' },
  { label: 'AI còn lại', value: user.value?.ai_quota_remaining ?? 0 },
  { label: 'OCR hạn mức', value: ocrLimit.value },
])

const isGenerating = ref(false)
const isPolling = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const jobId = ref('')
const jobStatus = ref('')
const currentStep = ref('') // <-- Thêm biến này
const generatedQuiz = ref(null)
let pollTimer = null

const statusLabel = computed(() => ({
  pending: 'Đang chờ',
  processing: 'Đang sinh quiz',
  completed: 'Hoàn tất',
  failed: 'Thất bại',
}[jobStatus.value] || jobStatus.value))

const actionLabel = computed(() => {
  if (isGenerating.value) return 'Đang gửi yêu cầu...'
  if (isPolling.value) return 'AI đang tạo quiz...'
  return 'Tạo quiz bằng AI'
})

// Mốc % mục tiêu ứng với mỗi current_step từ backend
const stepTargets = {
  '': 5,
  validate_prompt: 20,
  calling_ai_api: 70,
  parsing_ai_response: 85,
  saving_to_database: 95,
  completed: 100,
}

const stepLabels = {
  '': 'Đang khởi tạo...',
  validate_prompt: 'Đang kiểm tra và tối ưu câu lệnh...',
  calling_ai_api: 'AI đang suy nghĩ (có thể mất 10-20s)...',
  parsing_ai_response: 'Đang bóc tách dữ liệu câu hỏi...',
  saving_to_database: 'Đang đồng bộ đáp án vào hệ thống...',
  completed: 'Hoàn tất!',
}

const displayPercent = ref(0)
let rafId = null

const currentTarget = computed(() => stepTargets[currentStep.value] ?? displayPercent.value)
const currentLabel = computed(() => stepLabels[currentStep.value] ?? 'Đang xử lý...')


const startProgressLoop = () => {
  if (rafId) cancelAnimationFrame(rafId)
  rafId = requestAnimationFrame(animateProgress)
}

const stopProgressLoop = () => {
  if (rafId) {
    cancelAnimationFrame(rafId)
    rafId = null
  }
}

const animateProgress = () => {
  const target = currentTarget.value
  
  if (displayPercent.value < target) {
    const diff = target - displayPercent.value
    
    // Trick "đứng hình" mượt mà 
    // Cách đích > 5% thì tiến bình thường. Cách <= 5% thì nhích cực chậm (0.01%/frame) 
    // để trong lúc chờ 15s AI, thanh % vẫn trôi rề rề chứ không đứng yên.
    const step = diff > 5 ? Math.max(0.1, diff * 0.05) : 0.01
    
    displayPercent.value = Math.min(target, displayPercent.value + step)
  }
  rafId = requestAnimationFrame(animateProgress)
}

const isInvalidPromptMessage = (message = '') =>
  message.includes('Prompt chưa rõ nội dung') ||
  message.includes('Vui lòng nhập đúng prompt') ||
  message.includes('invalid_prompt')

const stopPolling = () => {
  if (pollTimer) {
    clearTimeout(pollTimer)
    pollTimer = null
  }
}

const pollJob = async () => {
  if (!jobId.value) return

  isPolling.value = true

  try {
    const job = await aiApi.getJob(jobId.value)
    jobStatus.value = job.status
    
    
    if (job.current_step) {
      currentStep.value = job.current_step 
    }

    if (job.status === 'completed' && job.quiz_id) {
      generatedQuiz.value = job.quiz_full || job.quiz || null
      await authApi.me()
      isPolling.value = false
      currentStep.value = 'completed' 
      displayPercent.value = 100
      stopPolling()
      stopProgressLoop()
      return
    }

    if (job.status === 'failed') {
      const jmsg = job.error_message || ''
      if (jmsg.includes('AI quota exhausted')) {
        errorMessage.value = 'Bạn đã hết token'
      } else {
        errorMessage.value = jmsg || 'AI tạo quiz thất bại.'
      }
      isPolling.value = false
      stopPolling()
      stopProgressLoop()
      return
    }

    pollTimer = setTimeout(pollJob, 500)
  } catch (error) {
    errorMessage.value = `Không lấy được trạng thái AI job: ${error.message}`
    isPolling.value = false
    stopPolling()
    stopProgressLoop()
  }
}

const generateQuiz = async () => {
  if (!prompt.value.trim()) {
    errorMessage.value = 'Bạn cần nhập prompt để AI sinh quiz.'
    return
  }

  displayPercent.value = 0
  currentStep.value = 'validate_prompt'
  isGenerating.value = true
  errorMessage.value = ''
  successMessage.value = ''
  generatedQuiz.value = null
  jobId.value = ''
  jobStatus.value = ''
  stopPolling()
  startProgressLoop()

  try {
    const response = await aiApi.generate({
      prompt: prompt.value.trim(),
      count: settings.value.count,
      difficulty: settings.value.difficulty,
      language: settings.value.language,
      visibility: settings.value.visibility,
    })

    jobId.value = response.job_id
    jobStatus.value = response.status
    successMessage.value = 'Đã gửi yêu cầu sang AI. Hệ thống đang sinh quiz và lưu vào backend.'
    await pollJob()
  } catch (error) {
    const msg = error?.message || String(error)

    if (isInvalidPromptMessage(msg)) {
      errorMessage.value = msg
    } else if (msg.includes('AI quota exhausted')) {
      errorMessage.value = 'Bạn đã hết token'
    } else {
      errorMessage.value = `Không tạo được AI quiz: ${msg}`
    }
    stopProgressLoop()
  } finally {
    isGenerating.value = false
  }
}

const openInEditor = () => {
  if (!generatedQuiz.value?.id) return
  router.push(`${questionBase.value}/edit/${generatedQuiz.value.id}`)
}

onBeforeUnmount(() => {
  stopProgressLoop()
  stopPolling()
})
</script>

<style scoped>
.quiz-select {
  appearance: none;
  -webkit-appearance: none;
  display: block;
  width: 100%;
  height: 46px;
  padding: 0 2.5rem 0 1rem;
  border: 1px solid var(--border);
  border-radius: 14px;
  background-color: var(--surface-soft);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239b2cff' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 14px;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text);
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
  backdrop-filter: blur(12px);
}

.quiz-select:hover {
  border-color: var(--border-strong);
  background-color: rgba(255, 255, 255, 0.1);
}

html[data-theme="light"] .quiz-select:hover {
  background-color: rgba(95, 50, 160, 0.12);
}

.quiz-select:focus {
  border-color: var(--border-strong);
  box-shadow: 0 0 0 3px rgba(155, 44, 255, 0.2);
}

.quiz-select option {
  font-size: 0.875rem;
  font-weight: 600;
  padding: 10px 12px;
  color: var(--text);
  background-color: var(--bg-2);
}
</style>
