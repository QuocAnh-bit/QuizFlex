<template>
  <section class="max-w-[1280px] mx-auto py-10 px-4 grid gap-10">
    
    <!-- Hero Banner with Neon Glow -->
    <div class="relative overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)]/60 p-8 md:p-12 text-center shadow-[var(--shadow-card)] backdrop-blur-3xl">
      <div class="pointer-events-none absolute left-1/2 top-0 h-96 w-96 -translate-x-1/2 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="pointer-events-none absolute right-10 bottom-0 h-48 w-48 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>
      
      <div class="relative z-10 max-w-3xl mx-auto">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-1.5 text-xs font-black uppercase tracking-wider text-[var(--primary)] shadow-[0_4px_12px_rgba(155,44,255,0.12)]">
          ✦ Premium Access
        </span>
        <h1 class="mt-5 text-4xl md:text-6xl font-black tracking-tight text-[var(--text)] leading-none">
          Mở Khóa Toàn Bộ <span class="bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] bg-clip-text text-transparent">Đặc Quyền Premium</span>
        </h1>
        <p class="mt-4 text-base leading-relaxed text-[var(--muted)]">
          Nâng cao khả năng học tập và giảng dạy với sức mạnh của AI sinh đề, scan tài liệu không giới hạn và tạo phòng thi đấu trực tuyến thời gian thực.
        </p>
      </div>
    </div>

    <!-- Trial Promo Banner / Active Status Banner -->
    <div v-if="currentUser" class="grid gap-6">
      <!-- Trial Promo Banner -->
      <div 
        v-if="currentUser.role === 'free' && !currentUser.trial_used_at"
        class="relative overflow-hidden rounded-[2rem] border border-emerald-500/30 bg-emerald-500/5 p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_8px_32px_rgba(16,185,129,0.08)] backdrop-blur-md"
      >
        <div class="pointer-events-none absolute left-0 top-0 h-full w-48 bg-gradient-to-r from-emerald-500/10 to-transparent"></div>
        <div class="relative z-10 text-left">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-400 border border-emerald-500/20 mb-2">
            ✦ Trải nghiệm miễn phí
          </span>
          <h3 class="text-xl md:text-2xl font-black text-[var(--text)]">Dùng thử Gói Plus Miễn Phí 7 Ngày</h3>
          <p class="text-sm text-[var(--muted)] mt-1 font-semibold leading-relaxed">
            Trải nghiệm đầy đủ đặc quyền của gói **Plus** (tạo phòng thi đấu, phòng bài tập và OCR scan) để tăng tốc học tập.<br>
            Nhận ngay **+20 lượt AI** sinh đề. Sử dụng một lần duy nhất cho mỗi tài khoản.
          </p>
        </div>
        <button 
          @click="activateFreeTrial"
          :disabled="isActivatingTrial"
          class="relative z-10 shrink-0 h-12 px-6 flex items-center justify-center font-black rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-[0_12px_24px_rgba(16,185,129,0.2)] hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition duration-300 disabled:opacity-50"
        >
          {{ isActivatingTrial ? 'Đang kích hoạt...' : 'Kích Hoạt Dùng Thử 🚀' }}
        </button>
      </div>

      <!-- Active Trial Status Banner -->
      <div 
        v-else-if="currentUser.role === 'plus' && currentUser.trial_used_at && isCurrentlyInTrial"
        class="relative overflow-hidden rounded-[2rem] border border-[var(--primary)]/30 bg-[var(--primary)]/5 p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_8px_32px_rgba(124,58,237,0.08)] backdrop-blur-md"
      >
        <div class="relative z-10 text-left">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-[var(--primary)]/20 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-[var(--primary)] border border-[var(--primary)]/20 mb-2">
            ✦ Trạng thái dùng thử
          </span>
          <h3 class="text-xl md:text-2xl font-black text-[var(--text)] flex items-center gap-2">
            <span>✨</span> Bạn đang trong thời gian dùng thử Plus
          </h3>
          <p class="text-sm text-[var(--muted)] mt-1 font-semibold">
            Đặc quyền Plus dùng thử của bạn sẽ kết thúc vào ngày: <span class="text-[var(--primary)] font-black">{{ formatTrialExpiry(currentUser.vip_expires_at) }}</span>.<br>
            Sau thời gian dùng thử, tài khoản của bạn sẽ tự động trở lại gói Free mà không làm mất dữ liệu.
          </p>
        </div>
        <div class="text-xs font-black text-[var(--muted)] shrink-0 bg-[var(--surface-soft)] px-4 py-2 rounded-full border border-[var(--border)]">
          Hết hạn sẽ tự động về gói Free
        </div>
      </div>
    </div>

    <!-- Pricing Cards Grid -->
    <div class="grid gap-6 md:grid-cols-3">
      <article 
        v-for="plan in plans" 
        :key="plan.id" 
        class="relative overflow-hidden rounded-[2.2rem] border p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl transition duration-300 flex flex-col justify-between"
        :class="plan.popular 
          ? 'border-[var(--primary)]/50 bg-[var(--surface-strong)]/80 scale-[1.03] shadow-[0_24px_50px_rgba(155,44,255,0.15)] ring-1 ring-[var(--primary)]/30' 
          : 'border-[var(--border)] bg-[var(--surface)]/50 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-soft)]'
        "
      >
        <!-- Popular Badge -->
        <span 
          v-if="plan.popular" 
          class="absolute top-4 right-4 bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full shadow-md"
        >
          Bán Chạy Nhất
        </span>

        <div>
          <!-- Plan Header -->
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <span class="text-3xl">{{ plan.icon }}</span>
              <div>
                <p class="font-black text-xl text-[var(--text)]">{{ plan.name }}</p>
                <p class="text-xs font-semibold text-[var(--muted)]">
                  {{ plan.id === 'plus_1m' && showPlusTrial ? 'Thời hạn 7 ngày' : plan.period }}
                </p>
              </div>
            </div>
            <!-- Trial Badge for Plus -->
            <span 
              v-if="plan.id === 'plus_1m' && showPlusTrial"
              class="bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full border border-emerald-500/20 shrink-0"
            >
              Có Dùng Thử
            </span>
          </div>

          <!-- Pricing -->
          <div class="mt-6 flex items-baseline gap-1 flex-wrap">
            <template v-if="plan.id === 'plus_1m' && showPlusTrial">
              <span class="text-xl line-through text-[var(--muted)] font-black mr-2">50.000đ</span>
              <h2 class="text-4xl font-black tracking-tight text-[var(--text)]">0đ</h2>
              <span class="text-sm font-semibold text-[var(--muted)]">/7 ngày dùng thử</span>
            </template>
            <template v-else>
              <h2 class="text-4xl font-black tracking-tight text-[var(--text)]">{{ plan.priceLabel }}</h2>
              <span v-if="plan.price > 0" class="text-sm font-semibold text-[var(--muted)]">/gói</span>
            </template>
          </div>

          <!-- Benefits Description -->
          <div class="mt-4 pb-4 border-b border-[var(--border)] text-xs text-[var(--muted)] font-semibold">
            {{ plan.desc }}
          </div>

          <!-- Feature List -->
          <div class="mt-6 grid gap-3">
            <div 
              v-for="feature in plan.features" 
              :key="feature" 
              class="flex items-center gap-2.5 text-sm font-bold text-[var(--text)]"
            >
              <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--primary)]/10 text-[var(--primary)] text-xs">✓</span>
              <span>{{ feature }}</span>
            </div>
          </div>
        </div>

        <!-- Action Button / Dual buttons for Plus Trial -->
        <div class="mt-8">
          <button 
            v-if="plan.id === 'plus_1m' && showPlusTrial"
            @click="activateFreeTrial"
            class="w-full h-12 flex items-center justify-center font-black rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-[0_12px_24px_rgba(16,185,129,0.2)] hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition duration-300"
          >
            Dùng thử Plus miễn phí 7 ngày
          </button>
          <template v-else>
            <!-- Nếu đã sở hữu gói hiện tại -->
            <button 
              v-if="currentUser && isUserCurrentPlan(plan.id)"
              disabled
              class="w-full h-12 flex items-center justify-center font-black rounded-full border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 cursor-not-allowed gap-2"
            >
              <span class="inline-block h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
              ✓ Đang sử dụng
            </button>
            <!-- Nếu là hạ cấp (không được phép) -->
            <button 
              v-else-if="currentUser && plan.upgradeInfo && !plan.upgradeInfo.allowed"
              disabled
              class="w-full h-12 flex items-center justify-center font-black rounded-full border border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] cursor-not-allowed text-xs px-2 gap-1.5"
            >
              🔒 Không thể hạ cấp
            </button>
            <!-- Nút mua / nâng cấp hợp lệ -->
            <button 
              v-else
              @click="openCheckout(plan)"
              class="w-full h-12 flex items-center justify-center font-black rounded-full transition duration-300 active:scale-[0.98]"
              :class="plan.popular
                ? 'bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] text-white hover:shadow-[0_16px_36px_rgba(155,44,255,0.3)]'
                : 'border border-[var(--border-strong)] bg-[var(--surface-soft)] text-[var(--text)] hover:bg-[var(--chip-active)] hover:border-[var(--primary)]/50'
              "
            >
              {{ plan.upgradeInfo && plan.upgradeInfo.unused_value > 0 ? 'Nâng cấp ngay' : plan.btnText }}
            </button>
          </template>
        </div>
      </article>
    </div>

    <!-- Transaction History (Show only when logged in) -->
    <div 
      v-if="currentUser" 
      class="overflow-hidden rounded-[2.2rem] border border-[var(--border)] bg-[var(--surface)]/40 p-6 md:p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl"
    >
      <h3 class="text-2xl font-black text-[var(--text)] flex items-center gap-2">
        <span>🕒</span> Lịch Sử Giao Dịch
      </h3>
      <p class="text-sm text-[var(--muted)] mt-1 font-semibold">Theo dõi và quản lý các hóa đơn thanh toán của bạn.</p>

      <div class="mt-6 overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm font-semibold">
          <thead>
            <tr class="border-b border-[var(--border)] text-[var(--muted)]">
              <th class="pb-3 pr-4 font-black">Mã giao dịch</th>
              <th class="pb-3 px-4 font-black">Gói</th>
              <th class="pb-3 px-4 font-black">Cổng thanh toán</th>
              <th class="pb-3 px-4 font-black">Số tiền</th>
              <th class="pb-3 px-4 font-black">Trạng thái</th>
              <th class="pb-3 pl-4 font-black">Ngày tạo</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="item in historyList" 
              :key="item.id" 
              class="border-b border-[var(--border)]/50 hover:bg-[var(--surface-soft)]/20 transition"
            >
              <td class="py-4 pr-4 font-mono text-xs text-[var(--text)]">{{ item.order_code }}</td>
              <td class="py-4 px-4 text-[var(--text)]">{{ item.plan_name || getPlanNameByAmount(item.amount) }}</td>
              <td class="py-4 px-4 text-xs font-bold uppercase text-[var(--muted)]">
                <span class="inline-flex items-center gap-1 rounded bg-fuchsia-500/10 text-fuchsia-400 px-2 py-0.5" v-if="item.provider === 'momo'">
                  MoMo
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-emerald-500/10 text-emerald-400 px-2 py-0.5" v-else-if="item.provider === 'payos'">
                  VietQR (PayOS)
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-purple-500/10 text-purple-400 px-2 py-0.5" v-else-if="item.provider === 'trial'">
                  Dùng thử
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-blue-500/10 text-blue-400 px-2 py-0.5" v-else>
                  VNPay
                </span>
              </td>
              <td class="py-4 px-4 text-[var(--text)] font-black">{{ formatPrice(item.amount) }}</td>
              <td class="py-4 px-4 text-xs">
                <span 
                  class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-black uppercase text-[10px]"
                  :class="getStatusBadgeClass(item.status)"
                >
                  {{ getStatusText(item.status) }}
                </span>
              </td>
              <td class="py-4 pl-4 text-[var(--muted)] text-xs">{{ formatDate(item.created_at) }}</td>
            </tr>
            <tr v-if="historyList.length === 0">
              <td colspan="6" class="py-10 text-center text-[var(--muted)] font-bold">
                Bạn chưa có giao dịch nào trên hệ thống.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Checkout Modal Pop-up -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isPaymentModalOpen" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
        @click.self="closeCheckout"
      >
        <div class="relative overflow-hidden w-full max-w-[500px] rounded-[2.2rem] border border-[var(--border-strong)] bg-[var(--surface)] p-6 md:p-8 shadow-[0_24px_80px_rgba(0,0,0,0.4)] transition">
          <div class="pointer-events-none absolute -right-20 -top-20 h-44 w-44 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
          
          <!-- Close Button -->
          <button 
            @click="closeCheckout"
            class="absolute top-4 right-4 h-9 w-9 flex items-center justify-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] text-[var(--text)] transition hover:border-[var(--border-strong)] active:scale-95"
          >
            ✕
          </button>

          <!-- Modal Header -->
          <div class="relative z-10 text-center">
            <span class="text-4xl">{{ selectedPlan.icon }}</span>
            <h4 class="mt-3 text-2xl font-black text-[var(--text)]">Xác Nhận Nâng Cấp</h4>
            <p class="text-sm text-[var(--muted)] mt-1 font-semibold">Vui lòng chọn phương thức thanh toán.</p>
          </div>

          <!-- Order Summary Card -->
          <div class="mt-6 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 relative z-10 grid gap-2">
            <div class="flex items-center justify-between font-bold text-sm">
              <span class="text-[var(--muted)]">Gói cước:</span>
              <span class="text-[var(--text)]">{{ selectedPlan.name }}</span>
            </div>
            <div class="flex items-center justify-between font-bold text-sm">
              <span class="text-[var(--muted)]">Hạn định:</span>
              <span class="text-[var(--text)]">{{ selectedPlan.period }}</span>
            </div>
            <div class="flex items-center justify-between font-bold text-sm">
              <span class="text-[var(--muted)]">AI Quota cộng thêm:</span>
              <span class="text-[var(--primary)]">+{{ selectedPlan.quota }} lượt dùng</span>
            </div>
            
            <div v-if="selectedPlan.upgradeInfo && selectedPlan.upgradeInfo.unused_value > 0" class="flex items-center justify-between font-bold text-sm">
              <span class="text-[var(--muted)]">Giá gốc gói mới:</span>
              <span class="text-[var(--text)] line-through">{{ selectedPlan.priceLabel }}</span>
            </div>
            
            <!-- Khấu trừ chênh lệch hiển thị trong Modal xác nhận -->
            <div 
              v-if="selectedPlan.upgradeInfo && selectedPlan.upgradeInfo.unused_value > 0"
              class="flex flex-col gap-0.5 mt-1 pt-1 border-t border-dashed border-[var(--border)]"
            >
              <div class="flex items-center justify-between font-bold text-sm text-emerald-400">
                <span>Khấu trừ gói cũ:</span>
                <span>-{{ formatPrice(selectedPlan.upgradeInfo.unused_value) }}</span>
              </div>
              <div class="text-right text-[10px] text-[var(--muted)] font-semibold">
                (Thời gian gói cũ còn lại: {{ formatRemainingTime(selectedPlan.upgradeInfo.remaining_days) }})
              </div>
            </div>

            <div class="mt-2 pt-2 border-t border-[var(--border)] flex items-center justify-between font-black text-base">
              <span class="text-[var(--text)]">Tổng tiền:</span>
              <span class="text-[var(--accent)] text-lg">
                {{ selectedPlan.upgradeInfo ? formatPrice(selectedPlan.upgradeInfo.amount) : selectedPlan.priceLabel }}
              </span>
            </div>
          </div>

          <!-- Error Alert -->
          <div 
            v-if="errorMessage" 
            class="mt-4 rounded-xl border border-rose-500/25 bg-rose-500/10 p-3 text-xs font-bold text-rose-400"
          >
            ⚠ {{ errorMessage }}
          </div>

          <!-- Payment Options -->
          <div class="mt-6 grid gap-3 relative z-10">
            <!-- PayOS VietQR -->
            <button 
              @click="handlePayment('payos')"
              :disabled="isProcessing"
              class="flex items-center justify-between rounded-[1.25rem] border border-emerald-500/40 bg-emerald-500/5 p-4 transition duration-300 hover:border-emerald-400 hover:bg-emerald-500/10 active:scale-[0.98] group disabled:opacity-50 relative overflow-hidden shadow-[0_0_15px_rgba(16,185,129,0.05)]"
            >
              <div class="pointer-events-none absolute right-0 top-0 h-16 w-16 rounded-full bg-emerald-500/10 blur-xl"></div>
              <div class="flex items-center gap-3 relative z-10">
                <div class="h-10 w-10 shrink-0 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex flex-col items-center justify-center text-white shadow-md">
                  <span class="text-[10px] font-black leading-none">Viet</span>
                  <span class="text-[11px] font-black leading-none tracking-tight">QR</span>
                </div>
                <div class="text-left">
                  <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-400 border border-emerald-500/20 mb-1">
                    ✦ Khuyên dùng
                  </span>
                  <b class="block text-sm font-black text-[var(--text)] group-hover:text-emerald-400 transition">Thanh toán VietQR (PayOS)</b>
                  <span class="text-[10px] font-semibold text-[var(--muted)]">Quét mã chuyển khoản từ mọi App Ngân hàng (Sandbox)</span>
                </div>
              </div>
              <span class="text-xs font-black text-emerald-400 relative z-10">Chọn ➔</span>
            </button>

            <!-- MoMo Wallet -->
            <button 
              @click="handlePayment('momo')"
              :disabled="isProcessing"
              class="flex items-center justify-between rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition duration-300 hover:border-fuchsia-500 hover:bg-fuchsia-500/5 active:scale-[0.98] group disabled:opacity-50"
            >
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 shrink-0 rounded-xl bg-[#a21960] flex items-center justify-center text-white text-xs font-black shadow-md">
                  MoMo
                </div>
                <div class="text-left">
                  <b class="block text-sm font-black text-[var(--text)] group-hover:text-fuchsia-400 transition">Ví Điện Tử MoMo</b>
                  <span class="text-[10px] font-semibold text-[var(--muted)]">Thanh toán tức thì qua ứng dụng MoMo (Sandbox)</span>
                </div>
              </div>
              <span class="text-xs font-black text-fuchsia-400">Chọn ➔</span>
            </button>

            <!-- VNPay (Inactive placeholder in checklist step, but designed beautifully) -->
            <button 
              disabled
              class="flex items-center justify-between rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface-soft)]/40 p-4 opacity-50 cursor-not-allowed group"
            >
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 shrink-0 rounded-xl bg-blue-600 flex items-center justify-center text-white text-[10px] font-black shadow-md">
                  VNPay
                </div>
                <div class="text-left">
                  <b class="block text-sm font-black text-[var(--text)]">Cổng thanh toán VNPay</b>
                  <span class="text-[10px] font-semibold text-[var(--muted)]">Hỗ trợ ATM nội địa & thẻ quốc tế (Sắp ra mắt)</span>
                </div>
              </div>
              <span class="text-[10px] font-black text-[var(--muted)]">Sắp có</span>
            </button>
          </div>

          <!-- Loading Spinner overlay inside modal -->
          <div 
            v-if="isProcessing" 
            class="absolute inset-0 bg-[var(--surface)]/90 backdrop-blur-sm z-30 flex flex-col items-center justify-center gap-3"
          >
            <div class="h-10 w-10 animate-spin rounded-full border-4 border-[var(--border)] border-t-[var(--primary)]"></div>
            <p class="text-sm font-black text-[var(--text)]">Đang kết nối cổng thanh toán...</p>
            <p class="text-xs font-bold text-[var(--muted)]">Vui lòng không đóng cửa sổ này</p>
          </div>

        </div>
      </div>
    </Transition>


    <!-- Confirm Trial Modal -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isConfirmTrialModalOpen" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
        @click.self="closeConfirmTrialModal"
      >
        <div class="relative overflow-hidden w-full max-w-[500px] rounded-[2.2rem] border border-emerald-500/30 bg-[var(--surface)] p-6 md:p-8 shadow-[0_24px_80px_rgba(16,185,129,0.15)] transition">
          <div class="pointer-events-none absolute -right-20 -top-20 h-44 w-44 rounded-full bg-emerald-500/15 blur-3xl"></div>
          
          <!-- Close Button -->
          <button 
            @click="closeConfirmTrialModal"
            class="absolute top-4 right-4 h-9 w-9 flex items-center justify-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] text-[var(--text)] transition hover:border-[var(--border-strong)] active:scale-95"
          >
            ✕
          </button>

          <!-- Modal Header -->
          <div class="relative z-10 text-center">
            <span class="text-4xl">🎁</span>
            <h4 class="mt-3 text-2xl font-black text-[var(--text)]">Dùng Thử Plus 7 Ngày</h4>
            <p class="text-sm text-[var(--muted)] mt-1 font-semibold">Mở khóa đặc quyền VIP hoàn toàn miễn phí</p>
          </div>

          <!-- Trial Benefits and Info -->
          <div class="mt-6 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 relative z-10 grid gap-4">
            <div class="grid gap-2.5 text-sm text-[var(--text)]">
              <div class="flex items-start gap-2.5">
                <span class="text-emerald-400 mt-0.5">✨</span>
                <span class="font-bold leading-normal">Nhận ngay +20 lượt AI tạo đề.</span>
              </div>
              <div class="flex items-start gap-2.5">
                <span class="text-emerald-400 mt-0.5">✨</span>
                <span class="font-bold leading-normal">Mở khóa tính năng quét tài liệu bằng AI OCR.</span>
              </div>
              <div class="flex items-start gap-2.5">
                <span class="text-emerald-400 mt-0.5">✨</span>
                <span class="font-bold leading-normal">Tạo phòng thi đấu nhóm & phòng bài tập.</span>
              </div>
            </div>
            
            <div class="pt-3.5 border-t border-[var(--border)] text-xs text-[var(--muted)] leading-relaxed">
              <p class="font-bold text-amber-500/90 flex items-start gap-2">
                <span class="mt-0.5">⚠️</span>
                <span>Mỗi tài khoản chỉ được kích hoạt dùng thử duy nhất 1 lần. Hết hạn 7 ngày, tài khoản sẽ tự động chuyển về gói Free (không tự động trừ tiền hoặc gia hạn).</span>
              </p>
            </div>
          </div>

          <!-- Error Alert for Trial -->
          <div 
            v-if="trialErrorMessage" 
            class="mt-4 rounded-xl border border-rose-500/25 bg-rose-500/10 p-3 text-xs font-bold text-rose-400"
          >
            ⚠ {{ trialErrorMessage }}
          </div>

          <!-- Actions -->
          <div class="mt-6 grid gap-3 relative z-10">
            <button 
              @click="submitActivateTrial"
              :disabled="isActivatingTrial"
              class="h-12 w-full flex items-center justify-center font-black rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-[0_12px_24px_rgba(16,185,129,0.2)] hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] transition duration-300 disabled:opacity-50"
            >
              {{ isActivatingTrial ? 'Đang kích hoạt...' : 'Kích Hoạt Dùng Thử Ngay 🚀' }}
            </button>
            <button 
              @click="closeConfirmTrialModal"
              :disabled="isActivatingTrial"
              class="h-12 w-full flex items-center justify-center font-black rounded-full border border-[var(--border-strong)] bg-[var(--surface-soft)] text-[var(--text)] transition hover:bg-[var(--chip-active)] active:scale-[0.98] disabled:opacity-50"
            >
              Hủy bỏ
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Success Trial Modal -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isSuccessTrialModalOpen" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
        @click.self="closeSuccessTrialModal"
      >
        <div class="relative overflow-hidden w-full max-w-[500px] rounded-[2.2rem] border border-emerald-500/30 bg-[var(--surface)] p-6 md:p-8 shadow-[0_24px_80px_rgba(16,185,129,0.25)] text-center transition">
          <div class="pointer-events-none absolute -right-20 -top-20 h-44 w-44 rounded-full bg-emerald-500/15 blur-3xl"></div>
          <div class="pointer-events-none absolute -left-20 -bottom-20 h-44 w-44 rounded-full bg-teal-500/15 blur-3xl"></div>
          
          <!-- Success Animation Icon -->
          <div class="relative mx-auto h-20 w-20 flex items-center justify-center">
            <div class="absolute inset-0 rounded-full bg-emerald-500/10 border border-emerald-500/30 animate-pulse"></div>
            <span class="text-5xl animate-bounce">🎉</span>
          </div>

          <h4 class="mt-5 text-2xl font-black text-[var(--text)]">Kích Hoạt Thành Công!</h4>
          <p class="text-sm text-[var(--muted)] mt-1 font-semibold">Tài khoản của bạn đã được nâng cấp lên gói Plus</p>

          <!-- Success Details -->
          <div class="mt-6 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-left text-sm font-semibold grid gap-2.5">
            <div class="flex justify-between">
              <span class="text-[var(--muted)]">Gói dịch vụ:</span>
              <span class="font-black text-[var(--primary)]">Plus (Trải nghiệm 7 ngày)</span>
            </div>
            <div class="flex justify-between">
              <span class="text-[var(--muted)]">Lượt AI nhận thêm:</span>
              <span class="font-black text-emerald-400">+20 lượt dùng</span>
            </div>
            <div class="flex justify-between flex-wrap gap-2">
              <span class="text-[var(--muted)]">Thời hạn dùng thử đến:</span>
              <span class="font-black text-[var(--text)]">{{ formatTrialExpiry(currentUser?.vip_expires_at) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-6">
            <button 
              @click="closeSuccessTrialModal"
              class="h-12 w-full flex items-center justify-center font-black rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-[0_16px_36px_rgba(16,185,129,0.25)] transition hover:-translate-y-0.5 active:scale-[0.98]"
            >
              Bắt Đầu Khám Phá Ngay 🚀
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { currentUserStorage, paymentsApi, authApi } from '@/services/api'

const router = useRouter()
const route = useRoute()
const currentUser = ref(currentUserStorage.get())
const historyList = ref([])

const showPlusTrial = computed(() => {
  return !currentUser.value || (currentUser.value.role === 'free' && !currentUser.value.trial_used_at)
})

const isPaymentModalOpen = ref(false)
const selectedPlan = ref(null)
const isProcessing = ref(false)
const errorMessage = ref('')
const isActivatingTrial = ref(false)

const isConfirmTrialModalOpen = ref(false)
const isSuccessTrialModalOpen = ref(false)
const trialErrorMessage = ref('')

const isCurrentlyInTrial = computed(() => {
  if (!currentUser.value || !currentUser.value.vip_expires_at) return false
  return new Date(currentUser.value.vip_expires_at) > new Date()
})

const formatTrialExpiry = (expiryStr) => {
  if (!expiryStr) return ''
  return new Date(expiryStr).toLocaleString('vi-VN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatRemainingTime = (daysFloat) => {
  if (!daysFloat || daysFloat <= 0) return ''
  const totalHours = Math.round(daysFloat * 24)
  const days = Math.floor(totalHours / 24)
  const hours = totalHours % 24
  if (days > 0) {
    if (hours > 0) return `${days} ngày ${hours} giờ`
    return `${days} ngày`
  }
  return `${hours} giờ`
}

const activateFreeTrial = () => {
  if (!currentUser.value) {
    router.push({ path: '/register', query: { redirect: '/upgrade' } })
    return
  }
  isConfirmTrialModalOpen.value = true
  trialErrorMessage.value = ''
}

const closeConfirmTrialModal = () => {
  if (isActivatingTrial.value) return
  isConfirmTrialModalOpen.value = false
  trialErrorMessage.value = ''
}

const submitActivateTrial = async () => {
  isActivatingTrial.value = true
  trialErrorMessage.value = ''
  try {
    const res = await paymentsApi.activateTrial()
    if (res.success && res.user) {
      currentUserStorage.set(res.user)
      currentUser.value = res.user
      isConfirmTrialModalOpen.value = false
      isSuccessTrialModalOpen.value = true
      loadHistory()
    } else {
      trialErrorMessage.value = res.message || 'Không thể kích hoạt dùng thử.'
    }
  } catch (error) {
    trialErrorMessage.value = error.message || 'Không thể kích hoạt dùng thử.'
  } finally {
    isActivatingTrial.value = false
  }
}

const closeSuccessTrialModal = () => {
  isSuccessTrialModalOpen.value = false
}

const isUserCurrentPlan = (planId) => {
  if (!currentUser.value) return false
  const role = currentUser.value.role?.toLowerCase()
  if (role === 'admin') return true
  if (role === 'plus' && planId === 'plus_1m') return true
  if (role === 'pro' && planId === 'pro_1m') return true
  if (role === 'ultra' && planId === 'ultra_1m') return true
  return false
}

const plans = ref([
  {
    id: 'plus_1m',
    name: 'Gói Plus (Cơ Bản)',
    price: 50000,
    priceLabel: '50.000đ',
    period: 'Thời hạn 30 ngày',
    icon: '⚡',
    desc: 'Tăng cường hiệu năng học tập với các tính năng cơ bản.',
    quota: 100,
    btnText: 'Nâng cấp ngay',
    popular: false,
    upgradeInfo: null,
    features: [
      'Dùng AI sinh đề (+100 lượt)',
      'Scan tài liệu OCR (10 lượt/tháng)',
      'Mở khóa Quiz Riêng tư',
      'Tạo phòng Realtime nhóm (max 20 người)',
      'Tạo phòng bài tập (max 5 phòng)'
    ]
  },
  {
    id: 'pro_1m',
    name: 'Gói Pro (Chuyên Nghiệp)',
    price: 120000,
    priceLabel: '120.000đ',
    period: 'Thời hạn 30 ngày',
    icon: '🚀',
    desc: 'Lựa chọn tốt nhất cho giáo viên và người tạo nội dung.',
    quota: 350,
    btnText: 'Mua gói phổ biến',
    popular: true,
    upgradeInfo: null,
    features: [
      'Dùng AI sinh đề (+350 lượt)',
      'Scan tài liệu OCR (50 lượt/tháng)',
      'Mở khóa Quiz Riêng tư',
      'Tạo phòng Realtime lớn (max 100 người)',
      'Tạo phòng bài tập (max 20 phòng)',
      'Hỗ trợ xuất đề ra PDF/Excel',
      'Badge Pro nổi bật'
    ]
  },
  {
    id: 'ultra_1m',
    name: 'Gói Ultra (Tối Thượng)',
    price: 250000,
    priceLabel: '250.000đ',
    period: 'Thời hạn 30 ngày',
    icon: '👑',
    desc: 'Tối đa hóa sức mạnh với quyền lợi không giới hạn.',
    quota: 1500,
    btnText: 'Sở hữu gói tối thượng',
    popular: false,
    upgradeInfo: null,
    features: [
      'Dùng AI sinh đề (+1500 lượt)',
      'Scan tài liệu OCR không giới hạn',
      'Mở khóa Quiz Riêng tư',
      'Tạo phòng Realtime cực đại (max 500 người)',
      'Tạo phòng bài tập không giới hạn',
      'Hỗ trợ xuất đề PDF/Excel',
      'Badge Ultra lấp lánh cạnh tên'
    ]
  }
])

const upgradeCostsLoaded = ref(false)

const fetchUpgradeCosts = async () => {
  if (!currentUser.value) return
  try {
    const res = await paymentsApi.getUpgradeCosts()
    if (res.success && res.plans) {
      plans.value = plans.value.map(plan => {
        const backendPlan = res.plans[plan.id]
        if (backendPlan && backendPlan.upgrade_info) {
          return {
            ...plan,
            upgradeInfo: backendPlan.upgrade_info
          }
        }
        return plan
      })
    }
  } catch (error) {
    console.error('Failed to fetch upgrade costs:', error)
  } finally {
    upgradeCostsLoaded.value = true
  }
}

onMounted(() => {
  if (currentUser.value) {
    loadHistory()
    fetchUpgradeCosts()
    
    authApi.me().then(latestUser => {
      currentUserStorage.set(latestUser)
      currentUser.value = latestUser
    }).catch(error => {
      console.error('Failed to sync user state on mount:', error)
    })

    // Tự động mở modal thanh toán nếu có plan truyền qua URL query
    if (route.query.plan) {
      const matchedPlan = plans.value.find(p => p.id === route.query.plan)
      if (matchedPlan) {
        openCheckout(matchedPlan)
      }
    }
  }
})

const loadHistory = async () => {
  try {
    const res = await paymentsApi.history()
    historyList.value = Array.isArray(res?.data) ? res.data : []
  } catch (error) {
    console.error('Failed to load transaction history', error)
  }
}

const openCheckout = (plan) => {
  if (!currentUser.value) {
    // Nếu chưa đăng nhập, chuyển hướng sang trang đăng ký kèm giữ ý định chọn gói
    router.push({ path: '/register', query: { plan: plan.id } })
    return
  }
  selectedPlan.value = plan
  isPaymentModalOpen.value = true
  errorMessage.value = ''
}

const closeCheckout = () => {
  if (isProcessing.value) return
  isPaymentModalOpen.value = false
  selectedPlan.value = null
}

const handlePayment = async (provider) => {
  if (!selectedPlan.value) return
  isProcessing.value = true
  errorMessage.value = ''

  try {
    const res = await paymentsApi.create({
      plan_id: selectedPlan.value.id,
      provider: provider
    })

    if (res.success && res.payUrl) {
      // Điều hướng trực tiếp tab hiện tại đến cổng thanh toán (MoMo hoặc PayOS)
      window.location.href = res.payUrl
    } else {
      throw new Error(res.message || 'Không khởi tạo được URL thanh toán.')
    }
  } catch (error) {
    errorMessage.value = error.message
    isProcessing.value = false
  }
}

// Helpers
const getPlanNameByAmount = (amount) => {
  const parsed = Number(amount)
  if (parsed === 0) return 'Dùng thử Plus (7 ngày)'
  if (parsed === 50000) return 'Gói Plus'
  if (parsed === 120000) return 'Gói Pro'
  if (parsed === 250000) return 'Gói Ultra'
  return 'Gói nâng cấp'
}

const formatPrice = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusText = (status) => {
  const mapping = {
    pending: 'Đang xử lý',
    success: 'Thành công',
    failed: 'Thất bại',
    refunded: 'Đã hoàn tiền'
  }
  return mapping[status] || status
}

const getStatusBadgeClass = (status) => {
  const mapping = {
    pending: 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
    success: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
    failed: 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
    refunded: 'bg-gray-500/10 text-gray-400 border border-gray-500/20'
  }
  return mapping[status] || 'bg-gray-500/10 text-gray-400'
}
</script>

<style scoped>
/* Thêm một số hiệu ứng glowing và neon */
article:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(124, 58, 237, 0.08);
}
</style>
