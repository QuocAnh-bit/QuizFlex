<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
          <Swords class="h-5 w-5" />
        </div>
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Quản trị hệ thống</p>
          <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Phòng thi đấu</h1>
          <p class="mt-1 text-sm font-medium text-[var(--muted)]">Quản lý tất cả phòng thi đấu (live) trên hệ thống.</p>
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
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Tổng số phòng thi đấu</span>
          <strong class="text-2xl font-black text-slate-900 block">{{ formatNumber(stats.live_total) }}</strong>
          <span class="text-[11px] text-slate-500 font-medium block">Tất cả phòng thi đấu</span>
        </div>
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-[#7C3AED]">
          <Swords class="h-4 w-4" />
        </div>
      </article>

      <article class="card p-5 card-hover flex items-start justify-between gap-3">
        <div class="space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Đang diễn ra</span>
          <strong class="text-2xl font-black text-amber-600 block">{{ formatNumber(stats.active_total) }}</strong>
          <span class="text-[11px] text-slate-500 font-medium block">{{ stats.active_percent }}% tổng số</span>
        </div>
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
          <PlayCircle class="h-4 w-4" />
        </div>
      </article>

      <article class="card p-5 card-hover flex items-start justify-between gap-3">
        <div class="space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Số phòng bị khóa</span>
          <strong class="text-2xl font-black text-slate-900 block">{{ formatNumber(stats.banned_total) }}</strong>
          <span class="text-[11px] text-slate-500 font-medium block">Đang bị khóa (banned)</span>
        </div>
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600">
          <Lock class="h-4 w-4" />
        </div>
      </article>

      <article class="card p-5 card-hover flex items-start justify-between gap-3">
        <div class="space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Trong thùng rác</span>
          <strong class="text-2xl font-black text-red-600 block">{{ formatNumber(stats.trash_total) }}</strong>
          <span class="text-[11px] text-slate-500 font-medium block">{{ stats.trash_percent }}% tổng số</span>
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
            <span>Tất cả phòng thi đấu</span>
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
            <option value="waiting">Chờ bắt đầu</option>
            <option value="playing">Đang diễn ra</option>
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
              <th class="py-3 px-3">Người chơi</th>
              <th class="py-3 px-3 hidden lg:table-cell">Tạo lúc</th>
              <th class="py-3 px-3 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="(room, index) in listState.items" :key="`live-${room.id}`" class="hover:bg-slate-50 transition-colors">
              <td class="py-3.5 px-3 font-bold text-[11px] text-slate-400">
                {{ (listState.meta.current_page - 1) * 10 + index + 1 }}
              </td>

              <td class="py-3.5 px-3 max-w-xs">
                <p class="font-bold text-slate-900 line-clamp-1 hover:text-[#7C3AED] cursor-pointer" @click="openDetail(room)">
                  {{ room.title || room.name || "Chưa đặt tên" }}
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
                  <span>{{ formatNumber(room.player_count) }} người</span>
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

                  <template v-if="canBanRoom(room)">
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
                  </template>

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
            <tr v-for="(room, index) in listState.items" :key="`trash-live-${room.id}`" class="hover:bg-slate-50 transition-colors">
              <td class="py-3.5 px-3 font-bold text-[11px] text-slate-400">
                {{ (listState.meta.current_page - 1) * 10 + index + 1 }}
              </td>
              <td class="py-3.5 px-3 max-w-xs font-bold text-slate-900">
                {{ room.title || room.name || "Chưa đặt tên" }}
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
          {{ viewMode === 'trash' ? 'Chưa có phòng thi đấu nào trong thùng rác.' : 'Không có phòng thi đấu nào phù hợp với bộ lọc.' }}
        </div>
      </div>

      <!-- Pagination Bar -->
      <div v-if="listState.items.length > 0" class="flex flex-col gap-3 border-t border-slate-100 pt-4 text-xs sm:flex-row sm:items-center sm:justify-between">
        <span class="text-slate-500 text-[11px]">
          Hiển thị {{ listState.meta.from || 0 }}–{{ listState.meta.to || 0 }} trong số {{ formatNumber(listState.meta.total || 0) }} phòng thi đấu
        </span>
        <div class="flex items-center gap-2">
          <button
            class="btn-secondary text-[11px] px-2.5 py-1 disabled:opacity-40 inline-flex items-center gap-1"
            type="button"
            :disabled="listState.loading || listState.meta.current_page <= 1"
            @click="loadRooms(listState.meta.current_page - 1)"
          >
            <ChevronLeft class="h-3 w-3" /> Trước
          </button>
          <span class="text-slate-700 font-bold">{{ listState.meta.current_page }} / {{ listState.meta.last_page }}</span>
          <button
            class="btn-secondary text-[11px] px-2.5 py-1 disabled:opacity-40 inline-flex items-center gap-1"
            type="button"
            :disabled="listState.loading || listState.meta.current_page >= listState.meta.last_page"
            @click="loadRooms(listState.meta.current_page + 1)"
          >
            Sau <ChevronRight class="h-3 w-3" />
          </button>
        </div>
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
          <span class="font-bold text-slate-900">"{{ forceDeleteModal.room?.title || forceDeleteModal.room?.name }}"</span>
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
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
              <Swords class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Phòng thi đấu</p>
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

          <LiveDetail :detail="detailData" :tab="detailTab" />
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
import {
  Swords,
  PlayCircle,
  Lock,
  Unlock,
  Trash2,
  RefreshCw,
  LayoutGrid,
  Search,
  Info,
  AlertCircle,
  Eye,
  Users,
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

// Summary Statistics state (Live/Battle rooms only)
const stats = reactive({
  live_total: 0,
  active_total: 0,
  active_percent: 0,
  banned_total: 0,
  trash_total: 0,
  trash_percent: 0,
});

const loadStats = async () => {
  try {
    const data = await adminRoomsApi.getStats();
    if (data) {
      Object.assign(stats, {
        live_total: data.live_total,
        active_total: data.live_active_total ?? data.active_total,
        active_percent: data.live_active_percent ?? data.active_percent,
        banned_total: data.banned_total ?? data.live_banned_total ?? 0,
        trash_total: data.trash_total,
        trash_percent: data.trash_percent,
      });
    }
  } catch (err) {
    console.error("Lỗi khi tải thống kê phòng thi đấu:", err);
  }
};

// View & Filter States (Live rooms only)
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
        ? await adminRoomApi.getLiveRoomsTrash(params)
        : await adminRoomApi.getLiveRooms(params);

    setLiveList(payload);
  } catch (error) {
    listState.error = error.message || "Không tải được danh sách phòng thi đấu.";
  } finally {
    listState.loading = false;
  }
};

const setLiveList = (payload) => {
  const items = (payload?.items || []).map((item) => ({
    ...item,
    room_type: "live",
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
const getRoomKey = (room) => `live:${room.id}`;

// Room Actions Helpers
const getStatusLabel = (room) => liveStatusLabel(String(room.status || "").toLowerCase());

const getStatusPillClass = (room) => {
  const status = String(room.status || "").toLowerCase();
  if (["active", "playing"].includes(status)) {
    return "bg-emerald-100 text-emerald-800";
  }
  if (["waiting", "pending"].includes(status)) {
    return "bg-amber-100 text-amber-800";
  }
  if (["finished", "closed"].includes(status)) {
    return "bg-slate-100 text-slate-600";
  }
  if (["banned"].includes(status)) {
    return "bg-red-100 text-red-800";
  }
  return "bg-purple-100 text-[#7C3AED]";
};

// Ban Restrictions Check for Finished Live Rooms
const isLiveFinished = (room) => {
  if (!room) return false;
  const status = String(room.status || "").toLowerCase();
  return status === "finished" || Boolean(room.finished_at || room.ended_at);
};

const canBanRoom = (room) => {
  if (!room) return false;
  if (isLiveFinished(room)) {
    return false; // Finished Live Room NEVER allows Ban
  }
  return true;
};

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
  if (isLiveFinished(room)) {
    if (showToast) showToast("Không thể khóa phòng thi đấu đã kết thúc.", "error");
    return;
  }

  const title = "Khóa phòng (Ban)";
  const message = `Khóa phòng thi đấu "${room.title || room.name || room.id}"? Trận đấu đang diễn ra sẽ bị kết thúc.`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      const updated = await adminRoomApi.banLiveRoom(room.id);
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
  const message = `Mở khóa phòng "${room.title || room.name || room.id}"?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      const updated = await adminRoomApi.unbanLiveRoom(room.id);
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
  const message = `Chuyển phòng "${room.title || room.name || room.id}" vào thùng rác?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      await adminRoomApi.softDeleteLiveRoom(room.id);
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
  const message = `Khôi phục phòng "${room.title || room.name || room.id}"?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      await adminRoomApi.restoreLiveRoom(room.id);
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
    await adminRoomsApi.forceDeleteLive(room.id);
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

// Detail Drawer States & Handlers (Live only)
const detailOpen = ref(false);
const detailTab = ref("info");
const detailData = ref(null);
const detailLoading = ref(false);
const detailError = ref("");

const detailTabs = [
  { label: "Thông tin phòng thi đấu", value: "info" },
  { label: "Người chơi", value: "players" },
  { label: "Leaderboard", value: "leaderboard" },
  { label: "Câu trả lời", value: "answers" },
  { label: "Realtime status", value: "realtime" },
];

const detailTitle = computed(() => {
  const room = detailData.value?.room;
  return room?.title || room?.name || "Chi tiết phòng";
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
    detailData.value = await adminRoomApi.getLiveRoomDetail(room.id);
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
const liveStatusLabel = (status) =>
  ({
    waiting: "Chờ bắt đầu",
    playing: "Đang diễn ra",
    active: "Đang diễn ra",
    finished: "Đã kết thúc",
    removed: "Đã xóa",
    cancelled: "Đã hủy",
    banned: "Bị khóa",
  })[String(status || "").toLowerCase()] ||
  status ||
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

const LiveDetail = defineComponent({
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
            { label: "Tên phòng thi đấu", value: room.title || room.name },
            { label: "Mã phòng", value: room.code },
            { label: "Chủ phòng", value: room.host?.name },
            { label: "Email chủ phòng", value: room.host?.email },
            { label: "Quiz", value: room.quiz?.title },
            { label: "Trạng thái", value: liveStatusLabel(room.status) },
            { label: "Số player", value: formatNumber(room.player_count) },
            { label: "Thời gian tạo", value: formatDateTime(room.created_at) },
            {
              label: "Thời gian bắt đầu",
              value: formatDateTime(room.started_at),
            },
            {
              label: "Thời gian kết thúc",
              value: formatDateTime(room.finished_at || room.ended_at),
            },
          ],
        });
      }

      if (props.tab === "players") {
        return h(SimpleTable, {
          rows: props.detail?.players || [],
          empty: "Chưa có người chơi.",
          columns: [
            {
              label: "Tên người chơi",
              format: (row) => row.name || row.user?.name || "-",
            },
            {
              label: "Email",
              format: (row) => row.email || row.user?.email || "-",
            },
            { label: "Điểm", format: (row) => formatNumber(row.score) },
            {
              label: "Số câu đúng",
              format: (row) => formatNumber(row.correct_count),
            },
            {
              label: "Câu hiện tại",
              format: (row) => formatNumber(row.current_question_index),
            },
            {
              label: "Hoàn thành",
              format: (row) => boolLabel(row.is_finished),
            },
            {
              label: "Trả lời gần nhất",
              format: (row) => formatDateTime(row.last_answered_at),
            },
          ],
        });
      }

      if (props.tab === "leaderboard") {
        return h(SimpleTable, {
          rows: props.detail?.leaderboard || [],
          empty: "Chưa có leaderboard.",
          columns: [
            { label: "Hạng", format: (row) => `#${row.rank}` },
            {
              label: "Tên người chơi",
              format: (row) => row.name || row.user?.name || "-",
            },
            { label: "Điểm", format: (row) => formatNumber(row.score) },
            {
              label: "Số câu đúng",
              format: (row) => formatNumber(row.correct_count),
            },
            {
              label: "Số câu đã trả lời",
              format: (row) => formatNumber(row.answered_count),
            },
            {
              label: "Thời gian hoàn thành",
              format: (row) => formatDateTime(row.finished_at),
            },
          ],
        });
      }

      if (props.tab === "answers") {
        return h(SimpleTable, {
          rows: props.detail?.answers || [],
          empty: "Room chưa có lượt trả lời.",
          columns: [
            { label: "Người chơi", format: (row) => row.player?.name || "-" },
            { label: "Câu hỏi", format: (row) => row.question?.content || "-" },
            {
              label: "Câu trả lời",
              format: (row) => row.answer?.content || "-",
            },
            {
              label: "Đúng / Sai",
              format: (row) => (row.is_correct ? "Đúng" : "Sai"),
            },
            {
              label: "Thời gian trả lời",
              format: (row) => formatDateTime(row.answered_at),
            },
          ],
        });
      }

      const realtime = props.detail?.realtime_status || {};
      return h(InfoGrid, {
        rows: [
          {
            label: "Trạng thái phòng",
            value: liveStatusLabel(realtime.room_status),
          },
          {
            label: "Player đã hoàn thành",
            value: formatNumber(realtime.finished_players),
          },
          {
            label: "Player chưa hoàn thành",
            value: formatNumber(realtime.unfinished_players),
          },
          {
            label: "Lần cập nhật gần nhất",
            value: formatDateTime(realtime.last_updated_at),
          },
          {
            label: "Ghi chú",
            value:
              realtime.note ||
              "Không có dấu hiệu bất thường từ dữ liệu hiện tại.",
          },
        ],
      });
    };
  },
});

onMounted(() => {
  loadStats();
  loadRooms();
});
</script>

<style scoped>
/*
  Các class .card / .btn-primary / .btn-secondary / .field dùng chung toàn hệ thống.
  Khối này CHỈ ép lại bo góc trong phạm vi trang Phòng thi đấu (nhờ `scoped`),
  để đồng nhất với tỉ lệ bo góc đang dùng ở trang Người dùng / Giao dịch / Quiz,
  không ảnh hưởng tới CSS global hay các trang khác.
*/
:deep(.card) {
  border-radius: 1rem; /* 16px – khớp card ngoài của các trang đã chỉnh */
}
:deep(.btn-primary),
:deep(.btn-secondary) {
  border-radius: 0.5rem; /* 8px – bỏ dáng pill, khớp ô field bên cạnh */
}
:deep(.field) {
  border-radius: 0.5rem; /* 8px */
}
</style>