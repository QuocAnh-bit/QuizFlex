import { createRouter, createWebHistory } from 'vue-router'
import {
  authApi,
  currentUserStorage,
  getDefaultRouteForRole,
  hasAnyRole,
  tokenStorage,
} from '@/services/api'

const workspaceRoles = ['admin', 'vip', 'user']
const userDashboardRoles = ['admin', 'vip', 'user']
const adminRoles = ['admin']

const routes = [
  { path: '/', name: 'home', component: () => import('@/views/user/Home.vue'), meta: { layout: 'user', title: 'Trang chủ' } },
  { path: '/quizzes', name: 'quiz-list', component: () => import('@/views/user/QuizList.vue'), meta: { layout: 'user', title: 'Danh sách quiz' } },
  { path: '/quizzes/:id', name: 'quiz-detail', component: () => import('@/views/user/QuizDetail.vue'), meta: { layout: 'user', title: 'Chi tiết quiz' } },
  { path: '/quizzes/:id/play', name: 'quiz-play', component: () => import('@/views/user/Quiz.vue'), meta: { layout: 'user', title: 'Làm quiz' } },
  { path: '/quiz/:id', redirect: (to) => `/quizzes/${to.params.id}` },
  { path: '/join-room', name: 'join-room', component: () => import('@/views/user/JoinRoom.vue'), meta: { layout: 'user', title: 'Join room' } },
  { path: '/upgrade', name: 'upgrade', component: () => import('@/views/user/Upgrade.vue'), meta: { layout: 'user', title: 'Nâng cấp VIP' } },
  { path: '/results', name: 'results', component: () => import('@/views/user/Results.vue'), meta: { layout: 'user', title: 'Kết quả của tôi', requiresAuth: true } },
  { path: '/results/:id', name: 'attempt-result', component: () => import('@/views/user/AttemptResult.vue'), meta: { layout: 'user', title: 'Kết quả bài làm', requiresAuth: true } },
  { path: '/profile', name: 'profile', component: () => import('@/views/user/Profile.vue'), meta: { layout: 'user', title: 'Hồ sơ cá nhân', requiresAuth: true } },

  { path: '/login', name: 'login', component: () => import('@/views/auth/Login.vue'), meta: { layout: 'auth', title: 'Đăng nhập' } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/Register.vue'), meta: { layout: 'auth', title: 'Đăng ký' } },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/auth/ForgotPassword.vue'), meta: { layout: 'auth', title: 'Quên mật khẩu' } },

  { path: '/dashboard', name: 'user-dashboard', component: () => import('@/views/user/Dashboard.vue'), meta: { layout: 'user', title: 'Dashboard người dùng', requiresAuth: true, roles: userDashboardRoles } },
  { path: '/dashboard/questions', name: 'user-questions', component: () => import('@/views/admin/Question.vue'), meta: { layout: 'user', title: 'Kho quiz của tôi', requiresAuth: true, roles: workspaceRoles } },
  { path: '/dashboard/questions/create', name: 'user-question-create', component: () => import('@/views/admin/CreateQuiz.vue'), meta: { layout: 'user', title: 'Tạo quiz', requiresAuth: true, roles: workspaceRoles } },
  { path: '/dashboard/questions/edit/:id', name: 'user-question-edit', component: () => import('@/views/admin/CreateQuiz.vue'), meta: { layout: 'user', title: 'Sửa quiz', requiresAuth: true, roles: workspaceRoles } },
  { path: '/dashboard/questions/ai', name: 'user-question-ai', component: () => import('@/views/admin/AiQuiz.vue'), meta: { layout: 'user', title: 'AI Generator', requiresAuth: true, roles: workspaceRoles } },
  { path: '/dashboard/questions/ocr', name: 'user-question-ocr', component: () => import('@/views/admin/OcrUpload.vue'), meta: { layout: 'user', title: 'OCR Upload', requiresAuth: true, roles: workspaceRoles } },
  { path: '/dashboard/rooms', name: 'user-rooms', component: () => import('@/views/admin/Rooms.vue'), meta: { layout: 'user', title: 'Room của tôi', requiresAuth: true, roles: workspaceRoles } },

  { path: '/admin', name: 'admin-dashboard', component: () => import('@/views/admin/Dashboard.vue'), meta: { layout: 'admin', title: 'Dashboard admin', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/questions', name: 'admin-questions', component: () => import('@/views/admin/Question.vue'), meta: { layout: 'admin', title: 'Kho quiz admin', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/questions/create', name: 'admin-question-create', component: () => import('@/views/admin/CreateQuiz.vue'), meta: { layout: 'admin', title: 'Tạo quiz admin', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/questions/edit/:id', name: 'admin-question-edit', component: () => import('@/views/admin/CreateQuiz.vue'), meta: { layout: 'admin', title: 'Sửa quiz admin', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/questions/ai', name: 'admin-question-ai', component: () => import('@/views/admin/AiQuiz.vue'), meta: { layout: 'admin', title: 'AI Generator admin', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/questions/ocr', name: 'admin-question-ocr', component: () => import('@/views/admin/OcrUpload.vue'), meta: { layout: 'admin', title: 'OCR Upload admin', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/rooms', name: 'admin-rooms', component: () => import('@/views/admin/Rooms.vue'), meta: { layout: 'admin', title: 'Room admin', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/reports', name: 'admin-reports', component: () => import('@/views/admin/Reports.vue'), meta: { layout: 'admin', title: 'Report', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/payments', name: 'admin-payments', component: () => import('@/views/admin/Payments.vue'), meta: { layout: 'admin', title: 'Payment', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/users', name: 'admin-users', component: () => import('@/views/admin/Users.vue'), meta: { layout: 'admin', title: 'User management', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/settings', name: 'admin-settings', component: () => import('@/views/admin/Settings.vue'), meta: { layout: 'admin', title: 'Settings', requiresAuth: true, roles: adminRoles } },

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

const safeRedirect = (value) => {
  if (!value || typeof value !== 'string') return ''
  if (!value.startsWith('/') || value.startsWith('//')) return ''
  return value
}

router.beforeEach(async (to) => {
  let user = currentUserStorage.get()
  const token = tokenStorage.get()
  const isAuthLayout = to.meta.layout === 'auth'

  if (!token && user) {
    authApi.clearSession()
    user = null
  }

  if (token && !user) {
    try {
      user = await authApi.me()
    } catch {
      authApi.clearSession()
      user = null
    }
  }

  if (user && isAuthLayout) {
    return safeRedirect(String(to.query.redirect || '')) || getDefaultRouteForRole(user.role)
  }

  if (to.meta.requiresAuth && !user) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (user && Array.isArray(to.meta.roles) && !hasAnyRole(user, to.meta.roles)) {
    return getDefaultRouteForRole(user.role)
  }

  return true
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} | QuizFlex` : 'QuizFlex'
})

export default router
