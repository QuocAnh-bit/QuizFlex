<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-500">
          <BookOpen class="h-5 w-5" />
        </div>
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Quản trị hệ thống</p>
          <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Phòng bài tập</h1>
          <p class="mt-1 text-sm font-medium text-[var(--muted)]">Quản lý tất cả phòng bài tập trên hệ thống.</p>
        </div>
      </div>
      <button
        class="btn-secondary text-xs px-3.5 py-1.5 inline-flex items-center gap-1.5"
        type="button"
        :disabled="listState.loading"
        @click="refreshData"
      >
        <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': listState.loading }" />
        <span>Làm mới</span>
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <article class="card p-5 card-hover flex items-start justify-between gap-3">
        <div class="space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Tổng số phòng bài tập</span>
          <div v-if="statsLoading" class="h-7 w-16 animate-pulse rounded-md bg-slate-200"></div>
          <strong v-else class="text-2xl font-black text-slate-900 block">{{ formatNumber(stats.homework_total) }}</strong>
          <span class="text-[11px] text-slate-500 font-medium block">Tất cả phòng bài tập</span>
        </div>
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600">
          <BookOpen class="h-4 w-4" />
        </div>
      </article>

      <article class="card p-5 card-hover flex items-start justify-between gap-3">
        <div class="space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Đang hoạt động</span>
          <div v-if="statsLoading" class="h-7 w-12 animate-pulse rounded-md bg-amber-100"></div>
          <strong v-else class="text-2xl font-black text-amber-600 block">{{ formatNumber(stats.active_total) }}</strong>
          <span v-if="statsLoading" class="mt-1 block h-3 w-20 animate-pulse rounded bg-slate-100"></span>
          <span v-else class="text-[11px] text-slate-500 font-medium block">{{ stats.active_percent }}% tổng số</span>
        </div>
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
          <Users class="h-4 w-4" />
        </div>
      </article>

      <article class="card p-5 card-hover flex items-start justify-between gap-3">
        <div class="space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Số phòng bị khóa</span>
          <div v-if="statsLoading" class="h-7 w-12 animate-pulse rounded-md bg-slate-200"></div>
          <strong v-else class="text-2xl font-black text-slate-900 block">{{ formatNumber(stats.banned_total) }}</strong>
          <span class="text-[11px] text-slate-500 font-medium block">Đang bị khóa (banned)</span>
        </div>
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-[#7C3AED]">
          <Lock class="h-4 w-4" />
        </div>
      </article>

      <article class="card p-5 card-hover flex items-start justify-between gap-3">
        <div class="space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Trong thùng rác</span>
          <div v-if="statsLoading" class="h-7 w-12 animate-pulse rounded-md bg-red-100"></div>
          <strong v-else class="text-2xl font-black text-red-600 block">{{ formatNumber(stats.trash_total) }}</strong>
          <span v-if="statsLoading" class="mt-1 block h-3 w-20 animate-pulse rounded bg-slate-100"></span>
          <span v-else class="text-[11px] text-slate-500 font-medium block">{{ stats.trash_percent }}% tổng số</span>
        </div>
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-600">
          <Trash2 class="h-4 w-4" />
        </div>
      </article>
    </div>

    <!-- Main Content Container -->
    <article class="card overflow-hidden">
      <!-- Tabs Navigation & Filter/Search bar trên cùng 1 hàng -->
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between border-b border-slate-100 bg-slate-50/70 p-3 px-5 text-xs font-bold">
        <!-- Tabs Navigation -->
        <div class="flex items-center gap-2 overflow-x-auto">
          <button
            type="button"
            class="rounded-lg px-3.5 py-1.5 transition inline-flex items-center gap-1.5 whitespace-nowrap"
            :class="viewMode === 'all' ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60'"
            @click="setViewMode('all')"
          >
            <LayoutGrid class="h-3.5 w-3.5" />
            <span>Tất cả phòng bài tập</span>
          </button>
          <button
            type="button"
            class="relative rounded-lg px-3.5 py-1.5 transition inline-flex items-center gap-1.5 whitespace-nowrap"
            :class="viewMode === 'trash' ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60'"
            @click="setViewMode('trash')"
          >
            <Trash2 class="h-3.5 w-3.5" />
            <span>Thùng rác</span>
            <span v-if="stats.trash_total > 0" class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white leading-none">{{ stats.trash_total }}</span>
          </button>
        </div>

        <!-- Filter/Search bar -->
        <form class="flex flex-wrap items-center gap-2" @submit.prevent="loadRooms(1)">
          <select
            v-model="statusFilter"
            class="field text-xs w-full sm:w-40"
            :disabled="viewMode === 'trash'"
            @change="loadRooms(1)"
          >
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang hoạt động</option>
            <option value="waiting">Chờ bắt đầu</option>
            <option value="finished">Đã kết thúc</option>
            <option value="banned">Bị khóa (Banned)</option>
          </select>

          <div class="relative flex-1 sm:w-56">
            <Search class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
            <input
              v-model.trim="searchQuery"
              class="field text-xs pl-8 w-full"
              type="search"
              placeholder="Tìm theo tên phòng, mã phòng, host..."
            />
          </div>

          <button class="btn-primary text-xs px-3 py-2 inline-flex items-center justify-center gap-1.5 shrink-0" type="submit" :disabled="listState.loading">
            <Search class="h-3.5 w-3.5" />
            <span>Tìm kiếm</span>
          </button>
        </form>
      </div>

      <div class="p-5 space-y-4">

      <!-- Info Alert Banner for Trash mode -->
      <div v-if="viewMode === 'trash'" class="flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50 p-3.5 text-xs font-bold text-blue-700">
        <div class="flex items-center gap-2">
          <Info class="h-4 w-4 shrink-0" />
          <span>Các phòng trong thùng rác vẫn có thể khôi phục.</span>
        </div>
        <button class="btn-secondary text-[11px] px-2.5 py-1" type="button" @click="loadRooms()">
          Làm mới
        </button>
      </div>

      <!-- Error / Loading -->
      <div v-if="listState.error" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700 flex items-start gap-2">
        <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" /> <span>{{ listState.error }}</span>
      </div>

      <div v-else-if="listState.loading" class="rounded-xl border border-slate-100 bg-slate-50 p-12 text-center text-xs text-slate-400 flex items-center justify-center gap-2">
        <RefreshCw class="h-4 w-4 animate-spin text-[#7C3AED]" />
        <span>Đang tải danh sách phòng...</span>
      </div>

      <div v-else class="overflow-x-auto rounded-xl border border-slate-100">
        <!-- ALL ROOMS TABLE -->
        <table v-if="viewMode === 'all'" class="w-full min-w-[900px] text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 uppercase text-[10px] font-bold">
              <th class="py-3 px-3 w-10">#</th>
              <th class="py-3 px-3">Tên phòng</th>
              <th class="py-3 px-3">Chủ phòng</th>
              <th class="py-3 px-3">Mã phòng</th>
              <th class="py-3 px-3">Trạng thái</th>
              <th class="py-3 px-3">Thành viên</th>
              <th class="py-3 px-3 hidden lg:table-cell">Tạo lúc</th>
              <th class="py-3 px-3 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="(room, index) in listState.items" :key="`hw-${room.id}`" class="hover:bg-slate-50 transition-colors">
              <td class="py-3.5 px-3 font-bold text-[11px] text-slate-400">
                {{ (listState.meta.current_page - 1) * 10 + index + 1 }}
              </td>

              <td class="py-3.5 px-3 max-w-xs">
                <p class="font-bold text-slate-900 line-clamp-1 hover:text-[#7C3AED] cursor-pointer" @click="openDetail(room)">
                  {{ room.name || room.title || "Chưa đặt tên" }}
                </p>
                <p v-if="room.description" class="mt-0.5 text-[11px] text-slate-400 line-clamp-1">
                  {{ room.description }}
                </p>
              </td>

              <td class="py-3.5 px-3">
                <p class="font-bold text-slate-900">{{ room.host?.name || "Không rõ" }}</p>
                <p class="text-[10px] text-slate-400">{{ room.host?.email || "-" }}</p>
              </td>

              <td class="py-3.5 px-3 font-mono text-xs">
                <div class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">
                  <span class="font-bold tracking-wider">{{ room.code || "-" }}</span>
                  <button
                    v-if="room.code"
                    type="button"
                    class="text-slate-400 transition-colors hover:text-[#7C3AED] focus:outline-none"
                    :title="copiedCodeMap[room.code] ? 'Đã sao chép!' : 'Sao chép mã phòng'"
                    aria-label="Sao chép mã phòng"
                    @click="copyRoomCode(room.code)"
                  >
                    <Check v-if="copiedCodeMap[room.code]" class="h-3.5 w-3.5 text-emerald-600" />
                    <Copy v-else class="h-3.5 w-3.5" />
                  </button>
                </div>
              </td>

              <td class="py-3.5 px-3">
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold" :class="getStatusPillClass(room)">
                  {{ getStatusLabel(room) }}
                </span>
              </td>

              <td class="py-3.5 px-3">
                <div class="inline-flex items-center gap-1.5 font-bold text-slate-700">
                  <Users class="h-3.5 w-3.5 text-slate-400" />
                  <span>{{ formatMemberCount(room) }}</span>
                </div>
              </td>

              <td class="py-3.5 px-3 text-slate-400 text-[11px] hidden lg:table-cell">
                {{ formatDateTime(room.created_at) }}
              </td>

              <td class="py-3.5 px-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="btn-secondary text-[11px] px-2.5 py-1 inline-flex items-center gap-1"
                    @click="openDetail(room)"
                  >
                    <Eye class="h-3 w-3" /> Xem
                  </button>

                  <button
                    type="button"
                    class="rounded-lg border text-[11px] px-2.5 py-1 font-bold inline-flex items-center gap-1 transition"
                    :class="isRoomBanned(room)
                      ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                      : 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'"
                    :disabled="roomActionLoading === getRoomKey(room)"
                    @click="toggleBanRoom(room)"
                  >
                    <Unlock v-if="isRoomBanned(room)" class="h-3 w-3" />
                    <Lock v-else class="h-3 w-3" />
                    {{ isRoomBanned(room) ? 'Mở khóa' : 'Khóa' }}
                  </button>

                  <button
                    type="button"
                    class="rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-[11px] px-2.5 py-1 font-bold inline-flex items-center gap-1 transition disabled:opacity-50"
                    :disabled="roomActionLoading === getRoomKey(room)"
                    @click="softDeleteRoom(room)"
                  >
                    <Trash2 class="h-3 w-3" /> Xóa
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- TRASH TABLE -->
        <table v-else class="w-full min-w-[900px] text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 uppercase text-[10px] font-bold">
              <th class="py-3 px-3 w-10">#</th>
              <th class="py-3 px-3">Tên phòng</th>
              <th class="py-3 px-3">Chủ phòng</th>
              <th class="py-3 px-3">Mã phòng</th>
              <th class="py-3 px-3">Đã xóa lúc</th>
              <th class="py-3 px-3 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="(room, index) in listState.items" :key="`trash-hw-${room.id}`" class="hover:bg-slate-50 transition-colors">
              <td class="py-3.5 px-3 font-bold text-[11px] text-slate-400">
                {{ (listState.meta.current_page - 1) * 10 + index + 1 }}
              </td>
              <td class="py-3.5 px-3 max-w-xs font-bold text-slate-900">
                {{ room.name || room.title || "Chưa đặt tên" }}
              </td>
              <td class="py-3.5 px-3">
                <p class="font-bold text-slate-900">{{ room.host?.name || "Không rõ" }}</p>
                <p class="text-[10px] text-slate-400">{{ room.host?.email || "-" }}</p>
              </td>
              <td class="py-3.5 px-3 font-mono text-xs">
                <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 font-bold tracking-wider text-slate-700">
                  {{ room.code || "-" }}
                </span>
              </td>
              <td class="py-3.5 px-3 text-slate-400 text-[11px]">
                {{ formatDateTime(room.deleted_at) }}
              </td>
              <td class="py-3.5 px-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="btn-secondary text-[11px] px-2.5 py-1 text-emerald-700 font-bold inline-flex items-center gap-1"
                    :disabled="roomActionLoading === getRoomKey(room)"
                    @click="restoreRoom(room)"
                  >
                    <RotateCcw class="h-3 w-3" /> Khôi phục
                  </button>
                  <button
                    type="button"
                    class="rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-[11px] px-2.5 py-1 font-bold inline-flex items-center gap-1 transition disabled:opacity-50"
                    :disabled="roomActionLoading === getRoomKey(room)"
                    @click="openForceDeleteModal(room)"
                  >
                    <Trash2 class="h-3 w-3" /> Xóa vĩnh viễn
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty state -->
        <div v-if="listState.items.length === 0" class="py-10 text-center text-slate-400">
          <Inbox class="mx-auto mb-2 h-6 w-6 opacity-40" />
          {{ viewMode === 'trash' ? 'Chưa có phòng bài tập nào trong thùng rác.' : 'Không có phòng bài tập nào phù hợp với bộ lọc.' }}
        </div>
      </div>

      <!-- Pagination Bar -->
      <div v-if="listState.items.length > 0" class="border-t border-slate-100 pt-4">
        <AppPagination
          :current-page="listState.meta.current_page"
          :last-page="listState.meta.last_page"
          :total="listState.meta.total"
          :per-page="listState.meta.per_page || 10"
          item-label="phòng bài tập"
          @change="loadRooms"
        />
      </div>
      </div>
    </article>

    <!-- FORCE DELETE CONFIRMATION MODAL -->
    <div
      v-if="forceDeleteModal.show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
      @click.self="closeForceDeleteModal"
    >
      <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
        <div class="flex items-center gap-3">
          <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-red-100 text-red-600">
            <Trash2 class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Xóa vĩnh viễn phòng?</h3>
            <p class="text-xs text-slate-500">Hành động này không thể hoàn tác.</p>
          </div>
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-xs font-medium text-slate-600">
          Phòng
          <span class="font-bold text-slate-900">"{{ forceDeleteModal.room?.name || forceDeleteModal.room?.title }}"</span>
          sẽ bị xóa hoàn toàn khỏi hệ thống và không thể khôi phục.
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            :disabled="forceDeleteModal.loading"
            @click="closeForceDeleteModal"
          >
            Hủy
          </button>
          <button
            type="button"
            class="rounded-xl bg-red-600 hover:bg-red-700 px-5 py-2 text-xs font-bold text-white shadow-sm transition disabled:opacity-50 inline-flex items-center gap-1.5"
            :disabled="forceDeleteModal.loading"
            @click="confirmForceDelete"
          >
            <RefreshCw v-if="forceDeleteModal.loading" class="h-3.5 w-3.5 animate-spin" />
            {{ forceDeleteModal.loading ? "Đang xóa..." : "Xóa vĩnh viễn" }}
          </button>
        </div>
      </div>
    </div>

    <!-- DETAIL DRAWER MODAL -->
    <div v-if="detailOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" @click.self="closeDetail">
      <aside class="ml-auto flex h-full w-full max-w-full md:max-w-3xl lg:max-w-5xl flex-col border-l border-slate-200 bg-white shadow-xl">
        <header class="flex items-start justify-between gap-4 border-b border-slate-100 p-5">
          <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-500">
              <BookOpen class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Phòng bài tập</p>
              <h2 class="text-2xl font-black text-slate-900">{{ detailTitle }}</h2>
              <p class="text-sm font-bold text-slate-500">{{ detailCode }}</p>
            </div>
          </div>
          <button class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" type="button" @click="closeDetail">
            <X class="h-4 w-4" />
          </button>
        </header>

        <div v-if="detailLoading" class="grid flex-1 place-items-center p-8 text-sm font-bold text-slate-400 gap-2">
          <RefreshCw class="h-5 w-5 animate-spin text-[#7C3AED]" />
          <span>Đang tải chi tiết...</span>
        </div>
        <div v-else-if="detailError" class="m-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
          {{ detailError }}
        </div>
        <div v-else class="min-h-0 flex-1 overflow-y-auto p-5">
          <div class="mb-5 flex flex-wrap gap-2">
            <button
              v-for="tab in detailTabs"
              :key="tab.value"
              type="button"
              class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition"
              :class="detailTab === tab.value ? 'bg-[#7C3AED] text-white shadow-sm' : 'bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900'"
              @click="detailTab = tab.value"
            >
              {{ tab.label }}
            </button>
          </div>

          <HomeworkDetail :detail="detailData" :tab="detailTab" />
        </div>
      </aside>
    </div>
  </section>
</template>

<script setup>
import {
  computed,
  defineComponent,
  h,
  onMounted,
  onBeforeUnmount,
  reactive,
  ref,
  inject,
} from "vue";
import { adminRoomApi, adminRoomsApi } from "@/services/api";
import { useAppLoading } from "@/composables/useAppLoading";
import AppPagination from "@/components/common/AppPagination.vue";
const { beginTask, endTask } = useAppLoading();
import {
  BookOpen,
  Users,
  Lock,
  Unlock,
  Trash2,
  RefreshCw,
  LayoutGrid,
  Search,
  Info,
  AlertCircle,
  Eye,
  Copy,
  Check,
  RotateCcw,
  ChevronLeft,
  ChevronRight,
  Inbox,
  X,
} from "lucide-vue-next";

const showConfirm = inject("showConfirm");
const showToast = inject("showToast");

const confirmAndExecute = (title, message, action) => {
  if (showConfirm) {
    showConfirm(title, message, action);
  } else {
    if (window.confirm(message)) {
      action();
    }
  }
};

// Summary Statistics state (Homework rooms only)
const stats = reactive({
  homework_total: 0,
  active_total: 0,
  active_percent: 0,
  banned_total: 0,
  trash_total: 0,
  trash_percent: 0,
});
const statsLoading = ref(true);

const loadStats = async () => {
  statsLoading.value = true;
  try {
    const data = await adminRoomsApi.getStats();
    if (data) {
      Object.assign(stats, {
        homework_total: data.homework_total,
        active_total: data.active_total,
        active_percent: data.active_percent,
        banned_total: data.banned_total ?? data.homework_banned_total ?? 0,
        trash_total: data.trash_total,
        trash_percent: data.trash_percent,
      });
    }
  } catch (err) {
    console.error("Lỗi khi tải thống kê phòng bài tập:", err);
  } finally {
    statsLoading.value = false;
  }
};

// View & Filter States (Homework rooms only)
const viewMode = ref("all"); // 'all' or 'trash'
const statusFilter = ref("");
const searchQuery = ref("");

const setViewMode = (mode) => {
  viewMode.value = mode;
  loadRooms(1);
};

// List State
const listState = reactive({
  items: [],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: null,
    to: null,
  },
  loading: false,
  error: "",
});

const roomActionLoading = ref("");

const cleanParams = (page) => {
  const params = { page, per_page: 10 };
  if (searchQuery.value) params.search = searchQuery.value;
  if (statusFilter.value && viewMode.value !== "trash")
    params.status = statusFilter.value;
  return params;
};

const loadRooms = async (page = listState.meta.current_page || 1) => {
  listState.loading = true;
  listState.error = "";
  try {
    const params = cleanParams(page);

    const payload =
      viewMode.value === "trash"
        ? await adminRoomApi.getHomeworkRoomsTrash(params)
        : await adminRoomApi.getHomeworkRooms(params);

    setHomeworkList(payload);
  } catch (error) {
    listState.error = error.message || "Không tải được danh sách phòng bài tập.";
  } finally {
    listState.loading = false;
  }
};

const setHomeworkList = (payload) => {
  const items = (payload?.items || []).map((item) => ({
    ...item,
    room_type: "homework",
  }));
  listState.items = items;
  listState.meta = payload?.meta || {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: items.length,
    from: 1,
    to: items.length,
  };
};

const refreshData = () => {
  loadStats();
  loadRooms();
};

// Copy Code Helper
const copiedCodeMap = ref({});
const copyRoomCode = async (code) => {
  if (!code) return;
  try {
    await navigator.clipboard.writeText(code);
    copiedCodeMap.value[code] = true;
    if (showToast) showToast(`Mã phòng ${code} đã được sao chép.`, "success");
    setTimeout(() => {
      copiedCodeMap.value[code] = false;
    }, 2000);
  } catch {
    if (showToast) showToast("Không thể sao chép mã phòng.", "error");
  }
};

// Key helper
const getRoomKey = (room) => `homework:${room.id}`;

// Room Actions Helpers
const getStatusLabel = (room) => homeworkStatusLabel(String(room.status || "").toLowerCase());

const getStatusPillClass = (room) => {
  const status = String(room.status || "").toLowerCase();
  if (["active", "open", "waiting"].includes(status)) {
    return "bg-emerald-100 text-emerald-800";
  }
  if (["finished", "closed"].includes(status)) {
    return "bg-slate-100 text-slate-600";
  }
  if (["banned"].includes(status)) {
    return "bg-red-100 text-red-800";
  }
  return "bg-purple-100 text-[#7C3AED]";
};

const formatMemberCount = (room) => `${formatNumber(room.member_count)} HV`;

const isRoomBanned = (room) =>
  String(room?.status || "").toLowerCase() === "banned";

const toggleBanRoom = (room) => {
  if (isRoomBanned(room)) {
    unbanRoom(room);
  } else {
    banRoom(room);
  }
};

const banRoom = async (room) => {
  const title = "Khóa phòng (Ban)";
  const message = `Khóa phòng bài tập "${room.name || room.id}"? Học sinh và Chủ phòng sẽ không thể thao tác.`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      const updated = await adminRoomApi.banHomeworkRoom(room.id);
      updateLocalRoom(room.id, updated);
      if (showToast) showToast("Đã khóa phòng thành công.", "success");
      loadStats();
    } catch (error) {
      if (showToast)
        showToast(error.message || "Không thể khóa phòng.", "error");
    } finally {
      roomActionLoading.value = "";
    }
  });
};

const unbanRoom = async (room) => {
  const title = "Mở khóa phòng";
  const message = `Mở khóa phòng "${room.name || room.id}"?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      const updated = await adminRoomApi.unbanHomeworkRoom(room.id);
      updateLocalRoom(room.id, updated);
      if (showToast) showToast("Đã mở khóa phòng thành công.", "success");
      loadStats();
    } catch (error) {
      if (showToast)
        showToast(error.message || "Không thể mở khóa phòng.", "error");
    } finally {
      roomActionLoading.value = "";
    }
  });
};

const softDeleteRoom = async (room) => {
  const title = "Chuyển vào thùng rác";
  const message = `Chuyển phòng "${room.name || room.id}" vào thùng rác?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      await adminRoomApi.softDeleteHomeworkRoom(room.id);
      removeLocalRoom(room.id);
      if (showToast) showToast("Đã chuyển phòng vào thùng rác.", "success");
      loadStats();
    } catch (error) {
      if (showToast)
        showToast(error.message || "Không thể chuyển vào thùng rác.", "error");
    } finally {
      roomActionLoading.value = "";
    }
  });
};

const restoreRoom = async (room) => {
  const title = "Khôi phục phòng";
  const message = `Khôi phục phòng "${room.name || room.id}"?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      await adminRoomApi.restoreHomeworkRoom(room.id);
      removeLocalRoom(room.id);
      if (showToast) showToast("Đã khôi phục phòng thành công.", "success");
      loadStats();
    } catch (error) {
      if (showToast)
        showToast(error.message || "Không thể khôi phục phòng.", "error");
    } finally {
      roomActionLoading.value = "";
    }
  });
};

// Force Delete Confirmation Modal
const forceDeleteModal = reactive({
  show: false,
  room: null,
  loading: false,
});

const openForceDeleteModal = (room) => {
  forceDeleteModal.room = room;
  forceDeleteModal.show = true;
};

const closeForceDeleteModal = () => {
  forceDeleteModal.show = false;
  forceDeleteModal.room = null;
  forceDeleteModal.loading = false;
};

const confirmForceDelete = async () => {
  const room = forceDeleteModal.room;
  if (!room) return;

  forceDeleteModal.loading = true;
  try {
    await adminRoomsApi.forceDeleteHomework(room.id);
    removeLocalRoom(room.id);
    if (showToast) showToast("Đã xóa vĩnh viễn phòng thành công.", "success");
    closeForceDeleteModal();
    loadStats();
  } catch (error) {
    if (showToast)
      showToast(error.message || "Không thể xóa vĩnh viễn phòng.", "error");
  } finally {
    forceDeleteModal.loading = false;
  }
};

const updateLocalRoom = (id, updated) => {
  const index = listState.items.findIndex((r) => String(r.id) === String(id));
  if (index !== -1) {
    listState.items[index] = { ...listState.items[index], ...updated };
  }
};

const removeLocalRoom = (id) => {
  listState.items = listState.items.filter((r) => String(r.id) !== String(id));
  listState.meta.total = Math.max(0, listState.meta.total - 1);
};

// Days remaining calculator
const calculateDaysRemaining = (deletedAt) => {
  if (!deletedAt) return { days: 30, text: "Còn 30 ngày", dateStr: "" };
  const deletedDate = new Date(deletedAt);
  const expireDate = new Date(deletedDate.getTime() + 30 * 24 * 60 * 60 * 1000);
  const diffMs = expireDate - new Date();
  const days = Math.max(0, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));
  const dateStr = expireDate.toLocaleDateString("vi-VN");
  return {
    days,
    text: `Còn ${days} ngày`,
    dateStr: `(${dateStr})`,
  };
};

// Detail Drawer States & Handlers (Homework only)
const detailOpen = ref(false);
const detailTab = ref("info");
const detailData = ref(null);
const detailLoading = ref(false);
const detailError = ref("");

const detailTabs = [
  { label: "Thông tin phòng", value: "info" },
  { label: "Thành viên", value: "members" },
  { label: "Bài tập đã giao", value: "assignments" },
  { label: "Lượt nộp bài", value: "submissions" },
];

const detailTitle = computed(() => {
  const room = detailData.value?.room;
  return room?.name || room?.title || "Chi tiết phòng";
});
const detailCode = computed(() =>
  detailData.value?.room?.code ? `Mã phòng: ${detailData.value.room.code}` : "",
);

const openDetail = async (room) => {
  detailOpen.value = true;
  detailTab.value = "info";
  detailData.value = null;
  detailLoading.value = true;
  detailError.value = "";
  try {
    detailData.value = await adminRoomApi.getHomeworkRoomDetail(room.id);
  } catch (error) {
    detailError.value = error.message || "Không tải được chi tiết phòng.";
  } finally {
    detailLoading.value = false;
  }
};

const closeDetail = () => {
  detailOpen.value = false;
  detailData.value = null;
  detailError.value = "";
};

// Formatters
const formatNumber = (value) =>
  new Intl.NumberFormat("vi-VN").format(Number(value || 0));
const formatDate = (value) => {
  if (!value) return "-";
  return new Date(value).toLocaleDateString("vi-VN");
};
const formatDateTime = (value) => {
  if (!value) return "-";
  return new Date(value).toLocaleString("vi-VN", {
    dateStyle: "short",
    timeStyle: "short",
  });
};
const formatDuration = (minutes) => {
  if (!minutes) return "-";
  return `${formatNumber(minutes)} phút`;
};
const homeworkStatusLabel = (status) =>
  ({
    active: "Đang hoạt động",
    waiting: "Đang hoạt động",
    open: "Đang hoạt động",
    closed: "Đã đóng",
    removed: "Đã xóa",
    archived: "Đã lưu trữ",
    finished: "Đã kết thúc",
    banned: "Bị khóa",
  })[String(status || "").toLowerCase()] ||
  status ||
  "-";

const roomRoleLabel = (role) =>
  ({
    host: "Chủ phòng",
    student: "Thành viên",
    member: "Thành viên",
  })[String(role || "").toLowerCase()] ||
  role ||
  "-";

const boolLabel = (value) => (value ? "Đã hoàn thành" : "Chưa hoàn thành");

// Child components
const EmptyState = defineComponent({
  props: { text: { type: String, required: true } },
  setup(props) {
    return () =>
      h(
        "div",
        {
          class:
            "rounded-xl border border-slate-100 bg-slate-50 p-10 text-center text-sm font-bold text-slate-400",
        },
        props.text,
      );
  },
});

const InfoGrid = defineComponent({
  props: { rows: { type: Array, required: true } },
  setup(props) {
    return () =>
      h(
        "div",
        { class: "grid gap-3 md:grid-cols-2" },
        props.rows.map((row) =>
          h(
            "div",
            {
              class:
                "rounded-xl border border-slate-100 bg-slate-50 p-4",
            },
            [
              h(
                "p",
                {
                  class:
                    "text-[10px] font-bold uppercase tracking-wide text-slate-400",
                },
                row.label,
              ),
              h(
                "b",
                {
                  class: "mt-1.5 block break-words text-slate-900 text-sm",
                },
                row.value || "-",
              ),
            ],
          ),
        ),
      );
  },
});

const SimpleTable = defineComponent({
  props: {
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    empty: { type: String, default: "Chưa có dữ liệu." },
  },
  setup(props) {
    return () =>
      props.rows.length
        ? h("div", { class: "overflow-x-auto" }, [
            h(
              "table",
              {
                class: "min-w-[800px] w-full text-left text-xs",
              },
              [
                h(
                  "thead",
                  {},
                  [
                    h(
                      "tr",
                      { class: "border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold" },
                      props.columns.map((column) =>
                        h("th", { class: "py-3 px-3" }, column.label),
                      ),
                    ),
                  ],
                ),
                h(
                  "tbody",
                  { class: "divide-y divide-slate-100 font-medium" },
                  props.rows.map((row) =>
                    h(
                      "tr",
                      { class: "hover:bg-slate-50 transition-colors text-slate-900" },
                      props.columns.map((column) =>
                        h(
                          "td",
                          { class: "py-3 px-3" },
                          column.format
                            ? column.format(row)
                            : row[column.key] || "-",
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ])
        : h(EmptyState, { text: props.empty });
  },
});

const HomeworkDetail = defineComponent({
  props: {
    detail: { type: Object, default: null },
    tab: { type: String, required: true },
  },
  setup(props) {
    return () => {
      const room = props.detail?.room || {};
      if (props.tab === "info") {
        return h(InfoGrid, {
          rows: [
            { label: "Tên phòng", value: room.name },
            { label: "Mã phòng", value: room.code },
            { label: "Mô tả", value: room.description || "-" },
            { label: "Chủ phòng", value: room.host?.name },
            { label: "Email chủ phòng", value: room.host?.email },
            { label: "Trạng thái", value: homeworkStatusLabel(room.status) },
            { label: "Ngày tạo", value: formatDateTime(room.created_at) },
            { label: "Số thành viên", value: formatNumber(room.member_count) },
            {
              label: "Số bài tập đã giao",
              value: formatNumber(room.assignment_count),
            },
            { label: "Chính sách tham gia", value: room.join_policy || "open" },
          ],
        });
      }

      if (props.tab === "members") {
        return h(SimpleTable, {
          rows: props.detail?.members || [],
          empty: "Chưa có thành viên.",
          columns: [
            {
              label: "Tên user",
              format: (row) => row.name || row.user?.name || "-",
            },
            {
              label: "Email",
              format: (row) => row.email || row.user?.email || "-",
            },
            {
              label: "Vai trò trong room",
              format: (row) => roomRoleLabel(row.role),
            },
            { label: "Trạng thái", format: (row) => row.status || "-" },
            {
              label: "Ngày tham gia",
              format: (row) => formatDateTime(row.joined_at),
            },
          ],
        });
      }

      if (props.tab === "assignments") {
        return h(SimpleTable, {
          rows: props.detail?.assignments || [],
          empty: "Chưa có bài tập đã giao.",
          columns: [
            { label: "Tên assignment", format: (row) => row.title || "-" },
            { label: "Quiz", format: (row) => row.quiz?.title || "-" },
            { label: "Người giao", format: (row) => row.assigner?.name || "-" },
            {
              label: "Deadline",
              format: (row) => formatDateTime(row.deadline_at),
            },
            {
              label: "Thời lượng",
              format: (row) => formatDuration(row.duration_minutes),
            },
            {
              label: "Số lần làm tối đa",
              format: (row) => formatNumber(row.max_attempts),
            },
            {
              label: "Số lượt nộp",
              format: (row) => formatNumber(row.submission_count),
            },
            { label: "Trạng thái", format: (row) => row.status || "-" },
          ],
        });
      }

      return h(SimpleTable, {
        rows: props.detail?.submissions || [],
        empty: "Chưa có lượt nộp bài.",
        columns: [
          { label: "Tên học viên", format: (row) => row.student?.name || "-" },
          {
            label: "Assignment",
            format: (row) => row.assignment?.title || "-",
          },
          { label: "Điểm", format: (row) => formatNumber(row.score) },
          {
            label: "Tổng điểm",
            format: (row) => formatNumber(row.total_points),
          },
          { label: "Trạng thái", format: (row) => row.status || "-" },
          {
            label: "Thời gian bắt đầu",
            format: (row) => formatDateTime(row.started_at),
          },
          {
            label: "Thời gian nộp",
            format: (row) =>
              formatDateTime(row.submitted_at || row.finished_at),
          },
        ],
      });
    };
  },
});

onMounted(async () => {
  beginTask();
  try {
    await Promise.all([loadStats(), loadRooms()]);
  } finally {
    endTask();
  }
});
</script>

<style scoped>
/*
  Các class .card / .btn-primary / .btn-secondary / .field dùng chung toàn hệ thống.
  Khối này CHỈ ép lại bo góc trong phạm vi trang Phòng bài tập (nhờ `scoped`),
  để đồng nhất với tỉ lệ bo góc đang dùng ở trang Người dùng / Giao dịch / Quiz / Phòng thi đấu,
  không ảnh hưởng tới CSS global hay các trang khác.
*/
:deep(.card) {
  border-radius: 1rem; /* 16px */
}
:deep(.btn-primary),
:deep(.btn-secondary) {
  border-radius: 0.5rem; /* 8px */
}
:deep(.field) {
  border-radius: 0.5rem; /* 8px */
}
</style>
