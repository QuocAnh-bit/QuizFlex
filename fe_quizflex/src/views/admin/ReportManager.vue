<template>
  <section class="space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <div class="inline-flex items-center gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">
            <Shield class="h-3.5 w-3.5" />
            <span>Moderation Center</span>
            <span class="text-rose-400">•</span>
            <span>Trung tâm Kiểm duyệt Vi phạm</span>
          </div>
          <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Quản lý Báo cáo Vi phạm
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500">
            Xem xét, gỡ công khai / công khai lại và gửi thông báo trực tiếp cho bài Quiz hoặc Câu hỏi bị người học báo cáo lỗi.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-center">
            <span class="text-[10px] font-medium uppercase text-slate-500">Quiz chờ duyệt</span>
            <p class="text-xl font-bold text-amber-600">{{ quizPendingCount }}</p>
          </div>
          <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-center">
            <span class="text-[10px] font-medium uppercase text-slate-500">Câu hỏi chờ duyệt</span>
            <p class="text-xl font-bold text-rose-600">{{ questionPendingCount }}</p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="fetchReports(false)"
          >
            <RefreshCw class="h-4 w-4" />
            Làm mới
          </button>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm">
      <button
        type="button"
        class="flex flex-1 items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-semibold transition"
        :class="activeTab === 'quiz'
          ? 'bg-[#7C3AED] text-white shadow-sm'
          : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'"
        @click="activeTab = 'quiz'"
      >
        <BookOpen class="h-4 w-4" />
        Báo cáo Bài Quiz
        <span
          v-if="quizPendingCount > 0"
          class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white"
        >
          {{ quizPendingCount }}
        </span>
      </button>

      <button
        type="button"
        class="flex flex-1 items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-semibold transition"
        :class="activeTab === 'question'
          ? 'bg-[#7C3AED] text-white shadow-sm'
          : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'"
        @click="activeTab = 'question'"
      >
        <FileText class="h-4 w-4" />
        Báo cáo Câu hỏi
        <span
          v-if="questionPendingCount > 0"
          class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white"
        >
          {{ questionPendingCount }}
        </span>
      </button>
    </div>

    <!-- TAB 1: Quiz Reports -->
    <div
      v-if="activeTab === 'quiz'"
      class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
              <th class="w-28 p-4 text-center">Trạng thái</th>
              <th class="p-4">Quiz bị báo cáo</th>
              <th class="p-4 text-center">Lý do</th>
              <th class="p-4">Chi tiết thêm</th>
              <th class="min-w-[280px] p-4 text-center">Thao tác Admin</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="report in quizReports"
              :key="report.id"
              class="transition hover:bg-slate-50"
            >
              <td class="p-4 text-center">
                <span :class="getStatusBadge(report.status)">
                  {{ getStatusText(report.status) }}
                </span>
              </td>

              <td class="p-4">
                <router-link
                  :to="`/admin/quizzes/${report.quiz_id}`"
                  class="font-semibold text-[#7C3AED] hover:underline"
                >
                  {{ report.quiz?.title || `Quiz #${report.quiz_id}` }}
                </router-link>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                  <span class="text-xs text-slate-500">
                    Tác giả: {{ report.quiz?.user?.name || 'Vô danh' }}
                  </span>
                  <span
                    class="rounded-md px-2 py-0.5 text-[10px] font-medium"
                    :class="Boolean(report.quiz?.is_public)
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-amber-50 text-amber-700'"
                  >
                    {{ Boolean(report.quiz?.is_public) ? 'Công khai' : 'Riêng tư' }}
                  </span>
                </div>
              </td>

              <td class="p-4 text-center">
                <span class="font-medium text-rose-600">{{ report.reason }}</span>
              </td>

              <td class="max-w-xs truncate p-4 text-slate-500" :title="report.description">
                {{ report.description || '—' }}
              </td>

              <td class="space-x-1.5 p-4 text-center whitespace-nowrap">
                <router-link
                  :to="`/admin/quizzes/${report.quiz_id}?from=reports`"
                  class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                >
                  <Eye class="h-3.5 w-3.5" />
                  Chi tiết
                </router-link>

                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-medium transition"
                  :class="Boolean(report.quiz?.is_public)
                    ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                  @click="toggleQuizVisibility(report)"
                >
                  <component :is="Boolean(report.quiz?.is_public) ? Lock : Unlock" class="h-3.5 w-3.5" />
                  {{ Boolean(report.quiz?.is_public) ? 'Gỡ công khai' : 'Công khai lại' }}
                </button>

                <template v-if="report.status === 'pending'">
                  <button
                    type="button"
                    class="text-xs font-medium text-emerald-600 hover:underline"
                    @click="updateStatus(report.id, 'resolved')"
                  >
                    Duyệt
                  </button>
                  <button
                    type="button"
                    class="text-xs font-medium text-slate-500 hover:underline"
                    @click="updateStatus(report.id, 'dismissed')"
                  >
                    Bỏ qua
                  </button>
                </template>
                <template v-else>
                  <button
                    type="button"
                    class="text-xs font-medium text-slate-500 hover:underline"
                    @click="updateStatus(report.id, 'pending')"
                  >
                    Đổi trạng thái
                  </button>
                </template>

                <router-link
                  :to="`/admin/quizzes/${report.quiz_id}/edit`"
                  class="text-xs font-medium text-amber-600 hover:underline"
                >
                  Sửa
                </router-link>
                <button
                  type="button"
                  class="text-xs font-medium text-rose-600 hover:underline"
                  @click="deleteQuiz(report.quiz_id, report.id)"
                >
                  Xóa
                </button>
              </td>
            </tr>

            <tr v-if="!isLoading && quizReports.length === 0">
              <td colspan="5" class="py-16 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                  <CheckCircle2 class="h-6 w-6" />
                </div>
                <p class="text-base font-medium text-slate-900">Không có báo cáo vi phạm bài Quiz nào</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="isLoading" class="py-12 text-center">
        <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-[#7C3AED] border-t-transparent"></div>
        <p class="text-sm font-medium text-slate-500">Đang tải báo cáo bài Quiz...</p>
      </div>
    </div>

    <!-- TAB 2: Question Reports -->
    <div
      v-if="activeTab === 'question'"
      class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
              <th class="w-28 p-4 text-center">Trạng thái</th>
              <th class="min-w-[240px] p-4">Câu hỏi bị báo cáo</th>
              <th class="p-4 text-center">Tác giả</th>
              <th class="p-4">Lý do & Mô tả</th>
              <th class="min-w-[300px] p-4 text-center">Thao tác Admin</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="report in questionReports"
              :key="report.id"
              class="transition hover:bg-slate-50"
            >
              <td class="p-4 text-center">
                <span :class="getStatusBadge(report.status)">
                  {{ getStatusText(report.status) }}
                </span>
              </td>

              <td class="p-4">
                <div class="mb-1 flex flex-wrap items-center gap-2">
                  <span class="font-semibold text-[#7C3AED]">#{{ report.question_id }}</span>
                  <span
                    v-if="report.question?.subject?.name"
                    class="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-600"
                  >
                    {{ report.question.subject.name }}
                  </span>
                  <span
                    class="rounded-md px-2 py-0.5 text-[10px] font-medium"
                    :class="Boolean(report.question?.is_public)
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-amber-50 text-amber-700'"
                  >
                    {{ Boolean(report.question?.is_public) ? 'Công khai' : 'Riêng tư' }}
                  </span>
                </div>
                <p class="line-clamp-2 text-xs font-medium text-slate-900" :title="report.question?.content">
                  {{ report.question?.content || `Câu hỏi #${report.question_id}` }}
                </p>
              </td>

              <td class="p-4 text-center text-xs text-slate-500">
                <div class="flex flex-col items-center">
                  <span class="font-medium">{{ report.question?.author_name || report.question?.user?.name || 'Vô danh' }}</span>
                  <span class="text-[10px]">{{ formatDate(report.created_at) }}</span>
                </div>
              </td>

              <td class="max-w-xs p-4">
                <p class="text-xs font-medium text-rose-600">{{ report.reason }}</p>
                <p
                  v-if="report.description"
                  class="mt-1 truncate text-xs italic text-slate-500"
                  :title="report.description"
                >
                  “{{ report.description }}”
                </p>
              </td>

              <td class="space-x-1.5 p-4 text-center whitespace-nowrap">
                <router-link
                  :to="`/admin/questions/${report.question_id}?from=reports`"
                  class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                >
                  <Eye class="h-3.5 w-3.5" />
                  Chi tiết
                </router-link>

                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-medium transition"
                  :class="Boolean(report.question?.is_public)
                    ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                  @click="toggleQuestionVisibility(report)"
                >
                  <component :is="Boolean(report.question?.is_public) ? Lock : Unlock" class="h-3.5 w-3.5" />
                  {{ Boolean(report.question?.is_public) ? 'Gỡ công khai' : 'Công khai lại' }}
                </button>

                <template v-if="report.status === 'pending'">
                  <button
                    type="button"
                    class="text-xs font-medium text-emerald-600 hover:underline"
                    @click="updateStatus(report.id, 'resolved')"
                  >
                    Duyệt
                  </button>
                  <button
                    type="button"
                    class="text-xs font-medium text-slate-500 hover:underline"
                    @click="updateStatus(report.id, 'dismissed')"
                  >
                    Bỏ qua
                  </button>
                </template>
                <template v-else>
                  <button
                    type="button"
                    class="text-xs font-medium text-slate-500 hover:underline"
                    @click="updateStatus(report.id, 'pending')"
                  >
                    Đổi trạng thái
                  </button>
                </template>
              </td>
            </tr>

            <tr v-if="!isLoading && questionReports.length === 0">
              <td colspan="5" class="py-16 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                  <CheckCircle2 class="h-6 w-6" />
                </div>
                <p class="text-base font-medium text-slate-900">Không có báo cáo vi phạm câu hỏi nào</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="isLoading" class="py-12 text-center">
        <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-[#7C3AED] border-t-transparent"></div>
        <p class="text-sm font-medium text-slate-500">Đang tải báo cáo câu hỏi...</p>
      </div>
    </div>
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
  CheckCircle2
} from 'lucide-vue-next'
import api, { reportApi, adminQuestionsApi, quizzesApi } from '@/services/api'

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const reports = ref([])
const isLoading = ref(true)
const activeTab = ref('quiz')

const quizReports = computed(() => reports.value.filter(r => Boolean(r.quiz_id)))
const questionReports = computed(() => reports.value.filter(r => Boolean(r.question_id)))

const quizPendingCount = computed(() =>
  quizReports.value.filter(r => r.status === 'pending').length
)
const questionPendingCount = computed(() =>
  questionReports.value.filter(r => r.status === 'pending').length
)

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
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
  const base = 'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium '
  switch (status) {
    case 'pending':
      return base + 'bg-amber-50 text-amber-700'
    case 'resolved':
      return base + 'bg-emerald-50 text-emerald-700'
    case 'dismissed':
      return base + 'bg-slate-100 text-slate-600'
    default:
      return base + 'bg-slate-100 text-slate-600'
  }
}

const getStatusText = (status) =>
  ({
    pending: 'Chờ xử lý',
    resolved: 'Đã xử lý',
    dismissed: 'Đã bỏ qua'
  }[status] || status)

onMounted(() => {
  fetchReports()
})
</script>