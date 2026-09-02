<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Loading State -->
    <div v-if="loading" class="card p-12 text-center text-xs text-slate-500 font-bold flex flex-col items-center justify-center gap-3">
      <div class="h-8 w-8 animate-spin rounded-full border-3 border-[#7C3AED] border-t-transparent"></div>
      <span>Đang tải thông tin chi tiết bài Quiz...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="card p-10 text-center space-y-3">
      <AlertCircle class="mx-auto h-8 w-8 text-rose-500" />
      <p class="text-sm font-bold text-slate-800">{{ error }}</p>
      <button type="button" class="btn-secondary text-xs px-4 py-2" @click="fetchQuizDetail">Thử lại</button>
    </div>

    <template v-else-if="quiz">
      <!-- HEADER -->
      <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="space-y-1.5 min-w-0">
          <div class="flex items-center gap-2 flex-wrap text-xs font-bold">
            <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-[11px] text-[#7C3AED]">
              Quiz #{{ quiz.id }}
            </span>
            <span
              class="rounded-full px-2.5 py-0.5 text-[11px] uppercase font-black"
              :class="reviewStatusClass"
            >
              {{ reviewStatusLabel }}
            </span>
            <span
              class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold"
              :class="quiz.is_public ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'"
            >
              <component :is="quiz.is_public ? Globe : Lock" class="h-3 w-3" />
              <span>{{ quiz.is_public ? 'Công khai' : 'Riêng tư' }}</span>
            </span>
            <span v-if="currentRevision?.revision_number" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-900">
              Lần gửi #{{ currentRevision.revision_number }}
            </span>
          </div>

          <h1 class="text-2xl font-black text-slate-900 sm:text-3xl truncate">{{ quiz.title }}</h1>
          <p class="text-xs text-slate-600 max-w-3xl leading-relaxed">{{ quiz.description || 'Không có mô tả cho bộ Quiz này.' }}</p>
        </div>

        <!-- HEADER ACTIONS -->
        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
          <button type="button" class="btn-secondary text-xs px-3.5 py-2 flex items-center gap-1.5" @click="goBack">
            <ArrowLeft class="h-3.5 w-3.5" />
            <span>Quay lại</span>
          </button>

          <!-- PENDING CONTEXT ACTIONS -->
          <template v-if="quiz.review_status === 'pending_review'">
            <button
              type="button"
              class="btn-secondary text-xs px-4 py-2 text-rose-600 hover:bg-rose-50 border-rose-200 flex items-center gap-1.5 shadow-xs font-bold"
              :disabled="isProcessing"
              @click="openRejectModal"
            >
              <X class="h-3.5 w-3.5" />
              <span>Từ chối</span>
            </button>

            <button
              type="button"
              class="btn-primary text-xs px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5 shadow-xs font-bold"
              :disabled="isProcessing"
              @click="handleApproveQuiz"
            >
              <Check class="h-3.5 w-3.5" />
              <span>Phê duyệt công khai</span>
            </button>
          </template>

          <!-- APPROVED CONTEXT ACTIONS -->
          <template v-else-if="quiz.review_status === 'approved' || (!quiz.review_status && quiz.is_public)">
            <button
              type="button"
              class="btn-secondary text-xs px-4 py-2 flex items-center gap-1.5 font-bold shadow-xs"
              :class="quiz.is_public ? 'text-amber-700 hover:bg-amber-50 border-amber-200' : 'text-emerald-700 hover:bg-emerald-50 border-emerald-200'"
              :disabled="isProcessing"
              @click="toggleVisibility"
            >
              <component :is="quiz.is_public ? Lock : Globe" class="h-3.5 w-3.5" />
              <span>{{ quiz.is_public ? 'Gỡ công khai (Ẩn)' : 'Công khai lại' }}</span>
            </button>
          </template>
        </div>
      </div>

      <!-- BANNER THEO TRẠNG THÁI -->
      <!-- 1. Pending Banner -->
      <div v-if="quiz.review_status === 'pending_review'" class="flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50/90 p-4 text-xs font-bold text-amber-950 shadow-xs">
        <Clock class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
        <div class="grid gap-0.5">
          <span class="text-amber-900 font-extrabold text-sm">
            Bài Quiz đang chờ thẩm định công khai (Lần gửi duyệt #{{ currentRevision?.revision_number || 1 }})
          </span>
          <span class="text-[11px] font-medium text-amber-800">
            Hãy kiểm tra kỹ nội dung, câu hỏi và đáp án trước khi duyệt. Khi duyệt, toàn bộ câu hỏi từ kho cá nhân sẽ được tự động tạo bản sao vào Ngân hàng câu hỏi.
          </span>
        </div>
      </div>

      <!-- 2. Rejected Banner -->
      <div v-else-if="quiz.review_status === 'rejected'" class="flex items-start gap-3 rounded-2xl border border-rose-300 bg-rose-50/90 p-4 text-xs font-bold text-rose-950 shadow-xs">
        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
        <div class="grid gap-0.5">
          <span class="text-rose-900 font-extrabold text-sm">
            Yêu cầu công khai bài Quiz này đã bị từ chối
          </span>
          <span class="text-xs font-semibold text-rose-800">
            Lý do từ chối: "{{ quiz.rejection_reason || previousRejectionReason || 'Nội dung chưa đạt chuẩn công khai' }}"
          </span>
          <span class="text-[11px] font-normal text-rose-700 mt-1">
            Tác giả cần cập nhật chỉnh sửa bài Quiz và gửi lại yêu cầu duyệt để kích hoạt quy trình thẩm định mới.
          </span>
        </div>
      </div>

      <!-- 3. Approved Banner -->
      <div v-else-if="quiz.review_status === 'approved'" class="flex items-start gap-3 rounded-2xl border border-emerald-300 bg-emerald-50/90 p-4 text-xs font-bold text-emerald-950 shadow-xs">
        <Check class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
        <div class="grid gap-0.5">
          <span class="text-emerald-900 font-extrabold text-sm">
            Bài Quiz đã được phê duyệt công khai vào hệ thống
          </span>
          <span class="text-[11px] font-medium text-emerald-800">
            Học sinh và giáo viên toàn hệ thống có thể tìm kiếm, tham gia luyện tập và sử dụng trong các phòng thi đấu.
          </span>
        </div>
      </div>

      <!-- STATS KPI GRID -->
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- 1. Tác giả -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
          <span class="block text-[10px] font-bold uppercase text-slate-400">Tác giả bài Quiz</span>
          <p class="mt-1 text-sm font-extrabold text-slate-900 truncate">{{ quiz.user?.name || 'Chưa rõ' }}</p>
          <p class="text-[11px] text-slate-500 font-medium truncate">{{ quiz.user?.email || 'N/A' }}</p>
        </div>

        <!-- 2. Phân loại -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
          <span class="block text-[10px] font-bold uppercase text-slate-400">Môn học & Khối lớp</span>
          <p class="mt-1 text-sm font-extrabold text-[#7C3AED] truncate">
            {{ quiz.subject?.name || quiz.subject_name || 'Chưa chọn môn' }}
          </p>
          <p class="text-[11px] text-slate-500 font-medium truncate">
            {{ quiz.grade?.name || 'Khối --' }} • {{ quiz.education_level?.name || quiz.educationLevel?.name || 'Cấp học --' }}
          </p>
        </div>

        <!-- 3. Số câu & Thời gian -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
          <span class="block text-[10px] font-bold uppercase text-slate-400">Quy mô đề thi</span>
          <p class="mt-1 text-sm font-extrabold text-slate-900">
            {{ quiz.questions?.length ?? quiz.questions_count ?? 0 }} câu hỏi
          </p>
          <p class="text-[11px] text-slate-500 font-medium">
            {{ quiz.time_limit_minutes ? `${quiz.time_limit_minutes} phút` : 'Không giới hạn thời gian' }}
          </p>
        </div>

        <!-- 4. Hiệu suất -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
          <span class="block text-[10px] font-bold uppercase text-slate-400">Lượt làm & Điểm TB</span>
          <p class="mt-1 text-sm font-extrabold text-emerald-600">
            {{ quiz.attempts_count ?? quiz.attempts?.length ?? 0 }} lượt làm
          </p>
          <p class="text-[11px] text-slate-500 font-medium">
            Điểm trung bình: <b class="text-[#7C3AED]">{{ averageScore }}%</b>
          </p>
        </div>
      </div>

      <!-- TABS NAVIGATION -->
      <div class="flex items-center border-b border-slate-200 bg-white px-4 rounded-2xl shadow-xs overflow-x-auto gap-2 sm:gap-4 text-xs font-bold">
        <button
          type="button"
          class="py-3.5 px-3 border-b-2 transition flex items-center gap-2 cursor-pointer whitespace-nowrap"
          :class="activeTab === 'questions' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
          @click="activeTab = 'questions'"
        >
          <HelpCircle class="h-4 w-4" />
          <span>Danh sách câu hỏi ({{ quiz.questions?.length ?? 0 }})</span>
        </button>

        <button
          v-if="diffData || currentRevision"
          type="button"
          class="py-3.5 px-3 border-b-2 transition flex items-center gap-2 cursor-pointer whitespace-nowrap"
          :class="activeTab === 'diff' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
          @click="activeTab = 'diff'"
        >
          <GitCompare class="h-4 w-4" />
          <span>Đối chiếu sai khác (Diff)</span>
          <span v-if="hasDiffChanges" class="rounded-full bg-purple-100 px-1.5 py-0.5 text-[10px] text-[#7C3AED]">
            Có thay đổi
          </span>
        </button>

        <button
          v-if="historyList.length > 0"
          type="button"
          class="py-3.5 px-3 border-b-2 transition flex items-center gap-2 cursor-pointer whitespace-nowrap"
          :class="activeTab === 'history' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
          @click="activeTab = 'history'"
        >
          <History class="h-4 w-4" />
          <span>Lịch sử các lần gửi ({{ historyList.length }})</span>
        </button>

        <button
          type="button"
          class="py-3.5 px-3 border-b-2 transition flex items-center gap-2 cursor-pointer whitespace-nowrap"
          :class="activeTab === 'attempts' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
          @click="activeTab = 'attempts'"
        >
          <BarChart2 class="h-4 w-4" />
          <span>Lượt làm bài ({{ quiz.attempts?.length ?? 0 }})</span>
        </button>
      </div>

      <!-- TAB 1: DANH SÁCH CÂU HỎI -->
      <article v-if="activeTab === 'questions'" class="card p-6 space-y-4 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h2 class="text-sm font-bold text-slate-900">Chi tiết các câu hỏi trong đề thi</h2>
          <span class="text-xs text-slate-500 font-medium">Tổng {{ quiz.questions?.length ?? 0 }} câu</span>
        </div>

        <div class="space-y-4">
          <div
            v-for="(question, index) in quiz.questions"
            :key="question.id || index"
            class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3 transition hover:bg-slate-50 hover:border-slate-300"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <span class="text-[11px] font-black text-[#7C3AED] uppercase">Câu {{ index + 1 }}</span>
                <p class="text-xs font-bold text-slate-900 leading-relaxed">{{ question.content }}</p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span
                  class="rounded-lg px-2 py-0.5 text-[10px] font-bold"
                  :class="question.is_public ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800'"
                >
                  {{ question.is_public ? 'Bank Public' : 'Kho cá nhân' }}
                </span>
                <span class="rounded bg-white border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                  {{ question.points ?? 10 }} pts
                </span>
              </div>
            </div>

            <!-- Answers Grid -->
            <div class="grid gap-2 sm:grid-cols-2">
              <div
                v-for="answer in question.answers"
                :key="answer.id || answer.key"
                class="flex items-center justify-between p-2.5 rounded-lg border text-xs transition"
                :class="answer.is_correct
                  ? 'bg-emerald-50 border-emerald-200 text-emerald-900 font-bold shadow-xs'
                  : 'bg-white border-slate-200 text-slate-700'"
              >
                <div class="flex items-center gap-2">
                  <span
                    class="grid h-5 w-5 place-items-center rounded text-[10px] font-black"
                    :class="answer.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'"
                  >
                    {{ answer.key || '•' }}
                  </span>
                  <span>{{ answer.content }}</span>
                </div>
                <span v-if="answer.is_correct" class="text-[10px] font-black text-emerald-700 uppercase">
                  ✓ Đúng
                </span>
              </div>
            </div>
          </div>

          <div v-if="!quiz.questions?.length" class="py-12 text-center text-xs font-bold text-slate-400">
            Chưa có câu hỏi nào trong bộ đề này.
          </div>
        </div>
      </article>

      <!-- TAB 2: ĐỐI CHIẾU SAI KHÁC (DIFF) -->
      <article v-else-if="activeTab === 'diff'" class="card p-6 space-y-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h2 class="text-sm font-bold text-slate-900">Đối chiếu sai khác giữa các phiên bản</h2>
            <p class="text-xs text-slate-500">
              So sánh Snapshot lần gửi <b>#{{ currentRevision?.revision_number || 1 }}</b> với
              <template v-if="previousRevision?.revision_number">
                lần gửi <b>#{{ previousRevision.revision_number }}</b> (bị từ chối trước đó)
              </template>
              <template v-else>
                (Phiên bản khởi tạo đầu tiên)
              </template>
            </p>
          </div>
        </div>

        <!-- Metadata changes diff -->
        <div v-if="diffData?.changes && Object.keys(diffData.changes).length > 0" class="rounded-xl border border-purple-200 bg-purple-50/50 p-4 space-y-3">
          <h3 class="text-xs font-black text-[#7C3AED] uppercase">Thay đổi cấu hình tổng quan</h3>
          <div class="grid gap-2 sm:grid-cols-2 text-xs">
            <div
              v-for="(change, key) in diffData.changes"
              :key="key"
              class="rounded-lg border border-purple-100 bg-white p-3 space-y-1 shadow-xs"
            >
              <span class="block text-[10px] font-bold text-slate-400 uppercase">{{ change.label }}</span>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="line-through text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded font-medium">{{ change.old || '(Trống)' }}</span>
                <span class="text-slate-400">→</span>
                <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-bold">{{ change.new || '(Trống)' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Questions Summary KPI -->
        <div v-if="diffData?.questions_summary" class="grid grid-cols-2 gap-3 sm:grid-cols-4 text-center">
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
            <b class="block text-xl font-black text-emerald-700">+{{ diffData.questions_summary.added_count }}</b>
            <span class="text-[10px] font-bold uppercase text-emerald-800">Thêm mới</span>
          </div>
          <div class="rounded-xl border border-rose-200 bg-rose-50 p-3">
            <b class="block text-xl font-black text-rose-700">-{{ diffData.questions_summary.removed_count }}</b>
            <span class="text-[10px] font-bold uppercase text-rose-800">Bị xóa</span>
          </div>
          <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
            <b class="block text-xl font-black text-amber-700">~{{ diffData.questions_summary.modified_count }}</b>
            <span class="text-[10px] font-bold uppercase text-amber-800">Chỉnh sửa</span>
          </div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <b class="block text-xl font-black text-slate-700">{{ diffData.questions_summary.unchanged_count }}</b>
            <span class="text-[10px] font-bold uppercase text-slate-500">Không đổi</span>
          </div>
        </div>

        <!-- Questions Diff List -->
        <div v-if="diffData?.question_diffs?.length" class="space-y-4">
          <h3 class="text-xs font-bold text-slate-900 border-b border-slate-100 pb-2">Chi tiết từng câu hỏi đối chiếu</h3>
          <div
            v-for="(qDiff, idx) in diffData.question_diffs"
            :key="idx"
            class="rounded-xl border p-4 space-y-3"
            :class="{
              'border-emerald-200 bg-emerald-50/40': qDiff.status === 'added',
              'border-rose-200 bg-rose-50/40': qDiff.status === 'removed',
              'border-amber-200 bg-amber-50/40': qDiff.status === 'modified',
              'border-slate-200 bg-slate-50/40': qDiff.status === 'unchanged',
            }"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-900">
                Câu hỏi #{{ idx + 1 }}: {{ qDiff.current?.content || qDiff.previous?.content }}
              </span>
              <span
                class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase"
                :class="{
                  'bg-emerald-100 text-emerald-800': qDiff.status === 'added',
                  'bg-rose-100 text-rose-800': qDiff.status === 'removed',
                  'bg-amber-100 text-amber-800': qDiff.status === 'modified',
                  'bg-slate-100 text-slate-700': qDiff.status === 'unchanged',
                }"
              >
                {{ qDiff.status }}
              </span>
            </div>

            <!-- Modified field diffs -->
            <div v-if="qDiff.field_changes && Object.keys(qDiff.field_changes).length" class="text-xs space-y-1 bg-white p-3 rounded-lg border border-amber-200">
              <div v-for="(fChange, fKey) in qDiff.field_changes" :key="fKey" class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-slate-500">{{ fChange.label }}:</span>
                <span class="line-through text-rose-600 bg-rose-50 px-1 rounded">{{ fChange.old }}</span>
                <span>→</span>
                <span class="text-emerald-700 bg-emerald-50 px-1 rounded font-bold">{{ fChange.new }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="py-8 text-center text-xs font-medium text-slate-500">
          Chưa có dữ liệu sai khác phiên bản chi tiết cho bài Quiz này.
        </div>
      </article>

      <!-- TAB 3: LỊCH SỬ CÁC LẦN GỬI (HISTORY) -->
      <article v-else-if="activeTab === 'history'" class="card p-6 space-y-4 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Lịch sử xét duyệt qua các phiên bản</h2>

        <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
          <div
            v-for="item in historyList"
            :key="item.id"
            class="relative space-y-1 text-xs"
          >
            <div
              class="absolute -left-6 top-1 h-3.5 w-3.5 rounded-full border-2 border-white ring-2"
              :class="item.status === 'approved' ? 'bg-emerald-500 ring-emerald-200' : (item.status === 'rejected' ? 'bg-rose-500 ring-rose-200' : 'bg-amber-500 ring-amber-200')"
            ></div>

            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-black text-slate-900">Lần gửi duyệt #{{ item.revision_number }}</span>
              <span
                class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                :class="item.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (item.status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')"
              >
                {{ item.status }}
              </span>
              <span class="text-slate-400 text-[11px]">{{ new Date(item.created_at).toLocaleString('vi-VN') }}</span>
            </div>

            <p v-if="item.request_note" class="text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100">
              Ghi chú của tác giả: "{{ item.request_note }}"
            </p>

            <p v-if="item.rejection_reason" class="text-rose-700 bg-rose-50 p-2 rounded-lg border border-rose-100 font-medium">
              Lý do từ chối của Admin: "{{ item.rejection_reason }}"
            </p>
          </div>
        </div>
      </article>

      <!-- TAB 4: LƯỢT LÀM BÀI (ATTEMPTS) -->
      <article v-else-if="activeTab === 'attempts'" class="card p-6 space-y-4 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Lịch sử các lượt làm bài</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
                <th class="py-2.5 px-3">Người dùng</th>
                <th class="py-2.5 px-3 text-center">Điểm</th>
                <th class="py-2.5 px-3 text-center">Trạng thái</th>
                <th class="py-2.5 px-3 text-right">Thời gian</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="attempt in quiz.attempts" :key="attempt.id" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 font-bold text-slate-900">{{ attempt.user?.name || 'Khách' }}</td>
                <td class="py-2.5 px-3 text-center font-bold text-[#7C3AED]">{{ attempt.score }}%</td>
                <td class="py-2.5 px-3 text-center">
                  <span
                    class="rounded px-2 py-0.5 text-[10px] font-bold"
                    :class="attempt.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                  >
                    {{ attempt.status === 'completed' ? 'Hoàn thành' : 'Đang làm' }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right text-slate-400 text-[11px]">{{ new Date(attempt.created_at).toLocaleString('vi-VN') }}</td>
              </tr>
              <tr v-if="!quiz.attempts?.length">
                <td colspan="4" class="py-6 text-center text-slate-400 text-xs">Chưa có ai tham gia làm bài thi này.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </template>

    <!-- REJECT REASON MODAL -->
    <div
      v-if="isRejectModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn"
      @click.self="isRejectModalOpen = false"
    >
      <div class="card p-6 max-w-md w-full space-y-4 shadow-2xl animate-scaleUp">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-black text-rose-600 flex items-center gap-2">
            <AlertCircle class="h-5 w-5" />
            <span>Từ chối phê duyệt Quiz</span>
          </h3>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-sm font-bold cursor-pointer"
            @click="isRejectModalOpen = false"
          >
            ✕
          </button>
        </div>

        <p class="text-xs leading-relaxed text-slate-600">
          Vui lòng nhập lý do từ chối cụ thể để tác giả biết cần chỉnh sửa điểm nào trong bài Quiz.
        </p>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Lý do từ chối <span class="text-rose-500">*</span></label>
          <textarea
            v-model="rejectionReason"
            rows="4"
            class="w-full rounded-xl border border-slate-200 p-3 text-xs outline-none focus:border-rose-500 resize-none font-medium text-slate-900"
            placeholder="Ví dụ: Câu hỏi số 3 sai chính tả, chưa chọn môn học phù hợp..."
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
            class="btn-primary text-xs px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white flex items-center gap-1.5 shadow-xs font-bold"
            :disabled="isProcessing || !rejectionReason.trim()"
            @click="handleRejectQuiz"
          >
            <span>{{ isProcessing ? 'Đang xử lý...' : 'Xác nhận từ chối' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import QuestionImage from '@/components/question/QuestionImage.vue'
import {
  AlertCircle,
  ArrowLeft,
  BarChart2,
  Check,
  Clock,
  GitCompare,
  Globe,
  HelpCircle,
  History,
  Lock,
  X,
} from 'lucide-vue-next'
import api, { adminQuizzesApi, quizReviewApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const quiz = ref(null)
const loading = ref(false)
const error = ref('')
const isProcessing = ref(false)
const averageScore = ref(0)
const activeTab = ref('questions')

// Diff & History state
const currentRevision = ref(null)
const previousRevision = ref(null)
const previousRejectionReason = ref('')
const diffData = ref(null)
const historyList = ref([])

// Reject modal
const isRejectModalOpen = ref(false)
const rejectionReason = ref('')

const reviewStatusLabel = computed(() => {
  const st = quiz.value?.review_status
  if (st === 'approved') return 'Đã duyệt công khai'
  if (st === 'pending_review') return 'Chờ thẩm định'
  if (st === 'rejected') return 'Bị từ chối'
  return 'Bản nháp'
})

const reviewStatusClass = computed(() => {
  const st = quiz.value?.review_status
  if (st === 'approved') return 'bg-emerald-100 text-emerald-800'
  if (st === 'pending_review') return 'bg-amber-100 text-amber-800'
  if (st === 'rejected') return 'bg-rose-100 text-rose-800'
  return 'bg-slate-100 text-slate-700'
})

const hasDiffChanges = computed(() => {
  if (!diffData.value) return false
  const changesCount = Object.keys(diffData.value.changes || {}).length
  const qSummary = diffData.value.questions_summary
  return changesCount > 0 || (qSummary && (qSummary.added_count > 0 || qSummary.removed_count > 0 || qSummary.modified_count > 0))
})

const goBack = () => {
  if (route.query.from === 'reports') {
    router.push('/admin/reports')
  } else if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/admin/quizzes')
  }
}

const fetchQuizDetail = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get(`/admin/quizzes/${route.params.id}`)
    const payload = res.data?.data || res.data
    quiz.value = payload.quiz || payload
    averageScore.value = payload.average_score ?? 0
    currentRevision.value = payload.current_revision || null
    previousRevision.value = payload.previous_revision || null
    previousRejectionReason.value = payload.previous_rejection_reason || ''
    diffData.value = payload.diff || null
    historyList.value = payload.history || []

    if (quiz.value?.review_status === 'pending_review' && (diffData.value || currentRevision.value)) {
      activeTab.value = 'diff'
    }
  } catch (err) {
    console.error('Lỗi khi tải chi tiết Quiz:', err)
    error.value = err.response?.data?.message || err.message || 'Không thể tải chi tiết bài Quiz.'
  } finally {
    loading.value = false
  }
}

const handleApproveQuiz = () => {
  const title = quiz.value?.title || ''
  const msg = `Bạn có chắc chắn muốn phê duyệt và công khai bài Quiz "${title}"? Toàn bộ câu hỏi từ kho cá nhân của tác giả sẽ được tạo snapshot vào Ngân hàng câu hỏi.`

  if (showConfirm) {
    showConfirm('Xác nhận phê duyệt Quiz', msg, async () => {
      await executeApproveQuiz()
    })
  } else {
    executeApproveQuiz()
  }
}

const executeApproveQuiz = async () => {
  isProcessing.value = true
  try {
    const res = await quizReviewApi.adminApprove(quiz.value.id)
    if (showToast) showToast(res.message || 'Phê duyệt bài Quiz thành công!', 'success')
    await fetchQuizDetail()
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Phê duyệt thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

const openRejectModal = () => {
  rejectionReason.value = ''
  isRejectModalOpen.value = true
}

const handleRejectQuiz = async () => {
  if (!rejectionReason.value.trim()) return

  isProcessing.value = true
  try {
    const res = await quizReviewApi.adminReject(quiz.value.id, rejectionReason.value.trim())
    if (showToast) showToast(res.message || 'Đã từ chối bài Quiz thành công!', 'success')
    isRejectModalOpen.value = false
    await fetchQuizDetail()
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Từ chối thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

const toggleVisibility = async () => {
  isProcessing.value = true
  try {
    const res = await adminQuizzesApi.toggleVisibility(quiz.value.id)
    if (showToast) showToast(res.message || 'Cập nhật trạng thái hiển thị thành công!', 'success')
    await fetchQuizDetail()
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Cập nhật thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

onMounted(() => {
  fetchQuizDetail()
})
</script>
