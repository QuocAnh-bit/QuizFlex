<template>
  <section class="mx-auto max-w-4xl py-6 md:py-10">
    <!-- Header Navigation -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
      <button 
        type="button" 
        class="flex items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--muted)] transition hover:border-[var(--border-strong)] hover:text-[var(--text)] active:scale-95"
        @click="goBack"
      >
        <span>← Quãng đường quay lại</span>
      </button>
      <div class="text-right">
        <h2 class="text-xl font-black text-[var(--text)] line-clamp-1 max-w-md">{{ quizMeta.title }}</h2>
        <p class="text-xs font-bold text-[var(--muted)]">Ôn tập thẻ ghi nhớ</p>
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
              <span>🔍 Nhấp để lật</span>
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
              <span class="text-emerald-400">✓ Đã lật</span>
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
                  <span v-if="answer.isCorrect" class="text-sm font-black text-emerald-400 shrink-0">✓ Đúng</span>
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
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { normalizeQuestion, normalizeQuizCard, quizzesApi } from '@/services/api'

const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref('')
const isFlipped = ref(false)
const currentIndex = ref(0)
const isFinished = ref(false)

const quizMeta = ref({ title: '', category: '', difficulty: '' })
const questions = ref([])
const activeList = ref([])

// Spaces/leitner tracking: keep list of indexes
const masteredQuestions = ref([])
const needReviewQuestions = ref([])
const isReviewingWeakOnly = ref(false)

const totalCount = computed(() => questions.value.length)
const currentCard = computed(() => activeList.value[currentIndex.value] || { question: '', answers: [] })
// xác định thẻ hiện tại đang hiện thị dựa trên currentIndex
const progressPercent = computed(() => {
  if (activeList.value.length === 0) return 0
  return Math.round((currentIndex.value / activeList.value.length) * 100)
})

const toggleFlip = () => {
  isFlipped.value = !isFlipped.value
}

const goBack = () => {
  router.push(`/quizzes/${route.params.id}`)
}

const markAnswer = (isMastered) => {
  const originalQuestion = currentCard.value
  // sẽ lấy câu hỏi và đáp án từ currentCard
  
  if (isMastered) {
    // Thêm vào danh sách đã thành thạo nếu chưa có, loại bỏ khỏi danh sách còn yếu.
    if (!masteredQuestions.value.includes(originalQuestion.id)) {
      masteredQuestions.value.push(originalQuestion.id)
    }
    needReviewQuestions.value = needReviewQuestions.value.filter(id => id !== originalQuestion.id)
  } else {
    // Thêm vào danh sách cần xem lại nếu chưa có, và loại bỏ khỏi danh sách đã nắm vững.
    if (!needReviewQuestions.value.includes(originalQuestion.id)) {
      needReviewQuestions.value.push(originalQuestion.id)
    }
    masteredQuestions.value = masteredQuestions.value.filter(id => id !== originalQuestion.id)
  }

  // Go to next card or complete
  if (currentIndex.value < activeList.value.length - 1) {
    // Slide transition: reset flip first, wait brief delay for flip transition, then switch card
    isFlipped.value = false
    setTimeout(() => {
      currentIndex.value += 1
    }, 150)
  } else {
    isFlipped.value = false
    setTimeout(() => {
      isFinished.value = true
    }, 150)
  }
}

const restartAll = () => {
  activeList.value = [...questions.value]
  currentIndex.value = 0
  isFlipped.value = false
  isFinished.value = false
  masteredQuestions.value = []
  needReviewQuestions.value = []
  isReviewingWeakOnly.value = false
}

const restartOnlyWeak = () => {
  const weakIds = [...needReviewQuestions.value]
  activeList.value = questions.value.filter(q => weakIds.includes(q.id))
  currentIndex.value = 0
  isFlipped.value = false
  isFinished.value = false
  isReviewingWeakOnly.value = true
}

const loadQuizDetails = async () => {
  // hàm bất đồng bộ async, vì nó phải gọi API lấy dữ liệu quiz
  isLoading.value = true
  errorMessage.value = ''
  
  try {
    //frontend gọi api lấy chi tiết quiz theo id trên url
    const rawData = await quizzesApi.get(route.params.id)
    const normalizedQuiz = normalizeQuizCard(rawData)
    
    quizMeta.value = {
      title: normalizedQuiz.title,
      category: normalizedQuiz.category,
      difficulty: normalizedQuiz.difficulty,
    }
    //chuyển đổi các thuộc tính từ phong cách của backend sang định dạng sử dụng của frontend
    
    const rawQuestions = rawData.questions || []
    questions.value = rawQuestions.map(q => normalizeQuestion(q))
    activeList.value = [...questions.value]
    // lưu câu hỏi vào activelist
  } catch (error) {
    errorMessage.value = `Không tải được dữ liệu: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadQuizDetails)
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
