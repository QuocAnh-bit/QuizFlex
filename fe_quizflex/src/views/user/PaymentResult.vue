<template>
  <section class="max-w-xl mx-auto py-16 px-4">
    <div class="card p-8 sm:p-10 text-center space-y-6">
      <!-- Loading State -->
      <div v-if="isLoading" class="py-12 space-y-3">
        <div class="h-10 w-10 border-4 border-purple-200 border-t-[#7C3AED] rounded-full animate-spin mx-auto"></div>
        <h2 class="text-lg font-bold text-slate-900">Đang kiểm tra kết quả giao dịch</h2>
        <p class="text-xs text-slate-500">Vui lòng chờ giây lát, hệ thống đang đối soát với cổng thanh toán...</p>
      </div>

      <!-- Success State -->
      <div v-else-if="isSuccess" class="space-y-6">
        <div class="h-16 w-16 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-2xl font-black mx-auto">
          ✓
        </div>

        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Thanh toán thành công</span>
          <h2 class="mt-1 text-2xl font-black text-slate-900">Chúc mừng bạn đã nâng cấp!</h2>
          <p class="mt-1 text-xs text-slate-600">Quyền lợi tài khoản và hạn mức AI đã được kích hoạt ngay lập tức.</p>
        </div>

        <!-- Receipt Card -->
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-xs space-y-2.5 text-left">
          <div class="flex items-center justify-between">
            <span class="text-slate-500 font-medium">Mã giao dịch:</span>
            <span class="font-mono font-bold text-slate-900">{{ resultData.order_code }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-500 font-medium">Số tiền đã nạp:</span>
            <span class="font-black text-emerald-700">{{ formatPrice(resultData.amount) }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-500 font-medium">Gói tài khoản mới:</span>
            <span class="rounded bg-purple-100 text-[#7C3AED] px-2 py-0.5 font-bold uppercase text-[10px]">
              👑 {{ resultData.user?.role }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-500 font-medium">Lượt AI khả dụng:</span>
            <span class="font-bold text-[#7C3AED]">{{ resultData.user?.ai_quota_remaining }} lượt</span>
          </div>
          <div class="flex items-center justify-between" v-if="resultData.user?.vip_expires_at">
            <span class="text-slate-500 font-medium">Hạn sử dụng gói:</span>
            <span class="font-bold text-slate-800">{{ formatDate(resultData.user?.vip_expires_at) }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="grid grid-cols-2 gap-3 pt-2">
          <router-link to="/" class="btn-secondary text-xs py-2.5">
            Về trang chủ
          </router-link>
          <router-link to="/dashboard/questions/ai" class="btn-primary text-xs py-2.5">
            Trải nghiệm AI ngay →
          </router-link>
        </div>
      </div>

      <!-- Failure State -->
      <div v-else class="space-y-6">
        <div class="h-16 w-16 rounded-full bg-red-50 text-red-600 border border-red-200 flex items-center justify-center text-2xl font-black mx-auto">
          ✕
        </div>

        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-red-600">Giao dịch chưa hoàn tất</span>
          <h2 class="mt-1 text-2xl font-black text-slate-900">Thanh toán không thành công</h2>
          <p class="mt-1 text-xs text-slate-600">Giao dịch bị hủy hoặc xảy ra lỗi trong quá trình xử lý ngân hàng.</p>
        </div>

        <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs font-bold text-red-700 text-left">
          ⚠ Chi tiết: {{ errorMsg || 'Giao dịch bị hủy bỏ bởi người dùng hoặc chữ ký không hợp lệ.' }}
        </div>

        <div class="grid grid-cols-2 gap-3 pt-2">
          <router-link to="/" class="btn-secondary text-xs py-2.5">
            Quay lại trang chủ
          </router-link>
          <router-link to="/upgrade" class="btn-primary text-xs py-2.5">
            Thử thanh toán lại
          </router-link>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { currentUserStorage, paymentsApi } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'

const { beginTask, endTask } = useAppLoading()

const route = useRoute()
const isLoading = ref(true)
const isSuccess = ref(false)
const errorMsg = ref('')
const resultData = ref({})

const verifyTransaction = async () => {
  const queryParams = route.query
  
  if (queryParams.orderCode) {
    try {
      const res = await paymentsApi.checkStatus(queryParams.orderCode)
      if (res.success && res.status === 'success') {
        isSuccess.value = true
        resultData.value = res
        if (res.user) {
          currentUserStorage.set(res.user)
        }
      } else {
        isSuccess.value = false
        let statusText = res.status
        if (res.status === 'failed') statusText = 'Thất bại'
        else if (res.status === 'cancelled') statusText = 'Đã hủy'
        else if (res.status === 'pending') statusText = 'Đang xử lý'
        errorMsg.value = `Giao dịch #${queryParams.orderCode} có trạng thái: ${statusText || 'Chưa hoàn tất'}`
      }
    } catch (error) {
      isSuccess.value = false
      errorMsg.value = error.message || 'Không thể kiểm tra trạng thái giao dịch PayOS.'
    } finally {
      isLoading.value = false
    }
    return
  }

  if (!queryParams.orderId || !queryParams.signature) {
    isLoading.value = false
    isSuccess.value = false
    errorMsg.value = 'Không tìm thấy thông tin xác minh giao dịch (Thiếu mã đơn hàng).'
    return
  }

  try {
    const res = await paymentsApi.callback(queryParams)
    if (res.success && res.status === 'success') {
      isSuccess.value = true
      resultData.value = res
      if (res.user) {
        currentUserStorage.set(res.user)
      }
    } else {
      isSuccess.value = false
      errorMsg.value = `Giao dịch đã kết thúc với trạng thái: ${res.status || 'unknown'}`
    }
  } catch (error) {
    isSuccess.value = false
    errorMsg.value = error.message || 'Xác minh chữ ký thanh toán thất bại tại hệ thống.'
  } finally {
    isLoading.value = false
  }
}

const formatPrice = (value) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0)

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(async () => {
  beginTask()
  try {
    await verifyTransaction()
  } finally {
    endTask()
  }
})
</script>
