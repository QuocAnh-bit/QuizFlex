<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">Quản lý Quiz</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#7C3AED] text-white shadow-sm">
            <Package class="h-6 w-6" />
          </div>
          <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
              Quản lý Quiz
            </h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
              Quản lý toàn bộ đề thi, kiểm duyệt đề thi công khai (đối chiếu sai khác các phiên bản câu hỏi/đáp án) và theo dõi hiệu suất.
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
        <!-- 1. Tổng Quiz -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="currentTab === 'all' ? 'border-[#7C3AED] bg-purple-50/50 ring-2 ring-[#7C3AED]/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Nhấp để xem tất cả Quiz"
          @click="selectTab('all')"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-[#7C3AED]">
              <Package class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-500">Tổng Quiz</p>
              <p class="text-2xl font-black text-slate-900">{{ stats.total || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- 2. Công khai -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="currentTab === 'public' ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Nhấp để xem Quiz Công khai"
          @click="selectTab('public')"
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
          <p class="mt-2 text-xs text-emerald-700 font-medium">{{ publicPercent }}% hệ thống</p>
        </div>

        <!-- 3. Chờ duyệt -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="currentTab === 'pending' ? 'border-amber-500 bg-amber-50/60 ring-2 ring-amber-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Nhấp để kiểm duyệt các bài Quiz chờ duyệt"
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

        <!-- 4. Đang bị ẩn / Riêng tư -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="currentTab === 'hidden' ? 'border-slate-400 bg-slate-100 ring-2 ring-slate-400/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Nhấp để xem Quiz Đang bị ẩn"
          @click="selectTab('hidden')"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-200 text-slate-700">
              <Lock class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-500">Đang bị ẩn</p>
              <p class="text-2xl font-black text-slate-800">{{ stats.private || 0 }}</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-slate-500 font-medium">{{ privatePercent }}% hệ thống</p>
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
            placeholder="Tìm theo tên Quiz, tác giả, ID..."
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

        <!-- Difficulty Filter -->
        <div>
          <select
            v-model="filters.difficulty"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="applyFilter"
          >
            <option value="">Mọi độ khó</option>
            <option value="easy">Dễ</option>
            <option value="medium">Trung bình</option>
            <option value="hard">Khó</option>
          </select>
        </div>
      </div>

      <!-- Reset filter -->
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
          Đang chọn {{ selectedIds.length }} bài Quiz
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Actions for pending review requests -->
        <template v-if="currentTab === 'pending'">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-50 cursor-pointer"
            :disabled="isProcessingBulk"
            @click="handleBulkApprove"
          >
            <Check class="h-3.5 w-3.5" />
            <span>Phê duyệt hàng loạt</span>
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
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition cursor-pointer"
            @click="handleBulkRestore"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            <span>Khôi phục hàng loạt</span>
          </button>
        </template>

        <!-- Actions for general tabs -->
        <template v-else>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition cursor-pointer"
            @click="selectedIds = []"
          >
            Bỏ chọn
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
              <th class="p-3.5 min-w-[260px]">Bài Quiz</th>
              <th class="p-3.5 min-w-[140px]">Tác giả</th>
              <th class="p-3.5 text-center min-w-[90px]">Số câu</th>
              <th class="p-3.5 text-center min-w-[110px]">Trạng thái duyệt</th>
              <th class="p-3.5 text-center min-w-[95px]">Hiển thị</th>
              <th class="p-3.5 text-center min-w-[90px]">Lượt làm</th>
              <th class="p-3.5 text-center min-w-[90px]">Điểm TB</th>
              <th class="p-3.5 text-right min-w-[160px]">Hành động</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 font-medium">
            <!-- Loading -->
            <tr v-if="isLoading">
              <td colspan="9" class="p-12 text-center text-slate-400">
                <RefreshCw class="mx-auto mb-2 h-6 w-6 animate-spin text-[#7C3AED]" />
                <span>Đang tải danh sách bài Quiz...</span>
              </td>
            </tr>

            <!-- Empty -->
            <tr v-else-if="items.length === 0">
              <td colspan="9" class="p-12 text-center text-slate-400">
                <Inbox class="mx-auto mb-2 h-8 w-8 opacity-40 text-slate-400" />
                <p class="text-xs font-semibold text-slate-700">Không tìm thấy bài Quiz nào</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Thử thay đổi bộ lọc hoặc chọn tab khác.</p>
              </td>
            </tr>

            <!-- Rows -->
            <tr
              v-for="quiz in items"
              :key="quiz.id"
              class="hover:bg-slate-50/80 transition"
              :class="selectedIds.includes(quiz.id) ? 'bg-purple-50/30' : ''"
            >
              <!-- Checkbox -->
              <td class="p-3.5 text-center align-middle">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded accent-[#7C3AED] cursor-pointer"
                  :value="quiz.id"
                  :checked="selectedIds.includes(quiz.id)"
                  @change="toggleSelect(quiz.id)"
                />
              </td>

              <!-- Quiz Info -->
              <td class="p-3.5 align-top max-w-md">
                <div class="space-y-1">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-700">
                      #{{ quiz.quiz_id || quiz.id }}
                    </span>
                    <span v-if="quiz.subject_name || quiz.subject?.name" class="rounded bg-purple-50 px-1.5 py-0.5 text-[10px] font-bold text-purple-700">
                      {{ quiz.subject_name || quiz.subject?.name }}
                    </span>
                    <span v-if="quiz.grade_name || quiz.grade?.name" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">
                      {{ quiz.grade_name || quiz.grade?.name }}
                    </span>
                    <span
                      class="rounded px-1.5 py-0.5 text-[10px] font-bold"
                      :class="{
                        'bg-emerald-50 text-emerald-700': quiz.difficulty === 'easy',
                        'bg-amber-50 text-amber-700': quiz.difficulty === 'medium',
                        'bg-rose-50 text-rose-700': quiz.difficulty === 'hard'
                      }"
                    >
                      {{ getDifficultyLabel(quiz.difficulty) }}
                    </span>
                    <span v-if="quiz.revision_number && quiz.revision_number > 1" class="rounded bg-purple-100 text-[#7C3AED] px-1.5 py-0.5 text-[10px] font-bold">
                      Rev #{{ quiz.revision_number }}
                    </span>
                  </div>

                  <p class="text-xs font-bold text-slate-900 line-clamp-1">
                    {{ quiz.title || quiz.snapshot_title }}
                  </p>
                </div>
              </td>

              <!-- Author -->
              <td class="p-3.5 align-top">
                <div class="space-y-0.5">
                  <p class="font-bold text-slate-900 truncate">{{ quiz.user?.name || quiz.author || 'Vô danh' }}</p>
                  <p class="text-[10px] text-slate-400 truncate">{{ quiz.user?.email }}</p>
                </div>
              </td>

              <!-- Questions Count -->
              <td class="p-3.5 align-top text-center font-bold text-slate-700">
                {{ quiz.questions_count ?? quiz.snapshot_questions?.length ?? 0 }} câu
              </td>

              <!-- Review Status (Separate Column) -->
              <td class="p-3.5 align-top text-center">
                <span
                  class="inline-block rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider"
                  :class="getReviewStatusClass(quiz.status || quiz.review_status)"
                >
                  {{ getReviewStatusLabel(quiz.status || quiz.review_status) }}
                </span>
                <div v-if="(quiz.rejection_reason || quiz.note) && (quiz.status === 'rejected' || quiz.review_status === 'rejected')" class="mt-1 text-[10px] text-rose-600 truncate max-w-[120px]" :title="quiz.rejection_reason || quiz.note">
                  "{{ quiz.rejection_reason || quiz.note }}"
                </div>
              </td>

              <!-- Visibility (Separate Column) -->
              <td class="p-3.5 align-top text-center">
                <span
                  class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider"
                  :class="quiz.is_public
                    ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                    : 'bg-slate-100 text-slate-600'"
                >
                  <component :is="quiz.is_public ? Globe : Lock" class="h-3 w-3" />
                  <span>{{ quiz.is_public ? 'Công khai' : 'Đã ẩn' }}</span>
                </span>
              </td>

              <!-- Attempts -->
              <td class="p-3.5 align-top text-center font-bold text-slate-800">
                {{ quiz.attempts_count ?? 0 }}
              </td>

              <!-- Avg Score -->
              <td class="p-3.5 align-top text-center font-bold text-[#7C3AED]">
                {{ quiz.avg_score ?? 0 }}%
              </td>

              <!-- Actions -->
              <td class="p-3.5 align-top text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- 1. PENDING ACTIONS: Xem chi tiết + Đối chiếu (KHÔNG có Quick Approve/Reject ngoài bảng) -->
                  <template v-if="quiz.status === 'pending' || quiz.review_status === 'pending_review'">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-purple-50 px-2.5 py-1.5 text-[11px] font-bold text-[#7C3AED] hover:bg-purple-100 transition cursor-pointer shadow-xs"
                      title="Đối chiếu sai khác các phiên bản"
                      @click="openReviewDetail(quiz.review_request_id || quiz.id)"
                    >
                      <GitCompare class="h-3.5 w-3.5" />
                      <span>{{ quiz.revision_number > 1 ? 'So sánh' : 'Đối chiếu' }}</span>
                    </button>

                    <router-link
                      :to="`/admin/quizzes/${quiz.quiz_id || quiz.id}`"
                      class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-50 transition cursor-pointer shadow-xs"
                      title="Xem trang chi tiết"
                    >
                      <Eye class="h-3.5 w-3.5" />
                    </router-link>
                  </template>

                  <!-- 2. APPROVED / PUBLIC / HIDDEN ACTIONS: Xem chi tiết + Toggle Visibility -->
                  <template v-else-if="quiz.review_status === 'approved' || (!quiz.review_status && quiz.is_public)">
                    <router-link
                      :to="`/admin/quizzes/${quiz.quiz_id || quiz.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-xs"
                      title="Xem chi tiết Quiz"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span>Xem chi tiết</span>
                    </router-link>

                    <!-- Toggle Visibility -->
                    <button
                      type="button"
                      class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                      :title="quiz.is_public ? 'Chuyển sang Ẩn' : 'Duyệt Công khai'"
                      @click="toggleSingleVisibility(quiz.quiz_id || quiz.id)"
                    >
                      <component :is="quiz.is_public ? Lock : Globe" class="h-3.5 w-3.5" />
                    </button>
                  </template>

                  <!-- 3. REJECTED ACTIONS: Xem chi tiết & Lịch sử đối chiếu (KHÔNG có Duyệt/Từ chối) -->
                  <template v-else-if="quiz.status === 'rejected' || quiz.review_status === 'rejected'">
                    <router-link
                      :to="`/admin/quizzes/${quiz.quiz_id || quiz.id}`"
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
                      @click="openReviewDetail(quiz.review_request_id || quiz.id)"
                    >
                      <History class="h-3.5 w-3.5" />
                      <span>Lịch sử</span>
                    </button>
                  </template>

                  <!-- 4. DEFAULT ACTIONS -->
                  <template v-else>
                    <router-link
                      :to="`/admin/quizzes/${quiz.quiz_id || quiz.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-xs"
                      title="Xem chi tiết Quiz"
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

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="flex flex-wrap items-center justify-between border-t border-slate-100 bg-slate-50/50 p-4 text-xs font-medium text-slate-600">
        <span>
          Hiển thị trang {{ pagination.currentPage }} / {{ pagination.lastPage }} (Tổng {{ pagination.total }} bài Quiz)
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
         QUIZ REVIEW & DIFF WORKSPACE MODAL (CRITICAL: 100% REVISION DIFF PRESERVED)
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
                  Thẩm định Quiz: {{ activeReviewDetail?.review_request?.snapshot_title || activeReviewDetail?.quiz?.title }}
                </h2>
                <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-[11px] font-bold text-purple-800">
                  Rev #{{ activeReviewDetail?.review_request?.revision_number || 1 }}
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Người gửi: <strong class="text-slate-800">{{ activeReviewDetail?.review_request?.user?.name || 'Người dùng' }}</strong>
                ({{ activeReviewDetail?.review_request?.user?.email }})
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

        <!-- Modal Nav Tabs -->
        <div class="flex items-center gap-3 border-b border-slate-100 px-6 bg-white text-xs font-bold">
          <button
            type="button"
            class="py-3 border-b-2 transition cursor-pointer"
            :class="modalTab === 'diff' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
            @click="modalTab = 'diff'"
          >
            Đối chiếu thay đổi (Revision Diff)
          </button>
          <button
            type="button"
            class="py-3 border-b-2 transition cursor-pointer"
            :class="modalTab === 'snapshot' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
            @click="modalTab = 'snapshot'"
          >
            Toàn bộ đề thi (Snapshot)
          </button>
        </div>

        <!-- Modal Body -->
        <div v-if="isLoadingReviewDetail" class="p-12 text-center text-sm font-medium text-slate-400">
          <RefreshCw class="mx-auto mb-3 h-6 w-6 animate-spin text-[#7C3AED]" />
          <span>Đang tính toán sai khác giữa các phiên bản...</span>
        </div>

        <div v-else class="flex-1 overflow-y-auto p-6 space-y-6 bg-[#F8FAFC]">
          <!-- TAB 1: DIFF COMPARISON -->
          <div v-if="modalTab === 'diff'" class="space-y-5">
            <!-- Diff Summary Badge Bar -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 text-xs font-bold text-center">
              <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">
                <span class="text-[10px] text-emerald-600 block uppercase">Thêm mới</span>
                <span class="text-lg font-black">+{{ activeReviewDetail?.diff?.added_count || 0 }} câu</span>
              </div>
              <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-800">
                <span class="text-[10px] text-amber-600 block uppercase">Chỉnh sửa</span>
                <span class="text-lg font-black">~{{ activeReviewDetail?.diff?.modified_count || 0 }} câu</span>
              </div>
              <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-rose-800">
                <span class="text-[10px] text-rose-600 block uppercase">Đã xóa</span>
                <span class="text-lg font-black">-{{ activeReviewDetail?.diff?.removed_count || 0 }} câu</span>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-3 text-slate-700">
                <span class="text-[10px] text-slate-400 block uppercase">Giữ nguyên</span>
                <span class="text-lg font-black">{{ activeReviewDetail?.diff?.unchanged_count || 0 }} câu</span>
              </div>
            </div>

            <!-- Metadata Diff Section -->
            <div v-if="activeReviewDetail?.diff?.metadata_changed" class="rounded-2xl border border-purple-200 bg-purple-50/40 p-4 space-y-3">
              <h4 class="text-xs font-black uppercase tracking-wider text-purple-900 flex items-center gap-2">
                <AlertCircle class="h-4 w-4 text-[#7C3AED]" />
                <span>Thay đổi thông tin chung của bài Quiz:</span>
              </h4>

              <div class="grid gap-2 text-xs">
                <div
                  v-for="(val, key) in activeReviewDetail.diff.metadata_diff"
                  :key="key"
                  class="rounded-xl border border-purple-200 bg-white p-3 flex flex-wrap items-center justify-between gap-2"
                >
                  <span class="font-bold text-slate-700">{{ getMetadataLabel(key) }}:</span>
                  <div class="flex items-center gap-2">
                    <span class="text-rose-700 line-through">{{ val.old || 'Trống' }}</span>
                    <span>→</span>
                    <span class="font-bold text-emerald-800">{{ val.new || 'Trống' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Questions Diff Detailed List -->
            <div class="space-y-3">
              <h4 class="text-xs font-black uppercase tracking-wider text-slate-700">
                Chi tiết câu hỏi thay đổi ({{ (activeReviewDetail?.diff?.question_diffs || []).length }} câu):
              </h4>

              <div v-if="(activeReviewDetail?.diff?.question_diffs || []).length === 0" class="rounded-2xl border border-slate-200 bg-white p-6 text-center text-xs text-slate-500">
                Không có câu hỏi nào thay đổi nội dung trong phiên bản này.
              </div>

              <div
                v-for="(qDiff, idx) in (activeReviewDetail?.diff?.question_diffs || [])"
                :key="idx"
                class="rounded-2xl border bg-white p-4 space-y-3 shadow-2xs"
                :class="{
                  'border-emerald-300': qDiff.status === 'added',
                  'border-amber-300': qDiff.status === 'modified',
                  'border-rose-300': qDiff.status === 'removed'
                }"
              >
                <!-- Diff Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                  <span class="font-bold text-xs text-slate-900">Câu hỏi #{{ qDiff.order || (idx + 1) }}</span>
                  <span
                    class="rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider"
                    :class="{
                      'bg-emerald-100 text-emerald-800': qDiff.status === 'added',
                      'bg-amber-100 text-amber-800': qDiff.status === 'modified',
                      'bg-rose-100 text-rose-800': qDiff.status === 'removed'
                    }"
                  >
                    {{ qDiff.status === 'added' ? '+ Thêm mới' : qDiff.status === 'modified' ? '~ Sửa đổi' : '- Đã xóa' }}
                  </span>
                </div>

                <!-- Added Question -->
                <div v-if="qDiff.status === 'added'" class="space-y-2">
                  <p class="text-xs font-bold text-emerald-900">{{ qDiff.new_question?.content }}</p>
                  <div class="grid gap-1.5 sm:grid-cols-2 text-xs">
                    <div
                      v-for="ans in qDiff.new_question?.answers"
                      :key="ans.id || ans.key"
                      class="rounded-lg border p-2"
                      :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-slate-50 text-slate-700'"
                    >
                      <span>{{ ans.key }}. {{ ans.content || ans.text }}</span>
                      <span v-if="ans.is_correct" class="ml-2 text-[10px] font-bold text-emerald-600">✓ Đúng</span>
                    </div>
                  </div>
                </div>

                <!-- Removed Question -->
                <div v-else-if="qDiff.status === 'removed'" class="space-y-2">
                  <p class="text-xs font-medium text-rose-800 line-through">{{ qDiff.old_question?.content }}</p>
                  <div class="text-[11px] text-rose-600 italic">Câu hỏi này đã bị gỡ khỏi bài Quiz.</div>
                </div>

                <!-- Modified Question (Side-by-Side) -->
                <div v-else-if="qDiff.status === 'modified'" class="space-y-2">
                  <div v-if="qDiff.field_changes?.content" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3">
                      <span class="text-[10px] font-bold text-rose-600 uppercase block mb-1">Nội dung cũ:</span>
                      <p class="line-through text-rose-900">{{ qDiff.field_changes.content.old }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3">
                      <span class="text-[10px] font-bold text-emerald-600 uppercase block mb-1">Nội dung mới:</span>
                      <p class="font-bold text-emerald-950">{{ qDiff.field_changes.content.new }}</p>
                    </div>
                  </div>

                  <!-- Answer Changes -->
                  <div v-if="qDiff.answer_changes && qDiff.answer_changes.length > 0" class="space-y-1.5 pt-2">
                    <span class="text-[11px] font-bold text-slate-700">Các đáp án thay đổi:</span>
                    <div
                      v-for="(aDiff, aIdx) in qDiff.answer_changes"
                      :key="aIdx"
                      class="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-xl border border-slate-200 p-2 text-xs"
                    >
                      <div class="text-rose-800">
                        <span class="line-through">{{ aDiff.key }}. {{ aDiff.old_content }}</span>
                        <span v-if="aDiff.old_is_correct" class="text-[10px] text-emerald-600 font-bold ml-1">✓</span>
                      </div>
                      <div class="text-emerald-900 font-bold">
                        <span>{{ aDiff.key }}. {{ aDiff.new_content }}</span>
                        <span v-if="aDiff.new_is_correct" class="text-[10px] text-emerald-600 font-bold ml-1">✓ Đúng</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: FULL SNAPSHOT -->
          <div v-else-if="modalTab === 'snapshot'" class="space-y-4">
            <div
              v-for="(q, qIdx) in (activeReviewDetail?.current_revision?.snapshot_questions || [])"
              :key="qIdx"
              class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3"
            >
              <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <span class="font-bold text-xs text-slate-900">Câu {{ qIdx + 1 }}:</span>
                <span class="text-[11px] text-slate-500 font-medium">{{ q.difficulty || 'medium' }} • {{ q.points || 10 }} điểm</span>
              </div>

              <p class="text-xs font-bold text-slate-900">{{ q.content }}</p>

              <div class="grid gap-2 sm:grid-cols-2 text-xs">
                <div
                  v-for="ans in q.answers"
                  :key="ans.key"
                  class="rounded-xl border p-2.5 flex items-center justify-between"
                  :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-slate-50 text-slate-700'"
                >
                  <span>{{ ans.key }}. {{ ans.content }}</span>
                  <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600">✓ Đáp án đúng</span>
                </div>
              </div>
            </div>
          </div>

          <!-- TIMELINE OF REVISIONS -->
          <div v-if="activeReviewDetail?.history && activeReviewDetail.history.length > 1" class="border-t border-slate-200 pt-4 space-y-2.5">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
              <History class="h-4 w-4 text-[#7C3AED]" />
              <span>Lịch sử các lần gửi duyệt:</span>
            </h4>

            <div class="space-y-1.5">
              <div
                v-for="h in activeReviewDetail.history"
                :key="h.id"
                class="rounded-xl border border-slate-200 bg-white p-3 text-xs flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <span class="font-black text-slate-900">Rev #{{ h.revision_number }}</span>
                  <span
                    class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                    :class="getReviewStatusClass(h.status)"
                  >
                    {{ getReviewStatusLabel(h.status) }}
                  </span>
                  <span class="text-slate-400 text-[11px]">{{ formatDate(h.created_at) }}</span>
                </div>
                <div v-if="h.rejection_reason" class="text-rose-700 font-medium truncate max-w-sm">
                  Lý do: {{ h.rejection_reason }}
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
            <template v-if="activeReviewDetail?.review_request?.status === 'pending'">
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
                <span>Phê duyệt công khai</span>
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
            <span>Từ chối phê duyệt Quiz</span>
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
          Vui lòng nhập lý do từ chối cụ thể để người tạo biết và chỉnh sửa lại bài Quiz.
        </p>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Lý do từ chối <span class="text-rose-500">*</span></label>
          <textarea
            v-model="rejectionReason"
            rows="4"
            class="w-full rounded-xl border border-slate-200 p-3 text-xs outline-none focus:border-rose-500 resize-none font-medium text-slate-900"
            placeholder="Ví dụ: Thiếu đáp án đúng ở câu 3, nội dung câu hỏi chưa đạt chuẩn..."
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
            :disabled="!rejectionReason.trim() || isSubmittingReject"
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
  ChevronRight,
  Clock,
  Eye,
  GitCompare,
  Globe,
  History,
  Inbox,
  Lock,
  Package,
  RefreshCw,
  RotateCcw,
  Search,
  Trash2,
  X,
} from 'lucide-vue-next'
import { adminQuizzesApi, quizReviewApi, taxonomyApi } from '@/services/api'

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
})

// Review Diff Modal
const isReviewModalOpen = ref(false)
const selectedReviewId = ref(null)
const activeReviewDetail = ref(null)
const isLoadingReviewDetail = ref(false)
const modalTab = ref('diff')

// Reject Modal
const isRejectModalOpen = ref(false)
const rejectTargetQuiz = ref(null)
const rejectionReason = ref('')
const isSubmittingReject = ref(false)
const isBulkReject = ref(false)

const tabs = computed(() => [
  { key: 'all', label: 'Tất cả' },
  { key: 'pending', label: 'Chờ duyệt', badge: stats.pending, badgeClass: 'bg-amber-100 text-amber-800' },
  { key: 'public', label: 'Công khai', badge: stats.public, badgeClass: 'bg-emerald-100 text-emerald-800' },
  { key: 'hidden', label: 'Đang bị ẩn', badge: stats.private, badgeClass: 'bg-slate-100 text-slate-700' },
  { key: 'rejected', label: 'Từ chối', badge: stats.rejected, badgeClass: 'bg-rose-100 text-rose-800' },
])

const publicPercent = computed(() => {
  if (!stats.total) return 0
  return ((stats.public / stats.total) * 100).toFixed(1)
})

const privatePercent = computed(() => {
  if (!stats.total) return 0
  return ((stats.private / stats.total) * 100).toFixed(1)
})

const hasActiveFilters = computed(() => {
  return Boolean(filters.search || filters.subject_id || filters.grade_id || filters.difficulty)
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
    if (currentTab.value === 'pending' || currentTab.value === 'rejected') {
      // 1. Load Review Requests
      const params = {
        page,
        per_page: pagination.perPage,
        status: currentTab.value,
        search: filters.search ? filters.search.trim() : undefined,
        subject_id: filters.subject_id || undefined,
      }

      const res = await quizReviewApi.fetchAdminReviewRequests(params)
      items.value = (res.items || []).map(r => ({
        ...r,
        review_request_id: r.id,
        title: r.snapshot_title || r.quiz?.title,
        subject_name: r.quiz?.subject?.name || r.quiz?.subject_name,
        grade_name: r.quiz?.grade?.name,
        difficulty: r.quiz?.difficulty || 'medium',
        is_public: r.quiz?.is_public,
      }))
      pagination.total = res.total || 0
      pagination.lastPage = res.last_page || 1

      if (res.stats) {
        stats.pending = res.stats.pending || 0
        stats.rejected = res.stats.rejected || 0
      }
    } else {
      // 3. Load All / Public / Hidden Quizzes
      const params = {
        page,
        per_page: pagination.perPage,
        search: filters.search ? filters.search.trim() : undefined,
        subject_id: filters.subject_id || undefined,
        grade_id: filters.grade_id || undefined,
        difficulty: filters.difficulty || undefined,
        visibility: currentTab.value === 'public' ? 'public' : currentTab.value === 'hidden' ? 'private' : undefined,
      }

      const res = await adminQuizzesApi.list(params)
      items.value = res.items || []
      pagination.total = res.total || 0
      pagination.lastPage = res.lastPage || 1

      if (res.stats) {
        stats.total = res.stats.total || 0
        stats.public = res.stats.public || 0
        stats.private = res.stats.private || 0
      }
    }

    fetchGlobalStats()
  } catch (err) {
    console.error('Lỗi tải danh sách Quiz:', err)
    errorMessage.value = `Không thể tải danh sách Quiz: ${err.message || 'Lỗi mạng'}`
  } finally {
    isLoading.value = false
  }
}

const fetchGlobalStats = async () => {
  try {
    const [quizStats, reqStats] = await Promise.allSettled([
      adminQuizzesApi.list({ per_page: 1 }),
      quizReviewApi.fetchAdminReviewRequests({ per_page: 1, status: 'all' }),
    ])

    if (quizStats.status === 'fulfilled' && quizStats.value.stats) {
      stats.total = quizStats.value.stats.total || 0
      stats.public = quizStats.value.stats.public || 0
      stats.private = quizStats.value.stats.private || 0
    }

    if (reqStats.status === 'fulfilled' && reqStats.value.stats) {
      stats.pending = reqStats.value.stats.pending || 0
      stats.rejected = reqStats.value.stats.rejected || 0
    }
  } catch (e) {
    console.error('Không thể cập nhật chỉ số thống kê Quiz:', e)
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
    console.error('Lỗi tải danh mục:', e)
  }
}

// -----------------------------------------------------------------------------
// REVIEW / DIFF MODAL ACTIONS
// -----------------------------------------------------------------------------
const openReviewDetail = async (id) => {
  selectedReviewId.value = id
  isReviewModalOpen.value = true
  isLoadingReviewDetail.value = true
  modalTab.value = 'diff'
  activeReviewDetail.value = null

  try {
    const res = await quizReviewApi.getAdminReviewRequest(id)
    activeReviewDetail.value = res?.data || res
  } catch (err) {
    console.error('Không thể lấy thông tin thẩm định Quiz:', err)
    if (showToast) showToast('Lỗi khi tải chi tiết thẩm định Quiz.', 'error')
  } finally {
    isLoadingReviewDetail.value = false
  }
}

const closeReviewModal = () => {
  isReviewModalOpen.value = false
  activeReviewDetail.value = null
  selectedReviewId.value = null
}

const handleApproveFromDetail = async () => {
  if (!activeReviewDetail.value?.review_request?.id) return
  await executeApprove(activeReviewDetail.value.review_request.id)
  closeReviewModal()
}

const openRejectModalFromDetail = () => {
  rejectTargetQuiz.value = activeReviewDetail.value?.review_request
  rejectionReason.value = ''
  isBulkReject.value = false
  isRejectModalOpen.value = true
}

const handleSingleApprove = async (quiz) => {
  const reqId = quiz.review_request_id || quiz.id
  const message = `Bạn có chắc muốn phê duyệt bài Quiz "${quiz.title || quiz.snapshot_title}" thành Công khai?`

  const execute = async () => {
    await executeApprove(reqId)
  }

  if (showConfirm) showConfirm('Xác nhận duyệt Quiz', message, execute)
  else execute()
}

const executeApprove = async (reqId) => {
  try {
    const res = await quizReviewApi.adminApprove(reqId)
    if (showToast) showToast(res.message || 'Phê duyệt bài Quiz thành công!', 'success')
    loadTabItems(pagination.currentPage)
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Phê duyệt thất bại.'
    if (showToast) showToast(msg, 'error')
  }
}

const openSingleRejectModal = (quiz) => {
  rejectTargetQuiz.value = quiz
  rejectionReason.value = ''
  isBulkReject.value = false
  isRejectModalOpen.value = true
}

const openBulkRejectModal = () => {
  rejectionReason.value = ''
  isBulkReject.value = true
  isRejectModalOpen.value = true
}

const executeReject = async () => {
  if (!rejectionReason.value.trim()) return
  isSubmittingReject.value = true

  try {
    if (isBulkReject.value) {
      const res = await quizReviewApi.adminBulkReject(selectedIds.value, rejectionReason.value.trim())
      if (showToast) showToast(res.message || 'Đã từ chối các bài Quiz đã chọn.', 'success')
      selectedIds.value = []
    } else {
      const reqId = rejectTargetQuiz.value?.review_request_id || rejectTargetQuiz.value?.id
      const res = await quizReviewApi.adminReject(reqId, rejectionReason.value.trim())
      if (showToast) showToast(res.message || 'Đã từ chối duyệt bài Quiz.', 'success')
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
  const message = `Bạn có chắc muốn phê duyệt ${selectedIds.value.length} bài Quiz đã chọn?`

  const execute = async () => {
    isProcessingBulk.value = true
    try {
      const res = await quizReviewApi.adminBulkApprove(selectedIds.value)
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
    const res = await adminQuizzesApi.toggleVisibility(id)
    if (showToast) showToast(res.message || 'Cập nhật trạng thái hiển thị thành công.', 'success')
    loadTabItems(pagination.currentPage)
  } catch (err) {
    if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
  }
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
    case 'pending':
    case 'pending_review': return 'Chờ duyệt'
    case 'approved': return 'Đã duyệt'
    case 'rejected': return 'Từ chối'
    default: return status || 'Nháp'
  }
}

const getReviewStatusClass = (status) => {
  switch (status) {
    case 'pending':
    case 'pending_review': return 'bg-amber-100 text-amber-800 border border-amber-200'
    case 'approved': return 'bg-emerald-100 text-emerald-800 border border-emerald-200'
    case 'rejected': return 'bg-rose-100 text-rose-800 border border-rose-200'
    default: return 'bg-slate-100 text-slate-700'
  }
}

const getMetadataLabel = (key) => {
  switch (key) {
    case 'title': return 'Tiêu đề'
    case 'description': return 'Mô tả'
    case 'subject_id': return 'Bộ môn'
    case 'grade_id': return 'Khối lớp'
    case 'duration_minutes': return 'Thời gian làm bài'
    case 'shuffle_questions': return 'Xáo trộn câu hỏi'
    case 'shuffle_answers': return 'Xáo trộn đáp án'
    default: return key
  }
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
