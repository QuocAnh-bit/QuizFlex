<template>
  <section class="mx-auto max-w-4xl py-4 space-y-6">
    <!-- 1. LOADING STATE -->
    <AppLoadingState
      v-if="isLoading"
      title="Đang chuẩn bị bộ thẻ học tập..."
      message="Vui lòng chờ trong giây lát để hệ thống xử lý dữ liệu quiz."
    />

    <!-- 2. ERROR STATE -->
    <AppErrorState
      v-else-if="errorMessage"
      title="Không thể tải bộ thẻ học tập"
      :message="errorMessage"
      @retry="loadQuizDetails"
    >
      <template #actions>
        <button
          type="button"
          class="btn-ghost text-xs inline-flex items-center gap-1.5"
          @click="goBack"
        >
          <ArrowLeft class="h-3.5 w-3.5" />
          Quay lại
        </button>
      </template>
    </AppErrorState>

    <!-- 3. LOADED STATE -->
    <template v-else>
      <!-- Header Navigation & Quiz Info -->
      <div class="card p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2.5">
          <button
            type="button"
            class="btn-secondary text-xs px-3 py-1.5 inline-flex items-center gap-1.5"
            @click="goBack"
          >
            <ArrowLeft class="h-3.5 w-3.5" />
            Quay lại
          </button>

          <!-- Audio Controls -->
          <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1 text-xs">
            <button
              type="button"
              class="rounded-md px-2.5 py-1 font-semibold transition active:scale-95 inline-flex items-center gap-1.5"
              :class="
                isAutoPlayAudio
                  ? 'bg-purple-100 text-[#7C3AED] font-bold'
                  : 'text-slate-600 hover:text-slate-900'
              "
              @click="toggleAutoPlayAudio"
            >
              <Volume2 v-if="isAutoPlayAudio" class="h-3.5 w-3.5" />
              <VolumeX v-else class="h-3.5 w-3.5" />
              Tự phát âm
            </button>

            <button
              type="button"
              class="rounded-md px-2.5 py-1 font-semibold transition active:scale-95 inline-flex items-center gap-1.5"
              :class="
                audioRate < 1.0
                  ? 'bg-indigo-100 text-indigo-700 font-bold'
                  : 'text-slate-600 hover:text-slate-900'
              "
              @click="toggleAudioRate"
            >
              <Turtle v-if="audioRate === 0.45" class="h-3.5 w-3.5" />
              <Gauge v-else class="h-3.5 w-3.5" />
              {{ audioRate }}x
            </button>

            <button
              type="button"
              class="rounded-md px-2.5 py-1 font-semibold transition active:scale-95 inline-flex items-center gap-1.5"
              :class="
                isSoundEffects
                  ? 'bg-amber-100 text-amber-800 font-bold'
                  : 'text-slate-600 hover:text-slate-900'
              "
              @click="toggleSoundEffects"
            >
              <Bell v-if="isSoundEffects" class="h-3.5 w-3.5" />
              <BellOff v-else class="h-3.5 w-3.5" />
              Hiệu ứng
            </button>
          </div>
        </div>

        <div class="text-right">
          <h2 class="text-base font-bold text-slate-900 truncate max-w-xs">
            {{ quizMeta.title }}
          </h2>
          <span class="text-xs text-slate-500 font-medium">
            Ôn tập Flashcard
          </span>
        </div>
      </div>

      <!-- EMPTY -->
      <div
        v-if="questions.length === 0"
        class="card p-10 text-center text-sm font-semibold text-slate-500"
      >
        Quiz này không có câu hỏi nào để tạo thẻ ghi nhớ.
      </div>

      <!-- FINISHED -->
      <div
        v-else-if="isFinished"
        class="card p-8 sm:p-12 text-center space-y-6"
      >
        <span
          class="inline-flex items-center gap-2 rounded-full bg-purple-50 border border-purple-200 px-4 py-1 text-xs font-bold uppercase tracking-wider text-[#7C3AED]"
        >
          <PartyPopper class="h-4 w-4" />
          Hoàn thành bài ôn tập!
        </span>

        <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
          Tuyệt vời, bạn đã hoàn thành bộ flashcard!
        </h1>

        <p class="text-sm text-slate-600 max-w-md mx-auto">
          Bạn đã duyệt qua toàn bộ các câu hỏi trong bộ thẻ ghi nhớ của quiz này.
        </p>

        <!-- Stats -->
        <div class="mx-auto grid max-w-sm grid-cols-2 gap-3">
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
            <span class="text-xs font-bold uppercase text-emerald-800">
              Đã thuộc lòng
            </span>
            <b class="mt-1 block text-2xl font-black text-emerald-700">
              {{ masteredQuestions.length }} / {{ totalCount }}
            </b>
          </div>

          <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-center">
            <span class="text-xs font-bold uppercase text-red-800">
              Cần ôn lại
            </span>
            <b class="mt-1 block text-2xl font-black text-red-700">
              {{ needReviewQuestions.length }} / {{ totalCount }}
            </b>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap justify-center gap-3 pt-4">
          <button
            type="button"
            class="btn-primary text-xs inline-flex items-center gap-1.5"
            @click="restartAll"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            Ôn lại tất cả
          </button>

          <button
            v-if="needReviewQuestions.length > 0"
            type="button"
            class="btn-secondary text-xs text-red-600 font-bold hover:bg-red-50 inline-flex items-center gap-1.5"
            @click="restartOnlyWeak"
          >
            <Flame class="h-3.5 w-3.5" />
            Chỉ ôn thẻ chưa thuộc
            ({{ needReviewQuestions.length }})
          </button>

          <button
            type="button"
            class="btn-ghost text-xs inline-flex items-center gap-1.5"
            @click="goBack"
          >
            <ArrowLeft class="h-3.5 w-3.5" />
            Quay lại Quiz
          </button>
        </div>
      </div>

      <!-- FLASHCARD -->
      <div v-else class="space-y-6">
        <!-- Progress Header -->
        <div class="flex items-center justify-between text-xs font-semibold text-slate-600">
          <span
            class="rounded-full bg-purple-50 px-3 py-1 font-bold text-[#7C3AED] inline-flex items-center gap-1.5"
          >
            <ListChecks class="h-3.5 w-3.5" />
            Thẻ {{ currentIndex + 1 }} / {{ activeList.length }}
          </span>

          <div>
            Đã thuộc:
            <b class="text-emerald-700">
              {{ masteredQuestions.length }}
            </b>
            · Cần ôn:
            <b class="text-red-700">
              {{ needReviewQuestions.length }}
            </b>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 border border-slate-200">
          <div
            class="h-full rounded-full bg-[#7C3AED] transition-all duration-300"
            :style="{ width: `${progressPercent}%` }"
          ></div>
        </div>

        <!-- 3D CARD -->
        <div
          class="perspective-container mx-auto w-full max-w-xl h-[340px] sm:h-[380px]"
          @click="toggleFlip"
        >
          <div
            class="flashcard-inner relative w-full h-full cursor-pointer transition-transform duration-500"
            :class="{ 'flashcard-flipped': isFlipped }"
          >
            <!-- FRONT -->
            <div
              class="flashcard-front absolute inset-0 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 flex flex-col justify-between shadow-md hover:border-purple-300 transition-all"
            >
              <div
                class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider"
              >
                <span>Câu hỏi</span>

                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100 inline-flex items-center gap-1.5"
                    @click.stop="speakFrontText"
                  >
                    <Volume2 class="h-3.5 w-3.5" />
                    Đọc
                  </button>

                  <span class="text-slate-400 inline-flex items-center gap-1">
                    <Lightbulb class="h-3.5 w-3.5" />
                    Nhấp để lật
                  </span>
                </div>
              </div>

              <div class="flex-1 flex items-center justify-center py-4 text-center">
                <h3 class="text-lg sm:text-xl font-bold leading-relaxed text-slate-900 line-clamp-6">
                  {{ currentCard.question }}
                </h3>
              </div>

              <div class="text-center text-xs text-slate-400 font-medium">
                Nhấp vào thẻ để xem đáp án đúng
              </div>
            </div>

            <!-- BACK -->
            <div
              class="flashcard-back absolute inset-0 rounded-2xl border border-purple-200 bg-purple-50/40 p-6 sm:p-8 flex flex-col justify-between shadow-md"
            >
              <div
                class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider"
              >
                <span>Đáp án</span>

                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    class="rounded-md border border-purple-200 bg-purple-100 px-2.5 py-1 text-xs font-bold text-[#7C3AED] inline-flex items-center gap-1.5"
                    @click.stop="speakBackText"
                  >
                    <Volume2 class="h-3.5 w-3.5" />
                    Đọc đáp án
                  </button>

                  <span class="text-emerald-700 font-bold inline-flex items-center gap-1">
                    <CheckCircle2 class="h-3.5 w-3.5" />
                    Đã lật
                  </span>
                </div>
              </div>

              <div class="flex-1 flex flex-col justify-center py-2 space-y-3">
                <p class="text-xs font-semibold text-slate-500 line-clamp-2 text-center">
                  {{ currentCard.question }}
                </p>

                <div class="grid gap-2 max-h-[180px] overflow-y-auto pr-1 scrollbar-soft">
                  <div
                    v-for="answer in currentCard.answers"
                    :key="answer.key"
                    class="flex items-center gap-2.5 rounded-lg border px-3 py-2 text-xs font-semibold"
                    :class="
                      answer.isCorrect
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                        : 'border-slate-200 bg-white text-slate-500 opacity-60'
                    "
                  >
                    <span
                      class="grid h-6 w-6 shrink-0 place-items-center rounded text-xs font-bold"
                      :class="
                        answer.isCorrect
                          ? 'bg-emerald-600 text-white'
                          : 'bg-slate-100 text-slate-600'
                      "
                    >
                      {{ answer.key }}
                    </span>

                    <span class="flex-1 leading-tight">
                      {{ answer.text }}
                    </span>

                    <span
                      v-if="answer.isCorrect"
                      class="text-emerald-700 font-bold shrink-0 inline-flex items-center gap-1"
                    >
                      <CheckCircle2 class="h-3.5 w-3.5" />
                      Đúng
                    </span>
                  </div>
                </div>
              </div>

              <div class="text-center text-xs text-slate-400 font-medium">
                Đánh dấu mức độ ghi nhớ của bạn ở nút bên dưới
              </div>
            </div>
          </div>
        </div>

        <!-- Controls -->
        <div class="flex items-center justify-center gap-3 pt-2">
          <button
            type="button"
            class="btn-danger text-xs px-6 py-2.5 inline-flex items-center gap-1.5"
            @click.stop="markAnswer(false)"
          >
            <XCircle class="h-4 w-4" />
            Chưa thuộc
          </button>

          <button
            type="button"
            class="btn-success text-xs px-6 py-2.5 inline-flex items-center gap-1.5"
            @click.stop="markAnswer(true)"
          >
            <CheckCircle2 class="h-4 w-4" />
            Đã thuộc
          </button>
        </div>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  ArrowLeft,
  Bell,
  BellOff,
  CheckCircle2,
  Flame,
  Gauge,
  Lightbulb,
  ListChecks,
  PartyPopper,
  RotateCcw,
  Turtle,
  Volume2,
  VolumeX,
  XCircle,
} from 'lucide-vue-next'

import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import {
  normalizeQuestion,
  normalizeQuizCard,
  quizzesApi,
  tokenStorage,
} from '@/services/api'
import speechService from '@/services/speechService'

const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref('')
const isFlipped = ref(false)
const currentIndex = ref(0)
const isFinished = ref(false)

const isLoggedIn = computed(() => !!tokenStorage.get())
const isAutoPlayAudio = ref(
  localStorage.getItem('flashcard_autoplay_audio') === 'true'
)
const isSoundEffects = ref(
  localStorage.getItem('flashcard_sfx_enabled') !== 'false'
)
const audioRate = ref(
  parseFloat(localStorage.getItem('flashcard_audio_rate') || '1.0')
)
const activeSpeakingTarget = ref(null)

const quizMeta = ref({
  title: '',
  category: '',
  difficulty: '',
})

const questions = ref([])
const activeList = ref([])

const masteredQuestions = ref([])
const needReviewQuestions = ref([])
const isReviewingWeakOnly = ref(false)

const totalCount = computed(() => questions.value.length)

const currentCard = computed(
  () =>
    activeList.value[currentIndex.value] || {
      question: '',
      answers: [],
    }
)

const progressPercent = computed(() => {
  if (activeList.value.length === 0) return 0

  return Math.round(
    (currentIndex.value / activeList.value.length) * 100
  )
})

const toggleAutoPlayAudio = () => {
  isAutoPlayAudio.value = !isAutoPlayAudio.value

  localStorage.setItem(
    'flashcard_autoplay_audio',
    isAutoPlayAudio.value ? 'true' : 'false'
  )

  if (!isAutoPlayAudio.value) {
    speechService.stop()
    activeSpeakingTarget.value = null
  }
}

const toggleAudioRate = () => {
  if (audioRate.value === 1.0) {
    audioRate.value = 0.7
  } else if (audioRate.value === 0.7) {
    audioRate.value = 0.45
  } else {
    audioRate.value = 1.0
  }

  localStorage.setItem(
    'flashcard_audio_rate',
    audioRate.value.toString()
  )

  speechService.stop()
  activeSpeakingTarget.value = null
}

const toggleSoundEffects = () => {
  isSoundEffects.value = !isSoundEffects.value

  localStorage.setItem(
    'flashcard_sfx_enabled',
    isSoundEffects.value ? 'true' : 'false'
  )
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
    },
  })
}

const speakBackText = () => {
  const correctAnswers = currentCard.value.answers
    .filter((a) => a.isCorrect)
    .map((a) => a.text)
    .join('. ')

  const textToSpeak = correctAnswers || currentCard.value.question

  activeSpeakingTarget.value = 'back'

  speechService.speak(textToSpeak, {
    rate: audioRate.value,
    onEnd: () => {
      if (activeSpeakingTarget.value === 'back') {
        activeSpeakingTarget.value = null
      }
    },
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

const markAnswer = (isMastered) => {
  speechService.stop()
  activeSpeakingTarget.value = null

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

    needReviewQuestions.value =
      needReviewQuestions.value.filter(
        (id) => id !== originalQuestion.id
      )
  } else {
    if (!needReviewQuestions.value.includes(originalQuestion.id)) {
      needReviewQuestions.value.push(originalQuestion.id)
    }

    masteredQuestions.value =
      masteredQuestions.value.filter(
        (id) => id !== originalQuestion.id
      )
  }

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

  activeList.value = questions.value.filter((q) =>
    weakIds.includes(q.id)
  )

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

    questions.value = rawQuestions.map((q) => normalizeQuestion(q))
    activeList.value = [...questions.value]

    if (
      isAutoPlayAudio.value &&
      activeList.value.length > 0
    ) {
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
.perspective-container {
  perspective: 1000px;
}

.flashcard-inner {
  transform-style: preserve-3d;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.flashcard-flipped {
  transform: rotateY(180deg);
}

.flashcard-front,
.flashcard-back {
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
}

.flashcard-back {
  transform: rotateY(180deg);
}
</style>
