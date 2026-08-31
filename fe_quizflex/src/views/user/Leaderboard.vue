<template>
  <div class="mx-auto max-w-5xl space-y-6 py-4 sm:py-6">
    <!-- =========================================================
         HEADER & HERO BANNER (Tinh tế, gãy gọn, không bị rối)
    ========================================================== -->
    <div
      class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 shadow-xs sm:p-8"
    >
      <!-- Background subtle decorative accents -->
      <div
        class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-purple-500/5 blur-3xl"
      ></div>
      <div
        class="pointer-events-none absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-amber-500/5 blur-3xl"
      ></div>

      <div class="relative z-10 flex flex-col justify-between gap-6 md:flex-row md:items-center">
        <!-- Left: Title & Subtitle -->
        <div class="space-y-1.5">
          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-full border border-purple-200/80 bg-purple-50/80 px-3 py-1 text-xs font-bold text-[#7C3AED]"
            >
              <Sparkles class="h-3.5 w-3.5 text-[#7C3AED]" />
              Bảng vàng thành tích toàn cầu
            </span>

            <span
              class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
              Trực tiếp
            </span>
          </div>

          <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
            Bảng Xếp Hạng QuizFlex
          </h1>

          <p class="text-sm font-medium leading-relaxed text-slate-600">
            Vinh danh những học viên tích cực nhất hệ thống qua cấp độ (Level) và điểm tích lũy (XP).
          </p>
        </div>

        <!-- Right: Vị trí của bạn (Card gãy gọn, phân cấp rõ ràng) -->
        <div
          v-if="myRankInfo"
          class="flex items-center gap-3.5 rounded-2xl border border-slate-200 bg-white p-3.5 sm:p-4 shadow-sm"
        >
          <!-- #Rank Badge -->
          <div
            class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#7C3AED] text-sm font-black text-white shadow-xs"
          >
            #{{ myRankInfo.rank }}
          </div>

          <div class="min-w-0">
            <div class="flex items-center gap-1.5">
              <span class="text-xs font-semibold text-slate-500">Vị trí của bạn</span>
              <span class="rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-bold text-slate-600 border border-slate-200/60">
                Lv.{{ myRankInfo.level }}
              </span>
            </div>
            <p class="truncate text-sm font-bold text-slate-900 mt-0.5">
              {{ myRankInfo.name }}
            </p>
            <div class="mt-1 flex items-center gap-2 text-xs">
              <span class="font-bold text-[#7C3AED] tabular-nums">
                {{ Number(myRankInfo.xp).toLocaleString() }} XP
              </span>
              <span class="text-slate-300">·</span>
              <span
                class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.2 text-[10px] font-medium border"
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
        class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-50 text-[#7C3AED]"
      >
        <Crown class="h-7 w-7 animate-bounce text-[#7C3AED]" :stroke-width="2" />
      </div>

      <h2 class="text-base font-bold text-slate-900">
        Đang tải Bảng tổng sắp...
      </h2>

      <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
        Đang đồng bộ dữ liệu điểm kinh nghiệm (XP) và danh hiệu học viên trên toàn hệ thống.
      </p>

      <div class="mt-6 h-1 w-48 overflow-hidden rounded-full bg-slate-100">
        <div class="h-full w-1/3 rounded-full bg-[#7C3AED] animate-loading-bar"></div>
      </div>
    </div>

    <!-- =========================================================
         LOADED STATE
    ========================================================== -->
    <template v-else>
      <!-- =======================================================
           TOP 3 PODIUM (Mẫu tham khảo trực quan chuẩn thiết kế)
      ======================================================== -->
      <section
        v-if="topThree.length > 0"
        class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs"
      >
        <!-- Header Badge -->
        <div class="mb-4 text-center">
          <span
            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-slate-50 px-4 py-1 text-xs sm:text-sm font-bold text-slate-700 shadow-2xs"
          >
            <Trophy class="h-4 w-4 text-amber-500" />
            Top 3 Xuất Sắc Nhất
          </span>
        </div>

        <!-- 3-Column Stage (Top 2 - Top 1 - Top 3) -->
        <div class="mx-auto grid max-w-3xl grid-cols-3 items-end gap-3 sm:gap-6 pt-3">
          <!-- =====================================================
               TOP 2 (BÊN TRÁI)
          ====================================================== -->
          <div v-if="topThree[1]" class="flex flex-col text-center">
            <!-- Profile Upper Section -->
            <div class="flex flex-col items-center">
              <!-- Medal Icon -->
              <span class="mb-1 text-xl sm:text-2xl">🥈</span>

              <!-- Rank Number Circle -->
              <div
                class="mb-2 grid h-7 w-7 sm:h-8 sm:w-8 place-items-center rounded-full bg-slate-400 text-xs sm:text-sm font-bold text-white shadow-xs"
              >
                2
              </div>

              <!-- Avatar -->
              <div
                class="mb-2 grid h-16 w-16 sm:h-20 sm:w-20 place-items-center rounded-full bg-slate-200 text-xl sm:text-2xl font-bold text-slate-800 shadow-inner"
              >
                {{ topThree[1].name?.charAt(0).toUpperCase() || "-" }}
              </div>

              <!-- Name & Level -->
              <h3
                class="w-full truncate text-sm sm:text-base font-bold text-slate-900"
                :title="topThree[1].name"
              >
                {{ topThree[1].name }}
                <span v-if="topThree[1].is_me" class="text-xs font-bold text-[#7C3AED]">(Bạn)</span>
              </h3>
              <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Lv.{{ topThree[1].level }}</p>

              <!-- XP Chip -->
              <div
                class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3.5 sm:px-4 py-1 text-xs sm:text-sm font-bold text-slate-700 tabular-nums"
              >
                <Zap class="h-3.5 w-3.5 fill-slate-400 text-slate-500" />
                <span>{{ Number(topThree[1].xp).toLocaleString() }} XP</span>
              </div>
            </div>

            <!-- Pedestal Base 2 -->
            <div
              class="mt-4 flex min-h-[90px] sm:min-h-[105px] flex-col items-center justify-center rounded-2xl border border-slate-200/80 bg-[#F8FAFC] p-3.5 sm:p-4 text-center shadow-2xs"
            >
              <div
                class="grid h-7 w-7 place-items-center rounded-full bg-slate-200 text-xs font-bold text-slate-700"
              >
                🥈
              </div>
              <span class="mt-1.5 text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-700">
                HẠNG 2
              </span>
            </div>
          </div>

          <!-- =====================================================
               TOP 1 (VỊ TRÍ TRUNG TÂM - QUÁN QUÂN)
          ====================================================== -->
          <div v-if="topThree[0]" class="flex flex-col text-center">
            <!-- Highlight Card for Top 1 -->
            <div
              class="flex flex-col items-center rounded-2xl border border-amber-200 bg-gradient-to-b from-[#FEF3C7]/70 via-[#FFFBEB] to-[#FFF7E6]/50 p-4 sm:p-5 shadow-xs"
            >
              <!-- Crown -->
              <div class="mb-1 text-2xl sm:text-3xl animate-bounce">
                👑
              </div>

              <!-- Rank Number Circle -->
              <div
                class="mb-2 grid h-8 w-8 sm:h-9 sm:w-9 place-items-center rounded-full bg-[#F59E0B] text-sm sm:text-base font-black text-white shadow-xs"
              >
                1
              </div>

              <!-- Avatar -->
              <div
                class="mb-2 grid h-20 w-20 sm:h-24 sm:w-24 place-items-center rounded-full border-2 border-amber-300 bg-[#FEF3C7] text-2xl sm:text-3xl font-black text-amber-950 shadow-sm"
              >
                {{ topThree[0].name?.charAt(0).toUpperCase() || "-" }}
              </div>

              <!-- Name & Level -->
              <h2
                class="w-full truncate text-base sm:text-lg font-black text-slate-900"
                :title="topThree[0].name"
              >
                {{ topThree[0].name }}
                <span v-if="topThree[0].is_me" class="text-xs font-bold text-[#7C3AED]">(Bạn)</span>
              </h2>
              <p class="text-xs sm:text-sm font-semibold text-amber-900 mt-0.5">Lv.{{ topThree[0].level }}</p>

              <!-- XP Chip -->
              <div
                class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-200/90 px-4 sm:px-5 py-1 text-xs sm:text-sm font-black text-amber-950 tabular-nums shadow-2xs"
              >
                <Zap class="h-3.5 w-3.5 fill-amber-600 text-amber-700" />
                <span>{{ Number(topThree[0].xp).toLocaleString() }} XP</span>
              </div>
            </div>

            <!-- Pedestal Base 1 -->
            <div
              class="mt-4 flex min-h-[105px] sm:min-h-[120px] flex-col items-center justify-center rounded-2xl border border-amber-300/90 bg-gradient-to-b from-[#FEF3C7] to-[#FDE68A]/60 p-4 text-center shadow-xs"
            >
              <div
                class="grid h-8 w-8 place-items-center rounded-full bg-amber-200 text-sm font-bold text-amber-950"
              >
                🥇
              </div>
              <span class="mt-1.5 text-xs sm:text-sm font-black uppercase tracking-widest text-amber-950">
                QUÁN QUÂN
              </span>
            </div>
          </div>

          <!-- =====================================================
               TOP 3 (BÊN PHẢI)
          ====================================================== -->
          <div v-if="topThree[2]" class="flex flex-col text-center">
            <!-- Profile Upper Section -->
            <div class="flex flex-col items-center">
              <!-- Medal Icon -->
              <span class="mb-1 text-xl sm:text-2xl">🥉</span>

              <!-- Rank Number Circle -->
              <div
                class="mb-2 grid h-7 w-7 sm:h-8 sm:w-8 place-items-center rounded-full bg-[#F97316] text-xs sm:text-sm font-bold text-white shadow-xs"
              >
                3
              </div>

              <!-- Avatar -->
              <div
                class="mb-2 grid h-16 w-16 sm:h-20 sm:w-20 place-items-center rounded-full bg-[#FFEDD5] text-xl sm:text-2xl font-bold text-orange-950 shadow-inner"
              >
                {{ topThree[2].name?.charAt(0).toUpperCase() || "-" }}
              </div>

              <!-- Name & Level -->
              <h3
                class="w-full truncate text-sm sm:text-base font-bold text-slate-900"
                :title="topThree[2].name"
              >
                {{ topThree[2].name }}
                <span v-if="topThree[2].is_me" class="text-xs font-bold text-[#7C3AED]">(Bạn)</span>
              </h3>
              <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Lv.{{ topThree[2].level }}</p>

              <!-- XP Chip -->
              <div
                class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-orange-200/70 bg-[#FEF3E7] px-3.5 sm:px-4 py-1 text-xs sm:text-sm font-bold text-orange-900 tabular-nums"
              >
                <Zap class="h-3.5 w-3.5 fill-orange-500 text-orange-600" />
                <span>{{ Number(topThree[2].xp).toLocaleString() }} XP</span>
              </div>
            </div>

            <!-- Pedestal Base 3 -->
            <div
              class="mt-4 flex min-h-[90px] sm:min-h-[105px] flex-col items-center justify-center rounded-2xl border border-orange-200/80 bg-[#FEF3E7]/70 p-3.5 sm:p-4 text-center shadow-2xs"
            >
              <div
                class="grid h-7 w-7 place-items-center rounded-full bg-orange-200 text-xs font-bold text-orange-900"
              >
                🥉
              </div>
              <span class="mt-1.5 text-xs sm:text-sm font-bold uppercase tracking-wider text-orange-900">
                HẠNG 3
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- =======================================================
           MAIN LEADERBOARD TABLE CARD
      ======================================================== -->
      <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xs">
        <!-- Toolbar & Filter Tabs -->
        <div
          class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6 bg-slate-50/40"
        >
          <!-- Left: Filter Tabs -->
          <div class="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl border border-slate-200/60">
            <button
              type="button"
              class="px-4 py-2 rounded-xl text-xs sm:text-sm transition cursor-pointer"
              :class="activeFilter === 'all' ? 'bg-white text-slate-900 font-bold shadow-2xs border border-slate-200/50' : 'text-slate-500 hover:text-slate-800 font-medium'"
              @click="activeFilter = 'all'"
            >
              Tất cả ({{ filteredLeaderboard.length }})
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-xl text-xs sm:text-sm transition cursor-pointer"
              :class="activeFilter === 'top10' ? 'bg-white text-slate-900 font-bold shadow-2xs border border-slate-200/50' : 'text-slate-500 hover:text-slate-800 font-medium'"
              @click="activeFilter = 'top10'"
            >
              Top 10
            </button>
            <button
              v-if="myRankInfo"
              type="button"
              class="px-4 py-2 rounded-xl text-xs sm:text-sm transition cursor-pointer"
              :class="activeFilter === 'near_me' ? 'bg-white text-[#7C3AED] font-bold shadow-2xs border border-purple-100' : 'text-slate-500 hover:text-[#7C3AED] font-medium'"
              @click="activeFilter = 'near_me'"
            >
              Gần vị trí của tôi
            </button>
          </div>

          <!-- Right: Search input -->
          <div class="relative min-w-[220px] sm:max-w-sm">
            <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Tìm kiếm học viên theo tên..."
              class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-4 text-xs sm:text-sm font-medium text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-1 focus:ring-purple-500/20"
            />
          </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50/70 text-xs font-bold uppercase tracking-wider text-slate-500">
                <th class="w-20 px-6 py-4 text-center">Hạng</th>
                <th class="px-6 py-4">Học viên</th>
                <th class="hidden px-6 py-4 md:table-cell">Danh hiệu</th>
                <th class="w-40 px-6 py-4 text-right">Điểm XP</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
              <tr
                v-for="(user, i) in displayedList"
                :key="user.user_id ?? i"
                class="transition hover:bg-slate-50/70"
                :class="user.is_me ? 'bg-purple-50/40 border-l-[4px] border-l-[#7C3AED] font-semibold text-slate-900' : ''"
              >
                <!-- RANK COLUMN -->
                <td class="px-6 py-4 text-center font-bold">
                  <div class="flex items-center justify-center">
                    <!-- #1 -->
                    <span
                      v-if="user.rank === 1"
                      class="grid h-7 w-7 sm:h-8 sm:w-8 place-items-center rounded-lg bg-amber-100 text-sm font-black text-amber-800"
                    >
                      🥇
                    </span>
                    <!-- #2 -->
                    <span
                      v-else-if="user.rank === 2"
                      class="grid h-7 w-7 sm:h-8 sm:w-8 place-items-center rounded-lg bg-slate-200 text-sm font-black text-slate-700"
                    >
                      🥈
                    </span>
                    <!-- #3 -->
                    <span
                      v-else-if="user.rank === 3"
                      class="grid h-7 w-7 sm:h-8 sm:w-8 place-items-center rounded-lg bg-amber-50 text-sm font-black text-amber-900 border border-amber-200"
                    >
                      🥉
                    </span>
                    <!-- Top 4 - 10 -->
                    <span
                      v-else-if="user.rank <= 10"
                      class="grid h-7 w-7 sm:h-8 sm:w-8 place-items-center rounded-lg bg-slate-100 text-xs sm:text-sm font-bold text-slate-700"
                    >
                      #{{ user.rank }}
                    </span>
                    <!-- Rest -->
                    <span v-else class="text-slate-400 font-semibold text-xs sm:text-sm">
                      #{{ user.rank }}
                    </span>
                  </div>
                </td>

                <!-- USER COLUMN: Avatar, Name, Level, Subtitle -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3.5">
                    <!-- Avatar with initials -->
                    <div
                      class="grid h-10 w-10 sm:h-11 sm:w-11 shrink-0 place-items-center rounded-xl text-sm sm:text-base font-bold transition"
                      :class="getUserAvatarClass(user)"
                    >
                      {{ user.name?.charAt(0).toUpperCase() || "-" }}
                    </div>

                    <!-- Information -->
                    <div class="min-w-0">
                      <div class="flex items-center gap-2 truncate font-bold text-slate-900 text-sm sm:text-base">
                        <span class="truncate">{{ user.name }}</span>
                        <!-- Subtle "Bạn" badge chip -->
                        <span
                          v-if="user.is_me"
                          class="inline-flex items-center rounded-md bg-purple-100 text-[#7C3AED] border border-purple-200 px-2 py-0.5 text-[11px] font-bold"
                        >
                          Bạn
                        </span>
                      </div>

                      <!-- Subtitle: Level & Mobile Title -->
                      <div class="flex items-center gap-1.5 text-xs text-slate-500 font-normal mt-0.5">
                        <span class="font-medium text-slate-600">Lv.{{ user.level }}</span>
                        <span class="md:hidden text-slate-300">·</span>
                        <span class="md:hidden font-medium text-slate-600">
                          {{ getUserAchievement(user).icon }} {{ getUserAchievement(user).title }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- TIER / ACHIEVEMENT BADGE COLUMN (Compact chip) -->
                <td class="hidden px-6 py-4 md:table-cell">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium border"
                    :class="getUserAchievement(user).badgeClass"
                  >
                    <span class="text-xs">{{ getUserAchievement(user).icon }}</span>
                    <span>{{ getUserAchievement(user).title }}</span>
                  </span>
                </td>

                <!-- XP COLUMN -->
                <td class="px-6 py-4 text-right">
                  <div class="inline-flex items-center justify-end gap-1.5 font-bold text-slate-800 text-sm sm:text-base tabular-nums">
                    <span>{{ Number(user.xp).toLocaleString() }}</span>
                    <span class="text-xs font-semibold text-slate-400">XP</span>
                  </div>
                </td>
              </tr>

              <!-- EMPTY STATE -->
              <tr v-if="displayedList.length === 0">
                <td colspan="4" class="px-6 py-12 text-center">
                  <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-slate-100">
                    <Trophy class="h-6 w-6 text-slate-400" />
                  </div>
                  <p class="font-bold text-slate-700 text-sm">Không tìm thấy học viên nào</p>
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
          v-if="activeFilter === 'all' && filteredLeaderboard.length > visibleCount"
          class="border-t border-slate-100 p-4 text-center bg-slate-50/40"
        >
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs sm:text-sm font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer shadow-2xs"
            @click="visibleCount += 15"
          >
            <span>Xem thêm danh sách xếp hạng (hiển thị {{ visibleCount }}/{{ filteredLeaderboard.length }})</span>
            <ChevronDown class="h-4 w-4 text-slate-400" />
          </button>
        </div>
      </article>

      <!-- =======================================================
           XP EARNING GUIDE (BOTTOM COMPACT STRIP)
      ======================================================== -->
      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3.5">
          <span class="grid h-7 w-7 place-items-center rounded-lg bg-amber-50 text-amber-600">
            <Zap class="h-4 w-4 fill-current" />
          </span>
          <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800">
            Quy tắc Tích Lũy Điểm Kinh Nghiệm (XP)
          </h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 text-xs sm:text-sm">
          <div class="flex items-start gap-3 rounded-2xl bg-slate-50/70 border border-slate-200/60 p-3.5 sm:p-4">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-purple-100/80 text-xs font-bold text-purple-700">
              +10
            </span>
            <div>
              <b class="text-slate-900 block font-bold text-xs sm:text-sm">Hoàn thành Quiz</b>
              <span class="text-slate-500 text-xs leading-relaxed">Nhận 10 XP sau mỗi bài thi hoặc bài ôn luyện hoàn thành.</span>
            </div>
          </div>

          <div class="flex items-start gap-3 rounded-2xl bg-slate-50/70 border border-slate-200/60 p-3.5 sm:p-4">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-amber-100/80 text-xs font-bold text-amber-800">
              +20
            </span>
            <div>
              <b class="text-slate-900 block font-bold text-xs sm:text-sm">Đấu trường Live Room</b>
              <span class="text-slate-500 text-xs leading-relaxed">Thi đấu thời gian thực và giành thứ hạng cao trong phòng.</span>
            </div>
          </div>

          <div class="flex items-start gap-3 rounded-2xl bg-slate-50/70 border border-slate-200/60 p-3.5 sm:p-4">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-emerald-100/80 text-xs font-bold text-emerald-800">
              +5
            </span>
            <div>
              <b class="text-slate-900 block font-bold text-xs sm:text-sm">Duy trì Streak</b>
              <span class="text-slate-500 text-xs leading-relaxed">Học tập và làm quiz đều đặn mỗi ngày để nhận điểm thưởng chuỗi.</span>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from "vue";
import {
  ChevronDown,
  Crown,
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
const activeFilter = ref("all"); // 'all' | 'top10' | 'near_me'

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

const displayedList = computed(() => {
  let list = filteredLeaderboard.value;

  if (activeFilter.value === "top10") {
    return list.slice(0, 10);
  }

  if (activeFilter.value === "near_me" && myRankInfo.value) {
    const myIndex = list.findIndex((item) => item.is_me);
    if (myIndex !== -1) {
      const start = Math.max(0, myIndex - 4);
      const end = Math.min(list.length, myIndex + 5);
      return list.slice(start, end);
    }
  }

  return list.slice(0, visibleCount.value);
});

/* =========================================================
   ACHIEVEMENT / BADGE HELPER
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
    badgeClass: "border-slate-200/80 bg-slate-50 text-slate-600",
  };
};

const getBadgeStyle = (badgeName = "") => {
  const name = (badgeName || "").toLowerCase();
  if (name.includes("chiến thần") || name.includes("huyền thoại") || name.includes("top 1")) {
    return "border-amber-200/80 bg-amber-50/70 text-amber-800";
  }
  if (name.includes("thiên tài") || name.includes("ngôi sao") || name.includes("tích lũy")) {
    return "border-indigo-200/70 bg-indigo-50/60 text-indigo-700";
  }
  if (name.includes("on fire") || name.includes("chăm chỉ")) {
    return "border-orange-200/70 bg-orange-50/60 text-orange-700";
  }
  if (name.includes("người mới")) {
    return "border-blue-200/70 bg-blue-50/60 text-blue-700";
  }
  return "border-purple-200/70 bg-purple-50/60 text-purple-700";
};

const getUserAvatarClass = (user) => {
  if (user.rank === 1) return "bg-amber-100 text-amber-900 border border-amber-300";
  if (user.rank === 2) return "bg-slate-200 text-slate-800 border border-slate-300";
  if (user.rank === 3) return "bg-amber-50 text-amber-800 border border-amber-200";
  if (user.is_me) return "bg-[#7C3AED] text-white shadow-2xs";
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