<template>
  <section
    id="question-list-top"
    class="grid gap-6 py-8"
  >
    <!-- =========================================================
         HEADER BANNER
    ========================================================== -->
    <div
      class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-6"
    >
      <div
        class="flex flex-col justify-between gap-5 xl:flex-row xl:items-end"
      >
        <div>
          <p
            class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--primary)]"
          >
            Question Repository & Smart Builder
          </p>

          <h1
            class="mt-2 text-3xl font-black tracking-[-0.02em] text-[var(--text)]"
          >
            Ngân hàng câu hỏi độc lập
          </h1>

          <p
            class="mt-2 max-w-3xl text-sm leading-6 text-[var(--muted)]"
          >
            Tra cứu theo Chủ đề, chọn thủ công hoặc khởi tạo bộ đề thi tự động
            phân bổ theo mức độ (Dễ / Vừa / Khó) chuẩn mực cho Giáo viên &
            Học sinh.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Tạo câu hỏi -->
          <router-link
            to="/question-bank/create-question"
            class="btn-ghost inline-flex items-center gap-2 border border-[var(--border)] hover:bg-[var(--chip-active)]"
          >
            <Plus
              :size="14"
              :stroke-width="2.5"
            />

            <span>Tạo câu hỏi</span>
          </router-link>

          <!-- Tạo bộ đề tự động -->
          <button
            class="btn-primary inline-flex items-center gap-2"
            type="button"
            @click="goToCreateExam('auto')"
          >
            <Sparkles
              :size="14"
              :stroke-width="2.2"
            />

            <span>Tạo bộ đề tự động</span>
          </button>
        </div>
      </div>
    </div>

    <!-- =========================================================
         ADVANCED TAXONOMY FILTER BAR
    ========================================================== -->
    <article
      class="grid gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5"
    >
      <!-- =======================================================
           TAXONOMY FILTERS
      ======================================================== -->
      <div
        class="grid gap-3 md:grid-cols-2 xl:grid-cols-4"
      >
        <!-- 1. Cấp học -->
        <div class="relative">
          <GraduationCap
            class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-[var(--muted)]"
            :size="16"
            :stroke-width="2"
          />

          <select
            v-model="filters.education_level_id"
            class="field w-full !pl-9"
            @change="onLevelChange"
          >
            <option value="">
              Tất cả Cấp học
            </option>

            <option
              v-for="level in taxonomyLevels"
              :key="level.id"
              :value="level.id"
            >
              {{ level.name }}
            </option>
          </select>
        </div>

        <!-- 2. Lớp học -->
        <div class="relative">
          <School
            class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-[var(--muted)]"
            :size="16"
            :stroke-width="2"
          />

          <select
            v-model="filters.grade_id"
            class="field w-full !pl-9"
            @change="onGradeSubjectChange"
          >
            <option value="">
              Tất cả Khối lớp
            </option>

            <option
              v-for="grade in availableGrades"
              :key="grade.id"
              :value="grade.id"
            >
              {{ grade.name }}
            </option>
          </select>
        </div>

        <!-- 3. Bộ môn -->
        <div class="relative">
          <BookOpen
            class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-[var(--muted)]"
            :size="16"
            :stroke-width="2"
          />

          <select
            v-model="filters.subject_id"
            class="field w-full !pl-9"
            @change="onGradeSubjectChange"
          >
            <option value="">
              Tất cả Bộ môn
            </option>

            <option
              v-for="subject in availableSubjects"
              :key="subject.id"
              :value="subject.id"
            >
              {{ subject.name }}
            </option>
          </select>
        </div>

        <!-- 4. Độ khó -->
        <div class="relative">
          <Zap
            class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-[var(--muted)]"
            :size="16"
            :stroke-width="2"
          />

          <select
            v-model="filters.difficulty"
            class="field w-full !pl-9"
            @change="onFilterSubmit"
          >
            <option value="">
              Tất cả độ khó
            </option>

            <option value="easy">
              Dễ (Nhận biết)
            </option>

            <option value="medium">
              Vừa (Thông hiểu)
            </option>

            <option value="hard">
              Khó (Vận dụng)
            </option>
          </select>
        </div>
      </div>

      <!-- =======================================================
           SEARCH / TOPIC / ACTIONS
      ======================================================== -->
      <div
        class="grid gap-3 xl:grid-cols-[1fr_260px_auto_auto]"
      >
        <!-- Search -->
        <div class="relative">
          <Search
            :size="16"
            :stroke-width="2"
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]"
          />

          <input
            v-model="filters.search"
            class="field w-full !pl-9"
            placeholder="Tìm từ khóa nội dung câu hỏi..."
            @keyup.enter="onFilterSubmit"
          />
        </div>

        <!-- Chủ đề -->
        <div class="relative">
          <ListFilter
            :size="15"
            :stroke-width="2"
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]"
          />

          <select
            v-model="filters.topic_name"
            class="field w-full !pl-9"
            @change="onFilterSubmit"
          >
            <option value="">
              Tất cả Chủ đề
            </option>

            <option
              v-for="top in topicsList"
              :key="top.topic_name"
              :value="top.topic_name"
            >
              {{ top.topic_name }}
              ({{ top.total_questions }} câu)
            </option>
          </select>
        </div>

        <!-- Search -->
        <button
          class="btn-primary inline-flex items-center justify-center gap-2"
          type="button"
          @click="onFilterSubmit"
        >
          <SearchCheck
            :size="15"
            :stroke-width="2.3"
          />

          <span>Tìm kiếm & Lọc</span>
        </button>

        <!-- Reset -->
        <button
          v-if="hasActiveFilters"
          class="btn-ghost inline-flex items-center justify-center gap-1.5 text-xs font-bold text-rose-500"
          type="button"
          @click="resetFilters"
        >
          <RotateCcw
            :size="13"
            :stroke-width="2.2"
          />

          <span>Đặt lại</span>
        </button>
      </div>
    </article>

    <!-- =========================================================
         INFO & STATS BAR
    ========================================================== -->
    <div
      v-if="questions.length > 0"
      class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"
    >
      <div class="flex flex-wrap items-center gap-3 text-xs font-bold text-[var(--muted)]">
        <span class="inline-flex items-center gap-1.5">
          <List
            :size="14"
            :stroke-width="2"
          />

          <span>
            Hiển thị {{ pageStartItem }} - {{ pageEndItem }} /
            Tổng {{ pagination.total }} câu hỏi
          </span>
        </span>
      </div>
    </div>

    <!-- =========================================================
         LOADING STATE
    ========================================================== -->
    <div
      v-if="isLoading"
      class="flex min-h-[360px] flex-col items-center justify-center rounded-2xl border border-[var(--border)] bg-[var(--surface)] px-6 py-10 text-center"
    >
      <div
        class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--primary)]/10"
      >
        <FileSearch
          :size="30"
          class="animate-pulse text-[var(--primary)]"
          :stroke-width="2"
        />
      </div>

      <h2
        class="text-xl font-black text-[var(--text)]"
      >
        Đang tải Ngân hàng câu hỏi...
      </h2>

      <p
        class="mt-2 max-w-md text-sm leading-6 text-[var(--muted)]"
      >
        Vui lòng chờ trong giây lát...
      </p>

      <div
        class="mt-6 h-1.5 w-64 overflow-hidden rounded-full bg-[var(--surface-soft)]"
      >
        <div
          class="h-full w-1/3 rounded-full bg-[var(--primary)] animate-question-loading"
        ></div>
      </div>
    </div>

    <!-- =========================================================
         ERROR STATE
    ========================================================== -->
    <AppErrorState
      v-else-if="errorMessage"
      title="Không thể tải Ngân hàng câu hỏi"
      :message="errorMessage"
      @retry="loadQuestions"
    />

    <!-- =========================================================
         LOADED STATE
    ========================================================== -->
    <template v-else>
      <!-- Question List -->
      <div class="grid gap-3">
        <article
          v-for="q in questions"
          :key="q.id"
          class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-5 transition-all duration-200 hover:border-[var(--border-strong)]"
        >
          <div class="flex items-start gap-4">
            <div class="min-w-0 flex-1">
              <!-- =================================================
                   QUESTION META
              ================================================== -->
              <div
                class="mb-2 flex flex-wrap items-center gap-1.5"
              >
                <!-- ID -->
                <span
                  class="rounded-md border border-[var(--border)] bg-[var(--surface-soft)] px-2 py-0.5 text-[11px] font-bold text-[var(--muted)]"
                >
                  #{{ q.id }}
                </span>

                <!-- Type -->
                <span
                  class="rounded-md border border-[var(--border)] bg-[var(--surface-soft)] px-2 py-0.5 text-[11px] font-bold uppercase text-[var(--text)]"
                >
                  {{ q.type }}
                </span>

                <!-- Difficulty -->
                <span
                  class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[11px] font-bold"
                  :class="{
                    'bg-emerald-500/10 text-emerald-400':
                      q.difficulty === 'easy',

                    'bg-amber-500/10 text-amber-400':
                      q.difficulty === 'medium',

                    'bg-rose-500/10 text-rose-400':
                      q.difficulty === 'hard',
                  }"
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="{
                      'bg-emerald-400':
                        q.difficulty === 'easy',

                      'bg-amber-400':
                        q.difficulty === 'medium',

                      'bg-rose-400':
                        q.difficulty === 'hard',
                    }"
                  ></span>

                  {{ difficultyText(q.difficulty) }}
                </span>

                <!-- Grade -->
                <span
                  v-if="q.grade_name"
                  class="rounded-md bg-indigo-600/15 px-2 py-0.5 text-[11px] font-bold text-indigo-400"
                >
                  {{ q.grade_name }}
                </span>

                <!-- Subject -->
                <span
                  v-if="q.subject_name"
                  class="rounded-md bg-emerald-600/15 px-2 py-0.5 text-[11px] font-bold text-emerald-400"
                >
                  {{ q.subject_name }}
                </span>

                <!-- Topic -->
                <span
                  v-if="q.topic_name"
                  class="inline-flex items-center gap-1 rounded-md bg-purple-600/15 px-2 py-0.5 text-[11px] font-bold text-purple-300"
                >
                  <Tag
                    :size="11"
                    :stroke-width="2"
                  />

                  <span>
                    Chủ đề: {{ q.topic_name }}
                  </span>
                </span>

                <!-- Xem đáp án button & Report button -->
                <div class="ml-auto flex items-center gap-1.5">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-[var(--border)] bg-[var(--surface)] px-2.5 py-0.5 text-[11px] font-bold text-[var(--text)] transition hover:bg-[var(--chip-active)] cursor-pointer"
                    title="Xem đáp án của câu hỏi này"
                    @click="toggleRevealAnswer(q.id)"
                  >
                    <Loader2
                      v-if="loadingAnswerId === q.id"
                      :size="12"
                      class="animate-spin text-[var(--primary)]"
                    />
                    <EyeOff
                      v-else-if="revealedQuestionIds.has(q.id)"
                      :size="12"
                      class="text-emerald-400"
                    />
                    <Eye
                      v-else
                      :size="12"
                      class="text-[var(--muted)]"
                    />

                    <span>{{ revealedQuestionIds.has(q.id) ? 'Ẩn đáp án' : 'Xem đáp án' }}</span>
                  </button>

                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-rose-500/30 bg-rose-500/10 px-2 py-0.5 text-[11px] font-bold text-rose-400 transition hover:bg-rose-500/15"
                    title="Báo cáo vi phạm hoặc sai sót ở câu hỏi này"
                    @click="openReportModal(q.id)"
                  >
                    <Flag
                      :size="12"
                      :stroke-width="2"
                    />

                    <span>Báo cáo</span>
                  </button>
                </div>
              </div>

              <!-- Question -->
              <h3
                class="text-base font-black leading-snug text-[var(--text)]"
              >
                {{ q.content || q.text }}
              </h3>

              <!-- =================================================
                   ANSWERS (Neutral by default, highlighted only when revealed)
              ================================================== -->
              <div
                v-if="
                  q.answers &&
                  q.answers.length > 0
                "
                class="mt-3 grid gap-2 md:grid-cols-2"
              >
                <div
                  v-for="ans in q.answers"
                  :key="ans.id"
                  class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition-colors duration-150"
                  :class="
                    isAnswerCorrectRevealed(q.id, ans)
                      ? 'border-emerald-500/40 bg-emerald-500/10 font-bold text-emerald-300 shadow-sm'
                      : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]'
                  "
                >
                  <!-- Answer key -->
                  <span
                    class="grid h-5 w-5 shrink-0 place-items-center rounded-md text-[10px] font-black"
                    :class="isAnswerCorrectRevealed(q.id, ans) ? 'bg-emerald-500 text-white' : 'bg-black/25 text-[var(--text)]'"
                  >
                    {{ ans.key }}
                  </span>

                  <!-- Answer text -->
                  <span class="truncate">
                    {{ ans.text || ans.content }}
                  </span>

                  <!-- Correct (only if revealed) -->
                  <span
                    v-if="isAnswerCorrectRevealed(q.id, ans)"
                    class="ml-auto inline-flex shrink-0 items-center gap-1 text-xs text-emerald-400 font-bold"
                  >
                    <CheckCircle2
                      :size="14"
                      :stroke-width="2.2"
                    />

                    <span>Đúng</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>

      <!-- =======================================================
           EMPTY STATE
      ======================================================== -->
      <div
        v-if="questions.length === 0"
        class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-10 text-center"
      >
        <div
          class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-[var(--primary)]/10"
        >
          <FileSearch
            :size="30"
            :stroke-width="1.8"
            class="text-[var(--primary)]"
          />
        </div>

        <h3
          class="text-xl font-black text-[var(--text)]"
        >
          Không tìm thấy câu hỏi nào
        </h3>

        <p
          class="mt-2 text-sm text-[var(--muted)]"
        >
          Thử thay đổi bộ lọc Cấp học, Lớp hoặc Chủ đề.
        </p>

        <button
          class="btn-ghost mt-4 inline-flex items-center gap-1.5 text-xs font-black text-[var(--primary)]"
          @click="resetFilters"
        >
          <RotateCcw
            :size="13"
            :stroke-width="2"
          />

          <span>Đặt lại bộ lọc</span>
        </button>
      </div>

      <!-- =======================================================
           PAGINATION
      ======================================================== -->
      <div
        v-if="pagination.last_page > 1"
        class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4"
      >
        <div
          class="flex items-center gap-2 text-xs font-bold text-[var(--muted)]"
        >
          <span class="inline-flex items-center gap-1.5">
            <List
              :size="14"
              :stroke-width="2"
            />

            <span>
              Trang {{ pagination.current_page }} /
              {{ pagination.last_page }}

              (Hiển thị {{ pageStartItem }} -
              {{ pageEndItem }} /
              {{ pagination.total }} câu)
            </span>
          </span>

          <select
            v-model.number="pagination.per_page"
            class="field ml-2 cursor-pointer !px-2 !py-1 text-xs"
            @change="onPerPageChange"
          >
            <option :value="10">
              10 câu / trang
            </option>

            <option :value="15">
              15 câu / trang
            </option>

            <option :value="25">
              25 câu / trang
            </option>

            <option :value="50">
              50 câu / trang
            </option>
          </select>
        </div>

        <div class="flex items-center gap-1.5">
          <!-- Previous -->
          <button
            class="btn-ghost inline-flex items-center gap-1 !px-3 !py-1.5 text-xs font-bold"
            :disabled="
              pagination.current_page <= 1
            "
            @click="
              changePage(
                pagination.current_page - 1
              )
            "
          >
            <ChevronLeft
              :size="14"
              :stroke-width="2.3"
            />

            <span>Trang trước</span>
          </button>

          <!-- Page numbers -->
          <button
            v-for="p in visiblePages"
            :key="p"
            class="h-8 w-8 rounded-lg text-xs font-black transition duration-150"
            :class="
              p === pagination.current_page
                ? 'bg-[var(--primary)] text-white shadow-sm'
                : 'bg-[var(--surface-soft)] text-[var(--text)] hover:bg-[var(--primary)]/15'
            "
            @click="changePage(p)"
          >
            {{ p }}
          </button>

          <!-- Next -->
          <button
            class="btn-ghost inline-flex items-center gap-1 !px-3 !py-1.5 text-xs font-bold"
            :disabled="
              pagination.current_page >=
              pagination.last_page
            "
            @click="
              changePage(
                pagination.current_page + 1
              )
            "
          >
            <span>Trang sau</span>

            <ChevronRight
              :size="14"
              :stroke-width="2.3"
            />
          </button>
        </div>
      </div>
    </template>

    <!-- =========================================================
         REPORT MODAL
    ========================================================== -->
    <QuestionReportModal
      :question-id="reportingQuestionId"
      :is-open="isReportModalOpen"
      @close="isReportModalOpen = false"
    />
  </section>
</template>

<script setup>
import {
  computed,
  onMounted,
  reactive,
  ref,
  watch,
} from "vue";

import {
  useRoute,
  useRouter,
} from "vue-router";

import AppErrorState from "@/components/common/AppErrorState.vue";

import QuestionReportModal from "@/components/question/QuestionReportModal.vue";

import {
  Plus,
  Sparkles,
  Search,
  SearchCheck,
  RotateCcw,
  FileCheck2,
  Flag,
  CheckCircle2,
  FileSearch,
  ChevronLeft,
  ChevronRight,
  GraduationCap,
  School,
  BookOpen,
  Zap,
  ListFilter,
  List,
  Tag,
  Check,
  Eye,
  EyeOff,
  Loader2,
} from "lucide-vue-next";

import {
  questionsBankApi,
  taxonomyApi,
} from "@/services/api";

/* =========================================================
   REPORT MODAL
========================================================= */

const isReportModalOpen = ref(false);
const reportingQuestionId = ref(null);

const openReportModal = (questionId) => {
  reportingQuestionId.value = questionId;
  isReportModalOpen.value = true;
};

/* =========================================================
   ROUTER
========================================================= */

const route = useRoute();
const router = useRouter();

/* =========================================================
   STATE
========================================================= */

const questions = ref([]);
const topicsList = ref([]);

const revealedQuestionIds = ref(new Set());
const revealedAnswersMap = ref(new Map());
const loadingAnswerId = ref(null);

const isLoading = ref(false);
const errorMessage = ref("");
const isSubmitting = ref(false);

const taxonomyLevels = ref([]);
const allSubjects = ref([]);

/* =========================================================
   REVEAL ANSWER LOGIC
========================================================= */

const toggleRevealAnswer = async (questionId) => {
  if (revealedQuestionIds.value.has(questionId)) {
    revealedQuestionIds.value.delete(questionId);
    return;
  }

  if (!revealedAnswersMap.value.has(questionId)) {
    loadingAnswerId.value = questionId;
    try {
      const detail = await questionsBankApi.getQuestion(questionId);
      if (detail) {
        revealedAnswersMap.value.set(questionId, detail);
      }
    } catch (e) {
      console.error("Không thể tải đáp án câu hỏi:", e);
    } finally {
      loadingAnswerId.value = null;
    }
  }

  revealedQuestionIds.value.add(questionId);
};

const isAnswerCorrectRevealed = (questionId, ans) => {
  if (!revealedQuestionIds.value.has(questionId)) return false;
  const detail = revealedAnswersMap.value.get(questionId);
  if (!detail || !detail.answers) return false;
  const matchedAns = detail.answers.find(
    (a) =>
      (ans.id && a.id === ans.id) ||
      a.key === ans.key ||
      a.answer_key === ans.key
  );
  return Boolean(matchedAns?.is_correct);
};

/* =========================================================
   FILTERS
========================================================= */

const filters = reactive({
  search: "",
  education_level_id: "",
  grade_id: "",
  subject_id: "",
  topic_name: "",
  difficulty: "",
});

/* =========================================================
   PAGINATION
========================================================= */

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

const pageStartItem = computed(() => {
  if (pagination.total === 0) return 0;

  return (
    (pagination.current_page - 1) *
      pagination.per_page +
    1
  );
});

const pageEndItem = computed(() => {
  return Math.min(
    pagination.current_page *
      pagination.per_page,
    pagination.total
  );
});

const visiblePages = computed(() => {
  const pages = [];
  const maxPages = 5;

  let start = Math.max(
    1,
    pagination.current_page - 2
  );

  let end = Math.min(
    pagination.last_page,
    start + maxPages - 1
  );

  if (end - start + 1 < maxPages) {
    start = Math.max(
      1,
      end - maxPages + 1
    );
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

/* =========================================================
   TAXONOMY
========================================================= */

const fetchTaxonomy = async () => {
  try {
    const data =
      await taxonomyApi.tree();

    const payload =
      data?.education_levels
        ? data
        : (data?.data ?? data);

    if (payload) {
      taxonomyLevels.value =
        payload.education_levels ||
        payload.educationLevels ||
        [];

      allSubjects.value =
        payload.subjects || [];
    }
  } catch (e) {
    console.error(
      "Không tải được taxonomy:",
      e
    );
  }
};

/* =========================================================
   TOPICS
========================================================= */

const fetchTopicsList = async () => {
  try {
    const params = {
      education_level_id:
        filters.education_level_id ||
        undefined,

      grade_id:
        filters.grade_id ||
        undefined,

      subject_id:
        filters.subject_id ||
        undefined,
    };

    const data =
      await questionsBankApi.fetchTopics(
        params
      );

    topicsList.value =
      Array.isArray(data)
        ? data
        : (
            data?.data &&
            Array.isArray(data.data)
              ? data.data
              : []
          );
  } catch (e) {
    console.error(
      "Không tải được danh sách Chuyên đề:",
      e
    );
  }
};



/* =========================================================
   AVAILABLE GRADES / SUBJECTS
========================================================= */

const availableGrades = computed(() => {
  if (!filters.education_level_id) {
    return taxonomyLevels.value.flatMap(
      (l) => l.grades || []
    );
  }

  const level =
    taxonomyLevels.value.find(
      (l) =>
        l.id ===
        Number(
          filters.education_level_id
        )
    );

  return level
    ? level.grades || []
    : [];
});

const availableSubjects = computed(() => {
  if (!filters.grade_id) {
    return allSubjects.value;
  }

  const grade =
    availableGrades.value.find(
      (g) =>
        g.id ===
        Number(filters.grade_id)
    );

  return grade &&
    grade.subjects &&
    grade.subjects.length
    ? grade.subjects
    : allSubjects.value;
});



/* =========================================================
   FILTER EVENTS
========================================================= */

const onLevelChange = () => {
  filters.grade_id = "";
  onGradeSubjectChange();
};

const onGradeSubjectChange = () => {
  fetchTopicsList();
  onFilterSubmit();
};



const selectTopic = (topicName) => {
  filters.topic_name = topicName;
  onFilterSubmit();
};

/* =========================================================
   FILTER SUBMIT
========================================================= */

const onFilterSubmit = () => {
  pagination.current_page = 1;
  loadQuestions();
};

/* =========================================================
   PAGINATION
========================================================= */

const changePage = (page) => {
  if (
    page < 1 ||
    page > pagination.last_page ||
    page === pagination.current_page
  ) {
    return;
  }

  pagination.current_page = page;

  loadQuestions();

  const el =
    document.getElementById(
      "question-list-top"
    );

  if (el) {
    el.scrollIntoView({
      behavior: "smooth",
    });
  }
};

const onPerPageChange = () => {
  pagination.current_page = 1;
  loadQuestions();
};

/* =========================================================
   ACTIVE FILTERS
========================================================= */

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.search ||
      filters.education_level_id ||
      filters.grade_id ||
      filters.subject_id ||
      filters.topic_name ||
      filters.difficulty
  );
});

/* =========================================================
   RESET
========================================================= */

const resetFilters = () => {
  filters.search = "";
  filters.education_level_id = "";
  filters.grade_id = "";
  filters.subject_id = "";
  filters.topic_name = "";
  filters.difficulty = "";

  pagination.current_page = 1;

  router.push({
    path: "/question-bank",
  });

  fetchTopicsList();
  loadQuestions();
};

/* =========================================================
   ROUTE FILTERS
========================================================= */

const applyRouteFilters = () => {
  filters.search =
    typeof route.query.search ===
    "string"
      ? route.query.search
      : "";

  filters.difficulty =
    typeof route.query.difficulty ===
    "string"
      ? route.query.difficulty
      : "";

  filters.education_level_id =
    route.query.education_level_id
      ? Number(
          route.query.education_level_id
        )
      : "";

  filters.grade_id =
    route.query.grade_id
      ? Number(route.query.grade_id)
      : "";

  filters.subject_id =
    route.query.subject_id
      ? Number(route.query.subject_id)
      : "";

  filters.topic_name =
    typeof route.query.topic_name ===
    "string"
      ? route.query.topic_name
      : "";
};



/* =========================================================
   LOAD QUESTIONS
========================================================= */

const loadQuestions = async () => {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    const params = {
      search:
        filters.search || undefined,

      education_level_id:
        filters.education_level_id ||
        undefined,

      grade_id:
        filters.grade_id || undefined,

      subject_id:
        filters.subject_id ||
        undefined,

      topic_name:
        filters.topic_name ||
        undefined,

      difficulty:
        filters.difficulty ||
        undefined,

      page: pagination.current_page,

      per_page:
        pagination.per_page,
    };

    const res =
      await questionsBankApi.fetchBank(
        params
      );

    if (
      res &&
      Array.isArray(res.items)
    ) {
      questions.value =
        res.items;

      pagination.total =
        res.total ??
        res.items.length;

      pagination.current_page =
        res.currentPage ?? 1;

      pagination.last_page =
        res.lastPage ?? 1;

      pagination.per_page =
        res.perPage ??
        pagination.per_page;
    } else if (
      Array.isArray(res)
    ) {
      questions.value = res;

      pagination.total =
        res.length;

      pagination.current_page = 1;
      pagination.last_page = 1;
    } else {
      questions.value = [];

      pagination.total = 0;
      pagination.current_page = 1;
      pagination.last_page = 1;
    }
  } catch (error) {
    errorMessage.value =
      `Không tải được Ngân hàng câu hỏi: ${error.message}`;
  } finally {
    isLoading.value = false;
  }
};

/* =========================================================
   CREATE EXAM
========================================================= */

const goToCreateExam = (
  mode = "random"
) => {
  const query = {
    mode: mode === "manual" ? "manual" : "random",

    education_level_id:
      filters.education_level_id ||
      undefined,

    grade_id:
      filters.grade_id ||
      undefined,

    subject_id:
      filters.subject_id ||
      undefined,

    topic_name:
      filters.topic_name ||
      undefined,
  };

  router.push({
    path: "/question-bank/create-exam",
    query,
  });
};

/* =========================================================
   WATCH ROUTE
========================================================= */

watch(
  () => route.query,
  () => {
    applyRouteFilters();
    fetchTopicsList();
    loadQuestions();
  }
);

/* =========================================================
   MOUNT
========================================================= */

onMounted(async () => {
  await fetchTaxonomy();

  applyRouteFilters();

  await fetchTopicsList();

  await loadQuestions();
});
</script>

<style scoped>
@keyframes question-loading {
  0% {
    transform: translateX(-150%);
  }

  50% {
    transform: translateX(120%);
  }

  100% {
    transform: translateX(350%);
  }
}

.animate-question-loading {
  animation: question-loading 1.4s ease-in-out infinite;
}
</style>