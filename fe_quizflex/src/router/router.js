import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', name: 'home', component: () => import('@/views/user/Home.vue'), meta: { layout: 'user', title: 'Trang chu' } },
  { path: '/quizzes', name: 'quiz-list', component: () => import('@/views/user/QuizList.vue'), meta: { layout: 'user', title: 'Danh sach quiz' } },
  { path: '/quizzes/:id', name: 'quiz-detail', component: () => import('@/views/user/QuizDetail.vue'), meta: { layout: 'user', title: 'Chi tiet quiz' } },
  { path: '/quizzes/:id/play', name: 'quiz-play', component: () => import('@/views/user/Quiz.vue'), meta: { layout: 'user', title: 'Lam quiz' } },
  { path: '/quiz/:id', redirect: (to) => `/quizzes/${to.params.id}` },
  { path: '/join-room', name: 'join-room', component: () => import('@/views/user/JoinRoom.vue'), meta: { layout: 'user', title: 'Join room' } },
  { path: '/rooms', name: 'rooms', component: () => import('@/views/user/Rooms.vue'), meta: { layout: 'user', title: 'Rooms cua toi' } },
  { path: '/rooms/:roomId/live', name: 'room-live', component: () => import('@/views/user/LiveSession.vue'), meta: { layout: 'user', title: 'Live Quiz', requiresAuth: true } },
  { path: '/rooms/:roomId/homework', name: 'room-homework', component: () => import('@/views/user/RoomDetail.vue'), meta: { layout: 'user', title: 'Homework trong room' } },
  { path: '/rooms/:roomId/assignments/:assignmentId/submissions', name: 'assignment-submissions', component: () => import('@/views/user/AssignmentSubmissions.vue'), meta: { layout: 'user', title: 'Bai nop homework' } },
  { path: '/rooms/:roomId/assignments/:assignmentId/do', name: 'assignment-play', component: () => import('@/views/user/AssignmentPlay.vue'), meta: { layout: 'user', title: 'Lam homework' } },
  { path: '/rooms/:roomId/assignments/:assignmentId/result', name: 'assignment-result', component: () => import('@/views/user/AssignmentResult.vue'), meta: { layout: 'user', title: 'Ket qua homework' } },
  { path: '/rooms/:roomId/assignments/:assignmentId', name: 'assignment-detail', component: () => import('@/views/user/AssignmentDetail.vue'), meta: { layout: 'user', title: 'Chi tiet homework' } },
  { path: '/rooms/:roomId', name: 'room-detail', component: () => import('@/views/user/RoomDetail.vue'), meta: { layout: 'user', title: 'Chi tiet room' } },
  { path: '/upgrade', name: 'upgrade', component: () => import('@/views/user/Upgrade.vue'), meta: { layout: 'user', title: 'Nang cap VIP' } },
  { path: '/results', name: 'results', component: () => import('@/views/user/Results.vue'), meta: { layout: 'user', title: 'Ket qua cua toi' } },
  { path: '/results/:id', name: 'attempt-result', component: () => import('@/views/user/AttemptResult.vue'), meta: { layout: 'user', title: 'Ket qua bai lam' } },
  { path: '/profile', name: 'profile', component: () => import('@/views/user/Profile.vue'), meta: { layout: 'user', title: 'Ho so ca nhan' } },
  { path: '/dashboard', name: 'dashboard', component: () => import('@/views/user/Home.vue'), meta: { layout: 'user', title: 'Dashboard' } },
  { path: '/dashboard/admin', name: 'dashboard-admin', component: () => import('@/views/user/AdminDashboard.vue'), meta: { layout: 'admin', title: 'Admin Dashboard' } },

  { path: '/login', name: 'login', component: () => import('@/views/auth/Login.vue'), meta: { layout: 'auth', title: 'Dang nhap' } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/Register.vue'), meta: { layout: 'auth', title: 'Dang ky' } },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/auth/ForgotPassword.vue'), meta: { layout: 'auth', title: 'Quen mat khau' } },

  { path: '/admin', name: 'admin-dashboard', component: () => import('@/views/user/AdminDashboard.vue'), meta: { layout: 'admin', title: 'Admin Dashboard' } },
  { path: '/admin/dashboard', name: 'admin-dashboard-alias', component: () => import('@/views/user/AdminDashboard.vue'), meta: { layout: 'admin', title: 'Admin Dashboard' } },
  { path: '/admin/questions', name: 'admin-questions', component: () => import('@/views/admin/Question.vue'), meta: { layout: 'admin', title: 'Kho quiz' } },
  { path: '/admin/questions/create', name: 'admin-question-create', component: () => import('@/views/admin/CreateQuiz.vue'), meta: { layout: 'admin', title: 'Tao quiz' } },
  { path: '/admin/questions/edit/:id', name: 'admin-question-edit', component: () => import('@/views/admin/CreateQuiz.vue'), meta: { layout: 'admin', title: 'Sua quiz' } },
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
