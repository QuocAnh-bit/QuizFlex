<template>
  <section class="mx-auto max-w-xl py-8 space-y-6">
    <!-- Navigation -->
    <div class="flex items-center justify-between">
      <router-link
        class="btn-secondary text-xs inline-flex items-center gap-1.5"
        to="/live-rooms"
      >
        <ArrowLeft class="h-3.5 w-3.5" />
        Quay lại
      </router-link>

      <router-link
        class="btn-secondary text-xs inline-flex items-center gap-1.5"
        to="/live-rooms/create"
      >
        <Plus class="h-3.5 w-3.5" />
        Tạo phòng mới
      </router-link>
    </div>

    <article class="card p-6 sm:p-10 text-center space-y-6">
      <!-- Header -->
      <div class="space-y-1">
        <span
          class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 border border-purple-200 px-3 py-0.5 text-xs font-bold text-[#7C3AED]"
        >
          <DoorOpen class="h-3.5 w-3.5" />
          Vào phòng thi đấu
        </span>

        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 pt-2">
          Nhập mã PIN phòng
        </h1>

        <p class="text-xs text-slate-600">
          Nhập mã PIN hiển thị trên màn hình của chủ phòng để tham gia ngay.
        </p>
      </div>

      <!-- Join Form -->
      <form
        class="rounded-2xl border border-slate-200 bg-slate-50 p-6 space-y-4"
        @submit.prevent="handleJoin"
      >
        <input
          v-model.trim="code"
          class="w-full bg-transparent text-center font-mono text-3xl sm:text-4xl font-black uppercase tracking-[0.2em] text-slate-900 outline-none placeholder:text-slate-300 placeholder:tracking-normal"
          placeholder="MÃ PIN"
          maxlength="12"
          required
        />

        <button
          class="btn-primary w-full py-2.5 text-xs inline-flex items-center justify-center gap-2"
          type="submit"
          :disabled="isSubmitting"
        >
          <LoaderCircle
            v-if="isSubmitting"
            class="h-4 w-4 animate-spin"
          />

          <LogIn
            v-else
            class="h-4 w-4"
          />

          {{ isSubmitting ? 'Đang tham gia...' : 'Vào phòng thi đấu ngay' }}

          <ArrowRight
            v-if="!isSubmitting"
            class="h-3.5 w-3.5"
          />
        </button>
      </form>

      <!-- Error -->
      <div
        v-if="errorMessage"
        class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700 text-left flex items-start gap-2"
      >
        <CircleAlert class="h-4 w-4 shrink-0 mt-0.5" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Information -->
      <div class="grid gap-3 text-left sm:grid-cols-2 text-xs">
        <div
          class="rounded-xl border border-slate-100 bg-slate-50 p-3 space-y-1"
        >
          <p class="font-bold text-slate-800 flex items-center gap-2">
            <Zap class="h-4 w-4 text-amber-500 shrink-0" />
            Thi đấu thời gian thực
          </p>

          <p class="text-slate-500 leading-relaxed">
            Điểm số và thứ hạng được cập nhật ngay lập tức sau mỗi câu trả lời.
          </p>
        </div>

        <div
          class="rounded-xl border border-slate-100 bg-slate-50 p-3 space-y-1"
        >
          <p class="font-bold text-slate-800 flex items-center gap-2">
            <Timer class="h-4 w-4 text-blue-500 shrink-0" />
            Tốc độ & Độ chính xác
          </p>

          <p class="text-slate-500 leading-relaxed">
            Trả lời nhanh và đúng để nhận điểm thưởng cao hơn.
          </p>
        </div>
      </div>
    </article>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeft,
  ArrowRight,
  CircleAlert,
  DoorOpen,
  LoaderCircle,
  LogIn,
  Plus,
  Timer,
  Zap,
} from 'lucide-vue-next'
import { liveRoomApi } from '@/services/api'

const router = useRouter()
const code = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

const resolveLiveRoomId = (data) =>
  data?.live_room?.id ||
  data?.live_room_id ||
  data?.room?.id ||
  data?.id

const handleJoin = async () => {
  if (isSubmitting.value) return

  const roomCode = String(code.value || '').trim().toUpperCase()

  if (!roomCode) {
    errorMessage.value = 'Vui lòng nhập mã phòng.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''
  code.value = roomCode

  try {
    const data = await liveRoomApi.joinLiveRoom(roomCode)
    const liveRoomId = resolveLiveRoomId(data)

    if (!liveRoomId) {
      throw new Error('Response không có live room id.')
    }

    await router.push({
      name: 'live-room-play',
      params: {
        liveRoomId: String(liveRoomId),
      },
    })
  } catch (error) {
    errorMessage.value =
      error.message || 'Không tham gia được phòng thi đấu.'
  } finally {
    isSubmitting.value = false
  }
}
</script>
