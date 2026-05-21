<template>
  <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-28 -top-28 h-80 w-80 rounded-full bg-[var(--accent-2)]/20 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">AI Generator</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tạo dàn ý quiz bằng AI</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Nhập yêu cầu, kiểm tra cấu hình rồi chuyển sang editor để lưu quiz vào backend.</p>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <div v-for="stat in quotaStats" :key="stat.label" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><p class="text-xs font-bold text-[var(--muted)]">{{ stat.label }}</p><b class="mt-1 block text-2xl font-black text-[var(--text)]">{{ stat.value }}</b></div>
        </div>
        <div class="mt-8 grid gap-4"><textarea v-model="prompt" class="field min-h-44" placeholder="Ví dụ: Tạo 10 câu quiz về quang hợp lớp 10, mức trung bình, có 4 đáp án mỗi câu..."></textarea><div class="grid gap-4 md:grid-cols-4"><select v-model="settings.count" class="field"><option>10 câu</option><option>15 câu</option><option>20 câu</option></select><select v-model="settings.difficulty" class="field"><option>Dễ</option><option>Vừa</option><option>Khó</option></select><select v-model="settings.language" class="field"><option>Tiếng Việt</option><option>English</option></select><select v-model="settings.visibility" class="field"><option value="private">Private</option><option value="public">Public</option><option value="group">Group</option></select></div>
          <router-link class="rounded-[1.5rem] border border-dashed border-[var(--border-strong)] bg-[var(--chip-active)] p-5 text-center transition hover:-translate-y-1" :to="`${questionBase}/ocr`"><p class="font-black text-[var(--text)]">Upload PDF / Image OCR</p><p class="mt-1 text-sm text-[var(--muted)]">Đi tới giao diện kéo thả file và preview OCR.</p></router-link>
          <button class="btn-primary w-fit" type="button" @click="prepareDraft">Chuẩn bị bản nháp</button></div>
      </div>
    </article>
    <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Preview</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">Bản nháp</h2><div v-if="!generated" class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6 text-center text-sm font-bold text-[var(--muted)]">Chưa có nội dung. Nhập prompt rồi chuẩn bị bản nháp.</div><div v-else class="mt-6 grid gap-3"><div class="rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><b class="text-[var(--text)]">Yêu cầu</b><p class="mt-2 text-sm text-[var(--muted)]">{{ prompt }}</p></div><router-link class="btn-primary text-center" :to="`${questionBase}/create`">Chỉnh trong editor</router-link></div></aside>
  </section>
</template>
<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { currentUserStorage } from '@/services/api'

const route = useRoute()
const questionBase = computed(() => route.path.startsWith('/dashboard') ? '/dashboard/questions' : '/admin/questions')
const prompt = ref('')
const generated = ref(false)
const settings = reactive({ count: '10 câu', difficulty: 'Vừa', language: 'Tiếng Việt', visibility: 'private' })
const user = computed(() => currentUserStorage.get())
const quotaStats = computed(() => [{ label: 'Role', value: user.value?.role_label || user.value?.role || 'Guest' }, { label: 'AI còn lại', value: user.value?.ai_quota_remaining ?? 0 }, { label: 'OCR còn lại', value: user.value?.role === 'vip' ? 120 : 3 }])
const prepareDraft = () => {
  generated.value = true
  sessionStorage.setItem('quizflex_ai_prompt', JSON.stringify({ prompt: prompt.value, settings }))
}
</script>
