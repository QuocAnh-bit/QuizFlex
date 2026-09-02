<template>
  <section class="grid gap-6 py-4 xl:grid-cols-[minmax(0,1fr)_340px]">
    <!-- Main Quiz Taking Area -->
    <article class="card p-6 sm:p-8 space-y-6">
      <div v-if="isLoading" class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-sm font-semibold text-slate-600">
        Đang chuẩn bị bài thi...
      </div>

      <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
        {{ errorMessage }}
      </div>

      <!-- Confirm Dialog for Submitting with Unanswered Questions -->
      <div v-if="showConfirmSubmit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
          <div class="flex items-center gap-2 text-amber-600">
            <span class="text-xl">⚠️</span>
            <h3 class="text-lg font-bold text-slate-900">Còn câu chưa chọn đáp án</h3>
          </div>
          <p class="text-sm leading-relaxed text-slate-600">
            Bạn còn <b class="text-slate-900">{{ unansweredCount }} câu</b> chưa chọn đáp án. Nếu nộp bài ngay, các câu chưa trả lời sẽ bị tính là sai.
          </p>
          <div class="flex justify-end gap-2.5 pt-2">
            <button class="btn-secondary text-xs" type="button" @click="showConfirmSubmit = false">Tiếp tục làm</button>
            <button class="btn-primary text-xs" type="button" :disabled="isSubmitting" @click="confirmSubmit">Xác nhận nộp bài</button>
          </div>
        </div>
      </div>

      <template v-if="currentQuestion.id">
        <!-- Question Header & Progress -->
        <div class="space-y-4 border-b border-slate-100 pb-5">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-[#7C3AED]">
                Câu {{ currentIndex + 1 }} / {{ quizQuestions.length || 1 }}
              </span>
              <span
                v-if="currentQuestion.type === 'multi_choice'"
                class="rounded-full border border-purple-200 bg-purple-100/70 px-3 py-1 text-xs font-bold text-purple-800 uppercase tracking-wide"
              >
                Nhiều đáp án đúng
              </span>
              <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ quizMeta.category || "Quiz" }}
              </span>
              <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ quizMeta.difficulty || "Vừa" }}
              </span>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-xs font-bold text-slate-500">
                Tiến độ: <b class="text-[#7C3AED]">{{ progressPercent }}%</b>
              </span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 border border-slate-200">
            <div
              class="h-full rounded-full bg-[#7C3AED] transition-all duration-300"
              :style="{ width: `${progressPercent}%` }"
            ></div>
          </div>

          <!-- Question Content -->
          <h1 class="text-xl font-bold leading-relaxed text-slate-900 sm:text-2xl pt-2">
            <MathText :text="currentQuestion.question" />
          </h1>

          <!-- Optional Question Image (Balanced, centered, elegant & zoomable) -->
          <div v-if="currentQuestion.image_url" class="py-2.5 flex justify-center">
            <QuestionImage
              :src="currentQuestion.image_url"
              size="normal"
              allow-zoom
            />
          </div>
        </div>

        <!-- Answers List -->
        <div class="grid gap-3 pt-2">
          <button
            v-for="answer in currentQuestion.answers"
            :key="answer.key"
            type="button"
            class="flex items-center gap-3.5 rounded-xl border p-4 text-left transition duration-150 active:scale-[0.99] cursor-pointer"
            :class="isAnswerSelected(answer.key)
              ? 'border-[#7C3AED] bg-purple-50 text-slate-900 shadow-sm'
              : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
            @click="selectAnswer(answer.key)"
          >
            <span
              class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-xs font-black transition"
              :class="isAnswerSelected(answer.key) ? 'bg-[#7C3AED] text-white' : 'bg-slate-100 text-slate-700'"
            >
              {{ answer.key }}
            </span>
            <span class="font-medium text-sm leading-relaxed flex-1">
              <MathText :text="answer.text" />
            </span>
            <span
              v-if="isAnswerSelected(answer.key)"
              class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-[#7C3AED] text-xs font-bold text-white"
            >
              ✓
            </span>
          </button>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-6 border-t border-slate-100">
          <button
            class="btn-secondary text-xs disabled:opacity-40"
            type="button"
            :disabled="currentIndex === 0"
            @click="goPrevious"
          >
            ← Câu trước
          </button>

          <div class="flex items-center gap-2.5">
            <button
              v-if="!isLastQuestion"
              class="btn-secondary text-xs px-4 border-slate-300 text-slate-700 hover:bg-slate-100"
              type="button"
              :disabled="isSubmitting"
              @click="triggerSubmit"
            >
              Nộp bài
            </button>

            <button
              class="btn-primary text-xs px-5"
              type="button"
              :disabled="isSubmitting"
              @click="goNext"
            >
              {{ isSubmitting ? "Đang nộp..." : isLastQuestion ? "Nộp bài" : "Câu tiếp theo →" }}
            </button>
          </div>
        </div>
      </template>
    </article>

    <!-- Sidebar: Timer & Question Map -->
    <aside class="grid content-start gap-5">
      <!-- Timer Card -->
      <article class="card p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Thời gian làm bài</span>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition cursor-pointer"
              :class="isMuted ? 'bg-slate-100 text-slate-500 hover:bg-slate-200' : 'bg-purple-50 text-[#7C3AED] hover:bg-purple-100'"
              :title="isMuted ? 'Bật âm thanh' : 'Tắt âm thanh'"
              @click="toggleSound"
            >
              <VolumeX v-if="isMuted" :size="14" />
              <Volume2 v-else :size="14" />
              <span>{{ isMuted ? "Tắt tiếng" : "Âm thanh" }}</span>
            </button>
            <span class="text-lg">⏱️</span>
          </div>
        </div>

        <div
          class="rounded-xl border p-4 text-center transition"
          :class="timeLeft <= 60 ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-slate-50 text-slate-900'"
        >
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Thời gian còn lại</p>
          <div class="mt-1 text-3xl font-black tracking-wider">
            {{ formatSeconds(timeLeft) }}
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs">
          <div class="rounded-lg border border-slate-100 bg-slate-50 p-2.5">
            <span class="text-slate-400 block text-[10px] font-bold uppercase">Quiz</span>
            <span class="font-bold text-slate-800 truncate block mt-0.5">{{ quizMeta.title || "Quiz" }}</span>
          </div>
          <div class="rounded-lg border border-slate-100 bg-slate-50 p-2.5">
            <span class="text-slate-400 block text-[10px] font-bold uppercase">Lần làm</span>
            <span class="font-bold text-slate-800 block mt-0.5">#{{ attemptId || "-" }}</span>
          </div>
        </div>
      </article>

      <!-- Question Map Card -->
      <article class="card p-5 space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Danh sách câu hỏi</h3>
          <span class="text-xs font-semibold text-slate-500">{{ answeredQuestionsCount }}/{{ quizQuestions.length }} đã chọn</span>
        </div>

        <div class="grid grid-cols-5 gap-1.5 pt-1">
          <button
            v-for="(_, index) in quizQuestions"
            :key="index"
            type="button"
            class="grid h-9 place-items-center rounded-lg border text-xs font-bold transition active:scale-95"
            :class="getQuestionMapClass(index)"
            @click="goToQuestion(index)"
          >
            {{ index + 1 }}
          </button>
        </div>
      </article>
    </aside>

  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, onUnmounted, ref, watch } from "vue";
import { onBeforeRouteLeave, useRoute, useRouter } from "vue-router";
import { Volume2, VolumeX } from "lucide-vue-next";
import { formatSeconds, normalizeQuestion, quizzesApi } from "@/services/api";
import { useAppLoading } from "@/composables/useAppLoading";

const { beginTask, endTask } = useAppLoading();
import MathText from "@/components/MathText.vue";
import QuestionImage from "@/components/question/QuestionImage.vue";
import audioService from "@/services/audioService";

const route = useRoute();
const router = useRouter();

const isMuted = ref(audioService.isMuted);

const toggleSound = () => {
  isMuted.value = audioService.toggleMute();
};

const currentIndex = ref(0);
const selectedAnswers = ref({});
const quizQuestions = ref([]);
const quizMeta = ref({
  title: "",
  category: "",
  difficulty: "",
  timeLimitSeconds: 600,
});
const attemptId = ref(null);
const showConfirmSubmit = ref(false);

const timeLeft = ref(0);
const isLoading = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref("");
let timer = null;

const attemptStorageKey = computed(
  () => `quizflex_practice_attempt_${route.params.id}`,
);

const answersStorageKey = computed(
  () => `quizflex_practice_answers_${route.params.id}_${attemptId.value || "temp"}`,
);

const indexStorageKey = computed(
  () => `quizflex_practice_index_${route.params.id}_${attemptId.value || "temp"}`,
);

watch(currentIndex, (newIndex) => {
  if (attemptId.value) {
    try {
      sessionStorage.setItem(indexStorageKey.value, String(newIndex));
    } catch (e) {
      console.error("Failed to save index to sessionStorage", e);
    }
  }
});

const currentQuestion = computed(
  () =>
    quizQuestions.value[currentIndex.value] || {
      id: 0,
      question: "",
      answers: [],
    },
);

const isAnswerSelected = (answerKey) => {
  const qId = currentQuestion.value.id;
  if (!qId) return false;
  const current = selectedAnswers.value[qId];
  if (Array.isArray(current)) {
    return current.includes(answerKey);
  }
  return current === answerKey;
};

const selectAnswer = (answerKey) => {
  const question = currentQuestion.value;
  if (!question?.id || !attemptId.value) return;

  const isMulti = question.type === "multi_choice";
  if (isMulti) {
    const current = Array.isArray(selectedAnswers.value[question.id])
      ? [...selectedAnswers.value[question.id]]
      : selectedAnswers.value[question.id]
        ? [selectedAnswers.value[question.id]]
        : [];
    const index = current.indexOf(answerKey);
    if (index > -1) {
      current.splice(index, 1);
    } else {
      current.push(answerKey);
    }
    selectedAnswers.value = {
      ...selectedAnswers.value,
      [question.id]: current,
    };
  } else {
    selectedAnswers.value = {
      ...selectedAnswers.value,
      [question.id]: answerKey,
    };
  }

  try {
    sessionStorage.setItem(
      answersStorageKey.value,
      JSON.stringify(selectedAnswers.value),
    );
  } catch (e) {
    console.error("Failed to save answers to sessionStorage", e);
  }
};

const progressPercent = computed(() =>
  Math.round(
    ((currentIndex.value + 1) / Math.max(quizQuestions.value.length, 1)) * 100,
  ),
);

const isLastQuestion = computed(
  () => currentIndex.value === quizQuestions.value.length - 1,
);

const answeredQuestionsCount = computed(() => {
  return Object.values(selectedAnswers.value).filter((v) =>
    Array.isArray(v) ? v.length > 0 : Boolean(v),
  ).length;
});

const getQuestionMapClass = (i) => {
  const questionId = quizQuestions.value[i]?.id;
  if (i === currentIndex.value) {
    return ["border-[#7C3AED]", "bg-[#7C3AED]", "text-white", "shadow-sm"];
  }
  const ans = selectedAnswers.value[questionId];
  const hasAnswered = Array.isArray(ans) ? ans.length > 0 : Boolean(ans);
  if (hasAnswered) {
    return ["border-emerald-200", "bg-emerald-50", "text-emerald-700"];
  }
  return ["border-slate-200", "bg-slate-50", "text-slate-600", "hover:bg-slate-100"];
};

const goPrevious = () => {
  if (currentIndex.value > 0) currentIndex.value -= 1;
};

const goToQuestion = (i) => {
  currentIndex.value = i;
};

const unansweredCount = computed(() => {
  return quizQuestions.value.filter((q) => {
    const ans = selectedAnswers.value[q.id];
    return Array.isArray(ans) ? ans.length === 0 : !ans;
  }).length;
});

const triggerSubmit = async () => {
  if (unansweredCount.value > 0) {
    showConfirmSubmit.value = true;
    return;
  }

  await submitAttempt();
};

const goNext = async () => {
  if (!isLastQuestion.value) {
    currentIndex.value += 1;
    return;
  }

  await triggerSubmit();
};

const confirmSubmit = async () => {
  showConfirmSubmit.value = false;
  await submitAttempt();
};

const startTimer = () => {
  clearInterval(timer);
  timer = setInterval(async () => {
    timeLeft.value -= 1;

    if (timeLeft.value === 3) {
      audioService.playCountdown();
    }

    if (timeLeft.value <= 0) {
      timeLeft.value = 0;
      clearInterval(timer);
      await submitAttempt(true);
    }
  }, 1000);
};

const submitAttempt = async (autoSubmit = false) => {
  if (isSubmitting.value) return;
  if (!autoSubmit && !quizQuestions.value.length) return;

  if (!attemptId.value) {
    errorMessage.value = "Không tìm thấy mã lượt làm bài. Vui lòng tải lại trang.";
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = "";
  clearInterval(timer);

  try {
    const result = await quizzesApi.submitAttempt(route.params.id, {
      attempt_id: attemptId.value,
      answers: selectedAnswers.value || {},
    });

    const id = result.attempt?.id || result.id || attemptId.value;
    if (id) {
      const currentAttempt = attemptId.value;
      sessionStorage.removeItem(attemptStorageKey.value);
      if (currentAttempt) {
        sessionStorage.removeItem(
          `quizflex_practice_answers_${route.params.id}_${currentAttempt}`,
        );
        sessionStorage.removeItem(
          `quizflex_practice_index_${route.params.id}_${currentAttempt}`,
        );
      }
      audioService.playFinish();
      setTimeout(() => {
        router.push(`/results/${id}`);
      }, 800);
      return;
    }

    errorMessage.value = autoSubmit
      ? "Đã hết giờ nhưng không nhận được mã kết quả."
      : "Nộp bài xong nhưng không nhận được mã kết quả.";
  } catch (error) {
    console.error("Submit attempt error:", error);
    errorMessage.value = `Không nộp được bài: ${error.message || "Lỗi kết nối máy chủ"}`;
    startTimer();
  } finally {
    isSubmitting.value = false;
  }
};

const loadQuiz = async () => {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    const savedAttemptId =
      Number(sessionStorage.getItem(attemptStorageKey.value) || 0) || undefined;
    const data = await quizzesApi.startAttempt(
      route.params.id,
      savedAttemptId ? { attempt_id: savedAttemptId } : {},
    );
    const quiz = data.quiz;
    attemptId.value = data.attempt?.id;
    if (attemptId.value) {
      sessionStorage.setItem(attemptStorageKey.value, String(attemptId.value));
      try {
        const savedAnswers = sessionStorage.getItem(answersStorageKey.value);
        if (savedAnswers) {
          selectedAnswers.value = JSON.parse(savedAnswers);
        }
      } catch (e) {
        console.error("Failed to restore answers from sessionStorage", e);
      }
    }

    quizMeta.value = {
      title: quiz.title,
      category: quiz.category,
      difficulty: quiz.difficulty,
      timeLimitSeconds: quiz.time_limit_seconds || 600,
    };
    quizQuestions.value = (quiz.questions || []).map((question) =>
      normalizeQuestion({
        ...question,
        difficulty: quiz.difficulty,
        category: quiz.category,
      }),
    );

    if (attemptId.value) {
      try {
        const savedIndex = sessionStorage.getItem(indexStorageKey.value);
        if (savedIndex !== null && savedIndex !== undefined) {
          const parsedIndex = Number(savedIndex);
          if (
            !isNaN(parsedIndex) &&
            parsedIndex >= 0 &&
            parsedIndex < quizQuestions.value.length
          ) {
            currentIndex.value = parsedIndex;
          }
        }
      } catch (e) {
        console.error("Failed to restore index from sessionStorage", e);
      }
    }

    const totalLimit = quiz.time_limit_seconds || 600;
    if (data.attempt?.started_at) {
      const startedAtMs = new Date(data.attempt.started_at).getTime();
      const nowMs = Date.now();
      const elapsedSeconds = Math.max(0, Math.floor((nowMs - startedAtMs) / 1000));
      timeLeft.value = Math.max(0, totalLimit - elapsedSeconds);
    } else {
      timeLeft.value = totalLimit;
    }

    if (timeLeft.value <= 0) {
      timeLeft.value = 0;
      await submitAttempt(true);
      return;
    }

    startTimer();
  } catch (error) {
    errorMessage.value = `Không tải được bài thi: ${error.message}`;
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  beginTask();
  try {
    audioService.stopAll();
    await loadQuiz();
  } finally {
    endTask();
  }
  audioService.playLobby();
});

onBeforeRouteLeave(() => {
  clearInterval(timer);
  audioService.stopAll();
});

onBeforeUnmount(() => {
  clearInterval(timer);
  audioService.stopAll();
});

onUnmounted(() => {
  clearInterval(timer);
  audioService.stopAll();
});
</script>
