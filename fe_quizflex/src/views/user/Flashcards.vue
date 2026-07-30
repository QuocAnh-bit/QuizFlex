<template>
  <section class="mx-auto max-w-4xl py-6 md:py-10">
    <!-- Header Navigation & Quiz Info -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border)] pb-4">
      <div class="flex flex-wrap items-center gap-3">
        <button 
          type="button" 
          class="flex items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--muted)] transition hover:border-[var(--border-strong)] hover:text-[var(--text)] active:scale-95 shadow-sm"
          @click="goBack"
        >
          <span>← Quay lại</span>
        </button>

        <!-- Compact Audio Controls Pill -->
        <div class="flex items-center gap-1.5 rounded-full border border-[var(--border)] bg-[var(--surface)] p-1 shadow-sm">
          <button 
            type="button" 
            class="flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition active:scale-95"
            :class="isAutoPlayAudio ? 'bg-[var(--chip-active)] text-[var(--primary)] font-black' : 'text-[var(--muted)] hover:text-[var(--text)]'"
            @click="toggleAutoPlayAudio"
            :title="isAutoPlayAudio ? 'Tự động phát âm thanh: Đang BẬT' : 'Tự động phát âm thanh: Đang TẮT'"
          >
            <span>{{ isAutoPlayAudio ? '🔊 Tự phát âm' : '🔇 Tự phát âm' }}</span>
          </button>

          <!-- Multi-speed Audio Rate Toggle (1.0x -> 0.75x -> 0.55x) -->
          <button 
            type="button" 
            class="flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition active:scale-95"
            :class="audioRate < 1.0 ? 'bg-indigo-500/15 text-indigo-400 font-black' : 'text-[var(--muted)] hover:text-[var(--text)]'"
            @click="toggleAudioRate"
            :title="`Tốc độ phát âm: ${audioRate}x (Nhấn để đổi tốc độ)`"
          >
            <span v-if="audioRate === 0.55">🐢 Rất chậm (0.55x)</span>
            <span v-else-if="audioRate === 0.75">🔉 Đọc vừa (0.75x)</span>
            <span v-else>⚡ Tốc độ (1.0x)</span>
          </button>

          <button 
            type="button" 
            class="flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition active:scale-95"
            :class="isSoundEffects ? 'bg-amber-500/15 text-amber-400 font-black' : 'text-[var(--muted)] hover:text-[var(--text)]'"
            @click="toggleSoundEffects"
            :title="isSoundEffects ? 'Hiệu ứng âm thanh: Đang BẬT' : 'Hiệu ứng âm thanh: Đang TẮT'"
          >
            <span>{{ isSoundEffects ? '🔔 Hiệu ứng' : '🔕 Hiệu ứng' }}</span>
          </button>
        </div>
      </div>

      <div class="text-right flex flex-col items-end">
        <h2 class="text-xl font-black text-[var(--text)] line-clamp-1 max-w-md">{{ quizMeta.title }}</h2>
        <div class="flex items-center gap-2 mt-0.5">
          <span v-if="!isLoggedIn" class="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-[11px] font-black text-amber-400 border border-amber-500/30">
            🔒 Khách (Dùng thử 5 thẻ)
          </span>
          <span class="text-xs font-bold text-[var(--muted)]">Ôn tập thẻ ghi nhớ</span>
        </div>
      </div>
    </div>

    <!-- MAIN INTERACTIVE CONTAINER -->
    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 text-center text-sm font-bold text-[var(--muted)] shadow-[var(--shadow-soft)]">
      Đang chuẩn bị bộ thẻ học tập...
    </div>

    <div v-else-if="errorMessage" class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
      {{ errorMessage }}
    </div>

    <div v-else-if="questions.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 text-center text-sm font-bold text-[var(--muted)] shadow-[var(--shadow-soft)]">
      Quiz này không có câu hỏi nào để tạo thẻ ghi nhớ.
    </div>

    <div v-else-if="isFinished" class="relative overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)] p-8 text-center shadow-[var(--shadow-soft)] backdrop-blur-2xl md:p-12">
      <!-- Decorative Orbs -->
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="pointer-events-none absolute -left-24 -bottom-24 h-72 w-72 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

      <div class="relative z-10">
        <!-- Congratulations Banner -->
        <span class="inline-flex rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] px-6 py-2.5 text-sm font-black uppercase tracking-[0.2em] text-white shadow-lg animate-bounce">
          🎉 Hoàn thành ôn tập! 🎉
        </span>
        <h1 class="mt-6 text-4xl font-black tracking-[-0.07em] text-[var(--text)] sm:text-5xl">Tuyệt vời, bạn đã xuất sắc vượt qua!</h1>
        <p class="mt-4 text-sm leading-7 text-[var(--muted)] max-w-xl mx-auto">
          Bạn đã hoàn thành việc ôn luyện tất cả các câu hỏi trong bộ thẻ ghi nhớ của quiz này.
        </p>

        <!-- Stats Section -->
        <div class="mt-8 mx-auto grid max-w-md grid-cols-2 gap-4">
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-center">
            <span class="block text-xs font-black uppercase tracking-[0.1em] text-emerald-400">Đã thuộc lòng</span>
            <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ masteredQuestions.length }} / {{ totalCount }}</b>
          </div>
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-center">
            <span class="block text-xs font-black uppercase tracking-[0.1em] text-rose-400">Cần học lại</span>
            <b class="mt-2 block text-3xl font-black text-[var(--text)]">{{ needReviewQuestions.length }} / {{ totalCount }}</b>
          </div>
        </div>

        <!-- Action Choices -->
        <div class="mt-10 flex flex-wrap justify-center gap-4">
          <button 
            type="button" 
            class="btn-primary shadow-lg"
            @click="restartAll"
          >
            🔄 Ôn lại tất cả
          </button>
          
          <button 
            v-if="needReviewQuestions.length > 0"
            type="button" 
            class="rounded-full bg-gradient-to-br from-rose-500 to-rose-600 px-6 py-3 text-sm font-black text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-rose-500/20 active:scale-95"
            @click="restartOnlyWeak"
          >
            🔥 Chỉ ôn thẻ chưa thuộc ({{ needReviewQuestions.length }})
          </button>

          <button 
            type="button" 
            class="btn-ghost"
            @click="goBack"
          >
            Quay lại Quiz
          </button>
        </div>
      </div>
    </div>

    <!-- INTERACTIVE 3D FLASHCARD -->
    <div v-else class="grid gap-8">
      <!-- Info Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="rounded-full bg-[var(--chip-active)] px-3.5 py-1.5 text-xs font-black text-[var(--primary)] uppercase tracking-wider">
            Thẻ {{ currentIndex + 1 }} / {{ activeList.length }}
          </span>
          <span v-if="isReviewingWeakOnly" class="rounded-full bg-rose-500/10 px-3.5 py-1.5 text-xs font-black text-rose-400 uppercase tracking-wider">
            Chế độ ôn thẻ yếu
          </span>
        </div>
        <div class="text-xs font-black text-[var(--muted)]">
          Đã thuộc: <span class="text-emerald-400 font-bold">{{ masteredQuestions.length }}</span> • Cần ôn: <span class="text-rose-400 font-bold">{{ needReviewQuestions.length }}</span>
        </div>
      </div>

      <!-- Progressive Bar -->
      <div class="overflow-hidden rounded-full border border-[var(--border)] bg-[var(--surface-soft)] p-0.5">
        <div 
          class="h-2 rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] transition-all duration-500" 
          :style="{ width: `${progressPercent}%` }"
        ></div>
      </div>

      <!-- 3D Card Scene -->
      <div class="perspective-container mx-auto w-full max-w-xl h-[380px] md:h-[420px]" @click="toggleFlip">
        <div 
          class="flashcard-inner relative w-full h-full cursor-pointer transition-transform duration-500"
          :class="{ 'flashcard-flipped': isFlipped }"
        >
          <!-- CARD FRONT -->
          <div class="flashcard-front absolute inset-0 rounded-[2.5rem] border border-[var(--border-strong)] bg-[var(--surface)] p-6 md:p-8 flex flex-col justify-between shadow-[var(--shadow-soft)] hover:shadow-2xl hover:border-[var(--primary)]/30 transition-all duration-300">
            <!-- Background Orbs -->
            <div class="pointer-events-none absolute -right-20 -top-20 h-44 w-44 rounded-full bg-[var(--primary)]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-44 w-44 rounded-full bg-[var(--accent)]/5 blur-3xl"></div>

            <div class="relative z-10 flex items-center justify-between text-xs font-black text-[var(--muted)] uppercase tracking-widest">
              <span>Mặt trước: Câu hỏi</span>
              <div class="flex items-center gap-2">
                <button 
                  type="button"
                  class="flex items-center gap-1.5 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-bold text-[var(--text)] transition hover:scale-105 active:scale-95 shadow-sm"
                  :class="{ 'ring-2 ring-[var(--primary)] text-[var(--primary)] animate-pulse': activeSpeakingTarget === 'front' }"
                  @click.stop="speakFrontText"
                  title="Đọc phát âm câu hỏi"
                >
                  <span>{{ activeSpeakingTarget === 'front' ? '🔊 Đang đọc...' : '🔊 Đọc' }}</span>
                </button>
                <span>🔍 Nhấp để lật</span>
              </div>
            </div>

            <div class="relative z-10 flex-1 flex items-center justify-center py-6 text-center">
              <h3 class="text-xl md:text-2xl font-black leading-snug tracking-[-0.04em] text-[var(--text)] line-clamp-6">
                {{ currentCard.question }}
              </h3>
            </div>

            <div class="relative z-10 text-center text-xs font-black text-[var(--muted)]">
              💡 Nhấp vào thẻ để kiểm tra đáp án đúng của bạn
            </div>
          </div>

          <!-- CARD BACK -->
          <div class="flashcard-back absolute inset-0 rounded-[2.5rem] border border-[var(--border-strong)] bg-[var(--surface-strong)] p-6 md:p-8 flex flex-col justify-between shadow-[var(--shadow-soft)]">
            <!-- Background Orbs -->
            <div class="pointer-events-none absolute -right-20 -bottom-20 h-44 w-44 rounded-full bg-emerald-500/10 blur-3xl"></div>

            <div class="relative z-10 flex items-center justify-between text-xs font-black text-[var(--muted)] uppercase tracking-widest">
              <span>Mặt sau: Đáp án</span>
              <div class="flex items-center gap-2">
                <button 
                  type="button"
                  class="flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400 transition hover:scale-105 active:scale-95 shadow-sm"
                  :class="{ 'ring-2 ring-emerald-500 animate-pulse': activeSpeakingTarget === 'back' }"
                  @click.stop="speakBackText"
                  title="Đọc các đáp án đúng"
                >
                  <span>{{ activeSpeakingTarget === 'back' ? '🔊 Đang đọc...' : '🔊 Đọc đáp án đúng' }}</span>
                </button>
                <span class="text-emerald-400">✓ Đã lật</span>
              </div>
            </div>

            <div class="relative z-10 flex-1 flex flex-col justify-center py-4">
              <p class="text-xs font-black text-[var(--muted)] line-clamp-2 mb-4 text-center">
                {{ currentCard.question }}
              </p>

              <!-- Option options listing -->
              <div class="grid gap-2.5 max-h-[220px] overflow-y-auto pr-1 scrollbar-soft">
                <div 
                  v-for="answer in currentCard.answers" 
                  :key="answer.key"
                  class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-bold transition duration-300"
                  :class="answer.isCorrect 
                    ? 'border-emerald-500 bg-emerald-500/15 text-emerald-300 shadow-[0_4px_20px_rgba(16,185,129,0.15)] ring-1 ring-emerald-500/30' 
                    : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] opacity-55'"
                >
                  <span 
                    class="grid h-8 w-8 shrink-0 place-items-center rounded-xl text-xs font-black shadow-inner"
                    :class="answer.isCorrect 
                      ? 'bg-gradient-to-br from-emerald-400 to-teal-500 text-white' 
                      : 'bg-[var(--surface-soft)] text-[var(--muted)]'"
                  >
                    {{ answer.key }}
                  </span>
                  <span class="flex-1 leading-snug">{{ answer.text }}</span>

                  <!-- Speaker icon per option -->
                  <button 
                    type="button"
                    class="rounded-lg p-1.5 text-xs transition hover:bg-[var(--surface-soft)] active:scale-95 shrink-0"
                    :class="activeSpeakingTarget === `ans-${answer.key}` ? 'text-emerald-400 font-bold animate-pulse' : 'text-[var(--muted)] hover:text-[var(--text)]'"
                    @click.stop="speakOptionText(answer)"
                    title="Đọc phát âm phương án này"
                  >
                    🔊
                  </button>

                  <span v-if="answer.isCorrect" class="text-sm font-black text-emerald-400 shrink-0 flex items-center gap-1">
                    ✓ Đúng
                  </span>
                </div>
              </div>
            </div>

            <div class="relative z-10 text-center text-xs font-black text-[var(--muted)]">
              💡 Bấm nút đỏ hoặc xanh bên dưới để đánh dấu mức độ thuộc bài
            </div>
          </div>
        </div>
      </div>

      <!-- CONTROL BUTTONS -->
      <div class="flex items-center justify-center gap-4">
        <button 
          type="button"
          class="group flex flex-1 max-w-[200px] h-12 items-center justify-center gap-2 rounded-full border border-rose-500/30 bg-gradient-to-br from-rose-500/10 to-rose-600/10 px-5 text-sm font-black text-rose-400 transition hover:-translate-y-1 hover:border-rose-500 hover:bg-rose-500/20 active:scale-95 shadow-[var(--shadow-card)]"
          @click.stop="markAnswer(false)"
        >
          <span>❌ Chưa thuộc</span>
        </button>

        <button 
          type="button"
          class="group flex flex-1 max-w-[200px] h-12 items-center justify-center gap-2 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 px-5 text-sm font-black text-white shadow-[0_12px_28px_rgba(16,185,129,0.28)] transition hover:-translate-y-1 hover:shadow-[0_18px_38px_rgba(16,185,129,0.38)] active:scale-95"
          @click.stop="markAnswer(true)"
        >
          <span>✓ Đã thuộc</span>
        </button>
      </div>
    </div>

    <!-- GUEST LIMIT AUTH MODAL -->
    <div v-if="showGuestLimitModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-md p-4 animate-fade-in">
      <div class="relative w-full max-w-md rounded-[2.5rem] border border-[var(--border-strong)] bg-[var(--surface)] p-8 text-center shadow-2xl overflow-hidden">
        <!-- Background Glow -->
        <div class="pointer-events-none absolute -right-20 -top-20 h-48 w-48 rounded-full bg-amber-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-20 -bottom-20 h-48 w-48 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>

        <div class="relative z-10">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500/20 to-orange-500/20 text-3xl shadow-inner border border-amber-500/30 mb-4 animate-bounce">
            🔒
          </div>
          <h3 class="text-2xl font-black text-[var(--text)] tracking-tight">Đăng ký để học trọn bộ!</h3>
          <p class="mt-3 text-sm text-[var(--muted)] leading-relaxed">
            Bạn đang trải nghiệm chế độ <b class="text-amber-400">Khách dùng thử ({{ GUEST_PREVIEW_LIMIT }} thẻ)</b>. Để học tiếp toàn bộ <b>{{ totalCount }}</b> thẻ và lưu kết quả thuộc bài, hãy đăng nhập hoặc tạo tài khoản miễn phí nhé!
          </p>

          <div class="mt-6 flex flex-col gap-3">
            <button 
              type="button" 
              class="w-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] py-3.5 text-sm font-black text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl active:scale-95"
              @click="goToLogin"
            >
              🚀 Đăng nhập / Đăng ký ngay
            </button>

            <button 
              type="button" 
              class="w-full rounded-full border border-[var(--border)] bg-[var(--surface-soft)] py-3 text-xs font-bold text-[var(--muted)] transition hover:text-[var(--text)] hover:border-[var(--border-strong)] active:scale-95"
              @click="restartGuestPreview"
            >
              🔄 Ôn lại 5 thẻ dùng thử
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { normalizeQuestion, normalizeQuizCard, quizzesApi, tokenStorage } from '@/services/api'
import speechService from '@/services/speechService'

const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref('')
const isFlipped = ref(false)
const currentIndex = ref(0)
const isFinished = ref(false)

// Auth & Guest Limit State
const isLoggedIn = computed(() => !!tokenStorage.get())
const GUEST_PREVIEW_LIMIT = 5
const showGuestLimitModal = ref(false)

// Audio Controls State
const isAutoPlayAudio = ref(localStorage.getItem('flashcard_autoplay_audio') === 'true')
const isSoundEffects = ref(localStorage.getItem('flashcard_sfx_enabled') !== 'false')
const audioRate = ref(parseFloat(localStorage.getItem('flashcard_audio_rate') || '1.0'))
const activeSpeakingTarget = ref(null) // 'front' | 'back' | 'ans-A' | etc.

const quizMeta = ref({ title: '', category: '', difficulty: '' })
const questions = ref([])
const activeList = ref([])

// Spaces/leitner tracking: keep list of indexes
const masteredQuestions = ref([])
const needReviewQuestions = ref([])
const isReviewingWeakOnly = ref(false)

const totalCount = computed(() => questions.value.length)
const currentCard = computed(() => activeList.value[currentIndex.value] || { question: '', answers: [] })
const progressPercent = computed(() => {
  if (activeList.value.length === 0) return 0
  return Math.round((currentIndex.value / activeList.value.length) * 100)
})

const toggleAutoPlayAudio = () => {
  isAutoPlayAudio.value = !isAutoPlayAudio.value
  localStorage.setItem('flashcard_autoplay_audio', isAutoPlayAudio.value ? 'true' : 'false')
  if (!isAutoPlayAudio.value) {
    speechService.stop()
    activeSpeakingTarget.value = null
  }
}

const toggleAudioRate = () => {
  if (audioRate.value === 1.0) {
    audioRate.value = 0.75
  } else if (audioRate.value === 0.75) {
    audioRate.value = 0.55
  } else {
    audioRate.value = 1.0
  }
  localStorage.setItem('flashcard_audio_rate', audioRate.value.toString())
  speechService.stop()
  activeSpeakingTarget.value = null
}

const toggleSoundEffects = () => {
  isSoundEffects.value = !isSoundEffects.value
  localStorage.setItem('flashcard_sfx_enabled', isSoundEffects.value ? 'true' : 'false')
}

const speakFrontText = () => {
  if (!currentCard.value.question) return
  activeSpeakingTarget.value = 'front'
  speechService.speak(currentCard.value.question, {
    rate: audioRate.value,
    onEnd: () => {
      if (activeSpeakingTarget.value === 'front') {
        activeSpeakingTarget.value = null
      }
    }
  })
}

const speakBackText = () => {
  const correctAnswers = currentCard.value.answers
    .filter(a => a.isCorrect)
    .map(a => a.text)
    .join('. ')
  
  const textToSpeak = correctAnswers || currentCard.value.question
  activeSpeakingTarget.value = 'back'
  speechService.speak(textToSpeak, {
    rate: audioRate.value,
    onEnd: () => {
      if (activeSpeakingTarget.value === 'back') {
        activeSpeakingTarget.value = null
      }
    }
  })
}

const speakOptionText = (answer) => {
  if (!answer || !answer.text) return
  activeSpeakingTarget.value = `ans-${answer.key}`
  speechService.speak(answer.text, {
    rate: audioRate.value,
    onEnd: () => {
      if (activeSpeakingTarget.value === `ans-${answer.key}`) {
        activeSpeakingTarget.value = null
      }
    }
  })
}

const toggleFlip = () => {
  speechService.stop()
  activeSpeakingTarget.value = null

  if (isSoundEffects.value) {
    speechService.playFlipSound()
  }

  isFlipped.value = !isFlipped.value

  if (isAutoPlayAudio.value) {
    setTimeout(() => {
      if (isFlipped.value) {
        speakBackText()
      } else {
        speakFrontText()
      }
    }, 300)
  }
}

const goBack = () => {
  speechService.stop()
  router.push(`/quizzes/${route.params.id}`)
}

const goToLogin = () => {
  speechService.stop()
  router.push({ path: '/login', query: { redirect: route.fullPath } })
}

const restartGuestPreview = () => {
  speechService.stop()
  showGuestLimitModal.value = false
  currentIndex.value = 0
  isFlipped.value = false
}

const markAnswer = (isMastered) => {
  speechService.stop()
  activeSpeakingTarget.value = null

  // Restrict Guest after 5 preview cards
  if (!isLoggedIn.value && currentIndex.value >= GUEST_PREVIEW_LIMIT - 1) {
    showGuestLimitModal.value = true
    return
  }

  if (isSoundEffects.value) {
    if (isMastered) {
      speechService.playSuccessSound()
    } else {
      speechService.playReviewSound()
    }
  }

  const originalQuestion = currentCard.value
  
  if (isMastered) {
    if (!masteredQuestions.value.includes(originalQuestion.id)) {
      masteredQuestions.value.push(originalQuestion.id)
    }
    needReviewQuestions.value = needReviewQuestions.value.filter(id => id !== originalQuestion.id)
  } else {
    if (!needReviewQuestions.value.includes(originalQuestion.id)) {
      needReviewQuestions.value.push(originalQuestion.id)
    }
    masteredQuestions.value = masteredQuestions.value.filter(id => id !== originalQuestion.id)
  }

  // Go to next card or complete
  if (currentIndex.value < activeList.value.length - 1) {
    isFlipped.value = false
    setTimeout(() => {
      currentIndex.value += 1
      if (isAutoPlayAudio.value) {
        speakFrontText()
      }
    }, 200)
  } else {
    isFlipped.value = false
    setTimeout(() => {
      isFinished.value = true
    }, 200)
  }
}

const restartAll = () => {
  speechService.stop()
  activeList.value = [...questions.value]
  currentIndex.value = 0
  isFlipped.value = false
  isFinished.value = false
  masteredQuestions.value = []
  needReviewQuestions.value = []
  isReviewingWeakOnly.value = false

  if (isAutoPlayAudio.value) {
    setTimeout(speakFrontText, 300)
  }
}

const restartOnlyWeak = () => {
  speechService.stop()
  const weakIds = [...needReviewQuestions.value]
  activeList.value = questions.value.filter(q => weakIds.includes(q.id))
  currentIndex.value = 0
  isFlipped.value = false
  isFinished.value = false
  isReviewingWeakOnly.value = true

  if (isAutoPlayAudio.value) {
    setTimeout(speakFrontText, 300)
  }
}

const loadQuizDetails = async () => {
  isLoading.value = true
  errorMessage.value = ''
  
  try {
    const rawData = await quizzesApi.get(route.params.id)
    const normalizedQuiz = normalizeQuizCard(rawData)
    
    quizMeta.value = {
      title: normalizedQuiz.title,
      category: normalizedQuiz.category,
      difficulty: normalizedQuiz.difficulty,
    }
    
    const rawQuestions = rawData.questions || []
    questions.value = rawQuestions.map(q => normalizeQuestion(q))
    activeList.value = [...questions.value]

    if (isAutoPlayAudio.value && activeList.value.length > 0) {
      setTimeout(speakFrontText, 500)
    }
  } catch (error) {
    errorMessage.value = `Không tải được dữ liệu: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadQuizDetails)

onUnmounted(() => {
  speechService.stop()
})
</script>

<style scoped>
/* 3D SCENE PERSPECTIVE SETUP */
.perspective-container {
  perspective: 1000px;
}

.flashcard-inner {
  transform-style: preserve-3d;
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.flashcard-flipped {
  transform: rotateY(180deg);
}

.flashcard-front, .flashcard-back {
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
}

.flashcard-back {
  transform: rotateY(180deg);
}
</style>
