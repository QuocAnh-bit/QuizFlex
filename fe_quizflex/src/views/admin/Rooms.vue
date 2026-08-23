<template>
  <section class="grid gap-6">
    <!-- Header Page -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Phòng bài tập</h1>
          <p class="mt-1 text-sm font-medium text-[var(--muted)]">
            Quản lý tất cả phòng bài tập trên hệ thống
          </p>
        </div>
        <button
          class="btn-ghost flex items-center gap-2 self-start sm:self-auto"
          type="button"
          :disabled="listState.loading"
          @click="refreshData"
        >
          <svg class="h-4 w-4" :class="{ 'animate-spin': listState.loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Làm mới</span>
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <!-- Card 1: Tổng số phòng bài tập -->
      <div
        class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-500"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
              <path
                d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"
              />
            </svg>
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">
              Tổng số phòng bài tập
            </p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">
              {{ formatNumber(stats.homework_total) }}
            </p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">
          Tất cả phòng bài tập
        </p>
      </div>

      <!-- Card 2: Đang hoạt động -->
      <div
        class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">
              Đang hoạt động
            </p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">
              {{ formatNumber(stats.active_total) }}
            </p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">
          {{ stats.active_percent }}% tổng số
        </p>
      </div>

      <!-- Card 3: Bị khóa -->
      <div
        class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">
              Số phòng bị khóa
            </p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">
              {{ formatNumber(stats.banned_total) }}
            </p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">
          Đang bị khóa (banned)
        </p>
      </div>

      <!-- Card 4: Trong thùng rác -->
      <div
        class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-500"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <polyline points="3 6 5 6 21 6" />
              <path
                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
              />
              <line x1="10" y1="11" x2="10" y2="17" />
              <line x1="14" y1="11" x2="14" y2="17" />
            </svg>
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">
              Trong thùng rác
            </p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">
              {{ formatNumber(stats.trash_total) }}
            </p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">
          {{ stats.trash_percent }}% tổng số
        </p>
      </div>
    </div>

    <!-- Main Content Container -->
    <div class="glass-card rounded-[2rem] p-5">
      <!-- Top Bar: Tabs & Search/Filter Controls -->
      <div class="flex flex-col gap-4">
        <!-- Tabs & Filter/Search bar -->
        <div
          class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-[var(--border)] pb-4"
        >
          <!-- Left side: Tabs -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-extrabold transition-all"
              :class="
                viewMode === 'all'
                  ? 'bg-[var(--chip-active)] text-[var(--primary)] border border-[var(--border-strong)]'
                  : 'text-[var(--muted)] hover:text-[var(--text)] border border-transparent'
              "
              @click="setViewMode('all')"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M3 9h18" />
                <path d="M3 15h18" />
              </svg>
              <span>Tất cả phòng bài tập</span>
            </button>

            <button
              type="button"
              class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-extrabold transition-all"
              :class="
                viewMode === 'trash'
                  ? 'bg-[var(--chip-active)] text-[var(--primary)] border border-[var(--border-strong)]'
                  : 'text-[var(--muted)] hover:text-[var(--text)] border border-transparent'
              "
              @click="setViewMode('trash')"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <polyline points="3 6 5 6 21 6" />
                <path
                  d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                />
              </svg>
              <span>Thùng rác</span>
              <span
                class="rounded-full bg-rose-500/10 px-2 py-0.5 text-xs font-bold text-rose-500 border border-rose-500/20"
              >
                {{ stats.trash_total }}
              </span>
            </button>
          </div>

          <!-- Right side: Filter & Search form -->
          <form
            class="flex flex-col gap-3 sm:flex-row sm:items-center"
            @submit.prevent="loadRooms(1)"
          >
            <!-- Select Status -->
            <div class="relative w-full sm:w-44">
              <select
                v-model="statusFilter"
                class="field w-full appearance-none pr-8 text-sm font-semibold"
                :disabled="viewMode === 'trash'"
                @change="loadRooms(1)"
              >
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang hoạt động</option>
                <option value="waiting">Chờ bắt đầu</option>
                <option value="finished">Đã kết thúc</option>
                <option value="banned">Bị khóa (Banned)</option>
              </select>
              <div
                class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[var(--muted)]"
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6" />
                </svg>
              </div>
            </div>

            <!-- Search input -->
            <div class="relative w-full sm:w-64">
              <input
                v-model.trim="searchQuery"
                class="field w-full pl-9 pr-4 text-sm font-medium"
                type="search"
                placeholder="Tìm theo tên phòng, mã phòng, host..."
              />
              <div
                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]"
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                >
                  <circle cx="11" cy="11" r="8" />
                  <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
              </div>
            </div>

            <!-- Search button -->
            <button
              class="btn-primary min-w-[100px] shrink-0"
              type="submit"
              :disabled="listState.loading"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
              </svg>
              <span>Tìm kiếm</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Info Alert Banner for Trash mode -->
      <div
        v-if="viewMode === 'trash'"
        class="mt-4 flex items-center justify-between rounded-xl border border-blue-500/20 bg-blue-500/10 p-3.5 text-xs font-semibold text-blue-300"
      >
        <div class="flex items-center gap-2">
          <svg
            class="h-4 w-4 shrink-0 text-blue-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="2"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="16" x2="12" y2="12" />
            <line x1="12" y1="8" x2="12.01" y2="8" />
          </svg>
          <span>Các phòng trong thùng rác vẫn có thể khôi phục.</span>
        </div>
        <button
          class="btn-ghost px-3 py-1 text-xs font-bold"
          type="button"
          @click="loadRooms()"
        >
          Làm mới
        </button>
      </div>

      <!-- Table Section -->
      <div
        v-if="listState.error"
        class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300"
      >
        {{ listState.error }}
      </div>

      <div
        v-else-if="listState.loading"
        class="mt-5 grid place-items-center rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-12 text-sm font-bold text-[var(--muted)]"
      >
        <div class="flex items-center gap-3">
          <svg
            class="h-5 w-5 animate-spin text-[var(--primary)]"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          <span>Đang tải danh sách phòng...</span>
        </div>
      </div>

      <div v-else class="mt-5 overflow-x-auto scrollbar-soft">
        <!-- ALL ROOMS TABLE (Homework only) -->
        <table
          v-if="viewMode === 'all'"
          class="w-full min-w-[900px] border-separate border-spacing-y-2 text-left text-sm"
        >
          <thead class="text-xs font-bold text-[var(--muted)]">
            <tr>
              <th class="px-4 py-2 w-12">#</th>
              <th class="px-4 py-2">Tên phòng</th>
              <th class="px-4 py-2">Chủ phòng</th>
              <th class="px-4 py-2">Mã phòng</th>
              <th class="px-4 py-2">Trạng thái</th>
              <th class="px-4 py-2">Thành viên</th>
              <th class="px-4 py-2 hidden lg:table-cell">Tạo lúc</th>
              <th class="px-4 py-2 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(room, index) in listState.items"
              :key="`hw-${room.id}`"
              class="group rounded-2xl bg-[var(--surface-soft)] text-[var(--text)] transition-all duration-150 hover:bg-[var(--surface)]"
            >
              <!-- # Index -->
              <td
                class="rounded-l-2xl px-4 py-3.5 font-bold text-xs text-[var(--muted)]"
              >
                {{ (listState.meta.current_page - 1) * 10 + index + 1 }}
              </td>

              <!-- Room Name & Description -->
              <td class="px-4 py-3.5 max-w-xs">
                <p
                  class="font-bold text-[var(--text)] line-clamp-1 hover:text-[var(--primary)] cursor-pointer"
                  @click="openDetail(room)"
                >
                  {{ room.name || room.title || "Chưa đặt tên" }}
                </p>
                <p
                  v-if="room.description"
                  class="mt-0.5 text-xs text-[var(--muted)] line-clamp-1"
                >
                  {{ room.description }}
                </p>
              </td>

              <!-- Host Info -->
              <td class="px-4 py-3.5">
                <p class="font-semibold text-xs text-[var(--text)]">
                  {{ room.host?.name || "Không rõ" }}
                </p>
                <p class="text-[11px] text-[var(--muted)]">
                  {{ room.host?.email || "-" }}
                </p>
              </td>

              <!-- Room Code & Copy Button -->
              <td class="px-4 py-3.5 font-mono text-xs">
                <div
                  class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-2.5 py-1 text-[var(--text)]"
                >
                  <span class="font-bold tracking-wider">{{
                    room.code || "-"
                  }}</span>
                  <button
                    v-if="room.code"
                    type="button"
                    class="text-[var(--muted)] transition-colors hover:text-[var(--primary)] focus:outline-none"
                    :title="
                      copiedCodeMap[room.code]
                        ? 'Đã sao chép!'
                        : 'Sao chép mã phòng'
                    "
                    aria-label="Sao chép mã phòng"
                    @click="copyRoomCode(room.code)"
                  >
                    <svg
                      v-if="copiedCodeMap[room.code]"
                      class="h-3.5 w-3.5 text-emerald-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      stroke-width="2.5"
                    >
                      <polyline points="20 6 9 17 4 12" />
                    </svg>
                    <svg
                      v-else
                      class="h-3.5 w-3.5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      stroke-width="2"
                    >
                      <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                      <path
                        d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"
                      />
                    </svg>
                  </button>
                </div>
              </td>

              <!-- Status Pill -->
              <td class="px-4 py-3.5">
                <span
                  class="inline-flex rounded-full border px-3 py-0.5 text-xs font-extrabold"
                  :class="getStatusPillClass(room)"
                >
                  {{ getStatusLabel(room) }}
                </span>
              </td>

              <!-- Member Count -->
              <td class="px-4 py-3.5">
                <div
                  class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--text)]"
                >
                  <svg
                    class="h-3.5 w-3.5 text-[var(--muted)]"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                  >
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                  </svg>
                  <span>{{ formatMemberCount(room) }}</span>
                </div>
              </td>

              <!-- Created At -->
              <td
                class="px-4 py-3.5 text-xs text-[var(--muted)] hidden lg:table-cell"
              >
                {{ formatDateTime(room.created_at) }}
              </td>

              <!-- Action Buttons (Icon-only with Tooltips) -->
              <td class="rounded-r-2xl px-4 py-3.5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Eye: Xem chi tiết -->
                  <div class="relative group/tooltip">
                    <button
                      type="button"
                      class="flex h-8 w-8 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10 text-blue-400 transition-all hover:bg-blue-500/20"
                      aria-label="Xem chi tiết"
                      @click="openDetail(room)"
                    >
                      <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                      >
                        <path
                          d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                        />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    </button>
                    <span
                      class="pointer-events-none absolute bottom-full left-1/2 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-lg bg-[var(--surface-strong)] px-2.5 py-1 text-[11px] font-bold text-[var(--text)] shadow-lg border border-[var(--border)] opacity-0 transition-opacity group-hover/tooltip:opacity-100 z-30"
                    >
                      Xem chi tiết
                    </span>
                  </div>

                  <!-- Lock / Ban -->
                  <div class="relative group/tooltip">
                    <button
                      type="button"
                      class="flex h-8 w-8 items-center justify-center rounded-xl border transition-all"
                      :class="
                        isRoomBanned(room)
                          ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'
                          : 'border-amber-500/20 bg-amber-500/10 text-amber-400 hover:bg-amber-500/20'
                      "
                      :aria-label="
                        isRoomBanned(room)
                          ? 'Mở khóa phòng'
                          : 'Khóa phòng (Ban)'
                      "
                      :disabled="roomActionLoading === getRoomKey(room)"
                      @click="toggleBanRoom(room)"
                    >
                      <svg
                        v-if="isRoomBanned(room)"
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                      >
                        <rect
                          x="3"
                          y="11"
                          width="18"
                          height="11"
                          rx="2"
                          ry="2"
                        />
                        <path d="M7 11V7a5 5 0 0 1 9.9-1" />
                      </svg>
                      <svg
                        v-else
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                      >
                        <rect
                          x="3"
                          y="11"
                          width="18"
                          height="11"
                          rx="2"
                          ry="2"
                        />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                      </svg>
                    </button>
                    <span
                      class="pointer-events-none absolute bottom-full left-1/2 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-lg bg-[var(--surface-strong)] px-2.5 py-1 text-[11px] font-bold text-[var(--text)] shadow-lg border border-[var(--border)] opacity-0 transition-opacity group-hover/tooltip:opacity-100 z-30"
                    >
                      {{
                        isRoomBanned(room)
                          ? "Mở khóa phòng"
                          : "Khóa phòng (Ban)"
                      }}
                    </span>
                  </div>

                  <!-- Trash2: Đưa vào thùng rác -->
                  <div class="relative group/tooltip">
                    <button
                      type="button"
                      class="flex h-8 w-8 items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 transition-all hover:bg-rose-500/20 disabled:opacity-50"
                      aria-label="Đưa vào thùng rác"
                      :disabled="roomActionLoading === getRoomKey(room)"
                      @click="softDeleteRoom(room)"
                    >
                      <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                      >
                        <polyline points="3 6 5 6 21 6" />
                        <path
                          d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                        />
                        <line x1="10" y1="11" x2="10" y2="17" />
                        <line x1="14" y1="11" x2="14" y2="17" />
                      </svg>
                    </button>
                    <span
                      class="pointer-events-none absolute bottom-full left-1/2 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-lg bg-[var(--surface-strong)] px-2.5 py-1 text-[11px] font-bold text-[var(--text)] shadow-lg border border-[var(--border)] opacity-0 transition-opacity group-hover/tooltip:opacity-100 z-30"
                    >
                      Đưa vào thùng rác
                    </span>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- TRASH TABLE (Homework only) -->
        <table
          v-else
          class="w-full min-w-[900px] border-separate border-spacing-y-2 text-left text-sm"
        >
          <thead class="text-xs font-bold text-[var(--muted)]">
            <tr>
              <th class="px-4 py-2 w-12">#</th>
              <th class="px-4 py-2">Tên phòng</th>
              <th class="px-4 py-2">Chủ phòng</th>
              <th class="px-4 py-2">Mã phòng</th>
              <th class="px-4 py-2">Đã xóa lúc</th>
              <th class="px-4 py-2 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(room, index) in listState.items"
              :key="`trash-hw-${room.id}`"
              class="group rounded-2xl bg-[var(--surface-soft)] text-[var(--text)] transition-all duration-150 hover:bg-[var(--surface)]"
            >
              <td
                class="rounded-l-2xl px-4 py-3.5 font-bold text-xs text-[var(--muted)]"
              >
                {{ (listState.meta.current_page - 1) * 10 + index + 1 }}
              </td>

              <td class="px-4 py-3.5 max-w-xs font-bold text-[var(--text)]">
                {{ room.name || room.title || "Chưa đặt tên" }}
              </td>

              <td class="px-4 py-3.5">
                <p class="font-semibold text-xs text-[var(--text)]">
                  {{ room.host?.name || "Không rõ" }}
                </p>
                <p class="text-[11px] text-[var(--muted)]">
                  {{ room.host?.email || "-" }}
                </p>
              </td>

              <td class="px-4 py-3.5 font-mono text-xs">
                <span
                  class="inline-flex items-center rounded-lg border border-[var(--border)] bg-[var(--surface)] px-2.5 py-1 font-bold tracking-wider text-[var(--text)]"
                >
                  {{ room.code || "-" }}
                </span>
              </td>

              <td class="px-4 py-3.5 text-xs text-[var(--muted)]">
                {{ formatDateTime(room.deleted_at) }}
              </td>

              <!-- Trash Action Buttons -->
              <td class="rounded-r-2xl px-4 py-3.5 text-right">
                <div class="flex items-center justify-end gap-2">
                  <!-- Restore Button -->
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-400 transition-all hover:bg-emerald-500/20 disabled:opacity-50"
                    :disabled="roomActionLoading === getRoomKey(room)"
                    @click="restoreRoom(room)"
                  >
                    <svg
                      class="h-3.5 w-3.5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      stroke-width="2"
                    >
                      <polyline points="1 4 1 10 7 10" />
                      <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
                    </svg>
                    <span>Khôi phục</span>
                  </button>

                  <!-- Permanent Delete Button -->
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-1.5 text-xs font-bold text-rose-400 transition-all hover:bg-rose-500/20 disabled:opacity-50"
                    :disabled="roomActionLoading === getRoomKey(room)"
                    @click="openForceDeleteModal(room)"
                  >
                    <svg
                      class="h-3.5 w-3.5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      stroke-width="2"
                    >
                      <polyline points="3 6 5 6 21 6" />
                      <path
                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                      />
                    </svg>
                    <span>Xóa vĩnh viễn</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty state -->
        <EmptyState
          v-if="listState.items.length === 0"
          :text="
            viewMode === 'trash'
              ? 'Chưa có phòng bài tập nào trong thùng rác.'
              : 'Không có phòng bài tập nào phù hợp với bộ lọc.'
          "
        />
      </div>

      <!-- Pagination Bar -->
      <div
        v-if="listState.items.length > 0"
        class="mt-5 flex flex-col gap-3 border-t border-[var(--border)] pt-4 text-xs font-bold text-[var(--muted)] sm:flex-row sm:items-center sm:justify-between"
      >
        <span
          >Hiển thị {{ listState.meta.from || 0 }} đến
          {{ listState.meta.to || 0 }} của
          {{ formatNumber(listState.meta.total || 0) }} phòng bài tập</span
        >
        <div class="flex items-center gap-2">
          <button
            class="flex h-8 w-8 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface)] text-[var(--text)] transition hover:bg-[var(--surface-soft)] disabled:opacity-40"
            type="button"
            :disabled="listState.loading || listState.meta.current_page <= 1"
            @click="loadRooms(listState.meta.current_page - 1)"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="2"
            >
              <polyline points="15 18 9 12 15 6" />
            </svg>
          </button>

          <span
            class="flex items-center rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-1.5 font-bold text-[var(--text)]"
          >
            Trang {{ listState.meta.current_page }} /
            {{ listState.meta.last_page }}
          </span>

          <button
            class="flex h-8 w-8 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface)] text-[var(--text)] transition hover:bg-[var(--surface-soft)] disabled:opacity-40"
            type="button"
            :disabled="
              listState.loading ||
              listState.meta.current_page >= listState.meta.last_page
            "
            @click="loadRooms(listState.meta.current_page + 1)"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="2"
            >
              <polyline points="9 18 15 12 9 6" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- FORCE DELETE CONFIRMATION MODAL -->
    <div
      v-if="forceDeleteModal.show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
      @click.self="closeForceDeleteModal"
    >
      <div
        class="w-full max-w-md overflow-hidden rounded-3xl border border-[var(--border)] bg-[var(--surface-strong)] p-6 shadow-[var(--shadow-soft)] transition-all"
      >
        <div class="flex items-center gap-4">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-500 border border-rose-500/20"
          >
            <svg
              class="h-6 w-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="2"
            >
              <polyline points="3 6 5 6 21 6" />
              <path
                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
              />
              <line x1="10" y1="11" x2="10" y2="17" />
              <line x1="14" y1="11" x2="14" y2="17" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-black text-[var(--text)]">
              Xóa vĩnh viễn phòng?
            </h3>
            <p class="mt-1 text-xs text-[var(--muted)]">
              Hành động này không thể hoàn tác.
            </p>
          </div>
        </div>

        <div
          class="mt-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-xs font-semibold text-[var(--muted)]"
        >
          Phòng
          <span class="font-bold text-[var(--text)]"
            >"{{
              forceDeleteModal.room?.name || forceDeleteModal.room?.title
            }}"</span
          >
          sẽ bị xóa hoàn toàn khỏi hệ thống và không thể khôi phục.
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
          <button
            type="button"
            class="btn-ghost px-5 py-2.5 text-xs font-extrabold"
            :disabled="forceDeleteModal.loading"
            @click="closeForceDeleteModal"
          >
            Hủy
          </button>
          <button
            type="button"
            class="rounded-full bg-rose-500 hover:bg-rose-600 px-5 py-2.5 text-xs font-extrabold text-white shadow-lg shadow-rose-500/20 transition-all disabled:opacity-50"
            :disabled="forceDeleteModal.loading"
            @click="confirmForceDelete"
          >
            {{ forceDeleteModal.loading ? "Đang xóa..." : "Xóa vĩnh viễn" }}
          </button>
        </div>
      </div>
    </div>

    <!-- DETAIL DRAWER MODAL -->
    <div
      v-if="detailOpen"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
      @click.self="closeDetail"
    >
      <aside
        class="ml-auto flex h-full w-full max-w-full md:max-w-3xl lg:max-w-5xl flex-col border-l border-[var(--border)] bg-[var(--surface-strong)] shadow-[var(--shadow-soft)]"
      >
        <header
          class="flex items-start justify-between gap-4 border-b border-[var(--border)] p-5"
        >
          <div>
            <p
              class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
            >
              Phòng bài tập
            </p>
            <h2 class="mt-1 text-2xl font-black text-[var(--text)]">
              {{ detailTitle }}
            </h2>
            <p class="mt-1 text-sm font-bold text-[var(--muted)]">
              {{ detailCode }}
            </p>
          </div>
          <button
            class="btn-ghost px-4 py-2"
            type="button"
            @click="closeDetail"
          >
            Đóng
          </button>
        </header>

        <div
          v-if="detailLoading"
          class="grid flex-1 place-items-center p-8 text-sm font-bold text-[var(--muted)]"
        >
          Đang tải chi tiết...
        </div>
        <div
          v-else-if="detailError"
          class="m-5 rounded-[1.5rem] border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300"
        >
          {{ detailError }}
        </div>
        <div v-else class="min-h-0 flex-1 overflow-y-auto p-5 scrollbar-soft">
          <div class="mb-5 flex flex-wrap gap-2">
            <button
              v-for="tab in detailTabs"
              :key="tab.value"
              type="button"
              class="rounded-full border px-4 py-2 text-sm font-black transition"
              :class="
                detailTab === tab.value
                  ? 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--primary)]'
                  : 'border-[var(--border)] text-[var(--muted)] hover:text-[var(--text)]'
              "
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

const loadStats = async () => {
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
    return "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
  }
  if (["finished", "closed"].includes(status)) {
    return "bg-slate-500/10 text-slate-400 border-slate-500/20";
  }
  if (["banned"].includes(status)) {
    return "bg-rose-500/10 text-rose-400 border-rose-500/20";
  }
  return "bg-[var(--chip-active)] text-[var(--primary)] border-[var(--border-strong)]";
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
            "rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center text-sm font-bold text-[var(--muted)]",
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
                "rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4",
            },
            [
              h(
                "p",
                {
                  class:
                    "text-xs font-black uppercase tracking-[0.12em] text-[var(--muted)]",
                },
                row.label,
              ),
              h(
                "b",
                {
                  class: "mt-1.5 block break-words text-[var(--text)] text-sm",
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
        ? h("div", { class: "overflow-x-auto scrollbar-soft" }, [
            h(
              "table",
              {
                class:
                  "min-w-[800px] w-full border-separate border-spacing-y-2 text-left text-sm",
              },
              [
                h(
                  "thead",
                  { class: "text-xs font-bold uppercase text-[var(--muted)]" },
                  [
                    h(
                      "tr",
                      {},
                      props.columns.map((column) =>
                        h("th", { class: "px-4 py-2" }, column.label),
                      ),
                    ),
                  ],
                ),
                h(
                  "tbody",
                  {},
                  props.rows.map((row) =>
                    h(
                      "tr",
                      { class: "bg-[var(--surface-soft)] text-[var(--text)]" },
                      props.columns.map((column, index) =>
                        h(
                          "td",
                          {
                            class: `${index === 0 ? "rounded-l-xl" : ""} ${index === props.columns.length - 1 ? "rounded-r-xl" : ""} px-4 py-3 text-xs`,
                          },
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

onMounted(() => {
  loadStats();
  loadRooms();
});
</script>