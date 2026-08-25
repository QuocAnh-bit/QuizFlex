<template>
  <Teleport to="body">
    <Transition name="studio-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[90] overflow-hidden bg-slate-900/70 backdrop-blur-md p-3 sm:p-5 lg:p-6"
        @click.self="handleCancel"
      >
        <!-- MAIN PICKER STUDIO CONTAINER (KHIEMDEV DESIGN) -->
        <div class="mx-auto flex h-full max-w-[1700px] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl text-slate-900">
          
          <!-- 1. TOP NAVBAR -->
          <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="grid h-10 w-10 place-items-center rounded-2xl bg-[#F5F3FF] text-[#7C3AED] font-black text-lg">
                📚
              </div>
              <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                  <span>Question Picker Studio</span>
                  <span class="inline-block h-2 w-2 rounded-full bg-[#7C3AED]"></span>
                </h2>
                <p class="text-xs text-slate-500">
                  Tra cứu, lọc và chọn câu hỏi từ Kho cá nhân hoặc Ngân hàng dùng chung chèn trực tiếp vào Bài Quiz.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <!-- Button to open Create Question Studio Modal -->
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-800 hover:bg-slate-100 transition cursor-pointer"
                title="Mở trình soạn thảo tạo câu hỏi mới"
                @click="isStudioOpen = true"
              >
                <Plus :size="15" :stroke-width="2.5" />
                <span>+ Tạo câu hỏi mới</span>
              </button>

              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100 cursor-pointer"
                @click="handleCancel"
              >
                Hủy / Đóng
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-[#7C3AED] px-5 py-2 text-xs font-bold text-white shadow-lg shadow-[#7C3AED]/25 transition hover:bg-[#6D28D9] disabled:opacity-50 cursor-pointer"
                @click="handleConfirm"
              >
                <Check :size="16" :stroke-width="2.5" />
                <span>Xác nhận chọn {{ localSelectedIds.length }} câu hỏi</span>
              </button>

              <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer"
                @click="handleCancel"
              >
                <X :size="20" :stroke-width="2.5" />
              </button>
            </div>
          </div>

          <!-- 2. STUDIO CONTENT GRID (2 COLUMNS) -->
          <div class="grid flex-1 overflow-hidden gap-6 p-6 xl:grid-cols-[340px_minmax(0,1fr)]">
            
            <!-- LEFT COLUMN: SIDEBAR FILTERS & SOURCE TABS -->
            <div class="flex flex-col overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-6">
              
              <!-- Tab Nguồn dữ liệu -->
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Nguồn câu hỏi</label>
                <div class="grid gap-2">
                  <button
                    type="button"
                    class="flex items-center justify-between rounded-xl border p-3.5 text-xs font-bold transition cursor-pointer"
                    :class="activeTab === 'my_bank'
                      ? 'border-[#7C3AED] bg-[#F5F3FF] text-[#7C3AED] shadow-sm'
                      : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'"
                    @click="switchTab('my_bank')"
                  >
                    <div class="flex items-center gap-2.5">
                      <User :size="16" class="text-[#7C3AED]" />
                      <span>Kho câu hỏi của tôi</span>
                    </div>
                    <span
                      class="rounded-md px-2 py-0.5 text-[10px] font-bold"
                      :class="activeTab === 'my_bank' ? 'bg-[#7C3AED] text-white' : 'bg-slate-200 text-slate-700'"
                    >
                      Kho cá nhân ({{ myBankSelectedCount }})
                    </span>
                  </button>

                  <button
                    type="button"
                    class="flex items-center justify-between rounded-xl border p-3.5 text-xs font-bold transition cursor-pointer"
                    :class="activeTab === 'public_bank'
                      ? 'border-[#7C3AED] bg-[#F5F3FF] text-[#7C3AED] shadow-sm'
                      : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'"
                    @click="switchTab('public_bank')"
                  >
                    <div class="flex items-center gap-2.5">
                      <Globe :size="16" class="text-sky-500" />
                      <span>Ngân hàng dùng chung</span>
                    </div>
                    <span
                      class="rounded-md px-2 py-0.5 text-[10px] font-bold"
                      :class="activeTab === 'public_bank' ? 'bg-sky-500 text-white' : 'bg-sky-500/10 text-sky-600'"
                    >
                      Cộng đồng ({{ publicBankSelectedCount }})
                    </span>
                  </button>
                </div>
              </div>

              <!-- Bộ lọc Phân loại Taxonomy -->
              <div class="space-y-4 border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Bộ lọc phân loại</label>
                  <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="text-[11px] font-bold text-[#7C3AED] hover:underline cursor-pointer flex items-center gap-1"
                    @click="resetFilters"
                  >
                    <RotateCcw :size="11" />
                    <span>Đặt lại</span>
                  </button>
                </div>

                <div class="space-y-3">
                  <!-- Cấp học -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Cấp học
                    <select
                      v-model="filters.education_level_id"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="onLevelChange"
                    >
                      <option value="">Tất cả Cấp học</option>
                      <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">
                        {{ level.name }}
                      </option>
                    </select>
                  </label>

                  <!-- Khối lớp -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Khối lớp
                    <select
                      v-model="filters.grade_id"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="onGradeSubjectChange"
                    >
                      <option value="">Tất cả Khối lớp</option>
                      <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">
                        {{ grade.name }}
                      </option>
                    </select>
                  </label>

                  <!-- Bộ môn -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Bộ môn
                    <select
                      v-model="filters.subject_id"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="onGradeSubjectChange"
                    >
                      <option value="">Tất cả Bộ môn</option>
                      <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                        {{ subject.name }}
                      </option>
                    </select>
                  </label>

                  <!-- Chủ đề -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Chủ đề
                    <select
                      v-model="filters.topic_name"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="applyFilter"
                    >
                      <option value="">Tất cả Chủ đề</option>
                      <option v-for="top in topicsList" :key="top.topic_name" :value="top.topic_name">
                        {{ top.topic_name }}
                      </option>
                    </select>
                  </label>

                  <!-- Độ khó -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Độ khó
                    <select
                      v-model="filters.difficulty"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="applyFilter"
                    >
                      <option value="">Tất cả Độ khó</option>
                      <option value="easy">Dễ (Nhận biết)</option>
                      <option value="medium">Vừa (Thông hiểu)</option>
                      <option value="hard">Khó (Vận dụng)</option>
                    </select>
                  </label>
                </div>
              </div>

              <!-- Thẻ đếm Tổng kết chọn câu hỏi -->
              <div class="mt-auto rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-semibold text-slate-600">Đã chọn cho bài Quiz:</span>
                  <span class="rounded-full bg-[#7C3AED] px-2.5 py-0.5 text-xs font-black text-white">
                    {{ localSelectedIds.length }} câu
                  </span>
                </div>

                <div class="text-[11px] text-slate-500 flex justify-between font-medium">
                  <span>Kho của tôi: <strong>{{ myBankSelectedCount }}</strong></span>
                  <span>Ngân hàng: <strong>{{ publicBankSelectedCount }}</strong></span>
                </div>

                <button
                  type="button"
                  class="w-full rounded-xl bg-[#7C3AED] py-2.5 text-xs font-bold text-white shadow-md shadow-[#7C3AED]/20 transition hover:bg-[#6D28D9] cursor-pointer"
                  @click="handleConfirm"
                >
                  ✓ Xác nhận {{ localSelectedIds.length }} câu vào Quiz
                </button>
              </div>

            </div>

            <!-- RIGHT COLUMN: MAIN QUESTION CARDS LIST (SPACIOUS KHIEMDEV DESIGN) -->
            <div class="flex flex-col overflow-hidden space-y-4">
              
              <!-- SEARCH BAR -->
              <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                <div class="relative flex-1">
                  <Search
                    :size="16"
                    class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                  />
                  <input
                    v-model="filters.search"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-4 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                    placeholder="Nhập từ khóa tìm kiếm nội dung câu hỏi..."
                    @keyup.enter="applyFilter"
                  />
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500 font-medium shrink-0">
                  <span>Tìm thấy: <b class="text-slate-900 font-bold">{{ pagination.total }}</b> câu hỏi</span>
                  
                  <button
                    v-if="selectableQuestions.length > 0"
                    type="button"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-[#7C3AED] bg-purple-50/60 hover:bg-purple-100 transition cursor-pointer"
                    @click="toggleSelectAllCurrentPage"
                  >
                    {{ isCurrentPageAllSelected ? 'Bỏ chọn trang này' : 'Chọn tất cả trang này' }}
                  </button>

                  <button
                    type="button"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                    @click="loadQuestions"
                  >
                    Làm mới
                  </button>
                </div>
              </div>

              <!-- QUESTION CARDS LIST CONTAINER (SCROLLABLE) -->
              <div class="flex-1 overflow-y-auto space-y-4 pr-1">
                <div v-if="isLoading" class="py-20 text-center text-xs text-slate-400 space-y-3">
                  <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-[#7C3AED] border-t-transparent"></div>
                  <p class="font-medium text-slate-500">Đang tải danh sách câu hỏi...</p>
                </div>

                <div v-else-if="questions.length === 0" class="py-20 text-center border border-dashed rounded-3xl p-8 bg-white space-y-3">
                  <div class="mx-auto h-12 w-12 grid place-items-center rounded-2xl bg-slate-100 text-slate-400 text-xl font-bold">
                    🔍
                  </div>
                  <h4 class="text-base font-bold text-slate-900">Không tìm thấy câu hỏi nào phù hợp</h4>
                  <p class="text-xs text-slate-500 max-w-md mx-auto">
                    {{ activeTab === 'my_bank' ? 'Kho cá nhân của bạn chưa có câu hỏi thỏa điều kiện. Thử tạo câu hỏi mới hoặc xem Ngân hàng dùng chung.' : 'Ngân hàng chung chưa có câu hỏi thỏa điều kiện tìm kiếm.' }}
                  </p>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white shadow-md shadow-[#7C3AED]/20 transition hover:bg-[#6D28D9] cursor-pointer mt-2"
                    @click="isStudioOpen = true"
                  >
                    <span>+ Tạo câu hỏi mới</span>
                  </button>
                </div>

                <template v-else>
                  <article
                    v-for="q in questions"
                    :key="q.id"
                    class="rounded-2xl border bg-white p-5 transition-all duration-150 cursor-pointer space-y-3"
                    :class="[
                      isQuestionDisabled(q.id)
                        ? 'opacity-60 cursor-not-allowed border-slate-200 bg-slate-50/60'
                        : (localSelectedIds.includes(q.id)
                            ? 'border-[#7C3AED] bg-[#F5F3FF]/40 shadow-md ring-2 ring-[#7C3AED]/20'
                            : 'border-slate-200 hover:border-slate-300 hover:shadow-sm')
                    ]"
                    @click="toggleSelectQuestion(q)"
                  >
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex items-center gap-3">
                        <!-- Big Checkbox -->
                        <input
                          type="checkbox"
                          :checked="isQuestionDisabled(q.id) || localSelectedIds.includes(q.id)"
                          :disabled="isQuestionDisabled(q.id)"
                          class="h-5 w-5 rounded-md accent-[#7C3AED] cursor-pointer"
                          @click.stop="toggleSelectQuestion(q)"
                        />

                        <!-- Badges -->
                        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                          <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-600">#{{ q.id }}</span>

                          <!-- Already in Quiz Badge -->
                          <span
                            v-if="isQuestionDisabled(q.id)"
                            class="inline-flex items-center gap-1 rounded-lg bg-slate-200 px-2.5 py-1 text-slate-700 font-bold text-[11px]"
                          >
                            <Check :size="12" />
                            <span>Đã có trong đề</span>
                          </span>

                          <!-- Visibility Badge -->
                          <span
                            v-else
                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1"
                            :class="q.is_public ? 'bg-purple-50 text-[#7C3AED]' : 'bg-amber-50 text-amber-700'"
                          >
                            <component :is="q.is_public ? Globe : Lock" :size="12" />
                            <span>{{ q.is_public ? (q.status === 'pending' ? '⏳ Chờ duyệt' : 'Công khai') : 'Riêng tư' }}</span>
                          </span>

                          <!-- Difficulty Badge -->
                          <span
                            class="rounded-lg px-2.5 py-1"
                            :class="{
                              'bg-emerald-50 text-emerald-700': q.difficulty === 'easy',
                              'bg-amber-50 text-amber-700': q.difficulty === 'medium',
                              'bg-rose-50 text-rose-700': q.difficulty === 'hard'
                            }"
                          >
                            {{ q.difficulty === 'easy' ? 'Dễ (Nhận biết)' : q.difficulty === 'hard' ? 'Khó (Vận dụng)' : 'Vừa (Thông hiểu)' }}
                          </span>

                          <span v-if="q.subject_name" class="rounded-lg bg-indigo-50 px-2.5 py-1 text-indigo-700">
                            {{ q.subject_name }}
                          </span>
                          <span v-if="q.topic_name" class="rounded-lg bg-purple-50 px-2.5 py-1 text-purple-700">
                            #{{ q.topic_name }}
                          </span>
                        </div>
                      </div>

                      <span
                        class="text-xs font-bold transition"
                        :class="localSelectedIds.includes(q.id) ? 'text-[#7C3AED]' : 'text-slate-400'"
                      >
                        {{ isQuestionDisabled(q.id) ? 'Đã có' : (localSelectedIds.includes(q.id) ? '✓ Đã chọn' : '+ Chọn') }}
                      </span>
                    </div>

                    <!-- Question Content -->
                    <h3 class="text-sm font-bold leading-relaxed text-slate-900 break-words">
                      {{ q.content || q.text }}
                    </h3>

                    <!-- Answers 2-Column Grid (Spacious) -->
                    <div v-if="q.answers && q.answers.length" class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-xs">
                      <div
                        v-for="ans in q.answers"
                        :key="ans.id || ans.key"
                        class="flex items-center gap-2.5 rounded-xl border p-2.5 font-medium transition"
                        :class="ans.is_correct ? 'border-emerald-200 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-slate-50 text-slate-600'"
                      >
                        <span
                          class="grid h-6 w-6 place-items-center rounded-lg text-xs font-black shrink-0"
                          :class="ans.is_correct ? 'bg-emerald-500 text-white' : 'bg-white text-slate-700 border border-slate-200'"
                        >
                          {{ ans.key || ans.answer_key }}
                        </span>
                        <span class="truncate min-w-0 flex-1">{{ ans.content || ans.text }}</span>
                        <span v-if="ans.is_correct" class="text-emerald-600 text-xs shrink-0 font-bold">✓</span>
                      </div>
                    </div>
                  </article>
                </template>

                <!-- PAGINATION CONTROLS -->
                <div
                  v-if="pagination.last_page > 1"
                  class="flex items-center justify-between border-t border-slate-200 pt-4 text-xs font-bold"
                >
                  <button
                    type="button"
                    class="btn-ghost inline-flex items-center gap-1 border border-slate-200 !py-1.5 !px-3 disabled:opacity-40 cursor-pointer"
                    :disabled="pagination.current_page <= 1"
                    @click="changePage(pagination.current_page - 1)"
                  >
                    <ChevronLeft :size="14" />
                    <span>Trang trước</span>
                  </button>

                  <span class="text-slate-700">
                    Trang {{ pagination.current_page }} / {{ pagination.last_page }}
                  </span>

                  <button
                    type="button"
                    class="btn-ghost inline-flex items-center gap-1 border border-slate-200 !py-1.5 !px-3 disabled:opacity-40 cursor-pointer"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="changePage(pagination.current_page + 1)"
                  >
                    <span>Trang sau</span>
                    <ChevronRight :size="14" />
                  </button>
                </div>
              </div>

            </div>

          </div>

        </div>
      </div>
    </Transition>

    <!-- FULLSCREEN CREATE QUESTION STUDIO OVERLAY -->
    <CreateQuestionStudioModal
      :is-open="isStudioOpen"
      @close="isStudioOpen = false"
      @created="onQuestionCreatedFromStudio"
    />
  </Teleport>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import {
  X,
  User,
  Globe,
  Lock,
  Search,
  RotateCcw,
  Check,
  Plus,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import CreateQuestionStudioModal from '@/components/question/CreateQuestionStudioModal.vue'
import { myQuestionsApi, questionsBankApi, taxonomyApi } from '@/services/api'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
  initialSelectedIds: {
    type: Array,
    default: () => [],
  },
  initialFilters: {
    type: Object,
    default: () => ({}),
  },
  disabledIds: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:modelValue', 'close', 'confirm'])

/* =========================================================
   STATE
========================================================= */
const activeTab = ref('my_bank') // 'my_bank' | 'public_bank'
const isStudioOpen = ref(false)
const localSelectedIds = ref([])
const selectedQuestionCache = ref(new Map())

const questions = ref([])
const isLoading = ref(false)

const taxonomyLevels = ref([])
const allSubjects = ref([])
const topicsList = ref([])

const filters = reactive({
  search: '',
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
  difficulty: '',
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

/* =========================================================
   COMPUTED COUNTS & STATS
========================================================= */
const myBankSelectedCount = computed(() => {
  return localSelectedIds.value.filter(id => {
    const item = selectedQuestionCache.value.get(id)
    return item?.source === 'my_bank'
  }).length
})

const publicBankSelectedCount = computed(() => {
  return localSelectedIds.value.filter(id => {
    const item = selectedQuestionCache.value.get(id)
    return item?.source === 'public_bank'
  }).length
})

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.search ||
    filters.education_level_id ||
    filters.grade_id ||
    filters.subject_id ||
    filters.topic_name ||
    filters.difficulty
  )
})

const availableGrades = computed(() => {
  if (!filters.education_level_id) {
    return taxonomyLevels.value.flatMap(l => l.grades || [])
  }
  const level = taxonomyLevels.value.find(l => l.id === Number(filters.education_level_id))
  return level ? level.grades || [] : []
})

const availableSubjects = computed(() => {
  if (!filters.grade_id) {
    return allSubjects.value
  }
  const grade = availableGrades.value.find(g => g.id === Number(filters.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const isQuestionDisabled = (questionId) => {
  return Array.isArray(props.disabledIds) && props.disabledIds.includes(questionId)
}

const selectableQuestions = computed(() => {
  return questions.value.filter(q => !isQuestionDisabled(q.id))
})

const isCurrentPageAllSelected = computed(() => {
  if (selectableQuestions.value.length === 0) return false
  return selectableQuestions.value.every(q => localSelectedIds.value.includes(q.id))
})

/* =========================================================
   WATCHERS & MOUNT
========================================================= */
watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      const initIds = props.modelValue?.length ? props.modelValue : (props.initialSelectedIds || [])
      localSelectedIds.value = [...initIds]
      applyInitialFilters()
      fetchTaxonomy()
      fetchTopicsList()
      loadQuestions()
    }
  },
  { immediate: true }
)

/* =========================================================
   DATA FETCHING
========================================================= */
const fetchTaxonomy = async () => {
  if (taxonomyLevels.value.length > 0) return
  try {
    const data = await taxonomyApi.tree()
    const payload = data?.education_levels ? data : (data?.data ?? data)
    if (payload) {
      taxonomyLevels.value = payload.education_levels || payload.educationLevels || []
      allSubjects.value = payload.subjects || []
    }
  } catch (e) {
    console.error('Không tải được taxonomy:', e)
  }
}

const fetchTopicsList = async () => {
  try {
    const params = {
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
    }
    const data = await questionsBankApi.fetchTopics(params)
    topicsList.value = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : [])
  } catch (e) {
    console.error('Không tải được danh sách Chủ đề:', e)
  }
}

const loadQuestions = async () => {
  isLoading.value = true
  try {
    const params = {
      search: filters.search || undefined,
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      topic_name: filters.topic_name || undefined,
      difficulty: filters.difficulty || undefined,
      page: pagination.current_page,
      per_page: pagination.per_page,
    }

    const apiCall = activeTab.value === 'my_bank'
      ? myQuestionsApi.fetchBank(params)
      : questionsBankApi.fetchBank(params)

    const res = await apiCall
    if (res && Array.isArray(res.items)) {
      questions.value = res.items
      pagination.total = res.total ?? res.items.length
      pagination.current_page = res.currentPage ?? 1
      pagination.last_page = res.lastPage ?? 1
      pagination.per_page = res.perPage ?? pagination.per_page
    } else if (Array.isArray(res)) {
      questions.value = res
      pagination.total = res.length
      pagination.current_page = 1
      pagination.last_page = 1
    } else {
      questions.value = []
      pagination.total = 0
      pagination.current_page = 1
      pagination.last_page = 1
    }

    // Cache current tab question items
    questions.value.forEach(q => {
      if (localSelectedIds.value.includes(q.id) && !selectedQuestionCache.value.has(q.id)) {
        selectedQuestionCache.value.set(q.id, {
          ...q,
          source: activeTab.value,
        })
      }
    })
  } catch (e) {
    console.error('Không tải được danh sách câu hỏi:', e)
    questions.value = []
  } finally {
    isLoading.value = false
  }
}

/* =========================================================
   TAB SWITCHING & FILTERS
========================================================= */
const switchTab = (tab) => {
  if (activeTab.value === tab) return
  activeTab.value = tab
  pagination.current_page = 1
  loadQuestions()
}

const onLevelChange = () => {
  filters.grade_id = ''
  onGradeSubjectChange()
}

const onGradeSubjectChange = () => {
  fetchTopicsList()
  applyFilter()
}

const applyFilter = () => {
  pagination.current_page = 1
  loadQuestions()
}

const resetFilters = () => {
  filters.search = ''
  filters.education_level_id = ''
  filters.grade_id = ''
  filters.subject_id = ''
  filters.topic_name = ''
  filters.difficulty = ''
  pagination.current_page = 1
  fetchTopicsList()
  loadQuestions()
}

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  pagination.current_page = page
  loadQuestions()
}

/* =========================================================
   SELECTION HANDLING
========================================================= */
const toggleSelectQuestion = (question) => {
  if (isQuestionDisabled(question.id)) return
  const index = localSelectedIds.value.indexOf(question.id)
  if (index > -1) {
    localSelectedIds.value.splice(index, 1)
  } else {
    localSelectedIds.value.push(question.id)
    selectedQuestionCache.value.set(question.id, {
      ...question,
      source: activeTab.value,
    })
  }
}

const toggleSelectAllCurrentPage = () => {
  const selectable = selectableQuestions.value
  if (selectable.length === 0) return
  const selectableIds = selectable.map(q => q.id)
  if (isCurrentPageAllSelected.value) {
    localSelectedIds.value = localSelectedIds.value.filter(id => !selectableIds.includes(id))
  } else {
    selectable.forEach(q => {
      if (!localSelectedIds.value.includes(q.id)) {
        localSelectedIds.value.push(q.id)
        selectedQuestionCache.value.set(q.id, {
          ...q,
          source: activeTab.value,
        })
      }
    })
  }
}

/* =========================================================
   STUDIO CREATED HANDLER
========================================================= */
const onQuestionCreatedFromStudio = (createdQuestion) => {
  activeTab.value = 'my_bank'
  loadQuestions()
  if (createdQuestion && createdQuestion.id) {
    if (!localSelectedIds.value.includes(createdQuestion.id)) {
      localSelectedIds.value.push(createdQuestion.id)
      selectedQuestionCache.value.set(createdQuestion.id, {
        ...createdQuestion,
        source: 'my_bank',
      })
    }
  }
}

/* =========================================================
   ACTIONS (CANCEL / CONFIRM)
========================================================= */
const handleCancel = () => {
  emit('close')
}

const handleConfirm = () => {
  const selectedList = localSelectedIds.value.map(id => {
    const cached = selectedQuestionCache.value.get(id)
    return cached || { id, content: `Câu hỏi #${id}`, difficulty: 'medium', source: 'public_bank' }
  })

  emit('update:modelValue', [...localSelectedIds.value])
  emit('confirm', {
    ids: [...localSelectedIds.value],
    questions: selectedList,
    myBankCount: myBankSelectedCount.value,
    publicBankCount: publicBankSelectedCount.value,
  })
  emit('close')
}

const applyInitialFilters = () => {
  if (props.initialFilters) {
    if (props.initialFilters.education_level_id) filters.education_level_id = props.initialFilters.education_level_id
    if (props.initialFilters.grade_id) filters.grade_id = props.initialFilters.grade_id
    if (props.initialFilters.subject_id) filters.subject_id = props.initialFilters.subject_id
    if (props.initialFilters.topic_name) filters.topic_name = props.initialFilters.topic_name
    if (props.initialFilters.difficulty) filters.difficulty = props.initialFilters.difficulty
  }
}

onMounted(() => {
  fetchTaxonomy()
  fetchTopicsList()
})
</script>

<style scoped>
.studio-fade-enter-active,
.studio-fade-leave-active {
  transition: opacity 0.25s ease;
}
.studio-fade-enter-from,
.studio-fade-leave-to {
  opacity: 0;
}
</style>
