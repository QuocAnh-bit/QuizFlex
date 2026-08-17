<template>
  <section class="grid gap-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-rose-500/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-center">
        <div>
          <div class="inline-flex items-center gap-2 rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-1.5 text-xs font-black text-rose-400">
            <span>🛡️ Moderation Center</span>
            <span>•</span>
            <span>Trung tâm Kiểm duyệt Vi phạm Hợp nhất</span>
          </div>
          <h1 class="mt-2 text-3xl sm:text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Quản lý Báo cáo Vi phạm</h1>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[var(--muted)]">
            Xem xét, gỡ công khai/công khai lại và gửi thông báo trực tiếp cho bài Quiz hoặc Câu hỏi bị người học báo cáo lỗi.
          </p>
        </div>

        <!-- Pending Badge Indicators -->
        <div class="flex flex-wrap gap-3">
          <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-center">
            <span class="text-[10px] font-bold text-[var(--muted)] uppercase">Quiz chờ duyệt</span>
            <p class="text-xl font-black text-amber-400">{{ quizPendingCount }}</p>
          </div>
          <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-center">
            <span class="text-[10px] font-bold text-[var(--muted)] uppercase">Câu hỏi chờ duyệt</span>
            <p class="text-xl font-black text-rose-400">{{ questionPendingCount }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 2-Tab Navigation Bar -->
    <div class="flex items-center gap-2 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-2 shadow-[var(--shadow-card)] backdrop-blur-xl">
      <button
        type="button"
        class="flex-1 rounded-xl py-3 text-xs sm:text-sm font-black transition flex items-center justify-center gap-2"
        :class="activeTab === 'quiz' ? 'bg-[var(--primary)] text-white shadow-lg shadow-[var(--primary)]/30' : 'text-[var(--muted)] hover:text-[var(--text)] hover:bg-[var(--surface-soft)]'"
        @click="activeTab = 'quiz'"
      >
        <span>📖 Báo cáo Bài Quiz</span>
        <span v-if="quizPendingCount > 0" class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] text-slate-950 font-black">
          {{ quizPendingCount }}
        </span>
      </button>

      <button
        type="button"
        class="flex-1 rounded-xl py-3 text-xs sm:text-sm font-black transition flex items-center justify-center gap-2"
        :class="activeTab === 'question' ? 'bg-[var(--primary)] text-white shadow-lg shadow-[var(--primary)]/30' : 'text-[var(--muted)] hover:text-[var(--text)] hover:bg-[var(--surface-soft)]'"
        @click="activeTab = 'question'"
      >
        <span>📝 Báo cáo Câu hỏi</span>
        <span v-if="questionPendingCount > 0" class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] text-white font-black">
          {{ questionPendingCount }}
        </span>
      </button>
    </div>

    <!-- TAB 1: BÁO CÁO BÀI QUIZ -->
    <div v-if="activeTab === 'quiz'" class="overflow-x-auto rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <table class="w-full border-collapse text-left text-sm text-[var(--text)]">
        <thead>
          <tr class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-wider text-[var(--muted)]">
            <th class="p-4 text-center w-28">Trạng thái Ticket</th>
            <th class="p-4">Quiz bị báo cáo</th>
            <th class="p-4 text-center">Nguồn / Lý do</th>
            <th class="p-4">Chi tiết thêm</th>
            <th class="p-4 text-center min-w-[280px]">Thao tác Admin</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
          <tr v-for="report in quizReports" :key="report.id" class="transition hover:bg-[var(--surface-soft)]">
            <td class="p-4 text-center">
              <span :class="getStatusBadge(report.status)">
                {{ getStatusText(report.status) }}
              </span>
            </td>
            <td class="p-4">
              <router-link :to="`/admin/quizzes/${report.quiz_id}`" class="font-bold text-[var(--primary)] hover:underline">
                {{ report.quiz?.title || `Quiz #${report.quiz_id}` }}
              </router-link>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-[11px] text-[var(--muted)]">👤 Tác giả: {{ report.quiz?.user?.name || 'Vô danh' }}</span>
                <span 
                  class="rounded-full px-2 py-0.2 text-[10px] font-black uppercase"
                  :class="Boolean(report.quiz?.is_public) ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border border-amber-500/30'"
                >
                  {{ Boolean(report.quiz?.is_public) ? '🌐 Public' : '🔒 Private' }}
                </span>
              </div>
            </td>
            <td class="p-4 text-center">
              <span class="font-bold text-rose-400">{{ report.reason }}</span>
            </td>
            <td class="p-4 text-[var(--muted)] max-w-xs truncate" :title="report.description">
              {{ report.description || '--' }}
            </td>
            <td class="p-4 text-center space-x-2">
              <!-- Xem chi tiết Quiz -->
              <router-link :to="`/admin/quizzes/${report.quiz_id}?from=reports`" class="rounded-full border border-[var(--primary)]/30 bg-[var(--primary)]/10 px-3 py-1 text-xs font-black text-[var(--primary)] hover:bg-[var(--primary)] hover:text-white transition">
                🔍 Chi tiết
              </router-link>

              <!-- Nút chuyển đổi trạng thái Công khai / Riêng tư của Quiz -->
              <button
                type="button"
                :class="Boolean(report.quiz?.is_public)
                  ? 'rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-black text-amber-300 hover:bg-amber-500 hover:text-slate-950 transition'
                  : 'rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-black text-emerald-300 hover:bg-emerald-500 hover:text-slate-950 transition'"
                :title="Boolean(report.quiz?.is_public) ? 'Gỡ công khai bài Quiz này và gửi thông báo cho tác giả' : 'Công khai lại bài Quiz này'"
                @click="toggleQuizVisibility(report)"
              >
                <span v-if="Boolean(report.quiz?.is_public)">🔒 Gỡ công khai</span>
                <span v-else>🔓 Công khai lại</span>
              </button>

              <!-- Các nút duyệt / bỏ qua trạng thái Báo cáo ticket -->
              <template v-if="report.status === 'pending'">
                <button @click="updateStatus(report.id, 'resolved')" class="text-emerald-400 hover:underline font-bold text-xs" title="Đánh dấu đã duyệt xong">
                  ✅ Duyệt
                </button>
                <button @click="updateStatus(report.id, 'dismissed')" class="text-gray-400 hover:underline font-bold text-xs" title="Bỏ qua báo cáo vi phạm này">
                  ❌ Bỏ qua
                </button>
              </template>
              <template v-else>
                <button @click="updateStatus(report.id, 'pending')" class="text-xs text-[var(--muted)] hover:text-[var(--text)] underline font-bold" title="Mở lại báo cáo về trạng thái Chờ xử lý">
                  🔄 Đổi trạng thái
                </button>
              </template>

              <RouterLink :to="`/admin/quizzes/${report.quiz_id}/edit`" class="text-amber-400 hover:underline font-bold text-xs">
                Sửa
              </RouterLink>
              <button @click="deleteQuiz(report.quiz_id, report.id)" class="text-rose-400 hover:underline font-bold text-xs">
                Xóa
              </button>
            </td>
          </tr>
          
          <tr v-if="!isLoading && quizReports.length === 0">
            <td colspan="5" class="text-center py-12 text-[var(--muted)] font-bold">
              🎉 Không có báo cáo vi phạm bài Quiz nào
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="isLoading" class="text-center py-10 text-[var(--muted)] font-bold">Đang tải báo cáo bài Quiz...</div>
    </div>

    <!-- TAB 2: BÁO CÁO CÂU HỎI -->
    <div v-if="activeTab === 'question'" class="overflow-x-auto rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <table class="w-full border-collapse text-left text-sm text-[var(--text)]">
        <thead>
          <tr class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-wider text-[var(--muted)]">
            <th class="p-4 text-center w-28">Trạng thái Ticket</th>
            <th class="p-4 min-w-[240px]">Câu hỏi bị báo cáo</th>
            <th class="p-4 text-center">Tác giả câu hỏi</th>
            <th class="p-4">Lý do & Mô tả lỗi</th>
            <th class="p-4 text-center min-w-[300px]">Thao tác Admin</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
          <tr v-for="report in questionReports" :key="report.id" class="transition hover:bg-[var(--surface-soft)]">
            <td class="p-4 text-center">
              <span :class="getStatusBadge(report.status)">
                {{ getStatusText(report.status) }}
              </span>
            </td>
            <td class="p-4">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-black text-[var(--primary)]">#{{ report.question_id }}</span>
                <span v-if="report.question?.subject?.name" class="rounded-full bg-[var(--surface-soft)] border border-[var(--border)] px-2 py-0.5 text-[10px] font-bold">
                  📖 {{ report.question.subject.name }}
                </span>
                <!-- Badge hiển thị trạng thái Public / Private của câu hỏi -->
                <span 
                  class="rounded-full px-2 py-0.2 text-[10px] font-black uppercase"
                  :class="Boolean(report.question?.is_public) ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border border-amber-500/30'"
                >
                  {{ Boolean(report.question?.is_public) ? '🌐 Public' : '🔒 Private' }}
                </span>
              </div>
              <p class="font-bold text-[var(--text)] line-clamp-2 text-xs" :title="report.question?.content">
                {{ report.question?.content || `Câu hỏi #${report.question_id}` }}
              </p>
            </td>
            <td class="p-4 text-center text-xs font-bold text-[var(--muted)]">
              <div class="flex flex-col items-center">
                <span>👤 {{ report.question?.author_name || report.question?.user?.name || 'Vô danh' }}</span>
                <span class="text-[10px] text-[var(--muted)]">{{ formatDate(report.created_at) }}</span>
              </div>
            </td>
            <td class="p-4 max-w-xs">
              <p class="font-bold text-rose-400 text-xs">{{ report.reason }}</p>
              <p v-if="report.description" class="text-xs text-[var(--muted)] italic mt-1 truncate" :title="report.description">
                "{{ report.description }}"
              </p>
            </td>
            <td class="p-4 text-center space-x-2">
              <!-- Navigation to Question Detail View -->
              <router-link
                :to="`/admin/questions/${report.question_id}?from=reports`"
                class="rounded-full border border-[var(--primary)]/30 bg-[var(--primary)]/10 px-3 py-1 text-xs font-black text-[var(--primary)] hover:bg-[var(--primary)] hover:text-white transition"
              >
                🔍 Chi tiết
              </router-link>

              <!-- Dynamic Toggle Public/Private Button -->
              <button
                type="button"
                :class="Boolean(report.question?.is_public)
                  ? 'rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-black text-amber-300 hover:bg-amber-500 hover:text-slate-950 transition'
                  : 'rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-black text-emerald-300 hover:bg-emerald-500 hover:text-slate-950 transition'"
                :title="Boolean(report.question?.is_public)
                  ? 'Gỡ công khai và chuyển về kho cá nhân để tác giả tự sửa'
                  : 'Cho phép công khai lại câu hỏi này trên ngân hàng toàn hệ thống'"
                @click="toggleQuestionVisibility(report)"
              >
                <span v-if="Boolean(report.question?.is_public)">🔒 Gỡ công khai</span>
                <span v-else>🔓 Công khai lại</span>
              </button>

              <!-- Action buttons: Duyệt / Bỏ qua hay Đổi trạng thái ticket -->
              <template v-if="report.status === 'pending'">
                <button @click="updateStatus(report.id, 'resolved')" class="text-emerald-400 hover:underline font-bold text-xs" title="Đánh dấu đã duyệt xong">
                  ✅ Duyệt
                </button>
                <button @click="updateStatus(report.id, 'dismissed')" class="text-gray-400 hover:underline font-bold text-xs" title="Bỏ qua báo cáo">
                  ❌ Bỏ qua
                </button>
              </template>
              <template v-else>
                <button @click="updateStatus(report.id, 'pending')" class="text-xs text-[var(--muted)] hover:text-[var(--text)] underline font-bold" title="Mở lại báo cáo về trạng thái Chờ xử lý">
                  🔄 Đổi trạng thái
                </button>
              </template>
            </td>
          </tr>

          <tr v-if="!isLoading && questionReports.length === 0">
            <td colspan="5" class="text-center py-12 text-[var(--muted)] font-bold">
              🎉 Không có báo cáo vi phạm câu hỏi nào
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="isLoading" class="text-center py-10 text-[var(--muted)] font-bold">Đang tải báo cáo câu hỏi...</div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import api, { reportApi, adminQuestionsApi, quizzesApi } from '@/services/api'

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const reports = ref([])
const isLoading = ref(true)
const activeTab = ref('quiz') // 'quiz' or 'question'

const quizReports = computed(() => {
  return reports.value.filter(r => Boolean(r.quiz_id))
})

const questionReports = computed(() => {
  return reports.value.filter(r => Boolean(r.question_id))
})

const quizPendingCount = computed(() => {
  return quizReports.value.filter(r => r.status === 'pending').length
})

const questionPendingCount = computed(() => {
  return questionReports.value.filter(r => r.status === 'pending').length
})

const formatDate = (dateStr) => {
  if (!dateStr) return '--'
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
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
      
      // Đổi ngay trạng thái is_public trên UI
      const newIsPublic = res?.data?.is_public ?? res?.is_public ?? !isCurrentlyPublic
      if (report.question) {
        report.question.is_public = Boolean(newIsPublic)
      } else {
        report.question = { is_public: Boolean(newIsPublic) }
      }

      // Nếu ticket đang pending, tự động đánh dấu đã xử lý
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
      
      // Đổi ngay trạng thái is_public trên UI
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
        ? `Đã gỡ công khai Quiz và gửi thông báo cho tác giả.`
        : `Đã công khai lại bài Quiz.`
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
          if (showToast) showToast('Xóa Quiz thất bại: ' + (error.response?.data?.message || error.message), 'error')
        }
      }
    )
  }
}

const getStatusBadge = (status) => {
  const base = 'rounded-full px-3 py-1 text-xs font-black uppercase tracking-wider border inline-block '
  switch (status) {
    case 'pending': return base + 'border-amber-500/30 bg-amber-500/10 text-amber-400'
    case 'resolved': return base + 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
    case 'dismissed': return base + 'border-gray-500/30 bg-gray-500/10 text-gray-400'
    default: return base + 'border-gray-500/30 bg-gray-500/10 text-gray-400'
  }
}

const getStatusText = (status) => {
  switch (status) {
    case 'pending': return 'Chờ xử lý'
    case 'resolved': return 'Đã xử lý'
    case 'dismissed': return 'Đã bỏ qua'
    default: return status
  }
}

onMounted(() => {
  fetchReports()
})
</script>