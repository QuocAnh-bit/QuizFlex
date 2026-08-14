<template>
  <section class="max-w-4xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 space-y-2">
      <span
        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-3 py-0.5 text-xs font-bold text-amber-700"
      >
        <Clock3 class="h-3.5 w-3.5" />
        Thời gian thực
      </span>

      <h1 class="text-2xl sm:text-3xl font-black text-slate-900 pt-1">
        Phòng thi đấu trực tiếp
      </h1>

      <p class="text-xs sm:text-sm text-slate-600 max-w-2xl leading-relaxed">
        Tạo phòng thi đấu thời gian thực kiểu Kahoot, mời bạn bè qua mã PIN và
        cùng tranh tài trên bảng xếp hạng trực tiếp.
      </p>
    </div>

    <!-- 2 Choice Cards -->
    <div class="grid gap-5 md:grid-cols-2">
      <!-- Host Card -->
      <router-link
        v-if="canCreateLiveRoom"
        class="card p-6 sm:p-8 flex flex-col justify-between card-hover"
        to="/live-rooms/create"
      >
        <div class="space-y-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 px-3 py-1 text-xs font-bold"
          >
            <Crown class="h-3.5 w-3.5" />
            Dành cho chủ phòng
          </span>

          <h2 class="text-xl font-bold text-slate-900 pt-1">
            Tạo phòng thi đấu
          </h2>

          <p class="text-xs text-slate-500 leading-relaxed">
            Chọn bộ quiz từ kho của bạn, tạo mã PIN và quản lý phòng thi đấu
            trực tuyến cho các thành viên.
          </p>
        </div>

        <div class="pt-6">
          <span
            class="btn-primary text-xs w-full justify-center inline-flex items-center gap-1.5"
          >
            Tạo phòng thi đấu ngay
            <ArrowRight class="h-3.5 w-3.5" />
          </span>
        </div>
      </router-link>

      <!-- Upgrade Card -->
      <article
        v-else
        class="card p-6 sm:p-8 flex flex-col justify-between card-hover"
      >
        <div class="space-y-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-600 px-3 py-1 text-xs font-bold"
          >
            <Crown class="h-3.5 w-3.5" />
            Gói nâng cấp
          </span>

          <h2 class="text-xl font-bold text-slate-900 pt-1">
            Tạo phòng thi đấu
          </h2>

          <p class="text-xs text-slate-500 leading-relaxed">
            Tài khoản Plus/Pro/Ultra mới có thể tạo phòng thi đấu. Bạn vẫn có
            thể tham gia vào bất kỳ phòng nào bằng mã PIN.
          </p>
        </div>

        <div class="pt-6">
          <router-link
            class="btn-secondary text-xs w-full justify-center inline-flex items-center gap-1.5"
            to="/upgrade"
          >
            Nâng cấp tài khoản
            <Crown class="h-3.5 w-3.5" />
          </router-link>
        </div>
      </article>

      <!-- Player Card -->
      <router-link
        class="card p-6 sm:p-8 flex flex-col justify-between card-hover"
        to="/live-rooms/join"
      >
        <div class="space-y-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 text-[#7C3AED] border border-purple-200 px-3 py-1 text-xs font-bold"
          >
            <Gamepad2 class="h-3.5 w-3.5" />
            Dành cho người chơi
          </span>

          <h2 class="text-xl font-bold text-slate-900 pt-1">
            Tham gia phòng thi đấu
          </h2>

          <p class="text-xs text-slate-500 leading-relaxed">
            Nhập mã PIN từ bạn bè hoặc giáo viên, chọn biệt danh và sẵn sàng
            thi đấu tính điểm trên bảng xếp hạng.
          </p>
        </div>

        <div class="pt-6">
          <span
            class="btn-secondary text-xs w-full justify-center inline-flex items-center gap-1.5"
          >
            Nhập mã PIN phòng
            <ArrowRight class="h-3.5 w-3.5" />
          </span>
        </div>
      </router-link>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import {
  ArrowRight,
  Clock3,
  Crown,
  Gamepad2,
} from 'lucide-vue-next'
import { currentUserStorage } from '@/services/api'

const currentUser = currentUserStorage.get()

const canCreateLiveRoom = computed(() => {
  const role = String(currentUser?.role || 'free').toLowerCase()
  return ['admin', 'plus', 'pro', 'ultra'].includes(role)
})
</script>