<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
          <Sparkles class="h-5 w-5" />
        </div>
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Trí tuệ nhân tạo</p>
          <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Tạo đề thi bằng AI</h1>
          <p class="mt-1 text-sm font-medium text-[var(--muted)]">Nhập chủ đề hoặc tài liệu, AI sẽ tự động sinh ngân hàng câu hỏi và tạo quiz hoàn chỉnh.</p>
        </div>
      </div>
      <router-link :to="`${questionBase}/ocr`" class="btn-secondary inline-flex items-center gap-2 text-xs px-3.5 py-1.5">
        <FileText class="h-3.5 w-3.5" />
        <span>Chuyển sang OCR từ tài liệu</span>
      </router-link>
    </div>

    <!-- Quota Stats & Limit Row -->
    <div class="grid gap-4 sm:grid-cols-3">
      <router-link 
        v-for="stat in quotaStats" 
        :key="stat.label" 
        to="/profile?tab=subscription"
        class="card p-4 space-y-1 hover:border-[#7C3AED] transition"
      >
        <span class="text-[10px] font-bold uppercase text-slate-400 block">{{ stat.label }}</span>
        <strong class="text-xl font-black text-slate-900 block">{{ stat.value }}</strong>
        <span class="text-[10px] font-bold text-[#7C3AED] block">Xem gói nâng cấp →</span>
      </router-link>
    </div>

    <!-- Main Generator & Preview Layout -->
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
      <!-- Left: Generator Form -->
      <article class="card p-6 sm:p-8 space-y-5">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Cấu hình yêu cầu sinh đề</h2>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Prompt / Chủ đề câu hỏi</label>
          <textarea 
            v-model="prompt" 
            class="field text-xs resize-none" 
            rows="5"
            placeholder="Ví dụ: Tạo 10 câu hỏi trắc nghiệm Lịch sử Việt Nam lớp 12 giai đoạn 1945-1954, mức độ vừa, có giải thích đáp án..."
          ></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-600 block">Số lượng câu</label>
            <select v-model.number="settings.count" class="field text-xs">
              <option :value="5">5 câu</option>
              <option :value="10">10 câu</option>
              <option :value="15">15 câu</option>
              <option :value="20">20 câu</option>
              <option :value="25">25 câu</option>
              <option :value="30">30 câu</option>
              <option :value="40">40 câu</option>
              <option :value="50">50 câu</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-600 block">Độ khó</label>
            <select v-model="settings.difficulty" class="field text-xs">
              <option value="easy">Dễ</option>
              <option value="medium">Vừa</option>
              <option value="hard">Khó</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-600 block">Hiển thị</label>
            <select v-model="settings.visibility" class="field text-xs">
              <option value="private">Private</option>
              <option value="public">Public</option>
              <option value="group">Group</option>
            </select>
          </div>
        </div>

        <!-- Action & Progress -->
        <div class="pt-3 border-t border-slate-100 space-y-4">
          <div class="flex items-center gap-3">
            <button 
              class="btn-primary text-xs px-5 py-2.5" 
              type="button" 
              :disabled="isGenerating || isPolling" 
              @click="generateQuiz"
            >
              {{ actionLabel }}
            </button>
            <span v-if="jobStatus" class="rounded bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 uppercase">
              {{ statusLabel }}
            </span>
          </div>

          <!-- Progress bar -->
          <div v-if="isGenerating || isPolling" class="rounded-xl border border-purple-100 bg-purple-50 p-4 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-[#7C3AED]">{{ currentLabel }}</span>
              <span class="font-black text-[#7C3AED]">{{ Math.round(displayPercent) }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-purple-200">
              <div 
                class="h-full rounded-full bg-[#7C3AED] transition-all duration-150" 
                :style="{ width: `${displayPercent}%` }"
              ></div>
            </div>
          </div>

          <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs font-bold text-red-700">
            {{ errorMessage }}
          </div>

          <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-bold text-emerald-700">
            {{ successMessage }}
          </div>
        </div>
      </article>

      <!-- Right: Preview -->
      <aside class="card p-6 space-y-4">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Bản xem trước câu hỏi</h2>

        <div v-if="!generatedQuiz" class="py-12 text-center text-xs text-slate-400">
          Chưa có nội dung. Hãy nhập prompt và bấm "Tạo quiz bằng AI".
        </div>

        <div v-else class="space-y-4 text-xs">
          <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-2">
            <b class="text-sm font-bold text-slate-900 block">{{ generatedQuiz.title }}</b>
            <p class="text-slate-500 line-clamp-2">{{ generatedQuiz.description || prompt }}</p>
            <div class="flex items-center gap-2 pt-1">
              <span class="rounded bg-white border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                {{ generatedQuiz.questions?.length || 0 }} câu hỏi
              </span>
              <span class="rounded bg-white border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                {{ Math.ceil((generatedQuiz.time_limit_seconds || 600) / 60) }} phút
              </span>
            </div>
          </div>

          <!-- Questions scrollable list -->
          <div class="max-h-[360px] overflow-y-auto space-y-3 pr-1">
            <div 
              v-for="(question, index) in generatedQuiz.questions" 
              :key="question.id || index" 
              class="rounded-xl border border-slate-100 bg-slate-50 p-3 space-y-2"
            >
              <p class="font-bold text-slate-900 leading-relaxed">
                Câu {{ index + 1 }}: {{ question.text || question.content }}
              </p>
              <div class="space-y-1">
                <div 
                  v-for="answer in question.answers" 
                  :key="answer.id || answer.key" 
                  class="rounded-lg p-2 text-xs" 
                  :class="answer.is_correct ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'bg-white text-slate-700'"
                >
                  {{ answer.answer_key || answer.key }}. {{ answer.text || answer.content }}
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
            <router-link class="btn-primary text-xs text-center py-2" :to="`/quizzes/${generatedQuiz.id}/play`">
              Làm thử ngay →
            </router-link>
            <button class="btn-secondary text-xs py-2" type="button" @click="openInEditor">
              Chỉnh sửa đề
            </button>
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Sparkles } from '@lucide/vue'
import { aiApi, authApi, currentUserStorage } from '@/services/api'

const route = useRoute()
const router = useRouter()
const questionBase = computed(() => route.path.startsWith('/dashboard') ? '/dashboard/questions' : '/admin/questions')

const prompt = ref('')
const MAX_PROMPT_LENGTH = 2000
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
  { label: 'Vai trò hiện tại', value: user.value?.role_label || user.value?.role || 'Guest' },
  { label: 'Lượt sinh AI còn lại', value: user.value?.ai_quota_remaining ?? 0 },
  { label: 'Hạn mức quét OCR', value: ocrLimit.value },
])

const isGenerating = ref(false)
const isPolling = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const jobId = ref('')
const jobStatus = ref('')
const currentStep = ref('')
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
  const trimmedPrompt = prompt.value.trim()

  if (!trimmedPrompt) {
    errorMessage.value = 'Bạn cần nhập prompt để AI sinh quiz.'
    return
  }

  if (trimmedPrompt.length > MAX_PROMPT_LENGTH) {
    errorMessage.value = `Prompt quá dài (tối đa ${MAX_PROMPT_LENGTH} ký tự, hiện có ${trimmedPrompt.length}).`
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
      prompt: trimmedPrompt,
      count: settings.value.count,
      difficulty: settings.value.difficulty,
      language: settings.value.language,
      visibility: settings.value.visibility,
    })

    jobId.value = response.job_id
    jobStatus.value = response.status
    successMessage.value = 'Đã gửi yêu cầu sang AI. Hệ thống đang sinh quiz và lưu vào cơ sở dữ liệu.'
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
