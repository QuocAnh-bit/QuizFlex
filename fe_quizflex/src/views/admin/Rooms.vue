<template>
  <section class="grid gap-6">
    <!-- Header Page -->
    <!-- <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Quản lý phòng (Homework & Live)</h1>
          <p class="mt-1 text-sm font-medium text-[var(--muted)]">
            Quản lý tất cả phòng học và phòng live trên hệ thống
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
    </div> -->

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
      <!-- Card 1: Tổng số phòng -->
      <div
        class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-500"
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
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">
              Tổng số phòng
            </p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">
              {{ formatNumber(stats.total_rooms) }}
            </p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">
          Tất cả phòng
        </p>
      </div>

      <!-- Card 2: Phòng Homework -->
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
              Phòng bài tập
            </p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">
              {{ formatNumber(stats.homework_total) }}
            </p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">
          {{ stats.homework_percent }}% tổng số
        </p>
      </div>

      <!-- Card 3: Phòng Live -->
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
              <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9" />
              <path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5" />
              <circle cx="12" cy="12" r="2" />
              <path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5" />
              <path d="M19.1 4.9c3.9 3.9 3.9 10.3 0 14.2" />
            </svg>
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">
              Phòng thi đấu
            </p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">
              {{ formatNumber(stats.live_total) }}
            </p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">
          {{ stats.live_percent }}% tổng số
        </p>
      </div>

      <!-- Card 4: Đang hoạt động -->
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

      <!-- Card 5: Trong thùng rác -->
      <div
        class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5 col-span-2 lg:col-span-1"
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
              <span>Tất cả phòng</span>
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
                <option value="playing">Đang diễn ra</option>
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
        <!-- ALL ROOMS TABLE -->
        <table
          v-if="viewMode === 'all'"
          class="w-full min-w-[950px] border-separate border-spacing-y-2 text-left text-sm"
        >
          <thead class="text-xs font-bold text-[var(--muted)]">
            <tr>
              <th class="px-4 py-2 w-12">#</th>
              <th class="px-4 py-2">Tên phòng</th>
              <th class="px-4 py-2">Loại phòng</th>
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
              :key="`${room.room_type || 'r'}-${room.id}`"
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

              <!-- Room Type Badge -->
              <td class="px-4 py-3.5">
                <span
                  v-if="room.room_type === 'homework'"
                  class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 px-2.5 py-1 text-xs font-extrabold text-blue-400"
                >
                  <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                  >
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path
                      d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"
                    />
                  </svg>
                  <span>Bài tập</span>
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 px-2.5 py-1 text-xs font-extrabold text-purple-400"
                >
                  <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                  >
                    <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9" />
                    <circle cx="12" cy="12" r="2" />
                    <path d="M19.1 4.9c3.9 3.9 3.9 10.3 0 14.2" />
                  </svg>
                  <span>Thi đấu</span>
                </span>
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

                  <!-- Lock / Ban: (Hidden for finished Live rooms!) -->
                  <template v-if="canBanRoom(room)">
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
                  </template>

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

        <!-- TRASH TABLE -->
        <table
          v-else
          class="w-full min-w-[950px] border-separate border-spacing-y-2 text-left text-sm"
        >
          <thead class="text-xs font-bold text-[var(--muted)]">
            <tr>
              <th class="px-4 py-2 w-12">#</th>
              <th class="px-4 py-2">Tên phòng</th>
              <th class="px-4 py-2">Loại phòng</th>
              <th class="px-4 py-2">Chủ phòng</th>
              <th class="px-4 py-2">Mã phòng</th>
              <th class="px-4 py-2">Đã xóa lúc</th>
              <th class="px-4 py-2 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(room, index) in listState.items"
              :key="`trash-${room.room_type || 'r'}-${room.id}`"
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
                <span
                  v-if="room.room_type === 'homework'"
                  class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 px-2.5 py-1 text-xs font-extrabold text-blue-400"
                >
                  <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                  >
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path
                      d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"
                    />
                  </svg>
                  <span>Bài tập</span>
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 px-2.5 py-1 text-xs font-extrabold text-purple-400"
                >
                  <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                  >
                    <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9" />
                    <circle cx="12" cy="12" r="2" />
                    <path d="M19.1 4.9c3.9 3.9 3.9 10.3 0 14.2" />
                  </svg>
                  <span>Thi đấu</span>
                </span>
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
              ? 'Chưa có phòng nào trong thùng rác.'
              : 'Không có phòng nào phù hợp với bộ lọc.'
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
          {{ formatNumber(listState.meta.total || 0) }} phòng</span
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
              {{ detailType === "homework" ? "Phòng bài tập" : "Phòng thi đấu" }}
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

          <HomeworkDetail
            v-if="detailType === 'homework'"
            :detail="detailData"
            :tab="detailTab"
          />
          <LiveDetail
            v-if="detailType === 'live'"
            :detail="detailData"
            :tab="detailTab"
          />
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

const props = defineProps({
  roomType: {
    type: String,
    default: "homework",
    validator: (value) => ["homework", "live", "all"].includes(value),
  },
});

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

// Summary Statistics state
const stats = reactive({
  total_rooms: 0,
  homework_total: 0,
  homework_percent: 0,
  live_total: 0,
  live_percent: 0,
  active_total: 0,
  active_percent: 0,
  trash_total: 0,
  trash_percent: 0,
});

const loadStats = async () => {
  try {
    const data = await adminRoomsApi.getStats();
    if (data) {
      Object.assign(stats, data);
    }
  } catch (err) {
    console.error("Lỗi khi tải thống kê phòng:", err);
  }
};

// View & Filter States
const viewMode = ref("all"); // 'all' or 'trash'
const roomTypeFilter = ref(props.roomType || "homework");
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

    if (viewMode.value === "trash") {
      if (roomTypeFilter.value === "homework") {
        const payload = await adminRoomApi.getHomeworkRoomsTrash(params);
        setSingleTypeList(payload, "homework");
      } else if (roomTypeFilter.value === "live") {
        const payload = await adminRoomApi.getLiveRoomsTrash(params);
        setSingleTypeList(payload, "live");
      } else {
        const [hw, live] = await Promise.all([
          adminRoomApi.getHomeworkRoomsTrash(params),
          adminRoomApi.getLiveRoomsTrash(params),
        ]);
        setCombinedList(hw, live, page);
      }
    } else {
      if (roomTypeFilter.value === "homework") {
        const payload = await adminRoomApi.getHomeworkRooms(params);
        setSingleTypeList(payload, "homework");
      } else if (roomTypeFilter.value === "live") {
        const payload = await adminRoomApi.getLiveRooms(params);
        setSingleTypeList(payload, "live");
      } else {
        const [hw, live] = await Promise.all([
          adminRoomApi.getHomeworkRooms(params),
          adminRoomApi.getLiveRooms(params),
        ]);
        setCombinedList(hw, live, page);
      }
    }
  } catch (error) {
    listState.error = error.message || "Không tải được danh sách phòng.";
  } finally {
    listState.loading = false;
  }
};

const setSingleTypeList = (payload, type) => {
  const items = (payload?.items || []).map((item) => ({
    ...item,
    room_type: type,
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

const setCombinedList = (hw, live, page) => {
  const hwItems = (hw?.items || []).map((item) => ({
    ...item,
    room_type: "homework",
  }));
  const liveItems = (live?.items || []).map((item) => ({
    ...item,
    room_type: "live",
  }));
  const combined = [...hwItems, ...liveItems].sort(
    (a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0),
  );

  const total = Number(hw?.meta?.total || 0) + Number(live?.meta?.total || 0);
  listState.items = combined;
  listState.meta = {
    current_page: page,
    last_page: Math.max(
      Number(hw?.meta?.last_page || 1),
      Number(live?.meta?.last_page || 1),
    ),
    per_page: 10,
    total,
    from: total > 0 ? (page - 1) * 10 + 1 : 0,
    to: total > 0 ? Math.min(page * 10, total) : 0,
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
const getRoomKey = (room) => `${room.room_type || "room"}:${room.id}`;

// Room Actions Helpers
const getStatusLabel = (room) => {
  const status = String(room.status || "").toLowerCase();
  if (room.room_type === "homework") {
    return homeworkStatusLabel(status);
  }
  return liveStatusLabel(status);
};

const getStatusPillClass = (room) => {
  const status = String(room.status || "").toLowerCase();
  if (["active", "open", "playing"].includes(status)) {
    return "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
  }
  if (["waiting", "pending"].includes(status)) {
    return "bg-amber-500/10 text-amber-400 border-amber-500/20";
  }
  if (["finished", "closed"].includes(status)) {
    return "bg-slate-500/10 text-slate-400 border-slate-500/20";
  }
  if (["banned"].includes(status)) {
    return "bg-rose-500/10 text-rose-400 border-rose-500/20";
  }
  return "bg-[var(--chip-active)] text-[var(--primary)] border-[var(--border-strong)]";
};

const formatMemberCount = (room) => {
  if (room.room_type === "homework") {
    return `${formatNumber(room.member_count)} HV`;
  }
  return `${formatNumber(room.player_count)} người`;
};

// Ban Restrictions Check for Finished Live Rooms
const isLiveFinished = (room) => {
  if (!room || room.room_type !== "live") return false;
  const status = String(room.status || "").toLowerCase();
  return status === "finished" || Boolean(room.finished_at || room.ended_at);
};

const canBanRoom = (room) => {
  if (!room) return false;
  if (room.room_type === "live" && isLiveFinished(room)) {
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
  if (room.room_type === "live" && isLiveFinished(room)) {
    if (showToast) showToast("Không thể khóa phòng thi đấu đã kết thúc.", "error");
    return;
  }

  const title = "Khóa phòng (Ban)";
  const message =
    room.room_type === "homework"
      ? `Khóa phòng bài tập "${room.name || room.id}"? Học sinh và Chủ phòng sẽ không thể thao tác.`
      : `Khóa phòng thi đấu "${room.title || room.id}"? Trận đấu đang diễn ra sẽ bị kết thúc.`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      if (room.room_type === "homework") {
        const updated = await adminRoomApi.banHomeworkRoom(room.id);
        updateLocalRoom(room.id, room.room_type, updated);
      } else {
        const updated = await adminRoomApi.banLiveRoom(room.id);
        updateLocalRoom(room.id, room.room_type, updated);
      }
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
  const message = `Mở khóa phòng "${room.name || room.title || room.id}"?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      if (room.room_type === "homework") {
        const updated = await adminRoomApi.unbanHomeworkRoom(room.id);
        updateLocalRoom(room.id, room.room_type, updated);
      } else {
        const updated = await adminRoomApi.unbanLiveRoom(room.id);
        updateLocalRoom(room.id, room.room_type, updated);
      }
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
  const message = `Chuyển phòng "${room.name || room.title || room.id}" vào thùng rác?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      if (room.room_type === "homework") {
        await adminRoomApi.softDeleteHomeworkRoom(room.id);
      } else {
        await adminRoomApi.softDeleteLiveRoom(room.id);
      }
      removeLocalRoom(room.id, room.room_type);
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
  const message = `Khôi phục phòng "${room.name || room.title || room.id}"?`;

  confirmAndExecute(title, message, async () => {
    const key = getRoomKey(room);
    roomActionLoading.value = key;
    try {
      if (room.room_type === "homework") {
        await adminRoomApi.restoreHomeworkRoom(room.id);
      } else {
        await adminRoomApi.restoreLiveRoom(room.id);
      }
      removeLocalRoom(room.id, room.room_type);
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
    if (room.room_type === "homework") {
      await adminRoomsApi.forceDeleteHomework(room.id);
    } else {
      await adminRoomsApi.forceDeleteLive(room.id);
    }
    removeLocalRoom(room.id, room.room_type);
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

const updateLocalRoom = (id, type, updated) => {
  const index = listState.items.findIndex(
    (r) => String(r.id) === String(id) && r.room_type === type,
  );
  if (index !== -1) {
    listState.items[index] = { ...listState.items[index], ...updated };
  }
};

const removeLocalRoom = (id, type) => {
  listState.items = listState.items.filter(
    (r) => !(String(r.id) === String(id) && r.room_type === type),
  );
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

// Detail Drawer States & Handlers
const detailOpen = ref(false);
const detailType = ref("homework");
const detailTab = ref("info");
const detailData = ref(null);
const detailLoading = ref(false);
const detailError = ref("");

const homeworkDetailTabs = [
  { label: "Thông tin phòng", value: "info" },
  { label: "Thành viên", value: "members" },
  { label: "Bài tập đã giao", value: "assignments" },
  { label: "Lượt nộp bài", value: "submissions" },
];

const liveDetailTabs = [
  { label: "Thông tin phòng thi đấu", value: "info" },
  { label: "Người chơi", value: "players" },
  { label: "Leaderboard", value: "leaderboard" },
  { label: "Câu trả lời", value: "answers" },
  { label: "Realtime status", value: "realtime" },
];

const detailTabs = computed(() =>
  detailType.value === "homework" ? homeworkDetailTabs : liveDetailTabs,
);
const detailTitle = computed(() => {
  const room = detailData.value?.room;
  return room?.name || room?.title || "Chi tiết phòng";
});
const detailCode = computed(() =>
  detailData.value?.room?.code ? `Mã phòng: ${detailData.value.room.code}` : "",
);

const openDetail = async (room) => {
  detailOpen.value = true;
  detailType.value = room.room_type || "homework";
  detailTab.value = "info";
  detailData.value = null;
  detailLoading.value = true;
  detailError.value = "";
  try {
    if (detailType.value === "homework") {
      detailData.value = await adminRoomApi.getHomeworkRoomDetail(room.id);
    } else {
      detailData.value = await adminRoomApi.getLiveRoomDetail(room.id);
    }
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
