<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Detail</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)] sm:text-5xl">{{ room?.name || `Room #${route.params.roomId}` }}</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ room?.description || 'Room chứa homework và live quiz. Practice public nằm ở luồng riêng.' }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-ghost" to="/rooms">My Rooms</router-link>
          <router-link class="btn-ghost" to="/quizzes">Practice</router-link>
        </div>
      </div>
    </article>

    <div class="flex gap-2 overflow-x-auto rounded-full border border-[var(--border)] bg-[var(--surface)] p-1 shadow-[var(--shadow-card)]">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="shrink-0 rounded-full px-4 py-2.5 text-sm font-black transition"
        :class="activeTab === tab.id ? 'bg-[var(--chip-active)] text-[var(--primary)] shadow-[0_10px_28px_rgba(155,44,255,0.16)]' : 'text-[var(--muted)] hover:text-[var(--text)]'"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải room...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <section v-if="!isLoading && activeTab === 'overview'" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Tổng quan</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <InfoBox label="Room code" :value="room?.code || '-'" />
          <InfoBox label="Vai trò" :value="roomRoleLabel" />
          <InfoBox label="Thành viên" :value="room?.members_count ?? members.length" />
          <InfoBox label="Homework" :value="assignments.length" />
        </div>
      </article>

      <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Actions</p>
        <div class="mt-5 grid gap-3">
          <button v-if="canManageRoom" class="btn-primary w-full" type="button" @click="activeTab = 'homework'; isAssignmentFormOpen = true">Giao Homework</button>
          <button v-if="canManageRoom" class="btn-ghost w-full" type="button" @click="activeTab = 'live'">Tạo Live Quiz</button>
          <button v-if="canManageRoom" class="btn-ghost w-full" type="button" @click="activeTab = 'members'">Quản lý thành viên</button>
          <template v-else>
            <button class="btn-primary w-full" type="button" @click="requireUpgrade">Tạo Room</button>
            <button class="btn-ghost w-full" type="button" @click="requireUpgrade">Giao Homework</button>
            <button class="btn-ghost w-full" type="button" @click="requireUpgrade">Mở Live Quiz</button>
          </template>
        </div>
      </aside>
    </section>

    <section v-if="!isLoading && activeTab === 'homework'" class="grid gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Homework</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ canManageRoom ? 'Assignment đã giao' : 'Bài được giao' }}</h2>
          </div>
          <button v-if="canManageRoom" class="btn-primary" type="button" @click="isAssignmentFormOpen = !isAssignmentFormOpen">{{ isAssignmentFormOpen ? 'Đóng form' : 'Giao bài mới' }}</button>
        </div>

        <form v-if="canManageRoom && isAssignmentFormOpen" class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5" @submit.prevent="createAssignment">
          <div class="grid gap-4 lg:grid-cols-2">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Quiz
              <select v-model="assignmentForm.quiz_id" class="field">
                <option value="">Chọn quiz</option>
                <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">{{ quiz.title }}</option>
              </select>
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Title
              <input v-model="assignmentForm.title" class="field" placeholder="Ví dụ: Bài tập tuần 3" />
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)] lg:col-span-2">
              Description
              <textarea v-model="assignmentForm.description" class="field min-h-24" placeholder="Ghi chú cho học sinh"></textarea>
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Starts at
              <input v-model="assignmentForm.starts_at" class="field" type="datetime-local" />
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Deadline
              <input v-model="assignmentForm.deadline_at" class="field" type="datetime-local" />
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Duration minutes
              <input v-model.number="assignmentForm.duration_minutes" class="field" min="1" max="600" type="number" />
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Max attempts
              <input v-model.number="assignmentForm.max_attempts" class="field" min="1" max="20" type="number" />
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Show result mode
              <select v-model="assignmentForm.show_result_mode" class="field">
                <option value="immediately">Immediately</option>
                <option value="after_submit">After submit</option>
                <option value="after_deadline">After deadline</option>
                <option value="manual">Manual</option>
              </select>
            </label>
          </div>
          <div class="mt-5 flex flex-wrap items-center gap-3">
            <button class="btn-primary disabled:cursor-not-allowed disabled:opacity-60" type="submit" :disabled="isSavingAssignment">{{ isSavingAssignment ? 'Đang giao...' : 'Publish assignment' }}</button>
            <p v-if="assignmentMessage" class="text-sm font-bold text-emerald-300">{{ assignmentMessage }}</p>
          </div>
        </form>
      </article>

      <div v-if="assignments.length" class="grid gap-4 lg:grid-cols-2">
        <article v-for="assignment in assignments" :key="assignment.id" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <span class="inline-flex rounded-full border px-3 py-1 text-xs font-black" :class="statusClass(assignment)">{{ statusLabel(assignment) }}</span>
              <h3 class="mt-3 text-2xl font-black text-[var(--text)]">{{ assignment.title }}</h3>
              <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--muted)]">{{ assignment.description || 'Chưa có mô tả.' }}</p>
            </div>
            <span class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-sm font-black text-[var(--primary)]">{{ assignment.quizTitle }}</span>
          </div>
          <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <InfoBox label="Deadline" :value="formatDate(assignment.deadline_at)" />
            <InfoBox label="Duration" :value="assignment.durationLabel" />
            <InfoBox label="Attempts" :value="assignment.maxAttemptsLabel" />
          </div>
          <div class="mt-5 flex flex-wrap gap-3">
            <router-link class="btn-ghost" :to="`/rooms/${route.params.roomId}/assignments/${assignment.id}`">Chi tiết</router-link>
            <router-link v-if="!canManageRoom && canWork(assignment)" class="btn-primary" :to="`/rooms/${route.params.roomId}/assignments/${assignment.id}/do`">{{ progressFor(assignment.id)?.status === 'in_progress' ? 'Tiếp tục làm' : 'Làm bài' }}</router-link>
            <router-link v-if="!canManageRoom && canViewResult(assignment)" class="btn-ghost" :to="`/rooms/${route.params.roomId}/assignments/${assignment.id}/result`">Xem kết quả</router-link>
            <button v-if="canManageRoom" class="btn-ghost" type="button" @click="openSubmissions(assignment)">Bài nộp ({{ assignment.submissions_count ?? 0 }})</button>
          </div>
        </article>
      </div>

      <article v-if="canManageRoom && submissionsAssignment" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Assignment Submissions</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ submissionsAssignment.title }}</h2>
            <p class="mt-2 text-sm text-[var(--muted)]">Chủ room/VIP xem kết quả thành viên đã làm trong assignment này.</p>
          </div>
          <button class="btn-ghost" type="button" @click="closeSubmissions">Đóng</button>
        </div>

        <div v-if="isLoadingSubmissions" class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Đang tải danh sách bài nộp...</div>
        <div v-if="submissionError" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ submissionError }}</div>

        <div v-if="!isLoadingSubmissions && submissions.length" class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.8fr)]">
          <div class="overflow-x-auto rounded-2xl border border-[var(--border)]">
            <div class="min-w-[760px]">
              <div class="grid grid-cols-[minmax(180px,1.4fr)_120px_120px_150px_110px] gap-3 border-b border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--muted)]">
                <span>Thành viên</span>
                <span>Trạng thái</span>
                <span>Điểm</span>
                <span>Đã nộp</span>
                <span></span>
              </div>
              <div
                v-for="submission in submissions"
                :key="submission.id"
                class="grid grid-cols-[minmax(180px,1.4fr)_120px_120px_150px_110px] gap-3 border-b border-[var(--border)] px-4 py-3 text-sm last:border-b-0"
              >
                <div>
                  <b class="block text-[var(--text)]">{{ submission.user?.name || `User #${submission.user_id}` }}</b>
                  <span class="text-xs text-[var(--muted)]">{{ submission.user?.email || 'Không có email' }}</span>
                </div>
                <span class="self-center rounded-full border px-3 py-1 text-xs font-black" :class="submissionStatusClass(submission.status)">{{ submissionStatusLabel(submission.status) }}</span>
                <b class="self-center text-[var(--text)]">{{ scoreLabel(submission) }}</b>
                <span class="self-center text-xs font-bold text-[var(--muted)]">{{ formatDate(submission.submitted_at) }}</span>
                <button class="btn-ghost px-3 py-2 text-xs" type="button" @click="viewSubmission(submission)">Xem</button>
              </div>
            </div>
          </div>

          <aside class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
            <div v-if="isLoadingSubmissionDetail" class="text-sm font-bold text-[var(--muted)]">Đang tải chi tiết...</div>
            <div v-else-if="selectedSubmission">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Chi tiết kết quả</p>
              <h3 class="mt-2 text-xl font-black text-[var(--text)]">{{ selectedSubmission.user?.name || `User #${selectedSubmission.user_id}` }}</h3>
              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <InfoBox label="Điểm" :value="scoreLabel(selectedSubmission)" />
                <InfoBox label="Đúng/Sai" :value="`${selectedSubmission.correct_count ?? 0}/${selectedSubmission.wrong_count ?? 0}`" />
                <InfoBox label="Tổng câu" :value="selectedSubmission.total_questions ?? '-'" />
                <InfoBox label="Nộp lúc" :value="formatDate(selectedSubmission.submitted_at)" />
              </div>
              <div v-if="selectedSubmission.answers?.length" class="mt-5 grid gap-3">
                <article v-for="answer in selectedSubmission.answers" :key="answer.id" class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4">
                  <div class="flex flex-wrap items-start justify-between gap-3">
                    <p class="text-sm font-black text-[var(--text)]">{{ answer.question || `Question #${answer.question_id}` }}</p>
                    <span class="rounded-full border px-3 py-1 text-xs font-black" :class="answer.is_correct ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-rose-500/30 bg-rose-500/10 text-rose-300'">{{ answer.is_correct ? 'Đúng' : 'Sai' }}</span>
                  </div>
                  <p class="mt-3 text-sm text-[var(--muted)]">Đã chọn: {{ answerText(answer.selected_answers) || answer.answer || '-' }}</p>
                  <p class="mt-1 text-sm text-[var(--muted)]">Đáp án đúng: {{ answerText(answer.correct_answers) || '-' }}</p>
                  <p class="mt-2 text-xs font-bold text-[var(--primary)]">Score: {{ answer.score ?? 0 }}</p>
                </article>
              </div>
              <p v-else class="mt-5 text-sm font-bold text-[var(--muted)]">Chưa có câu trả lời nào được lưu.</p>
            </div>
            <div v-else class="text-sm font-bold text-[var(--muted)]">Chọn một bài nộp để xem chi tiết.</div>
          </aside>
        </div>

        <div v-if="!isLoadingSubmissions && !submissions.length && !submissionError" class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-6 text-center">
          <h3 class="text-xl font-black text-[var(--text)]">Chưa có bài nộp</h3>
          <p class="mt-2 text-sm text-[var(--muted)]">Khi thành viên start/submit assignment, danh sách sẽ hiển thị tại đây.</p>
        </div>
      </article>

      <div v-if="!assignments.length" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
        <h3 class="text-2xl font-black text-[var(--text)]">Chưa có homework</h3>
        <p class="mt-2 text-sm text-[var(--muted)]">{{ canManageRoom ? 'Giao bài mới từ form phía trên.' : 'Giáo viên chưa giao bài trong room này.' }}</p>
      </div>
    </section>

    <section v-if="!isLoading && activeTab === 'live'" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Live Quiz</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Chưa kết nối realtime</h2>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">UI Live Quiz được đặt đúng trong Room, nhưng frontend/backend hiện chưa có endpoint live session hoàn chỉnh trong phạm vi này.</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
          <InfoBox label="Status" value="coming_soon" />
          <InfoBox label="Participants" value="TODO" />
          <InfoBox label="Leaderboard" value="TODO" />
        </div>
      </article>
      <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Actions</p>
        <div class="mt-5 grid gap-3">
          <button v-if="canManageRoom" class="btn-primary opacity-70" type="button" disabled>Tạo Live Quiz TODO</button>
          <button v-if="canManageRoom" class="btn-ghost opacity-70" type="button" disabled>Start Live TODO</button>
          <button v-else class="btn-primary opacity-70" type="button" disabled>Join Live TODO</button>
        </div>
      </aside>
    </section>

    <section v-if="!isLoading && activeTab === 'members'" class="grid gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Members</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Thành viên room</h2>
          </div>
          <button v-if="canManageRoom" class="btn-ghost opacity-70" type="button" disabled title="TODO: thiếu endpoint invite member">Mời thành viên TODO</button>
        </div>
      </article>

      <div v-if="members.length" class="grid gap-4 lg:grid-cols-2">
        <article v-for="member in members" :key="member.id" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="text-xl font-black text-[var(--text)]">{{ member.name || `User #${member.user_id}` }}</h3>
              <p class="mt-1 text-sm text-[var(--muted)]">{{ member.email || 'Không có email' }}</p>
            </div>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ member.room_role || member.role || 'member' }}</span>
          </div>
        </article>
      </div>

      <div v-else class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)] shadow-[var(--shadow-card)]">Chưa tải được danh sách thành viên hoặc API không trả dữ liệu.</div>
    </section>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  currentUserStorage,
  homeworkAssignmentsApi,
  homeworkProgressStorage,
  normalizeAssignment,
  quizzesApi,
  roomsApi,
} from '@/services/api'

const InfoBox = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '-' },
  },
  setup(props) {
    return () => h('div', { class: 'rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4' }, [
      h('p', { class: 'text-xs font-bold text-[var(--muted)]' }, props.label),
      h('b', { class: 'mt-1 block text-sm text-[var(--text)]' }, props.value ?? '-'),
    ])
  },
})

const route = useRoute()
const router = useRouter()

const tabs = [
  { id: 'overview', label: 'Tổng quan' },
  { id: 'homework', label: 'Homework' },
  { id: 'live', label: 'Live Quiz' },
  { id: 'members', label: 'Thành viên' },
]

const activeTab = ref(route.path.endsWith('/homework') ? 'homework' : 'overview')
const room = ref(null)
const members = ref([])
const assignments = ref([])
const quizzes = ref([])
const progressMap = ref({})
const isLoading = ref(false)
const isAssignmentFormOpen = ref(false)
const isSavingAssignment = ref(false)
const errorMessage = ref('')
const assignmentMessage = ref('')
const submissionsAssignment = ref(null)
const submissions = ref([])
const selectedSubmission = ref(null)
const isLoadingSubmissions = ref(false)
const isLoadingSubmissionDetail = ref(false)
const submissionError = ref('')

const assignmentForm = reactive({
  quiz_id: '',
  title: '',
  description: '',
  starts_at: '',
  deadline_at: '',
  duration_minutes: null,
  max_attempts: 1,
  show_result_mode: 'after_submit',
})

const currentUser = computed(() => currentUserStorage.get() || {})
const currentMember = computed(() => members.value.find((member) => Number(member.user_id) === Number(currentUser.value.id)) || null)
const userRole = computed(() => String(currentUser.value.role || 'user').toLowerCase())
const roomRoleLabel = computed(() => currentMember.value?.room_role || currentMember.value?.role || (Number(room.value?.host_id) === Number(currentUser.value.id) ? 'owner' : 'member'))
const canManageRoom = computed(() => {
  if (userRole.value === 'admin') return true
  if (Number(room.value?.host_id) === Number(currentUser.value.id)) return true
  return ['owner', 'teacher'].includes(String(roomRoleLabel.value || '').toLowerCase())
})

const nowMs = () => Date.now()
const dateMs = (value) => (value ? new Date(value).getTime() : null)
const isBeforeStart = (assignment) => {
  const startsAt = dateMs(assignment.starts_at)
  return startsAt && startsAt > nowMs()
}
const isPastDeadline = (assignment) => {
  const deadlineAt = dateMs(assignment.deadline_at)
  return deadlineAt && deadlineAt < nowMs()
}
const progressFor = (assignmentId) => progressMap.value[String(assignmentId)] || null

const formatDate = (value) => {
  if (!value) return 'Không giới hạn'
  return new Date(value).toLocaleString('vi-VN')
}

const statusLabel = (assignment) => {
  const progress = progressFor(assignment.id)
  if (progress?.status === 'submitted') return 'Đã nộp'
  if (progress?.status === 'late') return 'Quá hạn'
  if (isPastDeadline(assignment)) return 'Quá hạn'
  if (isBeforeStart(assignment)) return 'Chưa đến giờ'
  if (progress?.status === 'in_progress') return 'Đang làm'
  if (assignment.status !== 'published') return 'Chưa mở'
  return 'Chưa làm'
}

const statusClass = (assignment) => {
  const label = statusLabel(assignment)
  if (label === 'Đã nộp') return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
  if (label === 'Đang làm') return 'border-amber-500/30 bg-amber-500/10 text-amber-300'
  if (label === 'Quá hạn') return 'border-rose-500/30 bg-rose-500/10 text-rose-300'
  if (label === 'Chưa đến giờ' || label === 'Chưa mở') return 'border-slate-500/30 bg-slate-500/10 text-slate-300'
  return 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--primary)]'
}

const canWork = (assignment) => {
  const progress = progressFor(assignment.id)
  if (progress?.status === 'submitted' || progress?.status === 'late') return false
  return assignment.status === 'published' && !isBeforeStart(assignment) && !isPastDeadline(assignment)
}

const canViewResult = (assignment) => progressFor(assignment.id)?.status === 'submitted'

const submissionStatusLabel = (status) => ({
  in_progress: 'Đang làm',
  submitted: 'Đã nộp',
  late: 'Quá hạn',
}[status] || status || '-')

const submissionStatusClass = (status) => {
  if (status === 'submitted') return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
  if (status === 'in_progress') return 'border-amber-500/30 bg-amber-500/10 text-amber-300'
  if (status === 'late') return 'border-rose-500/30 bg-rose-500/10 text-rose-300'
  return 'border-slate-500/30 bg-slate-500/10 text-slate-300'
}

const scoreLabel = (submission) => {
  if (!submission) return '-'
  const score = submission.score ?? 0
  const total = submission.total_questions ?? 0
  const percent = submission.score_percent ?? (total > 0 ? Math.round(((submission.correct_count ?? 0) / total) * 100) : null)
  return percent === null ? String(score) : `${score} (${percent}%)`
}

const answerText = (answers = []) => {
  if (!Array.isArray(answers)) return ''
  return answers.map((answer) => answer.content || answer.text).filter(Boolean).join(', ')
}

const closeSubmissions = () => {
  submissionsAssignment.value = null
  submissions.value = []
  selectedSubmission.value = null
  submissionError.value = ''
}

const viewSubmission = async (submission) => {
  if (!submissionsAssignment.value) return

  isLoadingSubmissionDetail.value = true
  submissionError.value = ''

  try {
    selectedSubmission.value = await homeworkAssignmentsApi.getSubmissionResult(
      route.params.roomId,
      submissionsAssignment.value.id,
      submission.id,
    )
  } catch (error) {
    submissionError.value = `Không tải được chi tiết bài nộp: ${error.message}`
  } finally {
    isLoadingSubmissionDetail.value = false
  }
}

const openSubmissions = async (assignment) => {
  submissionsAssignment.value = assignment
  submissions.value = []
  selectedSubmission.value = null
  submissionError.value = ''
  isLoadingSubmissions.value = true

  try {
    submissions.value = await homeworkAssignmentsApi.listSubmissions(route.params.roomId, assignment.id)
    const firstSubmitted = submissions.value.find((submission) => submission.status === 'submitted') || submissions.value[0]
    if (firstSubmitted) {
      await viewSubmission(firstSubmitted)
    }
  } catch (error) {
    submissionError.value = `Không tải được danh sách bài nộp: ${error.message}`
  } finally {
    isLoadingSubmissions.value = false
  }
}

const requireUpgrade = () => {
  window.alert('Tạo Room, Giao Homework và Live Quiz là tính năng VIP. Vui lòng nâng cấp để sử dụng.')
  router.push('/upgrade')
}

const emptyToNull = (value) => (value === '' || value === undefined ? null : value)

const loadRoom = async () => {
  isLoading.value = true
  errorMessage.value = ''
  progressMap.value = homeworkProgressStorage.getAll()

  try {
    const [roomData, assignmentData, memberData, quizData] = await Promise.all([
      roomsApi.get(route.params.roomId),
      homeworkAssignmentsApi.list(route.params.roomId),
      roomsApi.members(route.params.roomId).catch(() => []),
      quizzesApi.list({ per_page: 100 }).catch(() => []),
    ])
    room.value = roomData
    assignments.value = assignmentData.map(normalizeAssignment)
    members.value = memberData
    quizzes.value = quizData
  } catch (error) {
    if (error.status === 401) {
      errorMessage.value = 'Bạn cần đăng nhập để xem room.'
      return
    }
    if (error.status === 403) {
      errorMessage.value = 'Bạn không có quyền truy cập room này.'
      return
    }
    errorMessage.value = `Không tải được room: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const createAssignment = async () => {
  if (!canManageRoom.value) {
    requireUpgrade()
    return
  }

  if (!assignmentForm.quiz_id || !assignmentForm.title.trim()) {
    errorMessage.value = 'Bạn cần chọn quiz và nhập title assignment.'
    return
  }

  isSavingAssignment.value = true
  errorMessage.value = ''
  assignmentMessage.value = ''

  try {
    await homeworkAssignmentsApi.create(route.params.roomId, {
      quiz_id: assignmentForm.quiz_id,
      title: assignmentForm.title.trim(),
      description: emptyToNull(assignmentForm.description.trim()),
      starts_at: emptyToNull(assignmentForm.starts_at),
      deadline_at: emptyToNull(assignmentForm.deadline_at),
      duration_minutes: emptyToNull(assignmentForm.duration_minutes),
      max_attempts: assignmentForm.max_attempts || 1,
      show_result_mode: assignmentForm.show_result_mode,
      status: 'published',
    })
    assignmentMessage.value = 'Giao bài thành công.'
    assignmentForm.quiz_id = ''
    assignmentForm.title = ''
    assignmentForm.description = ''
    assignmentForm.starts_at = ''
    assignmentForm.deadline_at = ''
    assignmentForm.duration_minutes = null
    assignmentForm.max_attempts = 1
    assignmentForm.show_result_mode = 'after_submit'
    isAssignmentFormOpen.value = false
    const assignmentData = await homeworkAssignmentsApi.list(route.params.roomId)
    assignments.value = assignmentData.map(normalizeAssignment)
  } catch (error) {
    errorMessage.value = `Không giao được bài: ${error.message}`
  } finally {
    isSavingAssignment.value = false
  }
}

onMounted(loadRoom)
</script>
