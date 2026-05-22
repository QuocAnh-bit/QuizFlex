<template>
  <section class="max-w-[600px] mx-auto py-16 px-4">
    
    <!-- Glassmorphic Card Container -->
    <div class="relative overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)] p-8 md:p-10 text-center shadow-[var(--shadow-card)] backdrop-blur-3xl">
      <div class="pointer-events-none absolute -right-20 -top-20 h-44 w-44 rounded-full bg-[var(--primary)]/10 blur-3xl"></div>
      
      <!-- Loading State -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center gap-4 py-8">
        <div class="h-14 w-14 animate-spin rounded-full border-[5px] border-[var(--border)] border-t-[var(--primary)] shadow-md"></div>
        <h2 class="text-2xl font-black text-[var(--text)] mt-3">Đang Xác Minh Giao Dịch</h2>
        <p class="text-sm font-semibold text-[var(--muted)]">Vui lòng chờ trong giây lát, hệ thống đang kiểm tra chữ ký và cập nhật tài khoản của bạn...</p>
      </div>

      <!-- Success State -->
      <div v-else-if="isSuccess" class="grid gap-6 py-4">
        <!-- Success Animated Circle -->
        <div class="mx-auto h-20 w-20 rounded-full bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-4xl text-emerald-400 shadow-[0_0_30px_rgba(16,185,129,0.2)] animate-pulse">
          ✓
        </div>

        <div class="grid gap-2">
          <span class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">Giao dịch thành công</span>
          <h2 class="text-3xl md:text-4xl font-black text-[var(--text)] tracking-tight">Chào Mừng Bạn Đến Với VIP!</h2>
          <p class="text-sm font-semibold text-[var(--muted)] leading-relaxed">
            Hệ thống đã ghi nhận thanh toán và nâng cấp tài khoản của bạn thành công. Quyền lợi VIP đã được kích hoạt ngay lập tức!
          </p>
        </div>

        <!-- Receipt Box -->
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-left text-sm font-semibold grid gap-3">
          <div class="flex items-center justify-between">
            <span class="text-[var(--muted)]">Mã giao dịch:</span>
            <span class="font-mono text-xs text-[var(--text)]">{{ resultData.order_code }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[var(--muted)]">Số tiền thanh toán:</span>
            <span class="font-black text-[var(--accent)]">{{ formatPrice(resultData.amount) }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[var(--muted)]">Loại tài khoản mới:</span>
            <span class="inline-flex items-center gap-1 rounded bg-amber-500/10 text-amber-400 px-2 py-0.5 text-xs font-black uppercase border border-amber-500/20">
              👑 {{ resultData.user?.role }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[var(--muted)]">AI Quota hiện tại:</span>
            <span class="font-black text-[var(--primary)]">{{ resultData.user?.ai_quota_remaining }} lượt sử dụng</span>
          </div>
          <div class="flex items-center justify-between" v-if="resultData.user?.vip_expires_at">
            <span class="text-[var(--muted)]">Hạn định VIP:</span>
            <span class="text-[var(--text)]">{{ formatDate(resultData.user?.vip_expires_at) }}</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <router-link 
            to="/" 
            class="h-12 flex items-center justify-center font-black rounded-full border border-[var(--border-strong)] bg-[var(--surface-soft)] text-[var(--text)] transition hover:bg-[var(--chip-active)] active:scale-[0.98]"
          >
            Về Trang Chủ
          </router-link>
          
          <router-link 
            to="/dashboard/questions/ai" 
            class="h-12 flex items-center justify-center font-black rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] text-white shadow-[0_16px_36px_rgba(155,44,255,0.25)] transition hover:-translate-y-0.5 active:scale-[0.98]"
          >
            Trải Nghiệm AI Ngay
          </router-link>
        </div>
      </div>

      <!-- Failure State -->
      <div v-else class="grid gap-6 py-4">
        <!-- Error Circle -->
        <div class="mx-auto h-20 w-20 rounded-full bg-rose-500/10 border border-rose-500/25 flex items-center justify-center text-4xl text-rose-400 shadow-[0_0_30px_rgba(244,63,94,0.15)] animate-bounce">
          ✕
        </div>

        <div class="grid gap-2">
          <span class="text-xs font-black uppercase tracking-[0.2em] text-rose-400">Giao dịch thất bại</span>
          <h2 class="text-3xl font-black text-[var(--text)] tracking-tight">Thanh Toán Chưa Hoàn Tất</h2>
          <p class="text-sm font-semibold text-[var(--muted)] leading-relaxed">
            Hệ thống không thể xác minh thanh toán này. Có thể bạn đã hủy giao dịch hoặc có lỗi xảy ra từ phía ngân hàng.
          </p>
        </div>

        <!-- Error Alert Message -->
        <div class="rounded-2xl border border-rose-500/25 bg-rose-500/10 p-4 text-sm font-bold text-rose-400 text-left">
          ⚠ Chi tiết lỗi: {{ errorMsg || 'Giao dịch bị hủy bỏ bởi người dùng hoặc chữ ký xác minh không hợp lệ.' }}
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <router-link 
            to="/" 
            class="h-12 flex items-center justify-center font-black rounded-full border border-[var(--border-strong)] bg-[var(--surface-soft)] text-[var(--text)] transition hover:bg-[var(--chip-active)] active:scale-[0.98]"
          >
            Quay Về Trang Chủ
          </router-link>
          
          <router-link 
            to="/upgrade" 
            class="h-12 flex items-center justify-center font-black rounded-full bg-rose-500 text-white shadow-[0_16px_36px_rgba(244,63,94,0.2)] transition hover:-translate-y-0.5 active:scale-[0.98]"
          >
            Thử Lại Thanh Toán
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

const route = useRoute()

const isLoading = ref(true)
const isSuccess = ref(false)
const errorMsg = ref('')
const resultData = ref({})

onMounted(() => {
  verifyTransaction()
})

const verifyTransaction = async () => {
  // Capture all redirect query parameters sent from MoMo sandbox
  const queryParams = route.query
  
  if (!queryParams.orderId || !queryParams.signature) {
    isLoading.value = false
    isSuccess.value = false
    errorMsg.value = 'Không tìm thấy thông tin xác minh giao dịch (Thiếu orderId hoặc chữ ký).'
    return
  }

  try {
    // 1. Gọi backend verify signature & sync trạng thái
    const res = await paymentsApi.callback(queryParams)
    
    if (res.success && res.status === 'success') {
      isSuccess.value = true
      resultData.value = res
      
      // 2. Tự động đồng bộ tài khoản mới trong localStorage của Client
      // Điều này tự động thay đổi Avatar, Quota và Role VIP ngay lập tức mà không cần reload!
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

// Helpers
const formatPrice = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

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
</script>
