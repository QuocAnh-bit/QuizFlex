<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Nhóm học tập</p>
        <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Phòng Quiz Nhóm</h1>
        <p class="mt-1 text-sm text-slate-600">Danh sách các bộ đề được cấu hình chế độ mã phòng (Group Code).</p>
      </div>
      <router-link class="btn-primary text-xs px-3.5 py-1.5" :to="`${questionBase}/create?visibility=group`">
        + Tạo Group Quiz
      </router-link>
    </div>

    <div v-if="isLoading" class="card p-10 text-center text-xs text-slate-400">Đang tải danh sách phòng...</div>
    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">{{ errorMessage }}</div>

    <div v-if="!isLoading && rooms.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="room in rooms"
        :key="room.id"
        :to="`/quizzes/${room.id}`"
        class="card card-hover p-5 space-y-3 block"
      >
        <div class="flex items-center justify-between gap-2">
          <b class="text-sm font-bold text-slate-900 line-clamp-1 hover:text-[#7C3AED] transition">{{ room.title }}</b>
          <span class="rounded bg-purple-50 text-[#7C3AED] px-2 py-0.5 text-xs font-bold font-mono shrink-0">{{ room.roomCode }}</span>
        </div>
        <p class="text-xs text-slate-500">{{ room.questions }} câu hỏi • {{ room.attempts }} lượt làm • {{ room.duration }}</p>
      </router-link>
    </div>

    <div v-if="!isLoading && rooms.length === 0" class="card p-10 text-center text-xs text-slate-400">
      Chưa có group quiz nào được tạo.
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { normalizeQuizCard, quizzesApi } from '@/services/api'

const route = useRoute()
const questionBase = computed(() => route.path.startsWith('/dashboard') ? '/dashboard/questions' : '/admin/questions')
const rooms = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const loadRooms = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const data = await quizzesApi.list({ visibility: 'group', per_page: 100 })
    rooms.value = data.map(normalizeQuizCard)
  } catch (error) {
    errorMessage.value = `Không tải được danh sách: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadRooms)
</script>
