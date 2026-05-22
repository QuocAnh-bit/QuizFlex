<template>
  <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-28 -top-28 h-80 w-80 rounded-full bg-[var(--accent-2)]/20 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">AI Generator</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tạo quiz bằng AI</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Nhập yêu cầu, AI sẽ sinh câu hỏi, lưu thành quiz trong backend, rồi bạn có thể làm bài ngay hoặc chuyển sang editor để sửa.</p>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <div v-for="stat in quotaStats" :key="stat.label" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">{{ stat.label }}</p>
            <b class="mt-1 block text-2xl font-black text-[var(--text)]">{{ stat.value }}</b>
          </div>
        </div>

        <div class="mt-8 grid gap-4">
          <textarea v-model="prompt" class="field min-h-44" placeholder="Ví dụ: Tạo 10 câu hỏi toán lớp 10 về hàm số bậc nhất, mức trung bình, 4 đáp án mỗi câu."></textarea>

          <div class="grid gap-4 md:grid-cols-4">
            <select v-model.number="settings.count" class="field">
              <option :value="10">10 câu</option>
              <option :value="15">15 câu</option>
              <option :value="20">20 câu</option>
            </select>

            <select v-model="settings.difficulty" class="field">
              <option value="easy">Dễ</option>
              <option value="medium">Vừa</option>
              <option value="hard">Khó</option>
            </select>

            <select v-model="settings.language" class="field">
              <option value="vi">Tiếng Việt</option>
              <option value="en">English</option>
            </select>

            <select v-model="settings.visibility" class="field">
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
        </div>

        <div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
          {{ errorMessage }}
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
const quotaStats = computed(() => [
  { label: 'Role', value: user.value?.role_label || user.value?.role || 'Guest' },
  { label: 'AI còn lại', value: user.value?.ai_quota_remaining ?? 0 },
  { label: 'OCR còn lại', value: user.value?.role === 'vip' ? 120 : 3 },
])

const isGenerating = ref(false)
const isPolling = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const jobId = ref('')
const jobStatus = ref('')
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

    if (job.status === 'completed' && job.quiz_id) {
      generatedQuiz.value = job.quiz_full || job.quiz || null
      await authApi.me()
      isPolling.value = false
      stopPolling()
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
      return
    }

    pollTimer = setTimeout(pollJob, 2500)
  } catch (error) {
    errorMessage.value = `Không lấy được trạng thái AI job: ${error.message}`
    isPolling.value = false
    stopPolling()
  }
}

const generateQuiz = async () => {
  if (!prompt.value.trim()) {
    errorMessage.value = 'Bạn cần nhập prompt để AI sinh quiz.'
    return
  }

  isGenerating.value = true
  errorMessage.value = ''
  successMessage.value = ''
  generatedQuiz.value = null
  jobId.value = ''
  jobStatus.value = ''
  stopPolling()

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
    if (msg.includes('AI quota exhausted')) {
      errorMessage.value = 'Bạn đã hết token'
    } else {
      errorMessage.value = `Không tạo được AI quiz: ${msg}`
    }
  } finally {
    isGenerating.value = false
  }
}

const openInEditor = () => {
  if (!generatedQuiz.value?.id) return
  router.push(`${questionBase.value}/edit/${generatedQuiz.value.id}`)
}

onBeforeUnmount(() => {
  stopPolling()
})
</script>
