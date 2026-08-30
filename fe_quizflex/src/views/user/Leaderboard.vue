<template>
  <div class="mx-auto max-w-6xl space-y-6 py-4">
    <!-- =========================================================
         HEADER & HERO BANNER
    ========================================================== -->
    <div
      class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
    >
      <!-- Background subtle decorative accents -->
      <div
        class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-purple-500/5 blur-3xl"
      ></div>
      <div
        class="pointer-events-none absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-amber-500/5 blur-3xl"
      ></div>

      <div class="relative z-10 flex flex-col justify-between gap-6 md:flex-row md:items-center">
        <div>
          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-bold text-[#7C3AED]"
            >
              <Sparkles class="h-3.5 w-3.5 text-[#7C3AED]" />
              Bảng vàng thành tích toàn cầu
            </span>

            <span
              class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"
            >
              <span class="h-1.5 w-1.5 animate-ping rounded-full bg-emerald-500"></span>
              Trực tiếp
            </span>
          </div>

          <h1 class="mt-2.5 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
            Bảng Xếp Hạng QuizFlex
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
          </h1>

          <p class="mt-1 text-sm font-medium leading-relaxed text-slate-600">
            Vinh danh những học viên tích cực nhất hệ thống qua tiến trình cấp độ (Level) và danh hiệu thành tích (Achievement).
          </p>
        </div>

        <!-- User Standing Card (Quick Summary) -->
        <div
          v-if="myRankInfo"
          class="flex items-center gap-4 rounded-2xl border border-purple-100 bg-gradient-to-br from-purple-50/70 to-indigo-50/40 p-4 shadow-xs"
        >
          <div
            class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#7C3AED] text-base font-black text-white shadow-md shadow-purple-500/20"
          >
            #{{ myRankInfo.rank }}
          </div>

          <div class="min-w-0">
            <div class="flex items-center gap-1.5">
              <span class="text-xs font-bold text-slate-500">Vị trí của bạn</span>
              <span class="rounded bg-purple-200/80 px-1.5 py-0.2 text-[10px] font-black text-purple-900">
                Level {{ myRankInfo.level }}
              </span>
            </div>
            <p class="truncate text-sm font-black text-slate-900">
              {{ myRankInfo.name }}
            </p>
            <div class="mt-0.5 flex items-center gap-2 text-xs">
              <span class="font-bold text-[#7C3AED] inline-flex items-center gap-1">
                <Zap class="h-3.5 w-3.5 fill-current" />
                {{ Number(myRankInfo.xp).toLocaleString() }} XP
              </span>
              <span class="text-slate-300">·</span>
              <span
                class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-bold"
                :class="getUserAchievement(myRankInfo).badgeClass"
              >
                <span>{{ getUserAchievement(myRankInfo).icon }}</span>
                <span>{{ getUserAchievement(myRankInfo).title }}</span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================
         LOADING STATE
    ========================================================== -->
    <div
      v-if="isLoading"
      class="card flex min-h-[400px] flex-col items-center justify-center px-6 py-12 text-center"
    >
      <div
        class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-50"
      >
        <Crown class="h-8 w-8 animate-bounce text-[#7C3AED]" :stroke-width="2" />
      </div>

      <h2 class="text-lg font-black text-slate-900">
        Đang tải Bảng tổng sắp...
      </h2>

      <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
        Đang đồng bộ dữ liệu điểm kinh nghiệm (XP) và danh hiệu của các học viên trên toàn hệ thống.
      </p>

      <div class="mt-6 h-1.5 w-56 overflow-hidden rounded-full bg-slate-100">
        <div class="h-full w-1/3 rounded-full bg-[#7C3AED] animate-loading-bar"></div>
      </div>
    </div>

    <!-- =========================================================
         LOADED STATE
    ========================================================== -->
    <template v-else>
      <!-- =======================================================
           TOP 3 PODIUM (BỤC VINH QUANG 3 CẤP)
      ======================================================== -->
      <div
        v-if="topThree.length > 0"
        class="grid grid-cols-1 items-end gap-4 pt-2 md:grid-cols-3"
      >
        <!-- =====================================================
             RANK 2 - SILVER (BẠC - BÊN TRÁI)
        ====================================================== -->
        <div
          v-if="topThree[1]"
          class="relative order-2 overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-b from-white to-slate-50 p-6 text-center shadow-sm transition hover:shadow-md md:order-1"
        >
          <!-- Silver Tier Accent Top Bar -->
          <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-slate-300 via-slate-400 to-slate-300"></div>

          <!-- Rank Badge -->
          <div class="mx-auto mb-3 flex items-center justify-center">
            <span
              class="inline-flex h-8 items-center gap-1 rounded-full border border-slate-300 bg-slate-100 px-3 text-xs font-black text-slate-700 shadow-2xs"
            >
              <Medal class="h-4 w-4 text-slate-500" />
              Hạng 2
            </span>
          </div>

          <!-- Avatar -->
          <div class="relative mx-auto mb-3 h-20 w-20">
            <div
              class="grid h-20 w-20 place-items-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-2xl font-black text-slate-700 shadow-inner ring-4 ring-slate-200"
            >
              {{ topThree[1].name?.charAt(0).toUpperCase() || "-" }}
            </div>
            <div
              class="absolute -bottom-1 -right-1 grid h-7 w-7 place-items-center rounded-xl bg-slate-600 text-xs font-black text-white shadow-sm"
            >
              2
            </div>
          </div>

          <!-- Name -->
          <h3 class="truncate text-base font-bold text-slate-900" :title="topThree[1].name">
            {{ topThree[1].name }}
            <span v-if="topThree[1].is_me" class="ml-1 text-xs font-bold text-[#7C3AED]">(Bạn)</span>
          </h3>

          <!-- Level & Title Unified -->
          <div class="mt-1.5 flex items-center justify-center gap-1.5 text-xs">
            <span class="font-bold text-slate-700">Level {{ topThree[1].level }}</span>
            <span class="text-slate-300">·</span>
            <span
              class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-bold"
              :class="getUserAchievement(topThree[1]).badgeClass"
            >
              <span>{{ getUserAchievement(topThree[1]).icon }}</span>
              <span>{{ getUserAchievement(topThree[1]).title }}</span>
            </span>
          </div>

          <!-- XP Badge -->
          <div
            class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-black text-slate-800 shadow-2xs"
          >
            <Zap class="h-4 w-4 fill-slate-400 text-slate-500" />
            <span>{{ Number(topThree[1].xp).toLocaleString() }} XP</span>
          </div>
        </div>

        <!-- =====================================================
             RANK 1 - GOLD (QUÁN QUÂN - Ở GIỮA, NÂNG CAO)
        ====================================================== -->
        <div
          v-if="topThree[0]"
          class="relative order-1 overflow-hidden rounded-3xl border-2 border-amber-300 bg-gradient-to-b from-amber-50/50 via-white to-amber-50/20 p-6 text-center shadow-lg shadow-amber-500/10 transition hover:shadow-xl md:order-2 md:-translate-y-3 sm:p-8"
        >
          <!-- Gold Tier Accent Top Bar -->
          <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500"></div>

          <!-- Crown Header -->
          <div class="mx-auto mb-2 flex items-center justify-center">
            <span
              class="inline-flex h-8 items-center gap-1.5 rounded-full border border-amber-300 bg-amber-100 px-3.5 text-xs font-black text-amber-900 shadow-2xs animate-pulse"
            >
              <Crown class="h-4 w-4 text-amber-600" />
              QUÁN QUÂN #1
            </span>
          </div>

          <!-- Avatar with Glowing Gold Ring -->
          <div class="relative mx-auto mb-3 h-24 w-24">
            <div
              class="grid h-24 w-24 place-items-center rounded-2xl bg-gradient-to-br from-amber-300 via-amber-400 to-yellow-500 text-3xl font-black text-amber-950 shadow-md ring-4 ring-amber-300/80"
            >
              {{ topThree[0].name?.charAt(0).toUpperCase() || "-" }}
            </div>
            <div
              class="absolute -bottom-1.5 -right-1.5 grid h-8 w-8 place-items-center rounded-xl bg-amber-500 text-xs font-black text-white shadow-md ring-2 ring-white"
            >
              👑 1
            </div>
          </div>

          <!-- Name -->
          <h2 class="truncate text-lg font-black text-slate-900" :title="topThree[0].name">
            {{ topThree[0].name }}
            <span v-if="topThree[0].is_me" class="ml-1 text-xs font-bold text-[#7C3AED]">(Bạn)</span>
          </h2>

          <!-- Level & Title Unified -->
          <div class="mt-1.5 flex items-center justify-center gap-1.5 text-xs">
            <span class="font-bold text-amber-950">Level {{ topThree[0].level }}</span>
            <span class="text-amber-400">·</span>
            <span
              class="inline-flex items-center gap-1 rounded-md border border-amber-300 bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-900"
              :class="getUserAchievement(topThree[0]).badgeClass"
            >
              <span>{{ getUserAchievement(topThree[0]).icon }}</span>
              <span>{{ getUserAchievement(topThree[0]).title }}</span>
            </span>
          </div>

          <!-- XP Badge -->
          <div
            class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-gradient-to-r from-amber-400 to-yellow-500 px-4 py-2 text-sm font-black text-amber-950 shadow-sm"
          >
            <Trophy class="h-4 w-4 text-amber-950" />
            <span>{{ Number(topThree[0].xp).toLocaleString() }} XP</span>
          </div>
        </div>

        <!-- =====================================================
             RANK 3 - BRONZE (ĐỒNG - BÊN PHẢI)
        ====================================================== -->
        <div
          v-if="topThree[2]"
          class="relative order-3 overflow-hidden rounded-3xl border border-amber-200 bg-gradient-to-b from-white to-amber-50/20 p-6 text-center shadow-sm transition hover:shadow-md"
        >
          <!-- Bronze Tier Accent Top Bar -->
          <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800"></div>

          <!-- Rank Badge -->
          <div class="mx-auto mb-3 flex items-center justify-center">
            <span
              class="inline-flex h-8 items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-3 text-xs font-black text-amber-800 shadow-2xs"
            >
              <Medal class="h-4 w-4 text-amber-700" />
              Hạng 3
            </span>
          </div>

          <!-- Avatar -->
          <div class="relative mx-auto mb-3 h-20 w-20">
            <div
              class="grid h-20 w-20 place-items-center rounded-2xl bg-gradient-to-br from-amber-100 to-amber-200 text-2xl font-black text-amber-900 shadow-inner ring-4 ring-amber-200"
            >
              {{ topThree[2].name?.charAt(0).toUpperCase() || "-" }}
            </div>
            <div
              class="absolute -bottom-1 -right-1 grid h-7 w-7 place-items-center rounded-xl bg-amber-700 text-xs font-black text-white shadow-sm"
            >
              3
            </div>
          </div>

          <!-- Name -->
          <h3 class="truncate text-base font-bold text-slate-900" :title="topThree[2].name">
            {{ topThree[2].name }}
            <span v-if="topThree[2].is_me" class="ml-1 text-xs font-bold text-[#7C3AED]">(Bạn)</span>
          </h3>

          <!-- Level & Title Unified -->
          <div class="mt-1.5 flex items-center justify-center gap-1.5 text-xs">
            <span class="font-bold text-slate-700">Level {{ topThree[2].level }}</span>
            <span class="text-slate-300">·</span>
            <span
              class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-bold"
              :class="getUserAchievement(topThree[2]).badgeClass"
            >
              <span>{{ getUserAchievement(topThree[2]).icon }}</span>
              <span>{{ getUserAchievement(topThree[2]).title }}</span>
            </span>
          </div>

          <!-- XP Badge -->
          <div
            class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-amber-200 bg-white px-3.5 py-1.5 text-xs font-black text-amber-900 shadow-2xs"
          >
            <Zap class="h-4 w-4 fill-amber-600 text-amber-700" />
            <span>{{ Number(topThree[2].xp).toLocaleString() }} XP</span>
          </div>
        </div>
      </div>

      <!-- =======================================================
           LEADERBOARD TABLE & SIDEBAR GRID
      ======================================================== -->
      <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <!-- MAIN LEADERBOARD TABLE -->
        <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <!-- Table Toolbar & Search -->
          <div
            class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4 sm:p-5"
          >
            <div class="flex items-center gap-2">
              <span class="h-4 w-1 rounded-full bg-indigo-500"></span>
              <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900">
                Danh sách Xếp Hạng Toàn Hệ Thống
              </h3>
              <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">
                {{ filteredLeaderboard.length }} học viên
              </span>
            </div>

            <!-- Search input -->
            <div class="relative min-w-[200px] flex-1 sm:max-w-xs">
              <Search class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Tìm học viên theo tên..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-xs font-medium text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
              />
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
              <thead>
                <tr class="border-b border-slate-100 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                  <th class="w-20 px-5 py-3.5 text-center">Hạng</th>
                  <th class="px-5 py-3.5">Học viên</th>
                  <th class="hidden px-5 py-3.5 sm:table-cell">Danh hiệu</th>
                  <th class="w-40 px-5 py-3.5 text-right">Điểm XP</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-100 text-xs">
                <tr
                  v-for="(user, i) in paginatedLeaderboard"
                  :key="user.user_id ?? i"
                  class="transition hover:bg-slate-50"
                  :class="user.is_me ? 'border-l-4 border-l-[#7C3AED] bg-purple-50/60 font-semibold' : ''"
                >
                  <!-- RANK COLUMN -->
                  <td class="px-5 py-3.5 text-center font-bold">
                    <div class="flex items-center justify-center">
                      <!-- #1 -->
                      <span
                        v-if="user.rank === 1"
                        class="grid h-7 w-7 place-items-center rounded-lg bg-amber-100 text-xs font-black text-amber-800 shadow-2xs"
                      >
                        🥇
                      </span>
                      <!-- #2 -->
                      <span
                        v-else-if="user.rank === 2"
                        class="grid h-7 w-7 place-items-center rounded-lg bg-slate-200 text-xs font-black text-slate-700 shadow-2xs"
                      >
                        🥈
                      </span>
                      <!-- #3 -->
                      <span
                        v-else-if="user.rank === 3"
                        class="grid h-7 w-7 place-items-center rounded-lg bg-amber-50 text-xs font-black text-amber-900 border border-amber-200 shadow-2xs"
                      >
                        🥉
                      </span>
                      <!-- Top 4 - 10 -->
                      <span
                        v-else-if="user.rank <= 10"
                        class="grid h-7 w-7 place-items-center rounded-lg bg-indigo-50 text-xs font-black text-indigo-700"
                      >
                        #{{ user.rank }}
                      </span>
                      <!-- Rest -->
                      <span v-else class="text-slate-400 font-bold text-xs">
                        #{{ user.rank }}
                      </span>
                    </div>
                  </td>

                  <!-- USER COLUMN: Name & Level Subtitle -->
                  <td class="px-5 py-3.5">
                    <div class="flex items-center gap-3">
                      <!-- Avatar with initials -->
                      <div
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-xl text-xs font-black transition"
                        :class="getUserAvatarClass(user)"
                      >
                        {{ user.name?.charAt(0).toUpperCase() || "-" }}
                      </div>

                      <!-- Information -->
                      <div class="min-w-0">
                        <div class="flex items-center gap-1.5 truncate font-bold text-slate-900">
                          <span class="truncate">{{ user.name }}</span>
                          <span
                            v-if="user.is_me"
                            class="inline-flex items-center rounded-full bg-[#7C3AED] px-2 py-0.2 text-[10px] font-black text-white"
                          >
                            Bạn
                          </span>
                        </div>

                        <!-- Subtitle: Level & Mobile Title -->
                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-normal mt-0.5">
                          <span class="font-bold text-slate-700">Level {{ user.level }}</span>
                          <span class="sm:hidden text-slate-300">·</span>
                          <span class="sm:hidden font-medium text-slate-600">
                            {{ getUserAchievement(user).icon }} {{ getUserAchievement(user).title }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- TIER / ACHIEVEMENT BADGE COLUMN (DESKTOP) -->
                  <td class="hidden px-5 py-3.5 sm:table-cell">
                    <span
                      class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs transition"
                      :class="getUserAchievement(user).badgeClass"
                    >
                      <span class="text-xs">{{ getUserAchievement(user).icon }}</span>
                      <span>{{ getUserAchievement(user).title }}</span>
                    </span>
                  </td>

                  <!-- XP COLUMN -->
                  <td class="px-5 py-3.5 text-right font-black text-slate-900">
                    <div class="inline-flex items-center justify-end gap-1 text-xs sm:text-sm text-[#7C3AED]">
                      <Zap class="h-3.5 w-3.5 fill-current text-[#7C3AED]" />
                      <span>{{ Number(user.xp).toLocaleString() }} XP</span>
                    </div>
                  </td>
                </tr>

                <!-- EMPTY STATE -->
                <tr v-if="filteredLeaderboard.length === 0">
                  <td colspan="4" class="px-5 py-12 text-center">
                    <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-slate-100">
                      <Trophy class="h-6 w-6 text-slate-400" />
                    </div>
                    <p class="font-bold text-slate-700">Không tìm thấy học viên nào</p>
                    <p class="mt-1 text-xs text-slate-500">
                      Vui lòng thử lại với từ khóa tìm kiếm khác.
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- LOAD MORE BUTTON -->
          <div
            v-if="filteredLeaderboard.length > visibleCount"
            class="border-t border-slate-100 p-4 text-center"
          >
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-indigo-600 transition hover:bg-indigo-50 cursor-pointer shadow-2xs"
              @click="visibleCount += 15"
            >
              <span>Xem thêm danh sách xếp hạng (hiển thị {{ visibleCount }}/{{ filteredLeaderboard.length }})</span>
              <ChevronDown class="h-4 w-4" />
            </button>
          </div>
        </article>

        <!-- SIDEBAR: RULES & XP GUIDE -->
        <aside class="space-y-5">
          <!-- How to earn XP Card -->
          <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <span class="grid h-7 w-7 place-items-center rounded-lg bg-amber-50 text-amber-600">
                <Zap class="h-4 w-4 fill-current" />
              </span>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                Cách Tích Lũy Điểm XP
              </h3>
            </div>

            <div class="space-y-3 text-xs">
              <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 transition hover:bg-purple-50/50">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-purple-100 text-xs font-black text-purple-700">
                  +10
                </span>
                <div>
                  <b class="text-slate-900 block font-bold">Hoàn thành bài Quiz</b>
                  <span class="text-slate-500 text-[11px] leading-relaxed">Tích lũy XP sau mỗi lần làm bài thi hoặc ôn luyện.</span>
                </div>
              </div>

              <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 transition hover:bg-amber-50/50">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-amber-100 text-xs font-black text-amber-800">
                  +20
                </span>
                <div>
                  <b class="text-slate-900 block font-bold">Đấu trường Live Room</b>
                  <span class="text-slate-500 text-[11px] leading-relaxed">Thi đấu trực tiếp cùng bạn bè và giành thứ hạng cao.</span>
                </div>
              </div>

              <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 transition hover:bg-emerald-50/50">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-emerald-100 text-xs font-black text-emerald-800">
                  +5
                </span>
                <div>
                  <b class="text-slate-900 block font-bold">Duy trì Chuỗi Streak</b>
                  <span class="text-slate-500 text-[11px] leading-relaxed">Đăng nhập và làm quiz liên tục mỗi ngày để nhận thưởng.</span>
                </div>
              </div>
            </div>
          </article>

          <!-- System Badges & Achievements Card -->
          <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <span class="grid h-7 w-7 place-items-center rounded-lg bg-purple-50 text-[#7C3AED]">
                <Award class="h-4 w-4" />
              </span>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                Huy Hiệu & Thành Tích
              </h3>
            </div>

            <div class="space-y-2 text-xs">
              <div class="flex items-center justify-between rounded-xl border border-slate-100 p-2.5">
                <div class="flex items-center gap-2">
                  <span class="text-base">🚀</span>
                  <div>
                    <span class="font-bold text-slate-800 block">Người mới</span>
                    <span class="text-[10px] text-slate-400">Hoàn thành quiz đầu tiên</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between rounded-xl border border-slate-100 p-2.5">
                <div class="flex items-center gap-2">
                  <span class="text-base">⚡</span>
                  <div>
                    <span class="font-bold text-slate-800 block">Tích lũy</span>
                    <span class="text-[10px] text-slate-400">Đạt mốc 100 XP</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between rounded-xl border border-orange-100 bg-orange-50/40 p-2.5">
                <div class="flex items-center gap-2">
                  <span class="text-base">🔥</span>
                  <div>
                    <span class="font-bold text-orange-950 block">On Fire</span>
                    <span class="text-[10px] text-orange-700">Streak 7 ngày liên tục</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50/40 p-2.5">
                <div class="flex items-center gap-2">
                  <span class="text-base">🧠</span>
                  <div>
                    <span class="font-bold text-indigo-950 block">Thiên tài</span>
                    <span class="text-[10px] text-indigo-700">Đạt mốc 500 XP</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50/60 p-2.5">
                <div class="flex items-center gap-2">
                  <span class="text-base">🏆</span>
                  <div>
                    <span class="font-bold text-amber-950 block">Chiến thần</span>
                    <span class="text-[10px] text-amber-800">Đạt Top 1 bảng xếp hạng</span>
                  </div>
                </div>
              </div>
            </div>
          </article>
        </aside>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from "vue";
import {
  Award,
  ChevronDown,
  Crown,
  Medal,
  Search,
  Sparkles,
  Trophy,
  Zap,
} from "lucide-vue-next";
import { currentUserStorage, gamificationApi } from "@/services/api";

/* =========================================================
   STATE
========================================================= */
const leaderboard = ref([]);
const visibleCount = ref(15);
const isLoading = ref(true);
const searchQuery = ref("");

/* =========================================================
   COMPUTED
========================================================= */
const topThree = computed(() => leaderboard.value.slice(0, 3));

const myRankInfo = computed(() => {
  return leaderboard.value.find((item) => item.is_me) || null;
});

const filteredLeaderboard = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return leaderboard.value;
  return leaderboard.value.filter((item) =>
    (item.name || "").toLowerCase().includes(q)
  );
});

const paginatedLeaderboard = computed(() => {
  return filteredLeaderboard.value.slice(0, visibleCount.value);
});

/* =========================================================
   ACHIEVEMENT / BADGE HELPER
   Lấy trực tiếp từ dữ liệu Badge/Achievement của hệ thống
========================================================= */
const getUserAchievement = (user) => {
  if (user?.badge?.name) {
    return {
      title: user.badge.name,
      icon: user.badge.icon || "🏆",
      isNovice: false,
      badgeClass: getBadgeStyle(user.badge.name),
    };
  }
  return {
    title: "Tập sự",
    icon: "🌱",
    isNovice: true,
    badgeClass: "border-slate-200 bg-slate-100 text-slate-600",
  };
};

const getBadgeStyle = (badgeName = "") => {
  const name = (badgeName || "").toLowerCase();
  if (name.includes("chiến thần") || name.includes("huyền thoại") || name.includes("top 1")) {
    return "border-amber-300 bg-amber-50 text-amber-900";
  }
  if (name.includes("thiên tài") || name.includes("ngôi sao") || name.includes("tích lũy")) {
    return "border-indigo-200 bg-indigo-50 text-indigo-800";
  }
  if (name.includes("on fire") || name.includes("chăm chỉ")) {
    return "border-orange-200 bg-orange-50 text-orange-800";
  }
  if (name.includes("người mới")) {
    return "border-blue-200 bg-blue-50 text-blue-800";
  }
  return "border-purple-200 bg-purple-50 text-purple-800";
};

const getUserAvatarClass = (user) => {
  if (user.rank === 1) return "bg-amber-100 text-amber-900 border border-amber-300";
  if (user.rank === 2) return "bg-slate-200 text-slate-800 border border-slate-300";
  if (user.rank === 3) return "bg-amber-50 text-amber-800 border border-amber-200";
  if (user.is_me) return "bg-[#7C3AED] text-white shadow-xs";
  return "bg-purple-100 text-purple-700";
};

/* =========================================================
   LOAD LEADERBOARD
========================================================= */
onMounted(async () => {
  isLoading.value = true;
  try {
    const currentUser = currentUserStorage.get();
    const data = await gamificationApi.getLeaderboard();

    const list = Array.isArray(data)
      ? data
      : (data?.data && Array.isArray(data.data) ? data.data : []);

    leaderboard.value = list.map((item, index) => ({
      ...item,
      rank: item.rank || index + 1,
      is_me: currentUser && Number(item.user_id) === Number(currentUser.id),
    }));

    await nextTick();
  } catch (error) {
    console.error("Failed to load leaderboard:", error);
    leaderboard.value = [];
  } finally {
    isLoading.value = false;
  }
});
</script>

<style scoped>
@keyframes loading-bar {
  0% {
    transform: translateX(-150%);
  }
  50% {
    transform: translateX(120%);
  }
  100% {
    transform: translateX(350%);
  }
}

.animate-loading-bar {
  animation: loading-bar 1.4s ease-in-out infinite;
}
</style>