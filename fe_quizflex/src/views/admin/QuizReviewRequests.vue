<template>
  <section class="space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <div class="inline-flex items-center gap-2 rounded-md border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700">
            <ClipboardCheck class="h-3.5 w-3.5" />
            <span>Quiz Moderation</span>
            <span class="text-purple-400">•</span>
            <span>Duyệt công khai bài Quiz</span>
          </div>
          <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
            Yêu cầu kiểm duyệt Quiz công khai
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500">
            Xem xét các bài Quiz do giáo viên/học sinh đề xuất công khai. Khi phê duyệt, các câu hỏi đạt chuẩn sẽ được tự động snapshot vào Ngân hàng câu hỏi.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-center">
            <span class="text-[10px] font-bold uppercase text-slate-500">Chờ duyệt</span>
            <div v-if="isLoading" class="mx-auto mt-1 h-5 w-10 animate-pulse rounded bg-amber-200"></div><p v-else class="text-xl font-black text-amber-600">{{ stats.pending }}</p>
          </div>
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-center">
            <span class="text-[10px] font-bold uppercase text-slate-500">Đã duyệt</span>
            <div v-if="isLoading" class="mx-auto mt-1 h-5 w-10 animate-pulse rounded bg-emerald-200"></div><p v-else class="text-xl font-black text-emerald-600">{{ stats.approved }}</p>
          </div>
          <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-center">
            <span class="text-[10px] font-bold uppercase text-slate-500">Bị từ chối</span>
            <div v-if="isLoading" class="mx-auto mt-1 h-5 w-10 animate-pulse rounded bg-rose-200"></div><p v-else class="text-xl font-black text-rose-600">{{ stats.rejected }}</p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer shadow-sm"
            @click="loadRequests(false)"
          >
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isLoading }" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Search -->
        <div class="relative sm:col-span-2">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo tên Quiz, tác giả..."
            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:bg-white"
            @keyup.enter="loadRequests(true)"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="filters.status"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="pending">Chờ duyệt (Pending)</option>
            <option value="approved">Đã duyệt (Approved)</option>
            <option value="rejected">Bị từ chối (Rejected)</option>
            <option value="all">Tất cả trạng thái</option>
          </select>
        </div>

        <!-- Subject Filter -->
        <div>
          <select
            v-model="filters.subject_id"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="">Tất cả môn học</option>
            <option v-for="sub in allSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table of Requests -->
    <div class="card overflow-hidden">
      <!-- Bulk Actions Bar -->
      <div v-if="selectedIds.length > 0" class="bg-purple-50 border-b border-purple-100 p-3 px-5 flex items-center justify-between">
        <span class="text-xs font-bold text-purple-900">Đã chọn {{ selectedIds.length }} yêu cầu</span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="btn-primary text-xs px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white"
            @click="handleBulkApprove"
          >
            ✓ Phê duyệt các mục đã chọn
          </button>
          <button
            type="button"
            class="btn-secondary text-xs px-3.5 py-1.5 text-rose-700 hover:bg-rose-50"
            @click="openBulkRejectModal"
          >
            ✕ Từ chối các mục đã chọn
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
              <th class="py-3 px-4 w-10">
                <input
                  type="checkbox"
                  class="rounded accent-[#7C3AED] cursor-pointer"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="py-3 px-4">Bài Quiz</th>
              <th class="py-3 px-4">Người gửi</th>
              <th class="py-3 px-4 text-center">Phiên bản</th>
              <th class="py-3 px-4 text-center">Số câu</th>
              <th class="py-3 px-4 text-center">Trạng thái</th>
              <th class="py-3 px-4 text-center">Thời gian gửi</th>
              <th class="py-3 px-4 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="item in requests" :key="item.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3.5 px-4">
                <input
                  type="checkbox"
                  class="rounded accent-[#7C3AED] cursor-pointer"
                  :value="item.id"
                  v-model="selectedIds"
                />
              </td>
              <td class="py-3.5 px-4 max-w-xs">
                <div class="font-bold text-slate-900 truncate">{{ item.snapshot_title || item.quiz?.title || 'Quiz #' + item.quiz_id }}</div>
                <div class="text-[11px] text-slate-500 truncate mt-0.5">
                  {{ item.quiz?.subject?.name || item.quiz?.subject_name || 'Chung' }} • {{ item.quiz?.grade?.name || 'Khối lớp' }}
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2">
                  <span class="h-7 w-7 rounded-full bg-purple-100 text-purple-700 font-bold text-xs flex items-center justify-center shrink-0">
                    {{ (item.user?.name || 'U').charAt(0).toUpperCase() }}
                  </span>
                  <div class="truncate">
                    <p class="font-bold text-slate-900 truncate">{{ item.user?.name || 'Người dùng' }}</p>
                    <p class="text-[11px] text-slate-400 truncate">{{ item.user?.email }}</p>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="rounded-md bg-purple-50 text-[#7C3AED] border border-purple-200 px-2 py-0.5 text-[10px] font-bold">
                  Rev #{{ item.revision_number || 1 }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center font-bold text-slate-700">
                {{ item.snapshot_questions?.length ?? (item.quiz?.questions_count ?? item.quiz?.questions?.length ?? 0) }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :value="item.status" />
              </td>
              <td class="py-3.5 px-4 text-center text-slate-500 text-[11px]">
                {{ formatDate(item.created_at) }}
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="btn-secondary text-xs px-2.5 py-1 flex items-center gap-1 hover:text-[#7C3AED]"
                    title="Xem chi tiết & đối chiếu thay đổi"
                    @click="openDetailModal(item.id)"
                  >
                    <Eye :size="13" />
                  </button>

                  <template v-if="item.status === 'pending'">
                    <button
                      type="button"
                      class="btn-primary text-xs px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white"
                      title="Duyệt nhanh"
                      @click="handleSingleApprove(item)"
                    >
                      <Check :size="13" />
                    </button>
                    <button
                      type="button"
                      class="btn-secondary text-xs px-2.5 py-1 text-rose-700 hover:bg-rose-50 border-rose-200"
                      title="Từ chối"
                      @click="openRejectModal(item)"
                    >
                      <X :size="13" />
                    </button>
                  </template>
                </div>
              </td>
            </tr>
            <tr v-if="!isLoading && requests.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-400">
                <Inbox :size="36" class="mx-auto mb-2 opacity-40" />
                <p class="text-xs font-medium">Không có yêu cầu kiểm duyệt nào phù hợp bộ lọc.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="p-4 border-t border-slate-100">
        <AppPagination
          :current-page="pagination.currentPage"
          :last-page="pagination.lastPage"
          :total="pagination.total"
          :per-page="pagination.perPage"
          item-label="yêu cầu"
          @change="changePage"
        />
      </div>
    </div>

    <!-- DETAIL & MODERATION MODAL (WITH 2-COLUMN COMPARISON TAB) -->
    <div
      v-if="isDetailModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
    >
      <div class="card max-w-5xl w-full max-h-[92vh] flex flex-col shadow-2xl animate-scaleUp overflow-hidden">
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600">Thẩm định Quiz</span>
              <span class="rounded bg-purple-100 text-purple-800 font-bold px-2 py-0.5 text-[10px]">
                Revision #{{ currentDetail?.current_revision?.revision_number || currentDetail?.review_request?.revision_number || 1 }}
              </span>
            </div>
            <h2 class="text-lg font-black text-slate-900 mt-1">
              {{ currentDetail?.current_revision?.title || currentDetail?.quiz?.title }}
            </h2>
          </div>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-base font-bold p-1"
            @click="isDetailModalOpen = false"
          >
            ✕
          </button>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center border-b border-slate-200 bg-white px-6 shrink-0 gap-6 text-xs font-bold">
          <button
            type="button"
            class="py-3 border-b-2 transition flex items-center gap-1.5"
            :class="activeTab === 'diff' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-800'"
            @click="activeTab = 'diff'"
          >
            <span>So sánh 2 cột (Chỉ phần thay đổi)</span>
            <span
              v-if="currentDetail?.diff?.has_previous"
              class="rounded-full bg-purple-100 text-purple-800 text-[10px] px-2 py-0.2"
            >
              Rev #{{ currentDetail?.previous_revision?.revision_number }} vs #{{ currentDetail?.current_revision?.revision_number }}
            </span>
          </button>
          <button
            type="button"
            class="py-3 border-b-2 transition"
            :class="activeTab === 'current' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-800'"
            @click="activeTab = 'current'"
          >
            Toàn bộ Snapshot hiện tại (#{{ currentDetail?.current_revision?.revision_number || 1 }})
          </button>
          <button
            type="button"
            class="py-3 border-b-2 transition flex items-center gap-1"
            :class="activeTab === 'history' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-800'"
            @click="activeTab = 'history'"
          >
            <span>Lịch sử xét duyệt ({{ currentDetail?.history?.length || 0 }})</span>
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-[#F8FAFC]">
          <!-- TAB 1: STRICT 2-COLUMN COMPARISON VIEW (ONLY CHANGED PARTS) -->
          <div v-if="activeTab === 'diff'" class="space-y-6">
            <!-- Case 1.1: First submission (Revision #1) -> No previous snapshot -->
            <div v-if="!currentDetail?.diff?.has_previous" class="space-y-4">
              <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-900 shadow-sm flex items-center gap-2.5">
                <AlertCircle class="h-4 w-4 text-blue-600 shrink-0" />
                <div>
                  <strong class="font-bold">Đây là lần gửi duyệt đầu tiên (Revision #1).</strong>
                  <p class="text-blue-700 mt-0.5">Chưa có phiên bản trước đó để đối chiếu so sánh. Dưới đây là toàn bộ nội dung của bài Quiz.</p>
                </div>
              </div>

              <!-- 2-Column Comparison Layout for Revision #1 -->
              <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <!-- Column Header -->
                <div class="grid grid-cols-1 md:grid-cols-2 border-b border-slate-200 bg-slate-100 text-xs font-black uppercase text-slate-600 tracking-wider">
                  <div class="p-3.5 border-b md:border-b-0 md:border-r border-slate-200 text-slate-400">
                    DỮ LIỆU CŨ (Chưa có)
                  </div>
                  <div class="p-3.5 text-purple-700">
                    DỮ LIỆU MỚI (Revision #1)
                  </div>
                </div>

                <!-- Row: Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 text-xs">
                  <div class="p-5 text-slate-400 italic bg-slate-50/50">
                    Chưa có dữ liệu lần trước (Phiên bản đầu tiên)
                  </div>
                  <div class="p-5 space-y-4 bg-white">
                    <div>
                      <span class="text-[10px] font-bold uppercase text-slate-400 block">Tiêu đề Quiz:</span>
                      <p class="font-bold text-sm text-slate-900 mt-0.5">{{ currentDetail?.current_revision?.title }}</p>
                    </div>
                    <div v-if="currentDetail?.current_revision?.description">
                      <span class="text-[10px] font-bold uppercase text-slate-400 block">Mô tả:</span>
                      <p class="text-slate-600 mt-0.5">{{ currentDetail.current_revision.description }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-[11px] pt-1">
                      <div>Môn: <strong class="text-slate-800">{{ currentDetail?.current_revision?.subject_name || '-' }}</strong></div>
                      <div>Khối: <strong class="text-slate-800">{{ currentDetail?.current_revision?.grade_name || '-' }}</strong></div>
                      <div>Thời gian: <strong class="text-slate-800">{{ currentDetail?.current_revision?.time_limit_minutes || 15 }} phút</strong></div>
                      <div>Độ khó: <strong class="text-slate-800">{{ currentDetail?.current_revision?.difficulty || 'medium' }}</strong></div>
                    </div>
                  </div>
                </div>

                <!-- Questions List in Column 2 -->
                <div class="grid grid-cols-1 md:grid-cols-2 border-t border-slate-200 divide-y md:divide-y-0 md:divide-x divide-slate-200 text-xs">
                  <div class="p-5 text-slate-400 italic bg-slate-50/50">
                    Không có câu hỏi cũ
                  </div>
                  <div class="p-5 space-y-3 bg-white">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block">
                      Danh sách câu hỏi ({{ currentDetail?.current_revision?.questions?.length || 0 }} câu)
                    </span>
                    <div
                      v-for="(q, idx) in currentDetail?.current_revision?.questions"
                      :key="q.id ?? idx"
                      class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 space-y-2.5"
                    >
                      <div class="flex items-start justify-between gap-2">
                        <span class="font-bold text-slate-900 text-xs">Câu {{ idx + 1 }}: {{ q.content || q.question }}</span>
                        <span class="text-[10px] font-bold text-slate-500 bg-white border border-slate-200 px-1.5 py-0.5 rounded">{{ q.points || 10 }} pts</span>
                      </div>
                      <div class="grid gap-1.5 sm:grid-cols-2 text-[11px]">
                        <div
                          v-for="(ans, aIdx) in q.answers"
                          :key="ans.id ?? aIdx"
                          class="p-2 rounded border"
                          :class="ans.is_correct ? 'bg-emerald-50 border-emerald-300 text-emerald-900 font-bold' : 'bg-white border-slate-200 text-slate-700'"
                        >
                          <span>{{ String.fromCharCode(65 + aIdx) }}. {{ ans.content }}</span>
                          <span v-if="ans.is_correct" class="ml-1 text-[10px] text-emerald-700">✓</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Case 1.2: Revision #2+ (Has previous snapshot) -->
            <div v-else class="space-y-5">
              <!-- Previous Rejection Reason Banner -->
              <div v-if="currentDetail?.previous_rejection_reason" class="rounded-xl border border-rose-300 bg-rose-50 p-4 text-xs text-rose-900 shadow-sm space-y-1">
                <span class="font-bold text-rose-800 uppercase tracking-wider text-[10px]">
                  Lý do từ chối ở lần trước (Revision #{{ currentDetail?.previous_revision?.revision_number }}):
                </span>
                <p class="font-semibold text-rose-900 text-xs leading-relaxed">{{ currentDetail.previous_rejection_reason }}</p>
              </div>

              <!-- Case 1.2A: NO DIFFERENCES AT ALL -->
              <div
                v-if="!hasAnyDifferences"
                class="rounded-2xl border border-emerald-200 bg-white p-12 text-center space-y-3 shadow-sm"
              >
                <div class="h-12 w-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto text-xl font-bold">
                  ✓
                </div>
                <h3 class="text-base font-black text-slate-900">Không có gì thay đổi so với lần gửi trước.</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                  Toàn bộ thông tin cấu hình, danh sách câu hỏi và các phương án đáp án hoàn toàn trùng khớp với phiên bản trước đó.
                </p>
              </div>

              <!-- Case 1.2B: HAS DIFFERENCES -> RENDER 2-COLUMN TABLE FOR ONLY CHANGED DATA -->
              <div v-else class="space-y-6">
                <!-- Main 2-Column Comparison Container -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                  <!-- 2-COLUMN HEADER -->
                  <div class="grid grid-cols-1 md:grid-cols-2 border-b border-slate-200 bg-slate-100 text-xs font-black uppercase tracking-wider">
                    <div class="p-3.5 border-b md:border-b-0 md:border-r border-slate-200 text-slate-600 flex items-center gap-2">
                      <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                      <span>DỮ LIỆU CŨ (Revision #{{ currentDetail?.previous_revision?.revision_number }})</span>
                    </div>
                    <div class="p-3.5 text-purple-800 flex items-center gap-2">
                      <span class="h-2.5 w-2.5 rounded-full bg-purple-600"></span>
                      <span>DỮ LIỆU MỚI (Revision #{{ currentDetail?.current_revision?.revision_number }})</span>
                    </div>
                  </div>

                  <!-- SECTION A: METADATA DIFFERENCES -->
                  <div v-if="currentDetail?.diff?.metadata_changed" class="border-b border-slate-200">
                    <div class="bg-slate-50/80 px-4 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                      1. Thay đổi thông tin chung
                    </div>

                    <div
                      v-for="(change, key) in currentDetail?.diff?.changes"
                      :key="key"
                      class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 border-b border-slate-100 last:border-b-0 text-xs"
                    >
                      <!-- Old Column -->
                      <div class="p-4 bg-rose-50/20 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">{{ change.label || key }}:</span>
                        <p class="text-rose-700 font-medium line-through">{{ change.old || '(Để trống)' }}</p>
                      </div>

                      <!-- New Column -->
                      <div class="p-4 bg-emerald-50/20 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">{{ change.label || key }}:</span>
                        <p class="text-emerald-800 font-bold">{{ change.new || '(Để trống)' }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- SECTION B: QUESTIONS DIFFERENCES -->
                  <div v-if="currentDetail?.diff?.question_diffs?.length > 0">
                    <div class="bg-slate-50/80 px-4 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 flex items-center justify-between">
                      <span>2. Thay đổi câu hỏi & đáp án ({{ currentDetail.diff.question_diffs.length }} thay đổi)</span>
                      <span class="text-[10px] text-slate-400 font-normal">Các câu hỏi không thay đổi được ẩn tự động</span>
                    </div>

                    <div
                      v-for="(diffItem, dIdx) in currentDetail.diff.question_diffs"
                      :key="dIdx"
                      class="border-b border-slate-200 last:border-b-0"
                    >
                      <!-- TYPE 1: ADDED QUESTION -->
                      <div v-if="diffItem.status === 'added'" class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 text-xs">
                        <div class="p-4 text-slate-400 italic bg-slate-50/50 flex items-center">
                          <span>Không có dữ liệu (Câu hỏi mới được thêm)</span>
                        </div>
                        <div class="p-4 space-y-2.5 bg-emerald-50/20">
                          <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-emerald-900 text-xs">
                              [MỚI] Câu {{ diffItem.order }}: {{ diffItem.new_question?.content }}
                            </span>
                            <span class="rounded bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 text-[10px]">
                              + Thêm mới
                            </span>
                          </div>
                          <div class="grid gap-1.5 sm:grid-cols-2 text-[11px]">
                            <div
                              v-for="(ans, aIdx) in diffItem.new_question?.answers"
                              :key="aIdx"
                              class="p-2 rounded border"
                              :class="ans.is_correct ? 'bg-emerald-100 border-emerald-300 text-emerald-900 font-bold' : 'bg-white border-slate-200 text-slate-700'"
                            >
                              <span>{{ String.fromCharCode(65 + aIdx) }}. {{ ans.content }}</span>
                              <span v-if="ans.is_correct" class="ml-1 text-[10px] text-emerald-700">✓</span>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- TYPE 2: REMOVED QUESTION -->
                      <div v-else-if="diffItem.status === 'removed'" class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 text-xs">
                        <div class="p-4 space-y-2.5 bg-rose-50/20">
                          <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-rose-900 text-xs">
                              [CŨ] Câu {{ diffItem.order }}: {{ diffItem.old_question?.content }}
                            </span>
                            <span class="rounded bg-rose-100 text-rose-800 font-bold px-2 py-0.5 text-[10px]">
                              - Đã xóa
                            </span>
                          </div>
                          <div class="grid gap-1.5 sm:grid-cols-2 text-[11px]">
                            <div
                              v-for="(ans, aIdx) in diffItem.old_question?.answers"
                              :key="aIdx"
                              class="p-2 rounded border bg-white border-slate-200 text-slate-600 line-through"
                            >
                              <span>{{ String.fromCharCode(65 + aIdx) }}. {{ ans.content }}</span>
                            </div>
                          </div>
                        </div>
                        <div class="p-4 text-rose-600 font-medium italic bg-rose-50/30 flex items-center">
                          <span>Câu hỏi đã bị xóa trong phiên bản này</span>
                        </div>
                      </div>

                      <!-- TYPE 3: MODIFIED QUESTION -->
                      <div v-else-if="diffItem.status === 'modified'" class="p-4 space-y-3 bg-white">
                        <!-- Question Header Banner -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                          <span class="font-bold text-xs text-purple-900">
                            Câu hỏi số {{ diffItem.order }} — Đã chỉnh sửa
                          </span>
                          <span class="rounded bg-blue-100 text-blue-800 font-bold px-2 py-0.5 text-[10px]">
                            Đã sửa
                          </span>
                        </div>

                        <!-- 2-Column Grid inside Modified Question -->
                        <div class="space-y-3">
                          <!-- 1. Question Content Changed -->
                          <div v-if="diffItem.field_changes?.content" class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 rounded-xl border border-slate-200 overflow-hidden text-xs">
                            <div class="p-3 bg-rose-50/20">
                              <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Nội dung câu hỏi cũ:</span>
                              <p class="text-rose-800 line-through">{{ diffItem.field_changes.content.old }}</p>
                            </div>
                            <div class="p-3 bg-emerald-50/20">
                              <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Nội dung câu hỏi mới:</span>
                              <p class="text-emerald-900 font-bold">{{ diffItem.field_changes.content.new }}</p>
                            </div>
                          </div>

                          <!-- 2. Question Points or Type Changed -->
                          <div v-if="diffItem.field_changes?.points || diffItem.field_changes?.type" class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 rounded-xl border border-slate-200 overflow-hidden text-xs">
                            <div class="p-3 bg-slate-50">
                              <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Cấu hình cũ:</span>
                              <p v-if="diffItem.field_changes?.points" class="text-slate-600 line-through">Điểm: {{ diffItem.field_changes.points.old }} pts</p>
                              <p v-if="diffItem.field_changes?.type" class="text-slate-600 line-through">Loại: {{ diffItem.field_changes.type.old }}</p>
                            </div>
                            <div class="p-3 bg-emerald-50/20">
                              <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Cấu hình mới:</span>
                              <p v-if="diffItem.field_changes?.points" class="text-emerald-800 font-bold">Điểm: {{ diffItem.field_changes.points.new }} pts</p>
                              <p v-if="diffItem.field_changes?.type" class="text-emerald-800 font-bold">Loại: {{ diffItem.field_changes.type.new }}</p>
                            </div>
                          </div>

                          <!-- 3. Answers Changed -->
                          <div v-if="diffItem.answer_changes?.length > 0" class="space-y-2">
                            <span class="text-[11px] font-bold text-slate-700 block">Các đáp án có sự thay đổi:</span>
                            <div
                              v-for="(ansDiff, aIdx) in diffItem.answer_changes"
                              :key="aIdx"
                              class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 rounded-xl border border-slate-200 overflow-hidden text-xs"
                            >
                              <!-- Old Answer Column -->
                              <div class="p-3 bg-rose-50/20 flex items-center justify-between">
                                <span v-if="ansDiff.status === 'added'" class="text-slate-400 italic">Không có đáp án này</span>
                                <template v-else>
                                  <span class="text-rose-800 line-through">{{ ansDiff.key }}. {{ ansDiff.old_content }}</span>
                                  <span v-if="ansDiff.old_is_correct" class="text-[10px] font-bold text-emerald-700 ml-2">✓ Đúng</span>
                                </template>
                              </div>

                              <!-- New Answer Column -->
                              <div class="p-3 bg-emerald-50/20 flex items-center justify-between">
                                <span v-if="ansDiff.status === 'removed'" class="text-rose-600 italic">Đáp án đã bị xóa</span>
                                <template v-else>
                                  <span class="text-emerald-900 font-bold">{{ ansDiff.key }}. {{ ansDiff.new_content }}</span>
                                  <span v-if="ansDiff.new_is_correct" class="text-[10px] font-bold text-emerald-700 ml-2">✓ Đúng</span>
                                </template>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: FULL CURRENT SNAPSHOT -->
          <div v-else-if="activeTab === 'current'" class="space-y-6">
            <!-- Quiz & Creator Overview Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-xs">
              <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                <span class="block text-[10px] font-bold uppercase text-slate-400">Người tạo</span>
                <span class="font-bold text-slate-900 block mt-0.5">{{ currentDetail?.review_request?.user?.name }}</span>
                <span class="text-[11px] text-slate-500">{{ currentDetail?.review_request?.user?.email }}</span>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                <span class="block text-[10px] font-bold uppercase text-slate-400">Thời gian nộp</span>
                <span class="font-bold text-slate-900 block mt-0.5">{{ formatDate(currentDetail?.review_request?.created_at) }}</span>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                <span class="block text-[10px] font-bold uppercase text-slate-400">Thời gian làm bài</span>
                <span class="font-bold text-purple-700 block mt-0.5">
                  {{ currentDetail?.current_revision?.time_limit_minutes || 15 }} phút
                </span>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                <span class="block text-[10px] font-bold uppercase text-slate-400">Trạng thái</span>
                <div class="mt-1">
                  <StatusBadge :value="currentDetail?.review_request?.status" />
                </div>
              </div>
            </div>

            <!-- Author Note -->
            <div v-if="currentDetail?.review_request?.request_note" class="rounded-xl border border-purple-200 bg-purple-50 p-4 text-xs text-purple-900 shadow-sm">
              <span class="font-bold block mb-1">Lời nhắn từ tác giả:</span>
              <p class="leading-relaxed">{{ currentDetail.review_request.request_note }}</p>
            </div>

            <!-- Description -->
            <div v-if="currentDetail?.current_revision?.description" class="rounded-xl border border-slate-200 bg-white p-4 text-xs shadow-sm">
              <span class="font-bold text-slate-800 block mb-1">Mô tả bài Quiz:</span>
              <p class="text-slate-600 leading-relaxed">{{ currentDetail.current_revision.description }}</p>
            </div>

            <!-- Questions List Section -->
            <div class="space-y-3">
              <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <h3 class="text-sm font-bold text-slate-900">
                  Danh sách câu hỏi Snapshot ({{ currentDetail?.current_revision?.questions?.length || currentDetail?.quiz?.questions?.length || 0 }})
                </h3>
                <span class="text-[11px] text-slate-500 font-medium">
                  Bản ghi Snapshot bất biến tại thời điểm gửi duyệt
                </span>
              </div>

              <div class="space-y-3">
                <div
                  v-for="(q, index) in (currentDetail?.current_revision?.questions || currentDetail?.quiz?.questions || [])"
                  :key="q.id ?? index"
                  class="rounded-xl border border-slate-200 bg-white p-4 space-y-3 shadow-sm"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                      <span class="h-6 w-6 rounded-lg bg-purple-100 text-[#7C3AED] font-black text-xs flex items-center justify-center shrink-0">
                        {{ index + 1 }}
                      </span>
                      <h4 class="text-xs font-bold text-slate-900 leading-snug">{{ q.content || q.question }}</h4>
                    </div>
                    <span class="rounded-md bg-slate-100 border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600 shrink-0">
                      {{ q.points || 10 }} pts
                    </span>
                  </div>

                  <!-- Answers Grid -->
                  <div class="grid gap-2 sm:grid-cols-2 text-xs">
                    <div
                      v-for="(ans, aIndex) in q.answers"
                      :key="ans.id ?? aIndex"
                      class="p-2.5 rounded-lg border text-xs flex items-center justify-between"
                      :class="ans.is_correct ? 'bg-emerald-50 border-emerald-300 text-emerald-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-700'"
                    >
                      <span>{{ String.fromCharCode(65 + aIndex) }}. {{ ans.content }}</span>
                      <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-700 uppercase">✓ Đúng</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 3: REVIEW HISTORY TIMELINE -->
          <div v-else-if="activeTab === 'history'" class="space-y-4">
            <div
              v-for="(hist, hIdx) in currentDetail?.history"
              :key="hist.id ?? hIdx"
              class="rounded-xl border bg-white p-5 space-y-3 text-xs shadow-sm"
              :class="hist.status === 'approved' ? 'border-emerald-300' : (hist.status === 'rejected' ? 'border-rose-300' : 'border-amber-300')"
            >
              <div class="flex items-center justify-between flex-wrap gap-2">
                <span class="font-black text-slate-900 text-sm">
                  Revision #{{ hist.revision_number }}
                </span>
                <StatusBadge :value="hist.status" />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] text-slate-500">
                <div>Thời gian nộp: <strong class="text-slate-800">{{ formatDate(hist.created_at) }}</strong></div>
                <div>Thời gian xử lý: <strong class="text-slate-800">{{ formatDate(hist.reviewed_at) }}</strong></div>
                <div>Người duyệt: <strong class="text-slate-800">{{ hist.reviewer?.name || '-' }}</strong></div>
              </div>

              <div v-if="hist.request_note" class="rounded-lg bg-purple-50 p-2.5 text-purple-900 border border-purple-200/60">
                <span class="font-bold block text-[10px] uppercase text-purple-700 mb-0.5">Lời nhắn của tác giả:</span>
                <p>{{ hist.request_note }}</p>
              </div>

              <div v-if="hist.rejection_reason" class="rounded-lg bg-rose-50 p-2.5 text-rose-900 border border-rose-200">
                <span class="font-bold block text-[10px] uppercase text-rose-700 mb-0.5">Lý do từ chối:</span>
                <p>{{ hist.rejection_reason }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions (ONLY APPROVE / REJECT — NO EDIT QUIZ) -->
        <div class="p-4 border-t border-slate-100 bg-white flex items-center justify-between shrink-0">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            @click="isDetailModalOpen = false"
          >
            Đóng
          </button>

          <div v-if="currentDetail?.review_request?.status === 'pending'" class="flex items-center gap-2">
            <button
              type="button"
              class="btn-secondary text-xs px-4 py-2 text-rose-700 hover:bg-rose-50 border-rose-200"
              :disabled="isProcessing"
              @click="openRejectModal(currentDetail.review_request)"
            >
              ✕ Từ chối phê duyệt
            </button>
            <button
              type="button"
              class="btn-primary text-xs px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5"
              :disabled="isProcessing"
              @click="handleSingleApprove(currentDetail.review_request)"
            >
              <Check :size="14" />
              <span>{{ isProcessing ? 'Đang xử lý...' : 'Phê duyệt & Công khai Quiz' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- REJECT REASON MODAL -->
    <div
      v-if="isRejectModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
    >
      <div class="card p-6 max-w-md w-full space-y-4 shadow-2xl animate-scaleUp">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-black text-rose-600 flex items-center gap-2">
            <AlertCircle :size="18" />
            <span>Từ chối phê duyệt Quiz</span>
          </h3>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-sm font-bold"
            @click="isRejectModalOpen = false"
          >
            ✕
          </button>
        </div>

        <p class="text-xs leading-relaxed text-slate-600">
          Vui lòng nhập lý do từ chối cụ thể để tác giả biết và chỉnh sửa lại bài Quiz trước khi gửi lại yêu cầu.
        </p>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Lý do từ chối <span class="text-rose-500">*</span></label>
          <textarea
            v-model="rejectionReason"
            rows="4"
            class="field text-xs resize-none w-full"
            placeholder="Ví dụ: Câu số 3 thiếu đáp án đúng, nội dung câu hỏi chưa rõ ràng..."
            required
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            :disabled="isProcessing"
            @click="isRejectModalOpen = false"
          >
            Hủy
          </button>
          <button
            type="button"
            class="btn-primary text-xs px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white flex items-center gap-1.5"
            :disabled="isProcessing || !rejectionReason.trim()"
            @click="handleConfirmReject"
          >
            <span>{{ isProcessing ? 'Đang xử lý...' : 'Xác nhận từ chối' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import {
  ClipboardCheck,
  Search,
  RefreshCw,
  Eye,
  Check,
  X,
  Inbox,
  AlertCircle,
} from 'lucide-vue-next'
import { quizReviewApi, taxonomyApi } from '@/services/api'
import StatusBadge from '@/components/common/StatusBadge.vue'
import AppPagination from '@/components/common/AppPagination.vue'
import { useAppLoading } from '@/composables/useAppLoading'

const { beginTask, endTask } = useAppLoading()

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const isLoading = ref(true)
const isProcessing = ref(false)
const requests = ref([])
const selectedIds = ref([])
const allSubjects = ref([])

const activeTab = ref('diff')

const stats = reactive({
  pending: 0,
  approved: 0,
  rejected: 0,
  total: 0,
})

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 10,
  total: 0,
})

const filters = reactive({
  search: '',
  status: 'pending',
  subject_id: '',
})

const isDetailModalOpen = ref(false)
const currentDetail = ref(null)

const isRejectModalOpen = ref(false)
const targetRejectRequest = ref(null)
const rejectionReason = ref('')
const isBulkReject = ref(false)

const isAllSelected = computed(() => {
  return requests.value.length > 0 && selectedIds.value.length === requests.value.length
})

const hasAnyDifferences = computed(() => {
  if (!currentDetail.value?.diff) return false
  const diff = currentDetail.value.diff
  if (diff.has_differences !== undefined) return Boolean(diff.has_differences)
  const metadataChanged = Boolean(diff.metadata_changed)
  const hasQuestionDiffs = Array.isArray(diff.question_diffs) && diff.question_diffs.length > 0
  return metadataChanged || hasQuestionDiffs
})

const toggleSelectAll = (e) => {
  if (e.target.checked) {
    selectedIds.value = requests.value.map(r => r.id)
  } else {
    selectedIds.value = []
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const loadRequests = async (resetPage = false) => {
  if (resetPage) pagination.currentPage = 1
  isLoading.value = true
  try {
    const params = {
      page: pagination.currentPage,
      per_page: pagination.perPage,
      status: filters.status,
      search: filters.search.trim() || undefined,
      subject_id: filters.subject_id || undefined,
    }
    const res = await quizReviewApi.fetchAdminReviewRequests(params)
    requests.value = res.items || []
    pagination.total = res.total || 0
    pagination.currentPage = res.current_page || 1
    pagination.lastPage = res.last_page || 1

    if (res.stats) {
      stats.pending = res.stats.pending || 0
      stats.approved = res.stats.approved || 0
      stats.rejected = res.stats.rejected || 0
      stats.total = res.stats.total || 0
    }
  } catch (error) {
    if (showToast) showToast(`Không tải được danh sách: ${error.message}`, 'error')
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  pagination.currentPage = page
  loadRequests()
}

const openDetailModal = async (requestId) => {
  try {
    activeTab.value = 'diff'
    const res = await quizReviewApi.getAdminReviewRequest(requestId)
    currentDetail.value = res
    isDetailModalOpen.value = true
  } catch (error) {
    if (showToast) showToast(`Không tải được chi tiết: ${error.message}`, 'error')
  }
}

const handleSingleApprove = (request) => {
  const quizTitle = request.quiz?.title || ''
  const message = `Bạn có chắc chắn muốn phê duyệt và công khai bài Quiz "${quizTitle}"? Các câu hỏi từ kho cá nhân của người dùng sẽ được tạo bản sao vào Ngân hàng câu hỏi.`

  if (showConfirm) {
    showConfirm('Xác nhận phê duyệt Quiz', message, async () => {
      await executeSingleApprove(request)
    })
  } else {
    executeSingleApprove(request)
  }
}

const executeSingleApprove = async (request) => {
  isProcessing.value = true
  try {
    const res = await quizReviewApi.adminApprove(request.id)
    if (showToast) showToast(res.message || 'Phê duyệt bài Quiz thành công!', 'success')
    isDetailModalOpen.value = false
    await loadRequests()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Phê duyệt thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

const openRejectModal = (request) => {
  targetRejectRequest.value = request
  rejectionReason.value = ''
  isBulkReject.value = false
  isRejectModalOpen.value = true
}

const openBulkRejectModal = () => {
  targetRejectRequest.value = null
  rejectionReason.value = ''
  isBulkReject.value = true
  isRejectModalOpen.value = true
}

const handleConfirmReject = async () => {
  if (!rejectionReason.value.trim()) {
    if (showToast) showToast('Vui lòng nhập lý do từ chối!', 'error')
    return
  }

  isProcessing.value = true
  try {
    if (isBulkReject.value) {
      const res = await quizReviewApi.adminBulkReject(selectedIds.value, rejectionReason.value.trim())
      if (showToast) showToast(res.message || 'Đã từ chối các yêu cầu được chọn!', 'success')
      selectedIds.value = []
    } else if (targetRejectRequest.value) {
      const res = await quizReviewApi.adminReject(targetRejectRequest.value.id, rejectionReason.value.trim())
      if (showToast) showToast(res.message || 'Đã từ chối bài Quiz thành công!', 'success')
      isDetailModalOpen.value = false
    }
    isRejectModalOpen.value = false
    await loadRequests()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Từ chối thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

const handleBulkApprove = () => {
  const count = selectedIds.value.length
  const message = `Bạn có chắc chắn muốn phê duyệt ${count} bài Quiz đã chọn? Tất cả bài Quiz này sẽ được chuyển sang trạng thái Công khai (Public).`

  if (showConfirm) {
    showConfirm('Phê duyệt hàng loạt', message, async () => {
      await executeBulkApprove()
    })
  } else {
    executeBulkApprove()
  }
}

const executeBulkApprove = async () => {
  isProcessing.value = true
  try {
    const res = await quizReviewApi.adminBulkApprove(selectedIds.value)
    if (showToast) showToast(res.message || 'Đã phê duyệt các bài Quiz thành công!', 'success')
    selectedIds.value = []
    await loadRequests()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Phê duyệt hàng loạt thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

onMounted(async () => {
  beginTask()
  try {
    allSubjects.value = await taxonomyApi.fetchSubjects()
  } catch (e) {
    console.error(e)
  }
    await loadRequests()
  } finally {
    endTask()
  }
})
</script>
