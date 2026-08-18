<template>
  <div class="py-4 space-y-8 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
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
          class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-xs font-bold text-amber-800"
        >
          <Trophy class="h-3.5 w-3.5" />
          Cập nhật trực tiếp
        </span>
      </div>
    </div>

    <!-- LOADING STATE -->
    <AppLoadingState
      v-if="isLoading"
      title="Đang tải Bảng xếp hạng..."
      message="Vui lòng chờ trong giây lát để hệ thống tải bảng tổng sắp điểm kinh nghiệm (XP)."
      :icon="Crown"
    />

    <!-- LOADED STATE -->
    <template v-else>
      <!-- Top 3 Podium -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end pt-4">

        <!-- Rank 2: Silver -->
        <div
          class="card p-6 text-center order-2 md:order-1 border-t-4 border-t-slate-400"
        >
          <div
            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-lg font-black text-slate-700 mb-2"
          >
            2
          </div>

          <div
            class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-200 text-xl font-black text-slate-800 mb-3"
          >
            {{ leaderboard[1]?.name?.charAt(0).toUpperCase() || '-' }}
          </div>

          <h3 class="text-base font-bold text-slate-900 truncate">
            {{ leaderboard[1]?.name || 'Chưa có' }}
          </h3>

          <span
            class="text-xs text-slate-500 block mt-0.5"
            v-if="leaderboard[1]"
          >
            Level {{ leaderboard[1]?.level }}
          </span>

          <div
            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"
            v-if="leaderboard[1]"
          >
            <Zap class="h-3.5 w-3.5" />
            {{ leaderboard[1]?.xp }} XP
          </div>
        </div>

        <!-- Rank 1: Gold -->
        <div
          class="card p-6 sm:p-8 text-center order-1 md:order-2 border-t-4 border-t-amber-500 shadow-md md:-translate-y-2"
        >
          <div class="flex justify-center mb-1">
            <Crown class="h-8 w-8 text-amber-500" />
          </div>

          <div
            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-lg font-black text-amber-800 mb-2"
          >
            1
          </div>

          <div
            class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-amber-200 text-2xl font-black text-amber-950 mb-3 border-2 border-amber-400"
          >
            {{ leaderboard[0]?.name?.charAt(0).toUpperCase() || '-' }}
          </div>

          <h2 class="text-lg font-black text-slate-900 truncate">
            {{ leaderboard[0]?.name || 'Chưa có' }}

            <span
              v-if="leaderboard[0]?.is_me"
              class="text-xs text-[#7C3AED] font-bold"
            >
              (Bạn)
            </span>
          </h2>

          <span
            class="text-xs text-slate-500 block mt-0.5"
            v-if="leaderboard[0]"
          >
            Level {{ leaderboard[0]?.level }}
          </span>

          <div
            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-4 py-1 text-sm font-black text-amber-800"
            v-if="leaderboard[0]"
          >
            <Trophy class="h-4 w-4" />
            {{ leaderboard[0]?.xp }} XP
          </div>
        </div>

        <!-- Rank 3: Bronze -->
        <div
          class="card p-6 text-center order-3 border-t-4 border-t-amber-700"
        >
          <div
            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-lg font-black text-amber-900 mb-2"
          >
            3
          </div>

          <div
            class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-xl font-black text-amber-900 mb-3"
          >
            {{ leaderboard[2]?.name?.charAt(0).toUpperCase() || '-' }}
          </div>

          <h3 class="text-base font-bold text-slate-900 truncate">
            {{ leaderboard[2]?.name || 'Chưa có' }}
          </h3>

          <span
            class="text-xs text-slate-500 block mt-0.5"
            v-if="leaderboard[2]"
          >
            Level {{ leaderboard[2]?.level }}
          </span>

          <div
            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"
            v-if="leaderboard[2]"
          >
            <Zap class="h-3.5 w-3.5" />
            {{ leaderboard[2]?.xp }} XP
          </div>
        </div>
      </div>

      <!-- Leaderboard Table -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr
                class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500"
              >
                <th class="py-3.5 px-5 text-center w-16">
                  Hạng
                </th>

                <th class="py-3.5 px-5">
                  Học viên
                </th>

                <th class="py-3.5 px-5 text-right w-36">
                  Điểm XP
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 text-xs">
              <tr
                v-for="(user, i) in leaderboard.slice(0, visibleCount)"
                :key="i"
                class="transition hover:bg-slate-50"
                :class="user.is_me ? 'bg-purple-50/70 font-semibold' : ''"
              >
                <!-- Rank -->
                <td class="py-3.5 px-5 text-center font-bold">
                  <Trophy
                    v-if="i === 0"
                    class="mx-auto h-5 w-5 text-amber-500"
                  />

                  <Medal
                    v-else-if="i === 1"
                    class="mx-auto h-5 w-5 text-slate-400"
                  />

                  <Medal
                    v-else-if="i === 2"
                    class="mx-auto h-5 w-5 text-amber-700"
                  />

                  <span
                    v-else
                    class="text-slate-500"
                  >
                    #{{ i + 1 }}
                  </span>
                </td>

                <!-- User -->
                <td class="py-3.5 px-5">
                  <div class="flex items-center gap-3">
                    <div
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-[#7C3AED]"
                    >
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>

                    <div>
                      <div class="font-bold text-slate-900">
                        {{ user.name }}

                        <span
                          v-if="user.is_me"
                          class="ml-1 text-[11px] text-[#7C3AED] font-bold"
                        >
                          (Bạn)
                        </span>
                      </div>

                      <div class="text-[11px] text-slate-500 font-normal">
                        Level {{ user.level }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- XP -->
                <td
                  class="py-3.5 px-5 text-right font-black text-[#7C3AED] text-sm"
                >
                  <span class="inline-flex items-center justify-end gap-1">
                    <Zap class="h-4 w-4" />
                    {{ user.xp }} XP
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Load More -->
        <div
          v-if="leaderboard.length > visibleCount"
          class="border-t border-slate-100 p-3 text-center"
        >
          <button
            class="btn-ghost text-xs w-full inline-flex items-center justify-center gap-1.5"
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
} from "@lucide/vue";

import AppLoadingState from "@/components/common/AppLoadingState.vue";
import {
  gamificationApi,
  currentUserStorage,
} from "@/services/api";

const leaderboard = ref([]);
const visibleCount = ref(10);
const isLoading = ref(true);

onMounted(async () => {
  isLoading.value = true;

  try {
    const currentUser = currentUserStorage.get();
    const data = await gamificationApi.getLeaderboard();
    const list = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : []);

    leaderboard.value = list.map((item) => ({
      ...item,
      is_me:
        currentUser &&
        Number(item.user_id) === Number(currentUser.id),
    }));

    await nextTick();
  } catch (error) {
    console.error("Failed to load leaderboard:", error);
  } finally {
    isLoading.value = false;
  }
});
</script>
