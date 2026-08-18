<template>
  <section class="max-w-xl mx-auto py-12 px-4">
    <div class="card p-8 sm:p-10 text-center space-y-6">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Phòng trắc nghiệm</p>
        <h1 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900">Tham gia bằng mã</h1>
        <p class="mt-2 text-xs sm:text-sm text-slate-600">Nhập mã phòng được giáo viên hoặc bạn bè cung cấp để vào thi.</p>
      </div>

      <div class="rounded-2xl border border-purple-100 bg-purple-50/50 p-6 space-y-4">
        <input 
          v-model="roomCode" 
          class="w-full bg-white border border-purple-200 rounded-xl p-4 text-center text-3xl sm:text-4xl font-black tracking-[0.2em] text-slate-900 uppercase outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-purple-200 transition" 
          placeholder="MÃ PHÒNG" 
          maxlength="12" 
          @keyup.enter="joinRoom" 
        />
        <button class="btn-primary w-full py-3 text-sm font-bold" type="button" @click="joinRoom">
          Tham gia làm bài →
        </button>
      </div>

      <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs font-bold text-red-700">
        {{ errorMessage }}
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { normalizeQuizCard, quizzesApi } from '@/services/api'

const router = useRouter()
const roomCode = ref('')
const errorMessage = ref('')

const joinRoom = async () => {
  errorMessage.value = ''
  const code = roomCode.value.trim().toUpperCase()
  
  if (!code) {
    errorMessage.value = 'Bạn chưa nhập mã phòng.'
    return
  }

  if (code.length > 12) {
    errorMessage.value = 'Mã phòng không hợp lệ.'
    return
  }
  
  try {
    const data = await quizzesApi.list({ search: code, visibility: 'group' })
    const found = data.map(normalizeQuizCard).find((room) => room.roomCode?.toUpperCase() === code)
    
    if (!found) {
      errorMessage.value = 'Không tìm thấy phòng tương ứng với mã này.'
      return
    }
    
    router.push(`/quizzes/${found.id}/play`)
  } catch (error) {
    errorMessage.value = `Không tìm được phòng: ${error.message}`
  }
}
</script>
