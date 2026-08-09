<template>
  <section class="grid gap-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-rose-500/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-500">Moderation Dashboard</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Quản lý Báo cáo</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Xem, xét duyệt và xử lý các báo cáo vi phạm nội dung, thông tin sai lệch hoặc bị hệ thống lọc từ khóa (Profanity Filter) quét được.</p>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <table class="w-full border-collapse text-left text-sm text-[var(--text)]">
        <thead>
          <tr class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-wider text-[var(--muted)]">
            <th class="p-4 text-center">Trạng thái</th>
            <th class="p-4">Quiz bị báo cáo</th>
            <th class="p-4 text-center">Nguồn / Lý do</th>
            <th class="p-4">Chi tiết thêm</th>
            <th class="p-4 text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
          <tr v-for="report in reports" :key="report.id" class="transition hover:bg-[var(--surface-soft)]">
            <td class="p-4 text-center">
              <span :class="getStatusBadge(report.status)">
                {{ getStatusText(report.status) }}
              </span>
            </td>
            <td class="p-4">
              <router-link :to="`/quizzes/${report.quiz_id}`" target="_blank" class="font-bold text-[var(--primary)] hover:underline">
                {{ report.quiz?.title || `Quiz #${report.quiz_id}` }}
              </router-link>
            </td>
            <td class="p-4 text-center">
              <span class="font-bold text-rose-500">{{ report.reason }}</span>
              <div v-if="report.reason.includes('thô tục') || report.reason.includes('Profanity')" class="text-[10px] text-[var(--muted)] mt-1 uppercase font-black tracking-widest">
                🤖 AI Cảnh báo
              </div>
            </td>
            <td class="p-4 text-[var(--muted)] max-w-xs truncate" :title="report.description">
              {{ report.description || '--' }}
            </td>
            <td class="p-4 text-center space-x-3">
              <!-- Nhóm nút Xử lý Báo cáo -->
              <template v-if="report.status === 'pending'">
                <button @click="updateStatus(report.id, 'resolved')" class="text-emerald-400 hover:underline font-bold" title="Duyệt và đánh dấu đã xử lý">
                  ✅ Duyệt
                </button>
                <button @click="updateStatus(report.id, 'dismissed')" class="text-gray-400 hover:underline font-bold" title="Bỏ qua báo cáo này">
                  ❌ Bỏ qua
                </button>
              </template>
              
              <!-- Nhóm nút Xử lý Quiz (Yêu cầu của Leader) -->
              <span v-if="report.status === 'pending'" class="text-[var(--border-strong)]">|</span>
              
              <RouterLink :to="`/admin/quizzes/${report.quiz_id}/edit`" class="text-amber-400 hover:underline font-bold">
                Sửa
              </RouterLink>
              <button @click="deleteQuiz(report.quiz_id, report.id)" class="text-rose-400 hover:underline font-bold">
                Xóa Quiz
              </button>
            </td>
          </tr>
          
          <tr v-if="!isLoading && reports.length === 0">
            <td colspan="5" class="text-center py-10 text-[var(--muted)] font-bold">Hiện chưa có báo cáo vi phạm nào</td>
          </tr>
        </tbody>
      </table>
      <div v-if="isLoading" class="text-center py-10 text-[var(--muted)] font-bold">Đang tải dữ liệu...</div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted, inject } from 'vue'
import api, { reportApi } from '@/services/api' 

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const reports = ref([])
const isLoading = ref(true)
let pollingInterval = null

// Data Fetching
// Thêm tham số isBackground để phân biệt tải lần đầu và tải ngầm
const fetchReports = async (isBackground = false) => {
  if (!isBackground) isLoading.value = true
  try {
    reports.value = await reportApi.listAdmin()
  } catch (error) {
    console.error("Lỗi khi lấy danh sách báo cáo:", error)
    if (!isBackground && showToast) showToast('Không thể tải danh sách báo cáo', 'error')
  } finally {
    if (!isBackground) isLoading.value = false
  }
}

// Xử lý Report (Đã xử lý / Bỏ qua)
const updateStatus = async (id, status) => {
  const confirmMsg = status === 'resolved' 
    ? 'Bạn có chắc chắn muốn đánh dấu báo cáo này là "Đã xử lý"?' 
    : 'Bạn muốn bỏ qua và không xử lý báo cáo này?'
    
  if (showConfirm) {
    showConfirm('Xác nhận thao tác', confirmMsg, async () => {
      try {
        await reportApi.updateAdminStatus(id, status)
        const index = reports.value.findIndex(r => r.id === id)
        if (index !== -1) reports.value[index].status = status
        window.dispatchEvent(new CustomEvent('notifications-updated'))
        if (showToast) showToast('Cập nhật trạng thái thành công')
      } catch (error) {
        if (showToast) showToast('Có lỗi xảy ra khi cập nhật!', 'error')
      }
    })
  }
}

// Xử lý Xóa Quiz trực tiếp từ Report
const deleteQuiz = (quizId, reportId) => {
  if (showConfirm) {
    showConfirm(
      'Xác nhận xóa Quiz',
      'Bạn có chắc chắn muốn xóa mềm Quiz vi phạm này không? Khi xóa Quiz, báo cáo này cũng sẽ được đánh dấu là "Đã xử lý".',
      async () => {
        try {
          await api.delete(`/admin/quizzes/${quizId}`)
          
          await reportApi.updateAdminStatus(reportId, 'resolved')
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

// Helpers cho UI
const getStatusBadge = (status) => {
  const base = "rounded-full px-3 py-1 text-xs font-black uppercase tracking-wider border inline-block "
  switch (status) {
    case 'pending': return base + "border-amber-500/30 bg-amber-500/10 text-amber-400"
    case 'resolved': return base + "border-emerald-500/30 bg-emerald-500/10 text-emerald-400"
    case 'dismissed': return base + "border-gray-500/30 bg-gray-500/10 text-gray-400"
    default: return base + "border-gray-500/30 bg-gray-500/10 text-gray-400"
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

const handleRealtimeNotification = (e) => {
  const notification = e.detail
  if (['report_created', 'report_resolved', 'report_action'].includes(notification?.type)) {
    fetchReports(true)
  }
}

const handleNotificationsUpdated = () => {
  fetchReports(true)
}

onMounted(() => {
  fetchReports()
  window.addEventListener('realtime-notification', handleRealtimeNotification)
  window.addEventListener('notifications-updated', handleNotificationsUpdated)
})

onUnmounted(() => {
  window.removeEventListener('realtime-notification', handleRealtimeNotification)
  window.removeEventListener('notifications-updated', handleNotificationsUpdated)
})
</script>