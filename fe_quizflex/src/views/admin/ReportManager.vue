<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-red-600">Kiểm duyệt nội dung</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Quản lý Báo cáo vi phạm</h1>
        <p class="mt-1 text-sm text-slate-600">Xem xét và giải quyết các báo cáo vi phạm nội dung hoặc cảnh báo từ khóa phản cảm.</p>
      </div>
      <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" @click="fetchReports(false)">
        🔄 Làm mới
      </button>
    </div>

    <!-- Table Card -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
              <th class="py-3 px-4 text-center">Trạng thái</th>
              <th class="py-3 px-4">Đề thi bị báo cáo</th>
              <th class="py-3 px-4 text-center">Lý do / Nguồn</th>
              <th class="py-3 px-4">Chi tiết phản ánh</th>
              <th class="py-3 px-4 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="report in reports" :key="report.id" class="hover:bg-slate-50">
              <td class="py-3.5 px-4 text-center">
                <span class="rounded px-2 py-0.5 text-[10px] font-bold" :class="getStatusBadgeClass(report.status)">
                  {{ getStatusText(report.status) }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <router-link :to="`/quizzes/${report.quiz_id}`" target="_blank" class="font-bold text-[#7C3AED] hover:underline block max-w-xs truncate">
                  {{ report.quiz?.title || `Quiz #${report.quiz_id}` }}
                </router-link>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="font-bold text-red-600 block">{{ report.reason }}</span>
                <span v-if="report.reason.includes('thô tục') || report.reason.includes('Profanity')" class="text-[9px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded font-bold uppercase mt-1 inline-block">
                  🤖 AI lọc từ
                </span>
              </td>
              <td class="py-3.5 px-4 text-slate-500 max-w-xs truncate" :title="report.description">
                {{ report.description || '-' }}
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-2 text-xs">
                  <template v-if="report.status === 'pending'">
                    <button @click="updateStatus(report.id, 'resolved')" class="text-emerald-700 hover:underline font-bold" title="Duyệt và đánh dấu đã xử lý">
                      ✓ Duyệt
                    </button>
                    <button @click="updateStatus(report.id, 'dismissed')" class="text-slate-400 hover:underline font-bold" title="Bỏ qua báo cáo này">
                      ✕ Bỏ qua
                    </button>
                    <span class="text-slate-300">|</span>
                  </template>
                  
                  <RouterLink :to="`/admin/quizzes/${report.quiz_id}/edit`" class="text-amber-600 hover:underline font-bold">
                    Sửa
                  </RouterLink>
                  <button @click="deleteQuiz(report.quiz_id, report.id)" class="text-red-600 hover:underline font-bold">
                    Xóa Quiz
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!isLoading && reports.length === 0">
              <td colspan="5" class="py-10 text-center text-slate-400 text-xs">Hiện không có báo cáo vi phạm nào.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="isLoading" class="text-center py-8 text-xs text-slate-400">Đang tải dữ liệu báo cáo...</div>
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

const getStatusBadgeClass = (status) => ({
  pending: 'bg-amber-100 text-amber-800',
  resolved: 'bg-emerald-100 text-emerald-800',
  dismissed: 'bg-slate-100 text-slate-600',
}[status] || 'bg-slate-100 text-slate-600')

const getStatusText = (status) => ({
  pending: 'Chờ xử lý',
  resolved: 'Đã xử lý',
  dismissed: 'Đã bỏ qua',
}[status] || status)

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