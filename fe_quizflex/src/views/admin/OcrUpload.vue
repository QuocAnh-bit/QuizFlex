<template>
  <section
    v-if="currentView === 'upload'"
    class="grid gap-6 xl:grid-cols-[1fr_420px]"
  >
    <article
      class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl"
    >
      <div class="relative z-10">
        <p
          class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
        >
          {{ $t('admin_views.OcrUpload.badge') }}
        </p>

        <h1
          class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]"
        >
          {{ $t('admin_views.OcrUpload.title') }}
        </h1>

        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
          {{ $t('admin_views.OcrUpload.description') }}
        </p>

        <label
          :class="[
            'mt-8 grid min-h-[260px] place-items-center rounded-[2rem] border-2 border-dashed border-[var(--border-strong)] bg-[var(--chip-active)] p-8 text-center transition duration-300',
            isUploading
              ? 'cursor-not-allowed opacity-60'
              : 'cursor-pointer hover:-translate-y-1',
          ]"
        >
          <input
            ref="fileInput"
            class="hidden"
            type="file"
            accept="image/*,.pdf,application/pdf"
            :disabled="isUploading"
            @change="handleFile"
          />

          <div>
            <div
              class="mx-auto mb-5 grid h-20 w-20 place-items-center rounded-[1.5rem] bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-3xl text-white"
            >
              OCR
            </div>

            <h2 class="text-2xl font-black text-[var(--text)]">
              {{
                isUploading
                  ? $t('admin_views.OcrUpload.processing_file')
                  : $t('admin_views.OcrUpload.dropzone_title')
              }}
            </h2>

            <p class="mt-2 text-sm font-semibold text-[var(--muted)]">
              {{ $t('admin_views.OcrUpload.file_hint') }}
            </p>
          </div>
        </label>

        <div
          v-if="fileName"
          class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <b class="text-[var(--text)]">{{ fileName }}</b>
              <p class="mt-1 text-sm text-[var(--muted)]">
                {{ isUploading ? loadingText : $t('admin_views.OcrUpload.ocr_ready') }}
              </p>
            </div>

            <span
              class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-black text-emerald-400"
            >
              {{ Math.floor(progress) }}%
            </span>
          </div>

          <div
            class="mt-4 h-3 overflow-hidden rounded-full bg-[var(--surface)]"
          >
            <div
              class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] transition-all duration-700"
              :class="{ 'animate-pulse': isUploading }"
              :style="{ width: `${progress}%` }"
            ></div>
          </div>
        </div>

        <div
          v-if="totalQuestions > 0"
          class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300"
        >
          {{ $t('admin_views.OcrUpload.rendered_questions', { count: totalQuestions }) }}
          <span v-if="showReadyMessage">{{ $t('admin_views.OcrUpload.redirecting_editor') }}</span>
        </div>

        <div
          v-if="errorMessage"
          class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300"
        >
          {{ errorMessage }}
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            class="btn-ghost disabled:cursor-not-allowed disabled:opacity-50"
            type="button"
            :disabled="isUploading"
            @click="resetFile"
          >
            {{ $t('admin_views.OcrUpload.remove_file_button') }}
          </button>

          <button
            class="btn-primary disabled:cursor-not-allowed disabled:opacity-50"
            type="button"
            :disabled="isUploading || !totalQuestions"
            @click="currentView = 'edit'"
          >
            {{ isUploading ? $t('admin_views.OcrUpload.processing_button') : $t('admin_views.OcrUpload.go_editor_button') }}
          </button>
        </div>
      </div>
    </article>

    <aside class="grid content-start gap-5">
      <article
        class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"
      >
        <p
          class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
        >
          {{ $t('admin_views.OcrUpload.preview_badge') }}
        </p>

        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">
          {{ $t('admin_views.OcrUpload.preview_title') }}
        </h2>

        <div
          class="mt-5 max-h-[420px] overflow-auto rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm leading-7 text-[var(--muted)] scrollbar-soft"
        >
          <p v-for="line in ocrLines" :key="line" class="mb-3">
            {{ line }}
          </p>
        </div>
      </article>

      <article
        class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"
      >
        <p
          class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
        >
          {{ $t('admin_views.OcrUpload.suggestion_badge') }}
        </p>

        <div class="mt-4 grid gap-3">
          <div
            v-for="item in suggestions"
            :key="item"
            class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold text-[var(--muted)]"
          >
            {{ item }}
          </div>
        </div>
      </article>
    </aside>
  </section>

  <section v-else class="grid gap-6">
    <article
      class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl"
    >
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p
            class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
          >
            {{ $t('admin_views.OcrUpload.editor_badge') }}
          </p>

          <h1
            class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]"
          >
            {{ $t('admin_views.OcrUpload.editor_title') }}
          </h1>

          <p class="mt-3 text-sm text-[var(--muted)]">
            {{ $t('admin_views.OcrUpload.editor_description') }}
          </p>

          <div
            class="mt-4 inline-flex rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-sm font-black text-emerald-300"
          >
            {{ $t('admin_views.OcrUpload.total_questions', { count: totalQuestions }) }}
          </div>
        </div>

        <button class="btn-ghost" type="button" @click="currentView = 'upload'">
          {{ $t('admin_views.OcrUpload.back_to_ocr_button') }}
        </button>
      </div>

      <div class="mt-8 grid gap-5">
        <div
          v-for="(q, index) in questions"
          :key="index"
          class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"
        >
          <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-black text-[var(--primary)]">
              {{ $t('admin_views.OcrUpload.question_number', { index: index + 1 }) }}
            </p>

            <button
              type="button"
              class="rounded-full bg-rose-500/10 px-3 py-1 text-xs font-black text-rose-300"
              @click="removeQuestion(index)"
            >
              {{ $t('admin_views.OcrUpload.delete_button') }}
            </button>
          </div>

          <textarea
            v-model="q.question"
            rows="3"
            class="mt-3 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold leading-7 text-[var(--text)] outline-none"
          ></textarea>

          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div v-for="(optionText, optionKey) in q.options" :key="optionKey">
              <label class="text-xs font-black uppercase text-[var(--muted)]">
                {{ $t('admin_views.OcrUpload.answer_label', { key: optionKey }) }}
              </label>

              <input
                v-model="q.options[optionKey]"
                class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold text-[var(--text)] outline-none"
              />
            </div>
          </div>

          <div class="mt-4">
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              {{ $t('admin_views.OcrUpload.correct_answer_label') }}
            </label>

            <select
              v-model="q.correct_answer"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
            >
              <option :value="null">{{ $t('admin_views.OcrUpload.not_selected') }}</option>

              <option
                v-for="(_, optionKey) in q.options"
                :key="optionKey"
                :value="optionKey"
              >
                {{ optionKey }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <div class="mt-8 flex flex-wrap gap-3">
        <button class="btn-ghost" type="button" @click="addQuestion">
          {{ $t('admin_views.OcrUpload.add_question_button') }}
        </button>

        <button class="btn-primary" type="button" @click="saveQuestions">
          {{ $t('admin_views.OcrUpload.save_questions_button') }}
        </button>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { ocrApi } from "@/services/api";

const { t } = useI18n();
const fileInput = ref(null);
const fileName = ref("");
const progress = ref(0);
const isUploading = ref(false);
const errorMessage = ref("");
const loadingText = ref(t("admin_views.OcrUpload.loading_upload"));
const progressTimer = ref(null);

const currentView = ref("upload");
const questions = ref([]);
const showReadyMessage = ref(false);

const totalQuestions = computed(() => questions.value.length);

const suggestions = computed(() => [
  t("admin_views.OcrUpload.suggestions.medium_10"),
  t("admin_views.OcrUpload.suggestions.concepts_first"),
  t("admin_views.OcrUpload.suggestions.add_application"),
  t("admin_views.OcrUpload.suggestions.keep_private"),
]);

const ocrLines = computed(() => {
  if (!questions.value.length) {
    return [t("admin_views.OcrUpload.empty_ocr")];
  }

  return questions.value.map((item, index) => {
    return t("admin_views.OcrUpload.ocr_question_line", { index: index + 1, question: item.question });
  });
});

const startFakeProgress = () => {
  stopFakeProgress();

  progress.value = 10;
  loadingText.value = t("admin_views.OcrUpload.loading_upload");

  progressTimer.value = setInterval(() => {
    if (progress.value < 55) {
      progress.value += 3;
      loadingText.value = t("admin_views.OcrUpload.loading_reading");
    } else if (progress.value < 80) {
      progress.value += 1;
      loadingText.value = t("admin_views.OcrUpload.loading_ai");
    } else if (progress.value < 92) {
      progress.value += 0.3;
      loadingText.value = t("admin_views.OcrUpload.loading_normalizing");
    } else {
      loadingText.value = t("admin_views.OcrUpload.loading_finishing");
    }
  }, 800);
};

const stopFakeProgress = () => {
  if (progressTimer.value) {
    clearInterval(progressTimer.value);
    progressTimer.value = null;
  }
};

const handleFile = async (event) => {
  if (isUploading.value) return;

  const file = event.target.files?.[0];
  if (!file) return;

  fileName.value = file.name;
  isUploading.value = true;
  errorMessage.value = "";
  showReadyMessage.value = false;

  try {
    startFakeProgress();

    const result = await ocrApi.scan(file);

    stopFakeProgress();

    progress.value = 100;
    loadingText.value = t("admin_views.OcrUpload.ocr_completed");

    const responseData = result.data || result;
    const quizData = responseData.data || responseData.quizOrc || responseData;

    questions.value = quizData.questions || [];

    sessionStorage.setItem(
      "quizflex_questions",
      JSON.stringify(questions.value),
    );

    showReadyMessage.value = true;

    setTimeout(() => {
      currentView.value = "edit";
      showReadyMessage.value = false;
    }, 2000);
  } catch (error) {
    stopFakeProgress();

    errorMessage.value = t("admin_views.OcrUpload.errors.ocr_failed", { message: error.message });
    progress.value = 0;

    if (fileInput.value) {
      fileInput.value.value = "";
    }
  } finally {
    isUploading.value = false;
  }
};

const resetFile = () => {
  if (isUploading.value) return;

  stopFakeProgress();

  fileName.value = "";
  progress.value = 0;
  errorMessage.value = "";
  isUploading.value = false;
  loadingText.value = t("admin_views.OcrUpload.loading_upload");
  questions.value = [];
  showReadyMessage.value = false;
  currentView.value = "upload";

  sessionStorage.removeItem("quizflex_questions");

  if (fileInput.value) {
    fileInput.value.value = "";
  }
};

const addQuestion = () => {
  questions.value.push({
    question: "",
    options: {
      A: "",
      B: "",
      C: "",
      D: "",
    },
    correct_answer: null,
  });
};

const removeQuestion = (index) => {
  questions.value.splice(index, 1);
};

const saveQuestions = () => {
  console.log(t("admin_views.OcrUpload.save_log"), questions.value);
};
</script>
