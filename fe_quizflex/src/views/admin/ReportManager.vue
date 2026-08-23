<template>
  <section class="space-y-4">
    <!-- 1. HERO HEADER CARD & KPI STATS -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3.5 min-w-0">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
            <AlertTriangle class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-slate-900 truncate">
              Quản lý Báo cáo
            </h1>
            <p class="text-xs text-slate-500 leading-relaxed truncate sm:whitespace-normal mt-0.5">
              Xử lý các báo cáo vi phạm, sai sót bài Quiz và câu hỏi từ cộng đồng.
            </p>
          </div>
        </div>

        <button
          type="button"
          class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer shadow-2xs"
          @click="refreshAllData"
        >
          <RefreshCw class="h-3.5 w-3.5 text-purple-600" :class="{ 'animate-spin': isLoading }" />
          <span>Làm mới dữ liệu</span>
        </button>
      </div>

      <!-- 2 KPI STATS GRID -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 flex items-center gap-3.5">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
            <BookOpen class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-xs font-medium text-slate-500 truncate">Quiz bị báo cáo</p>
            <p class="text-2xl font-bold text-slate-900 leading-tight">{{ quizPendingCount }}</p>
          </div>
        </div>

        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 flex items-center gap-3.5">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
            <FileText class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-xs font-medium text-slate-500 truncate">Câu hỏi bị báo cáo</p>
            <p class="text-2xl font-bold text-slate-900 leading-tight">{{ questionPendingCount }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. PRIMARY TAB NAVIGATION BAR -->
    <div class="rounded-2xl border border-slate-200 bg-white px-4 shadow-2xs flex items-center gap-6 overflow-x-auto text-sm font-semibold">
      <button
        type="button"
        class="inline-flex items-center gap-2 py-3.5 border-b-2 transition cursor-pointer whitespace-nowrap"
        :class="reportSubTab === 'quiz'
          ? 'border-[#7C3AED] text-[#7C3AED] font-bold'
          : 'border-transparent text-slate-600 hover:text-slate-900'"
        @click="reportSubTab = 'quiz'"
      >
        <BookOpen :size="16" />
        <span>Báo cáo Bài Quiz</span>
        <span
          class="rounded-full px-2 py-0.5 text-[11px] font-bold"
          :class="reportSubTab === 'quiz' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'"
        >
          {{ quizPendingCount }}
        </span>
      </button>

      <button
        type="button"
        class="inline-flex items-center gap-2 py-3.5 border-b-2 transition cursor-pointer whitespace-nowrap"
        :class="reportSubTab === 'question'
          ? 'border-[#7C3AED] text-[#7C3AED] font-bold'
          : 'border-transparent text-slate-600 hover:text-slate-900'"
        @click="reportSubTab = 'question'"
      >
        <FileText :size="16" />
        <span>Báo cáo Câu hỏi</span>
        <span
          class="rounded-full px-2 py-0.5 text-[11px] font-bold"
          :class="reportSubTab === 'question' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600'"
        >
          {{ questionPendingCount }}
        </span>
      </button>
    </div>

    <!-- ================================================================================== -->
    <!-- SECTION: QUẢN LÝ BÁO CÁO VI PHẠM (VIOLATION REPORTS) -->
    <!-- ================================================================================== -->
    <div v-if="mainSection === 'reports'" class="space-y-4">
      
      <!-- SUB-TAB 1: QUIZ REPORTS TABLE -->
      <div v-if="reportSubTab === 'quiz'" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-2xs">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                <th class="w-28 py-2.5 px-3.5 text-center">Trạng thái</th>
                <th class="py-2.5 px-3.5 min-w-[240px]">Quiz bị báo cáo</th>
                <th class="py-2.5 px-3.5 text-center">Lý do</th>
                <th class="py-2.5 px-3.5">Chi tiết thêm</th>
                <th class="min-w-[260px] py-2.5 px-3.5 text-center">Thao tác Admin</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="report in quizReports" :key="report.id" class="transition hover:bg-slate-50/70">
                <td class="py-3 px-3.5 text-center">
                  <span :class="getStatusBadge(report.status)">
                    {{ getStatusText(report.status) }}
                  </span>
                </td>

                <td class="py-3 px-3.5">
                  <router-link :to="`/admin/quizzes/${report.quiz_id}`" class="font-semibold text-slate-900 hover:text-indigo-600 transition line-clamp-1">
                    {{ report.quiz?.title || `Quiz #${report.quiz_id}` }}
                  </router-link>
                  <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span>Tác giả: <b class="text-slate-700 font-medium">{{ report.quiz?.user?.name || 'Vô danh' }}</b></span>
                    <span
                      class="rounded-full px-2 py-0.2 text-[10px] font-medium border"
                      :class="Boolean(report.quiz?.is_public) ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                    >
                      {{ Boolean(report.quiz?.is_public) ? 'Công khai' : 'Riêng tư' }}
                    </span>
                  </div>
                </td>

                <td class="py-3 px-3.5 text-center">
                  <span class="font-semibold text-rose-700 text-xs">{{ report.reason }}</span>
                </td>

                <td class="max-w-xs truncate py-3 px-3.5 text-xs text-slate-500" :title="report.description">
                  {{ report.description || '—' }}
                </td>

                <td class="py-3 px-3.5 text-center whitespace-nowrap space-x-1.5">
                  <router-link
                    :to="`/admin/quizzes/${report.quiz_id}?from=reports`"
                    class="inline-flex items-center gap-1 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60 px-2.5 py-1 text-xs font-semibold transition"
                  >
                    <Eye class="h-3.5 w-3.5" />
                    Xem
                  </router-link>

                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition"
                    @click="toggleQuizVisibility(report)"
                  >
                    <component :is="Boolean(report.quiz?.is_public) ? Lock : Unlock" class="h-3.5 w-3.5 text-slate-500" />
                    {{ Boolean(report.quiz?.is_public) ? 'Gỡ công khai' : 'Công khai lại' }}
                  </button>

                  <template v-if="report.status === 'pending'">
                    <button type="button" class="text-xs font-semibold text-emerald-600 hover:underline px-1" @click="updateStatus(report.id, 'resolved')">Duyệt</button>
                    <button type="button" class="text-xs font-medium text-slate-500 hover:underline px-1" @click="updateStatus(report.id, 'dismissed')">Bỏ qua</button>
                  </template>
                  <template v-else>
                    <button type="button" class="text-xs font-medium text-slate-500 hover:underline px-1" @click="updateStatus(report.id, 'pending')">Đổi lại</button>
                  </template>

                  <button type="button" class="text-xs font-semibold text-rose-600 hover:underline px-1" @click="deleteQuiz(report.quiz_id, report.id)">Xóa</button>
                </td>
              </tr>

              <tr v-if="!isLoading && quizReports.length === 0">
                <td colspan="5" class="py-12 text-center text-xs text-slate-400">Không có báo cáo vi phạm bài Quiz nào</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SUB-TAB 2: QUESTION REPORTS TABLE -->
      <div v-if="reportSubTab === 'question'" class="space-y-3">
        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-2xs">
          <!-- TABLE HEADER VỚI BỘ LỌC ĐÍNH CHÍNH TÍCH HỢP TRỰC TIẾP -->
          <div class="border-b border-slate-200/80 bg-slate-50/70 p-3 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="inline-flex items-center gap-1 rounded-xl bg-slate-200/60 p-1 text-xs font-semibold">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition cursor-pointer"
                :class="questionFilterTab === 'updated'
                  ? 'bg-white text-emerald-700 font-bold border border-slate-200/80 shadow-2xs'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                @click="questionFilterTab = 'updated'"
              >
                <CheckCircle2 :size="13" class="text-emerald-600" />
                <span>Đã đính chính</span>
                <span class="rounded-full px-2 py-0.2 text-[10px] font-bold bg-emerald-100 text-emerald-800">
                  {{ questionUpdatedCount }}
                </span>
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition cursor-pointer"
                :class="questionFilterTab === 'pending'
                  ? 'bg-white text-rose-700 font-bold border border-slate-200/80 shadow-2xs'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                @click="questionFilterTab = 'pending'"
              >
                <AlertTriangle :size="13" class="text-rose-600" />
                <span>Chưa đính chính</span>
                <span class="rounded-full px-2 py-0.2 text-[10px] font-bold bg-rose-100 text-rose-800">
                  {{ questionNotUpdatedCount }}
                </span>
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition cursor-pointer"
                :class="questionFilterTab === 'all'
                  ? 'bg-white text-indigo-700 font-bold border border-slate-200/80 shadow-2xs'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                @click="questionFilterTab = 'all'"
              >
                <FileText :size="13" class="text-indigo-600" />
                <span>Tất cả</span>
                <span class="rounded-full px-2 py-0.2 text-[10px] font-bold bg-slate-200/70 text-slate-700">
                  {{ questionReports.length }}
                </span>
              </button>
            </div>

            <div class="text-xs font-medium text-slate-500">
              Tổng số báo cáo: <b class="text-slate-900 font-bold">{{ questionReports.length }}</b>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                  <th class="w-36 py-2.5 px-3.5 text-center">Trạng thái</th>
                  <th class="min-w-[280px] py-2.5 px-3.5">Câu hỏi bị báo cáo</th>
                  <th class="w-32 py-2.5 px-3.5 text-center">Tác giả</th>
                  <th class="py-2.5 px-3.5 min-w-[200px]">Lý do & Mô tả</th>
                  <th class="min-w-[280px] py-2.5 px-3.5 text-center">Thao tác Admin</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="report in filteredQuestionReports" :key="report.id" class="transition hover:bg-slate-50/70">
                  <td class="py-3 px-3.5 text-center">
                    <div class="flex flex-col items-center gap-1">
                      <span :class="getStatusBadge(report.status)">
                        {{ getStatusText(report.status) }}
                      </span>
                      <span
                        v-if="report.has_author_updated || report.question?.has_author_updated"
                        class="inline-flex items-center text-[10px] font-medium text-emerald-700 bg-emerald-50 rounded-full px-2 py-0.2 border border-emerald-200"
                      >
                        Đã đính chính
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center text-[10px] font-medium text-slate-500 bg-slate-100 rounded-full px-2 py-0.2 border border-slate-200"
                      >
                        Chưa sửa
                      </span>
                    </div>
                  </td>

                  <td class="py-3 px-3.5">
                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] mb-1">
                      <span class="font-bold text-indigo-600">#{{ report.question_id }}</span>
                      <span v-if="report.question?.subject?.name" class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                        {{ report.question.subject.name }}
                      </span>
                      <span v-if="report.question?.grade?.name" class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                        {{ report.question.grade.name }}
                      </span>
                    </div>
                    <p class="line-clamp-2 text-xs font-semibold text-slate-900 leading-snug" :title="report.question?.content">
                      {{ report.question?.content || `Câu hỏi #${report.question_id}` }}
                    </p>
                  </td>

                  <td class="py-3 px-3.5 text-center">
                    <span class="text-xs font-medium text-slate-700 truncate block max-w-[130px] mx-auto" :title="report.question?.author_name || report.question?.user?.name">
                      {{ report.question?.author_name || report.question?.user?.name || 'Vô danh' }}
                    </span>
                  </td>

                  <td class="py-3 px-3.5 text-xs">
                    <p class="font-semibold text-rose-700">{{ report.reason }}</p>
                    <p v-if="report.description" class="mt-0.5 truncate italic text-slate-500 text-[11px] max-w-xs" :title="report.description">“{{ report.description }}”</p>
                  </td>

                  <td class="py-3 px-3.5 text-center whitespace-nowrap space-x-1.5">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200/60 px-2.5 py-1 text-xs font-semibold transition cursor-pointer"
                      @click="openQuestionDetailModal(report)"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span>Xem chi tiết</span>
                    </button>

                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                      @click="toggleQuestionVisibility(report)"
                    >
                      <component :is="Boolean(report.question?.is_public) ? Lock : Unlock" class="h-3.5 w-3.5 text-slate-500" />
                      <span>{{ Boolean(report.question?.is_public) ? 'Gỡ công khai' : 'Công khai lại' }}</span>
                    </button>

                    <template v-if="report.status === 'pending'">
                      <button type="button" class="text-xs font-semibold text-emerald-600 hover:underline px-1 cursor-pointer" @click="updateStatus(report.id, 'resolved')">Duyệt</button>
                      <button type="button" class="text-xs font-medium text-slate-500 hover:underline px-1 cursor-pointer" @click="updateStatus(report.id, 'dismissed')">Bỏ qua</button>
                    </template>
                    <template v-else>
                      <button type="button" class="text-xs font-medium text-slate-500 hover:underline px-1 cursor-pointer" @click="updateStatus(report.id, 'pending')">Đổi lại</button>
                    </template>
                  </td>
                </tr>

                <tr v-if="!isLoading && filteredQuestionReports.length === 0">
                  <td colspan="5" class="py-12 text-center text-xs text-slate-400">Không có báo cáo vi phạm câu hỏi nào trong mục này</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <!-- MODAL: SO SÁNH 2 BÊN (SIDE-BY-SIDE) CHI TIẾT CÂU HỎI VÀ BÁO CÁO VI PHẠM -->
    <Teleport to="body">
      <div v-if="isQuestionDetailModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/75 backdrop-blur-xs p-3 sm:p-5" @click.self="isQuestionDetailModalOpen = false">
        <div class="w-full max-w-5xl rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-2xl max-h-[92vh] overflow-y-auto space-y-4">
          
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
            <div class="flex items-center gap-3 flex-wrap">
              <span class="rounded-lg bg-slate-100 text-slate-700 px-2.5 py-1 text-xs font-bold">#{{ selectedQuestionReport?.question_id }}</span>
              <div>
                <h3 class="text-base font-bold text-slate-900">So sánh & Kiểm duyệt Đính chính</h3>
                <p class="text-xs text-slate-500">Đối chiếu phiên bản gốc và bản ghi hiện tại sau khi đính chính</p>
              </div>
              <span
                v-if="selectedQuestionReport?.has_author_updated || selectedQuestionReport?.question?.has_author_updated"
                class="rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 text-xs font-semibold"
              >
                Tác giả đã đính chính
              </span>
              <span
                v-else
                class="rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-medium"
              >
                Chưa đính chính
              </span>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition cursor-pointer" @click="isQuestionDetailModalOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Banner: Thông tin báo cáo vi phạm (Thiết kế tối giản trung tính) -->
          <div class="rounded-xl border border-rose-100 bg-rose-50/30 p-3.5 text-xs space-y-1.5">
            <div class="flex flex-wrap items-center justify-between gap-2 text-slate-500 border-b border-rose-100/60 pb-1.5">
              <span class="font-bold text-rose-800 uppercase tracking-wide text-[11px]">Thông tin báo cáo vi phạm</span>
              <span class="text-[11px]">Thời gian: {{ formatTime(selectedQuestionReport?.created_at) }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-0.5 text-slate-700">
              <p><b>Người báo cáo:</b> {{ selectedQuestionReport?.user?.name || 'Người dùng' }} ({{ selectedQuestionReport?.user?.email }})</p>
              <p><b>Lý do:</b> <span class="font-semibold text-rose-700 bg-white border border-rose-200 px-2 py-0.5 rounded-md">{{ selectedQuestionReport?.reason }}</span></p>
              <p v-if="selectedQuestionReport?.description"><b>Mô tả:</b> <span class="italic">“{{ selectedQuestionReport.description }}”</span></p>
            </div>
          </div>

          <!-- SIDE-BY-SIDE COMPARISON GRID (2 CỘT SONG SONG TRUNG TÍNH) -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            
            <!-- CỘT BÊN TRÁI: NỘI DUNG LÚC BỊ BÁO CÁO (BAN ĐẦU) -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3 flex flex-col justify-between">
              <div class="space-y-3">
                <!-- Header Cột -->
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                  <span class="font-bold text-xs uppercase tracking-wider text-slate-500">1. Lúc bị báo cáo (Ban đầu)</span>
                  <span class="text-[11px] text-slate-400 font-medium">Bản gốc</span>
                </div>

                <!-- Metadata Tags -->
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                  <span v-if="snapshotQuestion?.subject_name || selectedQuestionReport?.question?.subject?.name" class="rounded bg-slate-200/70 text-slate-700 px-2 py-0.5 font-medium">
                    {{ snapshotQuestion?.subject_name || selectedQuestionReport?.question?.subject?.name }}
                  </span>
                  <span v-if="snapshotQuestion?.grade_name || selectedQuestionReport?.question?.grade?.name" class="rounded bg-slate-200/70 text-slate-700 px-2 py-0.5 font-medium">
                    {{ snapshotQuestion?.grade_name || selectedQuestionReport?.question?.grade?.name }}
                  </span>
                  <span v-if="snapshotQuestion?.topic_name || selectedQuestionReport?.question?.topic_name" class="rounded bg-slate-200/70 text-slate-700 px-2 py-0.5 font-medium">
                    {{ snapshotQuestion?.topic_name || selectedQuestionReport?.question?.topic_name }}
                  </span>
                </div>

                <!-- Question Text -->
                <div
                  class="rounded-lg border p-3 text-xs sm:text-sm leading-relaxed transition"
                  :class="isContentModified ? 'border-amber-200 bg-amber-50/70 text-slate-900' : 'border-slate-200 bg-white text-slate-800'"
                >
                  <div v-if="isContentModified" class="text-[10px] font-bold text-amber-700 uppercase tracking-wide mb-1">Văn bản gốc cũ:</div>
                  <p class="font-medium break-words">
                    {{ snapshotQuestion?.content || selectedQuestionReport?.question?.content || selectedQuestionReport?.question?.text }}
                  </p>
                </div>

                <!-- Answers List -->
                <div class="space-y-1.5 pt-1">
                  <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Đáp án ban đầu:</p>
                  <div class="grid grid-cols-1 gap-1.5 text-xs">
                    <div
                      v-for="ans in (snapshotQuestion?.answers || selectedQuestionReport?.question?.answers || [])"
                      :key="ans.id || ans.key"
                      class="flex items-center gap-2 rounded-lg border p-2 font-medium transition"
                      :class="ans.is_correct ? 'border-amber-300 bg-amber-50/80 text-amber-950 font-bold' : 'border-slate-200 bg-white text-slate-600'"
                    >
                      <span
                        class="grid h-5 w-5 place-items-center rounded text-[11px] font-bold shrink-0"
                        :class="ans.is_correct ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-600'"
                      >
                        {{ ans.key || ans.answer_key }}
                      </span>
                      <span class="truncate min-w-0 flex-1">{{ ans.content || ans.text }}</span>
                      <span v-if="ans.is_correct" class="text-amber-800 font-semibold shrink-0 text-[11px]">✓ Đáp án đúng</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="pt-2 border-t border-slate-200/60 text-[11px] text-slate-400">
                Tạo báo cáo: {{ formatTime(selectedQuestionReport?.created_at) }}
              </div>
            </div>

            <!-- CỘT BÊN PHẢI: NỘI DUNG HIỆN TẠI (SAU KHỊ TÁC GIẢ SỬA) -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3 flex flex-col justify-between">
              <div class="space-y-3">
                <!-- Header Cột -->
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                  <span class="font-bold text-xs uppercase tracking-wider text-slate-700">2. Hiện tại (Sau đính chính)</span>
                  <span
                    class="text-[11px] font-semibold"
                    :class="(selectedQuestionReport?.has_author_updated || selectedQuestionReport?.question?.has_author_updated) ? 'text-emerald-600' : 'text-slate-400'"
                  >
                    {{ (selectedQuestionReport?.has_author_updated || selectedQuestionReport?.question?.has_author_updated) ? 'Đã đính chính' : 'Chưa chỉnh sửa' }}
                  </span>
                </div>

                <!-- Metadata Tags -->
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                  <span v-if="selectedQuestionReport?.question?.subject?.name" class="rounded bg-slate-200/70 text-slate-700 px-2 py-0.5 font-medium">
                    {{ selectedQuestionReport.question.subject.name }}
                  </span>
                  <span v-if="selectedQuestionReport?.question?.grade?.name" class="rounded bg-slate-200/70 text-slate-700 px-2 py-0.5 font-medium">
                    {{ selectedQuestionReport.question.grade.name }}
                  </span>
                  <span v-if="selectedQuestionReport?.question?.topic_name" class="rounded bg-slate-200/70 text-slate-700 px-2 py-0.5 font-medium">
                    {{ selectedQuestionReport.question.topic_name }}
                  </span>
                </div>

                <!-- Question Text -->
                <div
                  class="rounded-lg border p-3 text-xs sm:text-sm leading-relaxed transition"
                  :class="isContentModified ? 'border-emerald-300 bg-emerald-50/80 text-slate-900 font-medium' : 'border-slate-200 bg-white text-slate-800'"
                >
                  <div class="flex items-center justify-between mb-1" v-if="isContentModified">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wide">Văn bản đã đính chính:</span>
                    <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-100/80 px-1.5 py-0.2 rounded">Đã sửa chữ</span>
                  </div>
                  <p class="font-medium break-words">
                    {{ selectedQuestionReport?.question?.content || selectedQuestionReport?.question?.text }}
                  </p>
                </div>

                <!-- Answers List -->
                <div class="space-y-1.5 pt-1">
                  <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Đáp án hiện tại:</p>
                  <div class="grid grid-cols-1 gap-1.5 text-xs">
                    <div
                      v-for="ans in (selectedQuestionReport?.question?.answers || [])"
                      :key="ans.id || ans.key"
                      class="flex items-center gap-2 rounded-lg border p-2 font-medium transition"
                      :class="getAnswerDiffTag(ans)
                        ? 'border-emerald-300 bg-emerald-50/80 text-slate-900 font-semibold'
                        : (ans.is_correct ? 'border-slate-300 bg-slate-100 text-slate-900 font-semibold' : 'border-slate-200 bg-white text-slate-600')"
                    >
                      <span
                        class="grid h-5 w-5 place-items-center rounded text-[11px] font-bold shrink-0"
                        :class="ans.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'"
                      >
                        {{ ans.key }}
                      </span>
                      <span class="truncate min-w-0 flex-1">{{ ans.content || ans.text }}</span>

                      <span v-if="getAnswerDiffTag(ans)" class="rounded bg-emerald-100 text-emerald-800 font-semibold px-1.5 py-0.5 text-[10px] shrink-0">
                        {{ getAnswerDiffTag(ans) }}
                      </span>

                      <span v-if="ans.is_correct" class="text-emerald-700 font-semibold shrink-0 text-[11px]">✓ Đáp án đúng</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="pt-2 border-t border-slate-200/60 text-[11px] text-slate-400 flex items-center justify-between">
                <span>Cập nhật lần cuối: {{ formatTime(selectedQuestionReport?.question?.updated_at) }}</span>
                <span>Tác giả: <b class="text-slate-600 font-semibold">{{ selectedQuestionReport?.question?.author_name || 'Vô danh' }}</b></span>
              </div>
            </div>

          </div>

          <!-- Footer Bar: Chỉ giữ lại nút Đóng cửa sổ duy nhất -->
          <div class="flex items-center justify-end border-t border-slate-100 pt-3.5">
            <button
              type="button"
              class="inline-flex justify-center items-center gap-1 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer shadow-sm"
              @click="isQuestionDetailModalOpen = false"
            >
              Đóng cửa sổ
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import {
  Shield,
  RefreshCw,
  BookOpen,
  FileText,
  Eye,
  Lock,
  Unlock,
  Clock,
  Check,
  X,
  AlertTriangle,
  CheckCircle2,
  Search,
  SlidersHorizontal,
  MoreVertical
} from 'lucide-vue-next'
import api, { reportApi, adminQuestionsApi, quizzesApi } from '@/services/api'

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const reports = ref([])
const pendingQuestions = ref([])
const isLoading = ref(true)
const isLoadingPending = ref(false)

const searchQuery = ref('')

const filteredPendingQuestions = computed(() => {
  if (!searchQuery.value.trim()) return pendingQuestions.value
  const q = searchQuery.value.toLowerCase().trim()
  return pendingQuestions.value.filter(item => {
    const text = (item.content || item.text || '').toLowerCase()
    const author = (item.user_name || item.user?.name || '').toLowerCase()
    const idStr = String(item.id)
    const subject = (item.subject_name || '').toLowerCase()
    return text.includes(q) || author.includes(q) || idStr.includes(q) || subject.includes(q)
  })
})

// Navigation state
const mainSection = ref('reports') // 'reports' (Quản lý Báo cáo)
const reportSubTab = ref('quiz') // 'quiz' | 'question'

const quizReports = computed(() => reports.value.filter(r => Boolean(r.quiz_id)))
const questionReports = computed(() => reports.value.filter(r => Boolean(r.question_id)))

// Filter state for Question Reports
const questionFilterTab = ref('updated') // 'updated' | 'pending' | 'all'

const questionUpdatedReports = computed(() =>
  questionReports.value.filter(r => r.has_author_updated || r.question?.has_author_updated)
)
const questionNotUpdatedReports = computed(() =>
  questionReports.value.filter(r => !r.has_author_updated && !r.question?.has_author_updated)
)

const questionUpdatedCount = computed(() =>
  questionUpdatedReports.value.filter(r => r.status === 'pending').length
)
const questionNotUpdatedCount = computed(() =>
  questionNotUpdatedReports.value.filter(r => r.status === 'pending').length
)

const filteredQuestionReports = computed(() => {
  if (questionFilterTab.value === 'updated') return questionUpdatedReports.value
  if (questionFilterTab.value === 'pending') return questionNotUpdatedReports.value
  return questionReports.value
})

const isQuestionDetailModalOpen = ref(false)
const selectedQuestionReport = ref(null)

const snapshotQuestion = computed(() => selectedQuestionReport.value?.question_snapshot || null)
const currentQuestion = computed(() => selectedQuestionReport.value?.question || null)

const isContentModified = computed(() => {
  if (!snapshotQuestion.value || !currentQuestion.value) return false
  const oldText = (snapshotQuestion.value.content || snapshotQuestion.value.text || '').trim()
  const newText = (currentQuestion.value.content || currentQuestion.value.text || '').trim()
  return oldText !== '' && newText !== '' && oldText !== newText
})

const getAnswerDiffTag = (currentAns) => {
  if (!snapshotQuestion.value?.answers || !currentAns) return null
  const oldAns = snapshotQuestion.value.answers.find(a => (a.key || a.answer_key) === currentAns.key)
  if (!oldAns) return 'Mới thêm'

  const isCorrectChanged = Boolean(oldAns.is_correct) !== Boolean(currentAns.is_correct)
  const isTextChanged = (oldAns.content || oldAns.text || '').trim() !== (currentAns.content || currentAns.text || '').trim()

  if (isCorrectChanged && isTextChanged) return '✨ Sửa chữ & đổi đáp án'
  if (isCorrectChanged) return oldAns.is_correct ? 'Đổi thành Sai' : '✨ Đổi thành Đúng'
  if (isTextChanged) return '✨ Sửa chữ'
  return null
}

const openQuestionDetailModal = (report) => {
  selectedQuestionReport.value = report
  isQuestionDetailModalOpen.value = true
}

const handleApproveInModal = async () => {
  if (!selectedQuestionReport.value) return
  await toggleQuestionVisibility(selectedQuestionReport.value)
  isQuestionDetailModalOpen.value = false
}

const handleDismissInModal = async () => {
  if (!selectedQuestionReport.value) return
  await updateStatus(selectedQuestionReport.value.id, 'dismissed')
  isQuestionDetailModalOpen.value = false
}

const formatTime = (dateStr) => {
  if (!dateStr) return '—'
  try {
    return new Date(dateStr).toLocaleString('vi-VN')
  } catch (e) {
    return dateStr
  }
}

const quizPendingCount = computed(() =>
  quizReports.value.filter(r => r.status === 'pending').length
)
const questionPendingCount = computed(() =>
  questionReports.value.filter(r => r.status === 'pending').length
)
const totalPendingReports = computed(() => quizPendingCount.value + questionPendingCount.value)

const fetchPendingQuestions = async () => {
  isLoadingPending.value = true
  try {
    const res = await adminQuestionsApi.fetchPending()
    pendingQuestions.value = res?.items || res?.data || []
  } catch (e) {
    console.error('Lỗi khi lấy danh sách câu hỏi chờ duyệt:', e)
  } finally {
    isLoadingPending.value = false
  }
}

const fetchReports = async (isBackground = false) => {
  if (!isBackground) isLoading.value = true
  try {
    reports.value = await reportApi.listAdmin()
  } catch (error) {
    console.error('Lỗi khi lấy danh sách báo cáo:', error)
    if (!isBackground && showToast) showToast('Không thể tải danh sách báo cáo', 'error')
  } finally {
    if (!isBackground) isLoading.value = false
  }
}

const refreshAllData = () => {
  fetchPendingQuestions()
  fetchReports(false)
}

const moderateQuestion = async (id, action) => {
  const isApprove = action === 'approve'
  const confirmTitle = isApprove ? 'Phê duyệt câu hỏi' : 'Từ chối câu hỏi'
  const confirmMsg = isApprove
    ? `Phê duyệt câu hỏi #${id} lên Ngân hàng câu hỏi dùng chung cho toàn hệ thống?`
    : `Từ chối đưa câu hỏi #${id} lên Ngân hàng dùng chung? (Câu hỏi sẽ chuyển về phạm vi Riêng tư trong Kho cá nhân tác giả).`

  const handleAction = async () => {
    try {
      await adminQuestionsApi.moderate(id, { action })
      pendingQuestions.value = pendingQuestions.value.filter(q => q.id !== id)
      if (showToast) {
        showToast(
          isApprove
            ? `Đã phê duyệt câu hỏi #${id} lên Ngân hàng thành công!`
            : `Đã từ chối đưa câu hỏi #${id} lên Ngân hàng và chuyển về Kho cá nhân.`,
          'success'
        )
      }
    } catch (err) {
      if (showToast) showToast(`Thao tác thất bại: ${err.message}`, 'error')
    }
  }

  if (showConfirm) showConfirm(confirmTitle, confirmMsg, handleAction)
  else if (confirm(confirmMsg)) handleAction()
}

const updateStatus = async (id, status) => {
  let confirmMsg = 'Bạn có chắc chắn muốn cập nhật trạng thái báo cáo này?'
  if (status === 'resolved') confirmMsg = 'Đánh dấu báo cáo này là "Đã xử lý"?'
  else if (status === 'dismissed') confirmMsg = 'Bạn muốn bỏ qua và không xử lý báo cáo này?'
  else if (status === 'pending') confirmMsg = 'Đổi lại trạng thái báo cáo về "Chờ xử lý"?'

  const action = async () => {
    try {
      await reportApi.updateAdminStatus(id, status)
      const index = reports.value.findIndex(r => r.id === id)
      if (index !== -1) reports.value[index].status = status
      window.dispatchEvent(new CustomEvent('notifications-updated'))
      if (showToast) showToast('Cập nhật trạng thái báo cáo thành công', 'success')
    } catch (error) {
      const errMsg = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi cập nhật!'
      if (showToast) showToast(`Cập nhật thất bại: ${errMsg}`, 'error')
    }
  }

  if (showConfirm) showConfirm('Xác nhận thao tác', confirmMsg, action)
  else action()
}

const toggleQuestionVisibility = async (report) => {
  const isCurrentlyPublic = Boolean(report.question?.is_public)
  const actionTitle = isCurrentlyPublic ? 'Gỡ công khai câu hỏi' : 'Công khai lại câu hỏi'
  const msg = isCurrentlyPublic
    ? `Gỡ công khai câu hỏi #${report.question_id} và gửi thông báo cho tác giả tự chỉnh sửa trong kho cá nhân?`
    : `Công khai lại câu hỏi #${report.question_id} trên Ngân hàng câu hỏi chung?`

  const action = async () => {
    try {
      const res = await adminQuestionsApi.toggleVisibility(report.question_id)
      const newIsPublic = res?.data?.is_public ?? res?.is_public ?? !isCurrentlyPublic

      if (report.question) {
        report.question.is_public = Boolean(newIsPublic)
      } else {
        report.question = { is_public: Boolean(newIsPublic) }
      }

      if (report.status === 'pending') {
        const resolvedAction = isCurrentlyPublic ? 'hidden' : 'approved'
        await reportApi.updateAdminStatus(report.id, 'resolved', resolvedAction)
        report.status = 'resolved'
      }

      window.dispatchEvent(new CustomEvent('notifications-updated'))
      const toastMsg = isCurrentlyPublic
        ? `Đã gỡ công khai câu hỏi #${report.question_id} và gửi thông báo cho tác giả.`
        : `Đã công khai lại câu hỏi #${report.question_id}.`
      if (showToast) showToast(toastMsg, 'success')
    } catch (e) {
      if (showToast) showToast(`Thao tác thất bại: ${e.message}`, 'error')
    }
  }

  if (showConfirm) showConfirm(actionTitle, msg, action)
  else if (confirm(msg)) action()
}

const toggleQuizVisibility = async (report) => {
  const isCurrentlyPublic = Boolean(report.quiz?.is_public)
  const actionTitle = isCurrentlyPublic ? 'Gỡ công khai Quiz' : 'Công khai lại Quiz'
  const msg = isCurrentlyPublic
    ? `Gỡ công khai bài Quiz '${report.quiz?.title || report.quiz_id}' và gửi thông báo cho tác giả?`
    : `Công khai lại bài Quiz '${report.quiz?.title || report.quiz_id}'?`

  const action = async () => {
    try {
      const res = await quizzesApi.toggleVisibility(report.quiz_id)
      const newIsPublic = res?.data?.is_public ?? res?.is_public ?? !isCurrentlyPublic

      if (report.quiz) {
        report.quiz.is_public = Boolean(newIsPublic)
      } else {
        report.quiz = { is_public: Boolean(newIsPublic) }
      }

      if (report.status === 'pending') {
        const resolvedAction = isCurrentlyPublic ? 'hidden' : 'approved'
        await reportApi.updateAdminStatus(report.id, 'resolved', resolvedAction)
        report.status = 'resolved'
      }

      window.dispatchEvent(new CustomEvent('notifications-updated'))
      const toastMsg = isCurrentlyPublic
        ? 'Đã gỡ công khai Quiz và gửi thông báo cho tác giả.'
        : 'Đã công khai lại bài Quiz.'
      if (showToast) showToast(toastMsg, 'success')
    } catch (e) {
      if (showToast) showToast(`Thao tác thất bại: ${e.message}`, 'error')
    }
  }

  if (showConfirm) showConfirm(actionTitle, msg, action)
  else if (confirm(msg)) action()
}

const deleteQuiz = (quizId, reportId) => {
  if (showConfirm) {
    showConfirm(
      'Xác nhận xóa Quiz',
      'Bạn có chắc chắn muốn xóa mềm Quiz vi phạm này không? Khi xóa Quiz, báo cáo này cũng sẽ được đánh dấu là "Đã xử lý".',
      async () => {
        try {
          await api.delete(`/admin/quizzes/${quizId}`)
          await reportApi.updateAdminStatus(reportId, 'resolved', 'deleted')
          const index = reports.value.findIndex(r => r.id === reportId)
          if (index !== -1) reports.value[index].status = 'resolved'

          window.dispatchEvent(new CustomEvent('notifications-updated'))
          if (showToast) showToast('Đã xóa Quiz vi phạm thành công', 'success')
        } catch (error) {
          if (showToast) {
            showToast(
              'Xóa Quiz thất bại: ' + (error.response?.data?.message || error.message),
              'error'
            )
          }
        }
      }
    )
  }
}

const getStatusBadge = (status) => {
  const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium border '
  switch (status) {
    case 'pending':
      return base + 'bg-amber-50 text-amber-700 border-amber-200'
    case 'resolved':
      return base + 'bg-emerald-50 text-emerald-700 border-emerald-200'
    case 'dismissed':
      return base + 'bg-slate-100 text-slate-600 border-slate-200'
    default:
      return base + 'bg-slate-100 text-slate-600 border-slate-200'
  }
}

const getStatusText = (status) =>
  ({
    pending: 'Chờ xử lý',
    resolved: 'Đã xử lý',
    dismissed: 'Đã bỏ qua'
  }[status] || status)

onMounted(() => {
  fetchPendingQuestions()
  fetchReports()
})
</script>