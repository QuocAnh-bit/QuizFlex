<template>
  <section class="mx-auto max-w-xl py-8">
    <article class="card p-6 sm:p-10 text-center space-y-6">
      <!-- Header -->
      <div class="space-y-1">
        <span
          class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200 px-3 py-0.5 text-xs font-bold text-blue-700"
        >
          <DoorOpen class="h-3.5 w-3.5" />
          Phòng bài tập
        </span>

        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 pt-2">
          Tham gia phòng bằng mã
        </h1>

        <p class="text-xs text-slate-600">
          Nhập mã phòng được cung cấp bởi giáo viên hoặc chủ phòng.
        </p>
      </div>

      <!-- Join Form -->
      <form
        class="rounded-2xl border border-slate-200 bg-slate-50 p-6 space-y-4"
        @submit.prevent="joinRoom"
      >
        <input
          v-model.trim="roomCode"
          class="w-full bg-transparent text-center font-mono text-3xl sm:text-4xl font-black uppercase tracking-[0.2em] text-slate-900 outline-none placeholder:text-slate-300 placeholder:tracking-normal"
          maxlength="12"
          placeholder="MÃ PHÒNG"
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
          <LogIn v-else class="h-4 w-4" />

          {{ isSubmitting ? 'Đang tham gia...' : 'Tham gia phòng ngay' }}

          <ArrowRight v-if="!isSubmitting" class="h-3.5 w-3.5" />
        </button>
      </form>

      <!-- Information -->
      <div class="grid gap-3 text-left sm:grid-cols-2 text-xs">
        <div
          class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-1"
        >
          <p class="font-bold text-slate-800 flex items-center gap-2">
            <BookOpen class="h-4 w-4 text-blue-600 shrink-0" />
            Nhận bài tập tự động
          </p>

          <p class="text-slate-500 leading-relaxed">
            Sau khi tham gia, danh sách các bài thi và hạn nộp sẽ hiển thị đầy đủ.
          </p>
        </div>

        <div
          class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-1"
        >
          <p class="font-bold text-slate-800 flex items-center gap-2">
            <ChartNoAxesColumnIncreasing class="h-4 w-4 text-purple-600 shrink-0" />
            Xem điểm & nhận xét
          </p>

          <p class="text-slate-500 leading-relaxed">
            Nhận phản hồi và điểm số từ chủ phòng sau khi nộp bài.
          </p>
        </div>
      </div>

      <!-- Messages -->
      <div
        v-if="successMessage"
        class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700 flex items-center justify-center gap-2"
      >
        <CircleCheck class="h-4 w-4 shrink-0" />
        {{ successMessage }}
      </div>

      <div
        v-if="errorMessage"
        class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700 flex items-center justify-center gap-2"
      >
        <CircleAlert class="h-4 w-4 shrink-0" />
        {{ errorMessage }}
      </div>

      <!-- Back -->
      <div class="pt-2">
        <router-link
          class="btn-ghost text-xs inline-flex items-center gap-1.5"
          to="/homework-rooms"
        >
          <ArrowLeft class="h-3.5 w-3.5" />
          Quay lại danh sách
        </router-link>
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
  BookOpen,
  ChartNoAxesColumnIncreasing,
  CircleAlert,
  CircleCheck,
  DoorOpen,
  LoaderCircle,
  LogIn,
} from 'lucide-vue-next'
import { homeworkApi } from '@/services/api'

const router = useRouter()
const roomCode = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const joinRoom = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  const code = roomCode.value.trim().toUpperCase()

  if (!code) {
    errorMessage.value = 'Bạn cần nhập mã phòng.'
    return
  }

  isSubmitting.value = true

  try {
    await homeworkApi.joinHomeworkRoom(code)
    successMessage.value = 'Tham gia phòng thành công!'
    window.setTimeout(() => router.push('/homework-rooms'), 600)
  } catch (error) {
    errorMessage.value = error.message || 'Không tham gia được phòng.'
  } finally {
    isSubmitting.value = false
  }
}
</script>
