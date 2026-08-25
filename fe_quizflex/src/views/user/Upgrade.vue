<template>
  <section class="max-w-[1200px] mx-auto py-6 space-y-8">
    <!-- Hero Header -->
    <div class="card p-8 sm:p-12 text-center space-y-3">
      <span
        class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 border border-purple-200 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-[#7C3AED]"
      >
        <Sparkles class="h-3.5 w-3.5" />
        Gói dịch vụ cao cấp
      </span>

      <h1
        class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tight"
      >
        Mở khóa toàn bộ
        <span class="text-[#7C3AED]">đặc quyền học tập</span>
      </h1>

      <p
        class="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed"
      >
        Nâng cấp để sử dụng AI sinh câu hỏi, quét tài liệu OCR không giới hạn
        và tạo phòng thi đấu thời gian thực cùng bạn bè.
      </p>
    </div>

    <!-- Trial Promo Banner / Active Status Banner -->
    <div v-if="currentUser" class="space-y-4">
      <!-- Trial Promo Banner -->
      <div
        v-if="currentUser.role === 'free' && !currentUser.trial_used_at"
        class="card p-6 bg-emerald-50/40 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
      >
        <div class="space-y-1">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-800"
          >
            <Gift class="h-3 w-3" />
            Trải nghiệm miễn phí
          </span>

          <h3 class="text-lg font-bold text-slate-900">
            Dùng thử Gói Plus Miễn Phí 7 Ngày
          </h3>

          <p
            class="text-xs text-slate-600 leading-relaxed max-w-2xl"
          >
            Trải nghiệm đầy đủ tính năng tạo phòng thi đấu, phòng bài tập và
            nhận ngay <b>+20 lượt AI sinh đề</b>. Kích hoạt một lần duy nhất
            cho mỗi tài khoản.
          </p>
        </div>

        <button
          @click="activateFreeTrial"
          :disabled="isActivatingTrial"
          class="btn-success text-xs shrink-0 px-5 py-2.5 inline-flex items-center justify-center gap-2"
        >
          <Loader2
            v-if="isActivatingTrial"
            class="h-3.5 w-3.5 animate-spin"
          />
          <Rocket v-else class="h-3.5 w-3.5" />

          {{ isActivatingTrial ? 'Đang kích hoạt...' : 'Kích hoạt dùng thử ngay' }}
        </button>
      </div>

      <!-- Active Trial Status Banner -->
      <div
        v-else-if="
          currentUser.role === 'plus' &&
          currentUser.trial_used_at &&
          isCurrentlyInTrial
        "
        class="card p-6 border-l-4 border-l-[#7C3AED] bg-purple-50/40 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
      >
        <div class="space-y-1">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-[#7C3AED]"
          >
            <Clock class="h-3 w-3" />
            Trạng thái dùng thử
          </span>

          <h3
            class="text-lg font-bold text-slate-900 flex items-center gap-2"
          >
            <Sparkles class="h-5 w-5 text-[#7C3AED]" />
            Bạn đang trong thời gian dùng thử Plus
          </h3>

          <p class="text-xs text-slate-600">
            Đặc quyền dùng thử của bạn kết thúc vào:
            <b class="text-[#7C3AED]">
              {{ formatTrialExpiry(currentUser.vip_expires_at) }}
            </b>.
            Hết hạn sẽ tự động về gói Free.
          </p>
        </div>

        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 bg-white px-3 py-1.5 rounded-lg border border-slate-200"
        >
          <ShieldCheck class="h-3.5 w-3.5 text-emerald-600" />
          Không tự động trừ tiền
        </span>
      </div>
    </div>

    <!-- Pricing Cards Grid -->
    <div class="grid gap-6 md:grid-cols-3 items-stretch">
      <article
        v-for="plan in plans"
        :key="plan.id"
        class="card p-6 sm:p-8 flex flex-col justify-between transition hover:-translate-y-1"
        :class="
          plan.popular
            ? 'border-2 border-[#7C3AED] shadow-md relative'
            : 'border border-slate-200'
        "
      >
        <!-- Popular Badge -->
        <span
          v-if="plan.popular"
          class="absolute top-4 right-4 inline-flex items-center gap-1.5 bg-[#7C3AED] text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full"
        >
          <Star class="h-3 w-3 fill-current" />
          Phổ biến nhất
        </span>

        <div class="space-y-4">
          <!-- Header -->
          <div class="flex items-center gap-3">
            <div
              class="h-11 w-11 shrink-0 rounded-xl bg-purple-50 text-[#7C3AED] flex items-center justify-center"
            >
              <component
                :is="plan.icon"
                class="h-5 w-5"
                :stroke-width="2.2"
              />
            </div>

            <div>
              <h3 class="font-bold text-lg text-slate-900">
                {{ plan.name }}
              </h3>

              <p class="text-xs text-slate-500 font-medium">
                {{
                  plan.id === 'plus_1m' && showPlusTrial
                    ? 'Thời hạn 7 ngày'
                    : plan.period
                }}
              </p>
            </div>
          </div>

          <!-- Pricing -->
          <div class="pt-2">
            <template v-if="plan.id === 'plus_1m' && showPlusTrial">
              <span
                class="text-sm line-through text-slate-400 font-semibold mr-1.5"
              >
                50.000đ
              </span>

              <span class="text-3xl font-black text-slate-900">
                0đ
              </span>

              <span class="text-xs text-slate-500 font-medium">
                / 7 ngày
              </span>
            </template>

            <template v-else>
              <span class="text-3xl font-black text-slate-900">
                {{ plan.priceLabel }}
              </span>

              <span
                v-if="plan.price > 0"
                class="text-xs text-slate-500 font-medium"
              >
                / gói
              </span>
            </template>
          </div>

          <p
            class="text-xs text-slate-600 leading-relaxed border-b border-slate-100 pb-4"
          >
            {{ plan.desc }}
          </p>

          <!-- Feature List -->
          <div class="space-y-2.5 pt-1">
            <div
              v-for="feature in plan.features"
              :key="feature"
              class="flex items-start gap-2 text-xs font-semibold text-slate-700"
            >
              <span
                class="mt-0.5 h-4 w-4 shrink-0 rounded-full bg-purple-50 text-[#7C3AED] flex items-center justify-center"
              >
                <Check class="h-2.5 w-2.5" :stroke-width="3" />
              </span>

              <span class="leading-relaxed">
                {{ feature }}
              </span>
            </div>
          </div>
        </div>

        <!-- Action Button -->
        <div class="pt-6 mt-4 border-t border-slate-100">
          <button
            v-if="plan.id === 'plus_1m' && showPlusTrial"
            @click="activateFreeTrial"
            class="btn-success text-xs w-full py-2.5 inline-flex items-center justify-center gap-2"
          >
            <Gift class="h-3.5 w-3.5" />
            Dùng thử Plus miễn phí 7 ngày
          </button>

          <template v-else>
            <!-- Current Plan -->
            <button
              v-if="currentUser && isUserCurrentPlan(plan.id)"
              disabled
              class="btn-secondary text-xs w-full py-2.5 cursor-not-allowed opacity-75 inline-flex items-center justify-center gap-2"
            >
              <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
              Đang sử dụng
            </button>

            <!-- Cannot downgrade -->
            <button
              v-else-if="
                currentUser &&
                plan.upgradeInfo &&
                !plan.upgradeInfo.allowed
              "
              disabled
              class="btn-secondary text-xs w-full py-2.5 cursor-not-allowed opacity-50 inline-flex items-center justify-center gap-2"
            >
              <LockKeyhole class="h-3.5 w-3.5" />
              Không thể hạ cấp
            </button>

            <!-- Upgrade -->
            <button
              v-else
              @click="openCheckout(plan)"
              :class="plan.popular ? 'btn-primary' : 'btn-secondary'"
              class="text-xs w-full py-2.5 inline-flex items-center justify-center gap-2"
            >
              <ArrowUpCircle class="h-3.5 w-3.5" />

              {{
                plan.upgradeInfo &&
                plan.upgradeInfo.unused_value > 0
                  ? 'Nâng cấp ngay'
                  : plan.btnText
              }}
            </button>
          </template>
        </div>
      </article>
    </div>

    <!-- Transaction History -->
    <div
      v-if="currentUser"
      class="card p-6 sm:p-8 space-y-4"
    >
      <div class="border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2">
          <ReceiptText class="h-4 w-4 text-[#7C3AED]" />

          <h3 class="text-base font-bold text-slate-900">
            Lịch sử giao dịch
          </h3>
        </div>

        <p class="text-xs text-slate-500 mt-1">
          Xem lại các hóa đơn và gói dịch vụ đã mua.
        </p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr
              class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[10px]"
            >
              <th class="pb-3 pr-4">Mã giao dịch</th>
              <th class="pb-3 px-4">Gói cước</th>
              <th class="pb-3 px-4">Cổng</th>
              <th class="pb-3 px-4">Số tiền</th>
              <th class="pb-3 px-4">Trạng thái</th>
              <th class="pb-3 pl-4">Ngày tạo</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="item in historyList"
              :key="item.id"
              class="hover:bg-slate-50"
            >
              <td
                class="py-3 pr-4 font-mono font-bold text-slate-800"
              >
                {{ item.order_code }}
              </td>

              <td
                class="py-3 px-4 font-bold text-slate-900"
              >
                {{
                  item.plan_name ||
                  getPlanNameByAmount(item.amount)
                }}
              </td>

              <td class="py-3 px-4">
                <span
                  class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 font-bold uppercase text-[10px] text-slate-600"
                >
                  {{ item.provider || 'Nội bộ' }}
                </span>
              </td>

              <td class="py-3 px-4 font-bold text-slate-900">
                {{ formatPrice(item.amount) }}
              </td>

              <td class="py-3 px-4">
                <span
                  class="rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                  :class="getStatusBadgeClass(item.status)"
                >
                  {{ getStatusText(item.status) }}
                </span>
              </td>

              <td class="py-3 pl-4 text-slate-500">
                {{ formatDate(item.created_at) }}
              </td>
            </tr>

            <tr v-if="historyList.length === 0">
              <td
                colspan="6"
                class="py-8 text-center text-slate-400 font-semibold"
              >
                Bạn chưa có giao dịch nào trên hệ thống.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Checkout Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="isPaymentModalOpen"
          class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
          @click.self="closeCheckout"
        >
        <div
          class="relative w-full max-w-[480px] rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4"
        >
          <!-- Close -->
          <button
            @click="closeCheckout"
            class="absolute top-4 right-4 h-8 w-8 flex items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100 transition"
          >
            <X class="h-4 w-4" />
          </button>

          <!-- Header -->
          <div class="text-center space-y-1">
            <div
              class="mx-auto h-12 w-12 rounded-xl bg-purple-50 text-[#7C3AED] flex items-center justify-center mb-2"
            >
              <component
                :is="selectedPlan?.icon"
                class="h-6 w-6"
              />
            </div>

            <h4 class="text-xl font-bold text-slate-900">
              Xác nhận nâng cấp
            </h4>

            <p class="text-xs text-slate-500">
              Vui lòng chọn phương thức thanh toán
            </p>
          </div>

          <!-- Order Summary -->
          <div
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-2 text-xs"
          >
            <div class="flex items-center justify-between font-semibold">
              <span class="text-slate-500">Gói cước:</span>

              <span class="text-slate-900 font-bold">
                {{ selectedPlan?.name }}
              </span>
            </div>

            <div class="flex items-center justify-between font-semibold">
              <span class="text-slate-500">Thời hạn:</span>

              <span class="text-slate-900 font-bold">
                {{ selectedPlan?.period }}
              </span>
            </div>

            <div class="flex items-center justify-between font-semibold">
              <span class="text-slate-500">
                AI Quota cộng thêm:
              </span>

              <span class="text-[#7C3AED] font-bold">
                +{{ selectedPlan?.quota }} lượt
              </span>
            </div>

            <div
              v-if="
                selectedPlan?.upgradeInfo &&
                selectedPlan.upgradeInfo.unused_value > 0
              "
              class="pt-2 border-t border-slate-200 space-y-1"
            >
              <div
                class="flex items-center justify-between text-slate-500"
              >
                <span>Giá gốc gói mới:</span>

                <span class="line-through">
                  {{ selectedPlan.priceLabel }}
                </span>
              </div>

              <div
                class="flex items-center justify-between text-emerald-700 font-bold"
              >
                <span>Khấu trừ gói cũ:</span>

                <span>
                  -{{ formatPrice(selectedPlan.upgradeInfo.unused_value) }}
                </span>
              </div>
            </div>

            <div
              class="pt-2 border-t border-slate-200 flex items-center justify-between font-bold text-sm"
            >
              <span class="text-slate-900">
                Tổng thanh toán:
              </span>

              <span class="text-[#7C3AED] text-base">
                {{
                  selectedPlan?.upgradeInfo
                    ? formatPrice(selectedPlan.upgradeInfo.amount)
                    : selectedPlan?.priceLabel
                }}
              </span>
            </div>
          </div>

          <!-- Error -->
          <div
            v-if="errorMessage"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700 flex items-start gap-2"
          >
            <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
            <span>{{ errorMessage }}</span>
          </div>

          <!-- Payment Options -->
          <div class="space-y-2.5 pt-1">
            <!-- PayOS -->
            <button
              @click="handlePayment('payos')"
              :disabled="isProcessing"
              class="w-full flex items-center justify-between rounded-xl border border-emerald-300 bg-emerald-50/50 p-3.5 hover:bg-emerald-50 transition active:scale-[0.99] text-left"
            >
              <div class="flex items-center gap-3">
                <div
                  class="h-9 w-9 shrink-0 rounded-lg bg-emerald-600 flex items-center justify-center text-white"
                >
                  <QrCode class="h-5 w-5" />
                </div>

                <div>
                  <b class="block text-xs font-bold text-slate-900">
                    Thanh toán VietQR (PayOS)
                  </b>

                  <span class="text-[11px] text-slate-500">
                    Quét mã chuyển khoản từ mọi App Ngân hàng
                  </span>
                </div>
              </div>

              <span
                class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700"
              >
                Chọn
                <ArrowRight class="h-3.5 w-3.5" />
              </span>
            </button>

            <!-- MoMo -->
            <button
              @click="handlePayment('momo')"
              :disabled="isProcessing"
              class="w-full flex items-center justify-between rounded-xl border border-pink-200 bg-pink-50/30 p-3.5 hover:bg-pink-50 transition active:scale-[0.99] text-left"
            >
              <div class="flex items-center gap-3">
                <div
                  class="h-9 w-9 shrink-0 rounded-lg bg-[#a21960] flex items-center justify-center text-white"
                >
                  <WalletCards class="h-5 w-5" />
                </div>

                <div>
                  <b class="block text-xs font-bold text-slate-900">
                    Ví Điện Tử MoMo
                  </b>

                  <span class="text-[11px] text-slate-500">
                    Thanh toán tức thì qua ứng dụng MoMo
                  </span>
                </div>
              </div>

              <span
                class="inline-flex items-center gap-1 text-xs font-bold text-pink-700"
              >
                Chọn
                <ArrowRight class="h-3.5 w-3.5" />
              </span>
            </button>
          </div>

          <!-- Loading -->
          <div
            v-if="isProcessing"
            class="absolute inset-0 bg-white/90 backdrop-blur-sm z-30 flex flex-col items-center justify-center gap-2 rounded-2xl"
          >
            <Loader2
              class="h-8 w-8 animate-spin text-[#7C3AED]"
            />

            <p class="text-xs font-bold text-slate-800">
              Đang kết nối cổng thanh toán...
            </p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

    <!-- Confirm Trial Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="isConfirmTrialModalOpen"
          class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
          @click.self="closeConfirmTrialModal"
        >
        <div
          class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4"
        >
          <div class="text-center space-y-1">
            <div
              class="mx-auto h-14 w-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2"
            >
              <Gift class="h-7 w-7" />
            </div>

            <h4 class="text-lg font-bold text-slate-900">
              Dùng Thử Plus 7 Ngày
            </h4>

            <p class="text-xs text-slate-500">
              Mở khóa đặc quyền gói Plus hoàn toàn miễn phí
            </p>
          </div>

          <div
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-2 text-xs text-slate-700"
          >
            <div class="flex items-center gap-2 font-medium">
              <CheckCircle2
                class="h-4 w-4 text-emerald-600 shrink-0"
              />

              <span>Nhận ngay +20 lượt AI tạo đề</span>
            </div>

            <div class="flex items-center gap-2 font-medium">
              <CheckCircle2
                class="h-4 w-4 text-emerald-600 shrink-0"
              />

              <span>Mở khóa quét tài liệu bằng AI OCR</span>
            </div>

            <div class="flex items-center gap-2 font-medium">
              <CheckCircle2
                class="h-4 w-4 text-emerald-600 shrink-0"
              />

              <span>Tạo phòng thi đấu nhóm & phòng bài tập</span>
            </div>

            <p
              class="pt-2 text-[11px] text-amber-700 font-semibold border-t border-slate-200 flex items-start gap-1.5"
            >
              <AlertTriangle
                class="h-3.5 w-3.5 shrink-0 mt-0.5"
              />

              <span>
                Mỗi tài khoản chỉ dùng thử 1 lần. Hết 7 ngày sẽ tự về
                gói Free mà không trừ tiền.
              </span>
            </p>
          </div>

          <!-- Trial Error -->
          <div
            v-if="trialErrorMessage"
            class="rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs font-bold text-red-700 flex items-start gap-2"
          >
            <AlertCircle class="h-4 w-4 shrink-0" />

            <span>{{ trialErrorMessage }}</span>
          </div>

          <div class="flex justify-end gap-2.5 pt-2">
            <button
              @click="closeConfirmTrialModal"
              :disabled="isActivatingTrial"
              class="btn-secondary text-xs"
            >
              Hủy
            </button>

            <button
              @click="submitActivateTrial"
              :disabled="isActivatingTrial"
              class="btn-success text-xs inline-flex items-center gap-2"
            >
              <Loader2
                v-if="isActivatingTrial"
                class="h-3.5 w-3.5 animate-spin"
              />

              <Rocket
                v-else
                class="h-3.5 w-3.5"
              />

              {{
                isActivatingTrial
                  ? 'Đang kích hoạt...'
                  : 'Kích hoạt ngay'
              }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

    <!-- Success Trial Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="isSuccessTrialModalOpen"
          class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
          @click.self="closeSuccessTrialModal"
        >
        <div
          class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl text-center space-y-4"
        >
          <div
            class="mx-auto h-14 w-14 flex items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
          >
            <PartyPopper class="h-7 w-7" />
          </div>

          <div>
            <h4 class="text-lg font-bold text-slate-900">
              Kích hoạt thành công!
            </h4>

            <p class="text-xs text-slate-500">
              Tài khoản của bạn đã được nâng cấp lên gói Plus
            </p>
          </div>

          <div
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-left text-xs space-y-2"
          >
            <div class="flex justify-between">
              <span class="text-slate-500">
                Gói cước:
              </span>

              <span class="font-bold text-[#7C3AED]">
                Plus (7 ngày dùng thử)
              </span>
            </div>

            <div class="flex justify-between">
              <span class="text-slate-500">
                Lượt AI nhận thêm:
              </span>

              <span class="font-bold text-emerald-700">
                +20 lượt
              </span>
            </div>

            <div class="flex justify-between">
              <span class="text-slate-500">
                Thời hạn đến:
              </span>

              <span class="font-bold text-slate-900">
                {{ formatTrialExpiry(currentUser?.vip_expires_at) }}
              </span>
            </div>
          </div>

          <button
            @click="closeSuccessTrialModal"
            class="btn-primary text-xs w-full py-2.5 inline-flex items-center justify-center gap-2"
          >
            <Rocket class="h-3.5 w-3.5" />
            Bắt đầu khám phá ngay
          </button>
        </div>
      </div>
    </div>
  </Transition>
</Teleport>
  </section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

import {
  AlertCircle,
  AlertTriangle,
  ArrowRight,
  ArrowUpCircle,
  Check,
  CheckCircle2,
  Clock,
  Crown,
  Gift,
  KeyRound,
  Loader2,
  PartyPopper,
  QrCode,
  ReceiptText,
  Rocket,
  ShieldCheck,
  Sparkles,
  Star,
  WalletCards,
  Zap,
  X
} from 'lucide-vue-next'

import {
  currentUserStorage,
  paymentsApi,
  authApi
} from '@/services/api'

const router = useRouter()
const route = useRoute()

const currentUser = ref(currentUserStorage.get())
const historyList = ref([])

const showPlusTrial = computed(() => {
  return (
    !currentUser.value ||
    (
      currentUser.value.role === 'free' &&
      !currentUser.value.trial_used_at
    )
  )
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
  if (
    !currentUser.value ||
    !currentUser.value.vip_expires_at
  ) {
    return false
  }

  return (
    new Date(currentUser.value.vip_expires_at) >
    new Date()
  )
})

const formatTrialExpiry = (expiryStr) => {
  if (!expiryStr) return ''

  return new Date(expiryStr).toLocaleString('vi-VN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const activateFreeTrial = () => {
  if (!currentUser.value) {
    router.push({
      path: '/register',
      query: {
        redirect: '/upgrade'
      }
    })

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
      trialErrorMessage.value =
        res.message ||
        'Không thể kích hoạt dùng thử.'
    }
  } catch (error) {
    trialErrorMessage.value =
      error.message ||
      'Không thể kích hoạt dùng thử.'
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

  if (
    role === 'plus' &&
    planId === 'plus_1m'
  ) {
    return true
  }

  if (
    role === 'pro' &&
    planId === 'pro_1m'
  ) {
    return true
  }

  if (
    role === 'ultra' &&
    planId === 'ultra_1m'
  ) {
    return true
  }

  return false
}

const plans = ref([
  {
    id: 'plus_1m',
    name: 'Gói Plus',
    price: 50000,
    priceLabel: '50.000đ',
    period: 'Thời hạn 30 ngày',
    icon: Zap,
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
    name: 'Gói Pro',
    price: 120000,
    priceLabel: '120.000đ',
    period: 'Thời hạn 30 ngày',
    icon: Rocket,
    desc: 'Lựa chọn tốt nhất cho học sinh và giáo viên tạo nội dung.',
    quota: 350,
    btnText: 'Mua gói Pro',
    popular: true,
    upgradeInfo: null,
    features: [
      'Dùng AI sinh đề (+350 lượt)',
      'Scan tài liệu OCR (50 lượt/tháng)',
      'Mở khóa Quiz Riêng tư',
      'Tạo phòng Realtime lớn (max 100 người)',
      'Tạo phòng bài tập (max 20 phòng)',
      'Hỗ trợ xuất đề ra PDF/Excel'
    ]
  },

  {
    id: 'ultra_1m',
    name: 'Gói Ultra',
    price: 250000,
    priceLabel: '250.000đ',
    period: 'Thời hạn 30 ngày',
    icon: Crown,
    desc: 'Tối đa hóa sức mạnh với quyền lợi không giới hạn.',
    quota: 1500,
    btnText: 'Sở hữu gói Ultra',
    popular: false,
    upgradeInfo: null,
    features: [
      'Dùng AI sinh đề (+1500 lượt)',
      'Scan tài liệu OCR không giới hạn',
      'Mở khóa Quiz Riêng tư',
      'Tạo phòng Realtime cực đại (max 500 người)',
      'Tạo phòng bài tập không giới hạn',
      'Hỗ trợ xuất đề PDF/Excel'
    ]
  }
])

const fetchUpgradeCosts = async () => {
  if (!currentUser.value) return

  try {
    const res = await paymentsApi.getUpgradeCosts()

    if (res.success && res.plans) {
      plans.value = plans.value.map(plan => {
        const backendPlan = res.plans[plan.id]

        if (
          backendPlan &&
          backendPlan.upgrade_info
        ) {
          return {
            ...plan,
            upgradeInfo:
              backendPlan.upgrade_info
          }
        }

        return plan
      })
    }
  } catch (error) {
    console.error(
      'Failed to fetch upgrade costs:',
      error
    )
  }
}

onMounted(() => {
  if (currentUser.value) {
    loadHistory()
    fetchUpgradeCosts()

    authApi.me()
      .then(latestUser => {
        currentUserStorage.set(latestUser)
        currentUser.value = latestUser
      })
      .catch(error => {
        console.error(
          'Failed to sync user state on mount:',
          error
        )
      })

    if (route.query.plan) {
      const matchedPlan = plans.value.find(
        p => p.id === route.query.plan
      )

      if (matchedPlan) {
        openCheckout(matchedPlan)
      }
    }
  }
})

const loadHistory = async () => {
  try {
    const res = await paymentsApi.history()

    historyList.value =
      Array.isArray(res?.data)
        ? res.data
        : []
  } catch (error) {
    console.error(
      'Failed to load transaction history',
      error
    )
  }
}

const openCheckout = (plan) => {
  if (!currentUser.value) {
    router.push({
      path: '/register',
      query: {
        plan: plan.id
      }
    })

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
      window.location.href = res.payUrl
    } else {
      throw new Error(
        res.message ||
        'Không khởi tạo được URL thanh toán.'
      )
    }
  } catch (error) {
    errorMessage.value = error.message
    isProcessing.value = false
  }
}

const getPlanNameByAmount = (amount) => {
  const parsed = Number(amount)

  if (parsed === 0) {
    return 'Dùng thử Plus (7 ngày)'
  }

  if (parsed === 50000) {
    return 'Gói Plus'
  }

  if (parsed === 120000) {
    return 'Gói Pro'
  }

  if (parsed === 250000) {
    return 'Gói Ultra'
  }

  return 'Gói nâng cấp'
}

const formatPrice = (value) => {
  return new Intl.NumberFormat(
    'vi-VN',
    {
      style: 'currency',
      currency: 'VND'
    }
  ).format(value)
}

const formatDate = (dateString) => {
  if (!dateString) return ''

  return new Date(dateString).toLocaleDateString(
    'vi-VN',
    {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
  )
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
    pending: 'bg-amber-50 text-amber-700',
    success: 'bg-emerald-50 text-emerald-700',
    failed: 'bg-red-50 text-red-700',
    refunded: 'bg-slate-100 text-slate-600'
  }

  return (
    mapping[status] ||
    'bg-slate-100 text-slate-600'
  )
}
</script>
