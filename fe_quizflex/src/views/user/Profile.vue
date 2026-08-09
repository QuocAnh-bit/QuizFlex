<template>
  <section class="max-w-[1280px] mx-auto py-8 px-4 grid gap-8">
    
    <!-- Top Header & Tab Controls -->
    <div class="relative pb-2">
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
          <span class="inline-flex items-center gap-2 rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-3.5 py-1 text-[11px] font-black uppercase tracking-widest text-[var(--primary)] shadow-[0_2px_12px_rgba(155,44,255,0.15)]">
            <span class="h-1.5 w-1.5 rounded-full bg-[var(--primary)] animate-pulse"></span>
            Account & Subscription Dashboard
          </span>
          <h1 class="text-3xl md:text-4xl font-black tracking-tight text-[var(--text)] mt-2.5">
            Cài Đặt Tài Khoản & <span class="bg-gradient-to-r from-[var(--primary)] via-fuchsia-400 to-[var(--accent)] bg-clip-text text-transparent">Gói Dịch Vụ</span>
          </h1>
        </div>

        <!-- Sleek Luxury Segmented Tab Switcher -->
        <div class="flex items-center gap-1.5 rounded-full border border-[var(--border)] bg-[var(--surface-soft)]/90 p-1.5 shadow-[inset_0_2px_8px_rgba(0,0,0,0.15)] backdrop-blur-xl">
          <button 
            @click="switchTab('profile')" 
            type="button"
            class="flex items-center gap-2.5 px-5 py-2.5 rounded-full text-xs md:text-sm font-black transition duration-300"
            :class="activeTab === 'profile' 
              ? 'bg-gradient-to-r from-[var(--primary)] to-[var(--primary-2)] text-white shadow-[0_4px_20px_rgba(155,44,255,0.4)]' 
              : 'text-[var(--muted)] hover:text-[var(--text)] hover:bg-[var(--surface)]/50'"
          >
            <svg class="w-4 h-4 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Hồ Sơ Cá Nhân</span>
          </button>
          <button 
            @click="switchTab('subscription')" 
            type="button"
            class="flex items-center gap-2.5 px-5 py-2.5 rounded-full text-xs md:text-sm font-black transition duration-300 relative"
            :class="activeTab === 'subscription' 
              ? 'bg-gradient-to-r from-[var(--primary)] via-fuchsia-500 to-[var(--accent)] text-white shadow-[0_4px_20px_rgba(155,44,255,0.4)]' 
              : 'text-[var(--muted)] hover:text-[var(--text)] hover:bg-[var(--surface)]/50'"
          >
            <svg class="w-4 h-4 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Gói Dịch Vụ & Hạn Mức</span>
            <span v-if="profile.ai_quota_remaining < 20" class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></span>
          </button>
        </div>
      </div>

      <!-- Luxury Multi-Layered Glowing Line Divider -->
      <div class="relative mt-6 h-[1px] w-full bg-gradient-to-r from-[var(--border)] via-[var(--border-strong)] to-[var(--border)]">
        <div class="absolute left-0 top-0 h-[2px] w-48 bg-gradient-to-r from-[var(--primary)] via-fuchsia-400 to-transparent rounded-full shadow-[0_0_14px_rgba(155,44,255,0.7)]"></div>
      </div>
    </div>

    <!-- TAB 1: HỒ SƠ CÁ NHÂN -->
    <div v-if="activeTab === 'profile'" class="grid gap-6 lg:grid-cols-[380px_1fr]">
      <article class="rounded-[2.2rem] border border-[var(--border)] bg-[var(--surface)]/80 p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[var(--primary)]">Profile Overview</p>
        <h2 class="mt-2 text-2xl md:text-3xl font-black text-[var(--text)] tracking-tight">Hồ sơ cá nhân</h2>
        <p class="mt-2 text-xs font-semibold leading-relaxed text-[var(--muted)]">Avatar và thông tin tài khoản được đồng bộ trực tiếp trên toàn hệ thống.</p>

        <div class="mt-6 flex flex-col items-center rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface-soft)]/60 p-6 text-center backdrop-blur-md">
          <UserAvatar :user="avatarUser" size-class="h-28 w-28" text-class="text-2xl font-black" ring-class="ring-4 ring-white/10" shadow-class="shadow-[0_20px_40px_rgba(0,0,0,0.25)]" />
          <b class="mt-4 text-xl font-black text-[var(--text)] tracking-tight">{{ profile.name || 'Người dùng QuizFlex' }}</b>
          <span class="mt-1 text-xs font-bold text-[var(--muted)]">{{ profile.email || 'Chưa đăng nhập' }}</span>
          <span class="mt-3 inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-wider bg-[var(--primary)]/10 text-[var(--primary)] border border-[var(--primary)]/20 shadow-sm">
            Gói: {{ currentPlanLabel }}
          </span>
        </div>

        <div class="mt-6 grid gap-3 text-xs font-bold text-[var(--muted)]">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)]/50 p-4">
            <span class="text-[10px] font-black uppercase tracking-wider text-[var(--muted)]">Địa chỉ Email</span>
            <b class="mt-1 block text-sm text-[var(--text)] font-black truncate">{{ profile.email || 'Chưa đăng nhập' }}</b>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)]/50 p-4">
            <span class="text-[10px] font-black uppercase tracking-wider text-[var(--muted)]">Vai trò hệ thống</span>
            <b class="mt-1 block text-sm text-[var(--text)] font-black uppercase">{{ profile.role_label || profile.role || 'Guest' }}</b>
          </div>
        </div>
      </article>

      <article class="rounded-[2.2rem] border border-[var(--border)] bg-[var(--surface)]/80 p-6 md:p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <h2 class="text-2xl md:text-3xl font-black tracking-tight text-[var(--text)]">Thông tin hiển thị</h2>
        <div class="mt-6 grid gap-6">
          <div class="grid gap-4 xl:grid-cols-[260px_minmax(0,1fr)]">
            <div class="rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface-soft)]/60 p-5">
              <p class="text-xs font-black uppercase tracking-wider text-[var(--text)]">Avatar người dùng</p>
              <p class="mt-1 text-xs font-semibold leading-relaxed text-[var(--muted)]">Hỗ trợ định dạng PNG, JPG, WEBP. Dung lượng tối đa 2MB.</p>
              <div class="mt-4 flex flex-wrap items-center gap-3">
                <button class="btn-ghost !px-4 !py-2 text-xs font-black" type="button" @click="openAvatarPicker">Upload ảnh</button>
                <button v-if="avatarUser.avatar" class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-300 hover:bg-rose-500/20 transition" type="button" @click="removeAvatar">Xóa ảnh</button>
              </div>
              <p v-if="avatarFile" class="mt-3 text-xs font-bold text-[var(--primary)] truncate">Đã chọn: {{ avatarFile.name }}</p>
              <input ref="avatarInput" class="hidden" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" @change="handleAvatarFileChange" />
            </div>

            <div class="rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface-soft)]/40 p-5 text-xs font-semibold leading-relaxed text-[var(--muted)] flex items-center">
              Lưu avatar trên hệ thống giúp bạn giữ nguyên giao diện hiển thị nhận diện cá nhân khi tham gia các phòng thi trực tiếp và bài tập lớp học.
            </div>
          </div>

          <label class="grid gap-2 text-xs font-black uppercase tracking-wider text-[var(--text)]">
            Họ và tên
            <input v-model="profile.name" class="field text-sm font-bold" maxlength="100" />
            <span v-if="nameError" class="text-xs font-bold text-rose-400 normal-case tracking-normal">{{ nameError }}</span>
          </label>

          <label class="grid gap-2 text-xs font-black uppercase tracking-wider text-[var(--text)]">
            Email tài khoản
            <input v-model="profile.email" class="field text-sm font-bold" disabled />
          </label>

          <div v-if="message" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-300 shadow-sm">{{ message }}</div>
          <div v-if="errorMessage" class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-bold text-rose-300 shadow-sm">{{ errorMessage }}</div>

          <button class="btn-primary w-fit font-black px-8 py-3 text-xs uppercase tracking-wider" type="button" :disabled="isSaving" @click="saveProfile">{{ isSaving ? 'Đang lưu...' : 'Lưu thay đổi' }}</button>
        </div>
      </article>
    </div>

    <!-- TAB 2: GÓI DỊCH VỤ & HẠN MỨC SỬ DỤNG (CHUYÊN NGHIỆP & SANG TRỌNG) -->
    <div v-else-if="activeTab === 'subscription'" class="grid gap-8">
      
      <!-- 1. Current Plan Status Banner -->
      <div class="relative overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)]/70 p-6 md:p-9 shadow-[var(--shadow-card)] backdrop-blur-3xl">
        <!-- Glow Orbs -->
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-20 -bottom-20 h-64 w-64 rounded-full bg-[var(--accent)]/15 blur-3xl"></div>

        <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
          <div>
            <div class="flex items-center gap-3.5 flex-wrap">
              <span class="inline-flex items-center justify-center h-11 w-11 rounded-2xl bg-gradient-to-tr from-[var(--primary)] via-fuchsia-500 to-[var(--accent)] text-white shadow-[0_8px_20px_rgba(155,44,255,0.3)]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
              </span>
              <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-[var(--muted)]">Active Subscription Tier</span>
                <h2 class="text-2xl md:text-3xl font-black text-[var(--text)] tracking-tight">
                  Gói Hiện Tại: <span class="bg-gradient-to-r from-[var(--primary)] via-fuchsia-400 to-[var(--accent)] bg-clip-text text-transparent">{{ currentPlanLabel }}</span>
                </h2>
              </div>
              <span 
                class="px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider border shadow-sm self-center ml-2"
                :class="statusBadgeInfo.class"
              >
                {{ statusBadgeInfo.text }}
              </span>
            </div>

            <p class="mt-4 text-xs md:text-sm text-[var(--muted)] font-semibold leading-relaxed max-w-3xl">
              <template v-if="profile.role === 'admin'">
                Tài khoản Admin có quyền hạn tối cao, quản lý và sử dụng toàn bộ tính năng mà không có rào cản hạn mức.
              </template>
              <template v-else-if="isTrialPlan">
                Bạn đang trong thời gian <b class="text-emerald-400 font-black">dùng thử 7 ngày gói Plus</b> (kết thúc vào <b class="text-[var(--text)] font-black">{{ formatDate(profile.vip_expires_at || profile.plan_expires_at) }}</b>). Sau thời gian này, tài khoản sẽ tự động chuyển về gói Free mà không làm mất dữ liệu.
              </template>
              <template v-else-if="profile.vip_expires_at || profile.plan_expires_at">
                Gói dịch vụ chính thức của bạn đang hoạt động. Thời hạn đăng ký còn đến: 
                <b class="text-[var(--text)] font-black">{{ formatDate(profile.vip_expires_at || profile.plan_expires_at) }}</b>.
              </template>
              <template v-else>
                Bạn đang sử dụng gói Miễn phí với tài nguyên cơ bản. Nâng cấp gói dịch vụ để mở khóa đầy đủ sức mạnh AI, OCR scan và tạo phòng thi đấu.
              </template>
            </p>
          </div>

          <!-- Upgrade CTA Button -->
          <router-link 
            to="/upgrade" 
            class="shrink-0 h-12 px-7 flex items-center justify-center font-black text-xs uppercase tracking-wider rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] text-white shadow-[0_12px_32px_rgba(155,44,255,0.35)] hover:-translate-y-0.5 active:scale-95 transition duration-300"
          >
            Nâng Cấp Gói / Gia Hạn ➔
          </router-link>
        </div>
      </div>

      <!-- 2. AI Quota Metered Progress Bar Card -->
      <div class="relative overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)]/70 p-6 md:p-8 shadow-[var(--shadow-card)] backdrop-blur-3xl grid gap-6">
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <div>
            <div class="flex items-center gap-2">
              <span class="h-2.5 w-2.5 rounded-full bg-[var(--primary)] shadow-[0_0_12px_var(--primary)]"></span>
              <h3 class="text-xl font-black text-[var(--text)] tracking-tight">Hạn Mức Tiêu Thụ AI Generator</h3>
            </div>
            <p class="text-xs text-[var(--muted)] font-semibold mt-1">
              Số lượt sinh câu hỏi và tạo quiz bằng trí tuệ nhân tạo còn lại.
            </p>
          </div>

          <div class="flex items-center gap-6">
            <div class="text-right">
              <span class="text-3xl font-black text-[var(--text)] tracking-tight">{{ profile.ai_quota_remaining ?? 0 }}</span>
              <span class="text-xs text-[var(--muted)] font-bold uppercase"> / {{ maxAiQuota }} lượt</span>
            </div>
          </div>
        </div>

        <!-- Visual Meter Progress Bar -->
        <div class="space-y-2.5">
          <div class="h-4 w-full rounded-full bg-[var(--surface-soft)]/90 p-0.5 border border-[var(--border)] overflow-hidden shadow-inner">
            <div 
              class="h-full rounded-full transition-all duration-700 bg-gradient-to-r shadow-md"
              :class="aiQuotaColorClass"
              :style="{ width: `${aiQuotaPercentage}%` }"
            ></div>
          </div>
          <div class="flex justify-between text-xs font-bold text-[var(--muted)]">
            <span>Dung lượng khả dụng: {{ aiQuotaPercentage }}%</span>
            <span v-if="profile.ai_quota_remaining < 20" class="text-rose-400 font-black flex items-center gap-1">
              <span class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></span>
              Sắp hết lượt AI
            </span>
            <span v-else class="text-emerald-400 font-bold">Sẵn sàng sử dụng</span>
          </div>
        </div>
      </div>

      <!-- 3. Resource Limits Grid Cards -->
      <div>
        <div class="flex items-center justify-between gap-4 mb-5">
          <div>
            <span class="text-[10px] font-black uppercase tracking-widest text-[var(--primary)]">Resource Privileges</span>
            <h3 class="text-2xl font-black text-[var(--text)] tracking-tight">Hạn Mức Tài Nguyên Chi Tiết</h3>
          </div>
          <span class="text-xs font-bold text-[var(--muted)]">Theo đặc quyền gói cước hiện tại</span>
        </div>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
          
          <!-- OCR Scan Limit Card -->
          <div class="group relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)]/70 p-6 shadow-sm backdrop-blur-xl transition duration-300 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-soft)]">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20">OCR SCAN ENGINE</span>
              <span class="h-2.5 w-2.5 rounded-full" :class="limitsInfo.ocrAllowed ? 'bg-purple-400 shadow-[0_0_8px_rgba(192,132,252,0.8)]' : 'bg-rose-500'"></span>
            </div>
            <h4 class="mt-4 font-black text-base text-[var(--text)]">Quét Tài Liệu OCR</h4>
            <p class="mt-2 text-xs font-bold leading-relaxed" :class="limitsInfo.ocrAllowed ? 'text-[var(--text)]' : 'text-rose-400/90'">
              {{ limitsInfo.ocr }}
            </p>
          </div>

          <!-- Live Room Limit Card -->
          <div class="group relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)]/70 p-6 shadow-sm backdrop-blur-xl transition duration-300 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-soft)]">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">PHÒNG THI ĐẤU</span>
              <span class="h-2.5 w-2.5 rounded-full" :class="limitsInfo.liveRoomAllowed ? 'bg-blue-400 shadow-[0_0_8px_rgba(96,165,250,0.8)]' : 'bg-rose-500'"></span>
            </div>
            <h4 class="mt-4 font-black text-base text-[var(--text)]">Phòng Thi Trực Tiếp</h4>
            <p class="mt-2 text-xs font-bold leading-relaxed" :class="limitsInfo.liveRoomAllowed ? 'text-[var(--text)]' : 'text-rose-400/90'">
              {{ limitsInfo.liveRoom }}
            </p>
          </div>

          <!-- Homework Rooms Card -->
          <div class="group relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)]/70 p-6 shadow-sm backdrop-blur-xl transition duration-300 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-soft)]">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">PHÒNG BÀI TẬP</span>
              <span class="h-2.5 w-2.5 rounded-full" :class="limitsInfo.homeworkAllowed ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]' : 'bg-rose-500'"></span>
            </div>
            <h4 class="mt-4 font-black text-base text-[var(--text)]">Phòng Bài Tập Lớp Học</h4>
            <p class="mt-2 text-xs font-bold leading-relaxed" :class="limitsInfo.homeworkAllowed ? 'text-[var(--text)]' : 'text-rose-400/90'">
              {{ limitsInfo.homeworkRoom }}
            </p>
          </div>

          <!-- Export Report Card -->
          <div class="group relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)]/70 p-6 shadow-sm backdrop-blur-xl transition duration-300 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-soft)]">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">EXPORT ANALYTICS</span>
              <span class="h-2.5 w-2.5 rounded-full" :class="limitsInfo.exportAllowed ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]' : 'bg-rose-500'"></span>
            </div>
            <h4 class="mt-4 font-black text-base text-[var(--text)]">Xuất Báo Cáo PDF/Excel</h4>
            <p class="mt-2 text-xs font-bold leading-relaxed" :class="limitsInfo.exportAllowed ? 'text-emerald-400' : 'text-rose-400/90'">
              {{ limitsInfo.exportAllowed ? '✓ Khả dụng trong gói' : '🔒 Khóa (Yêu cầu gói Plus)' }}
            </p>
          </div>

        </div>
      </div>

      <!-- 4. Billing & Payment History -->
      <div class="overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)]/70 p-6 md:p-8 shadow-[var(--shadow-card)] backdrop-blur-3xl">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
          <div>
            <span class="text-[10px] font-black uppercase tracking-widest text-[var(--primary)]">Transaction Records</span>
            <h3 class="text-2xl font-black text-[var(--text)] tracking-tight">Lịch Sử Giao Dịch & Hóa Đơn</h3>
            <p class="text-xs text-[var(--muted)] mt-1 font-semibold">Theo dõi tất cả các hóa đơn thanh toán qua MoMo / PayOS của tài khoản.</p>
          </div>
          <button 
            @click="loadPaymentHistory" 
            type="button"
            class="text-xs font-black px-4 py-2.5 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] text-[var(--text)] hover:bg-[var(--chip-active)] hover:border-[var(--primary)]/40 transition active:scale-95"
          >
            Làm mới lịch sử
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left text-xs font-semibold">
            <thead>
              <tr class="border-b border-[var(--border)] text-[var(--muted)]">
                <th class="pb-3.5 pr-4 font-black uppercase tracking-wider text-[10px]">Mã giao dịch</th>
                <th class="pb-3.5 px-4 font-black uppercase tracking-wider text-[10px]">Gói cước</th>
                <th class="pb-3.5 px-4 font-black uppercase tracking-wider text-[10px]">Cổng thanh toán</th>
                <th class="pb-3.5 px-4 font-black uppercase tracking-wider text-[10px]">Số tiền</th>
                <th class="pb-3.5 px-4 font-black uppercase tracking-wider text-[10px]">Trạng thái</th>
                <th class="pb-3.5 pl-4 font-black uppercase tracking-wider text-[10px]">Thời gian</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="item in historyList" 
                :key="item.id" 
                class="border-b border-[var(--border)]/40 hover:bg-[var(--surface-soft)]/40 transition"
              >
                <td class="py-4 pr-4 font-mono text-xs text-[var(--text)] font-bold">{{ item.order_code }}</td>
                <td class="py-4 px-4 text-[var(--text)] font-black text-xs">{{ item.plan_name || 'Gói nạp' }}</td>
                <td class="py-4 px-4 text-[10px] font-bold uppercase text-[var(--muted)]">
                  <span class="inline-flex items-center gap-1 rounded-md bg-fuchsia-500/10 text-fuchsia-400 px-2.5 py-1 border border-fuchsia-500/20" v-if="item.provider === 'momo'">
                    MoMo
                  </span>
                  <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 text-emerald-400 px-2.5 py-1 border border-emerald-500/20" v-else-if="item.provider === 'payos'">
                    PayOS VietQR
                  </span>
                  <span class="inline-flex items-center gap-1 rounded-md bg-purple-500/10 text-purple-400 px-2.5 py-1 border border-purple-500/20" v-else-if="item.provider === 'trial'">
                    Dùng thử
                  </span>
                  <span class="inline-flex items-center gap-1 rounded-md bg-blue-500/10 text-blue-400 px-2.5 py-1 border border-blue-500/20" v-else>
                    {{ item.provider || 'Nội bộ' }}
                  </span>
                </td>
                <td class="py-4 px-4 text-[var(--text)] font-black text-xs">{{ formatPrice(item.amount) }}</td>
                <td class="py-4 px-4">
                  <span 
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-black uppercase text-[10px]"
                    :class="getStatusBadgeClass(item.status)"
                  >
                    {{ getStatusText(item.status) }}
                  </span>
                </td>
                <td class="py-4 pl-4 text-[var(--muted)] text-xs font-semibold">{{ formatDate(item.created_at) }}</td>
              </tr>
              <tr v-if="historyList.length === 0">
                <td colspan="6" class="py-12 text-center text-[var(--muted)] font-bold">
                  Bạn chưa có lịch sử giao dịch nào trên hệ thống.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UserAvatar from '@/components/common/UserAvatar.vue'
import { authApi, currentUserStorage, tokenStorage, paymentsApi } from '@/services/api'

const route = useRoute()
const router = useRouter()

const activeTab = ref('profile')
const profile = reactive({ name: '', email: '', role: 'free', role_label: 'Free', avatar: '', ai_quota_remaining: 0, ...(currentUserStorage.get() || {}) })
const avatarInput = ref(null)
const avatarFile = ref(null)
const avatarPreview = ref('')
const removeAvatarFlag = ref(false)
const message = ref('')
const errorMessage = ref('')
const isSaving = ref(false)
const historyList = ref([])

const avatarUser = computed(() => ({ ...profile, avatar: avatarPreview.value || profile.avatar || '' }))

const isTrialPlan = computed(() => {
  const role = (profile.role || 'free').toLowerCase()
  if (role !== 'plus') return false // Trial 7 ngày CHỈ dành riêng cho gói Plus!
  if (!profile.trial_used_at) return false
  const expiry = profile.vip_expires_at || profile.plan_expires_at
  return expiry ? new Date(expiry) > new Date() : false
})

const statusBadgeInfo = computed(() => {
  const role = (profile.role || 'free').toLowerCase()
  if (role === 'admin') {
    return { text: 'Quản trị viên', class: 'bg-purple-500/10 text-purple-400 border-purple-500/20 font-black' }
  }
  if (isTrialPlan.value) {
    return { text: 'Dùng thử Plus (7 ngày)', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 font-black animate-pulse' }
  }
  if (role === 'ultra') {
    return { text: 'Gói Ultra Chính Thức', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20 font-black' }
  }
  if (role === 'pro') {
    return { text: 'Gói Pro Chính Thức', class: 'bg-blue-500/10 text-blue-400 border-blue-500/20 font-black' }
  }
  if (role === 'plus') {
    return { text: 'Gói Plus Chính Thức', class: 'bg-teal-500/10 text-teal-400 border-teal-500/20 font-black' }
  }
  return { text: 'Tài khoản Miễn phí', class: 'bg-[var(--surface-soft)] text-[var(--muted)] border-[var(--border)]' }
})

const currentPlanLabel = computed(() => {
  const role = (profile.role || 'free').toLowerCase()
  if (role === 'admin') return 'Admin (Quản trị viên)'
  if (role === 'ultra') return 'ULTRA (Tối thượng)'
  if (role === 'pro') return 'PRO (Chuyên nghiệp)'
  if (role === 'plus') return isTrialPlan.value ? 'PLUS (Dùng thử 7 ngày)' : 'PLUS (Cơ bản)'
  return 'FREE (Miễn phí)'
})

const maxAiQuota = computed(() => {
  const role = (profile.role || 'free').toLowerCase()
  if (role === 'admin') return 9999
  if (role === 'ultra') return 1500
  if (role === 'pro') return 350
  if (role === 'plus') return 100
  return Math.max(profile.ai_quota_remaining || 0, 5)
})

const aiQuotaPercentage = computed(() => {
  const current = profile.ai_quota_remaining ?? 0
  const max = maxAiQuota.value
  if (!max || max <= 0) return 0
  return Math.min(100, Math.max(0, Math.round((current / max) * 100)))
})

const aiQuotaColorClass = computed(() => {
  const pct = aiQuotaPercentage.value
  if (pct > 30) return 'from-emerald-500 to-teal-400'
  if (pct >= 10) return 'from-amber-500 to-yellow-400'
  return 'from-rose-500 to-red-500'
})

const limitsInfo = computed(() => {
  const role = (profile.role || 'free').toLowerCase()
  if (role === 'admin' || role === 'ultra') {
    return {
      ocr: 'Không giới hạn số lượt',
      ocrAllowed: true,
      liveRoom: 'Tối đa 500 người chơi / phòng',
      liveRoomAllowed: true,
      homeworkRoom: 'Không giới hạn phòng',
      homeworkAllowed: true,
      exportAllowed: true
    }
  }
  if (role === 'pro') {
    return {
      ocr: 'Tối đa 50 lượt / tháng',
      ocrAllowed: true,
      liveRoom: 'Tối đa 100 người chơi / phòng',
      liveRoomAllowed: true,
      homeworkRoom: 'Tối đa 20 phòng',
      homeworkAllowed: true,
      exportAllowed: true
    }
  }
  if (role === 'plus') {
    return {
      ocr: 'Tối đa 10 lượt / tháng',
      ocrAllowed: true,
      liveRoom: 'Tối đa 20 người chơi / phòng',
      liveRoomAllowed: true,
      homeworkRoom: 'Tối đa 5 phòng',
      homeworkAllowed: true,
      exportAllowed: true
    }
  }
  return {
    ocr: '🔒 Khóa (Yêu cầu gói Plus)',
    ocrAllowed: false,
    liveRoom: '🔒 Khóa (Yêu cầu gói Plus)',
    liveRoomAllowed: false,
    homeworkRoom: '🔒 Khóa (Yêu cầu gói Plus)',
    homeworkAllowed: false,
    exportAllowed: false
  }
})

const switchTab = (tabName) => {
  activeTab.value = tabName
  router.replace({ query: { ...route.query, tab: tabName } })
  if (tabName === 'subscription') {
    loadPaymentHistory()
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  try {
    return new Date(dateStr).toLocaleString('vi-VN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return dateStr
  }
}

const formatPrice = (amount) => {
  if (amount === undefined || amount === null) return '0đ'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount)
}

const getStatusBadgeClass = (status) => {
  if (status === 'success') return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
  if (status === 'pending') return 'bg-amber-500/10 text-amber-400 border border-amber-500/20'
  return 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
}

const getStatusText = (status) => {
  if (status === 'success') return 'Thành công'
  if (status === 'pending') return 'Đang xử lý'
  return 'Thất bại'
}

const loadPaymentHistory = async () => {
  if (!tokenStorage.get()) return
  try {
    const res = await paymentsApi.history()
    historyList.value = Array.isArray(res?.data) ? res.data : []
  } catch (error) {
    console.error('Failed to load history:', error)
  }
}

const openAvatarPicker = () => avatarInput.value?.click()

const revokeAvatarPreview = () => {
  if (avatarPreview.value?.startsWith('blob:')) URL.revokeObjectURL(avatarPreview.value)
}

const clearAvatarInput = () => {
  if (avatarInput.value) avatarInput.value.value = ''
  errorMessage.value = ''
}

const removeAvatar = () => {
  revokeAvatarPreview()
  avatarPreview.value = ''
  avatarFile.value = null
  profile.avatar = ''
  removeAvatarFlag.value = true
  clearAvatarInput()
}

const ALLOWED_AVATAR_TYPES = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp']

const handleAvatarFileChange = (event) => {
  const [file] = event.target.files || []
  if (!file) return

  if (!ALLOWED_AVATAR_TYPES.includes(file.type)) {
    errorMessage.value = 'Avatar chỉ chấp nhận định dạng PNG, JPG hoặc WEBP.'
    event.target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = 'Avatar tối đa 2MB.'
    event.target.value = ''
    return
  }

  revokeAvatarPreview()
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
  removeAvatarFlag.value = false
  errorMessage.value = ''
}

const loadProfileFromApi = async () => {
  if (!tokenStorage.get()) return

  try {
    const user = await authApi.me()
    Object.assign(profile, user)
  } catch (error) {
    errorMessage.value = `Không tải được hồ sơ: ${error.message}`
  }
}

const nameError = ref('')

const validateName = (value) => {
  const trimmed = (value || '').trim()
  if (!trimmed) return 'Vui lòng nhập họ tên.'
  if (trimmed.length > 100) return 'Họ tên tối đa 100 ký tự.'
  return ''
}

const saveProfile = async () => {
  if (!tokenStorage.get()) {
    errorMessage.value = 'Bạn cần đăng nhập trước khi lưu hồ sơ.'
    return
  }

  isSaving.value = true
  message.value = ''
  errorMessage.value = ''

  try {
    const saved = await authApi.updateProfile({
      name: profile.name.trim(),
      avatar_file: avatarFile.value || undefined,
      remove_avatar: removeAvatarFlag.value,
    })

    Object.assign(profile, saved)
    revokeAvatarPreview()
    avatarPreview.value = ''
    avatarFile.value = null
    removeAvatarFlag.value = false
    clearAvatarInput()
    message.value = 'Đã lưu hồ sơ thành công.'
    setTimeout(() => { message.value = '' }, 2500)
  } catch (error) {
    errorMessage.value = `Lưu thất bại: ${error.message}`
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  if (route.query.tab === 'subscription') {
    activeTab.value = 'subscription'
    loadPaymentHistory()
  }
  loadProfileFromApi()
})

watch(() => route.query.tab, (newTab) => {
  if (newTab === 'subscription') {
    activeTab.value = 'subscription'
    loadPaymentHistory()
  } else if (newTab === 'profile') {
    activeTab.value = 'profile'
  }
})

onBeforeUnmount(revokeAvatarPreview)
</script>
