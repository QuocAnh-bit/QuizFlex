<template>
  <div class="py-2">
    <!-- Main 2-Column Dashboard Grid -->
    <div
      class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start"
    >
      <!-- LEFT MAIN COLUMN -->
      <div class="lg:col-span-8 space-y-8">

        <!-- 1. HERO SECTION -->
        <section
          class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-[0_4px_16px_rgba(15,23,42,0.06)] relative overflow-hidden"
        >
          <div
            class="grid gap-6 md:grid-cols-[1.15fr_0.85fr] items-center"
          >
            <!-- HERO LEFT -->
            <div class="space-y-4 relative z-10">
              <!-- Welcome -->
              <div
                v-if="currentUser"
                class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-[#F5F3FF] px-3.5 py-1 text-xs font-bold text-[#7C3AED]"
              >
                <span>👋</span>
                <span>
                  Chào mừng trở lại, {{ currentUser.name }}
                </span>
              </div>

              <div
                v-else
                class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-[#F5F3FF] px-3.5 py-1 text-xs font-bold text-[#7C3AED]"
              >
                <span>✨</span>
                <span>
                  Nền tảng học tập & trắc nghiệm thông minh
                </span>
              </div>

              <!-- Heading -->
              <h1
                class="text-3xl font-extrabold tracking-tight text-[#0F172A] sm:text-4xl lg:text-[40px] leading-[1.18]"
              >
                Học tập thông minh hơn cùng
                <span class="text-[#7C3AED]">
                  QuizFlex
                </span>
              </h1>

              <!-- Description -->
              <p
                class="text-sm font-normal leading-relaxed text-[#64748B] sm:text-base"
              >
                Khám phá hàng nghìn bộ quiz chất lượng, tham gia phòng bài tập
                và thi đấu trực tiếp cùng bạn bè trong thời gian thực.
              </p>

              <!-- Buttons -->
              <div
                class="pt-2 flex flex-wrap items-center gap-3"
              >
                <router-link
                  to="/quizzes"
                  class="inline-flex items-center justify-center rounded-lg bg-[#7C3AED] px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition hover:bg-[#6D28D9] active:scale-[0.98] shadow-sm"
                >
                  Làm quiz ngay
                  <span class="ml-2 text-base">→</span>
                </router-link>

                <router-link
                  to="/join-room"
                  class="inline-flex items-center justify-center rounded-lg border border-[#7C3AED] bg-white px-5 py-2.5 text-xs sm:text-sm font-bold text-[#7C3AED] transition hover:bg-[#F5F3FF] active:scale-[0.98]"
                >
                  Tham gia phòng
                  <span class="ml-2 text-base">♧</span>
                </router-link>
                <!-- NÚT AI TẠO QUIZ -->
   <!-- NÚT AI TẠO QUIZ -->
  <router-link
    to="/dashboard/questions/ai"
    class="inline-flex items-center justify-center rounded-lg bg-[#7C3AED] px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition hover:bg-[#6D28D9] active:scale-[0.98] shadow-sm"
  >
    <Sparkles class="mr-2 w-4 h-4" />
    Tạo quiz AI
  </router-link>

  <!-- NÚT OCR -->
  <router-link
    to="/dashboard/questions/ocr"
    class="inline-flex items-center justify-center rounded-lg bg-[#7C3AED] px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition hover:bg-[#6D28D9] active:scale-[0.98] shadow-sm"
  >
    <Camera class="mr-2 w-4 h-4" />
    Quét OCR
  </router-link>
              </div>
            </div>

            <!-- HERO RIGHT - USER IMAGE -->
            <div
              class="hidden md:flex items-center justify-center relative min-h-[300px]"
            >
              <!-- Soft background decoration -->
              <div
                class="absolute right-0 top-0 h-44 w-44 rounded-full bg-purple-100/60 blur-3xl pointer-events-none"
              ></div>

              <div
                class="absolute left-4 bottom-0 h-28 w-28 rounded-full bg-purple-50 blur-2xl pointer-events-none"
              ></div>

              <!-- Small decorative sparkle -->
              <div
                class="absolute right-5 top-8 z-10 text-[#C4B5FD] text-xl select-none pointer-events-none"
              >
                ✦
              </div>

              <div
                class="absolute left-8 top-20 z-10 text-[#DDD6FE] text-lg select-none pointer-events-none"
              >
                ✦
              </div>

              <!-- User Image -->
              <div
                class="relative z-20 flex items-center justify-center w-full"
              >
                <img
                  :src="userImage"
                  alt="QuizFlex Education"
                  class="w-[290px] sm:w-[320px] lg:w-[350px] h-auto object-contain select-none"
                />
              </div>
            </div>
          </div>
        </section>

        <!-- 2. QUICK ACTIONS -->
        <LandingQuickActions />

        <!-- 3. STATISTICS -->
        <section
          class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-[0_4px_16px_rgba(15,23,42,0.06)]"
        >
          <div
            class="grid grid-cols-2 gap-4 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-100"
          >
            <!-- STAT 1 - QUIZ -->
            <div
              class="flex items-center gap-3 pt-3 sm:pt-0 sm:px-2"
            >
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F3E8FF] text-[#7C3AED]"
              >
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 7h8M8 11h8M8 15h5"
                  />
                </svg>
              </div>

              <div>
                <strong
                  class="block text-xl font-black text-[#0F172A] sm:text-2xl"
                >
                  {{ rawQuizzes.length || 0 }}
                </strong>

                <span
                  class="text-[11px] font-semibold text-[#64748B]"
                >
                  Quiz có sẵn
                </span>
              </div>
            </div>

            <!-- STAT 2 - ROOMS -->
            <div
              class="flex items-center gap-3 pt-3 sm:pt-0 sm:px-2"
            >
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#DBEAFE] text-[#2563EB]"
              >
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                  />
                  <circle
                    cx="9"
                    cy="7"
                    r="4"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"
                  />
                </svg>
              </div>

              <div>
                <strong
                  class="block text-xl font-black text-[#0F172A] sm:text-2xl"
                >
                  {{ roomCountDisplay }}
                </strong>

                <span
                  class="text-[11px] font-semibold text-[#64748B]"
                >
                  Phòng hoạt động
                </span>
              </div>
            </div>

            <!-- STAT 3 - MATCHES -->
            <div
              class="flex items-center gap-3 pt-3 sm:pt-0 sm:px-2"
            >
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#FEF3C7] text-[#F59E0B]"
              >
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 21h8M12 17v4M7 4h10v4a5 5 0 01-10 0V4z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M7 6H4a3 3 0 003 3M17 6h3a3 3 0 01-3 3"
                  />
                </svg>
              </div>

              <div>
                <strong
                  class="block text-xl font-black text-[#0F172A] sm:text-2xl"
                >
                  {{ totalLiveMatches }}
                </strong>

                <span
                  class="text-[11px] font-semibold text-[#64748B]"
                >
                  Lượt thi đấu
                </span>
              </div>
            </div>

            <!-- STAT 4 - ATTEMPTS -->
            <div
              class="flex items-center gap-3 pt-3 sm:pt-0 sm:px-2"
            >
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#D1FAE5] text-[#10B981]"
              >
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 17l6-6 4 4 8-9"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 6h6v6"
                  />
                </svg>
              </div>

              <div>
                <strong
                  class="block text-xl font-black text-[#0F172A] sm:text-2xl"
                >
                  {{ totalAttemptsDisplay }}
                </strong>

                <span
                  class="text-[11px] font-semibold text-[#64748B]"
                >
                  Lượt làm bài
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- 4. QUIZ MỚI NHẤT -->
        <section
          id="quiz-topics"
          class="space-y-5 pt-1"
        >
          <!-- Header -->
          <div
            class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end"
          >
            <div>
              <p
                class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]"
              >
                Khám phá đề thi
              </p>

              <h2
                class="mt-0.5 text-2xl font-black text-[#0F172A] sm:text-3xl"
              >
                Quiz mới nhất
              </h2>

              <p
                class="mt-0.5 text-xs sm:text-sm text-[#64748B]"
              >
                Khám phá các bộ đề vừa được cập nhật từ giáo viên và cộng đồng.
              </p>
            </div>

            <!-- Search -->
            <div
              class="flex items-center gap-3"
            >
              <div
                class="relative w-full sm:w-60"
              >
                <input
                  v-model="searchKeyword"
                  class="w-full rounded-lg border border-slate-200 bg-white pl-8 pr-3 py-1.5 text-xs text-[#0F172A] outline-none focus:border-[#7C3AED] focus:ring-1 focus:ring-purple-200 transition"
                  placeholder="Tìm kiếm đề thi..."
                />
                <span
                  class="absolute left-2.5 top-2 text-xs text-slate-400"
                >
                  🔍
                </span>
              </div>

              <router-link
                to="/quizzes"
                class="text-xs font-bold text-[#7C3AED] hover:underline whitespace-nowrap"
              >
                Xem tất cả →
              </router-link>
            </div>
          </div>

          <!-- Category Filter -->
          <div
            class="flex flex-wrap gap-1.5"
          >
            <button
              v-for="category in categoryTabs"
              :key="category"
              type="button"
              class="rounded-lg border px-3 py-1.5 text-xs font-bold transition active:scale-95"
              :class="
                activeCategory === category
                  ? 'border-[#7C3AED] bg-[#F5F3FF] text-[#7C3AED]'
                  : 'border-slate-200 bg-white text-[#64748B] hover:bg-slate-50 hover:text-[#0F172A]'
              "
              @click="activeCategory = category"
            >
              {{ category }}
            </button>
          </div>

          <!-- Quiz Cards -->
          <div
            v-if="displayedQuizzes.length > 0"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
          >
            <article
              v-for="quiz in displayedQuizzes"
              :key="quiz.id"
              class="group rounded-2xl border border-slate-200 bg-white shadow-[0_4px_16px_rgba(15,23,42,0.04)] overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)] hover:border-purple-300 flex flex-col justify-between"
            >
              <router-link
                :to="`/quizzes/${quiz.id}`"
                class="block h-full flex flex-col justify-between"
              >
                <!-- Thumbnail -->
                <div
                  class="relative h-28 overflow-hidden bg-[#F5F3FF]"
                  :style="{ background: quiz.cover }"
                >
                  <div
                    class="absolute left-2.5 top-2.5 rounded-md bg-white/90 px-2 py-0.5 text-[10px] font-bold text-slate-800 shadow-sm"
                  >
                    {{ quiz.category || quiz.badge || 'Quiz' }}
                  </div>

                  <div
                    class="absolute bottom-2.5 right-2.5 flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow text-sm"
                  >
                    {{ quiz.icon || '📝' }}
                  </div>
                </div>

                <!-- Card Content -->
                <div
                  class="p-4 space-y-2.5 flex-1 flex flex-col justify-between"
                >
                  <div>
                    <h4
                      class="line-clamp-2 min-h-[38px] text-xs font-bold leading-snug text-[#0F172A] group-hover:text-[#7C3AED] transition-colors"
                    >
                      {{ quiz.title }}
                    </h4>

                    <div
                      class="mt-1 flex items-center justify-between text-[11px] text-[#64748B]"
                    >
                      <span
                        class="truncate max-w-[100px]"
                      >
                        {{ quiz.author || 'QuizFlex' }}
                      </span>

                      <span>
                        ⭐
                        <b class="text-slate-800">
                          {{ quiz.rating || '5.0' }}
                        </b>
                      </span>
                    </div>
                  </div>

                  <!-- Bottom -->
                  <div
                    class="flex items-center justify-between pt-2.5 border-t border-slate-100 text-[11px] font-semibold text-[#64748B]"
                  >
                    <span
                      class="rounded px-1.5 py-0.5 text-[10px] font-bold"
                      :class="difficultyClass(quiz.difficulty)"
                    >
                      {{ quiz.difficulty }}
                    </span>

                    <span>
                      {{ quiz.questions }} câu hỏi
                    </span>
                  </div>
                </div>
              </router-link>
            </article>
          </div>

          <!-- Pagination Bar -->
          <AppPagination
            v-if="totalQuizzes > 0"
            :current-page="currentPage"
            :last-page="lastPage"
            :total="totalQuizzes"
            :per-page="perPage"
            item-label="bộ đề"
            @update:current-page="onPageChange"
          />

          <!-- Empty State -->
          <div
            v-else-if="!isLoadingQuizzes"
            class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500 space-y-2"
          >
            <span class="text-3xl block">
              🔍
            </span>

            <p
              class="text-sm font-semibold text-slate-700"
            >
              Không tìm thấy quiz nào phù hợp.
            </p>

            <p
              class="text-xs text-slate-400"
            >
              Hãy thử đổi từ khóa tìm kiếm hoặc chọn danh mục khác.
            </p>
          </div>
        </section>
      </div>

      <!-- RIGHT SIDEBAR -->
      <aside
        class="lg:col-span-4 space-y-6"
      >
        <!-- Continue Learning -->
        <HomeContinueLearning />

        <!-- Notifications -->
        <HomeNotifications />

        <!-- Weekly Ranking -->
        <HomeWeeklyRanking />
      </aside>
    </div>
  </div>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'
import { ArrowRight, DoorOpen, Sparkles, Camera } from 'lucide-vue-next'
import LandingQuickActions from '@/components/Home/LandingQuickActions.vue'
import HomeContinueLearning from '@/components/Home/HomeContinueLearning.vue'
import HomeNotifications from '@/components/Home/HomeNotifications.vue'
import HomeWeeklyRanking from '@/components/Home/HomeWeeklyRanking.vue'
import AppPagination from '@/components/common/AppPagination.vue'

import {
  currentUserStorage,
  normalizeQuizCard,
  quizzesApi,
} from '@/services/api'

import userImage from '@/assets/user.png'

const currentUser = ref(
  currentUserStorage.get()
)

const searchKeyword = ref('')
const activeCategory = ref('Tất cả')
const rawQuizzes = ref([])
const totalQuizzes = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(6)
const isLoadingQuizzes = ref(false)

const categoryTabs = [
  'Tất cả',
  'Toán học',
  'Tiếng Anh',
  'Vật lý',
  'Hóa học',
  'Sinh học',
  'Lịch sử',
  'Địa lý',
  'Tin học',
]

const displayedQuizzes = computed(() => rawQuizzes.value)

const totalAttempts = computed(() => {
  return rawQuizzes.value.reduce(
    (sum, quiz) => sum + Number(quiz.attempts || 0),
    0
  )
})

const formatNumber = (num) => {
  if (!num || num === 0) return '0'
  if (num >= 1000) {
    return `${(num / 1000).toFixed(1).replace('.0', '')}K`
  }
  return String(num)
}

const totalAttemptsDisplay = computed(() => {
  const count = totalAttempts.value
  return count > 0 ? formatNumber(count) : '0'
})

const roomCountDisplay = computed(() => {
  return totalQuizzes.value > 0
    ? String(Math.max(1, Math.min(totalQuizzes.value, 12)))
    : '0'
})

const totalLiveMatches = computed(() => {
  const attempts = totalAttempts.value
  return attempts > 0 ? formatNumber(Math.floor(attempts * 0.4)) : '0'
})

let searchDebounceTimer = null

const loadQuizzes = async (page = 1) => {
  isLoadingQuizzes.value = true
  currentPage.value = page

  try {
    const params = {
      visibility: 'public',
      status: 'published',
      per_page: perPage.value,
      page,
      search: searchKeyword.value.trim() || undefined,
      category: activeCategory.value !== 'Tất cả' ? activeCategory.value : undefined,
    }

    const res = await quizzesApi.fetchPaginated(params)
    rawQuizzes.value = (res.items || []).map(normalizeQuizCard)
    totalQuizzes.value = res.total || 0
    lastPage.value = res.lastPage || 1
    currentPage.value = res.currentPage || page
  } catch {
    rawQuizzes.value = []
    totalQuizzes.value = 0
    lastPage.value = 1
  } finally {
    isLoadingQuizzes.value = false
  }
}

const onPageChange = (page) => {
  loadQuizzes(page)
  const element = document.getElementById('quiz-topics')
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

watch(activeCategory, () => {
  loadQuizzes(1)
})

watch(searchKeyword, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    loadQuizzes(1)
  }, 350)
})

const syncCurrentUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get()
}

const difficultyClass = (difficulty) => ({
  Dễ: 'bg-emerald-50 text-emerald-700',
  Vừa: 'bg-amber-50 text-amber-700',
  Khó: 'bg-red-50 text-red-700',
}[difficulty] || 'bg-purple-50 text-purple-700')

onMounted(() => {
  loadQuizzes(1)
  window.addEventListener('quizflex-user-updated', syncCurrentUser)
  window.addEventListener('storage', syncCurrentUser)
})

onBeforeUnmount(() => {
  clearTimeout(searchDebounceTimer)
  window.removeEventListener('quizflex-user-updated', syncCurrentUser)
  window.removeEventListener('storage', syncCurrentUser)
})
</script>
