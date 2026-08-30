<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">Quản lý Báo cáo & Hàng đợi Ngoại lệ</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-sm">
            <ShieldAlert class="h-6 w-6" />
          </div>
          <div>
            <div class="flex items-center gap-2.5">
              <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Giám Sát Báo Cáo
              </h1>
              <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-black text-rose-700 border border-rose-200">
                Exception Queue
              </span>
            </div>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
              Hệ thống tự động hóa xử lý các báo cáo thông thường và tự động gỡ câu hỏi sau 7 ngày. Quản trị viên chỉ cần xử lý các trường hợp ngoại lệ.
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <router-link
            to="/admin/question-bank?tab=reported"
            class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#6D28D9] transition cursor-pointer"
          >
            <HelpCircle class="h-4 w-4" />
            <span>Mở Ngân hàng Ưu tiên ↗</span>
          </router-link>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300 shadow-sm cursor-pointer"
            @click="fetchReports"
          >
            <RefreshCw class="h-4 w-4 text-slate-500" :class="{ 'animate-spin': isLoading }" />
            
          </button>
        </div>
      </div>

      <!-- KPI Stats Cards (Clickable) -->
      <div class="mt-6 grid grid-cols-2 gap-3 xl:grid-cols-5">
        <!-- 1. CẦN ADMIN XỬ LÝ (Exception Queue - Priority #1) -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'needs_admin_review' || selectedStatus === 'admin_review_required' ? 'border-rose-500 bg-rose-50/80 ring-2 ring-rose-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Trường hợp ngoại lệ cần Admin can thiệp xử lý"
          @click="selectedStatus = 'needs_admin_review'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
              <AlertTriangle class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Cần Admin xử lý</p>
              <p class="text-xl font-black text-rose-600">
                {{ stats.admin_review_required_cases || stats.exception_cases_count || stats.needs_admin_review || 0 }}
                <span class="text-[11px] font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-rose-700 font-bold">
            {{ stats.admin_review_required_tickets || 0 }} lượt báo cáo
          </p>
        </div>

        <!-- 2. TỰ ĐỘNG GIẢI QUYẾT (Auto Resolved) -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'auto_resolved' ? 'border-teal-500 bg-teal-50/60 ring-2 ring-teal-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Các case đã được hệ thống tự động giải quyết khi tác giả sửa hợp lệ"
          @click="selectedStatus = 'auto_resolved'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-100 text-teal-600">
              <CheckCircle2 class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Tự động duyệt</p>
              <p class="text-xl font-black text-teal-600">
                {{ stats.auto_resolved_cases || stats.resolved || 0 }}
                <span class="text-[11px] font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-teal-700 font-bold">
            {{ stats.auto_resolved_tickets || stats.resolved_tickets || 0 }} lượt báo cáo
          </p>
        </div>

        <!-- 3. TỰ ĐỘNG GỠ (Auto Private 7 ngày) -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'auto_privatized' ? 'border-orange-500 bg-orange-50/60 ring-2 ring-orange-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Các câu hỏi bị hệ thống tự động gỡ công khai sau 7 ngày không sửa"
          @click="selectedStatus = 'auto_privatized'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
              <Lock class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Tự động gỡ (Day 7)</p>
              <p class="text-xl font-black text-orange-600">
                {{ stats.auto_privatized_cases || stats.auto_privatized || 0 }}
                <span class="text-[11px] font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-orange-700 font-bold">Riêng tư</p>
        </div>

        <!-- 4. ĐÃ BỎ QUA (Dismissed) -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'dismissed' ? 'border-slate-400 bg-slate-100 ring-2 ring-slate-400/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Báo cáo đã được bác bỏ hoặc không có vi phạm"
          @click="selectedStatus = 'dismissed'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-200 text-slate-600">
              <XCircle class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Đã bỏ qua</p>
              <p class="text-xl font-black text-slate-800">
                {{ stats.dismissed_cases || stats.dismissed || 0 }}
                <span class="text-[11px] font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-slate-500 font-medium">Báo cáo sai / Spam</p>
        </div>

        <!-- 5. TẤT CẢ (Audit Log) -->
        <div
          class="rounded-xl border p-3.5 transition cursor-pointer shadow-xs"
          :class="selectedStatus === 'all' ? 'border-purple-500 bg-purple-50/60 ring-2 ring-purple-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Xem toàn bộ lịch sử báo cáo"
          @click="selectedStatus = 'all'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
              <Flag class="h-4 w-4" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Tổng Case</p>
              <p class="text-xl font-black text-purple-700">
                {{ stats.total_cases || stats.total || 0 }}
                <span class="text-[11px] font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-1.5 text-[11px] text-purple-700 font-medium">
            {{ stats.total || 0 }} lượt báo cáo
          </p>
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
          :class="isTabActive(tab.key)
            ? 'bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs'
            : 'text-slate-600 hover:bg-slate-100'"
          @click="selectedStatus = tab.key"
        >
          <span>{{ tab.label }}</span>
          <span
            v-if="tab.count > 0"
            class="rounded-full px-2 py-0.5 text-[10px]"
            :class="tab.key === 'needs_admin_review' || tab.key === 'admin_review_required' ? 'bg-rose-600 text-white' : (tab.key === 'auto_privatized' ? 'bg-orange-500 text-white' : (tab.key === 'auto_resolved' ? 'bg-teal-600 text-white' : 'bg-slate-200 text-slate-700'))"
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
            Theo Case câu hỏi
          </button>
          <button
            type="button"
            class="rounded-lg px-3 py-1.5 transition cursor-pointer"
            :class="viewMode === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
            @click="viewMode = 'all'"
          >
            Tất cả Ticket (Log)
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
         VIEW 1: GROUPED BY QUESTION / CASE (PRIMARY EXCEPTION WORKSPACE)
    ========================================================================== -->
    <div v-if="viewMode === 'grouped'" class="space-y-4">
      <div v-if="isLoading" class="p-12 text-center text-xs text-slate-400 rounded-2xl bg-white border border-slate-200">
        <RefreshCw class="mx-auto mb-2 h-6 w-6 animate-spin text-rose-600" />
        <span>Đang tổng hợp danh sách các Case ngoại lệ...</span>
      </div>

      <div v-else-if="filteredGroupedQuestions.length === 0" class="p-12 text-center text-xs text-slate-400 rounded-2xl bg-white border border-slate-200 space-y-2">
        <CheckCircle class="mx-auto h-10 w-10 text-emerald-500 opacity-80" />
        <p class="font-bold text-slate-700 text-sm">
          {{ selectedStatus === 'needs_admin_review' || selectedStatus === 'admin_review_required' ? 'Tuyệt vời! Không có câu hỏi nào cần Admin can thiệp lúc này.' : 'Không tìm thấy câu hỏi nào phù hợp bộ lọc.' }}
        </p>
        <p class="text-slate-500 text-xs">
          Mọi báo cáo thông thường đã được điều phối tự động tới Tác giả hoặc đã xử lý hoàn tất.
        </p>
      </div>

      <div
        v-for="group in filteredGroupedQuestions"
        :key="group.question_id"
        class="rounded-2xl border bg-white p-5 shadow-sm hover:shadow-md transition space-y-4"
        :class="group.hasAdminReviewRequired ? 'border-rose-300 ring-1 ring-rose-200' : (group.isAutoPrivatized ? 'border-orange-200 bg-orange-50/10' : (group.hasAuthorUpdated ? 'border-blue-200 bg-blue-50/10' : 'border-slate-200'))"
      >
        <!-- Group Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-slate-100 pb-3">
          <div class="space-y-1.5 max-w-4xl">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-800">
                Case Câu hỏi #{{ group.question_id }}
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

              <!-- Primary Status Badges -->
              <span
                v-if="group.hasAdminReviewRequired"
                class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-black text-rose-800 border border-rose-200 flex items-center gap-1"
              >
                <AlertTriangle class="h-3 w-3" />
                <span>CẦN ADMIN XỬ LÝ (NGOẠI LỆ)</span>
              </span>
              <span
                v-else-if="group.isAutoPrivatized"
                class="rounded-full bg-orange-100 px-2.5 py-0.5 text-[11px] font-black text-orange-800 border border-orange-200 flex items-center gap-1"
              >
                <Lock class="h-3 w-3" />
                <span>ĐÃ TỰ ĐỘNG GỠ (DAY 7)</span>
              </span>
              <span
                v-else-if="group.hasAuthorUpdated"
                class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-black text-blue-800 border border-blue-200"
              >
                🔵 TÁC GIẢ ĐÃ SỬA
              </span>
              <span
                v-else-if="group.hasPending"
                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-black text-amber-800 border border-amber-200"
              >
                🟡 CHỜ TÁC GIẢ SỬA
              </span>
              <span
                v-else-if="group.tickets.every(t => t.status === 'dismissed')"
                class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-black text-slate-700 border border-slate-200"
              >
                ⚪ ĐÃ BỎ QUA
              </span>
              <span
                v-else
                class="rounded-full bg-teal-100 px-2.5 py-0.5 text-[11px] font-black text-teal-800 border border-teal-200"
              >
                ✓ ĐÃ GIẢI QUYẾT
              </span>
            </div>

            <!-- Question Content -->
            <p class="text-sm font-bold text-slate-900 leading-relaxed pt-1">
              {{ group.question?.content || 'Nội dung câu hỏi không khả dụng' }}
            </p>
          </div>

          <!-- Group Action Buttons (Streamlined) -->
          <div class="flex items-center gap-2 shrink-0">
            <!-- Exception Action Button -->
            <button
              v-if="group.hasAdminReviewRequired || group.hasPending"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 transition cursor-pointer shadow-xs"
              @click="openModerationModal(group)"
            >
              <Shield class="h-3.5 w-3.5" />
              <span>Xử lý Case ({{ group.tickets.length }})</span>
            </button>

            <!-- Quick View / Detail Button -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-xs"
              @click="openModerationModal(group)"
            >
              <Eye class="h-3.5 w-3.5 text-slate-500" />
              <span>Xem chi tiết</span>
            </button>
          </div>
        </div>

        <!-- Diagnostic & Reasons Summary -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
          <!-- Reasons Tags -->
          <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-slate-500 font-medium">Lý do báo cáo:</span>
            <span
              v-for="(count, reason) in group.reasonsCount"
              :key="reason"
              class="rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 font-bold text-rose-800 text-[11px]"
            >
              {{ count }}× {{ reason }}
            </span>
          </div>

          <!-- Timeline & Author Info -->
          <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2 text-slate-500 text-[11px]">
            <span>Tác giả: <strong>{{ group.question?.user?.name || 'Chưa rõ' }}</strong></span>
            <span>•</span>
            <span>Báo cáo gần nhất: <strong>{{ formatDate(group.latestReportAt) }}</strong></span>
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
              <th class="w-36 p-3.5 text-center">Trạng thái</th>
              <th class="p-3.5">Câu hỏi</th>
              <th class="p-3.5">Lý do & Mô tả</th>
              <th class="p-3.5">Người báo cáo</th>
              <th class="p-3.5">Thời gian & Vòng đời</th>
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

              <!-- Lifecycle Markers & Time -->
              <td class="p-3.5 align-top text-slate-500 text-[11px] space-y-1">
                <div>{{ formatDate(report.created_at) }}</div>
                <div v-if="report.auto_privatized_at" class="text-orange-600 font-bold text-[10px]">
                  🔒 Auto-Private: {{ formatDate(report.auto_privatized_at) }}
                </div>
                <div v-else-if="report.warning_sent_at" class="text-amber-600 text-[10px]">
                  ⚠️ Warning: {{ formatDate(report.warning_sent_at) }}
                </div>
                <div v-else-if="report.reminder_sent_at" class="text-blue-600 text-[10px]">
                  ⏰ Reminder: {{ formatDate(report.reminder_sent_at) }}
                </div>
              </td>

              <!-- Actions -->
              <td class="p-3.5 align-top text-right">
                <button
                  type="button"
                  class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-2xs"
                  @click="openModalFromTicket(report)"
                >
                  Xử lý Case
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- =========================================================================
         CASE RESOLUTION & MODERATION WORKSPACE MODAL
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
            <div
              class="grid h-9 w-9 place-items-center rounded-xl"
              :class="activeGroupNeedsAdminAction ? 'bg-rose-100 text-rose-600' : (activeGroupResolutionType === 'auto_privatized' ? 'bg-orange-100 text-orange-600' : 'bg-emerald-100 text-emerald-600')"
            >
              <ShieldAlert v-if="activeGroupNeedsAdminAction" class="h-5 w-5" />
              <Lock v-else-if="activeGroupResolutionType === 'auto_privatized'" class="h-5 w-5" />
              <CheckCircle2 v-else class="h-5 w-5" />
            </div>
            <div>
              <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span>{{ activeGroupNeedsAdminAction ? 'Xử lý Case Báo cáo Câu hỏi' : 'Chi tiết Case Báo cáo Câu hỏi' }}</span>
                <span class="rounded-md bg-slate-200 px-2 py-0.5 font-mono text-xs font-bold text-slate-800">
                  #{{ activeGroup?.question_id }}
                </span>
              </h2>
              <p class="text-xs text-slate-500">
                {{ activeGroupNeedsAdminAction ? `Áp dụng quyết định quản trị viên cho ${activeGroup?.tickets?.length || 0} lượt báo cáo của câu hỏi này.` : `Case này đã được xử lý hoàn tất. Dưới đây là thông tin chi tiết và lịch sử phản ánh.` }}
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
        <div class="flex-1 overflow-y-auto p-6">
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- LEFT COLUMN: USER REPORTS & LIFECYCLE INFO -->
            <div class="space-y-4">
              <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <Flag class="h-4 w-4 text-rose-600" />
                <span>Các phản ánh từ người học ({{ activeGroup?.tickets?.length || 0 }} lượt):</span>
              </h3>

              <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
                <div
                  v-for="t in activeGroup?.tickets"
                  :key="t.id"
                  class="rounded-2xl border border-slate-200 bg-white p-4 space-y-2 shadow-xs"
                >
                  <div class="flex items-center justify-between">
                    <span class="rounded bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700 border border-rose-200">
                      {{ t.reason }}
                    </span>
                    <span class="text-[11px] text-slate-400">{{ formatDate(t.created_at) }}</span>
                  </div>

                  <p v-if="t.description" class="text-xs text-slate-700 italic leading-relaxed bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                    "{{ t.description }}"
                  </p>

                  <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                    <span>Người báo: <strong>{{ t.user?.name || 'Người dùng' }}</strong> ({{ t.user?.email }})</span>
                    <StatusBadge :value="t.status" />
                  </div>
                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN: QUESTION DETAILS & ACTIONS / RESOLUTION SUMMARY -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
                  <HelpCircle class="h-4 w-4 text-[#7C3AED]" />
                  <span>Nội dung câu hỏi:</span>
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
              <div class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3 shadow-xs text-xs">
                <!-- Content -->
                <div>
                  <span class="text-[10px] font-bold uppercase text-slate-400">Đề bài:</span>
                  <div class="font-bold text-slate-900 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-200 mt-1">
                    {{ activeGroup?.question?.content }}
                  </div>
                </div>

                <!-- Answers List -->
                <div class="space-y-1.5">
                  <span class="text-[10px] font-bold uppercase text-slate-400">Các phương án đáp án:</span>
                  <div
                    v-for="ans in activeGroup?.question?.answers"
                    :key="ans.id || ans.key"
                    class="rounded-xl border p-2 text-xs font-semibold flex items-center justify-between"
                    :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700'"
                  >
                    <span>{{ ans.key || ans.answer_key }}. {{ ans.content || ans.text }}</span>
                    <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600">✓ Đáp án đúng</span>
                  </div>
                </div>

                <!-- Author & Question Visibility Info -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                  <span>Tác giả: <strong>{{ activeGroup?.question?.user?.name || 'Chưa rõ' }}</strong></span>
                  <div class="flex items-center gap-1.5">
                    <span>Trạng thái câu hỏi:</span>
                    <span
                      class="rounded-md px-2 py-0.5 font-bold text-[10px]"
                      :class="isQuestionDeleted ? 'bg-rose-100 text-rose-700' : (isQuestionPublic ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700')"
                    >
                      {{ isQuestionDeleted ? 'Đã xóa (Thùng rác)' : (isQuestionPublic ? 'Công khai' : 'Riêng tư') }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- LATEST REVISION / AUTO-REVIEW METADATA BADGE (IF AVAILABLE) -->
              <div
                v-if="activeGroup?.question?.latestReviewRequest?.snapshot_metadata?.auto_approved"
                class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-3.5 space-y-1 text-xs"
              >
                <div class="flex items-center gap-1.5 text-emerald-900 font-bold">
                  <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                  <span>Bản đính chính đã được Tự động duyệt (Revision #{{ activeGroup.question.latestReviewRequest.revision_number }})</span>
                </div>
                <p class="text-[11px] text-emerald-800 leading-relaxed">
                  Tác giả đã cập nhật nội dung đính chính và vượt qua toàn bộ quy tắc kiểm định an toàn tự động lúc {{ formatDate(activeGroup.question.latestReviewRequest.snapshot_metadata.auto_approved_at || activeGroup.question.latestReviewRequest.updated_at) }}.
                </p>
              </div>

              <div
                v-else-if="activeGroup?.question?.latestReviewRequest?.snapshot_metadata?.auto_review_failed"
                class="rounded-2xl border border-rose-200 bg-rose-50/60 p-3.5 space-y-1 text-xs"
              >
                <div class="flex items-center gap-1.5 text-rose-900 font-bold">
                  <AlertTriangle class="h-4 w-4 text-rose-600" />
                  <span>Auto Review không đạt — Cần Admin thẩm định (Revision #{{ activeGroup.question.latestReviewRequest.revision_number }})</span>
                </div>
                <p class="text-[11px] text-rose-800 leading-relaxed">
                  Lý do: {{ activeGroup.question.latestReviewRequest.snapshot_metadata.auto_review_reason || 'Nội dung đính chính vi phạm quy tắc cấu trúc hoặc danh mục nghiêm trọng.' }}
                </p>
              </div>

              <!-- =========================================================================
                   MODE 1: CASE ACTION MODE (CÒN CẦN ADMIN XỬ LÝ)
              ========================================================================== -->
              <div
                v-if="activeGroupNeedsAdminAction"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3 shadow-xs"
              >
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                  <Shield class="h-4 w-4 text-rose-600" />
                  <span>Quyết định xử lý Case:</span>
                </h4>

                <div class="space-y-1">
                  <label class="text-[11px] font-bold text-slate-600 block">Ghi chú quản trị viên (tùy chọn):</label>
                  <textarea
                    v-model="resolutionNote"
                    rows="2"
                    class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs outline-none focus:border-rose-500 resize-none font-medium text-slate-900"
                    placeholder="Nhập lý do hoặc ghi chú giải quyết case..."
                  ></textarea>
                </div>

                <!-- Alert if question is already deleted -->
                <div v-if="isQuestionDeleted" class="p-2.5 rounded-xl border border-rose-200 bg-rose-50 text-[11px] text-rose-800 font-semibold flex items-center gap-1.5">
                  <AlertTriangle class="h-3.5 w-3.5 shrink-0 text-rose-600" />
                  <span>Câu hỏi này hiện đã bị xóa trong Thùng rác. Bạn có thể bỏ qua báo cáo hoặc giải quyết case.</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                  <!-- Action 1: Resolve & Keep (Only if not deleted) -->
                  <button
                    v-if="!isQuestionDeleted"
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('resolved', 'keep')"
                  >
                    <Check class="h-3.5 w-3.5" />
                    <span>{{ isQuestionPublic ? 'Giải quyết & Giữ câu hỏi' : 'Giải quyết (Giữ riêng tư)' }}</span>
                  </button>

                  <!-- Action 2: Resolve & Hide from Bank (ONLY IF QUESTION IS PUBLIC AND NOT DELETED) -->
                  <button
                    v-if="isQuestionPublic && !isQuestionDeleted"
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100 transition cursor-pointer shadow-xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('resolved', 'hide')"
                  >
                    <Lock class="h-3.5 w-3.5" />
                    <span>Gỡ công khai & Resolve</span>
                  </button>

                  <!-- Action 3: Dismiss (Spam) -->
                  <button
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer shadow-xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('dismissed', 'keep')"
                  >
                    <X class="h-3.5 w-3.5" />
                    <span>Bỏ qua báo cáo (Spam)</span>
                  </button>

                  <!-- Action 4: Delete Question (Only if not deleted) -->
                  <button
                    v-if="!isQuestionDeleted"
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-rose-700 transition cursor-pointer shadow-xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('resolved', 'delete')"
                  >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>Xóa câu hỏi vi phạm</span>
                  </button>
                </div>
              </div>

              <!-- =========================================================================
                   MODE 2: CASE READ-ONLY MODE (CASE ĐÃ HOÀN TẤT)
              ========================================================================== -->
              <div v-else class="space-y-3">
                <!-- Read-only Summary 1: Auto Privatized (Day 7) -->
                <div
                  v-if="activeGroupResolutionType === 'auto_privatized'"
                  class="rounded-2xl border border-orange-200 bg-orange-50/60 p-4 space-y-2 text-xs"
                >
                  <div class="flex items-center gap-2 text-orange-950 font-black">
                    <Lock class="h-4 w-4 text-orange-600" />
                    <span>🔒 CASE ĐÃ TỰ ĐỘNG GỠ CÔNG KHAI (AUTO PRIVATE)</span>
                  </div>
                  <p class="text-slate-600 leading-relaxed text-[11px]">
                    Tác giả không đính chính sau 7 ngày kể từ khi nhận báo cáo vi phạm. Hệ thống đã tự động chuyển câu hỏi về trạng thái Riêng tư và ẩn khỏi Ngân hàng đề thi để đảm bảo chất lượng.
                  </p>
                  <div class="text-[11px] text-slate-500 pt-2 border-t border-orange-200/60 flex items-center justify-between">
                    <span>Trạng thái: <strong class="text-orange-700">Tự động gỡ (Day 7)</strong></span>
                    <span v-if="activeGroup.tickets[0]?.auto_privatized_at">Thời gian: <strong>{{ formatDate(activeGroup.tickets[0].auto_privatized_at) }}</strong></span>
                  </div>
                </div>

                <!-- Read-only Summary 2: Dismissed -->
                <div
                  v-else-if="activeGroupResolutionType === 'dismissed'"
                  class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-xs"
                >
                  <div class="flex items-center gap-2 text-slate-900 font-black">
                    <XCircle class="h-4 w-4 text-slate-500" />
                    <span>⚪ CASE ĐÃ BỎ QUA BÁO CÁO (DISMISSED)</span>
                  </div>
                  <p class="text-slate-600 leading-relaxed text-[11px]">
                    Toàn bộ báo cáo vi phạm của câu hỏi này đã được Quản trị viên xác định là không hợp lệ hoặc spam và được đóng lại.
                  </p>
                  <div class="text-[11px] text-slate-500 pt-2 border-t border-slate-200 flex items-center justify-between">
                    <span>Trạng thái: <strong class="text-slate-700">Đã bỏ qua</strong></span>
                    <span>Thời gian: <strong>{{ formatDate(activeGroup.tickets[0]?.updated_at) }}</strong></span>
                  </div>
                </div>

                <!-- Read-only Summary 3: Auto Resolved / Resolved -->
                <div
                  v-else
                  class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 space-y-2 text-xs"
                >
                  <div class="flex items-center gap-2 text-emerald-950 font-black">
                    <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                    <span>✓ CASE ĐÃ ĐƯỢC GIẢI QUYẾT</span>
                  </div>
                  <p class="text-slate-600 leading-relaxed text-[11px]">
                    {{ activeGroupResolutionType === 'auto_resolved' ? 'Nội dung đính chính của tác giả đã vượt qua kiểm định an toàn tự động (Auto Approve) và toàn bộ báo cáo liên quan đã được giải quyết.' : 'Toàn bộ phản ánh vi phạm đã được Quản trị viên xử lý và khép lại thành công.' }}
                  </p>
                  <div class="text-[11px] text-slate-500 pt-2 border-t border-emerald-200/60 flex items-center justify-between">
                    <span>Trạng thái: <strong class="text-emerald-700">Đã giải quyết</strong></span>
                    <span>Thời gian: <strong>{{ formatDate(activeGroup.tickets[0]?.updated_at) }}</strong></span>
                  </div>
                </div>
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
  Eye,
  Flag,
  HelpCircle,
  Lock,
  RefreshCw,
  Search,
  Shield,
  ShieldAlert,
  Trash2,
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
const selectedStatus = ref('needs_admin_review')
const viewMode = ref('grouped')

const stats = reactive({
  total: 0,
  total_cases: 0,
  needs_admin_review: 0,
  admin_review_required: 0,
  admin_review_required_cases: 0,
  admin_review_required_tickets: 0,
  author_updated: 0,
  author_updated_cases: 0,
  author_updated_tickets: 0,
  pending: 0,
  pending_cases: 0,
  pending_tickets: 0,
  auto_privatized: 0,
  auto_privatized_cases: 0,
  auto_privatized_tickets: 0,
  resolved: 0,
  resolved_cases: 0,
  resolved_tickets: 0,
  auto_resolved: 0,
  auto_resolved_cases: 0,
  auto_resolved_tickets: 0,
  dismissed: 0,
  dismissed_cases: 0,
  dismissed_tickets: 0,
  exception_cases_count: 0,
  questions_count: 0,
})

// Moderation Modal
const isModerationModalOpen = ref(false)
const activeGroup = ref(null)
const resolutionNote = ref('')
const isSubmittingResolution = ref(false)

const activeGroupNeedsAdminAction = computed(() => {
  if (!activeGroup.value?.tickets?.length) return false
  return activeGroup.value.tickets.some(t => ['pending', 'author_updated', 'admin_review_required'].includes(t.status))
})

const isQuestionPublic = computed(() => {
  return Boolean(activeGroup.value?.question?.is_public)
})

const isQuestionDeleted = computed(() => {
  return Boolean(activeGroup.value?.question?.deleted_at)
})

const activeGroupResolutionType = computed(() => {
  if (!activeGroup.value) return 'none'
  if (activeGroup.value.isAutoPrivatized || activeGroup.value.tickets.some(t => Boolean(t.auto_privatized_at))) {
    return 'auto_privatized'
  }
  if (activeGroup.value.tickets.every(t => t.status === 'dismissed')) {
    return 'dismissed'
  }
  const latestReq = activeGroup.value.question?.latestReviewRequest
  if (latestReq?.snapshot_metadata?.auto_approved) {
    return 'auto_resolved'
  }
  if (activeGroup.value.tickets.every(t => t.status === 'resolved')) {
    return 'resolved'
  }
  return 'other'
})

const statusTabs = computed(() => [
  { key: 'needs_admin_review', label: '🔴 Cần Admin xử lý', count: stats.admin_review_required_cases || stats.exception_cases_count || stats.needs_admin_review || stats.admin_review_required || 0 },
  { key: 'author_updated', label: '✏️ Tác giả đã sửa', count: stats.author_updated_cases || stats.author_updated || 0 },
  { key: 'pending', label: '⏳ Chờ tác giả', count: stats.pending_cases || stats.pending || 0 },
  { key: 'auto_resolved', label: '🟢 Tự động duyệt', count: stats.auto_resolved_cases || stats.auto_resolved || 0 },
  { key: 'auto_privatized', label: '🔒 Tự động gỡ (7 ngày)', count: stats.auto_privatized_cases || stats.auto_privatized || 0 },
  { key: 'dismissed', label: '⚪ Đã bỏ qua', count: stats.dismissed_cases || stats.dismissed || 0 },
  { key: 'all', label: '📋 Tất cả', count: stats.total_cases || stats.total || 0 },
])

const isTabActive = (tabKey) => {
  if (tabKey === 'needs_admin_review' && (selectedStatus.value === 'needs_admin_review' || selectedStatus.value === 'admin_review_required')) {
    return true
  }
  return selectedStatus.value === tabKey
}

const filteredReports = computed(() => {
  return reports.value.filter((rep) => {
    if (selectedStatus.value === 'needs_admin_review' || selectedStatus.value === 'admin_review_required') {
      if (rep.status !== 'admin_review_required') return false
    } else if (selectedStatus.value === 'author_updated') {
      if (rep.status !== 'author_updated') return false
    } else if (selectedStatus.value === 'pending') {
      if (rep.status !== 'pending') return false
    } else if (selectedStatus.value === 'auto_privatized') {
      if (!rep.auto_privatized_at) return false
    } else if (selectedStatus.value === 'auto_resolved') {
      if (rep.status !== 'resolved') return false
    } else if (selectedStatus.value === 'dismissed') {
      if (rep.status !== 'dismissed') return false
    } else if (selectedStatus.value !== 'all' && rep.status !== selectedStatus.value) {
      return false
    }

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
        isAutoPrivatized: false,
        latestReportAt: ticket.created_at,
        caseStatus: 'pending',
      })
    }

    const entry = map.get(qId)
    entry.tickets.push(ticket)

    entry.reasonsCount[ticket.reason] = (entry.reasonsCount[ticket.reason] || 0) + 1

    if (ticket.status === 'pending') {
      entry.hasPending = true
    } else if (ticket.status === 'author_updated') {
      entry.hasAuthorUpdated = true
    } else if (ticket.status === 'admin_review_required') {
      entry.hasAdminReviewRequired = true
    }

    if (ticket.auto_privatized_at) {
      entry.isAutoPrivatized = true
    }

    if (new Date(ticket.created_at) > new Date(entry.latestReportAt)) {
      entry.latestReportAt = ticket.created_at
    }
  })

  const result = Array.from(map.values()).map((entry) => {
    if (entry.hasAdminReviewRequired) {
      entry.caseStatus = 'admin_review_required'
    } else if (entry.hasAuthorUpdated) {
      entry.caseStatus = 'author_updated'
    } else if (entry.hasPending) {
      entry.caseStatus = 'pending'
    } else if (entry.tickets.every(t => t.status === 'dismissed')) {
      entry.caseStatus = 'dismissed'
    } else if (entry.tickets.every(t => t.status === 'resolved')) {
      const latestReq = entry.question?.latestReviewRequest
      if (latestReq?.snapshot_metadata?.auto_approved) {
        entry.caseStatus = 'auto_resolved'
      } else {
        entry.caseStatus = 'resolved'
      }
    } else {
      entry.caseStatus = 'other'
    }
    return entry
  })

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
    if (selectedStatus.value === 'needs_admin_review' || selectedStatus.value === 'admin_review_required') {
      if (g.caseStatus !== 'admin_review_required') return false
    } else if (selectedStatus.value === 'author_updated') {
      if (g.caseStatus !== 'author_updated') return false
    } else if (selectedStatus.value === 'pending') {
      if (g.caseStatus !== 'pending') return false
    } else if (selectedStatus.value === 'auto_privatized') {
      if (!g.isAutoPrivatized || !['pending', 'author_updated', 'admin_review_required'].includes(g.caseStatus)) return false
    } else if (selectedStatus.value === 'auto_resolved') {
      if (g.caseStatus !== 'auto_resolved') return false
    } else if (selectedStatus.value === 'resolved') {
      if (g.caseStatus !== 'resolved' && g.caseStatus !== 'auto_resolved') return false
    } else if (selectedStatus.value === 'dismissed') {
      if (g.caseStatus !== 'dismissed') return false
    }

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
      Object.assign(stats, res.stats)
    }

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