<template>
  <section id="question-list-top" class="grid gap-6 py-8">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Question Repository & Smart Builder</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Ngân hàng câu hỏi độc lập</h1>
          <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">Tra cứu theo Chủ đề, chọn thủ công hoặc khởi tạo bộ đề thi tự động phân bổ theo mức độ (Dễ / Vừa / Khó) chuẩn mực cho Giáo viên & Học sinh.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <router-link to="/question-bank/create-question" class="btn-ghost flex items-center gap-2 border border-[var(--border)] hover:bg-[var(--chip-active)]">
            <span>+ Tạo câu hỏi</span>
          </router-link>
          <button class="btn-primary flex items-center gap-2" type="button" @click="goToCreateExam('auto')">
            <span>Tạo bộ đề tự động</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Advanced Taxonomy Filter Bar -->
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl grid gap-4">
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <!-- 1. Cấp học -->
        <select v-model="filters.education_level_id" class="field" @change="onLevelChange">
          <option value="">Tất cả Cấp học</option>
          <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
        </select>

        <!-- 2. Lớp học -->
        <select v-model="filters.grade_id" class="field" @change="onGradeSubjectChange">
          <option value="">Tất cả Khối lớp</option>
          <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
        </select>

        <!-- 3. Bộ môn -->
        <select v-model="filters.subject_id" class="field" @change="onGradeSubjectChange">
          <option value="">Tất cả Bộ môn</option>
          <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
        </select>

        <!-- 4. Độ khó -->
        <select v-model="filters.difficulty" class="field" @change="onFilterSubmit">
          <option value="">Tất cả độ khó</option>
          <option value="easy">Dễ (Nhận biết)</option>
          <option value="medium">Vừa (Thông hiểu)</option>
          <option value="hard">Khó (Vận dụng)</option>
        </select>
      </div>

      <div class="grid gap-3 xl:grid-cols-[1fr_260px_auto_auto]">
        <input v-model="filters.search" class="field" placeholder="Tìm từ khóa nội dung câu hỏi..." @keyup.enter="onFilterSubmit" />
        
        <!-- Dropdown Chủ đề động từ Ngân hàng -->
        <select v-model="filters.topic_name" class="field" @change="onFilterSubmit">
          <option value="">Tất cả Chủ đề</option>
          <option v-for="top in topicsList" :key="top.topic_name" :value="top.topic_name">
            {{ top.topic_name }} ({{ top.total_questions }} câu)
          </option>
        </select>

        <button class="btn-primary" type="button" @click="onFilterSubmit">Tìm kiếm & Lọc</button>
        <button v-if="hasActiveFilters" class="btn-ghost text-xs text-rose-500 font-bold" type="button" @click="resetFilters">Đặt lại</button>
      </div>
    </article>

    <!-- Selection Bar & Counter -->
    <div v-if="questions.length > 0" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 shadow-sm">
      <div class="flex flex-wrap items-center gap-3">
        <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-[var(--text)]">
          <input type="checkbox" :checked="isAllSelected" class="h-4 w-4 rounded accent-[var(--primary)]" @change="toggleSelectAll" />
          <span>Chọn tất cả {{ questions.length }} câu trên trang</span>
        </label>

        <span class="rounded-full bg-[var(--primary)]/15 px-3 py-1 text-xs font-black text-[var(--primary)]">
          Đã chọn {{ selectedIds.length }} câu hỏi
        </span>
      </div>

      <div class="flex items-center gap-3 text-xs font-bold text-[var(--muted)]">
        <span>Hiển thị {{ pageStartItem }} - {{ pageEndItem }} / Tổng {{ pagination.total }} câu hỏi</span>
        <button v-if="selectedIds.length > 0" class="btn-primary text-xs" type="button" @click="goToCreateExam('manual')">
          Tạo bộ đề từ {{ selectedIds.length }} câu đã chọn
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <AppLoadingState v-if="isLoading" title="Đang tải Ngân hàng câu hỏi..." message="Vui lòng chờ trong giây lát..." icon="📚" />

    <!-- Error State -->
    <AppErrorState v-else-if="errorMessage" title="Không thể tải Ngân hàng câu hỏi" :message="errorMessage" @retry="loadQuestions" />

    <!-- Loaded Questions List -->
    <template v-else>
      <div class="grid gap-4">
        <article 
          v-for="q in questions" 
          :key="q.id" 
          class="group rounded-[1.75rem] border p-5 transition duration-200"
          :class="selectedIds.includes(q.id) ? 'border-[var(--primary)] bg-[var(--primary)]/5 shadow-md' : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--border-strong)]'"
        >
          <div class="flex items-start gap-4">
            <input type="checkbox" :value="q.id" v-model="selectedIds" class="mt-1 h-5 w-5 rounded accent-[var(--primary)] cursor-pointer shrink-0" />
            
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-2.5 py-0.5 text-[11px] font-black text-[var(--muted)]">#{{ q.id }}</span>
                <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-2.5 py-0.5 text-[11px] font-black text-[var(--text)] uppercase">{{ q.type }}</span>
                <span 
                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                  :class="{
                    'bg-emerald-500/15 text-emerald-400': q.difficulty === 'easy',
                    'bg-amber-500/15 text-amber-400': q.difficulty === 'medium',
                    'bg-rose-500/15 text-rose-400': q.difficulty === 'hard'
                  }"
                >
                  <span 
                    class="h-1.5 w-1.5 rounded-full"
                    :class="{
                      'bg-emerald-400': q.difficulty === 'easy',
                      'bg-amber-400': q.difficulty === 'medium',
                      'bg-rose-400': q.difficulty === 'hard'
                    }"
                  ></span>
                  {{ difficultyText(q.difficulty) }}
                </span>
                <span v-if="q.grade_name" class="rounded-full bg-indigo-600/20 text-indigo-400 px-2.5 py-0.5 text-[11px] font-black">{{ q.grade_name }}</span>
                <span v-if="q.subject_name" class="rounded-full bg-emerald-600/20 text-emerald-400 px-2.5 py-0.5 text-[11px] font-black">{{ q.subject_name }}</span>
                <span v-if="q.topic_name" class="rounded-full bg-purple-600/20 text-purple-300 px-2.5 py-0.5 text-[11px] font-bold">Chủ đề: {{ q.topic_name }}</span>

                <!-- Nút Báo cáo vi phạm câu hỏi dành cho Người dùng / Học sinh -->
                <button
                  type="button"
                  class="ml-auto rounded-full border border-rose-500/30 bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-bold text-rose-400 hover:bg-rose-500/20 transition flex items-center gap-1"
                  title="Báo cáo vi phạm hoặc sai sót ở câu hỏi này"
                  @click="openReportModal(q.id)"
                >
                  <span>🚩</span>
                  <span>Báo cáo</span>
                </button>
              </div>

              <h3 class="text-base font-black text-[var(--text)] leading-snug">{{ q.content || q.text }}</h3>

              <!-- Answers List preview -->
              <div v-if="q.answers && q.answers.length > 0" class="mt-3 grid gap-2 md:grid-cols-2">
                <div 
                  v-for="ans in q.answers" 
                  :key="ans.id"
                  class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold"
                  :class="ans.is_correct ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300 font-bold' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]'"
                >
                  <span class="grid h-5 w-5 place-items-center rounded-lg bg-black/30 font-black text-[10px]">{{ ans.key }}</span>
                  <span class="truncate">{{ ans.text || ans.content }}</span>
                  <span v-if="ans.is_correct" class="ml-auto text-emerald-400 text-xs">✓ Đúng</span>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>

      <div v-if="questions.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
        <h3 class="text-2xl font-black text-[var(--text)]">Không tìm thấy câu hỏi nào</h3>
        <p class="mt-2 text-sm text-[var(--muted)]">Thử thay đổi bộ lọc Cấp học, Lớp hoặc Chủ đề.</p>
        <button class="btn-ghost mt-4 text-xs font-black text-[var(--primary)]" @click="resetFilters">Đặt lại bộ lọc</button>
      </div>

      <!-- THANH PHÂN TRANG HIỆN ĐẠI (PAGINATION BAR) -->
      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 shadow-[var(--shadow-soft)]">
        <div class="flex items-center gap-2 text-xs font-bold text-[var(--muted)]">
          <span>Trang {{ pagination.current_page }} / {{ pagination.last_page }} (Hiển thị {{ pageStartItem }} - {{ pageEndItem }} / {{ pagination.total }} câu)</span>
          <select v-model.number="pagination.per_page" class="field !py-1 !px-2 text-xs ml-2 cursor-pointer" @change="onPerPageChange">
            <option :value="10">10 câu / trang</option>
            <option :value="15">15 câu / trang</option>
            <option :value="25">25 câu / trang</option>
            <option :value="50">50 câu / trang</option>
          </select>
        </div>

        <div class="flex items-center gap-1.5">
          <button 
            class="btn-ghost !px-3 !py-1.5 text-xs font-bold" 
            :disabled="pagination.current_page <= 1" 
            @click="changePage(pagination.current_page - 1)"
          >
            ◀ Trang trước
          </button>

          <button
            v-for="p in visiblePages"
            :key="p"
            class="h-8 w-8 rounded-xl text-xs font-black transition duration-150"
            :class="p === pagination.current_page ? 'bg-[var(--primary)] text-white shadow-md' : 'bg-[var(--surface-soft)] text-[var(--text)] hover:bg-[var(--primary)]/20'"
            @click="changePage(p)"
          >
            {{ p }}
          </button>

          <button 
            class="btn-ghost !px-3 !py-1.5 text-xs font-bold" 
            :disabled="pagination.current_page >= pagination.last_page" 
            @click="changePage(pagination.current_page + 1)"
          >
            Trang sau ▶
          </button>
        </div>
      </div>
    </template>

    <!-- Modal báo cáo vi phạm câu hỏi cho người dùng -->
    <QuestionReportModal
      :question-id="reportingQuestionId"
      :is-open="isReportModalOpen"
      @close="isReportModalOpen = false"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import QuestionReportModal from '@/components/question/QuestionReportModal.vue'

const isReportModalOpen = ref(false)
const reportingQuestionId = ref(null)

const openReportModal = (questionId) => {
  reportingQuestionId.value = questionId
  isReportModalOpen.value = true
}
import { questionsBankApi, taxonomyApi } from '@/services/api'

const route = useRoute()
const router = useRouter()

const questions = ref([])
const selectedIds = ref([])
const topicsList = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const isSubmitting = ref(false)

const taxonomyLevels = ref([])
const allSubjects = ref([])

const filters = reactive({
  search: '',
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
  difficulty: '',
})

// TRẠNG THÁI PHÂN TRANG (PAGINATION)
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

// BỘ LỌC ĐỘNG RIÊNG CHO MODAL TẠO ĐỀ
const modalFilters = reactive({
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
})

const modalTopicsList = ref([])
const modalPoolStats = reactive({
  easy: 0,
  medium: 0,
  hard: 0,
  total: 0,
})

const isModalOpen = ref(false)
const modalMode = ref('manual') // 'manual' | 'random'

const quizForm = reactive({
  title: '',
  topic_name: '',
  description: '',
  easy_count: 3,
  medium_count: 5,
  hard_count: 2,
  time_limit_minutes: 15,
  shuffle_questions: true,
  visibility: 'public', // 'public' | 'private'
})

const totalMatrixQuestions = computed(() => {
  return (quizForm.easy_count || 0) + (quizForm.medium_count || 0) + (quizForm.hard_count || 0)
})

const pageStartItem = computed(() => {
  if (pagination.total === 0) return 0
  return (pagination.current_page - 1) * pagination.per_page + 1
})

const pageEndItem = computed(() => {
  return Math.min(pagination.current_page * pagination.per_page, pagination.total)
})

const visiblePages = computed(() => {
  const pages = []
  const maxPages = 5
  let start = Math.max(1, pagination.current_page - 2)
  let end = Math.min(pagination.last_page, start + maxPages - 1)
  
  if (end - start + 1 < maxPages) {
    start = Math.max(1, end - maxPages + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// REAL-TIME VALIDATION WARN BANNER
const matrixWarnings = computed(() => {
  const warnings = []
  if (quizForm.easy_count > modalPoolStats.easy) {
    warnings.push(`Không đủ câu hỏi Dễ. Hiện trong ngân hàng chỉ có ${modalPoolStats.easy} câu.`)
  }
  if (quizForm.medium_count > modalPoolStats.medium) {
    warnings.push(`Không đủ câu hỏi Vừa. Hiện trong ngân hàng chỉ có ${modalPoolStats.medium} câu.`)
  }
  if (quizForm.hard_count > modalPoolStats.hard) {
    warnings.push(`Không đủ câu hỏi Khó. Hiện trong ngân hàng chỉ có ${modalPoolStats.hard} câu.`)
  }
  return warnings
})

const fetchTaxonomy = async () => {
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
    console.error('Không tải được danh sách Chuyên đề:', e)
  }
}

const fetchModalTopicsList = async () => {
  try {
    const params = {
      education_level_id: modalFilters.education_level_id || undefined,
      grade_id: modalFilters.grade_id || undefined,
      subject_id: modalFilters.subject_id || undefined,
    }
    const data = await questionsBankApi.fetchTopics(params)
    modalTopicsList.value = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : [])
  } catch (e) {
    console.error('Không tải được danh sách Chuyên đề Modal:', e)
  }
}

const updateModalAvailableCount = async () => {
  try {
    const params = {
      education_level_id: modalFilters.education_level_id || undefined,
      grade_id: modalFilters.grade_id || undefined,
      subject_id: modalFilters.subject_id || undefined,
      topic_name: modalFilters.topic_name || undefined,
    }
    const res = await questionsBankApi.fetchStats(params)
    const stats = res?.easy !== undefined ? res : (res?.data ?? { easy: 0, medium: 0, hard: 0, total: 0 })
    modalPoolStats.easy = stats.easy || 0
    modalPoolStats.medium = stats.medium || 0
    modalPoolStats.hard = stats.hard || 0
    modalPoolStats.total = stats.total || 0

    // Tự động gợi ý số câu hợp lý phù hợp với kho hiện có
    if (quizForm.easy_count > modalPoolStats.easy) quizForm.easy_count = Math.min(3, modalPoolStats.easy)
    if (quizForm.medium_count > modalPoolStats.medium) quizForm.medium_count = Math.min(5, modalPoolStats.medium)
    if (quizForm.hard_count > modalPoolStats.hard) quizForm.hard_count = Math.min(2, modalPoolStats.hard)
  } catch (e) {
    console.error('Không đếm được số câu khả dụng trong Modal:', e)
  }
}

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

const modalAvailableGrades = computed(() => {
  if (!modalFilters.education_level_id) {
    return taxonomyLevels.value.flatMap(l => l.grades || [])
  }
  const level = taxonomyLevels.value.find(l => l.id === Number(modalFilters.education_level_id))
  return level ? level.grades || [] : []
})

const modalAvailableSubjects = computed(() => {
  if (!modalFilters.grade_id) {
    return allSubjects.value
  }
  const grade = modalAvailableGrades.value.find(g => g.id === Number(modalFilters.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const onLevelChange = () => {
  filters.grade_id = ''
  onGradeSubjectChange()
}

const onGradeSubjectChange = () => {
  fetchTopicsList()
  onFilterSubmit()
}

const onModalLevelChange = () => {
  modalFilters.grade_id = ''
  onModalGradeSubjectChange()
}

const onModalGradeSubjectChange = () => {
  fetchModalTopicsList()
  updateModalAvailableCount()
}

const onModalTopicSelectChange = () => {
  // Khi chọn ở ô Dropdown dưới -> Ô nhập Chủ đề ở trên tự động nhảy theo
  quizForm.topic_name = modalFilters.topic_name || ''
  if (modalMode.value === 'random' && modalFilters.topic_name) {
    quizForm.title = `Bộ đề thi tự động - Chủ đề: ${modalFilters.topic_name}`
  }
  updateModalAvailableCount()
}

const onQuizFormTopicInput = () => {
  // Khi gõ/chọn ở ô Nhập trên:
  // - Nếu tìm thấy trong danh sách kho -> Tự động nhảy ô Select ở dưới & đếm số câu khả dụng
  // - Nếu là Chủ đề mới -> Ô Select ở dưới về "Tất cả Chủ đề"
  const trimmed = (quizForm.topic_name || '').trim()
  const found = modalTopicsList.value.find(t => t.topic_name.toLowerCase() === trimmed.toLowerCase())
  if (found) {
    modalFilters.topic_name = found.topic_name
  } else if (!trimmed) {
    modalFilters.topic_name = ''
  }
  updateModalAvailableCount()
}

const selectTopic = (topicName) => {
  filters.topic_name = topicName
  onFilterSubmit()
}

const onFilterSubmit = () => {
  pagination.current_page = 1
  loadQuestions()
}

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  pagination.current_page = page
  loadQuestions()
  
  // Tự động cuộn mượt lên đầu danh sách câu hỏi
  const el = document.getElementById('question-list-top')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
  }
}

const onPerPageChange = () => {
  pagination.current_page = 1
  loadQuestions()
}

const hasActiveFilters = computed(() => {
  return Boolean(filters.search || filters.education_level_id || filters.grade_id || filters.subject_id || filters.topic_name || filters.difficulty)
})

const resetFilters = () => {
  filters.search = ''
  filters.education_level_id = ''
  filters.grade_id = ''
  filters.subject_id = ''
  filters.topic_name = ''
  filters.difficulty = ''
  pagination.current_page = 1
  router.push({ path: '/question-bank' })
  fetchTopicsList()
  loadQuestions()
}

const applyRouteFilters = () => {
  filters.search = typeof route.query.search === 'string' ? route.query.search : ''
  filters.difficulty = typeof route.query.difficulty === 'string' ? route.query.difficulty : ''
  filters.education_level_id = route.query.education_level_id ? Number(route.query.education_level_id) : ''
  filters.grade_id = route.query.grade_id ? Number(route.query.grade_id) : ''
  filters.subject_id = route.query.subject_id ? Number(route.query.subject_id) : ''
  filters.topic_name = typeof route.query.topic_name === 'string' ? route.query.topic_name : ''

  if (route.query.ids) {
    const parsedIds = String(route.query.ids).split(',').map(Number).filter(Boolean)
    const merged = new Set([...selectedIds.value, ...parsedIds])
    selectedIds.value = Array.from(merged)
  }
}

const difficultyText = (diff) => {
  switch (diff) {
    case 'easy': return 'Dễ'
    case 'hard': return 'Khó'
    default: return 'Vừa'
  }
}

const isAllSelected = computed(() => {
  if (questions.value.length === 0) return false
  return questions.value.every(q => selectedIds.value.includes(q.id))
})

const toggleSelectAll = () => {
  const currentPageIds = questions.value.map(q => q.id)
  if (isAllSelected.value) {
    selectedIds.value = selectedIds.value.filter(id => !currentPageIds.includes(id))
  } else {
    const set = new Set([...selectedIds.value, ...currentPageIds])
    selectedIds.value = Array.from(set)
  }
}

const loadQuestions = async () => {
  isLoading.value = true
  errorMessage.value = ''

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

    const res = await questionsBankApi.fetchBank(params)
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
  } catch (error) {
    errorMessage.value = `Không tải được Ngân hàng câu hỏi: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const goToCreateExam = (mode = 'auto') => {
  const query = {
    mode,
    education_level_id: filters.education_level_id || undefined,
    grade_id: filters.grade_id || undefined,
    subject_id: filters.subject_id || undefined,
    topic_name: filters.topic_name || undefined,
  }
  if (mode === 'manual' && selectedIds.value.length > 0) {
    query.ids = selectedIds.value.join(',')
  }
  router.push({ path: '/question-bank/create-exam', query })
}

watch(
  () => route.query,
  () => {
    applyRouteFilters()
    fetchTopicsList()
    loadQuestions()
  }
)

onMounted(async () => {
  await fetchTaxonomy()
  applyRouteFilters()
  await fetchTopicsList()
  await loadQuestions()
})
</script>
