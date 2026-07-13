<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--accent)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Management</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ pageHeader.title }}</h1>
          <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">
            {{ pageHeader.subtitle }}
          </p>
        </div>
        <button class="btn-ghost" type="button" :disabled="activeState.loading" @click="reloadActiveTab">
          {{ activeState.loading ? 'Đang tải...' : 'Tải lại' }}
        </button>
      </div>
    </div>

    <div v-if="actionMessage" class="rounded-[1.5rem] border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">
      {{ actionMessage }}
    </div>
    <div v-if="actionError" class="rounded-[1.5rem] border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
      {{ actionError }}
    </div>

    <!-- Tabs filter: Tất cả / Đã xóa -->
    <div class="flex gap-2 border-b border-[var(--border)] pb-3">
      <button
        type="button"
        class="rounded-full px-5 py-2.5 text-sm font-black transition"
        :class="viewMode === 'all' ? 'bg-[var(--chip-active)] text-[var(--primary)] border border-[var(--border-strong)]' : 'text-[var(--muted)] hover:text-[var(--text)] border border-transparent'"
        @click="setViewMode('all')"
      >
        Tất cả
      </button>
      <button
        type="button"
        class="rounded-full px-5 py-2.5 text-sm font-black transition"
        :class="viewMode === 'trash' ? 'bg-[var(--chip-active)] text-[var(--primary)] border border-[var(--border-strong)]' : 'text-[var(--muted)] hover:text-[var(--text)] border border-transparent'"
        @click="setViewMode('trash')"
      >
        Đã xóa
      </button>
    </div>

    <article v-if="activeTab === 'homework'" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <form class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_170px_150px_160px_160px_auto]" @submit.prevent="loadHomework(1)">
        <input v-model.trim="homeworkFilters.search" class="field" type="search" placeholder="Tìm tên, mã phòng, chủ room" />
        <select v-model="homeworkFilters.status" class="field" :disabled="viewMode === 'trash'">
          <option value="">Tất cả trạng thái</option>
          <option value="open">Hoạt động</option>
          <option value="waiting">Đang chờ</option>
          <option value="closed">Đã đóng</option>
          <option value="removed" v-if="viewMode === 'trash'">Đã xóa</option>
        </select>
        <input v-model="homeworkFilters.owner_id" class="field" type="number" min="1" placeholder="Owner ID" />
        <input v-model="homeworkFilters.created_from" class="field" type="date" />
        <input v-model="homeworkFilters.created_to" class="field" type="date" />
        <div class="flex gap-2">
          <button class="btn-primary min-w-24" type="submit">Lọc</button>
          <button class="btn-ghost min-w-24" type="button" @click="resetHomeworkFilters">Reset</button>
        </div>
      </form>

      <div v-if="homeworkState.error" class="mt-5 rounded-[1.5rem] border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
        {{ homeworkState.error }}
      </div>
      <div v-else-if="homeworkState.loading" class="mt-5 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center text-sm font-bold text-[var(--muted)]">
        Đang tải Homework Rooms...
      </div>
      <div v-else class="mt-5 overflow-x-auto scrollbar-soft">
        <table class="min-w-[1180px] w-full border-separate border-spacing-y-2 text-left text-sm">
          <thead class="text-xs font-black uppercase tracking-[0.12em] text-[var(--muted)]">
            <tr v-if="viewMode === 'trash'">
              <th class="px-3 py-2">ID</th>
              <th class="px-3 py-2">Tên phòng</th>
              <th class="px-3 py-2">Chủ room</th>
              <th class="px-3 py-2">Ngày tạo</th>
              <th class="px-3 py-2">Ngày xóa</th>
              <th class="px-3 py-2 text-right">Hành động</th>
            </tr>
            <tr v-else>
              <th class="px-3 py-2">ID</th>
              <th class="px-3 py-2">Tên phòng</th>
              <th class="px-3 py-2">Mã phòng</th>
              <th class="px-3 py-2">Chủ room</th>
              <th class="px-3 py-2">Email chủ room</th>
              <th class="px-3 py-2">Thành viên</th>
              <th class="px-3 py-2">Assignment</th>
              <th class="px-3 py-2">Trạng thái</th>
              <th class="px-3 py-2">Ngày tạo</th>
              <th class="px-3 py-2 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody v-if="viewMode === 'trash'">
            <tr v-for="room in homeworkState.items" :key="room.id" class="rounded-[1.25rem] bg-[var(--surface-soft)] text-[var(--text)]">
              <td class="rounded-l-[1.25rem] px-3 py-4 font-black">#{{ room.id }}</td>
              <td class="px-3 py-4 font-bold">{{ room.name || 'Chưa đặt tên' }}</td>
              <td class="px-3 py-4">{{ room.owner?.name || 'Không rõ' }}</td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ formatDate(room.created_at) }}</td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ formatDateTime(room.deleted_at) }}</td>
              <td class="rounded-r-[1.25rem] px-3 py-4">
                <div class="flex justify-end gap-2">
                  <button class="btn-ghost px-4 py-2 font-black text-[var(--primary)] hover:bg-[var(--surface)] border border-[var(--border)] rounded-full transition" type="button" :disabled="roomActionLoading === `homework:${room.id}`" @click="restoreHomeworkRoom(room)">
                    {{ roomActionLoading === `homework:${room.id}` ? 'Đang khôi phục...' : 'Khôi phục' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr v-for="room in homeworkState.items" :key="room.id" class="rounded-[1.25rem] bg-[var(--surface-soft)] text-[var(--text)]">
              <td class="rounded-l-[1.25rem] px-3 py-4 font-black">#{{ room.id }}</td>
              <td class="px-3 py-4 font-bold">{{ room.name || 'Chưa đặt tên' }}</td>
              <td class="px-3 py-4"><span class="badge">{{ room.code || '-' }}</span></td>
              <td class="px-3 py-4">{{ room.owner?.name || 'Không rõ' }}</td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ room.owner?.email || '-' }}</td>
              <td class="px-3 py-4 font-black">{{ formatNumber(room.member_count) }}</td>
              <td class="px-3 py-4 font-black">{{ formatNumber(room.assignment_count) }}</td>
              <td class="px-3 py-4"><StatusPill :label="homeworkStatusLabel(room.status)" /></td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ formatDate(room.created_at) }}</td>
              <td class="rounded-r-[1.25rem] px-3 py-4">
                <div class="flex justify-end gap-2">
                  <button class="btn-ghost px-4 py-2" type="button" @click="openHomeworkDetail(room.id)">Chi tiết</button>
                  <details class="relative">
                    <summary class="list-none rounded-full border border-[var(--border)] bg-[var(--surface)] px-4 py-2 text-xs font-black text-[var(--text)] transition hover:border-[var(--border-strong)] hover:text-[var(--primary)]">Quản lý</summary>
                    <div class="absolute right-0 z-20 mt-2 grid min-w-44 gap-1 rounded-2xl border border-[var(--border)] bg-[var(--surface-strong)] p-2 shadow-[var(--shadow-card)]">
                      <button v-if="String(room.status).toLowerCase() === 'open'" class="rounded-xl px-3 py-2 text-left text-xs font-black text-[var(--text)] hover:bg-[var(--surface-soft)] disabled:opacity-50" type="button" :disabled="roomActionLoading === `homework:${room.id}`" @click="closeHomeworkRoom(room)">
                        Đóng phòng
                      </button>
                      <button v-else-if="String(room.status).toLowerCase() === 'closed'" class="rounded-xl px-3 py-2 text-left text-xs font-black text-[var(--text)] hover:bg-[var(--surface-soft)] disabled:opacity-50" type="button" :disabled="roomActionLoading === `homework:${room.id}`" @click="reopenHomeworkRoom(room)">
                        Mở phòng
                      </button>
                      <button class="rounded-xl px-3 py-2 text-left text-xs font-black text-rose-300 hover:bg-rose-500/10 disabled:opacity-50" type="button" :disabled="roomActionLoading === `homework:${room.id}` || String(room.status).toLowerCase() === 'removed'" @click="softDeleteHomeworkRoom(room)">
                        Xóa phòng
                      </button>
                    </div>
                  </details>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <EmptyState v-if="homeworkState.items.length === 0" text="Chưa có Homework Room phù hợp." />
      </div>
      <PaginationBar :meta="homeworkState.meta" :loading="homeworkState.loading" @page="loadHomework" />
    </article>

    <article v-if="activeTab === 'live'" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <form class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_170px_150px_160px_160px_auto]" @submit.prevent="loadLive(1)">
        <input v-model.trim="liveFilters.search" class="field" type="search" placeholder="Tìm tên, mã phòng, host" />
        <select v-model="liveFilters.status" class="field" :disabled="viewMode === 'trash'">
          <option value="">Tất cả trạng thái</option>
          <option value="waiting">Đang chờ</option>
          <option value="playing">Đang diễn ra</option>
          <option value="finished">Đã kết thúc</option>
          <option value="removed" v-if="viewMode === 'trash'">Đã xóa</option>
        </select>
        <input v-model="liveFilters.host_id" class="field" type="number" min="1" placeholder="Host ID" />
        <input v-model="liveFilters.created_from" class="field" type="date" />
        <input v-model="liveFilters.created_to" class="field" type="date" />
        <div class="flex gap-2">
          <button class="btn-primary min-w-24" type="submit">Lọc</button>
          <button class="btn-ghost min-w-24" type="button" @click="resetLiveFilters">Reset</button>
        </div>
      </form>

      <div v-if="liveState.error" class="mt-5 rounded-[1.5rem] border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
        {{ liveState.error }}
      </div>
      <div v-else-if="liveState.loading" class="mt-5 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center text-sm font-bold text-[var(--muted)]">
        Đang tải Live Rooms...
      </div>
      <div v-else class="mt-5 overflow-x-auto scrollbar-soft">
        <table class="min-w-[1320px] w-full border-separate border-spacing-y-2 text-left text-sm">
          <thead class="text-xs font-black uppercase tracking-[0.12em] text-[var(--muted)]">
            <tr v-if="viewMode === 'trash'">
              <th class="px-3 py-2">ID</th>
              <th class="px-3 py-2">Tên live room</th>
              <th class="px-3 py-2">Host</th>
              <th class="px-3 py-2">Tạo lúc</th>
              <th class="px-3 py-2">Ngày xóa</th>
              <th class="px-3 py-2 text-right">Hành động</th>
            </tr>
            <tr v-else>
              <th class="px-3 py-2">ID</th>
              <th class="px-3 py-2">Tên live room</th>
              <th class="px-3 py-2">Mã phòng</th>
              <th class="px-3 py-2">Host</th>
              <th class="px-3 py-2">Email host</th>
              <th class="px-3 py-2">Quiz</th>
              <th class="px-3 py-2">Player</th>
              <th class="px-3 py-2">Trạng thái</th>
              <th class="px-3 py-2">Tạo lúc</th>
              <th class="px-3 py-2">Bắt đầu</th>
              <th class="px-3 py-2">Kết thúc</th>
              <th class="px-3 py-2 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody v-if="viewMode === 'trash'">
            <tr v-for="room in liveState.items" :key="room.id" class="rounded-[1.25rem] bg-[var(--surface-soft)] text-[var(--text)]">
              <td class="rounded-l-[1.25rem] px-3 py-4 font-black">#{{ room.id }}</td>
              <td class="px-3 py-4 font-bold">{{ room.title || room.name || 'Chưa đặt tên' }}</td>
              <td class="px-3 py-4">{{ room.host?.name || 'Không rõ' }}</td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ formatDateTime(room.created_at) }}</td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ formatDateTime(room.deleted_at) }}</td>
              <td class="rounded-r-[1.25rem] px-3 py-4">
                <div class="flex justify-end gap-2">
                  <button class="btn-ghost px-4 py-2 font-black text-[var(--primary)] hover:bg-[var(--surface)] border border-[var(--border)] rounded-full transition" type="button" :disabled="roomActionLoading === `live:${room.id}`" @click="restoreLiveRoom(room)">
                    {{ roomActionLoading === `live:${room.id}` ? 'Đang khôi phục...' : 'Khôi phục' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr v-for="room in liveState.items" :key="room.id" class="rounded-[1.25rem] bg-[var(--surface-soft)] text-[var(--text)]">
              <td class="rounded-l-[1.25rem] px-3 py-4 font-black">#{{ room.id }}</td>
              <td class="px-3 py-4 font-bold">{{ room.title || room.name || 'Chưa đặt tên' }}</td>
              <td class="px-3 py-4"><span class="badge">{{ room.code || '-' }}</span></td>
              <td class="px-3 py-4">{{ room.host?.name || 'Không rõ' }}</td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ room.host?.email || '-' }}</td>
              <td class="px-3 py-4">{{ room.quiz?.title || '-' }}</td>
              <td class="px-3 py-4 font-black">{{ formatNumber(room.player_count) }}</td>
              <td class="px-3 py-4"><StatusPill :label="liveStatusLabel(room.status)" /></td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ formatDateTime(room.created_at) }}</td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ formatDateTime(room.started_at) }}</td>
              <td class="px-3 py-4 text-[var(--muted)]">{{ formatDateTime(room.finished_at || room.ended_at) }}</td>
              <td class="rounded-r-[1.25rem] px-3 py-4">
                <div class="flex justify-end gap-2">
                  <button class="btn-ghost px-4 py-2" type="button" @click="openLiveDetail(room.id)">Chi tiết</button>
                  <details class="relative">
                    <summary class="list-none rounded-full border border-[var(--border)] bg-[var(--surface)] px-4 py-2 text-xs font-black text-[var(--text)] transition hover:border-[var(--border-strong)] hover:text-[var(--primary)]">Quản lý</summary>
                    <div class="absolute right-0 z-20 mt-2 grid min-w-48 gap-1 rounded-2xl border border-[var(--border)] bg-[var(--surface-strong)] p-2 shadow-[var(--shadow-card)]">
                      <button class="rounded-xl px-3 py-2 text-left text-xs font-black text-[var(--text)] hover:bg-[var(--surface-soft)] disabled:opacity-50" type="button" :disabled="roomActionLoading === `live:${room.id}` || ['finished', 'removed'].includes(String(room.status).toLowerCase())" @click="closeLiveRoom(room)">
                        Đóng/Kết thúc phòng
                      </button>
                      <button class="rounded-xl px-3 py-2 text-left text-xs font-black text-rose-300 hover:bg-rose-500/10 disabled:opacity-50" type="button" :disabled="roomActionLoading === `live:${room.id}` || String(room.status).toLowerCase() === 'removed'" @click="softDeleteLiveRoom(room)">
                        Xóa phòng
                      </button>
                    </div>
                  </details>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <EmptyState v-if="liveState.items.length === 0" text="Chưa có Live Room phù hợp." />
      </div>
      <PaginationBar :meta="liveState.meta" :loading="liveState.loading" @page="loadLive" />
    </article>

    <div v-if="detailOpen" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm" @click.self="closeDetail">
      <aside class="ml-auto flex h-full w-full max-w-5xl flex-col border-l border-[var(--border)] bg-[var(--surface-strong)] shadow-[var(--shadow-soft)]">
        <header class="flex items-start justify-between gap-4 border-b border-[var(--border)] p-5">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ detailType === 'homework' ? 'Homework Room' : 'Live Room' }}</p>
            <h2 class="mt-1 text-2xl font-black text-[var(--text)]">{{ detailTitle }}</h2>
            <p class="mt-1 text-sm font-bold text-[var(--muted)]">{{ detailCode }}</p>
          </div>
          <button class="btn-ghost px-4 py-2" type="button" @click="closeDetail">Đóng</button>
        </header>

        <div v-if="detailLoading" class="grid flex-1 place-items-center p-8 text-sm font-bold text-[var(--muted)]">Đang tải chi tiết...</div>
        <div v-else-if="detailError" class="m-5 rounded-[1.5rem] border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ detailError }}</div>
        <div v-else class="min-h-0 flex-1 overflow-y-auto p-5 scrollbar-soft">
          <div class="mb-5 flex flex-wrap gap-2">
            <button
              v-for="tab in detailTabs"
              :key="tab.value"
              type="button"
              class="rounded-full border px-4 py-2 text-sm font-black transition"
              :class="detailTab === tab.value ? 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--primary)]' : 'border-[var(--border)] text-[var(--muted)] hover:text-[var(--text)]'"
              @click="detailTab = tab.value"
            >
              {{ tab.label }}
            </button>
          </div>

          <HomeworkDetail v-if="detailType === 'homework'" :detail="detailData" :tab="detailTab" :member-action-loading="memberActionLoading" @remove-member="removeHomeworkMemberFromDetail" />
          <LiveDetail v-if="detailType === 'live'" :detail="detailData" :tab="detailTab" />
        </div>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref } from 'vue'
import { adminRoomApi } from '@/services/api'

const props = defineProps({
  roomType: {
    type: String,
    default: 'homework',
    validator: (value) => ['homework', 'live'].includes(value),
  },
})

const homeworkDetailTabs = [
  { label: 'Thông tin phòng', value: 'info' },
  { label: 'Thành viên', value: 'members' },
  { label: 'Bài tập đã giao', value: 'assignments' },
  { label: 'Lượt nộp bài', value: 'submissions' },
]

const liveDetailTabs = [
  { label: 'Thông tin live room', value: 'info' },
  { label: 'Người chơi', value: 'players' },
  { label: 'Leaderboard', value: 'leaderboard' },
  { label: 'Câu trả lời', value: 'answers' },
  { label: 'Realtime status', value: 'realtime' },
]

const makeListState = () => ({
  items: [],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: null, to: null },
  loading: false,
  error: '',
})

const activeTab = computed(() => props.roomType)
const homeworkState = reactive(makeListState())
const liveState = reactive(makeListState())
const homeworkFilters = reactive({ search: '', status: '', owner_id: '', created_from: '', created_to: '' })
const liveFilters = reactive({ search: '', status: '', host_id: '', created_from: '', created_to: '' })

const detailOpen = ref(false)
const detailType = ref('homework')
const detailTab = ref('info')
const detailData = ref(null)
const detailLoading = ref(false)
const detailError = ref('')
const actionMessage = ref('')
const actionError = ref('')
const roomActionLoading = ref('')
const memberActionLoading = ref('')

const viewMode = ref('all') // 'all' or 'trash'

const setViewMode = (mode) => {
  viewMode.value = mode
  if (activeTab.value === 'homework') {
    loadHomework(1)
  } else {
    loadLive(1)
  }
}

const activeState = computed(() => activeTab.value === 'homework' ? homeworkState : liveState)
const pageHeader = computed(() => activeTab.value === 'homework'
  ? {
      title: 'Homework Rooms',
      subtitle: 'Quản lý phòng giao bài, thành viên, assignment và lượt nộp.',
    }
  : {
      title: 'Live Rooms',
      subtitle: 'Quản lý phòng live, người chơi, leaderboard và câu trả lời.',
    })
const detailTabs = computed(() => detailType.value === 'homework' ? homeworkDetailTabs : liveDetailTabs)
const detailTitle = computed(() => {
  const room = detailData.value?.room
  return room?.name || room?.title || 'Chi tiết phòng'
})
const detailCode = computed(() => detailData.value?.room?.code ? `Mã phòng: ${detailData.value.room.code}` : '')

const cleanParams = (filters, page) => {
  const params = { page, per_page: 10 }
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) params[key] = value
  })
  return params
}

const assignListState = (state, payload) => {
  state.items = Array.isArray(payload?.items) ? payload.items : []
  state.meta = payload?.meta || makeListState().meta
}

const loadHomework = async (page = homeworkState.meta.current_page || 1) => {
  homeworkState.loading = true
  homeworkState.error = ''
  try {
    const apiCall = viewMode.value === 'trash' ? adminRoomApi.getHomeworkRoomsTrash : adminRoomApi.getHomeworkRooms
    const payload = await apiCall(cleanParams(homeworkFilters, page))
    assignListState(homeworkState, payload)
  } catch (error) {
    homeworkState.error = error.message || 'Không tải được Homework Rooms.'
  } finally {
    homeworkState.loading = false
  }
}

const loadLive = async (page = liveState.meta.current_page || 1) => {
  liveState.loading = true
  liveState.error = ''
  try {
    const apiCall = viewMode.value === 'trash' ? adminRoomApi.getLiveRoomsTrash : adminRoomApi.getLiveRooms
    const payload = await apiCall(cleanParams(liveFilters, page))
    assignListState(liveState, payload)
  } catch (error) {
    liveState.error = error.message || 'Không tải được Live Rooms.'
  } finally {
    liveState.loading = false
  }
}

const loadActiveTab = () => {
  const tab = activeTab.value
  if (tab === 'homework' && homeworkState.items.length === 0 && !homeworkState.loading) loadHomework()
  if (tab === 'live' && liveState.items.length === 0 && !liveState.loading) loadLive()
}

const reloadActiveTab = () => {
  if (activeTab.value === 'homework') loadHomework()
  else loadLive()
}

const resetHomeworkFilters = () => {
  Object.assign(homeworkFilters, { search: '', status: '', owner_id: '', created_from: '', created_to: '' })
  loadHomework(1)
}

const resetLiveFilters = () => {
  Object.assign(liveFilters, { search: '', status: '', host_id: '', created_from: '', created_to: '' })
  loadLive(1)
}

const openHomeworkDetail = async (id) => {
  detailOpen.value = true
  detailType.value = 'homework'
  detailTab.value = 'info'
  detailData.value = null
  detailLoading.value = true
  detailError.value = ''
  try {
    detailData.value = await adminRoomApi.getHomeworkRoomDetail(id)
  } catch (error) {
    detailError.value = error.message || 'Không tải được chi tiết Homework Room.'
  } finally {
    detailLoading.value = false
  }
}

const openLiveDetail = async (id) => {
  detailOpen.value = true
  detailType.value = 'live'
  detailTab.value = 'info'
  detailData.value = null
  detailLoading.value = true
  detailError.value = ''
  try {
    detailData.value = await adminRoomApi.getLiveRoomDetail(id)
  } catch (error) {
    detailError.value = error.message || 'Không tải được chi tiết Live Room.'
  } finally {
    detailLoading.value = false
  }
}

const closeDetail = () => {
  detailOpen.value = false
  detailData.value = null
  detailError.value = ''
}

const updateListRoom = (state, updatedRoom) => {
  if (!updatedRoom?.id) return
  const index = state.items.findIndex((room) => Number(room.id) === Number(updatedRoom.id))
  if (index !== -1) state.items[index] = { ...state.items[index], ...updatedRoom }
}

const removeListRoom = (state, roomId) => {
  state.items = state.items.filter((room) => Number(room.id) !== Number(roomId))
  if (state.meta?.total) state.meta.total = Math.max(0, Number(state.meta.total) - 1)
}

const resetActionNotice = () => {
  actionMessage.value = ''
  actionError.value = ''
}

const closeHomeworkRoom = async (room) => {
  resetActionNotice()
  if (!window.confirm('Đóng Homework room này? Dữ liệu assignment, submission và thành viên vẫn được giữ lại.')) return

  roomActionLoading.value = `homework:${room.id}`
  try {
    const updatedRoom = await adminRoomApi.closeHomeworkRoom(room.id)
    updateListRoom(homeworkState, updatedRoom)
    if (detailData.value?.room && Number(detailData.value.room.id) === Number(room.id)) {
      detailData.value = { ...detailData.value, room: { ...detailData.value.room, ...updatedRoom } }
    }
    actionMessage.value = 'Đã đóng Homework room.'
  } catch (error) {
    actionError.value = error.message || 'Không đóng được Homework room.'
  } finally {
    roomActionLoading.value = ''
  }
}

const reopenHomeworkRoom = async (room) => {
  resetActionNotice()
  if (!window.confirm('Mở lại Homework room này?')) return

  roomActionLoading.value = `homework:${room.id}`
  try {
    const updatedRoom = await adminRoomApi.reopenHomeworkRoom(room.id)
    updateListRoom(homeworkState, updatedRoom)
    if (detailData.value?.room && Number(detailData.value.room.id) === Number(room.id)) {
      detailData.value = { ...detailData.value, room: { ...detailData.value.room, ...updatedRoom } }
    }
    actionMessage.value = 'Đã mở lại Homework room.'
  } catch (error) {
    actionError.value = error.message || 'Không mở lại được Homework room.'
  } finally {
    roomActionLoading.value = ''
  }
}

const softDeleteHomeworkRoom = async (room) => {
  resetActionNotice()
  if (!window.confirm('Xóa mềm Homework room này? Phòng sẽ bị ẩn khỏi danh sách mặc định nhưng dữ liệu vẫn được giữ lại.')) return

  roomActionLoading.value = `homework:${room.id}`
  try {
    await adminRoomApi.softDeleteHomeworkRoom(room.id)
    removeListRoom(homeworkState, room.id)
    if (detailData.value?.room && Number(detailData.value.room.id) === Number(room.id)) closeDetail()
    actionMessage.value = 'Đã xóa mềm Homework room.'
  } catch (error) {
    actionError.value = error.message || 'Không xóa mềm được Homework room.'
  } finally {
    roomActionLoading.value = ''
  }
}

const closeLiveRoom = async (room) => {
  resetActionNotice()
  if (!window.confirm('Đóng/Kết thúc Live room này? Dữ liệu player và câu trả lời vẫn được giữ lại.')) return

  roomActionLoading.value = `live:${room.id}`
  try {
    const updatedRoom = await adminRoomApi.closeLiveRoom(room.id)
    updateListRoom(liveState, updatedRoom)
    if (detailData.value?.room && Number(detailData.value.room.id) === Number(room.id)) {
      detailData.value = { ...detailData.value, room: { ...detailData.value.room, ...updatedRoom } }
    }
    actionMessage.value = 'Đã kết thúc Live room.'
  } catch (error) {
    actionError.value = error.message || 'Không kết thúc được Live room.'
  } finally {
    roomActionLoading.value = ''
  }
}

const softDeleteLiveRoom = async (room) => {
  resetActionNotice()
  if (!window.confirm('Xóa mềm Live room này? Dữ liệu player và câu trả lời vẫn được giữ lại.')) return

  roomActionLoading.value = `live:${room.id}`
  try {
    await adminRoomApi.softDeleteLiveRoom(room.id)
    removeListRoom(liveState, room.id)
    if (detailData.value?.room && Number(detailData.value.room.id) === Number(room.id)) closeDetail()
    actionMessage.value = 'Đã xóa mềm Live room.'
  } catch (error) {
    actionError.value = error.message || 'Không xóa mềm được Live room.'
  } finally {
    roomActionLoading.value = ''
  }
}

const restoreHomeworkRoom = async (room) => {
  resetActionNotice()
  if (!window.confirm(`Khôi phục Homework room "${room.name || room.id}"?`)) return

  roomActionLoading.value = `homework:${room.id}`
  try {
    await adminRoomApi.restoreHomeworkRoom(room.id)
    removeListRoom(homeworkState, room.id)
    actionMessage.value = 'Đã khôi phục Homework room.'
  } catch (error) {
    actionError.value = error.message || 'Không khôi phục được Homework room.'
  } finally {
    roomActionLoading.value = ''
  }
}

const restoreLiveRoom = async (room) => {
  resetActionNotice()
  if (!window.confirm(`Khôi phục Live room "${room.title || room.id}"?`)) return

  roomActionLoading.value = `live:${room.id}`
  try {
    await adminRoomApi.restoreLiveRoom(room.id)
    removeListRoom(liveState, room.id)
    actionMessage.value = 'Đã khôi phục Live room.'
  } catch (error) {
    actionError.value = error.message || 'Không khôi phục được Live room.'
  } finally {
    roomActionLoading.value = ''
  }
}

const removeHomeworkMemberFromDetail = async (member) => {
  resetActionNotice()
  const room = detailData.value?.room
  if (!room || !member?.id) return
  if (!window.confirm('Xóa thành viên này khỏi Homework room? Bài nộp và dữ liệu cũ vẫn được giữ lại.')) return

  memberActionLoading.value = String(member.id)
  try {
    await adminRoomApi.removeHomeworkRoomMember(room.id, member.id)
    const remainingMembers = (detailData.value?.members || []).filter((item) => Number(item.id) !== Number(member.id))
    const nextCount = remainingMembers.filter((item) => String(item.role).toLowerCase() !== 'owner').length
    detailData.value = {
      ...detailData.value,
      members: remainingMembers,
      room: { ...room, member_count: nextCount },
    }
    updateListRoom(homeworkState, { id: room.id, member_count: nextCount })
    actionMessage.value = 'Đã xóa thành viên khỏi Homework room.'
  } catch (error) {
    actionError.value = error.message || 'Không xóa được thành viên khỏi Homework room.'
  } finally {
    memberActionLoading.value = ''
  }
}

const formatNumber = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0))

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('vi-VN')
}

const formatDateTime = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
}

const formatDuration = (minutes) => {
  if (!minutes) return '-'
  return `${formatNumber(minutes)} phút`
}

const homeworkStatusLabel = (status) => ({
  active: 'Hoạt động',
  waiting: 'Hoạt động',
  open: 'Hoạt động',
  closed: 'Đã đóng',
  removed: 'Đã xóa',
  archived: 'Đã lưu trữ',
  finished: 'Đã kết thúc',
})[String(status || '').toLowerCase()] || (status || '-')

const liveStatusLabel = (status) => ({
  waiting: 'Đang chờ',
  playing: 'Đang diễn ra',
  active: 'Đang diễn ra',
  finished: 'Đã kết thúc',
  removed: 'Đã xóa',
  cancelled: 'Đã hủy',
})[String(status || '').toLowerCase()] || (status || '-')

const roomRoleLabel = (role) => ({
  owner: 'Chủ phòng',
  student: 'Thành viên',
  member: 'Thành viên',
})[String(role || '').toLowerCase()] || (role || '-')

const boolLabel = (value) => value ? 'Đã hoàn thành' : 'Chưa hoàn thành'

const StatusPill = defineComponent({
  props: { label: { type: String, default: '-' } },
  setup(props) {
    return () => h('span', {
      class: 'inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]',
    }, props.label)
  },
})

const EmptyState = defineComponent({
  props: { text: { type: String, required: true } },
  setup(props) {
    return () => h('div', {
      class: 'rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center text-sm font-bold text-[var(--muted)]',
    }, props.text)
  },
})

const PaginationBar = defineComponent({
  props: {
    meta: { type: Object, required: true },
    loading: { type: Boolean, default: false },
  },
  emits: ['page'],
  setup(props, { emit }) {
    const go = (page) => {
      if (props.loading) return
      if (page < 1 || page > Number(props.meta?.last_page || 1)) return
      emit('page', page)
    }

    return () => {
      const currentPage = Number(props.meta?.current_page || 1)
      const lastPage = Number(props.meta?.last_page || 1)
      return h('div', { class: 'mt-5 flex flex-col gap-3 border-t border-[var(--border)] pt-5 text-sm font-bold text-[var(--muted)] md:flex-row md:items-center md:justify-between' }, [
        h('span', {}, `Hiển thị ${props.meta?.from || 0}-${props.meta?.to || 0} / ${formatNumber(props.meta?.total || 0)}`),
        h('div', { class: 'flex gap-2' }, [
          h('button', {
            class: 'btn-ghost px-4 py-2 disabled:opacity-50',
            type: 'button',
            disabled: props.loading || currentPage <= 1,
            onClick: () => go(currentPage - 1),
          }, 'Trước'),
          h('span', { class: 'grid min-w-28 place-items-center rounded-full border border-[var(--border)] px-4 py-2 text-[var(--text)]' }, `${currentPage} / ${lastPage}`),
          h('button', {
            class: 'btn-ghost px-4 py-2 disabled:opacity-50',
            type: 'button',
            disabled: props.loading || currentPage >= lastPage,
            onClick: () => go(currentPage + 1),
          }, 'Sau'),
        ]),
      ])
    }
  },
})

const InfoGrid = defineComponent({
  props: { rows: { type: Array, required: true } },
  setup(props) {
    return () => h('div', { class: 'grid gap-3 md:grid-cols-2' }, props.rows.map((row) =>
      h('div', { class: 'rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4' }, [
        h('p', { class: 'text-xs font-black uppercase tracking-[0.12em] text-[var(--muted)]' }, row.label),
        h('b', { class: 'mt-2 block break-words text-[var(--text)]' }, row.value || '-'),
      ]),
    ))
  },
})

const SimpleTable = defineComponent({
  props: {
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    empty: { type: String, default: 'Chưa có dữ liệu.' },
  },
  setup(props) {
    return () => props.rows.length
      ? h('div', { class: 'overflow-x-auto scrollbar-soft' }, [
        h('table', { class: 'min-w-[900px] w-full border-separate border-spacing-y-2 text-left text-sm' }, [
          h('thead', { class: 'text-xs font-black uppercase tracking-[0.12em] text-[var(--muted)]' }, [
            h('tr', {}, props.columns.map((column) => h('th', { class: 'px-3 py-2' }, column.label))),
          ]),
          h('tbody', {}, props.rows.map((row) => h('tr', { class: 'bg-[var(--surface-soft)] text-[var(--text)]' }, props.columns.map((column, index) =>
            h('td', { class: `${index === 0 ? 'rounded-l-[1.25rem]' : ''} ${index === props.columns.length - 1 ? 'rounded-r-[1.25rem]' : ''} px-3 py-4` }, column.format ? column.format(row) : row[column.key] || '-'),
          )))),
        ]),
      ])
      : h(EmptyState, { text: props.empty })
  },
})

const HomeworkDetail = defineComponent({
  props: {
    detail: { type: Object, default: null },
    tab: { type: String, required: true },
  },
  setup(props) {
    return () => {
      const room = props.detail?.room || {}
      if (props.tab === 'info') {
        return h(InfoGrid, {
          rows: [
            { label: 'Tên phòng', value: room.name },
            { label: 'Mã phòng', value: room.code },
            { label: 'Mô tả', value: room.description || '-' },
            { label: 'Chủ room', value: room.owner?.name },
            { label: 'Email chủ room', value: room.owner?.email },
            { label: 'Trạng thái', value: homeworkStatusLabel(room.status) },
            { label: 'Ngày tạo', value: formatDateTime(room.created_at) },
            { label: 'Số thành viên', value: formatNumber(room.member_count) },
            { label: 'Số bài tập đã giao', value: formatNumber(room.assignment_count) },
            { label: 'Chính sách tham gia', value: room.join_policy || 'open' },
          ],
        })
      }

      if (props.tab === 'members') {
        return h(SimpleTable, {
          rows: props.detail?.members || [],
          empty: 'Chưa có thành viên.',
          columns: [
            { label: 'Tên user', format: (row) => row.name || row.user?.name || '-' },
            { label: 'Email', format: (row) => row.email || row.user?.email || '-' },
            { label: 'Vai trò trong room', format: (row) => roomRoleLabel(row.role) },
            { label: 'Trạng thái', format: (row) => row.status || '-' },
            { label: 'Ngày tham gia', format: (row) => formatDateTime(row.joined_at) },
          ],
        })
      }

      if (props.tab === 'assignments') {
        return h(SimpleTable, {
          rows: props.detail?.assignments || [],
          empty: 'Chưa có bài tập đã giao.',
          columns: [
            { label: 'Tên assignment', format: (row) => row.title || '-' },
            { label: 'Quiz', format: (row) => row.quiz?.title || '-' },
            { label: 'Người giao', format: (row) => row.assigner?.name || '-' },
            { label: 'Deadline', format: (row) => formatDateTime(row.deadline_at) },
            { label: 'Thời lượng', format: (row) => formatDuration(row.duration_minutes) },
            { label: 'Số lần làm tối đa', format: (row) => formatNumber(row.max_attempts) },
            { label: 'Số lượt nộp', format: (row) => formatNumber(row.submission_count) },
            { label: 'Trạng thái', format: (row) => row.status || '-' },
          ],
        })
      }

      return h(SimpleTable, {
        rows: props.detail?.submissions || [],
        empty: 'Chưa có lượt nộp bài.',
        columns: [
          { label: 'Tên học viên', format: (row) => row.student?.name || '-' },
          { label: 'Assignment', format: (row) => row.assignment?.title || '-' },
          { label: 'Điểm', format: (row) => formatNumber(row.score) },
          { label: 'Tổng điểm', format: (row) => formatNumber(row.total_points) },
          { label: 'Trạng thái', format: (row) => row.status || '-' },
          { label: 'Thời gian bắt đầu', format: (row) => formatDateTime(row.started_at) },
          { label: 'Thời gian nộp', format: (row) => formatDateTime(row.submitted_at || row.finished_at) },
        ],
      })
    }
  },
})

const LiveDetail = defineComponent({
  props: {
    detail: { type: Object, default: null },
    tab: { type: String, required: true },
  },
  setup(props) {
    return () => {
      const room = props.detail?.room || {}
      if (props.tab === 'info') {
        return h(InfoGrid, {
          rows: [
            { label: 'Tên live room', value: room.title || room.name },
            { label: 'Mã phòng', value: room.code },
            { label: 'Host', value: room.host?.name },
            { label: 'Email host', value: room.host?.email },
            { label: 'Quiz', value: room.quiz?.title },
            { label: 'Trạng thái', value: liveStatusLabel(room.status) },
            { label: 'Số player', value: formatNumber(room.player_count) },
            { label: 'Thời gian tạo', value: formatDateTime(room.created_at) },
            { label: 'Thời gian bắt đầu', value: formatDateTime(room.started_at) },
            { label: 'Thời gian kết thúc', value: formatDateTime(room.finished_at || room.ended_at) },
          ],
        })
      }

      if (props.tab === 'players') {
        return h(SimpleTable, {
          rows: props.detail?.players || [],
          empty: 'Chưa có người chơi.',
          columns: [
            { label: 'Tên người chơi', format: (row) => row.name || row.user?.name || '-' },
            { label: 'Email', format: (row) => row.email || row.user?.email || '-' },
            { label: 'Điểm', format: (row) => formatNumber(row.score) },
            { label: 'Số câu đúng', format: (row) => formatNumber(row.correct_count) },
            { label: 'Câu hiện tại', format: (row) => formatNumber(row.current_question_index) },
            { label: 'Hoàn thành', format: (row) => boolLabel(row.is_finished) },
            { label: 'Trả lời gần nhất', format: (row) => formatDateTime(row.last_answered_at) },
          ],
        })
      }

      if (props.tab === 'leaderboard') {
        return h(SimpleTable, {
          rows: props.detail?.leaderboard || [],
          empty: 'Chưa có leaderboard.',
          columns: [
            { label: 'Hạng', format: (row) => `#${row.rank}` },
            { label: 'Tên người chơi', format: (row) => row.name || row.user?.name || '-' },
            { label: 'Điểm', format: (row) => formatNumber(row.score) },
            { label: 'Số câu đúng', format: (row) => formatNumber(row.correct_count) },
            { label: 'Số câu đã trả lời', format: (row) => formatNumber(row.answered_count) },
            { label: 'Thời gian hoàn thành', format: (row) => formatDateTime(row.finished_at) },
          ],
        })
      }

      if (props.tab === 'answers') {
        return h(SimpleTable, {
          rows: props.detail?.answers || [],
          empty: 'Backend chưa có câu trả lời phù hợp hoặc room chưa có lượt trả lời.',
          columns: [
            { label: 'Người chơi', format: (row) => row.player?.name || '-' },
            { label: 'Câu hỏi', format: (row) => row.question?.content || '-' },
            { label: 'Câu trả lời', format: (row) => row.answer?.content || '-' },
            { label: 'Đúng / Sai', format: (row) => row.is_correct ? 'Đúng' : 'Sai' },
            { label: 'Thời gian trả lời', format: (row) => formatDateTime(row.answered_at) },
          ],
        })
      }

      const realtime = props.detail?.realtime_status || {}
      return h(InfoGrid, {
        rows: [
          { label: 'Trạng thái phòng', value: liveStatusLabel(realtime.room_status) },
          { label: 'Player đã hoàn thành', value: formatNumber(realtime.finished_players) },
          { label: 'Player chưa hoàn thành', value: formatNumber(realtime.unfinished_players) },
          { label: 'Lần cập nhật gần nhất', value: formatDateTime(realtime.last_updated_at) },
          { label: 'Ghi chú', value: realtime.note || 'Không có dấu hiệu bất thường từ dữ liệu hiện tại.' },
        ],
      })
    }
  },
})

onMounted(() => {
  loadActiveTab()
})
</script>
