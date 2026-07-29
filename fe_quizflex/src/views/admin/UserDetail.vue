<template>
  <section class="grid gap-6">
    <div class="flex flex-col gap-4 rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin user</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Chi tiết người dùng</h1>
          <p class="mt-2 text-sm leading-6 text-[var(--muted)]">Xem thông tin cá nhân, role, VIP, quiz, lượt làm bài và lịch sử thanh toán.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button class="btn-ghost" type="button" @click="goBack">Quay lại danh sách</button>
        </div>
      </div>

      <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải chi tiết user...</div>
      <div v-else-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

      <div v-else class="grid gap-6">
        <div class="grid gap-5 lg:grid-cols-[280px_minmax(0,1fr)]">
          <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
            <div class="flex flex-col items-center gap-4 text-center">
              <div class="flex h-24 w-24 items-center justify-center rounded-full bg-[var(--primary)]/10 text-4xl font-black text-[var(--primary)]">
                {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
              </div>
              <div>
                <h2 class="text-2xl font-black text-[var(--text)]">{{ user.name }}</h2>
                <p class="text-sm text-[var(--muted)]">{{ user.email }}</p>
              </div>
            </div>

            <div class="mt-6 space-y-5">
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Plan</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ user.plan_label || user.plan }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">AI còn lại</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ user.role === 'admin' ? '∞' : (user.ai_quota_remaining ?? 0) + ' lượt' }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Role hiện tại</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ user.role_label || user.role }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Trạng thái VIP</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ user.vip_status || 'Không VIP' }}</p>
                <p class="mt-1 text-sm text-[var(--muted)]">Hết hạn: {{ formatDate(user.vip_expires_at) || 'Chưa có' }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Tổng số tiền đã nạp</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ formatCurrency(user.total_paid || 0) }}</p>
              </div>
            </div>
          </div>

          <div class="grid gap-4">
            <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
              <h3 class="text-lg font-black text-[var(--text)]">Thông tin cơ bản</h3>
              <div class="mt-4 grid gap-3">
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Tên</span>
                  <span>{{ user.name }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Email</span>
                  <span>{{ user.email }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Role thực tế</span>
                  <span>{{ user.role_label || user.role }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Ngày tạo</span>
                  <span>{{ formatDate(user.created_at) }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Lượt làm bài</span>
                  <span>{{ user.attempts_count ?? 0 }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Tổng quiz tạo</span>
                  <span>{{ user.quizzes_count ?? user.quizzes?.length ?? 0 }}</span>
                </div>
              </div>
            </div>

            <div class="grid gap-4">
              <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
                <h3 class="text-lg font-black text-[var(--text)]">VIP / thanh toán</h3>
                <div class="mt-4 space-y-3 text-sm text-[var(--muted)]">
                  <div>
                    <p class="font-semibold text-[var(--text)]">User đã mua VIP tháng</p>
                    <p>{{ user.vip_months_purchased?.length ? user.vip_months_purchased.join(', ') : 'Chưa có' }}</p>
                  </div>
                  <div>
                    <p class="font-semibold text-[var(--text)]">User đã nạp tiền tháng</p>
                    <p>{{ user.payment_months?.length ? user.payment_months.join(', ') : 'Chưa có' }}</p>
                  </div>
                </div>
              </div>
              <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
                <h3 class="text-lg font-black text-[var(--text)]">Thông tin quiz</h3>
                <div class="mt-4 grid gap-3 text-sm text-[var(--muted)]">
                  <div>
                    <span class="font-semibold text-[var(--text)]">Quiz đã tạo</span>
                    <span class="block mt-1 text-[var(--text)]">{{ user.quizzes?.length ?? 0 }}</span>
                  </div>
                  <div>
                    <span class="font-semibold text-[var(--text)]">Tổng lượt làm bài của quiz</span>
                    <span class="block mt-1 text-[var(--text)]">{{ user.quizzes?.reduce((sum, quiz) => sum + (quiz.attempts_count || 0), 0) ?? 0 }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
            <h2 class="text-xl font-black text-[var(--text)]">Danh sách quiz đã tạo</h2>
            <div class="mt-4 overflow-x-auto">
              <table class="min-w-full text-left text-sm">
                <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs uppercase tracking-[0.2em] text-[var(--muted)]">
                  <tr>
                    <th class="px-4 py-3">Tiêu đề</th>
                    <th class="px-4 py-3">Số câu hỏi</th>
                    <th class="px-4 py-3">Tổng lượt làm</th>
                    <th class="px-4 py-3">Điểm trung bình (%)</th>
                    <th class="px-4 py-3">Ngày tạo</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                  <tr v-for="quiz in user.quizzes" :key="quiz.id">
                    <td class="px-4 py-3 font-semibold text-[var(--text)]">{{ quiz.title }}</td>
                    <td class="px-4 py-3">{{ quiz.questions_count ?? 0 }}</td>
                    <td class="px-4 py-3">{{ quiz.attempts_count ?? 0 }}</td>
                    <td class="px-4 py-3">{{ quiz.avg_score ?? 0 }}%</td>
                    <td class="px-4 py-3">{{ formatDate(quiz.created_at) }}</td>
                  </tr>
                  <tr v-if="!user.quizzes?.length">
                    <td class="px-4 py-6 text-center text-sm text-[var(--muted)]" colspan="5">Người dùng chưa tạo quiz nào.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
              <h2 class="text-xl font-black text-[var(--text)]">Lịch sử làm bài</h2>
              <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                  <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs uppercase tracking-[0.2em] text-[var(--muted)]">
                    <tr>
                      <th class="px-4 py-3">Quiz</th>
                      <th class="px-4 py-3">Điểm</th>
                      <th class="px-4 py-3">Tỷ lệ</th>
                      <th class="px-4 py-3">Trạng thái</th>
                      <th class="px-4 py-3">Ngày</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-[var(--border)]">
                    <tr v-for="attempt in user.attempt_history" :key="attempt.id">
                      <td class="px-4 py-3">{{ attempt.quiz_title || 'Quiz không rõ' }}</td>
                      <td class="px-4 py-3">{{ attempt.score }}/{{ attempt.total_points }}</td>
                      <td class="px-4 py-3">{{ attempt.percent }}%</td>
                      <td class="px-4 py-3">{{ attempt.status }}</td>
                      <td class="px-4 py-3">{{ formatDate(attempt.finished_at) }}</td>
                    </tr>
                    <tr v-if="!user.attempt_history?.length">
                      <td class="px-4 py-6 text-center text-sm text-[var(--muted)]" colspan="5">Chưa có lịch sử làm bài.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
              <h2 class="text-xl font-black text-[var(--text)]">Lịch sử thanh toán</h2>
              <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                  <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs uppercase tracking-[0.2em] text-[var(--muted)]">
                    <tr>
                      <th class="px-4 py-3">Mã đơn</th>
                      <th class="px-4 py-3">Nhà cung cấp</th>
                      <th class="px-4 py-3">Số tiền</th>
                      <th class="px-4 py-3">Trạng thái</th>
                      <th class="px-4 py-3">Ngày</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-[var(--border)]">
                    <tr v-for="payment in user.payment_history" :key="payment.id">
                      <td class="px-4 py-3">{{ payment.order_code }}</td>
                      <td class="px-4 py-3">{{ payment.provider }}</td>
                      <td class="px-4 py-3">{{ formatCurrency(payment.amount) }}</td>
                      <td class="px-4 py-3">{{ payment.status }}</td>
                      <td class="px-4 py-3">{{ formatDate(payment.paid_at) }}</td>
                    </tr>
                    <tr v-if="!user.payment_history?.length">
                      <td class="px-4 py-6 text-center text-sm text-[var(--muted)]" colspan="5">Chưa có lịch sử thanh toán.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usersApi } from '@/services/api'

const ocrQuotaForPlan = (plan) => ({ ultra: '∞', pro: '50', plus: '10', free: '0' }[plan] || '0')

const route = useRoute()
const router = useRouter()
const user = ref(null)
const isLoading = ref(true)
const errorMessage = ref('')

const loadUser = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const data = await usersApi.get(route.params.id)
    user.value = data
  } catch (error) {
    errorMessage.value = `Không tải được chi tiết user: ${error.message}`
    user.value = null
  } finally {
    isLoading.value = false
  }
}

const goBack = () => router.push({ name: 'admin-users' })

const formatDate = (value) => {
  if (!value) return 'Chưa rõ'
  const date = new Date(value)
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const formatCurrency = (value) => {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  }).format(amount)
}

onMounted(loadUser)
</script>
