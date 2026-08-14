<template>
  <section class="grid gap-6 py-4">
    <!-- Header -->
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
          Danh sách bộ câu hỏi
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
        <Plus :size="15" :stroke-width="2.5" />
        <span>Tạo quiz mới</span>
      </router-link>
    </div>

    <!-- Filters -->
    <article class="card p-4 sm:p-5">
      <div
        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_180px_160px_160px_auto]"
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
            placeholder="Tìm kiếm quiz, chủ đề, tag..."
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
            <option value="all">Tất cả chế độ</option>
            <option value="public">Công khai (Public)</option>
            <option value="private">Riêng tư (Private)</option>
            <option value="group">Nhóm (Group)</option>
          </select>
        </div>

        <!-- Difficulty -->
        <div class="relative">
          <SlidersHorizontal
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            :size="15"
            :stroke-width="2"
          />

          <select
            v-model="filters.difficulty"
            class="field w-full pl-9 text-xs"
            @change="loadQuizzes"
          >
            <option value="">Tất cả độ khó</option>
            <option value="easy">Dễ</option>
            <option value="medium">Vừa</option>
            <option value="hard">Khó</option>
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
            <option value="all">Tất cả tag</option>
            <option value="AI">AI</option>

            <option
              v-for="tag in tags"
              :key="tag"
              :value="tag"
            >
              {{ tag }}
            </option>
          </select>
        </div>

        <!-- Search Button -->
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition-all duration-200 hover:border-[#7C3AED]/30 hover:bg-[#F5F3FF] hover:text-[#7C3AED] active:scale-[0.98]"
          @click="loadQuizzes"
        >
          <Search :size="14" :stroke-width="2.5" />
          <span>Tìm kiếm</span>
        </button>
      </div>
    </article>

    <!-- Loading -->
    <AppLoadingState
      v-if="isLoading"
      title="Đang tải danh sách quiz..."
      message="Vui lòng chờ trong giây lát để hệ thống tổng hợp danh sách các bộ câu hỏi."
    />

    <!-- Error -->
    <AppErrorState
      v-else-if="errorMessage"
      title="Không thể tải danh sách quiz"
      :message="errorMessage"
      @retry="loadQuizzes"
    />

    <!-- Loaded -->
    <template v-else>
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
              <!-- Overlay -->
              <div
                class="absolute inset-0 bg-gradient-to-br from-black/10 via-transparent to-white/10"
              ></div>

              <!-- Quiz Badge -->
              <div
                class="absolute left-3 top-3 rounded-lg bg-slate-900/80 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur-sm"
              >
                {{ quiz.badge || 'Quiz' }}
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
                <VisibilityBadge :value="quiz.visibility" />

                <span
                  class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600"
                >
                  {{ quiz.difficulty }}
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
                {{ quiz.description || 'Chưa có mô tả chi tiết.' }}
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

      <!-- Empty State -->
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
          Hãy thử thay đổi tiêu chí bộ lọc hoặc tạo quiz mới
          trong tài khoản của bạn.
        </p>
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
} from 'vue'

import { useRoute } from 'vue-router'

import {
  Globe,
  Plus,
  Search,
  SlidersHorizontal,
  Tag,
  UserRound,
} from 'lucide-vue-next'

import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'

import {
  currentUserStorage,
  normalizeQuizCard,
  quizzesApi,
} from '@/services/api'

const route = useRoute()

const quizzes = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const tags = ref([])

const filters = reactive({
  search: '',
  visibility: currentUserStorage.get()
    ? 'all'
    : 'public',
  difficulty: '',
  tag: 'all',
})

const applyRouteFilters = () => {
  filters.search =
    typeof route.query.search === 'string'
      ? route.query.search
      : ''

  filters.difficulty =
    typeof route.query.difficulty === 'string'
      ? route.query.difficulty
      : ''

  filters.tag =
    typeof route.query.tag === 'string'
      ? route.query.tag
      : 'all'

  const visibility =
    typeof route.query.visibility === 'string'
      ? route.query.visibility
      : ''

  filters.visibility = [
    'all',
    'public',
    'private',
    'group',
  ].includes(visibility)
    ? visibility
    : currentUserStorage.get()
      ? 'all'
      : 'public'
}

const loadQuizzes = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const params = {
      search: filters.search || undefined,

      visibility:
        filters.visibility === 'all'
          ? undefined
          : filters.visibility,

      difficulty:
        filters.difficulty || undefined,
    }

    const data = await quizzesApi.list(params)

    quizzes.value = data.map(normalizeQuizCard)

    tags.value = [
      ...new Set(
        quizzes.value
          .map((quiz) => quiz.tag)
          .filter(Boolean)
          .filter((tag) => tag !== 'AI'),
      ),
    ]
  } catch (error) {
    errorMessage.value =
      `Không tải được quiz: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const filteredQuizzes = computed(() => {
  return quizzes.value.filter(
    (quiz) =>
      filters.tag === 'all' ||
      quiz.tag === filters.tag,
  )
})

onMounted(async () => {
  applyRouteFilters()
  await loadQuizzes()
})
</script>