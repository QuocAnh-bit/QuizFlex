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
            <ShieldAlert class="h-6 w-6" />
          </div>
          <div>
            <div class="flex items-center gap-2.5">
              <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Quản lý báo cáo câu hỏi
              </h1>
            </div>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
              Quản lý và điều phối các phản ánh vi phạm theo từng Case câu hỏi. Hệ thống tự động hóa chu trình nhắc nhở và tự động gỡ, Quản trị viên tập trung xử lý các trường hợp ngoại lệ.
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
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

      <!-- KPI Stats Cards (3 Main Workflow Stages + Total) -->
      <div class="mt-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <!-- 1. CẦN TÔI XỬ LÝ (needs_admin) -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="selectedStage === 'needs_admin' ? 'border-rose-500 bg-rose-50/80 ring-2 ring-rose-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Các Case ngoại lệ hoặc vượt ngưỡng cần Admin thẩm định và ra quyết định"
          @click="selectedStage = 'needs_admin'"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
              <AlertTriangle class="h-5 w-5" />
            </div>
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Cần tôi xử lý</p>
              <div v-if="isLoading" class="mt-1 h-7 w-16 animate-pulse rounded-md bg-rose-100"></div><p v-else class="text-2xl font-black text-rose-600">
                {{ stats.needs_admin_cases ?? stats.admin_review_required_cases ?? 0 }}
                <span class="text-xs font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-2 text-[11px] text-rose-700 font-bold">
            {{ stats.admin_review_required_tickets || stats.needs_admin_cases || 0 }} lượt báo cáo cần duyệt
          </p>
        </div>

        <!-- 2. ĐANG XỬ LÝ (processing) -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="selectedStage === 'processing' ? 'border-amber-500 bg-amber-50/80 ring-2 ring-amber-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Các Case đang chờ tác giả đính chính hoặc đang trong chu trình tự động"
          @click="selectedStage = 'processing'"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
              <Clock class="h-5 w-5" />
            </div>
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Đang xử lý</p>
              <div v-if="isLoading" class="mt-1 h-7 w-16 animate-pulse rounded-md bg-amber-100"></div><p v-else class="text-2xl font-black text-amber-600">
                {{ stats.processing_cases ?? ((stats.pending_cases || 0) + (stats.author_updated_cases || 0)) ?? 0 }}
                <span class="text-xs font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-2 text-[11px] text-amber-700 font-medium">
            Chờ tác giả đính chính
          </p>
        </div>

        <!-- 3. ĐÃ HOÀN TẤT (completed) -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="selectedStage === 'completed' ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Các Case đã được giải quyết hoặc đã bỏ qua"
          @click="selectedStage = 'completed'"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
              <CheckCircle2 class="h-5 w-5" />
            </div>
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Đã hoàn tất</p>
              <div v-if="isLoading" class="mt-1 h-7 w-16 animate-pulse rounded-md bg-emerald-100"></div><p v-else class="text-2xl font-black text-emerald-600">
                {{ stats.completed_cases ?? ((stats.resolved_cases || 0) + (stats.dismissed_cases || 0)) ?? 0 }}
                <span class="text-xs font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-2 text-[11px] text-emerald-700 font-medium">
            Đã xử lý & kết thúc
          </p>
        </div>

        <!-- 4. TẤT CẢ (all) -->
        <div
          class="rounded-xl border p-4 transition cursor-pointer shadow-xs"
          :class="selectedStage === 'all' ? 'border-purple-500 bg-purple-50/80 ring-2 ring-purple-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          title="Xem toàn bộ Case và lịch sử phản ánh"
          @click="selectedStage = 'all'"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
              <Flag class="h-5 w-5" />
            </div>
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Tổng Case</p>
              <div v-if="isLoading" class="mt-1 h-7 w-16 animate-pulse rounded-md bg-purple-100"></div><p v-else class="text-2xl font-black text-purple-700">
                {{ stats.total_cases || stats.total || 0 }}
                <span class="text-xs font-normal text-slate-500">case</span>
              </p>
            </div>
          </div>
          <p class="mt-2 text-[11px] text-purple-700 font-medium">
            {{ stats.total || 0 }} lượt báo cáo tổng thể
          </p>
        </div>
      </div>
    </div>

    <!-- TABS & CONTROLS BAR -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-white p-4 rounded-2xl shadow-xs">
      <!-- 3 Primary Stage Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto text-xs font-bold py-1">
        <button
          type="button"
          class="rounded-xl px-3.5 py-2 transition cursor-pointer flex items-center gap-2 whitespace-nowrap"
          :class="selectedStage === 'needs_admin' ? 'bg-rose-600 text-white shadow-xs font-black' : 'bg-rose-50 text-rose-800 hover:bg-rose-100'"
          @click="selectedStage = 'needs_admin'"
        >
          <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
          <span>Cần tôi xử lý</span>
          <span class="rounded-full px-2 py-0.5 text-[10px]" :class="selectedStage === 'needs_admin' ? 'bg-white text-rose-700 font-black' : 'bg-rose-600 text-white font-bold'">
            <span v-if="isLoading" class="block h-3 w-3 animate-pulse rounded bg-current opacity-30"></span>
            <template v-else>{{ stats.needs_admin_cases ?? stats.admin_review_required_cases ?? 0 }}</template>
          </span>
        </button>

        <button
          type="button"
          class="rounded-xl px-3.5 py-2 transition cursor-pointer flex items-center gap-2 whitespace-nowrap"
          :class="selectedStage === 'processing' ? 'bg-amber-600 text-white shadow-xs font-black' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'"
          @click="selectedStage = 'processing'"
        >
          <Clock class="h-3.5 w-3.5 shrink-0" />
          <span>Đang xử lý</span>
          <span class="rounded-full px-2 py-0.5 text-[10px]" :class="selectedStage === 'processing' ? 'bg-white text-amber-700 font-black' : 'bg-amber-600 text-white font-bold'">
            <span v-if="isLoading" class="block h-3 w-3 animate-pulse rounded bg-current opacity-30"></span>
            <template v-else>{{ stats.processing_cases ?? ((stats.pending_cases || 0) + (stats.author_updated_cases || 0)) ?? 0 }}</template>
          </span>
        </button>

        <button
          type="button"
          class="rounded-xl px-3.5 py-2 transition cursor-pointer flex items-center gap-2 whitespace-nowrap"
          :class="selectedStage === 'completed' ? 'bg-emerald-600 text-white shadow-xs font-black' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100'"
          @click="selectedStage = 'completed'"
        >
          <CheckCircle2 class="h-3.5 w-3.5 shrink-0" />
          <span>Đã hoàn tất</span>
          <span class="rounded-full px-2 py-0.5 text-[10px]" :class="selectedStage === 'completed' ? 'bg-white text-emerald-700 font-black' : 'bg-emerald-600 text-white font-bold'">
            <span v-if="isLoading" class="block h-3 w-3 animate-pulse rounded bg-current opacity-30"></span>
            <template v-else>{{ stats.completed_cases ?? ((stats.resolved_cases || 0) + (stats.dismissed_cases || 0)) ?? 0 }}</template>
          </span>
        </button>

        <button
          type="button"
          class="rounded-xl px-3.5 py-2 transition cursor-pointer flex items-center gap-2 whitespace-nowrap"
          :class="selectedStage === 'all' ? 'bg-purple-600 text-white shadow-xs font-black' : 'bg-purple-50 text-purple-800 hover:bg-purple-100'"
          @click="selectedStage = 'all'"
        >
          <Layers class="h-3.5 w-3.5 shrink-0" />
          <span>Tất cả</span>
          <span class="rounded-full px-2 py-0.5 text-[10px]" :class="selectedStage === 'all' ? 'bg-white text-purple-700 font-black' : 'bg-purple-600 text-white font-bold'">
            <span v-if="isLoading" class="block h-3 w-3 animate-pulse rounded bg-current opacity-30"></span>
            <template v-else>{{ stats.total_cases || 0 }}</template>
          </span>
        </button>
      </div>

      <!-- View Mode & Search -->
      <div class="flex flex-wrap items-center gap-3">
        <!-- View Mode Switcher -->
        <div class="flex items-center rounded-xl bg-slate-100 p-1 text-xs font-bold">
          <button
            type="button"
            class="rounded-lg px-3.5 py-1.5 transition cursor-pointer"
            :class="viewMode === 'grouped' ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-500 hover:text-slate-800'"
            @click="viewMode = 'grouped'"
          >
            Theo câu hỏi
          </button>
          <button
            type="button"
            class="rounded-lg px-3.5 py-1.5 transition cursor-pointer"
            :class="viewMode === 'all' ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-500 hover:text-slate-800'"
            @click="viewMode = 'all'"
          >
            Lịch sử báo cáo
          </button>
        </div>

        <!-- Search input -->
        <div class="relative min-w-[240px]">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="viewMode === 'grouped' ? 'Tìm mã câu hỏi, nội dung, tác giả, lý do...' : 'Tìm mã ticket, câu hỏi, người báo, lý do...'"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-8 pr-3 py-1.5 text-xs font-medium text-slate-900 outline-none focus:border-rose-500 focus:bg-white"
          />
        </div>
      </div>
    </div>

    <!-- =========================================================================
         VIEW 1: THEO CÂU HỎI (PRIMARY CASE WORKSPACE)
    ========================================================================== -->
    <div v-if="viewMode === 'grouped'" class="space-y-4">
      <div v-if="isLoading" class="p-12 text-center text-xs text-slate-400 rounded-2xl bg-white border border-slate-200">
        <RefreshCw class="mx-auto mb-2 h-6 w-6 animate-spin text-rose-600" />
        <span>Đang tổng hợp danh sách các Case câu hỏi...</span>
      </div>

      <!-- Human-friendly Empty State tailored per Workflow Stage -->
      <div v-else-if="filteredCases.length === 0" class="p-12 text-center text-xs text-slate-400 rounded-2xl bg-white border border-slate-200 space-y-2">
        <CheckCircle2 class="mx-auto h-10 w-10 text-emerald-500 opacity-80" />
        <p class="font-bold text-slate-800 text-sm">
          {{ emptyStateTitle }}
        </p>
        <p class="text-slate-500 text-xs max-w-md mx-auto">
          {{ emptyStateDesc }}
        </p>
      </div>

      <!-- Case List Scroll Container (Displays ~3 Cases with internal smooth vertical scroll) -->
      <div
        v-else
        ref="caseListContainer"
        class="case-list-scroll max-h-[580px] overflow-y-auto pr-1.5 space-y-4 rounded-2xl focus:outline-none"
      >
        <div
          v-for="group in filteredCases"
          :key="group.question_id"
          class="rounded-2xl border bg-white p-5 shadow-sm hover:shadow-md transition space-y-4"
          :class="group.workflow_stage === 'needs_admin' ? 'border-rose-300 ring-1 ring-rose-200' : (group.workflow_stage === 'processing' ? 'border-amber-200 bg-amber-50/10' : 'border-slate-200')"
        >
          <!-- Group Header -->
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-slate-100 pb-3">
            <div class="space-y-2 max-w-4xl">
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

                <!-- 1. MAIN WORKFLOW STAGE BADGE -->
                <span
                  v-if="group.workflow_stage === 'needs_admin'"
                  class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-black text-rose-800 border border-rose-200 flex items-center gap-1.5"
                >
                  <AlertTriangle class="h-3 w-3" />
                  <span>Cần tôi xử lý</span>
                </span>
                <span
                  v-else-if="group.workflow_stage === 'processing'"
                  class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-black text-amber-800 border border-amber-200 flex items-center gap-1.5"
                >
                  <Clock class="h-3 w-3" />
                  <span>Đang xử lý</span>
                </span>
                <span
                  v-else
                  class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-black text-emerald-800 border border-emerald-200 flex items-center gap-1.5"
                >
                  <CheckCircle2 class="h-3 w-3" />
                  <span>Đã hoàn tất</span>
                </span>

                <!-- 2. AUTOMATION & RESOLUTION METADATA (Auxiliary Badges) -->
                <span
                  v-if="group.workflow_stage === 'processing' && (group.has_author_updated || group.case_status === 'author_updated')"
                  class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 border border-blue-200 flex items-center gap-1"
                >
                  <Pencil class="h-3 w-3" />
                  <span>Tác giả đã sửa</span>
                </span>

                <span
                  v-if="group.is_auto_privatized"
                  class="rounded-md bg-orange-50 px-2 py-0.5 text-[10px] font-bold text-orange-700 border border-orange-200 flex items-center gap-1"
                >
                  <Lock class="h-2.5 w-2.5" />
                  <span>Đã tự động ẩn</span>
                </span>

                <span
                  v-if="group.workflow_stage === 'completed' && (group.is_auto_resolved || group.resolution_source === 'auto_review')"
                  class="rounded-md bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 border border-teal-200 flex items-center gap-1"
                >
                  <Sparkles class="h-3 w-3" />
                  <span>Tự động duyệt</span>
                </span>

                <span
                  v-else-if="group.workflow_stage === 'completed' && group.case_status === 'dismissed'"
                  class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 border border-slate-200 flex items-center gap-1"
                >
                  <Slash class="h-3 w-3" />
                  <span>Đã bỏ qua</span>
                </span>

                <span
                  v-else-if="group.workflow_stage === 'completed' && group.case_status === 'resolved'"
                  class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200 flex items-center gap-1"
                >
                  <UserCheck class="h-3 w-3" />
                  <span>Admin xử lý</span>
                </span>

                <!-- Reports Count Badge -->
                <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700 border border-rose-200">
                  {{ group.reports_count }} lượt báo cáo
                </span>
              </div>

              <!-- Question Content -->
              <div class="pt-1 text-sm font-bold leading-relaxed text-slate-900"><MathText :content="group.question?.content || 'Nội dung câu hỏi không khả dụng'" /></div>
            </div>

            <!-- Group Action Buttons (Clear CTA) -->
            <div class="flex items-center gap-2 shrink-0">
              <button
                v-if="group.workflow_stage === 'needs_admin'"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 transition cursor-pointer shadow-xs"
                @click="openModerationModal(group)"
              >
                <Shield class="h-3.5 w-3.5" />
                <span>Xử lý Case</span>
              </button>

              <button
                v-else
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
            <div class="flex flex-wrap items-center gap-1.5">
              <span class="text-slate-500 font-medium">Lý do báo cáo:</span>
              <span
                v-for="(count, reason) in group.reasons_count"
                :key="reason"
                class="rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 font-bold text-rose-800 text-[11px]"
              >
                {{ count }}× {{ reason }}
              </span>
            </div>

            <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2 text-slate-500 text-[11px]">
              <span>Tác giả: <strong>{{ group.question?.user?.name || 'Chưa rõ' }}</strong></span>
              <span>•</span>
              <span v-if="group.days_open !== undefined">Mở: <strong>{{ group.days_open }} ngày</strong></span>
              <span>•</span>
              <span>Báo cáo gần nhất: <strong>{{ formatDate(group.latest_report_at) }}</strong></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================================
         VIEW 2: LỊCH SỬ BÁO CÁO (RAW AUDIT LOG TABLE OF ALL TICKETS)
    ========================================================================== -->
    <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <th class="w-24 p-3.5 text-center">Ticket ID</th>
              <th class="p-3.5">Câu hỏi liên quan</th>
              <th class="p-3.5">Người báo cáo</th>
              <th class="p-3.5">Lý do & Mô tả</th>
              <th class="p-3.5">Thời gian gửi</th>
              <th class="w-36 p-3.5 text-center">Trạng thái Ticket</th>
              <th class="w-28 p-3.5 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="filteredReports.length === 0">
              <td colspan="7" class="p-12 text-center text-slate-400 font-medium">
                Không tìm thấy lượt báo cáo nào phù hợp.
              </td>
            </tr>

            <tr
              v-for="report in filteredReports"
              :key="report.id"
              class="hover:bg-slate-50/80 transition"
            >
              <td class="p-3.5 text-center align-top font-mono font-bold text-slate-700">
                #{{ report.id }}
              </td>

              <td class="p-3.5 align-top max-w-sm">
                <div class="space-y-1">
                  <span class="font-bold text-slate-900">Câu hỏi #{{ report.question_id }}</span>
                  <div class="line-clamp-2 text-slate-600 italic"><MathText :content="report.question?.content || 'N/A'" compact /></div>
                </div>
              </td>

              <td class="p-3.5 align-top">
                <div class="space-y-0.5">
                  <p class="font-bold text-slate-900 truncate">{{ report.user?.name || 'Người dùng' }}</p>
                  <p class="text-[10px] text-slate-400 truncate">{{ report.user?.email }}</p>
                </div>
              </td>

              <td class="p-3.5 align-top max-w-xs space-y-1">
                <span class="inline-block rounded bg-rose-50 px-2 py-0.5 font-bold text-rose-700 border border-rose-200 text-[10px]">
                  {{ report.reason }}
                </span>
                <p v-if="report.description" class="text-slate-600 text-[11px] leading-relaxed">
                  {{ report.description }}
                </p>
              </td>

              <td class="p-3.5 align-top text-slate-500 text-[11px] space-y-1">
                <div>{{ formatDate(report.created_at) }}</div>
                <div v-if="report.auto_privatized_at" class="text-orange-600 font-bold text-[10px] flex items-center gap-1">
                  <Lock class="h-3 w-3 shrink-0" />
                  <span>Tự động ẩn: {{ formatDate(report.auto_privatized_at) }}</span>
                </div>
              </td>

              <td class="p-3.5 text-center align-top">
                <StatusBadge :value="report.status" />
              </td>

              <td class="p-3.5 align-top text-right">
                <button
                  type="button"
                  class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-2xs"
                  @click="openModalFromTicket(report)"
                >
                  Xem Case
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- History Table Pagination Bar (10 questions per page) -->
      <div v-if="pagination.total > 0" class="px-5 py-3.5 bg-slate-50/70 border-t border-slate-200">
        <AppPagination
          :current-page="pagination.currentPage"
          :last-page="pagination.lastPage"
          :total="pagination.total"
          :per-page="pagination.perPage"
          :item-label="'câu hỏi'"
          @change="onPageChange"
        />
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
              :class="activeGroupStage === 'needs_admin' ? 'bg-rose-100 text-rose-600' : (activeGroupStage === 'processing' ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600')"
            >
              <ShieldAlert v-if="activeGroupStage === 'needs_admin'" class="h-5 w-5" />
              <Clock v-else-if="activeGroupStage === 'processing'" class="h-5 w-5" />
              <CheckCircle2 v-else class="h-5 w-5" />
            </div>
            <div>
              <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span>{{ activeGroupStage === 'completed' ? 'Chi tiết Case Báo cáo Câu hỏi' : 'Xử lý Case Báo cáo Câu hỏi' }}</span>
                <span class="rounded-md bg-slate-200 px-2 py-0.5 font-mono text-xs font-bold text-slate-800">
                  Case #{{ activeGroup?.question_id }}
                </span>
              </h2>
              <p class="text-xs text-slate-500">
                {{ activeGroupStage === 'completed' ? 'Case này đã kết thúc workflow. Dưới đây là thông tin chi tiết và lịch sử phản ánh.' : `Áp dụng quyết định quản trị viên cho ${activeGroup?.tickets?.length || 0} lượt báo cáo của câu hỏi này.` }}
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
            <!-- LEFT COLUMN: USER REPORTS (TICKETS) -->
            <div class="space-y-4">
              <!-- Báo cáo: Tổng số Ticket & Phân loại lý do -->
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5 space-y-2 text-xs">
                <div class="flex items-center justify-between text-slate-700 font-bold">
                  <span class="flex items-center gap-1.5">
                    <Flag class="h-3.5 w-3.5 text-rose-600" />
                    <span>Tổng quan báo cáo:</span>
                  </span>
                  <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-black text-rose-700">
                    {{ activeGroup?.tickets?.length || activeGroup?.reports_count || 0 }} lượt báo cáo
                  </span>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                  <span
                    v-for="(count, reason) in activeGroup?.reasons_count"
                    :key="reason"
                    class="rounded-md border border-rose-200 bg-white px-2 py-0.5 font-bold text-rose-800 text-[11px]"
                  >
                    {{ count }}× {{ reason }}
                  </span>
                </div>
              </div>

              <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <Clock class="h-4 w-4 text-slate-500" />
                <span>Lịch sử các lượt phản ánh ({{ activeGroup?.tickets?.length || 0 }} tickets):</span>
              </h3>

              <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1">
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

            <!-- RIGHT COLUMN: QUESTION DETAILS & ACTION/RESOLUTION -->
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
                <div>
                  <span class="text-[10px] font-bold uppercase text-slate-400">Đề bài:</span>
                  <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 p-3 font-bold leading-relaxed text-slate-900"><MathText :content="activeGroup?.question?.content || ''" /></div>
                </div>

                <div class="space-y-1.5">
                  <span class="text-[10px] font-bold uppercase text-slate-400">Các phương án đáp án:</span>
                  <div
                    v-for="ans in activeGroup?.question?.answers"
                    :key="ans.id || ans.key"
                    class="rounded-xl border p-2 text-xs font-semibold flex items-center justify-between"
                    :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700'"
                  >
                    <span class="flex min-w-0 flex-1 items-start gap-1"><b class="shrink-0">{{ ans.key || ans.answer_key }}.</b><MathText :content="ans.content || ans.text || ''" compact class="min-w-0" /></span>
                    <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600 flex items-center gap-1">
                      <Check class="h-3 w-3 stroke-[3]" />
                      <span>Đáp án đúng</span>
                    </span>
                  </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                  <span>Tác giả: <strong>{{ activeGroup?.question?.user?.name || 'Chưa rõ' }}</strong></span>
                  <div class="flex items-center gap-1.5">
                    <span>Trạng thái câu hỏi:</span>
                    <span
                      class="rounded-md px-2 py-0.5 font-bold text-[10px]"
                      :class="isQuestionDeleted ? 'bg-rose-100 text-rose-700' : (isQuestionPublic ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700')"
                    >
                      {{ isQuestionDeleted ? 'Đã xóa' : (isQuestionPublic ? 'Công khai' : 'Riêng tư') }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- LATEST REVISION / AUTO-REVIEW METADATA BADGE -->
              <div
                v-if="activeGroup?.latest_review_request?.is_auto_approved"
                class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-3.5 space-y-1 text-xs"
              >
                <div class="flex items-center gap-1.5 text-emerald-900 font-bold">
                  <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                  <span>Bản đính chính đã được Tự động duyệt (Phiên bản {{ activeGroup.latest_review_request.revision_number }})</span>
                </div>
                <p class="text-[11px] text-emerald-800 leading-relaxed">
                  Tác giả đã cập nhật nội dung đính chính và vượt qua toàn bộ quy tắc kiểm định an toàn tự động lúc {{ formatDate(activeGroup.latest_review_request.auto_approved_at || activeGroup.latest_review_request.updated_at) }}.
                </p>
              </div>

              <div
                v-else-if="activeGroup?.latest_review_request?.auto_review_failed"
                class="rounded-2xl border border-rose-200 bg-rose-50/60 p-3.5 space-y-1 text-xs"
              >
                <div class="flex items-center gap-1.5 text-rose-900 font-bold">
                  <AlertTriangle class="h-4 w-4 text-rose-600" />
                  <span>Auto Review không đạt — Cần Quản trị viên thẩm định (Phiên bản {{ activeGroup.latest_review_request.revision_number }})</span>
                </div>
                <p class="text-[11px] text-rose-800 leading-relaxed">
                  Lý do: {{ activeGroup.latest_review_request.auto_review_reason || 'Nội dung đính chính vi phạm quy tắc cấu trúc hoặc danh mục nghiêm trọng.' }}
                </p>
              </div>

              <!-- =========================================================================
                   CASE ACTION WORKSPACE (CHO CASE CHƯA HOÀN TẤT: needs_admin & processing)
              ========================================================================== -->
              <div
                v-if="activeGroupStage !== 'completed'"
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

                <div v-if="isQuestionDeleted" class="p-2.5 rounded-xl border border-rose-200 bg-rose-50 text-[11px] text-rose-800 font-semibold flex items-center gap-1.5">
                  <AlertTriangle class="h-3.5 w-3.5 shrink-0 text-rose-600" />
                  <span>Câu hỏi này hiện đã bị xóa trong Thùng rác. Bạn có thể bỏ qua báo cáo hoặc giải quyết case.</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                  <button
                    v-if="!isQuestionDeleted"
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('resolved', 'keep')"
                  >
                    <Check class="h-3.5 w-3.5" />
                    <span>Xác nhận giải quyết</span>
                  </button>

                  <button
                    v-if="isQuestionPublic && !isQuestionDeleted"
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100 transition cursor-pointer shadow-xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('resolved', 'hide')"
                  >
                    <Lock class="h-3.5 w-3.5" />
                    <span>Gỡ công khai</span>
                  </button>

                  <button
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer shadow-xs"
                    :disabled="isSubmittingResolution"
                    @click="executeGroupResolution('dismissed', 'keep')"
                  >
                    <X class="h-3.5 w-3.5" />
                    <span>Bỏ qua báo cáo (Spam)</span>
                  </button>

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
                   CASE READ-ONLY MODE (CASE ĐÃ HOÀN TẤT: completed)
              ========================================================================== -->
              <div v-else class="space-y-3">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 space-y-2 text-xs">
                  <div class="flex items-center justify-between text-emerald-950 font-black">
                    <div class="flex items-center gap-2">
                      <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                      <span>CASE ĐÃ HOÀN TẤT XỬ LÝ</span>
                    </div>
                    <span class="rounded-md bg-white px-2 py-0.5 text-[11px] font-bold border" :class="activeGroup?.is_auto_privatized ? 'text-orange-800 border-orange-200' : 'text-emerald-800 border-emerald-200'">
                      {{ activeGroup?.is_auto_privatized ? 'Tự động ẩn (Day 7)' : (activeGroup?.case_status === 'dismissed' ? 'Đã bỏ qua' : (activeGroup?.is_auto_resolved ? 'Tự động duyệt' : 'Đã giải quyết')) }}
                    </span>
                  </div>

                  <p class="text-slate-600 leading-relaxed text-[11px]">
                    {{ activeGroup?.is_auto_privatized ? 'Tác giả không cập nhật nội dung đính chính sau 7 ngày. Hệ thống đã tự động chuyển câu hỏi về chế độ riêng tư và khép lại case.' : (activeGroup?.case_status === 'dismissed' ? 'Toàn bộ báo cáo vi phạm đã được Quản trị viên thẩm định và bỏ qua.' : (activeGroup?.is_auto_resolved ? 'Nội dung đính chính của tác giả đã vượt qua kiểm định an toàn tự động (Auto Approve).' : 'Toàn bộ phản ánh vi phạm đã được Quản trị viên giải quyết.')) }}
                  </p>

                  <div v-if="activeGroup?.resolution_note" class="bg-white p-2.5 rounded-xl border border-emerald-100 text-[11px] text-slate-700">
                    <strong>Ghi chú:</strong> {{ activeGroup.resolution_note }}
                  </div>

                  <div class="text-[11px] text-slate-500 pt-2 border-t border-emerald-200/60 flex items-center justify-between">
                    <span>Nguồn: <strong>{{ activeGroup?.resolution_source || (activeGroup?.is_auto_privatized ? 'system' : 'Hệ thống') }}</strong></span>
                    <span>Thời gian: <strong>{{ formatDate(activeGroup?.resolved_at || activeGroup?.tickets?.[0]?.updated_at) }}</strong></span>
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
import { computed, inject, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  AlertTriangle,
  Check,
  CheckCircle2,
  ChevronRight,
  Clock,
  Eye,
  Flag,
  HelpCircle,
  Layers,
  Lock,
  Pencil,
  RefreshCw,
  Search,
  Shield,
  ShieldAlert,
  Slash,
  Sparkles,
  Trash2,
  UserCheck,
  X,
} from 'lucide-vue-next'
import StatusBadge from '@/components/common/StatusBadge.vue'
import AppPagination from '@/components/common/AppPagination.vue'
import MathText from '@/components/MathText.vue'
import { reportApi } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'

const { beginTask, endTask } = useAppLoading()

const route = useRoute()
const showToast = inject('showToast')

const cases = ref([])
const reports = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedStage = ref('needs_admin')
const viewMode = ref('grouped')
const caseListContainer = ref(null)

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 10,
  total: 0,
})

// Reset scroll container position when stage, search, or view mode changes
watch([selectedStage, searchQuery, viewMode], () => {
  if (caseListContainer.value) {
    caseListContainer.value.scrollTop = 0
  }
})

let searchDebounceTimer = null
watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    pagination.currentPage = 1
    fetchReports(1)
  }, 300)
})

watch(selectedStage, () => {
  pagination.currentPage = 1
  fetchReports(1)
})

const onPageChange = (page) => {
  pagination.currentPage = page
  fetchReports(page)
}

const stats = reactive({
  total: 0,
  total_cases: 0,
  needs_admin: 0,
  needs_admin_cases: 0,
  processing: 0,
  processing_cases: 0,
  completed: 0,
  completed_cases: 0,
  admin_review_required_cases: 0,
  admin_review_required_tickets: 0,
  author_updated_cases: 0,
  author_updated_tickets: 0,
  pending_cases: 0,
  pending_tickets: 0,
  auto_privatized_cases: 0,
  auto_privatized_tickets: 0,
  resolved_cases: 0,
  resolved_tickets: 0,
  dismissed_cases: 0,
  dismissed_tickets: 0,
})

// Moderation Modal
const isModerationModalOpen = ref(false)
const activeGroup = ref(null)
const resolutionNote = ref('')
const isSubmittingResolution = ref(false)

const activeGroupStage = computed(() => {
  return activeGroup.value?.workflow_stage || 'completed'
})

const isQuestionPublic = computed(() => {
  return Boolean(activeGroup.value?.is_question_public)
})

const isQuestionDeleted = computed(() => {
  return Boolean(activeGroup.value?.is_question_deleted)
})

const filteredReports = computed(() => {
  return reports.value.filter((rep) => {
    if (selectedStage.value === 'needs_admin') {
      if (rep.status !== 'admin_review_required') return false
    } else if (selectedStage.value === 'processing') {
      if (rep.status !== 'pending' && rep.status !== 'author_updated') return false
    } else if (selectedStage.value === 'completed') {
      if (rep.status !== 'resolved' && rep.status !== 'dismissed') return false
    }

    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase().trim()
    const ticketId = String(rep.id || '')
    const qId = String(rep.question_id || '')
    const content = (rep.question?.content || '').toLowerCase()
    const reason = (rep.reason || '').toLowerCase()
    const desc = (rep.description || '').toLowerCase()
    const reporter = (rep.user?.name || '').toLowerCase()
    const email = (rep.user?.email || '').toLowerCase()

    return (
      ticketId.includes(q) ||
      qId.includes(q) ||
      content.includes(q) ||
      reason.includes(q) ||
      desc.includes(q) ||
      reporter.includes(q) ||
      email.includes(q)
    )
  })
})

const filteredCases = computed(() => {
  return cases.value.filter((g) => {
    // Sử dụng trực tiếp workflow_stage do Backend phân giải duy nhất
    if (selectedStage.value !== 'all' && g.workflow_stage !== selectedStage.value) {
      return false
    }

    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase().trim()
    const qId = String(g.question_id || '')
    const content = (g.question?.content || '').toLowerCase()
    const author = (g.question?.user?.name || '').toLowerCase()
    const reasons = Object.keys(g.reasons_count || {}).join(' ').toLowerCase()

    return qId.includes(q) || content.includes(q) || author.includes(q) || reasons.includes(q)
  })
})

const emptyStateTitle = computed(() => {
  if (searchQuery.value.trim()) return 'Không tìm thấy Case câu hỏi nào phù hợp với tìm kiếm.'
  if (selectedStage.value === 'needs_admin') return 'Không có báo cáo nào cần bạn xử lý.'
  if (selectedStage.value === 'processing') return 'Hiện không có Case đang chờ xử lý.'
  if (selectedStage.value === 'completed') return 'Chưa có Case nào hoàn tất.'
  return 'Không tìm thấy Case câu hỏi nào.'
})

const emptyStateDesc = computed(() => {
  if (searchQuery.value.trim()) return 'Hãy thử tìm kiếm với từ khóa khác hoặc xóa bộ lọc tìm kiếm.'
  if (selectedStage.value === 'needs_admin') return 'Tất cả các báo cáo đang được điều phối tự động hoặc đã được giải quyết xong.'
  if (selectedStage.value === 'processing') return 'Không có câu hỏi nào đang chờ tác giả đính chính hoặc trong chu trình tự động.'
  if (selectedStage.value === 'completed') return 'Các case sau khi được giải quyết hoặc bỏ qua sẽ xuất hiện tại đây.'
  return 'Danh sách case báo cáo hiện đang trống.'
})

const fetchReports = async (page = pagination.currentPage) => {
  isLoading.value = true
  try {
    const params = {
      page,
      per_page: pagination.perPage,
    }
    if (selectedStage.value !== 'all') {
      params.stage = selectedStage.value
    }
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }

    const res = await reportApi.listAdmin(params)
    cases.value = res.cases || []
    reports.value = res.items || []
    if (res.stats) {
      Object.assign(stats, res.stats)
    }
    if (res.pagination) {
      pagination.currentPage = Number(res.pagination.current_page || page)
      pagination.lastPage = Number(res.pagination.last_page || 1)
      pagination.perPage = Number(res.pagination.per_page || 10)
      pagination.total = Number(res.pagination.total ?? (res.cases?.length || 0))
    }

    if (route.query.question_id) {
      const targetQId = Number(route.query.question_id)
      const targetCase = cases.value.find(g => g.question_id === targetQId)
      if (targetCase) {
        openModerationModal(targetCase)
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
  const targetCase = cases.value.find(g => g.question_id === ticket.question_id)
  if (targetCase) {
    openModerationModal(targetCase)
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

onMounted(async () => {
  beginTask()
  try {
  if (route.query.status) {
    const s = route.query.status
    if (s === 'needs_admin_review' || s === 'admin_review_required' || s === 'needs_admin') {
      selectedStage.value = 'needs_admin'
    } else if (s === 'pending' || s === 'author_updated' || s === 'processing') {
      selectedStage.value = 'processing'
    } else if (s === 'resolved' || s === 'dismissed' || s === 'completed') {
      selectedStage.value = 'completed'
    } else if (s === 'all') {
      selectedStage.value = 'all'
    }
  } else if (route.query.stage) {
    selectedStage.value = route.query.stage
  }
  await fetchReports()
  } finally {
    endTask()
  }
})
</script>

<style scoped>
.case-list-scroll::-webkit-scrollbar {
  width: 6px;
}
.case-list-scroll::-webkit-scrollbar-track {
  background: #f8fafc;
  border-radius: 9999px;
}
.case-list-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
.case-list-scroll::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
