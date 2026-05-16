import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', name: 'home', component: () => import('@/views/user/Home.vue'), meta: { layout: 'user', title: 'Trang chủ' } },
  { path: '/quiz/:id', name: 'quiz-play', component: () => import('@/views/user/Quiz.vue'), meta: { layout: 'user', title: 'Làm quiz' } },
  { path: '/join-room', name: 'join-room', component: () => import('@/views/user/JoinRoom.vue'), meta: { layout: 'user', title: 'Join room' } },
  { path: '/upgrade', name: 'upgrade', component: () => import('@/views/user/Upgrade.vue'), meta: { layout: 'user', title: 'Nâng cấp VIP' } },
  { path: '/results', name: 'results', component: () => import('@/views/user/Results.vue'), meta: { layout: 'user', title: 'Kết quả của tôi' } },
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
  { path: '/admin/reports', name: 'admin-reports', component: () => import('@/views/admin/Reports.vue'), meta: { layout: 'admin', title: 'Report' } },
  { path: '/admin/payments', name: 'admin-payments', component: () => import('@/views/admin/Payments.vue'), meta: { layout: 'admin', title: 'Payment' } },
  { path: '/admin/users', name: 'admin-users', component: () => import('@/views/admin/Users.vue'), meta: { layout: 'admin', title: 'User management' } },
  { path: '/admin/settings', name: 'admin-settings', component: () => import('@/views/admin/Settings.vue'), meta: { layout: 'admin', title: 'Settings' } },

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

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} | QuizFlex` : 'QuizFlex'
 })

export default router
