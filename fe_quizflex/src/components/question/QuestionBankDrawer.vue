<template>
  <Teleport to="body">
    <Transition name="studio-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[90] overflow-hidden bg-slate-900/70 backdrop-blur-md p-3 sm:p-6 lg:p-8"
        @click.self="closeDrawer"
      >
        <!-- MAIN PICKER STUDIO CONTAINER -->
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
                title="Mở trình soạn thảo câu hỏi mới"
                @click="isStudioOpen = true"
              >
                <Plus :size="15" :stroke-width="2.5" />
                <span>+ Tạo câu hỏi mới</span>
              </button>

              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100 cursor-pointer"
                @click="closeDrawer"
              >
                Hủy / Đóng
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-[#7C3AED] px-5 py-2 text-xs font-bold text-white shadow-lg shadow-[#7C3AED]/25 transition hover:bg-[#6D28D9] disabled:opacity-50 cursor-pointer"
                @click="confirmSelection"
              >
                <Check :size="16" :stroke-width="2.5" />
                <span>Xác nhận chọn {{ selectedIds.length }} câu hỏi</span>
              </button>

              <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer"
                @click="closeDrawer"
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
                    :class="activeTab === 'my_questions'
                      ? 'border-slate-300 bg-slate-100 text-slate-900 shadow-sm'
                      : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'"
                    @click="switchTab('my_questions')"
                  >
                    <div class="flex items-center gap-2.5">
                      <span class="text-base">📂</span>
                      <span>Kho câu hỏi của tôi</span>
                    </div>
                    <span class="rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700">Cá nhân</span>
                  </button>

                  <button
                    type="button"
                    class="flex items-center justify-between rounded-xl border p-3.5 text-xs font-bold transition cursor-pointer"
                    :class="activeTab === 'public_bank'
                      ? 'border-slate-300 bg-slate-100 text-slate-900 shadow-sm'
                      : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'"
                    @click="switchTab('public_bank')"
                  >
                    <div class="flex items-center gap-2.5">
                      <span class="text-base">🌐</span>
                      <span>Ngân hàng cộng đồng</span>
                    </div>
                    <span class="rounded-md bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-600">Dùng chung</span>
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
                    class="text-[11px] font-bold text-[#7C3AED] hover:underline cursor-pointer"
                    @click="resetFilters"
                  >
                    Đặt lại
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
                      @change="loadQuestions"
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
                      @change="loadQuestions"
                    >
                      <option value="">Tất cả Bộ môn</option>
                      <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                        {{ subject.name }}
                      </option>
                    </select>
                  </label>

                  <!-- Độ khó -->
                  <label class="grid gap-1.5 text-xs font-semibold text-slate-700">
                    Độ khó
                    <select
                      v-model="filters.difficulty"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED]"
                      @change="loadQuestions"
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
                    {{ selectedIds.length }} câu
                  </span>
                </div>

                <button
                  type="button"
                  class="w-full rounded-xl bg-[#7C3AED] py-2.5 text-xs font-bold text-white shadow-md shadow-[#7C3AED]/20 transition hover:bg-[#6D28D9] cursor-pointer"
                  @click="confirmSelection"
                >
                  ✓ Thêm {{ selectedIds.length }} câu vào bài Quiz
                </button>
              </div>

            </div>

            <!-- RIGHT COLUMN: MAIN QUESTION CARDS LIST (SPACIOUS) -->
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
                    @keyup.enter="loadQuestions"
                  />
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500 font-medium shrink-0">
                  <span>Tìm thấy: <b class="text-slate-900 font-bold">{{ questions.length }}</b> câu hỏi</span>
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
                    {{ activeTab === 'my_questions' ? 'Kho cá nhân của bạn chưa có câu hỏi thỏa điều kiện. Thử tạo câu hỏi mới hoặc xem Ngân hàng dùng chung.' : 'Ngân hàng chung chưa có câu hỏi thỏa điều kiện tìm kiếm.' }}
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
                    :class="
                      selectedIds.includes(q.id)
                        ? 'border-[#7C3AED] bg-[#F5F3FF]/40 shadow-md ring-2 ring-[#7C3AED]/20'
                        : 'border-slate-200 hover:border-slate-300 hover:shadow-sm'
                    "
                    @click="toggleSelect(q.id)"
                  >
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex items-center gap-3">
                        <!-- Big Checkbox -->
                        <input
                          type="checkbox"
                          :checked="selectedIds.includes(q.id)"
                          class="h-5 w-5 rounded-md accent-[#7C3AED] cursor-pointer"
                          @click.stop="toggleSelect(q.id)"
                        />

                        <!-- Badges -->
                        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                          <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-600">#{{ q.id }}</span>

                          <!-- Visibility Badge -->
                          <span
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
                        :class="selectedIds.includes(q.id) ? 'text-[#7C3AED]' : 'text-slate-400'"
                      >
                        {{ selectedIds.includes(q.id) ? '✓ Đã chọn' : '+ Chọn' }}
                      </span>
                    </div>

                    <!-- Question Content -->
                    <h3 class="text-sm font-bold leading-relaxed text-slate-900 break-words">
                      <MathText :content="q.content || q.text || ''" compact />
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
                          {{ ans.key }}
                        </span>
                        <MathText :content="ans.content || ans.text || ''" compact class="min-w-0 flex-1 truncate" />
                        <span v-if="ans.is_correct" class="text-emerald-600 text-xs shrink-0 font-bold">✓</span>
                      </div>
                    </div>
                  </article>
                </template>
              </div>

            </div>

          </div>

        </div>
      </div>
    </Transition>

    <!-- FULLSCREEN STUDIO OVERLAY -->
    <CreateQuestionStudioModal
      :is-open="isStudioOpen"
      @close="isStudioOpen = false"
      @created="onQuestionCreatedFromStudio"
    />
  </Teleport>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Check, Globe, Lock, Plus, Search, X } from 'lucide-vue-next'
import CreateQuestionStudioModal from '@/components/question/CreateQuestionStudioModal.vue'
import MathText from '@/components/MathText.vue'
import { myQuestionsApi, questionsBankApi, taxonomyApi } from '@/services/api'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  initialSelectedIds: { type: Array, default: () => [] }
})

const emit = defineEmits(['close', 'confirm'])

const activeTab = ref('my_questions')
const isLoading = ref(false)
const isStudioOpen = ref(false)
const questions = ref([])
const selectedIds = ref([])
const taxonomyLevels = ref([])
const allSubjects = ref([])

const filters = reactive({
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  difficulty: '',
  search: ''
})

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.education_level_id ||
    filters.grade_id ||
    filters.subject_id ||
    filters.difficulty ||
    filters.search
  )
})

const resetFilters = () => {
  filters.education_level_id = ''
  filters.grade_id = ''
  filters.subject_id = ''
  filters.difficulty = ''
  filters.search = ''
  loadQuestions()
}

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      selectedIds.value = [...props.initialSelectedIds]
      loadQuestions()
    }
  }
)

const switchTab = (tab) => {
  activeTab.value = tab
  loadQuestions()
}

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const confirmSelection = () => {
  emit('confirm', selectedIds.value)
  emit('close')
}

const closeDrawer = () => {
  emit('close')
}

const availableGrades = computed(() => {
  if (!filters.education_level_id) {
    return taxonomyLevels.value.flatMap((l) => l.grades || [])
  }
  const level = taxonomyLevels.value.find((l) => l.id === Number(filters.education_level_id))
  return level ? level.grades || [] : []
})

const availableSubjects = computed(() => {
  if (!filters.grade_id) return allSubjects.value
  const grade = availableGrades.value.find((g) => g.id === Number(filters.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const onLevelChange = () => {
  filters.grade_id = ''
  loadQuestions()
}

const loadQuestions = async () => {
  isLoading.value = true
  try {
    const params = {
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      difficulty: filters.difficulty || undefined,
      search: filters.search || undefined,
      per_page: 50
    }

    let res
    if (activeTab.value === 'my_questions') {
      res = await myQuestionsApi.fetchBank(params)
    } else {
      res = await questionsBankApi.fetchBank(params)
    }

    const list = Array.isArray(res) ? res : res?.items || res?.data || []
    questions.value = list
  } catch (err) {
    console.error('Không tải được câu hỏi:', err)
  } finally {
    isLoading.value = false
  }
}

const onQuestionCreatedFromStudio = (createdQuestion) => {
  activeTab.value = 'my_questions'
  loadQuestions()
  if (createdQuestion && createdQuestion.id) {
    if (!selectedIds.value.includes(createdQuestion.id)) {
      selectedIds.value.push(createdQuestion.id)
    }
  }
}

onMounted(async () => {
  try {
    const data = await taxonomyApi.tree()
    const payload = data?.education_levels ? data : data?.data ?? data
    if (payload) {
      taxonomyLevels.value = payload.education_levels || payload.educationLevels || []
      allSubjects.value = payload.subjects || []
    }
  } catch (e) {
    console.error('Không tải được taxonomy:', e)
  }
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
