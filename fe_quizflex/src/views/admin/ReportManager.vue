<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">Quản lý Báo cáo</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-sm">
            <Flag class="h-6 w-6" />
          </div>
          <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">
              Giám sát & Xử lý Báo cáo Câu hỏi
            </h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
              Trung tâm giám sát phản ánh sai sót từ người học. Hệ thống tự động thông báo tới tác giả sửa và tự động hoàn tất báo cáo khi Quản trị viên duyệt bản chỉnh sửa của tác giả.
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <router-link
            to="/admin/question-bank?tab=reported"
            class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#6D28D9] transition cursor-pointer"
          >
            <HelpCircle class="h-4 w-4" />
            <span>Mở Ngân hàng Ưu tiên (Thẩm định)</span>
          </router-link>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300 shadow-sm cursor-pointer"
            @click="fetchReports"
          >
            <RefreshCw class="h-4 w-4 text-slate-500" :class="{ 'animate-spin': isLoading }" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>

      <!-- KPI Stats Cards (Clickable) -->
      <div class="mt-6 grid grid-cols-2 gap-3 xl:grid-cols-5">
        <!-- 1. Chờ chủ câu hỏi xử lý -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'pending' ? 'border-amber-500 bg-amber-50/60 ring-2 ring-amber-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Xem các báo cáo đang chờ chủ câu hỏi sửa"
          @click="selectedStatus = 'pending'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
              <Clock class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Chờ chủ xử lý</p>
              <p class="text-xl font-black text-amber-600">{{ stats.pending || 0 }}</p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-amber-700 font-bold">Chưa sửa</p>
        </div>

        <!-- 2. Chủ câu hỏi đã cập nhật -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'author_updated' ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Tác giả đã cập nhật nội dung mới"
          @click="selectedStatus = 'author_updated'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
              <CheckCircle2 class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Chủ đã sửa</p>
              <p class="text-xl font-black text-blue-600">{{ stats.author_updated || 0 }}</p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-blue-700 font-bold">Có revision mới</p>
        </div>

        <!-- 3. Cần Admin xử lý -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'admin_review_required' ? 'border-rose-500 bg-rose-50/60 ring-2 ring-rose-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Trường hợp ngoại lệ cần Admin can thiệp"
          @click="selectedStatus = 'admin_review_required'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
              <AlertTriangle class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Cần Admin</p>
              <p class="text-xl font-black text-rose-600">{{ stats.admin_review_required || 0 }}</p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-rose-700 font-bold">Ngoại lệ</p>
        </div>

        <!-- 4. Đã giải quyết -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'resolved' ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Xem các báo cáo đã giải quyết"
          @click="selectedStatus = 'resolved'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
              <CheckCircle class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Đã giải quyết</p>
              <p class="text-xl font-black text-emerald-600">{{ stats.resolved || 0 }}</p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-emerald-700 font-medium">Hoàn tất</p>
        </div>

        <!-- 5. Đã bỏ qua -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'dismissed' ? 'border-slate-400 bg-slate-100 ring-2 ring-slate-400/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Xem các báo cáo đã bỏ qua"
          @click="selectedStatus = 'dismissed'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-200 text-slate-600">
              <XCircle class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Đã bỏ qua</p>
              <p class="text-xl font-black text-slate-800">{{ stats.dismissed || 0 }}</p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-slate-500 font-medium">Không hợp lệ</p>
        </div>
      </div>
    </div>

    <!-- TABS & CONTROLS BAR -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-white p-4 rounded-2xl shadow-xs">
      <!-- Status Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto text-xs font-bold">
        <button
          v-for="tab in statusTabs"
          :key="tab.key"
          type="button"
          class="rounded-xl px-3.5 py-2 transition cursor-pointer flex items-center gap-2 whitespace-nowrap"
          :class="selectedStatus === tab.key
            ? 'bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs'
            : 'text-slate-600 hover:bg-slate-100'"
          @click="selectedStatus = tab.key"
        >
          <span>{{ tab.label }}</span>
          <span
            v-if="tab.count > 0"
            class="rounded-full px-2 py-0.5 text-[10px]"
            :class="tab.key === 'pending' ? 'bg-amber-500 text-white' : (tab.key === 'author_updated' ? 'bg-blue-600 text-white' : (tab.key === 'admin_review_required' ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-700'))"
          >
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- View Mode & Search -->
      <div class="flex flex-wrap items-center gap-3">
        <!-- View Mode Switcher -->
        <div class="flex items-center rounded-xl bg-slate-100 p-1 text-xs font-bold">
          <button
            type="button"
            class="rounded-lg px-3 py-1.5 transition cursor-pointer"
            :class="viewMode === 'grouped' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
            @click="viewMode = 'grouped'"
          >
            Theo câu hỏi (Nhóm)
          </button>
          <button
            type="button"
            class="rounded-lg px-3 py-1.5 transition cursor-pointer"
            :class="viewMode === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
            @click="viewMode = 'all'"
          >
            Tất cả ticket (Log)
          </button>
        </div>

        <!-- Search input -->
        <div class="relative min-w-[220px]">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Tìm câu hỏi, lý do, người báo..."
            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-8 pr-3 py-1.5 text-xs font-medium text-slate-900 outline-none focus:border-rose-500 focus:bg-white"
          />
        </div>
      </div>
    </div>

    <!-- =========================================================================
         VIEW 1: GROUPED BY QUESTION (PRIMARY MONITORING VIEW)
    ========================================================================== -->
    <div v-if="viewMode === 'grouped'" class="space-y-4">
      <div v-if="isLoading" class="p-12 text-center text-xs text-slate-400 rounded-2xl bg-white border border-slate-200">
        <RefreshCw class="mx-auto mb-2 h-6 w-6 animate-spin text-rose-600" />
        <span>Đang tổng hợp các nhóm báo cáo vi phạm...</span>
      </div>

      <div v-else-if="filteredGroupedQuestions.length === 0" class="p-12 text-center text-xs text-slate-400 rounded-2xl bg-white border border-slate-200">
        <Inbox class="mx-auto mb-2 h-8 w-8 opacity-40 text-slate-400" />
        <p class="font-semibold text-slate-700">Không có câu hỏi nào có báo cáo phù hợp bộ lọc.</p>
      </div>

      <div
        v-for="group in filteredGroupedQuestions"
        :key="group.question_id"
        class="rounded-2xl border bg-white p-5 shadow-sm hover:shadow-md transition space-y-4"
        :class="group.hasAdminReviewRequired ? 'border-rose-300' : (group.hasAuthorUpdated ? 'border-blue-200 bg-blue-50/20' : (group.hasPending ? 'border-amber-200' : 'border-slate-200'))"
      >
        <!-- Group Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-slate-100 pb-3">
          <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-800">
                Câu hỏi #{{ group.question_id }}
              </span>
              <span v-if="group.question?.subject?.name" class="rounded bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-700">
                {{ group.question.subject.name }}
              </span>
              <span v-if="group.question?.grade?.name" class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                {{ group.question.grade.name }}
              </span>
              <span
                class="rounded px-2 py-0.5 text-xs font-bold"
                :class="group.question?.is_public ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600'"
              >
                {{ group.question?.is_public ? 'Công khai' : 'Riêng tư' }}
              </span>

              <!-- Status Badges -->
              <span
                v-if="group.hasAuthorUpdated"
                class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-black text-blue-800 border border-blue-200"
              >
                🔵 CHỦ CÂU HỎI ĐÃ CẬP NHẬT
              </span>
              <span
                v-else-if="group.hasAdminReviewRequired"
                class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-black text-rose-800 border border-rose-200"
              >
                🔴 CẦN ADMIN XỬ LÝ
              </span>
              <span
                v-else-if="group.hasPending"
                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-black text-amber-800 border border-amber-200"
              >
                🟡 CHỜ CHỦ CÂU HỎI XỬ LÝ
              </span>
              <span
                v-else-if="group.tickets.every(t => t.status === 'dismissed')"
                class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-black text-slate-700 border border-slate-200"
              >
                ⚪ ĐÃ BỎ QUA
              </span>
              <span
                v-else
                class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-black text-emerald-800 border border-emerald-200"
              >
                ✓ ĐÃ GIẢI QUYẾT
              </span>
            </div>

            <p class="text-sm font-bold text-slate-900 leading-relaxed">
              {{ group.question?.content || 'Nội dung câu hỏi không khả dụng' }}
            </p>
          </div>

          <!-- Group Action Buttons -->
          <div class="flex items-center gap-2 shrink-0">
            <!-- If author updated, direct CTA to Question Bank Review -->
            <router-link
              v-if="group.hasAuthorUpdated"
              :to="`/admin/question-bank?tab=reported&question_id=${group.question_id}`"
              class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#6D28D9] transition cursor-pointer shadow-xs"
              title="Chuyển sang Ngân hàng câu hỏi để xem đối chiếu Diff và Duyệt bản sửa đổi"
            >
              <ExternalLink class="h-3.5 w-3.5" />
              <span>Thẩm định Revision ↗</span>
            </router-link>

            <!-- Exception Moderation button if admin review required -->
            <button
              v-if="group.hasAdminReviewRequired"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-rose-700 transition cursor-pointer shadow-xs"
              @click="openModerationModal(group)"
            >
              <Shield class="h-3.5 w-3.5" />
              <span>Xử lý ngoại lệ ({{ group.tickets.length }})</span>
            </button>

            <!-- Standard view detail button -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-xs"
              @click="openModerationModal(group)"
            >
              <Eye class="h-3.5 w-3.5 text-slate-500" />
              <span>Xem chi tiết ({{ group.tickets.length }})</span>
            </button>
          </div>
        </div>

        <!-- Breakdown of Reasons & Reporters -->
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
          <!-- Reasons Breakdown Tags -->
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-slate-500 font-medium">Lý do báo cáo:</span>
            <span
              v-for="(count, reason) in group.reasonsCount"
              :key="reason"
              class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 font-bold text-rose-800 text-[11px]"
            >
              {{ count }} {{ reason }}
            </span>
          </div>

          <!-- Timeline -->
          <div class="text-slate-400 text-[11px]">
            Báo cáo gần nhất: <strong>{{ formatDate(group.latestReportAt) }}</strong> (Tổng {{ group.tickets.length }} lượt phản ánh)
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================================
         VIEW 2: RAW AUDIT LOG TABLE (ALL INDIVIDUAL TICKETS)
    ========================================================================== -->
    <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <th class="w-32 p-3.5 text-center">Trạng thái</th>
              <th class="p-3.5">Câu hỏi</th>
              <th class="p-3.5">Lý do & Mô tả</th>
              <th class="p-3.5">Người báo cáo</th>
              <th class="p-3.5">Thời gian</th>
              <th class="w-36 p-3.5 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="filteredReports.length === 0">
              <td colspan="6" class="p-12 text-center text-slate-400 font-medium">
                Không tìm thấy báo cáo nào phù hợp.
              </td>
            </tr>

            <tr
              v-for="report in filteredReports"
              :key="report.id"
              class="hover:bg-slate-50/80 transition"
            >
              <!-- Status -->
              <td class="p-3.5 text-center align-top">
                <StatusBadge :value="report.status" />
              </td>

              <!-- Question -->
              <td class="p-3.5 align-top max-w-sm">
                <div class="space-y-1">
                  <span class="font-bold text-slate-900">#{{ report.question_id }}</span>
                  <p class="text-slate-600 line-clamp-2 italic">
                    "{{ report.question?.content || 'N/A' }}"
                  </p>
                </div>
              </td>

              <!-- Reason -->
              <td class="p-3.5 align-top max-w-xs space-y-1">
                <span class="inline-block rounded bg-rose-50 px-2 py-0.5 font-bold text-rose-700 border border-rose-200 text-[10px]">
                  {{ report.reason }}
                </span>
                <p v-if="report.description" class="text-slate-600 text-[11px] leading-relaxed">
                  {{ report.description }}
                </p>
              </td>

              <!-- Reporter -->
              <td class="p-3.5 align-top">
                <div class="space-y-0.5">
                  <p class="font-bold text-slate-900 truncate">{{ report.user?.name || 'Người dùng' }}</p>
                  <p class="text-[10px] text-slate-400 truncate">{{ report.user?.email }}</p>
                </div>
              </td>

              <!-- Time -->
              <td class="p-3.5 align-top text-slate-400 text-[11px] whitespace-nowrap">
                {{ formatDate(report.created_at) }}
              </td>

              <!-- Actions -->
              <td class="p-3.5 align-top text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <router-link
                    v-if="report.status === 'author_updated'"
                    :to="`/admin/question-bank?tab=reported&question_id=${report.question_id}`"
                    class="rounded-lg bg-purple-50 text-[#7C3AED] px-2.5 py-1 text-[11px] font-bold hover:bg-purple-100 transition"
                    title="Thẩm định Revision tại Ngân hàng"
                  >
                    Duyệt ↗
                  </router-link>

                  <button
                    type="button"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-2xs"
                    @click="openModalFromTicket(report)"
                  >
                    Chi tiết
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- =========================================================================
         MODERATION & REPORT DETAIL WORKSPACE MODAL (2-COLUMN LAYOUT)
    ========================================================================== -->
    <div
      v-if="isModerationModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 sm:p-6 overflow-y-auto backdrop-blur-xs"
      @click.self="closeModerationModal"
    >
      <div class="relative w-full max-w-5xl rounded-3xl bg-white shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="grid h-9 w-9 place-items-center rounded-xl bg-rose-100 text-rose-600">
              <Flag class="h-5 w-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-base font-black text-slate-900">
                  Chi tiết Báo cáo & Câu hỏi #{{ activeGroup?.question_id }}
                </h2>
                <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800">
                  {{ activeGroup?.tickets?.length || 0 }} lượt phản ánh
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Môn học: <strong>{{ activeGroup?.question?.subject?.name || 'Chung' }}</strong> •
                Khối: <strong>{{ activeGroup?.question?.grade?.name || 'Chung' }}</strong>
              </p>
            </div>
          </div>

          <button
            type="button"
            class="rounded-xl p-2 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition cursor-pointer"
            @click="closeModerationModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Modal Body (2-Column Grid) -->
        <div class="flex-1 overflow-y-auto p-6 bg-[#F8FAFC]">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- LEFT COLUMN: ALL REPORT TICKETS -->
            <div class="space-y-4">
              <h3 class="text-xs font-black uppercase tracking-wider text-rose-900 flex items-center gap-2">
                <AlertTriangle class="h-4 w-4 text-rose-600" />
                <span>Danh sách phản ánh từ người học ({{ activeGroup?.tickets?.length }}):</span>
              </h3>

              <div class="space-y-3">
                <div
                  v-for="t in activeGroup?.tickets"
                  :key="t.id"
                  class="rounded-2xl border border-slate-200 bg-white p-4 space-y-2.5 shadow-2xs"
                >
                  <div class="flex items-center justify-between">
                    <span class="rounded bg-rose-100 text-rose-800 font-bold px-2 py-0.5 text-[11px]">
                      {{ t.reason }}
                    </span>
                    <span class="text-[10px] text-slate-400">{{ formatDate(t.created_at) }}</span>
                  </div>

                  <p v-if="t.description" class="text-xs text-slate-700 italic leading-relaxed bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                    "{{ t.description }}"
                  </p>

                  <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                    <span>Người báo cáo: <strong>{{ t.user?.name || 'Người dùng' }}</strong> ({{ t.user?.email }})</span>
                    <StatusBadge :value="t.status" />
                  </div>
                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN: QUESTION DETAILS & ACTION BANNER -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
                  <HelpCircle class="h-4 w-4 text-[#7C3AED]" />
                  <span>Nội dung câu hỏi hiện tại:</span>
                </h3>

                <router-link
                  :to="`/admin/question-bank?search=${activeGroup?.question_id}`"
                  target="_blank"
                  class="text-[11px] font-bold text-[#7C3AED] hover:underline inline-flex items-center gap-1"
                >
                  <span>Mở trong Ngân hàng ↗</span>
                </router-link>
              </div>

              <!-- Question Card -->
              <div class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4 shadow-sm">
                <!-- Content -->
                <div class="space-y-1">
                  <span class="text-[10px] font-bold uppercase text-slate-400">Đề bài:</span>
                  <div class="text-sm font-bold text-slate-900 leading-relaxed bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    {{ activeGroup?.question?.content }}
                  </div>
                </div>

                <!-- Answers List -->
                <div class="space-y-2">
                  <span class="text-[10px] font-bold uppercase text-slate-400">Các phương án đáp án:</span>
                  <div class="space-y-1.5">
                    <div
                      v-for="ans in activeGroup?.question?.answers"
                      :key="ans.id || ans.key"
                      class="rounded-xl border p-2.5 text-xs font-semibold flex items-center justify-between"
                      :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700'"
                    >
                      <span>{{ ans.key || ans.answer_key }}. {{ ans.content || ans.text }}</span>
                      <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600">✓ Đáp án đúng</span>
                    </div>
                  </div>
                </div>

                <!-- Related Quizzes -->
                <div v-if="activeGroup?.question?.quizzes?.length || activeGroup?.question?.quiz" class="space-y-1.5 pt-2 border-t border-slate-100">
                  <span class="text-[10px] font-bold uppercase text-slate-400">Các bài Quiz đang sử dụng câu hỏi này:</span>
                  <div class="flex flex-wrap gap-1.5">
                    <router-link
                      v-for="qz in (activeGroup?.question?.quizzes || (activeGroup?.question?.quiz ? [activeGroup.question.quiz] : []))"
                      :key="qz.id"
                      :to="`/admin/quizzes/${qz.id}`"
                      target="_blank"
                      class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-purple-50 px-2.5 py-1 text-[11px] font-bold text-[#7C3AED] hover:bg-purple-100 transition"
                      title="Mở chi tiết bài Quiz"
                    >
                      <Package class="h-3.5 w-3.5" />
                      <span>{{ qz.title }}</span>
                      <span class="text-[9px] font-normal text-slate-500">({{ qz.is_public ? 'Công khai' : 'Riêng tư' }})</span>
                    </router-link>
                  </div>
                </div>

                <!-- Metadata info -->
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 text-[11px]">
                  <span class="text-slate-500">Tác giả: <strong>{{ activeGroup?.question?.user?.name || 'Vô danh' }}</strong></span>
                  <span>•</span>
                  <span class="text-slate-500">Trạng thái duyệt: <strong>{{ activeGroup?.question?.bank_submission_status || 'none' }}</strong></span>
                </div>
              </div>

              <!-- AUTHOR UPDATED CTA BANNER (Primary workflow) -->
              <div
                v-if="activeGroup?.hasAuthorUpdated || activeGroup?.question?.bank_submission_status === 'pending'"
                class="rounded-2xl border border-blue-200 bg-blue-50/80 p-4 space-y-2.5 shadow-xs"
              >
                <div class="flex items-center gap-2 text-blue-900 font-bold text-xs">
                  <CheckCircle2 class="h-4 w-4 text-blue-600" />
                  <span>Chủ câu hỏi đã cập nhật & gửi duyệt Revision mới</span>
                </div>
                <p class="text-xs text-blue-800 leading-relaxed">
                  Tác giả câu hỏi đã tiếp thu phản ánh và chỉnh sửa nội dung. Bạn có thể chuyển sang Ngân hàng câu hỏi để đối chiếu Diff. Khi bạn <strong>Phê duyệt (Approve)</strong> Revision, hệ thống sẽ <strong>tự động giải quyết toàn bộ các báo cáo</strong> liên quan!
                </p>
                <router-link
                  :to="`/admin/question-bank?tab=reported&question_id=${activeGroup.question_id}`"
                  class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white hover:bg-[#6D28D9] transition cursor-pointer shadow-xs"
                >
                  <ExternalLink class="h-3.5 w-3.5" />
                  <span>Chuyển sang Thẩm định Revision tại Ngân hàng câu hỏi ↗</span>
                </router-link>
              </div>

              <!-- EXCEPTION ACTION FORM (For Admin Review Required / Manual Interventions) -->
              <div
                v-if="activeGroup?.hasPending || activeGroup?.hasAdminReviewRequired"
                class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3 shadow-xs"
              >
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                  <Shield class="h-4 w-4 text-rose-600" />
                  <span>Xử lý ngoại lệ / Can thiệp thủ công:</span>
                </h4>

                <div class="space-y-1">
                  <label class="text-[11px] font-bold text-slate-600 block">Ghi chú quản trị viên (tùy chọn):</label>
                  <textarea
                    v-model="resolutionNote"
                    rows="2"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs outline-none focus:border-rose-500 focus:bg-white resize-none font-medium text-slate-900"
                    placeholder="Nhập ghi chú hoặc lý do xử lý ngoại lệ..."
                  ></textarea>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-1">
                  <!-- Action 1: Mark as Admin Review Required -->
                  <button
                    v-if="!activeGroup?.hasAdminReviewRequired"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition cursor-pointer shadow-2xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('admin_review_required', 'keep')"
                  >
                    <AlertTriangle class="h-3.5 w-3.5" />
                    <span>Đánh dấu Cần can thiệp</span>
                  </button>

                  <!-- Action 2: Resolve & Hide -->
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 transition cursor-pointer shadow-2xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('resolved', 'hide')"
                  >
                    <Lock class="h-3.5 w-3.5" />
                    <span>Ẩn khỏi Ngân hàng & Resolve</span>
                  </button>

                  <!-- Action 3: Force Resolve -->
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-2xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('resolved', 'keep')"
                  >
                    <Check class="h-3.5 w-3.5" />
                    <span>Giải quyết thủ công</span>
                  </button>

                  <!-- Action 4: Dismiss -->
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer shadow-2xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('dismissed', 'keep')"
                  >
                    <X class="h-3.5 w-3.5" />
                    <span>Bỏ qua báo cáo (Spam)</span>
                  </button>
                </div>
              </div>

              <!-- Already Resolved Banner -->
              <div
                v-else-if="!activeGroup?.hasAuthorUpdated"
                class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 space-y-1 shadow-xs"
              >
                <div class="flex items-center gap-2 text-emerald-800 font-bold text-xs">
                  <CheckCircle class="h-4 w-4 text-emerald-600" />
                  <span>Toàn bộ báo cáo của câu hỏi này đã được xử lý hoàn tất</span>
                </div>
                <p class="text-[11px] text-emerald-700 font-medium">
                  Không còn ticket nào ở trạng thái chờ xử lý.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end border-t border-slate-100 bg-white px-6 py-4 shrink-0">
          <button
            type="button"
            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer"
            @click="closeModerationModal"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  AlertTriangle,
  Check,
  CheckCircle,
  CheckCircle2,
  ChevronRight,
  Clock,
  ExternalLink,
  Eye,
  Flag,
  HelpCircle,
  Inbox,
  Lock,
  Package,
  RefreshCw,
  Search,
  Shield,
  X,
  XCircle,
} from 'lucide-vue-next'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { reportApi } from '@/services/api'

const route = useRoute()
const showToast = inject('showToast')

const reports = ref([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('all')
const viewMode = ref('grouped')

const stats = reactive({
  total: 0,
  pending: 0,
  author_updated: 0,
  admin_review_required: 0,
  resolved: 0,
  dismissed: 0,
  questions_count: 0,
})

// Moderation Modal
const isModerationModalOpen = ref(false)
const activeGroup = ref(null)
const resolutionNote = ref('')
const isSubmittingResolution = ref(false)

const statusTabs = computed(() => [
  { key: 'all', label: 'Tất cả', count: stats.total },
  { key: 'admin_review_required', label: 'Cần Admin', count: stats.admin_review_required },
  { key: 'author_updated', label: 'Đã cập nhật', count: stats.author_updated },
  { key: 'pending', label: 'Chờ chủ xử lý', count: stats.pending },
  { key: 'resolved', label: 'Đã giải quyết', count: stats.resolved },
  { key: 'dismissed', label: 'Đã bỏ qua', count: stats.dismissed },
])

const filteredReports = computed(() => {
  return reports.value.filter((rep) => {
    const matchStatus = selectedStatus.value === 'all' || rep.status === selectedStatus.value
    if (!matchStatus) return false

    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase().trim()
    const content = (rep.question?.content || '').toLowerCase()
    const reason = (rep.reason || '').toLowerCase()
    const reporter = (rep.user?.name || '').toLowerCase()
    const email = (rep.user?.email || '').toLowerCase()
    const qId = String(rep.question_id || '')

    return content.includes(q) || reason.includes(q) || reporter.includes(q) || email.includes(q) || qId.includes(q)
  })
})

const groupedQuestions = computed(() => {
  const map = new Map()

  reports.value.forEach((ticket) => {
    const qId = ticket.question_id
    if (!map.has(qId)) {
      map.set(qId, {
        question_id: qId,
        question: ticket.question,
        tickets: [],
        reasonsCount: {},
        hasPending: false,
        hasAuthorUpdated: false,
        hasAdminReviewRequired: false,
        latestReportAt: ticket.created_at,
      })
    }

    const entry = map.get(qId)
    entry.tickets.push(ticket)

    // Count reason breakdown
    entry.reasonsCount[ticket.reason] = (entry.reasonsCount[ticket.reason] || 0) + 1

    if (ticket.status === 'pending') {
      entry.hasPending = true
    } else if (ticket.status === 'author_updated') {
      entry.hasAuthorUpdated = true
    } else if (ticket.status === 'admin_review_required') {
      entry.hasAdminReviewRequired = true
    }

    if (new Date(ticket.created_at) > new Date(entry.latestReportAt)) {
      entry.latestReportAt = ticket.created_at
    }
  })

  const result = Array.from(map.values())
  result.sort((a, b) => {
    if (a.hasAdminReviewRequired && !b.hasAdminReviewRequired) return -1
    if (!a.hasAdminReviewRequired && b.hasAdminReviewRequired) return 1
    if (a.hasAuthorUpdated && !b.hasAuthorUpdated) return -1
    if (!a.hasAuthorUpdated && b.hasAuthorUpdated) return 1
    if (a.hasPending && !b.hasPending) return -1
    if (!a.hasPending && b.hasPending) return 1
    return new Date(b.latestReportAt) - new Date(a.latestReportAt)
  })
  return result
})

const filteredGroupedQuestions = computed(() => {
  return groupedQuestions.value.filter((g) => {
    // Status filter
    if (selectedStatus.value === 'pending' && !g.hasPending) return false
    if (selectedStatus.value === 'author_updated' && !g.hasAuthorUpdated) return false
    if (selectedStatus.value === 'admin_review_required' && !g.hasAdminReviewRequired) return false
    if (selectedStatus.value === 'resolved' && g.tickets.some(t => ['pending', 'author_updated', 'admin_review_required'].includes(t.status))) return false
    if (selectedStatus.value === 'dismissed' && g.tickets.some(t => t.status !== 'dismissed')) return false

    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase().trim()
    const content = (g.question?.content || '').toLowerCase()
    const qId = String(g.question_id)
    const reasons = Object.keys(g.reasonsCount).join(' ').toLowerCase()

    return content.includes(q) || qId.includes(q) || reasons.includes(q)
  })
})

const fetchReports = async () => {
  isLoading.value = true
  try {
    const res = await reportApi.listAdmin()
    reports.value = res.items || []
    if (res.stats) {
      stats.total = res.stats.total || 0
      stats.pending = res.stats.pending || 0
      stats.author_updated = res.stats.author_updated || 0
      stats.admin_review_required = res.stats.admin_review_required || 0
      stats.resolved = res.stats.resolved || 0
      stats.dismissed = res.stats.dismissed || 0
      stats.questions_count = res.stats.questions_count || 0
    }

    // If query param question_id is present, auto-open modal for that question
    if (route.query.question_id) {
      const targetQId = Number(route.query.question_id)
      const targetGroup = groupedQuestions.value.find(g => g.question_id === targetQId)
      if (targetGroup) {
        openModerationModal(targetGroup)
      }
    }
  } catch (err) {
    console.error('Lỗi khi tải báo cáo vi phạm:', err)
    if (showToast) showToast(`Không thể tải dữ liệu báo cáo: ${err.message}`, 'error')
  } finally {
    isLoading.value = false
  }
}

const openModerationModal = (group) => {
  activeGroup.value = group
  resolutionNote.value = ''
  isModerationModalOpen.value = true
}

const openModalFromTicket = (ticket) => {
  const targetGroup = groupedQuestions.value.find(g => g.question_id === ticket.question_id)
  if (targetGroup) {
    openModerationModal(targetGroup)
  }
}

const closeModerationModal = () => {
  isModerationModalOpen.value = false
  activeGroup.value = null
  resolutionNote.value = ''
}

const executeGroupResolution = async (status, action) => {
  if (!activeGroup.value?.question_id) return
  isSubmittingResolution.value = true

  try {
    const res = await reportApi.resolveQuestionReports({
      question_id: activeGroup.value.question_id,
      status,
      action,
      admin_note: resolutionNote.value.trim() || undefined,
    })

    if (showToast) showToast(res.message || 'Xử lý báo cáo thành công!', 'success')
    closeModerationModal()
    fetchReports()
  } catch (err) {
    console.error('Lỗi khi giải quyết báo cáo:', err)
    if (showToast) showToast(`Xử lý thất bại: ${err.message}`, 'error')
  } finally {
    isSubmittingResolution.value = false
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

onMounted(() => {
  if (route.query.status) {
    selectedStatus.value = route.query.status
  }
  fetchReports()
})
</script>