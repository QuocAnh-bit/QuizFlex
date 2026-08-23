<template>
  <section class="space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <div class="inline-flex items-center gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">
            <Shield class="h-3.5 w-3.5" />
            <span>Audit & Moderation</span>
            <span class="text-rose-400">•</span>
            <span>Nhật ký Báo cáo Câu hỏi</span>
          </div>
          <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Lịch sử Báo cáo Vi phạm
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500">
            Theo dõi tất cả phản ánh từ người học đối với câu hỏi trong hệ thống. Quy trình kiểm duyệt và đính chính được xử lý tập trung tại mục Duyệt Ngân Hàng với độ ưu tiên cao.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-center">
            <span class="text-[10px] font-medium uppercase text-slate-500">Báo cáo chờ xử lý</span>
            <p class="text-xl font-bold text-rose-600">{{ pendingCount }}</p>
          </div>
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-center">
            <span class="text-[10px] font-medium uppercase text-slate-500">Đã giải quyết</span>
            <p class="text-xl font-bold text-emerald-600">{{ resolvedCount }}</p>
          </div>
          <router-link
            to="/admin/question-bank-requests?priority=high"
            class="inline-flex items-center gap-1.5 rounded-lg bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#6D28D9] transition"
          >
            <CheckSquare class="h-4 w-4" />
            <span>Mở Duyệt Ưu Tiên</span>
          </router-link>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer"
            @click="fetchReports(false)"
          >
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isLoading }" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Filters & Status bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
      <div class="flex items-center gap-2">
        <button
          v-for="st in statusTabs"
          :key="st.key"
          type="button"
          class="rounded-lg px-3 py-1.5 text-xs font-bold transition cursor-pointer"
          :class="selectedStatus === st.key ? 'bg-purple-50 text-[#7C3AED] border border-purple-200' : 'text-slate-600 hover:bg-slate-100'"
          @click="selectedStatus = st.key"
        >
          {{ st.label }} ({{ getCountByStatus(st.key) }})
        </button>
      </div>

      <div class="flex items-center gap-2">
        <div class="relative min-w-[240px]">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Tìm theo nội dung, người báo cáo..."
            class="w-full rounded-lg border border-slate-200 bg-slate-50 pl-8 pr-3 py-1.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:bg-white transition"
          />
        </div>
      </div>
    </div>

    <!-- Reports Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <th class="w-24 p-3.5 text-center">Trạng thái</th>
              <th class="p-3.5">Câu hỏi liên quan</th>
              <th class="p-3.5">Lý do & Mô tả</th>
              <th class="p-3.5">Người báo cáo</th>
              <th class="p-3.5">Thời gian</th>
              <th class="w-36 p-3.5 text-center">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="filteredReports.length === 0">
              <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
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
                <span
                  class="inline-block rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider"
                  :class="{
                    'bg-rose-100 text-rose-800 border border-rose-200': report.status === 'pending',
                    'bg-emerald-100 text-emerald-800 border border-emerald-200': report.status === 'resolved',
                    'bg-slate-100 text-slate-700': report.status === 'dismissed'
                  }"
                >
                  {{ formatStatus(report.status) }}
                </span>
              </td>

              <!-- Question Content -->
              <td class="p-3.5 align-top max-w-sm">
                <div class="space-y-1">
                  <div class="flex items-center gap-1.5">
                    <span class="font-bold text-slate-900">Câu hỏi #{{ report.question_id }}</span>
                    <span
                      v-if="report.question?.is_public"
                      class="rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 border border-emerald-200"
                    >
                      Public Snapshot
                    </span>
                    <span
                      v-else
                      class="rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold text-slate-600"
                    >
                      Private
                    </span>
                  </div>
                  <p class="text-slate-600 line-clamp-2 italic">
                    "{{ report.question?.content || 'N/A' }}"
                  </p>
                </div>
              </td>

              <!-- Reason & Description -->
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
                  <p class="font-bold text-slate-800">{{ report.user?.name || 'Ẩn danh' }}</p>
                  <p class="text-[10px] text-slate-400">{{ report.user?.email }}</p>
                </div>
              </td>

              <!-- Created At -->
              <td class="p-3.5 align-top text-slate-500 whitespace-nowrap">
                {{ formatDate(report.created_at) }}
              </td>

              <!-- Actions -->
              <td class="p-3.5 align-top text-center">
                <router-link
                  :to="`/admin/question-bank-requests?search=${report.question_id}`"
                  class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-purple-50 hover:text-[#7C3AED] hover:border-purple-200 transition"
                  title="Mở Question Review"
                >
                  <ExternalLink class="h-3 w-3" />
                  <span>Xem Review</span>
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Shield,
  RefreshCw,
  Search,
  CheckSquare,
  ExternalLink,
} from 'lucide-vue-next'
import { reportApi } from '@/services/api'

const reports = ref([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('all')

const statusTabs = [
  { key: 'all', label: 'Tất cả' },
  { key: 'pending', label: 'Chờ xử lý' },
  { key: 'resolved', label: 'Đã giải quyết' },
]

const fetchReports = async (isBackground = false) => {
  if (!isBackground) isLoading.value = true
  try {
    const data = await reportApi.listAdmin()
    reports.value = Array.isArray(data) ? data : (data?.data ?? [])
  } catch (error) {
    console.error('Lỗi khi lấy danh sách báo cáo:', error)
  } finally {
    if (!isBackground) isLoading.value = false
  }
}

const pendingCount = computed(() => reports.value.filter(r => r.status === 'pending').length)
const resolvedCount = computed(() => reports.value.filter(r => r.status === 'resolved').length)

const getCountByStatus = (statusKey) => {
  if (statusKey === 'all') return reports.value.length
  return reports.value.filter(r => r.status === statusKey).length
}

const filteredReports = computed(() => {
  let list = reports.value
  if (selectedStatus.value !== 'all') {
    list = list.filter(r => r.status === selectedStatus.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(r => {
      const qContent = r.question?.content?.toLowerCase() || ''
      const qId = String(r.question_id || '')
      const reporter = r.user?.name?.toLowerCase() || ''
      const reason = r.reason?.toLowerCase() || ''
      return qContent.includes(q) || qId.includes(q) || reporter.includes(q) || reason.includes(q)
    })
  }
  return list
})

const formatStatus = (st) => {
  switch (st) {
    case 'pending': return 'Chờ xử lý'
    case 'resolved': return 'Đã giải quyết'
    case 'dismissed': return 'Đã đóng'
    default: return st
  }
}

const formatDate = (d) => {
  if (!d) return 'N/A'
  try {
    return new Date(d).toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return d
  }
}

onMounted(() => {
  fetchReports()
})
</script>