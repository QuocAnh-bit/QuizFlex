<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <router-link to="/admin/question-bank" class="hover:text-[#7C3AED] transition-colors">
        Ngân hàng câu hỏi
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">Chi tiết #{{ questionId }}</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-[#7C3AED] hover:text-white cursor-pointer"
            title="Quay lại"
            @click="goBack"
          >
            <ArrowLeft class="h-5 w-5" />
          </button>

          <div>
            <div class="flex flex-wrap items-center gap-2.5">
              <h1 class="text-2xl font-black tracking-tight text-slate-900">
                Câu hỏi #{{ questionId }}
              </h1>

              <!-- Review Status Badge -->
              <span
                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider"
                :class="getReviewStatusClass(question.bank_submission_status)"
              >
                <Clock v-if="question.bank_submission_status === 'pending'" class="h-3.5 w-3.5" />
                <Check v-else-if="question.bank_submission_status === 'approved'" class="h-3.5 w-3.5" />
                <X v-else-if="question.bank_submission_status === 'rejected'" class="h-3.5 w-3.5" />
                <span>{{ getReviewStatusLabel(question.bank_submission_status) }}</span>
              </span>

              <!-- Visibility / Trash Badge (Trash has highest priority) -->
              <span
                v-if="question.deleted_at"
                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200"
              >
                <Trash2 class="h-3.5 w-3.5 text-rose-600" />
                <span>Trong thùng rác</span>
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider"
                :class="Boolean(question.is_public)
                  ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                  : 'bg-slate-100 text-slate-600 border border-slate-200'"
              >
                <component :is="Boolean(question.is_public) ? Globe : Lock" class="h-3.5 w-3.5" />
                <span>{{ Boolean(question.is_public) ? 'Công khai' : 'Riêng tư' }}</span>
              </span>

              <!-- Revision Number if any -->
              <span
                v-if="question.revision_number && question.revision_number > 1"
                class="rounded-lg bg-purple-100 text-[#7C3AED] px-2.5 py-1 text-xs font-bold"
              >
                Revision #{{ question.revision_number }}
              </span>
            </div>

            <p class="mt-1 text-xs text-slate-500">
              Tác giả: <strong class="text-slate-800">{{ question.author?.name || 'Vô danh' }}</strong>
              <span v-if="question.author?.email" class="text-slate-400 ml-1">({{ question.author?.email }})</span>
              • Ngày tạo: <strong>{{ formatDate(question.created_at) }}</strong>
            </p>
          </div>
        </div>

        <!-- Header Actions based on Status -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- 1. TRASH ACTIONS (HIGHEST PRIORITY) -->
          <template v-if="question.deleted_at">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-xs disabled:opacity-50"
              :disabled="isProcessing"
              @click="handleRestore"
            >
              <RotateCcw class="h-4 w-4" />
              <span>Khôi phục câu hỏi</span>
            </button>

            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition cursor-pointer shadow-xs disabled:opacity-50"
              :disabled="isProcessing"
              @click="handleForceDelete"
            >
              <Trash2 class="h-4 w-4" />
              <span>Xóa vĩnh viễn</span>
            </button>
          </template>

          <!-- 2. PENDING ACTIONS: Approve / Reject (Only when not in trash) -->
          <template v-else-if="question.bank_submission_status === 'pending'">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition cursor-pointer shadow-xs"
              :disabled="isProcessing"
              @click="openRejectModal"
            >
              <X class="h-4 w-4" />
              <span>Từ chối</span>
            </button>

            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-xs disabled:opacity-50"
              :disabled="isProcessing"
              @click="handleApprove"
            >
              <Check class="h-4 w-4" />
              <span>Phê duyệt vào Ngân hàng</span>
            </button>
          </template>

          <!-- 3. APPROVED ACTIONS: Toggle Visibility & Delete to Trash (Only when not in trash) -->
          <template v-else-if="question.bank_submission_status === 'approved'">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl border px-4 py-2 text-xs font-bold transition cursor-pointer shadow-xs"
              :class="Boolean(question.is_public)
                ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
              @click="toggleVisibility"
            >
              <component :is="Boolean(question.is_public) ? Lock : Globe" class="h-4 w-4" />
              <span>{{ Boolean(question.is_public) ? 'Gỡ công khai' : 'Duyệt công khai' }}</span>
            </button>

            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition cursor-pointer shadow-xs"
              @click="deleteQuestion"
            >
              <Trash2 class="h-4 w-4" />
              <span>Chuyển thùng rác</span>
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- CONTEXT BANNER 0: TRASHED ALERT (HIGHEST PRIORITY) -->
    <div
      v-if="question.deleted_at"
      class="flex items-start gap-3 rounded-2xl border border-rose-300 bg-rose-50 p-4 text-xs text-rose-900 shadow-xs"
    >
      <Trash2 class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
      <div class="space-y-1">
        <p class="font-black uppercase tracking-wider text-rose-950">
          Câu hỏi này đang nằm trong Thùng rác (Xóa lúc {{ formatDate(question.deleted_at) }})
        </p>
        <p class="text-rose-800 leading-relaxed font-medium">
          Câu hỏi đã bị xóa mềm và không hiển thị trong Ngân hàng câu hỏi cũng như các bài thi công khai. Mọi thao tác đổi hiển thị hoặc duyệt bị khóa cho đến khi được khôi phục.
        </p>
      </div>
    </div>

    <!-- CONTEXT BANNER 1: PENDING REVIEW ALERT -->
    <div
      v-else-if="question.bank_submission_status === 'pending'"
      class="flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-xs text-amber-900 shadow-xs"
    >
      <Clock class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
      <div class="space-y-1">
        <p class="font-black uppercase tracking-wider text-amber-950">
          Câu hỏi đang chờ thẩm định vào Ngân hàng (Lần gửi duyệt #{{ question.current_revision?.revision_number || 1 }})
        </p>
        <p class="text-amber-800 leading-relaxed font-medium">
          Xem nội dung đối chiếu sai khác bên dưới để kiểm tra tính hợp lệ trước khi phê duyệt hoặc gửi lý do từ chối cho tác giả.
        </p>
      </div>
    </div>

    <!-- CONTEXT BANNER 2: REJECTED ALERT -->
    <div
      v-else-if="question.bank_submission_status === 'rejected'"
      class="flex items-start gap-3 rounded-2xl border border-rose-300 bg-rose-50 p-4 text-xs text-rose-900 shadow-xs"
    >
      <AlertCircle class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
      <div class="space-y-1">
        <p class="font-black uppercase tracking-wider text-rose-950">
          Yêu cầu vào Ngân hàng đã bị từ chối
        </p>
        <p class="text-rose-800 leading-relaxed font-medium">
          Lý do từ chối: <strong class="text-rose-950 font-black">"{{ question.bank_submission_note || question.rejection_reason || 'Nội dung chưa đạt tiêu chuẩn' }}"</strong>
        </p>
        <p class="text-[11px] text-rose-700">
          Tác giả cần chỉnh sửa nội dung và gửi lại phiên bản mới để tiếp tục quy trình thẩm định.
        </p>
      </div>
    </div>

    <!-- CONTEXT BANNER 3: QUESTION NÀY ĐÃ TỪNG ĐƯỢC BÁO CÁO (REPORT CONTEXT & AUTOMATION BANNER) -->
    <div
      v-if="question.is_priority || question.review_priority === 'high' || (question.reports && question.reports.length > 0)"
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
                {{ question.reports?.length || 0 }} lượt phản ánh
              </span>
            </div>
            <p class="text-[11px] text-rose-800">
              Phản ánh gần nhất: <strong>{{ formatDate(question.reports?.[0]?.created_at) }}</strong>
            </p>
          </div>
        </div>

        <router-link
          :to="`/admin/reports?question_id=${question.id}`"
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
              v-for="grp in getGroupedReasons(question.reports)"
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
            Khi Admin bấm <strong>Phê duyệt</strong> câu hỏi này, hệ thống sẽ <strong>tự động chuyển tất cả báo cáo sang Đã giải quyết</strong>.
          </p>
        </div>
      </div>
    </div>

    <!-- TABS BAR (if Diff or History is available) -->
    <div
      v-if="hasDiffOrHistory"
      class="flex items-center border-b border-slate-200 bg-white px-4 rounded-2xl shadow-xs gap-3 text-xs font-bold"
    >
      <button
        type="button"
        class="py-3 px-3 border-b-2 transition cursor-pointer flex items-center gap-2"
        :class="activeTab === 'content'
          ? 'border-[#7C3AED] text-[#7C3AED]'
          : 'border-transparent text-slate-500 hover:text-slate-900'"
        @click="activeTab = 'content'"
      >
        <FileText class="h-4 w-4" />
        <span>Nội dung câu hỏi</span>
      </button>

      <button
        v-if="question.previous_revision || (question.history && question.history.length > 1)"
        type="button"
        class="py-3 px-3 border-b-2 transition cursor-pointer flex items-center gap-2"
        :class="activeTab === 'diff'
          ? 'border-[#7C3AED] text-[#7C3AED]'
          : 'border-transparent text-slate-500 hover:text-slate-900'"
        @click="activeTab = 'diff'"
      >
        <GitCompare class="h-4 w-4" />
        <span>Đối chiếu sai khác</span>
      </button>

      <button
        v-if="question.history && question.history.length > 0"
        type="button"
        class="py-3 px-3 border-b-2 transition cursor-pointer flex items-center gap-2"
        :class="activeTab === 'history'
          ? 'border-[#7C3AED] text-[#7C3AED]'
          : 'border-transparent text-slate-500 hover:text-slate-900'"
        @click="activeTab = 'history'"
      >
        <History class="h-4 w-4" />
        <span>Lịch sử các lần gửi ({{ question.history.length }})</span>
      </button>
    </div>

    <!-- Loading -->
    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white py-16 text-center shadow-sm"
    >
      <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-[#7C3AED] border-t-transparent"></div>
      <p class="text-xs font-bold text-slate-500">Đang tải chi tiết câu hỏi #{{ questionId }}...</p>
    </div>

    <!-- Error -->
    <div
      v-else-if="errorMessage"
      class="rounded-2xl border border-rose-200 bg-rose-50 p-6"
    >
      <div class="flex items-start gap-3">
        <AlertTriangle class="h-5 w-5 shrink-0 text-rose-600" />
        <div>
          <p class="text-xs font-bold text-rose-800">{{ errorMessage }}</p>
          <button
            type="button"
            class="mt-3 text-xs font-bold text-rose-700 hover:underline cursor-pointer"
            @click="$router.push('/admin/question-bank')"
          >
            ← Quay lại danh sách câu hỏi
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 1: CONTENT VIEW -->
    <div v-else-if="activeTab === 'content'" class="grid gap-6 lg:grid-cols-12">
      <!-- LEFT COLUMN (Main details) -->
      <div class="space-y-6 lg:col-span-8">
        <!-- Question Content -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
              <FileText class="h-4 w-4" />
              <span>Nội dung câu hỏi</span>
            </span>
            <span class="text-xs font-mono font-bold text-slate-500">ID: #{{ question.id }}</span>
          </div>

          <div class="text-base font-bold leading-relaxed text-slate-900 whitespace-pre-line sm:text-lg">
            {{ question.content }}
          </div>

          <div
            v-if="question.image_url"
            class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
          >
            <img
              :src="question.image_url"
              alt="Question Image"
              class="max-h-80 w-full object-contain p-3"
            />
          </div>
        </article>

        <!-- Answers -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
              <ListChecks class="h-4 w-4" />
              <span>Các phương án đáp án</span>
            </span>
            <span class="text-xs font-medium text-slate-500">
              {{ (question.answers || []).length }} lựa chọn
            </span>
          </div>

          <div class="space-y-2.5">
            <div
              v-for="ans in question.answers"
              :key="ans.id"
              class="flex items-start gap-3 rounded-xl border p-3.5 transition text-xs"
              :class="ans.is_correct
                ? 'border-emerald-300 bg-emerald-50 text-emerald-950 font-bold'
                : 'border-slate-200 bg-slate-50 text-slate-700'"
            >
              <span
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-black"
                :class="ans.is_correct
                  ? 'bg-emerald-600 text-white'
                  : 'border border-slate-200 bg-white text-slate-700'"
              >
                {{ ans.key || ans.answer_key }}
              </span>

              <div class="min-w-0 flex-1 pt-1 leading-relaxed">
                {{ ans.content }}
              </div>

              <div
                v-if="ans.is_correct"
                class="shrink-0 rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-black text-emerald-700 flex items-center gap-1"
              >
                <Check class="h-3 w-3" :stroke-width="3" />
                <span>Đáp án đúng</span>
              </div>
            </div>
          </div>
        </article>

        <!-- Quizzes using this question -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
              <BookOpen class="h-4 w-4" />
              <span>Quiz đang sử dụng câu hỏi này</span>
            </span>
            <span class="text-xs font-bold text-slate-500">
              {{ (question.using_quizzes || []).length }} bài
            </span>
          </div>

          <div v-if="(question.using_quizzes || []).length > 0" class="grid gap-3 sm:grid-cols-2">
            <div
              v-for="quizItem in question.using_quizzes"
              :key="quizItem.id"
              class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3.5 transition hover:border-slate-300"
            >
              <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-bold text-slate-900" :title="quizItem.title">
                  {{ quizItem.title }}
                </p>
                <p class="mt-0.5 text-[11px] text-slate-500">
                  ID: #{{ quizItem.id }} · {{ quizItem.is_public ? 'Công khai' : 'Riêng tư' }}
                </p>
              </div>
              <router-link
                :to="`/admin/quizzes/${quizItem.id}`"
                class="shrink-0 text-xs font-bold text-[#7C3AED] hover:underline"
              >
                Xem →
              </router-link>
            </div>
          </div>

          <div v-else class="py-6 text-center text-xs text-slate-400">
            Câu hỏi này chưa được gắn vào bài quiz nào (thuộc ngân hàng chung).
          </div>
        </article>

        <!-- Reports Tickets (if any) -->
        <article
          v-if="(question.reports || []).length > 0"
          class="rounded-2xl border border-rose-200 bg-rose-50/60 p-6 shadow-sm space-y-4"
        >
          <div class="flex items-center justify-between border-b border-rose-200 pb-3">
            <span class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-rose-800">
              <AlertTriangle class="h-4 w-4 text-rose-600" />
              <span>Danh sách báo cáo vi phạm ({{ question.reports.length }})</span>
            </span>
            <router-link
              :to="`/admin/reports?question_id=${question.id}`"
              class="text-xs font-bold text-rose-700 hover:underline"
            >
              <span>Xem trong Quản lý Báo cáo →</span>
            </router-link>
          </div>

          <div class="space-y-2.5">
            <div
              v-for="rep in question.reports"
              :key="rep.id"
              class="rounded-xl border border-rose-200 bg-white p-3.5 text-xs text-rose-950 space-y-1.5 shadow-2xs"
            >
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-rose-800">Lý do: {{ rep.reason }}</span>
                  <StatusBadge :value="rep.status" />
                </div>
                <span class="text-[11px] text-slate-500">Người báo cáo: <strong>{{ rep.reporter_name }}</strong> • {{ formatDate(rep.created_at) }}</span>
              </div>
              <p v-if="rep.description" class="text-slate-700 italic leading-relaxed bg-slate-50 p-2 rounded-lg">
                "{{ rep.description }}"
              </p>
            </div>
          </div>
        </article>
      </div>

      <!-- RIGHT COLUMN (Taxonomy & Author) -->
      <div class="space-y-6 lg:col-span-4">
        <!-- Classification -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-3.5">
          <h3 class="flex items-center gap-2 border-b border-slate-100 pb-3 text-xs font-black uppercase tracking-wider text-[#7C3AED]">
            <Settings class="h-4 w-4" />
            <span>Phân loại & Cấu hình</span>
          </h3>

          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Độ khó</span>
            <span
              class="rounded-md px-2.5 py-1 text-[11px] font-bold"
              :class="{
                'bg-emerald-50 text-emerald-700': question.difficulty === 'easy',
                'bg-amber-50 text-amber-700': question.difficulty === 'medium',
                'bg-rose-50 text-rose-700': question.difficulty === 'hard'
              }"
            >
              {{ question.difficulty === 'easy' ? 'Dễ' : question.difficulty === 'hard' ? 'Khó' : 'Trung bình' }}
            </span>
          </div>

          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Điểm số</span>
            <span class="font-bold text-slate-900">{{ question.points || 10 }} điểm</span>
          </div>

          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Môn học</span>
            <span class="font-bold text-slate-900">
              {{ question.subject_name || question.subject?.name || 'Chưa phân loại' }}
            </span>
          </div>

          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Khối lớp</span>
            <span class="font-bold text-slate-900">
              {{ question.grade_name || question.grade?.name || 'Chưa phân loại' }}
            </span>
          </div>

          <div v-if="question.topic_name" class="flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Chủ đề</span>
            <span class="font-bold text-[#7C3AED]">{{ question.topic_name }}</span>
          </div>
        </article>

        <!-- Author & Timestamps -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-3.5">
          <h3 class="flex items-center gap-2 border-b border-slate-100 pb-3 text-xs font-black uppercase tracking-wider text-[#7C3AED]">
            <User class="h-4 w-4" />
            <span>Tác giả & Thời gian</span>
          </h3>

          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7C3AED] text-sm font-black text-white">
              {{ (question.author?.name || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-xs font-bold text-slate-900">
                {{ question.author?.name || 'Vô danh' }}
              </p>
              <p class="truncate text-[11px] text-slate-400">
                {{ question.author?.email || 'N/A' }}
              </p>
            </div>
          </div>

          <div class="space-y-2 border-t border-slate-100 pt-3 text-xs">
            <div class="flex justify-between">
              <span class="text-slate-500">Ngày tạo</span>
              <span class="font-medium text-slate-900">{{ formatDate(question.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Cập nhật</span>
              <span class="font-medium text-slate-900">{{ formatDate(question.updated_at) }}</span>
            </div>
          </div>
        </article>

        <!-- Back button -->
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer"
            @click="goBack"
          >
            <ArrowLeft class="h-4 w-4" />
            <span>Quay lại danh sách</span>
          </button>
        </article>
      </div>
    </div>

    <!-- TAB 2: REVISION DIFF VIEW (Comparing Previous vs Current) -->
    <div v-else-if="activeTab === 'diff'" class="space-y-6">
      <div v-if="question.previous_revision" class="space-y-5">
        <div class="flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-2xl p-4 text-xs text-purple-900 font-medium">
          <AlertCircle class="h-4 w-4 text-[#7C3AED] shrink-0" />
          <span>
            Bảng dưới đây đối chiếu chi tiết giữa phiên bản cũ và phiên bản mới.
          </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- LEFT: OLD DATA -->
          <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-rose-200 pb-3">
              <div class="flex items-center gap-2">
                <span class="grid h-6 w-6 place-items-center rounded-lg bg-rose-600 text-white text-[11px] font-black">
                  {{ question.previous_revision.revision_number }}
                </span>
                <h3 class="text-xs font-black text-rose-900 uppercase tracking-wider">
                  DỮ LIỆU CŨ
                </h3>
              </div>
              <span class="rounded-md bg-rose-100 text-rose-800 px-2 py-0.5 text-[10px] font-bold">
                {{ getReviewStatusLabel(question.previous_revision.status) }}
              </span>
            </div>

            <div v-if="question.previous_revision.rejection_reason" class="rounded-xl border border-rose-300 bg-white p-3 text-xs text-rose-900 space-y-1">
              <div class="font-bold flex items-center gap-1 text-rose-700">
                <X class="h-3.5 w-3.5" />
                <span>Lý do từ chối lần trước:</span>
              </div>
              <p class="leading-relaxed font-medium pl-4">
                "{{ question.previous_revision.rejection_reason }}"
              </p>
            </div>

            <!-- Content Old -->
            <div class="space-y-1">
              <span class="text-[11px] font-bold uppercase text-slate-500">Nội dung câu hỏi cũ:</span>
              <div class="rounded-xl border border-rose-200 bg-white p-3.5 text-xs font-semibold text-slate-800 leading-relaxed">
                {{ question.previous_revision.content }}
              </div>
            </div>

            <!-- Answers Old -->
            <div class="space-y-2">
              <span class="text-[11px] font-bold uppercase text-slate-500">Danh sách đáp án cũ:</span>
              <div class="space-y-1.5">
                <div
                  v-for="ans in question.previous_revision.answers"
                  :key="ans.id || ans.key"
                  class="flex items-center gap-2.5 rounded-xl border p-2.5 text-xs"
                  :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700'"
                >
                  <span class="grid h-5 w-5 place-items-center rounded text-[10px] font-bold" :class="ans.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'">
                    {{ ans.key }}
                  </span>
                  <span class="flex-1 truncate">{{ ans.content || ans.text }}</span>
                  <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600">✓ Đúng</span>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT: NEW DATA -->
          <div class="rounded-2xl border border-purple-300 bg-purple-50/40 p-5 space-y-4 shadow-sm">
            <div class="flex items-center justify-between border-b border-purple-200 pb-3">
              <div class="flex items-center gap-2">
                <span class="grid h-6 w-6 place-items-center rounded-lg bg-[#7C3AED] text-white text-[11px] font-black">
                  {{ question.current_revision.revision_number }}
                </span>
                <h3 class="text-xs font-black text-purple-900 uppercase tracking-wider">
                  DỮ LIỆU MỚI
                </h3>
              </div>
              <span class="rounded-md px-2 py-0.5 text-[10px] font-bold" :class="getReviewStatusClass(question.current_revision.status)">
                {{ getReviewStatusLabel(question.current_revision.status) }}
              </span>
            </div>

            <div v-if="question.current_revision.request_note" class="rounded-xl border border-purple-200 bg-white p-3 text-xs text-purple-900 space-y-1">
              <div class="font-bold text-purple-700">Ghi chú của tác giả:</div>
              <p class="leading-relaxed font-medium">"{{ question.current_revision.request_note }}"</p>
            </div>

            <!-- Content New -->
            <div class="space-y-1">
              <span class="text-[11px] font-bold uppercase text-slate-500">Nội dung câu hỏi mới:</span>
              <div
                class="rounded-xl border p-3.5 text-xs font-bold leading-relaxed shadow-xs"
                :class="question.current_revision.content !== question.previous_revision.content
                  ? 'border-purple-400 bg-white text-purple-950 ring-2 ring-purple-400/20'
                  : 'border-purple-200 bg-white text-slate-800'"
              >
                {{ question.current_revision.content }}
              </div>
            </div>

            <!-- Answers New -->
            <div class="space-y-2">
              <span class="text-[11px] font-bold uppercase text-slate-500">Danh sách đáp án mới:</span>
              <div class="space-y-1.5">
                <div
                  v-for="ans in question.current_revision.answers"
                  :key="ans.id || ans.key"
                  class="flex items-center gap-2.5 rounded-xl border p-2.5 text-xs font-semibold shadow-xs"
                  :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700'"
                >
                  <span class="grid h-5 w-5 place-items-center rounded text-[10px] font-bold" :class="ans.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'">
                    {{ ans.key }}
                  </span>
                  <span class="flex-1 truncate">{{ ans.content || ans.text }}</span>
                  <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-600">✓ Đáp án đúng</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Single Revision Diff (First version) -->
      <div v-else class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-xs text-slate-500 space-y-2">
        <p class="font-bold text-slate-700">Đây là phiên bản đầu tiên (Revision #1)</p>
        <p>Câu hỏi này chưa có phiên bản cũ để so sánh.</p>
      </div>
    </div>

    <!-- TAB 3: REVISION HISTORY TIMELINE -->
    <div v-else-if="activeTab === 'history'" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
      <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
        <History class="h-4 w-4 text-[#7C3AED]" />
        <span>Lịch sử các lần gửi thẩm định ({{ question.history?.length || 0 }} lần)</span>
      </h3>

      <div class="space-y-3">
        <div
          v-for="h in question.history"
          :key="h.id"
          class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 space-y-2 text-xs"
        >
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="font-black text-slate-900">Revision #{{ h.revision_number }}</span>
              <span
                class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                :class="getReviewStatusClass(h.status)"
              >
                {{ getReviewStatusLabel(h.status) }}
              </span>
            </div>
            <span class="text-slate-400 text-[11px]">{{ formatDate(h.created_at) }}</span>
          </div>

          <p class="text-slate-800 font-medium">
            "{{ h.content }}"
          </p>

          <div v-if="h.rejection_reason" class="rounded-xl border border-rose-200 bg-white p-3 text-rose-800 font-medium">
            Lý do từ chối: "{{ h.rejection_reason }}"
            <div v-if="h.reviewed_by_name" class="text-[11px] text-slate-400 mt-1">
              Người duyệt: {{ h.reviewed_by_name }} • {{ formatDate(h.reviewed_at) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- REJECT MODAL (MANDATORY REASON) -->
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
            v-model="rejectionReason"
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
            :disabled="!rejectionReason.trim() || isProcessing"
            @click="executeReject"
          >
            <span>{{ isProcessing ? 'Đang gửi...' : 'Xác nhận từ chối' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  AlertCircle,
  AlertTriangle,
  ArrowLeft,
  BookOpen,
  Check,
  CheckCircle,
  ChevronRight,
  Clock,
  FileText,
  GitCompare,
  Globe,
  History,
  ListChecks,
  Lock,
  RotateCcw,
  Settings,
  Trash2,
  User,
  X,
} from 'lucide-vue-next'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { adminBankRequestsApi, adminQuestionsApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const questionId = route.params.id
const question = ref({})
const isLoading = ref(true)
const isProcessing = ref(false)
const errorMessage = ref('')
const activeTab = ref('content')

const isRejectModalOpen = ref(false)
const rejectionReason = ref('')

const hasDiffOrHistory = computed(() => {
  return Boolean(
    question.value.previous_revision ||
    (question.value.history && question.value.history.length > 1) ||
    question.value.bank_submission_status === 'pending'
  )
})

const getReviewStatusClass = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-amber-50 text-amber-800 border border-amber-200'
    case 'approved':
      return 'bg-emerald-50 text-emerald-800 border border-emerald-200'
    case 'rejected':
      return 'bg-rose-50 text-rose-800 border border-rose-200'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

const getReviewStatusLabel = (status) => {
  switch (status) {
    case 'pending':
      return 'Chờ duyệt'
    case 'approved':
      return 'Đã duyệt'
    case 'rejected':
      return 'Đã từ chối'
    case 'superseded':
      return 'Đã thay thế'
    default:
      return 'Chưa gửi'
  }
}

const goBack = () => {
  if (route.query.from === 'reports') {
    router.push('/admin/report-tickets')
  } else if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/admin/question-bank')
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

const fetchQuestionDetail = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await adminQuestionsApi.get(questionId)
    question.value = data || {}
    if (question.value.bank_submission_status === 'pending' && (question.value.previous_revision || (question.value.history && question.value.history.length > 1))) {
      activeTab.value = 'diff'
    }
  } catch (err) {
    errorMessage.value = `Không thể tải câu hỏi #${questionId}: ${err.message}`
  } finally {
    isLoading.value = false
  }
}

const handleApprove = () => {
  const msg = `Bạn có chắc chắn muốn phê duyệt câu hỏi #${questionId} vào Ngân hàng câu hỏi?`
  const executeAction = async () => {
    isProcessing.value = true
    try {
      await adminBankRequestsApi.approve(questionId)
      if (showToast) showToast('Đã phê duyệt câu hỏi vào Ngân hàng thành công.', 'success')
      await fetchQuestionDetail()
    } catch (err) {
      if (showToast) showToast(`Phê duyệt thất bại: ${err.message}`, 'error')
    } finally {
      isProcessing.value = false
    }
  }

  if (showConfirm) {
    showConfirm('Phê duyệt câu hỏi', msg, executeAction)
  } else if (confirm(msg)) {
    executeAction()
  }
}

const openRejectModal = () => {
  rejectionReason.value = ''
  isRejectModalOpen.value = true
}

const executeReject = async () => {
  if (!rejectionReason.value.trim()) return

  isProcessing.value = true
  try {
    await adminBankRequestsApi.reject(questionId, { reason: rejectionReason.value.trim() })
    if (showToast) showToast('Đã từ chối câu hỏi.', 'success')
    isRejectModalOpen.value = false
    await fetchQuestionDetail()
  } catch (err) {
    if (showToast) showToast(`Từ chối thất bại: ${err.message}`, 'error')
  } finally {
    isProcessing.value = false
  }
}

const toggleVisibility = async () => {
  try {
    const updated = await adminQuestionsApi.toggleVisibility(questionId)
    const isPub = Boolean(updated?.data?.is_public ?? updated?.is_public)
    question.value.is_public = isPub
    if (showToast) {
      showToast(
        isPub ? 'Đã công khai câu hỏi thành công.' : 'Đã gỡ công khai câu hỏi.',
        'success'
      )
    }
  } catch (err) {
    if (showToast) showToast(`Cập nhật thất bại: ${err.message}`, 'error')
  }
}

const deleteQuestion = async () => {
  const msg = `Bạn có chắc muốn chuyển câu hỏi #${questionId} vào thùng rác?`
  const executeAction = async () => {
    try {
      await adminQuestionsApi.remove(questionId)
      if (showToast) showToast('Đã chuyển câu hỏi vào thùng rác.', 'success')
      router.push('/admin/question-bank')
    } catch (err) {
      if (showToast) showToast(`Xóa thất bại: ${err.message}`, 'error')
    }
  }

  if (showConfirm) {
    showConfirm('Xóa câu hỏi', msg, executeAction)
  } else if (confirm(msg)) {
    executeAction()
  }
}

const handleRestore = async () => {
  isProcessing.value = true
  try {
    await adminQuestionsApi.restore(questionId)
    if (showToast) showToast('Khôi phục câu hỏi thành công!', 'success')
    await fetchQuestionDetail()
  } catch (err) {
    if (showToast) showToast(`Khôi phục thất bại: ${err.message}`, 'error')
  } finally {
    isProcessing.value = false
  }
}

const handleForceDelete = () => {
  const msg = `Bạn có chắc chắn muốn xóa vĩnh viễn câu hỏi #${questionId}? Dữ liệu sẽ không thể phục hồi.`
  const executeAction = async () => {
    isProcessing.value = true
    try {
      await adminQuestionsApi.forceDelete(questionId)
      if (showToast) showToast('Đã xóa vĩnh viễn câu hỏi.', 'success')
      router.push('/admin/questions-trash')
    } catch (err) {
      if (showToast) showToast(`Xóa thất bại: ${err.message}`, 'error')
    } finally {
      isProcessing.value = false
    }
  }

  if (showConfirm) {
    showConfirm('Xóa vĩnh viễn', msg, executeAction)
  } else if (confirm(msg)) {
    executeAction()
  }
}

onMounted(() => {
  fetchQuestionDetail()
})
</script>