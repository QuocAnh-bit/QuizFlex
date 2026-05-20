import { createRouter, createWebHistory } from 'vue-router'
import { currentUserStorage } from '@/services/api'

const routes = [
  { path: '/', name: 'home', component: () => import('@/views/user/Home.vue'), meta: { layout: 'user', title: 'Trang chủ' } },
  { path: '/quizzes', name: 'quiz-list', component: () => import('@/views/user/QuizList.vue'), meta: { layout: 'user', title: 'Danh sách quiz' } },
  { path: '/quizzes/:id', name: 'quiz-detail', component: () => import('@/views/user/QuizDetail.vue'), meta: { layout: 'user', title: 'Chi tiết quiz' } },
  { path: '/quizzes/:id/play', name: 'quiz-play', component: () => import('@/views/user/Quiz.vue'), meta: { layout: 'user', title: 'Làm quiz' } },
  { path: '/quiz/:id', redirect: (to) => `/quizzes/${to.params.id}` },
  { path: '/join-room', name: 'join-room', component: () => import('@/views/user/JoinRoom.vue'), meta: { layout: 'user', title: 'Join room' } },
  { path: '/upgrade', name: 'upgrade', component: () => import('@/views/user/Upgrade.vue'), meta: { layout: 'user', title: 'Nâng cấp VIP' } },
  { path: '/results', name: 'results', component: () => import('@/views/user/Results.vue'), meta: { layout: 'user', title: 'Kết quả của tôi' } },
  { path: '/results/:id', name: 'attempt-result', component: () => import('@/views/user/AttemptResult.vue'), meta: { layout: 'user', title: 'Kết quả bài làm' } },
  { path: '/profile', name: 'profile', component: () => import('@/views/user/Profile.vue'), meta: { layout: 'user', title: 'Hồ sơ cá nhân' } },

  { path: '/login', name: 'login', component: () => import('@/views/auth/Login.vue'), meta: { layout: 'auth', title: 'Đăng nhập' } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/Register.vue'), meta: { layout: 'auth', title: 'Đăng ký' } },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/auth/ForgotPassword.vue'), meta: { layout: 'auth', title: 'Quên mật khẩu' } },

  { path: '/admin', name: 'admin-dashboard', component: () => import('@/views/admin/Dashboard.vue'), meta: { layout: 'admin', title: 'Dashboard' } },
  { path: '/admin/questions', name: 'admin-questions', component: () => import('@/views/admin/Question.vue'), meta: { layout: 'admin', title: 'Kho quiz' } },
  { path: '/admin/questions/create', name: 'admin-question-create', component: () => import('@/views/admin/CreateQuiz.vue'), meta: { layout: 'admin', title: 'Tạo quiz' } },
  { path: '/admin/questions/edit/:id', name: 'admin-question-edit', component: () => import('@/views/admin/CreateQuiz.vue'), meta: { layout: 'admin', title: 'Sửa quiz' } },
  { path: '/admin/questions/ai', name: 'admin-question-ai', component: () => import('@/views/admin/AiQuiz.vue'), meta: { layout: 'admin', title: 'AI Generator' } },
  { path: '/admin/questions/ocr', name: 'admin-question-ocr', component: () => import('@/views/admin/OcrUpload.vue'), meta: { layout: 'admin', title: 'OCR Upload' } },
  { path: '/admin/rooms', name: 'admin-rooms', component: () => import('@/views/admin/Rooms.vue'), meta: { layout: 'admin', title: 'Room' } },
  { path: '/admin/reports', name: 'admin-reports', component: () => import('@/views/admin/Reports.vue'), meta: { layout: 'admin', title: 'Report', requiresAdmin: true } },
  { path: '/admin/payments', name: 'admin-payments', component: () => import('@/views/admin/Payments.vue'), meta: { layout: 'admin', title: 'Payment', requiresAdmin: true } },
  { path: '/admin/users', name: 'admin-users', component: () => import('@/views/admin/Users.vue'), meta: { layout: 'admin', title: 'User management', requiresAdmin: true } },
  { path: '/admin/settings', name: 'admin-settings', component: () => import('@/views/admin/Settings.vue'), meta: { layout: 'admin', title: 'Settings', requiresAdmin: true } },

  { path: '/:pathMatch(.*)*', name: 'not-found', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0, behavior: 'smooth' }
  },
})

router.beforeEach((to, from, next) => {
  const user = currentUserStorage.get()
  const isAuthLayout = to.meta.layout === 'auth'
  const isAdminLayout = to.meta.layout === 'admin'

  // Chặn user đã login quay lại trang login/register
  if (user && isAuthLayout) {
    return next('/admin')
  }

  // Yêu cầu login nếu truy cập trang admin
  if (!user && isAdminLayout) {
    return next('/login')
  }

  // Kiểm tra quyền Admin (Chỉ block các trang requiresAdmin)
  if (user && to.meta.requiresAdmin && String(user.role).toLowerCase() !== 'admin') {
    return next('/')
  }

  // Yêu cầu login cho các trang cá nhân
  const requiresAuth = ['profile', 'results', 'attempt-result'].includes(to.name)
  if (!user && requiresAuth) {
    return next('/login')
  }

  next()
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} | QuizFlex` : 'QuizFlex'
})

export default router
