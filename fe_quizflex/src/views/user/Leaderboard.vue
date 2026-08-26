<template>
  <div class="py-4 space-y-8 max-w-5xl mx-auto">
    <!-- =========================================================
         HEADER
    ========================================================== -->
    <div
      class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
    >
      <div>
        <p
          class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]"
        >
          Bảng vàng thành tích
        </p>

        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">
          Bảng xếp hạng QuizFlex
        </h1>

        <p class="mt-1 text-sm text-slate-600">
          Vinh danh các học viên có điểm kinh nghiệm (XP) cao nhất toàn hệ thống.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <span
          class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800"
        >
          <Trophy class="h-3.5 w-3.5" />
          Cập nhật trực tiếp
        </span>
      </div>
    </div>

    <!-- =========================================================
         LOADING STATE
         Loading riêng của trang - KHÔNG dùng AppLoadingState
    ========================================================== -->
    <div
      v-if="isLoading"
      class="card flex min-h-[360px] flex-col items-center justify-center px-6 py-10 text-center"
    >
      <!-- Crown -->
      <div
        class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-50"
      >
        <Crown
          class="h-8 w-8 text-[#7C3AED] animate-pulse"
          :stroke-width="2"
        />
      </div>

      <!-- Title -->
      <h2 class="text-xl font-black text-slate-900">
        Đang tải Bảng xếp hạng...
      </h2>

      <!-- Message -->
      <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">
        Vui lòng chờ trong giây lát để hệ thống tải bảng tổng sắp điểm
        kinh nghiệm (XP).
      </p>

      <!-- Loading bar -->
      <div
        class="mt-6 h-1.5 w-64 overflow-hidden rounded-full bg-slate-100"
      >
        <div
          class="h-full w-1/3 rounded-full bg-[#7C3AED] animate-loading-bar"
        ></div>
      </div>
    </div>

    <!-- =========================================================
         LOADED STATE
    ========================================================== -->
    <template v-else>
      <!-- =======================================================
           TOP 3 PODIUM
      ======================================================== -->
      <div
        class="grid grid-cols-1 items-end gap-4 pt-4 md:grid-cols-3"
      >
        <!-- =====================================================
             RANK 2 - SILVER
        ====================================================== -->
        <div
          class="card order-2 border-t-4 border-t-slate-400 p-6 text-center md:order-1"
        >
          <!-- Rank -->
          <div
            class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-lg font-black text-slate-700"
          >
            2
          </div>

          <!-- Avatar -->
          <div
            class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-200 text-xl font-black text-slate-800"
          >
            {{
              leaderboard[1]?.name?.charAt(0).toUpperCase() || "-"
            }}
          </div>

          <!-- Name -->
          <h3 class="truncate text-base font-bold text-slate-900">
            {{ leaderboard[1]?.name || "Chưa có" }}
          </h3>

          <!-- Level -->
          <span
            v-if="leaderboard[1]"
            class="mt-0.5 block text-xs text-slate-500"
          >
            Level {{ leaderboard[1]?.level }}
          </span>

          <!-- XP -->
          <div
            v-if="leaderboard[1]"
            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"
          >
            <Zap class="h-3.5 w-3.5" />
            {{ leaderboard[1]?.xp }} XP
          </div>
        </div>

        <!-- =====================================================
             RANK 1 - GOLD
        ====================================================== -->
        <div
          class="card order-1 border-t-4 border-t-amber-500 p-6 text-center shadow-md md:order-2 md:-translate-y-2 sm:p-8"
        >
          <!-- Crown -->
          <div class="mb-1 flex justify-center">
            <Crown class="h-8 w-8 text-amber-500" />
          </div>

          <!-- Rank -->
          <div
            class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-lg font-black text-amber-800"
          >
            1
          </div>

          <!-- Avatar -->
          <div
            class="mx-auto mb-3 flex h-20 w-20 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-200 text-2xl font-black text-amber-950"
          >
            {{
              leaderboard[0]?.name?.charAt(0).toUpperCase() || "-"
            }}
          </div>

          <!-- Name -->
          <h2 class="truncate text-lg font-black text-slate-900">
            {{ leaderboard[0]?.name || "Chưa có" }}

            <span
              v-if="leaderboard[0]?.is_me"
              class="text-xs font-bold text-[#7C3AED]"
            >
              (Bạn)
            </span>
          </h2>

          <!-- Level -->
          <span
            v-if="leaderboard[0]"
            class="mt-0.5 block text-xs text-slate-500"
          >
            Level {{ leaderboard[0]?.level }}
          </span>

          <!-- XP -->
          <div
            v-if="leaderboard[0]"
            class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-4 py-1 text-sm font-black text-amber-800"
          >
            <Trophy class="h-4 w-4" />
            {{ leaderboard[0]?.xp }} XP
          </div>
        </div>

        <!-- =====================================================
             RANK 3 - BRONZE
        ====================================================== -->
        <div
          class="card order-3 border-t-4 border-t-amber-700 p-6 text-center"
        >
          <!-- Rank -->
          <div
            class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-lg font-black text-amber-900"
          >
            3
          </div>

          <!-- Avatar -->
          <div
            class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-xl font-black text-amber-900"
          >
            {{
              leaderboard[2]?.name?.charAt(0).toUpperCase() || "-"
            }}
          </div>

          <!-- Name -->
          <h3 class="truncate text-base font-bold text-slate-900">
            {{ leaderboard[2]?.name || "Chưa có" }}
          </h3>

          <!-- Level -->
          <span
            v-if="leaderboard[2]"
            class="mt-0.5 block text-xs text-slate-500"
          >
            Level {{ leaderboard[2]?.level }}
          </span>

          <!-- XP -->
          <div
            v-if="leaderboard[2]"
            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"
          >
            <Zap class="h-3.5 w-3.5" />
            {{ leaderboard[2]?.xp }} XP
          </div>
        </div>
      </div>

      <!-- =======================================================
           LEADERBOARD TABLE
      ======================================================== -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <!-- TABLE HEADER -->
            <thead>
              <tr
                class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500"
              >
                <th class="w-16 px-5 py-3.5 text-center">
                  Hạng
                </th>

                <th class="px-5 py-3.5">
                  Học viên
                </th>

                <th class="w-36 px-5 py-3.5 text-right">
                  Điểm XP
                </th>
              </tr>
            </thead>

            <!-- TABLE BODY -->
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr
                v-for="(user, i) in leaderboard.slice(0, visibleCount)"
                :key="user.user_id ?? i"
                class="transition hover:bg-slate-50"
                :class="
                  user.is_me
                    ? 'bg-purple-50/70 font-semibold'
                    : ''
                "
              >
                <!-- =================================================
                     RANK
                ================================================== -->
                <td class="px-5 py-3.5 text-center font-bold">
                  <!-- #1 -->
                  <Trophy
                    v-if="i === 0"
                    class="mx-auto h-5 w-5 text-amber-500"
                  />

                  <!-- #2 -->
                  <Medal
                    v-else-if="i === 1"
                    class="mx-auto h-5 w-5 text-slate-400"
                  />

                  <!-- #3 -->
                  <Medal
                    v-else-if="i === 2"
                    class="mx-auto h-5 w-5 text-amber-700"
                  />

                  <!-- #4 trở đi -->
                  <span
                    v-else
                    class="text-slate-500"
                  >
                    #{{ i + 1 }}
                  </span>
                </td>

                <!-- =================================================
                     USER
                ================================================== -->
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-3">
                    <!-- Avatar -->
                    <div
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-[#7C3AED]"
                    >
                      {{
                        user.name?.charAt(0).toUpperCase() || "-"
                      }}
                    </div>

                    <!-- Information -->
                    <div class="min-w-0">
                      <div class="truncate font-bold text-slate-900">
                        {{ user.name }}

                        <span
                          v-if="user.is_me"
                          class="ml-1 text-[11px] font-bold text-[#7C3AED]"
                        >
                          (Bạn)
                        </span>
                      </div>

                      <div
                        class="text-[11px] font-normal text-slate-500"
                      >
                        Level {{ user.level }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- =================================================
                     XP
                ================================================== -->
                <td
                  class="px-5 py-3.5 text-right text-sm font-black text-[#7C3AED]"
                >
                  <span
                    class="inline-flex items-center justify-end gap-1"
                  >
                    <Zap class="h-4 w-4" />
                    {{ user.xp }} XP
                  </span>
                </td>
              </tr>

              <!-- EMPTY -->
              <tr v-if="leaderboard.length === 0">
                <td
                  colspan="3"
                  class="px-5 py-12 text-center"
                >
                  <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100"
                  >
                    <Trophy
                      class="h-6 w-6 text-slate-400"
                    />
                  </div>

                  <p class="font-bold text-slate-700">
                    Chưa có dữ liệu xếp hạng
                  </p>

                  <p class="mt-1 text-xs text-slate-500">
                    Hãy hoàn thành quiz để bắt đầu tích lũy XP.
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- =======================================================
             LOAD MORE
        ======================================================== -->
        <div
          v-if="leaderboard.length > visibleCount"
          class="border-t border-slate-100 p-3 text-center"
        >
          <button
            class="btn-ghost inline-flex w-full items-center justify-center gap-1.5 text-xs"
            @click="visibleCount += 10"
          >
            Xem thêm danh sách xếp hạng

            <ChevronDown class="h-4 w-4" />
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue";

import {
  Trophy,
  Crown,
  Zap,
  Medal,
  ChevronDown,
} from "lucide-vue-next";

import {
  gamificationApi,
  currentUserStorage,
} from "@/services/api";

/* =========================================================
   STATE
========================================================= */

const leaderboard = ref([]);
const visibleCount = ref(10);
const isLoading = ref(true);

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
      : (
          data?.data &&
          Array.isArray(data.data)
            ? data.data
            : []
        );

    leaderboard.value = list.map((item) => ({
      ...item,

      is_me:
        currentUser &&
        Number(item.user_id) === Number(currentUser.id),
    }));

    await nextTick();
  } catch (error) {
    console.error(
      "Failed to load leaderboard:",
      error
    );

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