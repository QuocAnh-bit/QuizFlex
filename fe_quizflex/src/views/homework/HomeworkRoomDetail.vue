<template>
  <section class="grid gap-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" to="/homework-rooms">Quay lại danh sách</router-link>
      <router-link v-if="canManageRoom" class="btn-primary" :to="`/homework-rooms/${roomId}/assignments/create`">Giao quiz</router-link>
    </div>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải chi tiết room...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <template v-if="!isLoading && room">
      <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-6 xl:flex-row xl:items-end">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Homework</p>
            <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ room.name || 'Room Homework' }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">{{ room.description || 'Chưa có mô tả.' }}</p>
          </div>
          <div class="grid gap-2 text-right">
            <span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">{{ room.code || 'NO CODE' }}</span>
            <StatusBadge :value="room.status || 'active'" />
          </div>
        </div>

        <div class="relative z-10 mt-6 grid gap-3 md:grid-cols-4">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Chủ room</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ room.owner?.name || '-' }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Thành viên</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ memberCount }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Bài được giao</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ room.assignments_count ?? assignments.length }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Ngày tạo</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ formatDate(room.created_at) }}</p>
          </div>
        </div>
      </article>

      <article v-if="shouldShowAllowedMembers" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Allowed Emails</p>
            <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Danh sách email được phép tham gia</h2>
            <p class="mt-2 text-sm font-bold leading-6 text-[var(--muted)]">Danh sách này chỉ được kiểm tra khi room dùng chế độ whitelist email.</p>
          </div>
          <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ allowedMembers.length }}</span>
        </div>

        <form class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]" @submit.prevent="addAllowedMembers">
          <textarea
            v-model="allowedEmailText"
            class="field min-h-24 resize-y"
            placeholder="student1@example.com&#10;student2@example.com, student3@example.com"
          ></textarea>
          <button class="btn-primary self-start" type="submit" :disabled="isSavingAllowedMembers">{{ isSavingAllowedMembers ? 'Đang thêm...' : 'Thêm email' }}</button>
        </form>

        <div v-if="allowedMembersMessage" class="mt-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ allowedMembersMessage }}</div>
        <div v-if="allowedMembersError" class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ allowedMembersError }}</div>

        <div v-if="allowedMembers.length" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <article v-for="allowedMember in allowedMembers" :key="allowedMember.id" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h3 class="break-words text-sm font-black text-[var(--text)]">{{ allowedMember.email }}</h3>
                <p class="mt-1 text-xs font-bold text-[var(--muted)]">{{ allowedMember.joined_at ? `Đã tham gia: ${formatDateTime(allowedMember.joined_at)}` : 'Chưa tham gia' }}</p>
              </div>
              <button class="btn-ghost shrink-0 px-3 py-2 text-xs" type="button" @click="removeAllowedMember(allowedMember.id)">Xóa</button>
            </div>
          </article>
        </div>

        <div v-else class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Chưa có email nào trong danh sách.</div>
      </article>

      <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Members</p>
              <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Thành viên</h2>
            </div>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ filteredMembers.length }}</span>
          </div>

          <div v-if="filteredMembers.length" class="mt-5 grid gap-3">
            <article v-for="member in filteredMembers" :key="member.id || `${member.room_id}-${member.user_id}`" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <h3 class="font-black text-[var(--text)] truncate">{{ member.user?.name || `User #${member.user_id}` }}</h3>
                  <p class="mt-1 text-xs font-bold text-[var(--muted)] truncate">{{ member.user?.email || 'Chưa có email' }}</p>
                </div>
                <StatusBadge :value="member.role || 'member'" />
              </div>
              <div class="mt-3 flex items-center justify-between">
                <StatusBadge :value="member.status || 'active'" />
                <div class="flex gap-2">
                  <button
                    v-if="canManageRoom || Number(member.user_id) === Number(currentUser?.id)"
                    class="btn-ghost px-3 py-1.5 text-xs text-[var(--primary)] hover:bg-[var(--primary)]/10"
                    type="button"
                    @click="openMemberDetail(member)"
                  >
                    Chi tiết
                  </button>
                  <button
                    v-if="canManageRoom"
                    class="btn-ghost px-3 py-1.5 text-xs text-rose-400 hover:bg-rose-500/10"
                    type="button"
                    @click="removeMember(member)"
                  >
                    Xóa
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">Chưa có thành viên.</div>
        </article>

        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Assignments</p>
              <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Bài được giao</h2>
            </div>
            <router-link v-if="canManageRoom" class="btn-ghost" :to="`/homework-rooms/${roomId}/assignments/create`">Giao quiz</router-link>
          </div>

          <div v-if="assignments.length" class="mt-5 grid gap-4">
            <article v-for="assignment in assignments" :key="assignment.id" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge :value="assignment.status || 'published'" />
                    <span class="rounded-full border border-[var(--border)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ assignment.show_result_mode || 'immediately' }}</span>
                  </div>
                  <h3 class="mt-3 text-xl font-black text-[var(--text)]">{{ assignment.title || assignment.quiz?.title || 'Bài được giao' }}</h3>
                  <p class="mt-2 text-sm leading-6 text-[var(--muted)]">{{ assignment.description || 'Chưa có mô tả.' }}</p>
                  <p class="mt-3 text-sm font-bold text-[var(--muted)]">Quiz: <span class="text-[var(--text)]">{{ assignment.quiz?.title || `#${assignment.quiz_id}` }}</span></p>
                </div>
                <router-link v-if="canManageRoom" class="btn-ghost whitespace-nowrap" :to="`/homework-rooms/${roomId}/assignments/${assignment.id}/attempts`">Xem bài nộp</router-link>
                <router-link v-else class="btn-ghost whitespace-nowrap" :to="`/homework-rooms/${roomId}/assignments/${assignment.id}/take`">Làm bài</router-link>
              </div>

              <div class="mt-5 grid gap-3 md:grid-cols-4">
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3">
                  <p class="text-xs font-bold text-[var(--muted)]">Bắt đầu</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ formatDateTime(assignment.starts_at) }}</p>
                </div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3">
                  <p class="text-xs font-bold text-[var(--muted)]">Deadline</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ formatDateTime(assignment.deadline_at) }}</p>
                </div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3">
                  <p class="text-xs font-bold text-[var(--muted)]">Thời lượng</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ assignment.duration_minutes ? `${assignment.duration_minutes} phút` : 'Không giới hạn' }}</p>
                </div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3">
                  <p class="text-xs font-bold text-[var(--muted)]">Số lần</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ assignment.my_attempts_count ?? 0 }}/{{ assignment.max_attempts ?? 1 }}</p>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="mt-5 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Empty</p>
            <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Chưa có bài nào được giao</h3>
            <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Khi chủ room giao quiz, bài sẽ xuất hiện tại đây.</p>
          </div>
        </article>
      </div>

      <!-- Modal Chi tiết thành viên -->
      <div v-if="selectedMember" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-lg rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] transition-all">
          <div class="flex items-center justify-between pb-4 border-b border-[var(--border)]">
            <h3 class="text-xl font-black text-[var(--text)]">Chi tiết thành viên</h3>
            <button @click="closeMemberDetail" class="text-[var(--muted)] hover:text-[var(--text)] text-2xl font-bold">&times;</button>
          </div>

          <div class="mt-5 space-y-6 max-h-[70vh] overflow-y-auto pr-1">
            <div class="flex items-center gap-3">
              <div class="h-12 w-12 rounded-full bg-[var(--primary)]/10 flex items-center justify-center font-black text-[var(--primary)] text-lg">
                {{ selectedMember.user?.name ? selectedMember.user.name.charAt(0).toUpperCase() : 'M' }}
              </div>
              <div class="min-w-0">
                <h4 class="font-black text-[var(--text)] truncate">{{ selectedMember.user?.name || `User #${selectedMember.user_id}` }}</h4>
                <p class="text-xs font-bold text-[var(--muted)] truncate">{{ selectedMember.user?.email || 'Chưa có email' }}</p>
              </div>
            </div>

            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)] mb-3">Thông tin học tập</p>
              <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Ngày tham gia</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ formatDateTime(selectedMember.joined_at) }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Bài được giao</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ selectedMember.assigned ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Đã hoàn thành</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ selectedMember.completed ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Tỷ lệ hoàn thành</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ selectedMember.completion_rate ?? 0 }}%</p>
                </div>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 col-span-2">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Điểm trung bình</p>
                  <p class="mt-1 text-lg font-black text-[var(--primary)]">{{ selectedMember.average_score ?? 0 }}/10</p>
                </div>
              </div>
            </div>

            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)] mb-3">Đánh giá thành viên</p>
              
              <div v-if="isLoadingEvaluation" class="py-4 text-center text-xs font-bold text-[var(--muted)]">
                Đang tải đánh giá...
              </div>

              <div v-else>
                <!-- Nhận xét theo từng bài (Lịch sử nhận xét) -->
                <div class="border-b border-[var(--border)] pb-4 mb-4">
                  <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)] mb-3">Lịch sử nhận xét bài nộp</p>
                  <div 
                    v-if="evaluationData?.submission_evaluations && evaluationData.submission_evaluations.length"
                    class="space-y-3 max-h-48 overflow-y-auto pr-1"
                  >
                    <div 
                      v-for="subEval in evaluationData.submission_evaluations" 
                      :key="subEval.id"
                      class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-xs"
                    >
                      <div class="flex items-start justify-between gap-2">
                        <span class="font-black text-[var(--text)] truncate max-w-[200px]" :title="subEval.assignment_name">
                          {{ subEval.assignment_name }}
                        </span>
                        <span class="shrink-0 font-bold text-[var(--muted)]">
                          Điểm: <strong class="text-[var(--text)]">{{ subEval.score }}</strong>
                        </span>
                      </div>
                      <p class="mt-1 text-[10px] text-[var(--muted)]">{{ formatDateTime(subEval.submitted_at) }}</p>
                      <p class="mt-2 font-medium leading-relaxed" :class="subEval.comment ? 'text-[var(--text)] italic' : 'text-[var(--muted)]'">
                        {{ subEval.comment ? `"${subEval.comment}"` : 'Chưa có nhận xét bài nộp.' }}
                      </p>
                    </div>
                  </div>
                  <div v-else class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center text-xs font-bold text-[var(--muted)]">
                    Chưa có bài nộp nào trong room này.
                  </div>
                </div>

                <div v-if="canManageRoom" class="space-y-4">
                  <div>
                    <label class="block text-xs font-bold text-[var(--muted)] mb-1 uppercase">Nhận xét của chủ phòng</label>
                    <textarea 
                      v-model="evaluationForm.comment"
                      class="field min-h-20 w-full resize-y text-sm"
                      placeholder="Nhập nhận xét thành viên (Ví dụ: Làm bài đầy đủ và nghiêm túc...)"
                    ></textarea>
                  </div>
                </div>

                <div v-else class="space-y-3 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
                  <div>
                    <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Nhận xét</p>
                    <p class="mt-1 text-sm font-bold leading-relaxed text-[var(--text)] italic">
                      "{{ evaluationData?.comment || 'Chưa có nhận xét nào.' }}"
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-[var(--border)]">
            <button @click="closeMemberDetail" class="btn-ghost" type="button">Đóng</button>
            <button 
              v-if="canManageRoom && !isLoadingEvaluation" 
              @click="saveEvaluation" 
              class="btn-primary" 
              type="button"
              :disabled="isSavingEvaluation"
            >
              {{ isSavingEvaluation ? 'Đang lưu...' : (evaluationData ? 'Cập nhật đánh giá' : 'Lưu đánh giá') }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { currentUserStorage, homeworkApi } from '@/services/api'

const route = useRoute()
const roomId = computed(() => route.params.roomId)
const currentUser = currentUserStorage.get()

const room = ref(null)
const members = ref([])
const assignments = ref([])
const allowedMembers = ref([])
const allowedEmailText = ref('')
const isLoading = ref(false)
const isSavingAllowedMembers = ref(false)
const errorMessage = ref('')
const allowedMembersError = ref('')
const allowedMembersMessage = ref('')

// Evaluation state variables
const selectedMember = ref(null)
const isLoadingEvaluation = ref(false)
const isSavingEvaluation = ref(false)
const evaluationData = ref(null)
const evaluationForm = ref({
  comment: '',
})

const canManageRoom = computed(() => currentUser?.role === 'admin' || Number(room.value?.owner_id) === Number(currentUser?.id))

const openMemberDetail = async (member) => {
  selectedMember.value = member
  isLoadingEvaluation.value = true
  evaluationData.value = null
  evaluationForm.value.comment = ''

  try {
    const data = await homeworkApi.getMemberEvaluation(roomId.value, member.user_id)
    evaluationData.value = data
    if (data) {
      evaluationForm.value.comment = data.comment || ''
    }
  } catch (error) {
    console.error('Không tải được đánh giá:', error)
  } finally {
    isLoadingEvaluation.value = false
  }
}

const closeMemberDetail = () => {
  selectedMember.value = null
}

const saveEvaluation = async () => {
  if (!selectedMember.value) return

  isSavingEvaluation.value = true
  try {
    const data = await homeworkApi.saveMemberEvaluation(roomId.value, selectedMember.value.user_id, {
      comment: evaluationForm.value.comment,
    })
    evaluationData.value = data
    
    // update statistics locally in list if owner changes evaluation, though stats themselves come from assignments/attempts
    alert('Đã lưu đánh giá thành công.')
  } catch (error) {
    alert(`Không lưu được đánh giá: ${error.message}`)
  } finally {
    isSavingEvaluation.value = false
  }
}
const canManageAllowedMembers = computed(() => room.value?.type === 'homework' && Number(room.value?.owner_id) === Number(currentUser?.id))
const shouldShowAllowedMembers = computed(() => canManageAllowedMembers.value && room.value?.join_policy === 'email_whitelist')
const filteredMembers = computed(() => members.value.filter((member) => Number(member.user_id) !== Number(room.value?.owner_id)))
const memberCount = computed(() => room.value?.members_count ?? filteredMembers.value.length)

const parseAllowedEmails = () =>
  allowedEmailText.value
    .split(/[,\n;]/)
    .map((email) => email.trim().toLowerCase())
    .filter(Boolean)

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('vi-VN')
}

const formatDateTime = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('vi-VN')
}

const loadRoomDetail = async () => {
  isLoading.value = true
  errorMessage.value = ''
  allowedMembersError.value = ''

  try {
    const [roomData, membersData, assignmentsData] = await Promise.all([
      homeworkApi.getHomeworkRoom(roomId.value),
      homeworkApi.getRoomMembers(roomId.value),
      homeworkApi.getRoomAssignments(roomId.value),
    ])

    room.value = roomData
    members.value = membersData
    assignments.value = assignmentsData
    allowedMembers.value = roomData?.type === 'homework' && Number(roomData.owner_id) === Number(currentUser?.id)
      ? await homeworkApi.getAllowedMembers(roomId.value)
      : []
  } catch (error) {
    errorMessage.value = `Không tải được chi tiết room: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const addAllowedMembers = async () => {
  allowedMembersError.value = ''
  allowedMembersMessage.value = ''

  const emails = parseAllowedEmails()
  if (!emails.length) {
    allowedMembersError.value = 'Bạn cần nhập ít nhất một email.'
    return
  }

  isSavingAllowedMembers.value = true
  try {
    await homeworkApi.addAllowedMembers(roomId.value, emails)
    allowedMembers.value = await homeworkApi.getAllowedMembers(roomId.value)
    allowedEmailText.value = ''
    allowedMembersMessage.value = 'Đã cập nhật danh sách email.'
  } catch (error) {
    allowedMembersError.value = `Không thêm được email: ${error.message}`
  } finally {
    isSavingAllowedMembers.value = false
  }
}

const removeAllowedMember = async (allowedMemberId) => {
  allowedMembersError.value = ''
  allowedMembersMessage.value = ''

  if (!window.confirm('Xóa email này khỏi danh sách được phép tham gia?')) return

  try {
    await homeworkApi.removeAllowedMember(roomId.value, allowedMemberId)
    allowedMembers.value = allowedMembers.value.filter((member) => Number(member.id) !== Number(allowedMemberId))
    allowedMembersMessage.value = 'Đã xóa email khỏi danh sách.'
  } catch (error) {
    allowedMembersError.value = `Không xóa được email: ${error.message}`
  }
}

const removeMember = async (member) => {
  if (!window.confirm(`Xóa thành viên "${member.user?.name || member.user_id}" khỏi Homework room này?`)) return
  try {
    await homeworkApi.removeRoomMember(roomId.value, member.id)
    members.value = members.value.filter((m) => Number(m.id) !== Number(member.id))
  } catch (error) {
    errorMessage.value = `Không xóa được thành viên: ${error.message}`
  }
}

onMounted(loadRoomDetail)
</script>
