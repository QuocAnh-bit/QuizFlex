<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">Ngân hàng câu hỏi</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#7C3AED] text-white shadow-sm">
            <HelpCircle class="h-6 w-6" />
          </div>
          <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
              Ngân hàng câu hỏi
            </h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
              Quản lý, phân loại, kiểm duyệt thẩm định nội dung (đối chiếu phiên bản cũ/mới) và điều chỉnh hiển thị câu hỏi toàn hệ thống.
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300 shadow-sm cursor-pointer"
            @click="refreshCurrentTab"
          >
            <RefreshCw class="h-4 w-4 text-slate-500" :class="{ 'animate-spin': isLoading }" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>

      <!-- KPI Stats Cards (Clickable to switch tab / filter) -->
      <div class="mt-6 grid grid-cols-2 gap-3.5 xl:grid-cols-4">
        <!-- 1. Tổng câu hỏi -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="currentTab === 'all' ? 'border-[#7C3AED] bg-purple-50/50 ring-2 ring-[#7C3AED]/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Nhấp để xem tất cả câu hỏi"
          @click="selectTab('all')"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-[#7C3AED]">
              <FileText class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-500">Tổng câu hỏi</p>
              <p class="text-2xl font-black text-slate-900">{{ stats.total || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- 2. Công khai -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="(currentTab === 'approved' && filters.visibility === 'public') ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Nhấp để xem câu hỏi Công khai"
          @click="selectPublicKpi"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
              <Globe class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-500">Công khai</p>
              <p class="text-2xl font-black text-emerald-600">{{ stats.public || 0 }}</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-emerald-700/80 font-medium">{{ publicPercent }}% ngân hàng</p>
        </div>

        <!-- 3. Chờ duyệt -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="currentTab === 'pending' ? 'border-amber-500 bg-amber-50/60 ring-2 ring-amber-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Nhấp để duyệt các câu hỏi chờ xử lý"
          @click="selectTab('pending')"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
              <Clock class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-500">Chờ duyệt</p>
              <p class="text-2xl font-black text-amber-600">{{ stats.pending || 0 }}</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-amber-700 font-bold">Cần thẩm định</p>
        </div>

        <!-- 4. Có báo cáo / Ưu tiên -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="currentTab === 'reported' ? 'border-rose-500 bg-rose-50/60 ring-2 ring-rose-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Nhấp để xem câu hỏi có báo cáo vi phạm"
          @click="selectTab('reported')"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
              <AlertTriangle class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-500">Có báo cáo</p>
              <p class="text-2xl font-black text-rose-600">{{ stats.reported || 0 }}</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-rose-700 font-bold">Ưu tiên kiểm tra</p>
        </div>
      </div>
    </div>

    <!-- TABS BAR -->
    <div class="flex items-center border-b border-slate-200 bg-white px-4 rounded-2xl shadow-xs overflow-x-auto gap-2 sm:gap-4 text-xs font-bold">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        class="py-3.5 px-3 border-b-2 transition flex items-center gap-2 whitespace-nowrap cursor-pointer"
        :class="currentTab === tab.key
          ? 'border-[#7C3AED] text-[#7C3AED]'
          : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'"
        @click="selectTab(tab.key)"
      >
        <span>{{ tab.label }}</span>
        <span
          v-if="tab.badge !== undefined && tab.badge !== null && tab.badge > 0"
          class="rounded-full px-2 py-0.5 text-[10px] font-bold"
          :class="tab.badgeClass || 'bg-slate-100 text-slate-700'"
        >
          {{ tab.badge }}
        </span>
      </button>
    </div>

    <!-- FILTERS BAR -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <!-- Search -->
        <div class="relative sm:col-span-2">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo nội dung, tác giả, ID..."
            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:bg-white"
            @keyup.enter="applyFilter"
          />
        </div>

        <!-- Subject Filter -->
        <div>
          <select
            v-model="filters.subject_id"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="applyFilter"
          >
            <option value="">Tất cả môn học</option>
            <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
          </select>
        </div>

        <!-- Grade Filter -->
        <div>
          <select
            v-model="filters.grade_id"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="applyFilter"
          >
            <option value="">Tất cả khối lớp</option>
            <option v-for="gr in grades" :key="gr.id" :value="gr.id">{{ gr.name }}</option>
          </select>
        </div>

        <!-- Visibility Filter (only relevant when not in trash or review tabs) -->
        <div>
          <select
            v-model="filters.visibility"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="applyFilter"
          >
            <option value="all">Tất cả hiển thị</option>
            <option value="public">Công khai (Public)</option>
            <option value="private">Riêng tư (Private)</option>
          </select>
        </div>
      </div>

      <!-- Advanced filter toggle & reset -->
      <div v-if="hasActiveFilters" class="flex items-center justify-between border-t border-slate-100 pt-2.5 text-xs">
        <span class="text-slate-500 font-medium">Đang áp dụng bộ lọc</span>
        <button
          type="button"
          class="font-bold text-[#7C3AED] hover:underline cursor-pointer"
          @click="resetFilters"
        >
          Đặt lại bộ lọc
        </button>
      </div>
    </div>

    <!-- BULK ACTIONS TOOLBAR -->
    <div
      v-if="selectedIds.length > 0"
      class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-purple-200 bg-purple-50 p-4 shadow-sm"
    >
      <div class="flex items-center gap-2">
        <span class="grid h-6 w-6 place-items-center rounded-md bg-[#7C3AED] text-xs font-bold text-white">
          {{ selectedIds.length }}
        </span>
        <span class="text-xs font-bold text-purple-900">
          Đang chọn {{ selectedIds.length }} câu hỏi
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Actions for pending tab -->
        <template v-if="currentTab === 'pending'">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-50 cursor-pointer"
            :disabled="isProcessingBulk"
            @click="handleBulkApprove"
          >
            <Check class="h-3.5 w-3.5" />
            <span>Duyệt hàng loạt ({{ selectedIds.length }})</span>
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3.5 py-1.5 text-xs font-bold text-rose-600 shadow-sm hover:bg-rose-50 transition disabled:opacity-50 cursor-pointer"
            :disabled="isProcessingBulk"
            @click="openBulkRejectModal"
          >
            <X class="h-3.5 w-3.5" />
            <span>Từ chối hàng loạt</span>
          </button>
        </template>

        <!-- Actions for trash tab -->
        <template v-else-if="currentTab === 'trash'">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-50 cursor-pointer"
            :disabled="isProcessingBulk"
            @click="handleBulkRestore"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            <span>Khôi phục hàng loạt</span>
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3.5 py-1.5 text-xs font-bold text-rose-600 shadow-sm hover:bg-rose-50 transition disabled:opacity-50 cursor-pointer"
            :disabled="isProcessingBulk"
            @click="handleBulkForceDelete"
          >
            <Trash2 class="h-3.5 w-3.5" />
            <span>Xóa vĩnh viễn hàng loạt</span>
          </button>
        </template>

        <!-- Actions for standard/approved bank tabs -->
        <template v-else>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 cursor-pointer"
            @click="handleBulkVisibility(true)"
          >
            <Globe class="h-3.5 w-3.5" />
            <span>Duyệt công khai</span>
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 transition hover:bg-amber-100 cursor-pointer"
            @click="handleBulkVisibility(false)"
          >
            <Lock class="h-3.5 w-3.5" />
            <span>Chuyển riêng tư</span>
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3.5 py-1.5 text-xs font-bold text-rose-600 shadow-sm hover:bg-rose-50 transition cursor-pointer"
            @click="handleBulkDelete"
          >
            <Trash2 class="h-3.5 w-3.5" />
            <span>Xóa vào thùng rác</span>
          </button>
        </template>
      </div>
    </div>

    <!-- ERROR STATE -->
    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold text-rose-700">
      {{ errorMessage }}
    </div>

    <!-- DATA TABLE -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <th class="w-10 p-3.5 text-center">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded accent-[#7C3AED] cursor-pointer"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="p-3.5 min-w-[280px]">Câu hỏi</th>
              <th class="p-3.5 min-w-[140px]">Tác giả</th>
              <th class="p-3.5 text-center min-w-[110px]">Trạng thái duyệt</th>
              <th class="p-3.5 text-center min-w-[100px]">Hiển thị</th>
              <th class="p-3.5 text-center min-w-[110px]">Báo cáo / Ưu tiên</th>
              <th class="p-3.5 text-center min-w-[120px]">Thời gian</th>
              <th class="p-3.5 text-right min-w-[160px]">Hành động</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 font-medium">
            <!-- Loading Row -->
            <tr v-if="isLoading">
              <td colspan="8" class="p-12 text-center text-slate-400">
                <RefreshCw class="mx-auto mb-2 h-6 w-6 animate-spin text-[#7C3AED]" />
                <span>Đang tải danh sách câu hỏi...</span>
              </td>
            </tr>

            <!-- Empty Row -->
            <tr v-else-if="items.length === 0">
              <td colspan="8" class="p-12 text-center text-slate-400">
                <Inbox class="mx-auto mb-2 h-8 w-8 opacity-40 text-slate-400" />
                <p class="text-xs font-semibold text-slate-700">Không tìm thấy câu hỏi nào</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Thử thay đổi bộ lọc hoặc chọn tab khác.</p>
              </td>
            </tr>

            <!-- Data Rows -->
            <tr
              v-for="item in items"
              :key="item.id"
              class="hover:bg-slate-50/80 transition"
              :class="selectedIds.includes(item.id) ? 'bg-purple-50/30' : ''"
            >
              <!-- Checkbox -->
              <td class="p-3.5 text-center align-middle">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded accent-[#7C3AED] cursor-pointer"
                  :value="item.id"
                  :checked="selectedIds.includes(item.id)"
                  @change="toggleSelect(item.id)"
                />
              </td>

              <!-- Question Info -->
              <td class="p-3.5 align-top max-w-md">
                <div class="space-y-1.5">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-700">
                      #{{ item.id }}
                    </span>
                    <span v-if="item.subject_name || item.subject?.name" class="rounded bg-purple-50 px-1.5 py-0.5 text-[10px] font-bold text-purple-700">
                      {{ item.subject_name || item.subject?.name }}
                    </span>
                    <span v-if="item.grade_name || item.grade?.name" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">
                      {{ item.grade_name || item.grade?.name }}
                    </span>
                    <span
                      class="rounded px-1.5 py-0.5 text-[10px] font-bold"
                      :class="{
                        'bg-emerald-50 text-emerald-700': item.difficulty === 'easy',
                        'bg-amber-50 text-amber-700': item.difficulty === 'medium',
                        'bg-rose-50 text-rose-700': item.difficulty === 'hard'
                      }"
                    >
                      {{ getDifficultyLabel(item.difficulty) }}
                    </span>
                    <span v-if="item.revision_number && item.revision_number > 1" class="rounded bg-purple-100 text-[#7C3AED] px-1.5 py-0.5 text-[10px] font-bold">
                      Rev #{{ item.revision_number }}
                    </span>
                  </div>

                  <p class="text-xs font-bold text-slate-900 line-clamp-2 leading-relaxed">
                    {{ item.content || item.text }}
                  </p>

                  <div v-if="item.topic_name" class="text-[11px] text-slate-400">
                    Chủ đề: {{ item.topic_name }}
                  </div>
                </div>
              </td>

              <!-- Author -->
              <td class="p-3.5 align-top">
                <div class="space-y-0.5">
                  <p class="font-bold text-slate-900 truncate">{{ item.author_name || item.user?.name || 'Vô danh' }}</p>
                  <p class="text-[10px] text-slate-400 truncate">{{ item.author_email || item.user?.email }}</p>
                </div>
              </td>

              <!-- Review Status (Separate Column) -->
              <td class="p-3.5 align-top text-center">
                <span
                  class="inline-block rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider"
                  :class="getReviewStatusClass(item.bank_submission_status)"
                >
                  {{ getReviewStatusLabel(item.bank_submission_status) }}
                </span>
                <div v-if="item.bank_submission_status === 'rejected' && (item.rejection_reason || item.bank_submission_note)" class="mt-1 text-[10px] text-rose-600 truncate max-w-[120px]" :title="item.rejection_reason || item.bank_submission_note">
                  "{{ item.rejection_reason || item.bank_submission_note }}"
                </div>
              </td>

              <!-- Visibility (Separate Column) -->
              <td class="p-3.5 align-top text-center">
                <span
                  class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider"
                  :class="item.is_public
                    ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                    : 'bg-slate-100 text-slate-600'"
                >
                  <component :is="item.is_public ? Globe : Lock" class="h-3 w-3" />
                  <span>{{ item.is_public ? 'Công khai' : 'Riêng tư' }}</span>
                </span>
              </td>

              <!-- Report / Priority Badge -->
              <td class="p-3.5 align-top text-center">
                <div v-if="item.is_priority || item.review_priority === 'high' || item.reports_count > 0 || item.has_report" class="space-y-1">
                  <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 text-rose-800 border border-rose-300 px-2 py-0.5 text-[10px] font-black shadow-2xs">
                    <AlertTriangle class="h-3 w-3 text-rose-600" />
                    <span>🔴 ƯU TIÊN</span>
                  </span>
                  <p v-if="item.reports_count > 0" class="text-[10px] text-rose-600 font-bold">
                    {{ item.reports_count }} báo cáo
                  </p>
                </div>
                <span v-else class="text-[10px] text-slate-400">—</span>
              </td>

              <!-- Timestamp -->
              <td class="p-3.5 align-top text-center text-slate-500 text-[11px] whitespace-nowrap">
                {{ formatDate(item.deleted_at || item.bank_submission_at || item.updated_at || item.created_at) }}
              </td>

              <!-- Actions -->
              <td class="p-3.5 align-top text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- 1. TRASH ACTIONS -->
                  <template v-if="currentTab === 'trash'">
                    <!-- Restore from Trash -->
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-xs"
                      title="Khôi phục câu hỏi"
                      @click="handleSingleRestore(item.id)"
                    >
                      <RotateCcw class="h-3 w-3" />
                      <span>Khôi phục</span>
                    </button>

                    <!-- Force Delete from Trash -->
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg border border-rose-200 bg-white p-1.5 text-rose-600 hover:bg-rose-50 transition cursor-pointer shadow-xs"
                      title="Xóa vĩnh viễn"
                      @click="handleSingleForceDelete(item.id)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </button>
                  </template>

                  <!-- 2. PENDING ACTIONS: Xem chi tiết & đối chiếu thẩm định (KHÔNG duyệt/từ chối ngoài bảng) -->
                  <template v-else-if="item.bank_submission_status === 'pending'">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-purple-50 px-2.5 py-1.5 text-[11px] font-bold text-[#7C3AED] hover:bg-purple-100 transition cursor-pointer shadow-xs"
                      :title="item.revision_number > 1 ? 'So sánh phiên bản Cũ và Mới' : 'Xem chi tiết thẩm định'"
                      @click="openReviewDetail(item.id)"
                    >
                      <GitCompare class="h-3.5 w-3.5" />
                      <span>{{ item.revision_number > 1 ? 'So sánh' : 'Xem chi tiết' }}</span>
                    </button>

                    <router-link
                      :to="`/admin/questions/${item.id}`"
                      class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                      title="Xem trang chi tiết"
                    >
                      <Eye class="h-3.5 w-3.5" />
                    </router-link>
                  </template>

                  <!-- 3. APPROVED ACTIONS: Xem chi tiết, Đổi ẩn/hiện, Xóa vào thùng rác -->
                  <template v-else-if="item.bank_submission_status === 'approved'">
                    <router-link
                      :to="`/admin/questions/${item.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-xs"
                      title="Xem chi tiết câu hỏi"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span>Xem chi tiết</span>
                    </router-link>

                    <!-- Toggle Visibility -->
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                      :title="item.is_public ? 'Chuyển sang Riêng tư' : 'Duyệt Công khai'"
                      @click="toggleSingleVisibility(item.id)"
                    >
                      <component :is="item.is_public ? Lock : Globe" class="h-3.5 w-3.5" />
                    </button>

                    <!-- Delete to Trash -->
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition cursor-pointer"
                      title="Chuyển vào thùng rác"
                      @click="handleSingleDelete(item.id)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </button>
                  </template>

                  <!-- 4. REJECTED ACTIONS: Xem chi tiết & lý do (KHÔNG có duyệt/từ chối) -->
                  <template v-else-if="item.bank_submission_status === 'rejected'">
                    <router-link
                      :to="`/admin/questions/${item.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-xs"
                      title="Xem chi tiết & Lý do từ chối"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span>Xem chi tiết</span>
                    </router-link>

                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-purple-50 px-2 py-1.5 text-[11px] font-bold text-[#7C3AED] hover:bg-purple-100 transition cursor-pointer shadow-xs"
                      title="Xem lịch sử các phiên bản"
                      @click="openReviewDetail(item.id)"
                    >
                      <History class="h-3.5 w-3.5" />
                      <span>Lịch sử</span>
                    </button>
                  </template>

                  <!-- 5. DEFAULT / OTHER -->
                  <template v-else>
                    <router-link
                      :to="`/admin/questions/${item.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-xs"
                      title="Xem chi tiết"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span>Xem chi tiết</span>
                    </router-link>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION -->
      <div v-if="pagination.lastPage > 1" class="flex flex-wrap items-center justify-between border-t border-slate-100 bg-slate-50/50 p-4 text-xs font-medium text-slate-600">
        <span>
          Hiển thị trang {{ pagination.currentPage }} / {{ pagination.lastPage }} (Tổng {{ pagination.total }} câu hỏi)
        </span>

        <div class="flex items-center gap-1.5">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 hover:bg-slate-50 disabled:opacity-40 cursor-pointer"
            :disabled="pagination.currentPage <= 1"
            @click="changePage(pagination.currentPage - 1)"
          >
            ← Trước
          </button>

          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 hover:bg-slate-50 disabled:opacity-40 cursor-pointer"
            :disabled="pagination.currentPage >= pagination.lastPage"
            @click="changePage(pagination.currentPage + 1)"
          >
            Sau →
          </button>
        </div>
      </div>
    </div>

    <!-- =========================================================================
         DIFF COMPARISON WORKSPACE MODAL (CRITICAL: PRESERVES 100% REVISION / DIFF)
    ========================================================================== -->
    <div
      v-if="isReviewModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 sm:p-6 overflow-y-auto backdrop-blur-xs"
      @click.self="closeReviewModal"
    >
      <div class="relative w-full max-w-5xl rounded-3xl bg-white shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="grid h-9 w-9 place-items-center rounded-xl bg-purple-100 text-[#7C3AED]">
              <GitCompare class="h-5 w-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-base font-black text-slate-900">
                  Thẩm định câu hỏi #{{ activeDetail?.question?.id || selectedQuestionId }}
                </h2>
                <span
                  v-if="activeDetail?.current_revision?.revision_number > 1"
                  class="rounded-full bg-purple-100 px-2.5 py-0.5 text-[11px] font-bold text-purple-800"
                >
                  Lần gửi duyệt #{{ activeDetail?.current_revision?.revision_number }}
                </span>
                <span
                  v-else
                  class="rounded-full bg-slate-200 px-2.5 py-0.5 text-[11px] font-bold text-slate-700"
                >
                  Lần gửi duyệt #1
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Tác giả: <strong class="text-slate-800">{{ activeDetail?.question?.author_name || activeDetail?.current_revision?.author_name || 'Vô danh' }}</strong>
                <span v-if="activeDetail?.question?.author_email || activeDetail?.current_revision?.author_email" class="text-slate-400 ml-1">
                  ({{ activeDetail?.question?.author_email || activeDetail?.current_revision?.author_email }})
                </span>
              </p>
            </div>
          </div>

          <button
            type="button"
            class="rounded-xl p-2 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition cursor-pointer"
            @click="closeReviewModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div v-if="isLoadingDetail" class="p-12 text-center text-sm font-medium text-slate-400">
          <RefreshCw class="mx-auto mb-3 h-6 w-6 animate-spin text-[#7C3AED]" />
          <span>Đang tải thông tin chi tiết và lịch sử các phiên bản...</span>
        </div>

        <div v-else class="flex-1 overflow-y-auto p-6 space-y-6 bg-[#F8FAFC]">
          <!-- SECTION: QUESTION NÀY ĐÃ TỪNG ĐƯỢC BÁO CÁO (REPORT CONTEXT & RESOLUTION BANNER) -->
          <div
            v-if="activeDetail?.reports && activeDetail.reports.length > 0"
            class="rounded-2xl border border-rose-300 bg-rose-50/70 p-5 space-y-4 shadow-sm"
          >
            <!-- Header & Summary -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-rose-200/80 pb-3">
              <div class="flex items-center gap-2.5">
                <div class="grid h-8 w-8 place-items-center rounded-xl bg-rose-600 text-white shadow-2xs">
                  <AlertTriangle class="h-4 w-4" />
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h3 class="text-xs font-black uppercase tracking-wider text-rose-950">
                      Question này đã từng được báo cáo
                    </h3>
                    <span class="rounded-full bg-rose-200/80 px-2 py-0.5 text-[10px] font-black text-rose-900">
                      {{ activeDetail.reports.length }} lượt phản ánh
                    </span>
                  </div>
                  <p class="text-[11px] text-rose-800">
                    Phản ánh gần nhất: <strong>{{ formatDate(activeDetail.reports[0]?.created_at) }}</strong>
                  </p>
                </div>
              </div>

              <!-- Quick Link to Report Manager -->
              <router-link
                :to="`/admin/reports?question_id=${activeDetail?.question?.id || selectedQuestionId}`"
                target="_blank"
                class="text-[11px] font-bold text-rose-700 hover:underline inline-flex items-center gap-1 shrink-0"
              >
                <span>Xem trong Quản lý Báo cáo ↗</span>
              </router-link>
            </div>

            <!-- Reasons breakdown & Processing Status -->
            <div class="grid gap-3 sm:grid-cols-2">
              <!-- Left: Reasons Breakdown -->
              <div class="rounded-xl border border-rose-200 bg-white p-3 space-y-1.5 shadow-2xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Lý do báo cáo chính:</span>
                <div class="flex flex-wrap gap-1.5">
                  <span
                    v-for="grp in getGroupedReasons(activeDetail.reports)"
                    :key="grp.reason"
                    class="rounded-lg bg-rose-100 px-2.5 py-1 text-[11px] font-bold text-rose-800 border border-rose-200"
                  >
                    {{ grp.count }}x {{ grp.reason }}
                  </span>
                </div>
              </div>

              <!-- Right: Automation Note -->
              <div class="rounded-xl border border-blue-200 bg-blue-50/80 p-3 space-y-1 text-xs shadow-2xs">
                <div class="flex items-center gap-1.5 font-bold text-blue-950 text-[11px]">
                  <CheckCircle class="h-3.5 w-3.5 text-blue-600 shrink-0" />
                  <span>Cơ chế xử lý tự động:</span>
                </div>
                <p class="text-[11px] text-blue-800 leading-relaxed">
                  Khi Admin bấm <strong>Phê duyệt (Approve)</strong> bản sửa đổi này, hệ thống sẽ <strong>tự động chuyển tất cả báo cáo sang Đã giải quyết (Resolved)</strong>.
                </p>
              </div>
            </div>

            <!-- Individual Report Tickets list -->
            <div class="space-y-2">
              <span class="text-[10px] font-bold uppercase tracking-wider text-rose-900 block">Chi tiết các phản ánh:</span>
              <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                <div
                  v-for="rep in activeDetail.reports"
                  :key="rep.id"
                  class="rounded-xl border border-rose-200 bg-white p-3 text-xs text-rose-950 space-y-1.5 shadow-2xs"
                >
                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <span class="rounded bg-rose-100 text-rose-800 font-bold px-2 py-0.5 text-[10px]">
                        {{ rep.reason }}
                      </span>
                      <StatusBadge :value="rep.status" />
                    </div>
                    <span class="text-[10px] text-slate-500 font-medium">
                      Người báo: <strong>{{ rep.reporter_name }}</strong> ({{ rep.reporter_email }}) • {{ formatDate(rep.created_at) }}
                    </span>
                  </div>
                  <p v-if="rep.description" class="text-slate-700 italic leading-relaxed bg-slate-50 p-2 rounded-lg text-[11px]">
                    "{{ rep.description }}"
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- CASE 1: HAS PREVIOUS REVISION -> 2-COLUMN COMPARISON GRID -->
          <div v-if="activeDetail?.previous_revision" class="space-y-4">
            <div class="flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-xl p-3.5 text-xs text-purple-900 font-medium">
              <AlertCircle class="h-4 w-4 text-[#7C3AED] shrink-0" />
              <span>
                Tác giả đã chỉnh sửa và gửi duyệt lại (Revision #{{ activeDetail.current_revision.revision_number }}). Bảng dưới đây đối chiếu chi tiết giữa phiên bản cũ bị từ chối và nội dung mới gửi.
              </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
              <!-- LEFT COLUMN: OLD DATA (Previous Revision) -->
              <div class="rounded-2xl border border-rose-200 bg-rose-50/30 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-rose-200 pb-3">
                  <div class="flex items-center gap-2">
                    <span class="grid h-6 w-6 place-items-center rounded-lg bg-rose-600 text-white text-[11px] font-black">
                      {{ activeDetail.previous_revision.revision_number }}
                    </span>
                    <h3 class="text-sm font-black text-rose-900 uppercase tracking-wider">
                      DỮ LIỆU CŨ (Revision #{{ activeDetail.previous_revision.revision_number }})
                    </h3>
                  </div>
                  <span class="rounded-md bg-rose-100 text-rose-800 px-2 py-0.5 text-[11px] font-bold">
                    {{ getReviewStatusLabel(activeDetail.previous_revision.status) }}
                  </span>
                </div>

                <!-- Lý do từ chối lần trước -->
                <div class="rounded-xl border border-rose-300 bg-white p-3 text-xs text-rose-900 space-y-1 shadow-xs">
                  <div class="font-bold flex items-center gap-1.5 text-rose-700">
                    <XCircle class="h-4 w-4" />
                    <span>Lý do từ chối lần trước:</span>
                  </div>
                  <p class="pl-5 leading-relaxed font-medium">
                    {{ activeDetail.previous_revision.rejection_reason || 'Không có ghi chú' }}
                  </p>
                  <div v-if="activeDetail.previous_revision.reviewed_by_name || activeDetail.previous_revision.reviewed_at" class="pl-5 text-[11px] text-slate-500 pt-1">
                    Người duyệt: <strong>{{ activeDetail.previous_revision.reviewed_by_name || 'Admin' }}</strong> • {{ formatDate(activeDetail.previous_revision.reviewed_at) }}
                  </div>
                </div>

                <!-- Meta Badges Cũ -->
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                  <span class="rounded-md bg-white border border-rose-200 px-2 py-0.5 font-bold text-slate-700">
                    Độ khó: {{ getDifficultyLabel(activeDetail.previous_revision.difficulty) }}
                  </span>
                  <span v-if="activeDetail.previous_revision.subject_name" class="rounded-md bg-white border border-rose-200 px-2 py-0.5 font-bold text-slate-700">
                    {{ activeDetail.previous_revision.subject_name }}
                  </span>
                  <span v-if="activeDetail.previous_revision.grade_name" class="rounded-md bg-white border border-rose-200 px-2 py-0.5 font-medium text-slate-600">
                    {{ activeDetail.previous_revision.grade_name }}
                  </span>
                </div>

                <!-- Nội dung câu hỏi cũ -->
                <div class="space-y-1">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Nội dung câu hỏi cũ:</span>
                  <div class="rounded-xl border border-rose-200 bg-white p-3.5 text-xs font-semibold text-slate-800 leading-relaxed break-words">
                    {{ activeDetail.previous_revision.content }}
                  </div>
                </div>

                <!-- Danh sách đáp án cũ -->
                <div class="space-y-2">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Danh sách đáp án cũ:</span>
                  <div class="space-y-1.5">
                    <div
                      v-for="ans in activeDetail.previous_revision.answers"
                      :key="ans.id || ans.key"
                      class="flex items-center gap-2.5 rounded-xl border p-2.5 text-xs font-medium"
                      :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700'"
                    >
                      <span
                        class="grid h-5 w-5 shrink-0 place-items-center rounded-md text-[10px] font-bold"
                        :class="ans.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'"
                      >
                        {{ ans.key }}
                      </span>
                      <span class="flex-1 truncate">{{ ans.content || ans.text }}</span>
                      <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600 shrink-0">✓ Đúng</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- RIGHT COLUMN: NEW DATA (Current Revision) -->
              <div class="rounded-2xl border border-purple-300 bg-purple-50/30 p-5 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-purple-200 pb-3">
                  <div class="flex items-center gap-2">
                    <span class="grid h-6 w-6 place-items-center rounded-lg bg-[#7C3AED] text-white text-[11px] font-black">
                      {{ activeDetail.current_revision.revision_number }}
                    </span>
                    <h3 class="text-sm font-black text-purple-900 uppercase tracking-wider">
                      DỮ LIỆU MỚI (Revision #{{ activeDetail.current_revision.revision_number }})
                    </h3>
                  </div>
                  <span
                    class="rounded-md px-2 py-0.5 text-[11px] font-bold"
                    :class="getReviewStatusClass(activeDetail.current_revision.status)"
                  >
                    {{ getReviewStatusLabel(activeDetail.current_revision.status) }}
                  </span>
                </div>

                <!-- Ghi chú của tác giả nếu có -->
                <div v-if="activeDetail.current_revision.request_note" class="rounded-xl border border-purple-200 bg-white p-3 text-xs text-purple-900 space-y-1 shadow-xs">
                  <div class="font-bold flex items-center gap-1.5 text-purple-700">
                    <MessageSquare class="h-4 w-4" />
                    <span>Ghi chú của tác giả:</span>
                  </div>
                  <p class="pl-5 leading-relaxed font-medium">
                    "{{ activeDetail.current_revision.request_note }}"
                  </p>
                </div>

                <!-- Meta Badges Mới -->
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                  <span
                    class="rounded-md px-2 py-0.5 font-bold"
                    :class="activeDetail.current_revision.difficulty !== activeDetail.previous_revision.difficulty
                      ? 'bg-purple-600 text-white shadow-xs'
                      : 'bg-white border border-purple-200 text-slate-700'"
                  >
                    Độ khó: {{ getDifficultyLabel(activeDetail.current_revision.difficulty) }}
                  </span>
                  <span v-if="activeDetail.current_revision.subject_name" class="rounded-md bg-white border border-purple-200 px-2 py-0.5 font-bold text-purple-700">
                    {{ activeDetail.current_revision.subject_name }}
                  </span>
                  <span v-if="activeDetail.current_revision.grade_name" class="rounded-md bg-white border border-purple-200 px-2 py-0.5 font-medium text-slate-600">
                    {{ activeDetail.current_revision.grade_name }}
                  </span>
                </div>

                <!-- Nội dung câu hỏi mới -->
                <div class="space-y-1">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Nội dung câu hỏi mới:</span>
                  <div
                    class="rounded-xl border p-3.5 text-xs font-bold leading-relaxed break-words shadow-xs"
                    :class="activeDetail.current_revision.content !== activeDetail.previous_revision.content
                      ? 'border-purple-400 bg-white text-purple-950 ring-2 ring-purple-400/20'
                      : 'border-purple-200 bg-white text-slate-800'"
                  >
                    {{ activeDetail.current_revision.content }}
                  </div>
                </div>

                <!-- Danh sách đáp án mới -->
                <div class="space-y-2">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Danh sách đáp án mới:</span>
                  <div class="space-y-1.5">
                    <div
                      v-for="ans in activeDetail.current_revision.answers"
                      :key="ans.id || ans.key"
                      class="flex items-center gap-2.5 rounded-xl border p-2.5 text-xs font-semibold shadow-xs"
                      :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700'"
                    >
                      <span
                        class="grid h-5 w-5 shrink-0 place-items-center rounded-md text-[10px] font-bold"
                        :class="ans.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'"
                      >
                        {{ ans.key }}
                      </span>
                      <span class="flex-1 truncate">{{ ans.content || ans.text }}</span>
                      <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600 shrink-0 flex items-center gap-1">
                        <Check class="h-3 w-3" :stroke-width="3" />
                        <span>Đáp án đúng</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CASE 2: FIRST REVISION (NO PREVIOUS) -> CLEAN 1-COLUMN VIEW -->
          <div v-else-if="activeDetail?.current_revision" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-5 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div class="flex items-center gap-2">
                <span class="rounded-md bg-[#7C3AED] text-white px-2.5 py-1 text-xs font-bold">
                  Lần gửi duyệt đầu tiên (Revision #1)
                </span>
                <span class="text-xs text-slate-500">
                  Đây là phiên bản đầu tiên. Không có phiên bản trước để so sánh.
                </span>
              </div>
              <span
                class="rounded-md px-2.5 py-1 text-xs font-bold"
                :class="getReviewStatusClass(activeDetail.current_revision.status)"
              >
                {{ getReviewStatusLabel(activeDetail.current_revision.status) }}
              </span>
            </div>

            <!-- Ghi chú nếu có -->
            <div v-if="activeDetail.current_revision.request_note" class="rounded-xl border border-purple-200 bg-purple-50 p-3.5 text-xs text-purple-900">
              <strong>Ghi chú tác giả:</strong> {{ activeDetail.current_revision.request_note }}
            </div>

            <!-- Meta Badges -->
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                Độ khó: {{ getDifficultyLabel(activeDetail.current_revision.difficulty) }}
              </span>
              <span v-if="activeDetail.current_revision.subject_name" class="rounded-md bg-purple-50 text-[#7C3AED] px-2.5 py-1 text-xs font-bold">
                {{ activeDetail.current_revision.subject_name }}
              </span>
              <span v-if="activeDetail.current_revision.grade_name" class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                {{ activeDetail.current_revision.grade_name }}
              </span>
            </div>

            <!-- Nội dung câu hỏi -->
            <div class="space-y-1.5">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Nội dung câu hỏi:</h4>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-900 leading-relaxed">
                {{ activeDetail.current_revision.content }}
              </div>
            </div>

            <!-- Danh sách đáp án -->
            <div class="space-y-2">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Danh sách đáp án:</h4>
              <div class="grid gap-2 sm:grid-cols-2">
                <div
                  v-for="ans in activeDetail.current_revision.answers"
                  :key="ans.id || ans.key"
                  class="flex items-center gap-2.5 rounded-xl border p-3 text-xs font-semibold"
                  :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-700'"
                >
                  <span
                    class="grid h-6 w-6 shrink-0 place-items-center rounded-md text-[11px] font-bold"
                    :class="ans.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ ans.key }}
                  </span>
                  <span class="flex-1 truncate">{{ ans.content || ans.text }}</span>
                  <span v-if="ans.is_correct" class="ml-auto text-[11px] font-bold text-emerald-600 shrink-0">Đáp án đúng</span>
                </div>
              </div>
            </div>
          </div>

          <!-- REVISION HISTORY TIMELINE -->
          <div v-if="activeDetail?.history && activeDetail.history.length > 1" class="border-t border-slate-200 pt-5 space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
              <History class="h-4 w-4 text-[#7C3AED]" />
              <span>Lịch sử các vòng thẩm định ({{ activeDetail.history.length }} lần gửi):</span>
            </h4>

            <div class="space-y-2">
              <div
                v-for="h in activeDetail.history"
                :key="h.id"
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3 text-xs shadow-2xs"
              >
                <div class="flex items-center gap-3">
                  <span class="font-black text-slate-900">Revision #{{ h.revision_number }}</span>
                  <span
                    class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                    :class="getReviewStatusClass(h.status)"
                  >
                    {{ getReviewStatusLabel(h.status) }}
                  </span>
                  <span class="text-slate-500 text-[11px]">• {{ formatDate(h.created_at) }}</span>
                </div>

                <div v-if="h.rejection_reason" class="text-rose-700 font-medium truncate max-w-sm" :title="h.rejection_reason">
                  Lý do từ chối: {{ h.rejection_reason }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="flex items-center justify-between border-t border-slate-100 bg-white px-6 py-4 shrink-0">
          <button
            type="button"
            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer"
            @click="closeReviewModal"
          >
            Đóng
          </button>

          <div class="flex items-center gap-2.5">
            <template v-if="activeDetail?.question?.bank_submission_status === 'pending' || activeDetail?.current_revision?.status === 'pending'">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 cursor-pointer transition shadow-xs"
                @click="openRejectModalFromDetail"
              >
                <X class="h-4 w-4" />
                <span>Từ chối</span>
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white hover:bg-emerald-700 cursor-pointer transition shadow-xs"
                @click="handleApproveFromDetail"
              >
                <Check class="h-4 w-4" />
                <span>Phê duyệt vào Ngân hàng</span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================================
         REJECT MODAL (WITH REASON)
    ========================================================================== -->
    <div
      v-if="isRejectModalOpen"
      class="fixed inset-0 z-60 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
    >
      <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-sm font-black text-rose-700 flex items-center gap-2">
            <AlertCircle class="h-4 w-4" />
            <span>Từ chối phê duyệt câu hỏi</span>
          </h3>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer"
            @click="isRejectModalOpen = false"
          >
            ✕
          </button>
        </div>

        <p class="text-xs leading-relaxed text-slate-600">
          Vui lòng nhập lý do từ chối cụ thể để tác giả biết và chỉnh sửa lại câu hỏi.
        </p>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Lý do từ chối <span class="text-rose-500">*</span></label>
          <textarea
            v-model="rejectReason"
            rows="4"
            class="w-full rounded-xl border border-slate-200 p-3 text-xs outline-none focus:border-rose-500 resize-none font-medium text-slate-900"
            placeholder="Ví dụ: Sai đáp án đúng ở phương án B, nội dung câu hỏi chưa rõ ràng..."
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer"
            @click="isRejectModalOpen = false"
          >
            Hủy
          </button>

          <button
            type="button"
            class="rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white hover:bg-rose-700 cursor-pointer disabled:opacity-50 transition"
            :disabled="!rejectReason.trim() || isSubmittingReject"
            @click="executeReject"
          >
            <span>{{ isSubmittingReject ? 'Đang gửi...' : 'Xác nhận từ chối' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  AlertCircle,
  AlertTriangle,
  Check,
  CheckCircle,
  ChevronRight,
  Clock,
  Eye,
  FileText,
  GitCompare,
  Globe,
  HelpCircle,
  History,
  Inbox,
  Lock,
  MessageSquare,
  RefreshCw,
  RotateCcw,
  Search,
  Trash2,
  X,
  XCircle,
} from 'lucide-vue-next'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { adminBankRequestsApi, adminQuestionsApi, taxonomyApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const currentTab = ref('all')
const items = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const selectedIds = ref([])
const isProcessingBulk = ref(false)

const subjects = ref([])
const grades = ref([])

const stats = reactive({
  total: 0,
  public: 0,
  private: 0,
  pending: 0,
  rejected: 0,
  reported: 0,
  trash: 0,
})

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 15,
})

const filters = reactive({
  search: '',
  subject_id: '',
  grade_id: '',
  difficulty: '',
  visibility: 'all',
})

// Review Diff Modal State
const isReviewModalOpen = ref(false)
const selectedQuestionId = ref(null)
const activeDetail = ref(null)
const isLoadingDetail = ref(false)

// Reject Modal State
const isRejectModalOpen = ref(false)
const rejectTargetId = ref(null)
const rejectReason = ref('')
const isSubmittingReject = ref(false)
const isBulkReject = ref(false)

const tabs = computed(() => [
  { key: 'all', label: 'Tất cả' },
  { key: 'pending', label: 'Chờ duyệt', badge: stats.pending, badgeClass: 'bg-amber-100 text-amber-800' },
  { key: 'approved', label: 'Đã duyệt', badge: stats.public, badgeClass: 'bg-emerald-100 text-emerald-800' },
  { key: 'rejected', label: 'Từ chối', badge: stats.rejected, badgeClass: 'bg-rose-100 text-rose-800' },
  { key: 'reported', label: '⚠️ Có báo cáo', badge: stats.reported, badgeClass: 'bg-rose-100 text-rose-800' },
  { key: 'trash', label: 'Thùng rác', badge: stats.trash, badgeClass: 'bg-slate-100 text-slate-700' },
])

const publicPercent = computed(() => {
  if (!stats.total) return 0
  return ((stats.public / stats.total) * 100).toFixed(1)
})

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.search ||
    filters.subject_id ||
    filters.grade_id ||
    filters.difficulty ||
    filters.visibility !== 'all'
  )
})

const isAllSelected = computed(() => {
  return items.value.length > 0 && items.value.every(item => selectedIds.value.includes(item.id))
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = items.value.map(item => item.id)
  }
}

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const selectTab = (tabKey) => {
  currentTab.value = tabKey
  selectedIds.value = []
  pagination.currentPage = 1
  loadTabItems(1)
}

const selectPublicKpi = () => {
  currentTab.value = 'approved'
  filters.visibility = 'public'
  selectedIds.value = []
  pagination.currentPage = 1
  loadTabItems(1)
}

const applyFilter = () => {
  pagination.currentPage = 1
  selectedIds.value = []
  loadTabItems(1)
}

const resetFilters = () => {
  filters.search = ''
  filters.subject_id = ''
  filters.grade_id = ''
  filters.difficulty = ''
  filters.visibility = 'all'
  pagination.currentPage = 1
  selectedIds.value = []
  loadTabItems(1)
}

const refreshCurrentTab = () => {
  loadTabItems(pagination.currentPage)
}

const changePage = (page) => {
  pagination.currentPage = page
  loadTabItems(page)
}

const loadTabItems = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  pagination.currentPage = page

  try {
    if (currentTab.value === 'trash') {
      // 1. Load Trash
      const res = await adminQuestionsApi.trash({
        page,
        search: filters.search ? filters.search.trim() : undefined,
        per_page: pagination.perPage,
      })
      items.value = (res.items || []).map(q => ({
        ...q,
        bank_submission_status: 'approved',
      }))
      pagination.total = res.total || 0
      pagination.lastPage = res.lastPage || 1
      stats.trash = res.trashCount || res.total || 0
    } else if (currentTab.value === 'pending' || currentTab.value === 'rejected' || currentTab.value === 'reported') {
      // 2. Load Review Requests / Reported Requests
      const params = {
        page,
        per_page: pagination.perPage,
        status: currentTab.value === 'reported' ? 'all' : currentTab.value,
        priority: currentTab.value === 'reported' ? 'high' : undefined,
        search: filters.search ? filters.search.trim() : undefined,
        subject_id: filters.subject_id || undefined,
        grade_id: filters.grade_id || undefined,
        difficulty: filters.difficulty || undefined,
      }

      const res = await adminBankRequestsApi.fetchRequests(params)
      items.value = res.items || []
      pagination.total = res.total || 0
      pagination.lastPage = res.lastPage || 1

      if (res.stats) {
        stats.pending = res.stats.pending || 0
        stats.rejected = res.stats.rejected || 0
        stats.reported = res.stats.priority || 0
      }
    } else {
      // 3. Load All / Approved Bank Questions
      const params = {
        page,
        per_page: pagination.perPage,
        search: filters.search ? filters.search.trim() : undefined,
        subject_id: filters.subject_id || undefined,
        grade_id: filters.grade_id || undefined,
        difficulty: filters.difficulty || undefined,
        visibility: filters.visibility !== 'all' ? filters.visibility : undefined,
      }

      const res = await adminQuestionsApi.list(params)
      items.value = (res.items || []).map(q => ({
        ...q,
        bank_submission_status: q.bank_submission_status || 'approved',
      }))
      pagination.total = res.total || 0
      pagination.lastPage = res.lastPage || 1

      if (res.stats) {
        stats.total = res.stats.total || 0
        stats.public = res.stats.public || 0
        stats.private = res.stats.private || 0
        stats.reported = res.stats.reported || 0
      }
    }

    // Refresh review request pending counts in background if needed
    fetchGlobalStats()
  } catch (err) {
    console.error('Lỗi khi tải dữ liệu câu hỏi:', err)
    errorMessage.value = `Không thể tải dữ liệu câu hỏi: ${err.message || 'Lỗi mạng'}`
  } finally {
    isLoading.value = false
  }
}

const fetchGlobalStats = async () => {
  try {
    const [bankStats, reqStats, trashStats] = await Promise.allSettled([
      adminQuestionsApi.list({ per_page: 1 }),
      adminBankRequestsApi.fetchRequests({ per_page: 1, status: 'all' }),
      adminQuestionsApi.trash({ per_page: 1 }),
    ])

    if (bankStats.status === 'fulfilled' && bankStats.value.stats) {
      stats.total = bankStats.value.stats.total || 0
      stats.public = bankStats.value.stats.public || 0
      stats.private = bankStats.value.stats.private || 0
    }

    if (reqStats.status === 'fulfilled' && reqStats.value.stats) {
      stats.pending = reqStats.value.stats.pending || 0
      stats.rejected = reqStats.value.stats.rejected || 0
      stats.reported = reqStats.value.stats.priority || bankStats.value?.stats?.reported || 0
    }

    if (trashStats.status === 'fulfilled') {
      stats.trash = trashStats.value.trashCount || trashStats.value.total || 0
    }
  } catch (e) {
    console.error('Không thể cập nhật chỉ số thống kê:', e)
  }
}

const fetchTaxonomies = async () => {
  try {
    const tree = await taxonomyApi.tree()
    if (tree) {
      if (Array.isArray(tree.subjects)) subjects.value = tree.subjects
      if (Array.isArray(tree.education_levels)) {
        const grList = []
        tree.education_levels.forEach(el => {
          if (Array.isArray(el.grades)) {
            el.grades.forEach(g => {
              if (!grList.some(existing => existing.id === g.id)) grList.push(g)
            })
          }
        })
        grades.value = grList
      }
    }
  } catch (e) {
    console.error('Lỗi tải danh mục môn học:', e)
  }
}

// -----------------------------------------------------------------------------
// REVIEW / DIFF MODAL ACTIONS
// -----------------------------------------------------------------------------
const openReviewDetail = async (id) => {
  selectedQuestionId.value = id
  isReviewModalOpen.value = true
  isLoadingDetail.value = true
  activeDetail.value = null

  try {
    const res = await adminBankRequestsApi.fetchRequestDetail(id)
    activeDetail.value = res?.data || res
  } catch (err) {
    console.error('Không thể lấy thông tin thẩm định:', err)
    if (showToast) showToast('Lỗi khi tải chi tiết thẩm định câu hỏi.', 'error')
  } finally {
    isLoadingDetail.value = false
  }
}

const closeReviewModal = () => {
  isReviewModalOpen.value = false
  activeDetail.value = null
  selectedQuestionId.value = null
}

const handleApproveFromDetail = async () => {
  if (!selectedQuestionId.value) return
  await executeApprove(selectedQuestionId.value)
  closeReviewModal()
}

const openRejectModalFromDetail = () => {
  rejectTargetId.value = selectedQuestionId.value
  rejectReason.value = ''
  isBulkReject.value = false
  isRejectModalOpen.value = true
}

const handleSingleApprove = async (id) => {
  const execute = async () => {
    await executeApprove(id)
  }
  if (showConfirm) {
    showConfirm('Xác nhận duyệt câu hỏi', `Phê duyệt câu hỏi #${id} vào Ngân hàng dùng chung?`, execute)
  } else {
    execute()
  }
}

const executeApprove = async (id) => {
  try {
    const res = await adminBankRequestsApi.approve(id)
    if (showToast) showToast(res.message || 'Phê duyệt câu hỏi thành công!', 'success')
    loadTabItems(pagination.currentPage)
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Phê duyệt thất bại.'
    if (showToast) showToast(msg, 'error')
  }
}

const openSingleRejectModal = (id) => {
  rejectTargetId.value = id
  rejectReason.value = ''
  isBulkReject.value = false
  isRejectModalOpen.value = true
}

const openBulkRejectModal = () => {
  rejectReason.value = ''
  isBulkReject.value = true
  isRejectModalOpen.value = true
}

const executeReject = async () => {
  if (!rejectReason.value.trim()) return
  isSubmittingReject.value = true

  try {
    if (isBulkReject.value) {
      const res = await adminBankRequestsApi.bulkReject(selectedIds.value, rejectReason.value.trim())
      if (showToast) showToast(res.message || 'Đã từ chối các câu hỏi đã chọn.', 'success')
      selectedIds.value = []
    } else {
      const res = await adminBankRequestsApi.reject(rejectTargetId.value, {
        note: rejectReason.value.trim(),
        reason: rejectReason.value.trim(),
      })
      if (showToast) showToast(res.message || 'Đã từ chối duyệt câu hỏi.', 'success')
    }
    isRejectModalOpen.value = false
    if (isReviewModalOpen.value) closeReviewModal()
    loadTabItems(pagination.currentPage)
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Từ chối thất bại.'
    if (showToast) showToast(msg, 'error')
  } finally {
    isSubmittingReject.value = false
  }
}

const handleBulkApprove = async () => {
  if (!selectedIds.value.length) return
  const message = `Bạn có chắc chắn muốn phê duyệt ${selectedIds.value.length} câu hỏi đã chọn vào Ngân hàng?`

  const execute = async () => {
    isProcessingBulk.value = true
    try {
      const res = await adminBankRequestsApi.bulkApprove(selectedIds.value)
      if (showToast) showToast(res.message || 'Phê duyệt hàng loạt thành công!', 'success')
      selectedIds.value = []
      loadTabItems(pagination.currentPage)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    } finally {
      isProcessingBulk.value = false
    }
  }

  if (showConfirm) showConfirm('Duyệt hàng loạt', message, execute)
  else execute()
}

// -----------------------------------------------------------------------------
// VISIBILITY & DELETE / RESTORE ACTIONS
// -----------------------------------------------------------------------------
const toggleSingleVisibility = async (id) => {
  try {
    const res = await adminQuestionsApi.toggleVisibility(id)
    if (showToast) showToast(res.message || 'Cập nhật trạng thái hiển thị thành công.', 'success')
    loadTabItems(pagination.currentPage)
  } catch (err) {
    if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
  }
}

const handleBulkVisibility = async (isPublic) => {
  if (!selectedIds.value.length) return
  const actionText = isPublic ? 'Duyệt công khai' : 'Chuyển riêng tư'
  const message = `Bạn có chắc muốn ${actionText} cho ${selectedIds.value.length} câu hỏi đã chọn?`

  const execute = async () => {
    try {
      await adminQuestionsApi.bulkToggleVisibility(selectedIds.value, isPublic)
      if (showToast) showToast(`Đã ${actionText} thành công.`, 'success')
      selectedIds.value = []
      loadTabItems(pagination.currentPage)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }

  if (showConfirm) showConfirm(`${actionText} hàng loạt`, message, execute)
  else execute()
}

const handleSingleDelete = async (id) => {
  const execute = async () => {
    try {
      await adminQuestionsApi.remove(id)
      if (showToast) showToast('Đã chuyển câu hỏi vào thùng rác.', 'success')
      loadTabItems(pagination.currentPage)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }
  if (showConfirm) showConfirm('Xóa câu hỏi', 'Chuyển câu hỏi này vào thùng rác?', execute)
  else execute()
}

const handleBulkDelete = async () => {
  if (!selectedIds.value.length) return
  const execute = async () => {
    try {
      await adminQuestionsApi.bulkDelete(selectedIds.value)
      if (showToast) showToast('Đã chuyển các câu hỏi vào thùng rác.', 'success')
      selectedIds.value = []
      loadTabItems(pagination.currentPage)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }
  if (showConfirm) showConfirm('Xóa hàng loạt', `Chuyển ${selectedIds.value.length} câu hỏi vào thùng rác?`, execute)
  else execute()
}

const handleSingleRestore = async (id) => {
  try {
    const res = await adminQuestionsApi.restore(id)
    if (showToast) showToast(res.message || 'Khôi phục câu hỏi thành công!', 'success')
    loadTabItems(pagination.currentPage)
  } catch (err) {
    if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
  }
}

const handleBulkRestore = async () => {
  if (!selectedIds.value.length) return
  try {
    const res = await adminQuestionsApi.bulkRestore(selectedIds.value)
    if (showToast) showToast(res.message || 'Đã khôi phục các câu hỏi.', 'success')
    selectedIds.value = []
    loadTabItems(pagination.currentPage)
  } catch (err) {
    if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
  }
}

const handleSingleForceDelete = async (id) => {
  const execute = async () => {
    try {
      await adminQuestionsApi.forceDelete(id)
      if (showToast) showToast('Đã xóa vĩnh viễn câu hỏi.', 'success')
      loadTabItems(pagination.currentPage)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }
  if (showConfirm) showConfirm('Xóa vĩnh viễn', 'Câu hỏi sẽ bị xóa vĩnh viễn và không thể khôi phục lại. Bạn có chắc chắn không?', execute)
  else execute()
}

const handleBulkForceDelete = async () => {
  if (!selectedIds.value.length) return
  const execute = async () => {
    try {
      await adminQuestionsApi.bulkForceDelete(selectedIds.value)
      if (showToast) showToast('Đã xóa vĩnh viễn các câu hỏi đã chọn.', 'success')
      selectedIds.value = []
      loadTabItems(pagination.currentPage)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }
  if (showConfirm) showConfirm('Xóa vĩnh viễn hàng loạt', `Xóa vĩnh viễn ${selectedIds.value.length} câu hỏi? Thao tác này không thể hoàn tác.`, execute)
  else execute()
}

// -----------------------------------------------------------------------------
// FORMATTERS & HELPERS
// -----------------------------------------------------------------------------
const getDifficultyLabel = (diff) => {
  switch (diff) {
    case 'easy': return 'Dễ'
    case 'hard': return 'Khó'
    default: return 'Vừa'
  }
}

const getReviewStatusLabel = (status) => {
  switch (status) {
    case 'pending': return 'Chờ duyệt'
    case 'approved': return 'Đã duyệt'
    case 'rejected': return 'Từ chối'
    case 'superseded': return 'Đã thay thế'
    default: return status || 'Chưa duyệt'
  }
}

const getReviewStatusClass = (status) => {
  switch (status) {
    case 'pending': return 'bg-amber-100 text-amber-800 border border-amber-200'
    case 'approved': return 'bg-emerald-100 text-emerald-800 border border-emerald-200'
    case 'rejected': return 'bg-rose-100 text-rose-800 border border-rose-200'
    case 'superseded': return 'bg-slate-100 text-slate-600 border border-slate-200'
    default: return 'bg-slate-100 text-slate-700'
  }
}

const getGroupedReasons = (reps) => {
  if (!reps || reps.length === 0) return []
  const counts = {}
  reps.forEach(r => {
    const reason = r.reason || 'Khác'
    counts[reason] = (counts[reason] || 0) + 1
  })
  return Object.entries(counts).map(([reason, count]) => ({ reason, count }))
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

let searchDebounceTimer = null
watch(() => filters.search, (newVal, oldVal) => {
  if (newVal === oldVal) return
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    applyFilter()
  }, 400)
})

onMounted(() => {
  if (route.query.tab) {
    currentTab.value = route.query.tab
  }
  if (route.query.search) {
    filters.search = route.query.search
  }
  if (route.query.review_id) {
    openReviewDetail(Number(route.query.review_id))
  }
  fetchTaxonomies()
  loadTabItems(1)
})
</script>
