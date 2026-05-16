<template>
  <section class="grid gap-6 py-8 lg:grid-cols-[minmax(0,1fr)_340px]">
    <article class="glass-card-strong rounded-[28px] p-6 sm:p-8">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-sm font-black uppercase tracking-[0.2em]" style="color: var(--primary)">Câu 1 / 10</p>
          <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] sm:text-4xl">{{ currentQuestion.question }}</h1>
        </div>
        <div class="grid h-20 w-20 place-items-center rounded-full text-lg font-black" style="background: radial-gradient(circle at center, var(--surface-strong) 53%, transparent 54%), conic-gradient(var(--accent-2) 0 78%, var(--surface-soft) 78% 100%)">
          78%
        </div>
      </div>

      <div class="mt-5 flex flex-wrap gap-2">
        <span class="badge badge-group">{{ currentQuestion.category }}</span>
        <span class="badge badge-public">{{ currentQuestion.difficulty }}</span>
        <span class="badge">AI generated</span>
      </div>

      <div class="mt-8 grid gap-3">
        <button v-for="answer in currentQuestion.answers" :key="answer.key" class="flex items-center gap-4 rounded-2xl border p-4 text-left transition hover:-translate-y-0.5" :style="selectedAnswer === answer.key ? selectedStyle : optionStyle" type="button" @click="selectedAnswer = answer.key">
          <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl text-sm font-black text-white" style="background: linear-gradient(135deg, var(--primary), var(--primary-2))">
            {{ answer.key }}
          </span>
          <span class="font-bold">{{ answer.text }}</span>
        </button>
      </div>

      <div class="mt-8 flex flex-wrap justify-between gap-3">
        <button class="btn-ghost" type="button">Câu trước</button>
        <button class="btn-primary" type="button">Trả lời và tiếp tục</button>
      </div>
    </article>

    <aside class="grid gap-5 content-start">
      <article class="glass-card rounded-[28px] p-6">
        <h2 class="text-2xl font-black tracking-[-0.05em]">Phòng realtime</h2>
        <p class="muted-text mt-2 text-sm leading-6">Mã phòng hiện tại</p>
        <div class="mt-4 rounded-2xl border p-5 text-center text-3xl font-black tracking-[0.18em]" style="border-color: var(--border-strong); background: var(--chip-active)">
          QZ24
        </div>
      </article>

      <article class="glass-card rounded-[28px] p-6">
        <h2 class="mb-4 text-2xl font-black tracking-[-0.05em]">Người chơi</h2>
        <div class="grid gap-3">
          <div v-for="player in players" :key="player.name" class="grid grid-cols-[1fr_auto] rounded-2xl border p-3" style="border-color: var(--border); background: var(--surface-soft)">
            <div>
              <b class="font-black">{{ player.name }}</b>
              <span class="muted-text mt-1 block text-xs">{{ player.status }}</span>
            </div>
            <span class="badge">{{ player.score }}</span>
          </div>
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { quizQuestions } from '@/data/demoData'

const currentQuestion = quizQuestions[0]
const selectedAnswer = ref('A')

const optionStyle = 'border-color: var(--border); background: var(--surface-soft); color: var(--text)'
const selectedStyle = 'border-color: var(--border-strong); background: var(--chip-active); color: var(--text)'

const players = [
  { name: 'Minh Anh', status: 'Đã trả lời', score: '320' },
  { name: 'Quốc Huy', status: 'Đang suy nghĩ', score: '280' },
  { name: 'Gia Linh', status: 'Đã trả lời', score: '250' },
]
</script>
