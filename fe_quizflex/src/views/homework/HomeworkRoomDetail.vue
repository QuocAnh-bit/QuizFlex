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
            <span class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--muted)]">{{ room.status || 'active' }}</span>
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
                <div>
                  <h3 class="font-black text-[var(--text)]">{{ member.user?.name || `User #${member.user_id}` }}</h3>
                  <p class="mt-1 text-xs font-bold text-[var(--muted)]">{{ member.user?.email || 'Chưa có email' }}</p>
                </div>
                <span class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ member.role || 'member' }}</span>
              </div>
              <p class="mt-3 text-xs font-bold text-[var(--muted)]">Trạng thái: {{ member.status || 'active' }}</p>
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
                    <span class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ assignment.status || 'published' }}</span>
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
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { currentUserStorage, homeworkApi } from '@/services/api'

const route = useRoute()
const roomId = computed(() => route.params.roomId)
const currentUser = currentUserStorage.get()

const room = ref(null)
const members = ref([])
const assignments = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const canManageRoom = computed(() => currentUser?.role === 'admin' || Number(room.value?.owner_id) === Number(currentUser?.id))
const filteredMembers = computed(() => members.value.filter((member) => Number(member.user_id) !== Number(room.value?.owner_id)))
const memberCount = computed(() => room.value?.members_count ?? filteredMembers.value.length)

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

  try {
    const [roomData, membersData, assignmentsData] = await Promise.all([
      homeworkApi.getHomeworkRoom(roomId.value),
      homeworkApi.getRoomMembers(roomId.value),
      homeworkApi.getRoomAssignments(roomId.value),
    ])

    room.value = roomData
    members.value = membersData
    assignments.value = assignmentsData
  } catch (error) {
    errorMessage.value = `Không tải được chi tiết room: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadRoomDetail)
</script>
