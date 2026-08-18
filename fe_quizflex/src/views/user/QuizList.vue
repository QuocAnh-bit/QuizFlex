<template>
  <section class="grid gap-6 py-4">
    <!-- =========================================================
         HEADER
    ========================================================== -->
    <div
      class="card flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center sm:p-8"
    >
      <div>
        <p
          class="text-xs font-bold uppercase tracking-[0.12em] text-[#7C3AED]"
        >
          Khám phá kiến thức
        </p>

        <h1
          class="mt-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"
        >
          Danh sách Quiz
        </h1>

        <p class="mt-1 text-sm text-slate-600">
          Lựa chọn bộ quiz phù hợp để luyện tập, thi đấu hoặc kiểm tra kiến thức.
        </p>
      </div>

      <!-- Create Quiz -->
      <router-link
        to="/dashboard/questions/create"
        class="inline-flex shrink-0 items-center justify-center gap-2 self-start rounded-xl bg-[#7C3AED] px-4 py-2.5 text-xs font-bold text-white shadow-[0_4px_12px_rgba(124,58,237,0.18)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#6D28D9] hover:shadow-[0_7px_18px_rgba(124,58,237,0.22)] active:translate-y-0 sm:self-auto"
      >
        <Plus
          :size="15"
          :stroke-width="2.5"
        />

        <span>Tạo quiz mới</span>
      </router-link>
    </div>

    <!-- =========================================================
         ADVANCED TAXONOMY & SEARCH FILTER BAR
    ========================================================== -->
    <article class="card p-4 sm:p-5">
      <!-- =======================================================
           TAXONOMY FILTERS
      ======================================================== -->
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- 1. Cấp học -->
        <div class="relative">
          <GraduationCap
            class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-slate-400"
            :size="15"
            :stroke-width="2"
          />

          <select
            v-model="filters.education_level_id"
            class="field w-full pl-9 text-xs"
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
            class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-slate-400"
            :size="15"
            :stroke-width="2"
          />

          <select
            v-model="filters.grade_id"
            class="field w-full pl-9 text-xs"
            @change="loadQuizzes"
          >
            <option value="">
              Tất cả Lớp
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
            class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-slate-400"
            :size="15"
            :stroke-width="2"
          />

          <select
            v-model="filters.subject_id"
            class="field w-full pl-9 text-xs"
            @change="loadQuizzes"
          >
            <option value="">
              Tất cả Môn học
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
            class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-slate-400"
            :size="15"
            :stroke-width="2"
          />

          <select
            v-model="filters.difficulty"
            class="field w-full pl-9 text-xs"
            @change="loadQuizzes"
          >
            <option value="">
              Tất cả độ khó
            </option>

            <option value="easy">
              Dễ
            </option>

            <option value="medium">
              Vừa
            </option>

            <option value="hard">
              Khó
            </option>
          </select>
        </div>
      </div>

      <!-- =======================================================
           SEARCH / VISIBILITY / TAG / BUTTONS
      ======================================================== -->
      <div
        class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_180px_160px_auto]"
      >
        <!-- Search -->
        <div class="relative">
          <Search
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            :size="16"
            :stroke-width="2"
          />

          <input
            v-model="filters.search"
            class="field w-full pl-9 text-xs"
            placeholder="Tìm kiếm quiz, chuyên đề, từ khóa..."
            @keyup.enter="loadQuizzes"
          />
        </div>

        <!-- Visibility -->
        <div class="relative">
          <Globe
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            :size="15"
            :stroke-width="2"
          />

          <select
            v-model="filters.visibility"
            class="field w-full pl-9 text-xs"
            @change="loadQuizzes"
          >
            <option value="all">
              Tất cả chế độ
            </option>

            <option value="public">
              Công khai (Public)
            </option>

            <option value="private">
              Riêng tư (Private)
            </option>

            <option value="group">
              Nhóm (Group)
            </option>
          </select>
        </div>

        <!-- Tag -->
        <div class="relative">
          <Tag
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            :size="15"
            :stroke-width="2"
          />

          <select
            v-model="filters.tag"
            class="field w-full pl-9 text-xs"
          >
            <option value="all">
              Tất cả tag
            </option>

            <option value="AI">
              AI
            </option>

            <option
              v-for="tag in tags"
              :key="tag"
              :value="tag"
            >
              {{ tag }}
            </option>
          </select>
        </div>

        <!-- Search & Reset Buttons -->
        <div class="flex items-center gap-2">
          <!-- Search -->
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition-all duration-200 hover:border-[#7C3AED]/30 hover:bg-[#F5F3FF] hover:text-[#7C3AED] active:scale-[0.98]"
            @click="loadQuizzes"
          >
            <Search
              :size="14"
              :stroke-width="2.5"
            />

            <span>Tìm kiếm</span>
          </button>

          <!-- Reset -->
          <button
            v-if="hasActiveFilters"
            type="button"
            class="btn-ghost inline-flex items-center justify-center gap-1.5 px-3 py-2.5 text-xs font-bold text-rose-500"
            @click="resetFilters"
          >
            <RotateCcw
              :size="14"
              :stroke-width="2.2"
            />

            <span>Đặt lại</span>
          </button>
        </div>
      </div>
    </article>

    <!-- =========================================================
         LOADING - RIÊNG CHO TRANG
         KHÔNG DÙNG AppLoadingState
    ========================================================== -->
    <div
      v-if="isLoading"
      class="card flex min-h-[360px] flex-col items-center justify-center px-6 py-10 text-center"
    >
      <!-- Loading Icon -->
      <div
        class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#F5F3FF]"
      >
        <FileSearch
          :size="30"
          :stroke-width="2"
          class="animate-pulse text-[#7C3AED]"
        />
      </div>

      <!-- Loading Title -->
      <h2 class="text-xl font-black text-slate-900">
        Đang tải danh sách quiz...
      </h2>

      <!-- Loading Message -->
      <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">
        Vui lòng chờ trong giây lát để hệ thống tổng hợp danh sách các bộ câu hỏi.
      </p>

      <!-- Loading Bar -->
      <div
        class="mt-6 h-1.5 w-64 overflow-hidden rounded-full bg-slate-100"
      >
        <div
          class="h-full w-1/3 rounded-full bg-[#7C3AED] animate-quiz-loading"
        ></div>
      </div>
    </div>

    <!-- =========================================================
         ERROR
    ========================================================== -->
    <AppErrorState
      v-else-if="errorMessage"
      title="Không thể tải danh sách quiz"
      :message="errorMessage"
      @retry="loadQuizzes"
    />

    <!-- =========================================================
         LOADED
    ========================================================== -->
    <template v-else>
      <!-- Quiz Cards -->
      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="quiz in filteredQuizzes"
          :key="quiz.id"
          class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_4px_16px_rgba(15,23,42,0.05)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#7C3AED]/20 hover:shadow-[0_10px_28px_rgba(15,23,42,0.08)]"
        >
          <router-link
            :to="`/quizzes/${quiz.id}`"
            class="block"
          >
            <!-- Cover -->
            <div
              class="relative h-32 overflow-hidden"
              :style="{ background: quiz.cover }"
            >
              <div
                class="absolute inset-0 bg-gradient-to-br from-black/10 via-transparent to-white/10"
              ></div>

              <!-- Badges / Taxonomy -->
              <div
                class="absolute left-3 top-3 flex flex-wrap gap-1.5"
              >
                <span
                  v-if="quiz.grade_name"
                  class="rounded-md bg-slate-900/80 px-2 py-0.5 text-[10px] font-bold text-white backdrop-blur-sm"
                >
                  {{ quiz.grade_name }}
                </span>

                <span
                  v-if="quiz.subject_name"
                  class="rounded-md bg-emerald-600/80 px-2 py-0.5 text-[10px] font-bold text-white backdrop-blur-sm"
                >
                  {{ quiz.subject_name }}
                </span>

                <span
                  v-else
                  class="rounded-md bg-slate-900/80 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur-sm"
                >
                  {{ quiz.badge || "Quiz" }}
                </span>
              </div>

              <!-- User Icon -->
              <div
                class="absolute bottom-3 right-3 grid h-11 w-11 place-items-center rounded-xl border border-white/70 bg-white shadow-[0_4px_12px_rgba(15,23,42,0.10)] transition-transform duration-200 group-hover:scale-105"
              >
                <UserRound
                  :size="20"
                  :stroke-width="1.8"
                  class="text-[#7C3AED]"
                />
              </div>
            </div>

            <!-- Content -->
            <div class="space-y-3 p-5">
              <!-- Visibility + Difficulty -->
              <div class="flex items-center gap-2">
                <VisibilityBadge
                  :value="quiz.visibility"
                />

                <span
                  class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600"
                >
                  {{ quiz.difficulty }}
                </span>

                <span
                  v-if="
                    quiz.topic_name &&
                    quiz.topic_name !== quiz.subject_name
                  "
                  class="max-w-[150px] truncate rounded-full bg-purple-50 px-2.5 py-1 text-[11px] font-bold text-purple-700"
                >
                  {{ quiz.topic_name }}
                </span>
              </div>

              <!-- Title -->
              <h2
                class="line-clamp-2 text-base font-bold text-slate-900 transition-colors group-hover:text-[#7C3AED]"
              >
                {{ quiz.title }}
              </h2>

              <!-- Description -->
              <p
                class="line-clamp-2 text-xs leading-relaxed text-slate-500"
              >
                {{
                  quiz.description ||
                  "Chưa có mô tả chi tiết."
                }}
              </p>

              <!-- Stats -->
              <div
                class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-center"
              >
                <!-- Questions -->
                <div
                  class="rounded-xl bg-slate-50 px-2 py-2.5 transition-colors group-hover:bg-[#F8F7FF]"
                >
                  <b
                    class="block text-sm font-bold text-slate-900"
                  >
                    {{ quiz.questions }}
                  </b>

                  <span
                    class="mt-0.5 block text-[10px] font-semibold text-slate-400"
                  >
                    Câu hỏi
                  </span>
                </div>

                <!-- Duration -->
                <div
                  class="rounded-xl bg-slate-50 px-2 py-2.5 transition-colors group-hover:bg-[#F8F7FF]"
                >
                  <b
                    class="block text-sm font-bold text-slate-900"
                  >
                    {{ quiz.duration }}
                  </b>

                  <span
                    class="mt-0.5 block text-[10px] font-semibold text-slate-400"
                  >
                    Thời gian
                  </span>
                </div>

                <!-- Average Score -->
                <div
                  class="rounded-xl bg-slate-50 px-2 py-2.5 transition-colors group-hover:bg-[#F8F7FF]"
                >
                  <b
                    class="block text-sm font-bold text-[#7C3AED]"
                  >
                    {{ quiz.avgScore }}%
                  </b>

                  <span
                    class="mt-0.5 block text-[10px] font-semibold text-slate-400"
                  >
                    Điểm TB
                  </span>
                </div>
              </div>
            </div>
          </router-link>
        </article>
      </div>

      <!-- =======================================================
           EMPTY STATE
      ======================================================== -->
      <div
        v-if="filteredQuizzes.length === 0"
        class="card flex flex-col items-center justify-center p-12 text-center"
      >
        <div
          class="mb-4 grid h-12 w-12 place-items-center rounded-xl bg-[#F5F3FF]"
        >
          <Search
            :size="21"
            :stroke-width="1.8"
            class="text-[#7C3AED]"
          />
        </div>

        <h3 class="text-lg font-bold text-slate-800">
          Không tìm thấy quiz phù hợp
        </h3>

        <p
          class="mt-1 max-w-md text-xs leading-relaxed text-slate-500"
        >
          Hãy thử thay đổi tiêu chí bộ lọc hoặc tạo quiz mới trong tài khoản của bạn.
        </p>

        <button
          class="btn-ghost mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-[#7C3AED]"
          @click="resetFilters"
        >
          <RotateCcw
            :size="14"
            :stroke-width="2"
          />

          <span>Đặt lại bộ lọc</span>
        </button>
      </div>
    </template>
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

import {
  Globe,
  Plus,
  Search,
  Tag,
  UserRound,
  FileSearch,
  GraduationCap,
  School,
  BookOpen,
  Zap,
  RotateCcw,
} from "@lucide/vue";

import AppErrorState from "@/components/common/AppErrorState.vue";
import VisibilityBadge from "@/components/common/VisibilityBadge.vue";

import {
  currentUserStorage,
  normalizeQuizCard,
  quizzesApi,
  taxonomyApi,
} from "@/services/api";

/* =========================================================
   ROUTER
========================================================= */

const route = useRoute();
const router = useRouter();

/* =========================================================
   STATE
========================================================= */

const quizzes = ref([]);
const isLoading = ref(false);
const errorMessage = ref("");
const tags = ref([]);

const taxonomyLevels = ref([]);
const allSubjects = ref([]);

/* =========================================================
   FILTERS
========================================================= */

const filters = reactive({
  search: "",

  visibility: currentUserStorage.get()
    ? "all"
    : "public",

  difficulty: "",

  education_level_id: "",

  grade_id: "",

  subject_id: "",

  tag: "all",
});

/* =========================================================
   TAXONOMY
========================================================= */

const fetchTaxonomyTree = async () => {
  try {
    const data = await taxonomyApi.tree();

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
      "Không tải được danh mục Cấp học:",
      e
    );
  }
};

/* =========================================================
   AVAILABLE GRADES
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

/* =========================================================
   AVAILABLE SUBJECTS
========================================================= */

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
   LEVEL CHANGE
========================================================= */

const onLevelChange = () => {
  filters.grade_id = "";
  loadQuizzes();
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
      filters.difficulty
  );
});

/* =========================================================
   RESET FILTERS
========================================================= */

const resetFilters = () => {
  filters.search = "";
  filters.difficulty = "";
  filters.education_level_id = "";
  filters.grade_id = "";
  filters.subject_id = "";
  filters.tag = "all";

  router.push({
    path: "/quizzes",
  });

  loadQuizzes();
};

/* =========================================================
   APPLY ROUTE FILTERS
========================================================= */

const applyRouteFilters = () => {
  filters.search =
    typeof route.query.search === "string"
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

  filters.tag =
    typeof route.query.tag === "string"
      ? route.query.tag
      : "all";

  const visibility =
    typeof route.query.visibility ===
    "string"
      ? route.query.visibility
      : "";

  filters.visibility = [
    "all",
    "public",
    "private",
    "group",
  ].includes(visibility)
    ? visibility
    : currentUserStorage.get()
      ? "all"
      : "public";
};

/* =========================================================
   LOAD QUIZZES
========================================================= */

const loadQuizzes = async () => {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    const params = {
      search:
        filters.search || undefined,

      visibility:
        filters.visibility === "all"
          ? undefined
          : filters.visibility,

      difficulty:
        filters.difficulty || undefined,

      education_level_id:
        filters.education_level_id ||
        undefined,

      grade_id:
        filters.grade_id || undefined,

      subject_id:
        filters.subject_id ||
        undefined,
    };

    const data =
      await quizzesApi.list(params);

    const list = Array.isArray(data)
      ? data
      : (
          data?.data &&
          Array.isArray(data.data)
            ? data.data
            : []
        );

    quizzes.value = list.map((q) => {
      const card =
        normalizeQuizCard(q);

      card.education_level_name =
        q.education_level_name;

      card.grade_name =
        q.grade_name;

      card.subject_name =
        q.subject_name;

      card.topic_name =
        q.topic_name;

      return card;
    });

    tags.value = [
      ...new Set(
        quizzes.value
          .map((quiz) => quiz.tag)
          .filter(Boolean)
          .filter(
            (tag) => tag !== "AI"
          )
      ),
    ];
  } catch (error) {
    errorMessage.value =
      `Không tải được quiz: ${error.message}`;
  } finally {
    isLoading.value = false;
  }
};

/* =========================================================
   FILTERED QUIZZES
========================================================= */

const filteredQuizzes = computed(() => {
  return quizzes.value.filter(
    (quiz) =>
      filters.tag === "all" ||
      quiz.tag === filters.tag
  );
});

/* =========================================================
   WATCH ROUTE
========================================================= */

watch(
  () => route.query,
  () => {
    applyRouteFilters();
    loadQuizzes();
  }
);

/* =========================================================
   MOUNT
========================================================= */

onMounted(async () => {
  await fetchTaxonomyTree();

  applyRouteFilters();

  await loadQuizzes();
});
</script>

<style scoped>
/* =========================================================
   PAGE LOADING ANIMATION
========================================================= */

@keyframes quiz-loading {
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

.animate-quiz-loading {
  animation: quiz-loading 1.4s ease-in-out infinite;
}
</style>