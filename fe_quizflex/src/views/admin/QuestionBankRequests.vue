<template>
  <section class="space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <div class="inline-flex items-center gap-2 rounded-md border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700">
            <CheckCircle class="h-3.5 w-3.5" />
            <span>Bank Moderation</span>
            <span class="text-purple-400">•</span>
            <span>Duyệt câu hỏi vào Ngân hàng</span>
          </div>
          <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Yêu cầu duyệt vào Ngân hàng câu hỏi
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500">
            Xem xét, thẩm định nội dung, so sánh lịch sử chỉnh sửa và phê duyệt các câu hỏi do người dùng đóng góp vào Ngân hàng câu hỏi dùng chung.
          </p>
        </div>

        <div class="flex items-center gap-2.5 flex-nowrap shrink-0 overflow-x-auto pb-1 sm:pb-0">
          <div
            class="min-w-[85px] sm:min-w-[95px] rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2 text-center cursor-pointer hover:bg-amber-100/70 transition shadow-2xs shrink-0"
            title="Bấm để lọc câu hỏi Chờ duyệt"
            @click="filters.status = 'pending'; filters.priority = ''; loadRequests(true)"
          >
            <span class="text-[10px] font-medium uppercase text-slate-500 block">Chờ duyệt</span>
            <p class="text-xl font-bold text-amber-600 leading-tight mt-0.5">{{ stats.pending }}</p>
          </div>

          <div
            class="min-w-[85px] sm:min-w-[95px] rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-center cursor-pointer hover:bg-emerald-100/70 transition shadow-2xs shrink-0"
            title="Bấm để lọc câu hỏi Đã duyệt"
            @click="filters.status = 'approved'; filters.priority = ''; loadRequests(true)"
          >
            <span class="text-[10px] font-medium uppercase text-slate-500 block">Đã duyệt</span>
            <p class="text-xl font-bold text-emerald-600 leading-tight mt-0.5">{{ stats.approved }}</p>
          </div>

          <div
            class="min-w-[85px] sm:min-w-[95px] rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-center cursor-pointer hover:bg-rose-100/70 transition shadow-2xs shrink-0"
            title="Bấm để lọc câu hỏi Bị từ chối"
            @click="filters.status = 'rejected'; filters.priority = ''; loadRequests(true)"
          >
            <span class="text-[10px] font-medium uppercase text-slate-500 block">Bị từ chối</span>
            <p class="text-xl font-bold text-rose-600 leading-tight mt-0.5">{{ stats.rejected }}</p>
          </div>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300 shadow-2xs cursor-pointer shrink-0 whitespace-nowrap"
            title="Làm mới dữ liệu"
            @click="loadRequests(false)"
          >
            <RefreshCw class="h-4 w-4 text-slate-500" :class="{ 'animate-spin': isLoading }" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>
    </div>


    <!-- Filters Bar -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <!-- Search -->
        <div class="relative sm:col-span-3">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo nội dung, tác giả..."
            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:bg-white"
            @keyup.enter="loadRequests(true)"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="filters.status"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="pending">Chờ duyệt (Pending)</option>
            <option value="approved">Đã duyệt (Approved)</option>
            <option value="rejected">Đã từ chối (Rejected)</option>
            <option value="all">Tất cả trạng thái</option>
          </select>
        </div>

        <!-- Subject Filter -->
        <div>
          <select
            v-model="filters.subject_id"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="">Tất cả môn học</option>
            <option v-for="sub in allSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
          </select>
        </div>

        <!-- Difficulty Filter -->
        <div>
          <select
            v-model="filters.difficulty"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="">Tất cả độ khó</option>
            <option value="easy">Dễ (Nhận biết)</option>
            <option value="medium">Vừa (Thông hiểu)</option>
            <option value="hard">Khó (Vận dụng)</option>
          </select>
        </div>
      </div>


      <!-- Active Filters Reset -->
      <div v-if="hasActiveFilters" class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
        <span class="text-slate-500">Đang áp dụng bộ lọc</span>
        <button
          type="button"
          class="font-bold text-[#7C3AED] hover:underline cursor-pointer"
          @click="resetFilters"
        >
          Đặt lại bộ lọc
        </button>
      </div>
    </div>

    <!-- Bulk Action Toolbar -->
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

      <div class="flex items-center gap-2">
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
      </div>
    </div>

    <!-- Error state -->
    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold text-rose-700">
      {{ errorMessage }}
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-sm font-medium text-slate-400">
      <RefreshCw class="mx-auto mb-3 h-6 w-6 animate-spin text-[#7C3AED]" />
      <span>Đang tải danh sách yêu cầu duyệt...</span>
    </div>

    <!-- Empty State -->
    <div v-else-if="requests.length === 0" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
      <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
        <Inbox class="h-6 w-6" />
      </div>
      <h3 class="text-base font-bold text-slate-900">Không có yêu cầu duyệt nào</h3>
      <p class="mt-1 text-xs text-slate-500">
        {{ filters.status === 'pending' ? 'Hiện tại không có câu hỏi nào đang chờ duyệt vào Ngân hàng.' : 'Không tìm thấy câu hỏi phù hợp với bộ lọc hiện tại.' }}
      </p>
    </div>

    <!-- Questions List -->
    <div v-else class="space-y-4">
      <article
        v-for="item in requests"
        :key="item.id"
        class="rounded-2xl border bg-white p-5 shadow-sm transition hover:border-slate-300"
        :class="selectedIds.includes(item.id) ? 'border-[#7C3AED] bg-purple-50/20' : 'border-slate-200'"
      >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex items-start gap-3 flex-1 min-w-0">
            <!-- Select Checkbox -->
            <input
              v-if="item.bank_submission_status === 'pending'"
              type="checkbox"
              :checked="selectedIds.includes(item.id)"
              class="h-4 w-4 rounded accent-[#7C3AED] mt-1 shrink-0 cursor-pointer"
              @change="toggleSelect(item.id)"
            />

            <div class="flex-1 min-w-0 space-y-2.5">
              <!-- Meta Badges -->
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-700">
                  #{{ item.id }}
                </span>



                <!-- Status Badge -->
                <span
                  class="rounded-md px-2 py-0.5 text-[11px] font-bold"
                  :class="getStatusBadgeClass(item.bank_submission_status)"
                >
                  {{ getStatusLabel(item.bank_submission_status) }}
                </span>

                <!-- Revision Badge -->
                <span
                  v-if="item.revision_number && item.revision_number > 1"
                  class="inline-flex items-center gap-1 rounded-md bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 text-[11px] font-bold"
                >
                  <RotateCcw class="h-3 w-3" />
                  <span>Phiên bản {{ item.revision_number }}</span>
                </span>
                <span
                  v-else
                  class="rounded-md bg-slate-100 text-slate-600 px-2 py-0.5 text-[11px] font-bold"
                >
                  Phiên bản 1
                </span>

                <!-- Difficulty Badge -->
                <span
                  class="rounded-md px-2 py-0.5 text-[11px] font-bold"
                  :class="{
                    'bg-emerald-50 text-emerald-700': item.difficulty === 'easy',
                    'bg-amber-50 text-amber-700': item.difficulty === 'medium',
                    'bg-rose-50 text-rose-700': item.difficulty === 'hard'
                  }"
                >
                  {{ getDifficultyLabel(item.difficulty) }}
                </span>

                <!-- Subject / Grade -->
                <span v-if="item.subject_name" class="rounded-md bg-purple-50 px-2 py-0.5 text-[11px] font-bold text-purple-700">
                  {{ item.subject_name }}
                </span>
                <span v-if="item.grade_name" class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
                  {{ item.grade_name }}
                </span>
                <span v-if="item.topic_name" class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                  Chủ đề: {{ item.topic_name }}
                </span>

                <!-- Author info -->
                <span class="ml-auto text-xs text-slate-500 font-medium">
                  Tác giả: <strong class="text-slate-800">{{ item.author_name || 'Vô danh' }}</strong>
                  <span v-if="item.author_email" class="text-slate-400 ml-1">({{ item.author_email }})</span>
                </span>
              </div>



              <!-- Rejection Note if Rejected -->
              <div
                v-if="item.bank_submission_status === 'rejected' && item.rejection_reason"
                class="rounded-xl border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-800 font-medium"
              >
                <strong>Lý do từ chối:</strong> {{ item.rejection_reason }}
                <span v-if="item.reviewer_name" class="text-rose-600 ml-1 font-normal">• Người duyệt: {{ item.reviewer_name }}</span>
              </div>


              <!-- Question Content -->
              <h3 class="text-sm font-bold text-slate-900 leading-snug">
                {{ item.content || item.text }}
              </h3>

              <!-- Answers Grid with full Answer Key visibility (Admin always sees correct answer) -->
              <div v-if="item.answers && item.answers.length > 0" class="grid gap-2 sm:grid-cols-2 pt-1">
                <div
                  v-for="ans in item.answers"
                  :key="ans.id"
                  class="flex items-center gap-2.5 rounded-xl border px-3 py-2 text-xs font-semibold"
                  :class="ans.is_correct
                    ? 'border-emerald-300 bg-emerald-50 text-emerald-800 shadow-sm'
                    : 'border-slate-200 bg-slate-50 text-slate-600'"
                >
                  <span
                    class="grid h-5 w-5 shrink-0 place-items-center rounded-md text-[10px] font-bold"
                    :class="ans.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ ans.key }}
                  </span>

                  <span class="truncate flex-1">{{ ans.text || ans.content }}</span>

                  <span v-if="ans.is_correct" class="ml-auto inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-emerald-600">
                    <Check class="h-3 w-3" :stroke-width="3" />
                    <span>Đáp án đúng</span>
                  </span>
                </div>
              </div>

              <!-- Submission Timestamp -->
              <div class="text-[11px] text-slate-400 font-medium pt-1">
                Gửi duyệt lúc: {{ formatDate(item.bank_submission_at || item.created_at) }}
              </div>
            </div>
          </div>

          <!-- Single Action Buttons -->
          <div class="flex items-center gap-2 shrink-0 self-end lg:self-start pt-2 lg:pt-0">
            <!-- Nút So sánh Diff & Chi tiết -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-xs font-bold text-purple-700 hover:bg-purple-100 transition cursor-pointer shadow-sm"
              title="So sánh dữ liệu phiên bản này với các phiên bản trước"
              @click="openDetailModal(item.id)"
            >
              <GitCompare class="h-3.5 w-3.5" />
              <span>{{ item.revision_number > 1 ? 'So sánh Cũ / Mới' : 'Chi tiết' }}</span>
            </button>

            <button
              v-if="item.bank_submission_status === 'pending'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition cursor-pointer"
              title="Phê duyệt câu hỏi này vào Ngân hàng"
              @click="approveSingle(item.id)"
            >
              <Check class="h-3.5 w-3.5" />
              <span>Duyệt</span>
            </button>

            <button
              v-if="item.bank_submission_status === 'pending'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition cursor-pointer"
              title="Từ chối câu hỏi này"
              @click="openRejectModal(item.id)"
            >
              <X class="h-3.5 w-3.5" />
              <span>Từ chối</span>
            </button>
          </div>
        </div>
      </article>

      <!-- Pagination -->
      <AppPagination
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        :per-page="pagination.per_page"
        item-label="câu hỏi"
        @change="changePage"
      />
    </div>

    <!-- MODAL SO SÁNH DỮ LIỆU CŨ / MỚI (DIFF COMPARISON MODAL) -->
    <div
      v-if="isDetailModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 sm:p-6 overflow-y-auto"
      @click.self="closeDetailModal"
    >
      <div class="relative w-full max-w-5xl rounded-3xl bg-white shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="grid h-9 w-9 place-items-center rounded-xl bg-purple-100 text-[#7C3AED]">
              <GitCompare class="h-5 w-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-base font-black text-slate-900">
                  Thẩm định câu hỏi #{{ detailData?.question?.id }}
                </h2>
                <span
                  v-if="detailData?.current_revision?.revision_number > 1"
                  class="rounded-full bg-purple-100 px-2.5 py-0.5 text-[11px] font-bold text-purple-800"
                >
                  Phiên bản {{ detailData?.current_revision?.revision_number }}
                </span>
                <span
                  v-else
                  class="rounded-full bg-slate-200 px-2.5 py-0.5 text-[11px] font-bold text-slate-700"
                >
                  Phiên bản 1
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Tác giả: <strong class="text-slate-800">{{ detailData?.question?.author_name || detailData?.current_revision?.author_name || 'Vô danh' }}</strong>
              </p>
            </div>
          </div>

          <button
            type="button"
            class="rounded-xl p-2 text-slate-400 hover:bg-slate-200/60 hover:text-slate-700 transition cursor-pointer"
            @click="closeDetailModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Modal Body: Comparison Panels -->
        <div v-if="isLoadingDetail" class="p-12 text-center text-sm font-medium text-slate-400">
          <RefreshCw class="mx-auto mb-3 h-6 w-6 animate-spin text-[#7C3AED]" />
          <span>Đang tải thông tin chi tiết và lịch sử các phiên bản...</span>
        </div>

        <div v-else class="flex-1 overflow-y-auto p-6 space-y-6">


          <!-- Trường hợp: Có Previous Revision (Gửi duyệt từ phiên bản 2 trở đi) -> Layout 2 cột SO SÁNH CŨ vs MỚI -->
          <div v-if="detailData?.previous_revision" class="space-y-4">
            <div class="flex items-center justify-between bg-purple-50/60 border border-purple-200/80 rounded-2xl p-3.5 text-xs text-purple-900">
              <div class="flex items-center gap-2">
                <AlertCircle class="h-4 w-4 text-[#7C3AED] shrink-0" />
                <span class="font-semibold">
                  Tác giả đã cập nhật câu hỏi (Phiên bản {{ detailData.current_revision.revision_number }}). Dưới đây là bảng đối chiếu giữa Phiên bản {{ detailData.previous_revision.revision_number }} ({{ getStatusLabel(detailData.previous_revision.status) }}) và nội dung mới gửi.
                </span>
              </div>
            </div>


            <!-- GRID 2 CỘT: CŨ | MỚI -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
              <!-- CỘT TRÁI: DỮ LIỆU CŨ -->
              <div class="rounded-2xl border border-rose-200 bg-rose-50/30 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-rose-200 pb-3">
                  <div class="flex items-center gap-2">
                    <span class="grid h-6 w-6 place-items-center rounded-lg bg-rose-600 text-white text-[11px] font-black">
                      {{ detailData.previous_revision.revision_number }}
                    </span>
                    <h3 class="text-sm font-black text-rose-900 uppercase tracking-wider">
                      DỮ LIỆU CŨ (Phiên bản {{ detailData.previous_revision.revision_number }})
                    </h3>
                  </div>
                  <span class="rounded-md bg-rose-100 text-rose-800 px-2 py-0.5 text-[11px] font-bold">
                    {{ getStatusLabel(detailData.previous_revision.status) }}
                  </span>
                </div>

                <!-- Lý do từ chối trước đó (Chỉ hiển thị nếu thực sự có phiên bản bị từ chối) -->
                <div
                  v-if="detailData.previous_rejected_revision && detailData.previous_rejected_revision.rejection_reason"
                  class="rounded-xl border border-rose-300 bg-white p-3 text-xs text-rose-900 space-y-1 shadow-sm"
                >
                  <div class="font-bold flex items-center gap-1.5 text-rose-700">
                    <XCircle class="h-4 w-4" />
                    <span>Lý do từ chối ở Phiên bản {{ detailData.previous_rejected_revision.revision_number }}:</span>
                  </div>
                  <p class="pl-5 leading-relaxed font-medium">
                    {{ detailData.previous_rejected_revision.rejection_reason }}
                  </p>
                  <div v-if="detailData.previous_rejected_revision.reviewed_by_name || detailData.previous_rejected_revision.reviewed_at" class="pl-5 text-[11px] text-slate-500 pt-1">
                    Người duyệt: <strong>{{ detailData.previous_rejected_revision.reviewed_by_name || 'Admin' }}</strong> • {{ formatDate(detailData.previous_rejected_revision.reviewed_at) }}
                  </div>
                </div>

                <!-- Meta Badges Cũ -->
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                  <span class="rounded-md bg-white border border-rose-200 px-2 py-0.5 font-bold text-slate-700">
                    Độ khó: {{ getDifficultyLabel(detailData.previous_revision.difficulty) }}
                  </span>
                  <span v-if="detailData.previous_revision.subject_name" class="rounded-md bg-white border border-rose-200 px-2 py-0.5 font-bold text-slate-700">
                    {{ detailData.previous_revision.subject_name }}
                  </span>
                  <span v-if="detailData.previous_revision.grade_name" class="rounded-md bg-white border border-rose-200 px-2 py-0.5 font-medium text-slate-600">
                    {{ detailData.previous_revision.grade_name }}
                  </span>
                  <span v-if="detailData.previous_revision.topic_name" class="rounded-md bg-white border border-rose-200 px-2 py-0.5 text-slate-600">
                    #{{ detailData.previous_revision.topic_name }}
                  </span>
                </div>

                <!-- Nội dung câu hỏi cũ -->
                <div class="space-y-1">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Nội dung câu hỏi cũ:</span>
                  <div class="rounded-xl border border-rose-200 bg-white p-3.5 text-xs font-semibold text-slate-800 leading-relaxed break-words">
                    {{ detailData.previous_revision.content }}
                  </div>
                </div>

                <!-- Danh sách đáp án cũ -->
                <div class="space-y-2">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Danh sách đáp án cũ:</span>
                  <div class="space-y-1.5">
                    <div
                      v-for="ans in detailData.previous_revision.answers"
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
                      <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600 shrink-0 flex items-center gap-1">
                        <Check class="h-3 w-3" :stroke-width="3" />
                        <span>Đúng</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- CỘT PHẢI: DỮ LIỆU MỚI (Phiên bản gửi duyệt hiện tại) -->
              <div class="rounded-2xl border border-purple-300 bg-purple-50/30 p-5 space-y-4 shadow-sm">
                <div class="flex items-center justify-between border-b border-purple-200 pb-3">
                  <div class="flex items-center gap-2">
                    <span class="grid h-6 w-6 place-items-center rounded-lg bg-[#7C3AED] text-white text-[11px] font-black">
                      {{ detailData.current_revision.revision_number }}
                    </span>
                    <h3 class="text-sm font-black text-purple-900 uppercase tracking-wider">
                      DỮ LIỆU MỚI (Phiên bản {{ detailData.current_revision.revision_number }})
                    </h3>
                  </div>
                  <span
                    class="rounded-md px-2 py-0.5 text-[11px] font-bold"
                    :class="getStatusBadgeClass(detailData.current_revision.status)"
                  >
                    {{ getStatusLabel(detailData.current_revision.status) }}
                  </span>
                </div>

                <!-- Ghi chú của User gửi kèm nếu có -->
                <div v-if="detailData.current_revision.request_note" class="rounded-xl border border-purple-200 bg-white p-3 text-xs text-purple-900 space-y-1 shadow-sm">
                  <div class="font-bold flex items-center gap-1.5 text-purple-700">
                    <MessageSquare class="h-4 w-4" />
                    <span>Ghi chú của tác giả:</span>
                  </div>
                  <p class="pl-5 leading-relaxed font-medium">
                    "{{ detailData.current_revision.request_note }}"
                  </p>
                </div>

                <!-- Meta Badges Mới -->
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                  <span
                    class="rounded-md px-2 py-0.5 font-bold"
                    :class="detailData.current_revision.difficulty !== detailData.previous_revision.difficulty
                      ? 'bg-purple-600 text-white shadow-sm'
                      : 'bg-white border border-purple-200 text-slate-700'"
                  >
                    Độ khó: {{ getDifficultyLabel(detailData.current_revision.difficulty) }}
                  </span>
                  <span v-if="detailData.current_revision.subject_name" class="rounded-md bg-white border border-purple-200 px-2 py-0.5 font-bold text-purple-700">
                    {{ detailData.current_revision.subject_name }}
                  </span>
                  <span v-if="detailData.current_revision.grade_name" class="rounded-md bg-white border border-purple-200 px-2 py-0.5 font-medium text-slate-600">
                    {{ detailData.current_revision.grade_name }}
                  </span>
                  <span v-if="detailData.current_revision.topic_name" class="rounded-md bg-white border border-purple-200 px-2 py-0.5 text-slate-600">
                    #{{ detailData.current_revision.topic_name }}
                  </span>
                </div>

                <!-- Nội dung câu hỏi mới -->
                <div class="space-y-1">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Nội dung câu hỏi mới:</span>
                  <div
                    class="rounded-xl border p-3.5 text-xs font-bold leading-relaxed break-words shadow-sm"
                    :class="detailData.current_revision.content !== detailData.previous_revision.content
                      ? 'border-purple-400 bg-white text-purple-950 ring-2 ring-purple-400/20'
                      : 'border-purple-200 bg-white text-slate-800'"
                  >
                    {{ detailData.current_revision.content }}
                  </div>
                </div>

                <!-- Danh sách đáp án mới -->
                <div class="space-y-2">
                  <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Danh sách đáp án mới:</span>
                  <div class="space-y-1.5">
                    <div
                      v-for="ans in detailData.current_revision.answers"
                      :key="ans.id || ans.key"
                      class="flex items-center gap-2.5 rounded-xl border p-2.5 text-xs font-semibold shadow-sm"
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

          <!-- Trường hợp: Phiên bản đầu tiên (Phiên bản 1) -> Layout 1 cột chi tiết -->
          <div v-else-if="detailData?.current_revision" class="rounded-2xl border border-slate-200 bg-slate-50/40 p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
              <div class="flex items-center gap-2">
                <span class="rounded-md bg-[#7C3AED] text-white px-2.5 py-1 text-xs font-bold">
                  Phiên bản 1
                </span>
                <span class="text-xs text-slate-500">
                  Gửi lúc: {{ formatDate(detailData.current_revision.created_at) }}
                </span>
              </div>
              <span
                class="rounded-md px-2.5 py-1 text-xs font-bold"
                :class="getStatusBadgeClass(detailData.current_revision.status)"
              >
                {{ getStatusLabel(detailData.current_revision.status) }}
              </span>
            </div>

            <!-- Ghi chú nếu có -->
            <div v-if="detailData.current_revision.request_note" class="rounded-xl border border-purple-200 bg-purple-50 p-3.5 text-xs text-purple-900">
              <strong>Ghi chú tác giả:</strong> {{ detailData.current_revision.request_note }}
            </div>

            <!-- Meta Badges -->
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-md bg-white border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700">
                Độ khó: {{ getDifficultyLabel(detailData.current_revision.difficulty) }}
              </span>
              <span v-if="detailData.current_revision.subject_name" class="rounded-md bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-1 text-xs font-bold">
                {{ detailData.current_revision.subject_name }}
              </span>
              <span v-if="detailData.current_revision.grade_name" class="rounded-md bg-white border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600">
                {{ detailData.current_revision.grade_name }}
              </span>
              <span v-if="detailData.current_revision.topic_name" class="rounded-md bg-white border border-slate-200 px-2.5 py-1 text-xs text-slate-600">
                Chủ đề: {{ detailData.current_revision.topic_name }}
              </span>
            </div>

            <!-- Nội dung câu hỏi -->
            <div class="space-y-1.5">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Nội dung câu hỏi:</h4>
              <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm font-bold text-slate-900 leading-relaxed">
                {{ detailData.current_revision.content }}
              </div>
            </div>

            <!-- Danh sách đáp án -->
            <div class="space-y-2">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Danh sách đáp án:</h4>
              <div class="grid gap-2 sm:grid-cols-2">
                <div
                  v-for="ans in detailData.current_revision.answers"
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

          <!-- TIMELINE LỊCH SỬ TẤT CẢ CÁC PHIÊN BẢN (NẾU CÓ TỪ 2 LẦN TRỞ LÊN) -->
          <div v-if="detailData?.history && detailData.history.length > 1" class="border-t border-slate-100 pt-5 space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-600 flex items-center gap-2">
              <History class="h-4 w-4 text-[#7C3AED]" />
              <span>Lịch sử phiên bản ({{ detailData.history.length }} phiên bản):</span>
            </h4>

            <div class="space-y-2">
              <div
                v-for="h in detailData.history"
                :key="h.id"
                class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-xs"
              >
                <div class="flex items-center gap-3">
                  <span class="font-bold text-slate-900">Phiên bản {{ h.revision_number }}</span>
                  <span
                    class="rounded-md px-2 py-0.5 text-[10px] font-bold"
                    :class="getStatusBadgeClass(h.status)"
                  >
                    {{ getStatusLabel(h.status) }}
                  </span>
                  <span class="text-slate-500 text-[11px]">• {{ formatDate(h.created_at) }}</span>
                </div>

                <div v-if="h.rejection_reason" class="text-rose-700 font-medium truncate max-w-[300px]" :title="h.rejection_reason">
                  Lý do từ chối: {{ h.rejection_reason }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-6 py-4">
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
            @click="closeDetailModal"
          >
            Đóng
          </button>

          <div class="flex items-center gap-2">
            <button
              v-if="detailData?.question?.bank_submission_status === 'pending'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition cursor-pointer shadow-sm"
              @click="openRejectModalFromDetail"
            >
              <X class="h-4 w-4" />
              <span>Từ chối</span>
            </button>

            <button
              v-if="detailData?.question?.bank_submission_status === 'pending'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-sm"
              @click="approveFromDetail"
            >
              <Check class="h-4 w-4" />
              <span>Phê duyệt vào Ngân hàng</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- REJECT MODAL -->
    <div
      v-if="isRejectModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
      @click.self="isRejectModalOpen = false"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2 text-rose-600 font-bold text-sm">
            <XCircle class="h-4 w-4" />
            <span>{{ isBulkReject ? `Từ chối ${selectedIds.length} câu hỏi` : `Từ chối câu hỏi #${rejectTargetId}` }}</span>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-700 cursor-pointer" @click="isRejectModalOpen = false">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">
            Lý do từ chối kiểm duyệt *
          </label>
          <textarea
            v-model="rejectionNote"
            rows="4"
            required
            placeholder="Nhập lý do câu hỏi chưa đạt chuẩn (vd: sai đáp án, nội dung trùng lặp, thiếu thông tin môn học...)"
            class="w-full rounded-xl border border-slate-200 p-3 text-xs font-medium text-slate-900 outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer"
            @click="isRejectModalOpen = false"
          >
            Hủy
          </button>

          <button
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 transition disabled:opacity-50 cursor-pointer"
            :disabled="!rejectionNote.trim() || isSubmittingReject"
            @click="confirmReject"
          >
            <span>{{ isSubmittingReject ? 'Đang xử lý...' : 'Xác nhận từ chối' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import { useRoute } from 'vue-router'
import {

  CheckCircle,
  RefreshCw,
  Search,
  Check,
  X,
  XCircle,
  Inbox,
  ChevronLeft,
  ChevronRight,
  GitCompare,
  RotateCcw,
  AlertCircle,
  AlertTriangle,
  MessageSquare,
  History,
} from 'lucide-vue-next'
import { adminBankRequestsApi, taxonomyApi } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'
import AppPagination from '@/components/common/AppPagination.vue'

const { beginTask, endTask } = useAppLoading()

const route = useRoute()
const showToast = inject('showToast')


const requests = ref([])
const selectedIds = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const allSubjects = ref([])

const stats = reactive({
  pending: 0,
  approved: 0,
  rejected: 0,
  priority: 0,
  total: 0,
})

const filters = reactive({
  search: '',
  status: 'pending',
  priority: '',
  subject_id: '',
  difficulty: '',
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

// Modal reject state
const isRejectModalOpen = ref(false)
const isBulkReject = ref(false)
const rejectTargetId = ref(null)
const rejectionNote = ref('')
const isSubmittingReject = ref(false)
const isProcessingBulk = ref(false)

// Modal Detail / Diff Comparison State
const isDetailModalOpen = ref(false)
const isLoadingDetail = ref(false)
const detailData = ref(null)

const hasActiveFilters = computed(() => {
  return Boolean(filters.search || filters.status !== 'pending' || filters.priority || filters.subject_id || filters.difficulty)
})

const loadRequests = async (resetPage = false) => {
  if (resetPage) {
    pagination.current_page = 1
  }
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = {
      page: pagination.current_page,
      per_page: pagination.per_page,
      status: filters.status,
      priority: filters.priority || undefined,
      search: filters.search.trim() || undefined,
      subject_id: filters.subject_id || undefined,
      difficulty: filters.difficulty || undefined,
    }

    const res = await adminBankRequestsApi.fetchRequests(params)
    requests.value = res.items || []
    pagination.total = res.total || 0
    pagination.current_page = res.currentPage || 1
    pagination.last_page = res.lastPage || 1
    pagination.per_page = res.perPage || 15

    if (res.stats) {
      stats.pending = res.stats.pending || 0
      stats.approved = res.stats.approved || 0
      stats.rejected = res.stats.rejected || 0
      stats.priority = res.stats.priority || 0
      stats.total = res.stats.total || 0
    }
  } catch (e) {
    errorMessage.value = `Không thể tải danh sách: ${e.message}`
  } finally {
    isLoading.value = false
  }
}

const openDetailModal = async (id) => {
  isDetailModalOpen.value = true
  isLoadingDetail.value = true
  detailData.value = null
  try {
    const res = await adminBankRequestsApi.fetchRequestDetail(id)
    detailData.value = res?.data || res
  } catch (e) {
    if (showToast) {
      showToast(`Không thể tải chi tiết câu hỏi: ${e.message}`, 'error')
    }
    isDetailModalOpen.value = false
  } finally {
    isLoadingDetail.value = false
  }
}

const closeDetailModal = () => {
  isDetailModalOpen.value = false
  detailData.value = null
}

const approveFromDetail = async () => {
  if (!detailData.value?.question?.id) return
  const qId = detailData.value.question.id
  await approveSingle(qId)
  closeDetailModal()
}

const openRejectModalFromDetail = () => {
  if (!detailData.value?.question?.id) return
  const qId = detailData.value.question.id
  closeDetailModal()
  openRejectModal(qId)
}

const fetchTaxonomy = async () => {
  try {
    const data = await taxonomyApi.tree()
    const payload = data?.education_levels ? data : (data?.data ?? data)
    if (payload) {
      allSubjects.value = payload.subjects || []
    }
  } catch (e) {
    console.error('Không tải được taxonomy:', e)
  }
}

const resetFilters = () => {
  filters.search = ''
  filters.status = 'pending'
  filters.priority = ''
  filters.subject_id = ''
  filters.difficulty = ''
  loadRequests(true)
}

const changePage = (page) => {
  pagination.current_page = page
  loadRequests()
}

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const approveSingle = async (id) => {
  try {
    const res = await adminBankRequestsApi.approve(id)
    if (showToast) {
      showToast(res?.message || `Đã phê duyệt câu hỏi #${id} vào Ngân hàng!`, 'success')
    }
    await loadRequests()
  } catch (e) {
    if (showToast) {
      showToast(`Không thể duyệt: ${e.message}`, 'error')
    }
  }
}

const openRejectModal = (id) => {
  isBulkReject.value = false
  rejectTargetId.value = id
  rejectionNote.value = ''
  isRejectModalOpen.value = true
}

const openBulkRejectModal = () => {
  if (selectedIds.value.length === 0) return
  isBulkReject.value = true
  rejectTargetId.value = null
  rejectionNote.value = ''
  isRejectModalOpen.value = true
}

const confirmReject = async () => {
  if (!rejectionNote.value.trim()) return
  isSubmittingReject.value = true
  try {
    if (isBulkReject.value) {
      const res = await adminBankRequestsApi.bulkReject(selectedIds.value, rejectionNote.value.trim())
      if (showToast) {
        showToast(res?.message || 'Đã từ chối các câu hỏi đã chọn!', 'success')
      }
      selectedIds.value = []
    } else {
      const res = await adminBankRequestsApi.reject(rejectTargetId.value, { note: rejectionNote.value.trim() })
      if (showToast) {
        showToast(res?.message || `Đã từ chối câu hỏi #${rejectTargetId.value}!`, 'success')
      }
    }
    isRejectModalOpen.value = false
    await loadRequests()
  } catch (e) {
    if (showToast) {
      showToast(`Không thể từ chối: ${e.message}`, 'error')
    }
  } finally {
    isSubmittingReject.value = false
  }
}

const handleBulkApprove = async () => {
  if (selectedIds.value.length === 0) return
  isProcessingBulk.value = true
  try {
    const res = await adminBankRequestsApi.bulkApprove(selectedIds.value)
    if (showToast) {
      showToast(res?.message || `Đã phê duyệt ${selectedIds.value.length} câu hỏi vào Ngân hàng!`, 'success')
    }
    selectedIds.value = []
    await loadRequests()
  } catch (e) {
    if (showToast) {
      showToast(`Không thể phê duyệt hàng loạt: ${e.message}`, 'error')
    }
  } finally {
    isProcessingBulk.value = false
  }
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'approved':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'rejected':
      return 'bg-rose-50 text-rose-700 border border-rose-200'
    case 'pending':
    default:
      return 'bg-amber-50 text-amber-700 border border-amber-200'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'approved':
      return 'Đã vào Ngân hàng'
    case 'rejected':
      return 'Bị từ chối'
    case 'pending':
    default:
      return 'Đang chờ duyệt'
  }
}

const getDifficultyLabel = (diff) => {
  switch (diff) {
    case 'easy':
      return 'Dễ'
    case 'hard':
      return 'Khó'
    case 'medium':
    default:
      return 'Vừa'
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

onMounted(async () => {
  beginTask()
  try {
  if (route.query.priority) {
    filters.priority = String(route.query.priority)
  }
  if (route.query.status) {
    filters.status = String(route.query.status)
  }
  if (route.query.search) {
    filters.search = String(route.query.search)
  }
  await Promise.all([fetchTaxonomy(), loadRequests()])
  if (route.query.open_id) {
    openDetailModal(Number(route.query.open_id))
  }
  } finally {
    endTask()
  }
})

</script>
