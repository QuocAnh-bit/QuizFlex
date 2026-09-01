import { createRouter, createWebHistory } from "vue-router";
import {
  authApi,
  currentUserStorage,
  getDefaultRouteForRole,
  hasAnyRole,
  tokenStorage,
} from "@/services/api";

const workspaceRoles = ["admin", "free", "plus", "pro", "ultra"];
const userDashboardRoles = ["admin", "free", "plus", "pro", "ultra"];
const adminRoles = ["admin"];

const routes = [
  {
    path: "/",
    name: "home",
    component: () => import("@/views/user/Home.vue"),
    meta: { layout: "user", title: "Trang chủ" },
  },
  {
    path: "/quizzes",
    name: "quiz-list",
    component: () => import("@/views/user/QuizList.vue"),
    meta: { layout: "user", title: "Danh sách quiz" },
  },
  {
    path: "/quiz-editor-v2",
    name: "quiz-editor-v2",
    component: () => import("@/views/quiz/QuizEditorV2.vue"),
    meta: { layout: "user", title: "Quiz Editor V2" },
  },
  {
    path: "/question-bank",
    redirect: "/dashboard/my-questions",
  },
  {
    path: "/question-bank/create-question",
    redirect: "/dashboard/my-questions/create",
  },
  {
    path: "/dashboard/my-questions/create",
    name: "user-my-questions-create",
    component: () => import("@/views/user/CreateQuestionView.vue"),
    meta: { layout: "user", title: "Tạo câu hỏi mới", requiresAuth: true, roles: workspaceRoles },
  },
  {
    path: "/dashboard/my-questions/:id/edit",
    name: "user-my-questions-edit",
    component: () => import("@/views/user/EditQuestionView.vue"),
    meta: { layout: "user", title: "Chỉnh sửa câu hỏi", requiresAuth: true, roles: workspaceRoles },
  },
  {
    path: "/quizzes/:id",
    name: "quiz-detail",
    component: () => import("@/views/user/QuizDetail.vue"),
    meta: { layout: "user", title: "Chi tiết quiz" },
  },
  {
    path: "/quizzes/:id/flashcards",
    name: "quiz-flashcards",
    component: () => import("@/views/user/Flashcards.vue"),
    meta: { layout: "user", title: "Ôn tập Flashcard" },
  },
  {
    path: "/quizzes/:id/play",
    name: "quiz-play",
    component: () => import("@/views/user/Quiz.vue"),
    meta: {
      layout: "user",
      title: "Làm quiz",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  { path: "/quiz/:id", redirect: (to) => `/quizzes/${to.params.id}` },
  {
    path: "/join-room",
    name: "join-room",
    component: () => import("@/views/user/JoinRoom.vue"),
    meta: { layout: "user", title: "Join room" },
  },
  {
    path: "/upgrade",
    name: "upgrade",
    component: () => import("@/views/user/Upgrade.vue"),
    meta: { layout: "user", title: "Nâng cấp tài khoản" },
  },
  {
    path: "/payment-result",
    name: "payment-result",
    component: () => import("@/views/user/PaymentResult.vue"),
    meta: { layout: "user", title: "Kết quả thanh toán" },
  },
  {
    path: "/results",
    name: "results",
    component: () => import("@/views/user/Results.vue"),
    meta: {
      layout: "user",
      title: "Kết quả của tôi",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/analytics",
    name: "analytics",
    component: () => import("@/views/user/Analytics.vue"),
    meta: {
      layout: "user",
      title: "Phân tích năng lực",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/results/:id",
    name: "attempt-result",
    component: () => import("@/views/user/AttemptResult.vue"),
    meta: {
      layout: "user",
      title: "Kết quả bài làm",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/homework-rooms",
    name: "homework-rooms",
    component: () => import("@/views/homework/HomeworkRooms.vue"),
    meta: {
      layout: "user",
      title: "Phòng bài tập",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/homework-rooms/create",
    name: "homework-room-create",
    component: () => import("@/views/homework/HomeworkRoomCreate.vue"),
    meta: {
      layout: "user",
      title: "Tạo phòng bài tập",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/homework-rooms/join",
    name: "homework-room-join",
    component: () => import("@/views/homework/HomeworkRoomJoin.vue"),
    meta: {
      layout: "user",
      title: "Tham gia phòng bài tập",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/homework-rooms/:roomId",
    name: "homework-room-detail",
    component: () => import("@/views/homework/HomeworkRoomDetail.vue"),
    meta: {
      layout: "user",
      title: "Chi tiết phòng bài tập",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/homework-rooms/:roomId/assignments/create",
    name: "homework-assignment-create",
    component: () => import("@/views/homework/HomeworkAssignmentCreate.vue"),
    meta: {
      layout: "user",
      title: "Giao quiz",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/homework-rooms/:roomId/assignments/:assignmentId/attempts",
    name: "homework-assignment-attempts",
    component: () => import("@/views/homework/HomeworkAssignmentAttempts.vue"),
    meta: {
      layout: "user",
      title: "Bài nộp ",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/homework-rooms/:roomId/assignments/:assignmentId/take",
    name: "homework-assignment-take",
    component: () => import("@/views/homework/HomeworkAssignmentTake.vue"),
    meta: {
      layout: "user",
      title: "Làm bài tập",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/live-rooms",
    name: "live-rooms",
    component: () => import("@/views/live/LiveRooms.vue"),
    meta: {
      layout: "user",
      title: "Phòng thi đấu",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/live-rooms/create",
    name: "live-room-create",
    component: () => import("@/views/live/LiveRoomCreate.vue"),
    meta: {
      layout: "user",
      title: "Tạo phòng thi đấu",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/live-rooms/join",
    name: "live-room-join",
    component: () => import("@/views/live/LiveRoomJoin.vue"),
    meta: {
      layout: "user",
      title: "Tham gia phòng thi đấu",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/live-rooms/:liveRoomId/host",
    name: "live-room-host",
    component: () => import("@/views/live/LiveRoomHost.vue"),
    meta: {
      layout: "user",
      title: "Chủ phòng",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/live-rooms/:liveRoomId/play",
    name: "live-room-play",
    component: () => import("@/views/live/LiveRoomPlay.vue"),
    meta: {
      layout: "user",
      title: "Chơi live room",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/live-rooms/:liveRoomId/leaderboard",
    name: "live-room-leaderboard",
    component: () => import("@/views/live/LiveRoomLeaderboard.vue"),
    meta: {
      layout: "user",
      title: "Leaderboard live room",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/profile",
    name: "profile",
    component: () => import("@/views/user/Profile.vue"),
    meta: { layout: "user", title: "Hồ sơ cá nhân" },
  },
  {
    path: "/notifications",
    name: "notifications",
    component: () => import("@/views/user/Notifications.vue"),
    meta: {
      layout: "user",
      title: "Thông báo của tôi",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/my-reports",
    name: "my-reports",
    component: () => import("@/views/user/MyReports.vue"),
    meta: {
      layout: "user",
      title: "Báo cáo của tôi",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/dashboard/my-reports",
    redirect: "/my-reports",
  },

  {
    path: "/login",
    name: "login",
    component: () => import("@/views/auth/Login.vue"),
    meta: { layout: "auth", title: "Đăng nhập" },
  },
  {
    path: "/register",
    name: "register",
    component: () => import("@/views/auth/Register.vue"),
    meta: { layout: "auth", title: "Đăng ký" },
  },
  {
    path: "/account-locked",
    name: "account-locked",
    component: () => import("@/views/user/AccountLocked.vue"),
    meta: { layout: "user", title: "Tài khoản bị khóa" },
  },
  {
    path: "/forgot-password",
    name: "forgot-password",
    component: () => import("@/views/auth/ForgotPassword.vue"),
    meta: { layout: "auth", title: "Quên mật khẩu" },
  },

  {
    path: "/dashboard",
    name: "user-dashboard",
    component: () => import("@/views/user/Dashboard.vue"),
    meta: {
      layout: "user",
      title: "Dashboard người dùng",
      requiresAuth: true,
      roles: userDashboardRoles,
    },
  },
  {
    path: "/dashboard/questions",
    name: "user-questions",
    component: () => import("@/views/admin/Question.vue"),
    meta: {
      layout: "user",
      title: "Kho quiz của tôi",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/dashboard/my-questions",
    name: "user-my-questions",
    component: () => import("@/views/user/MyQuestionBank.vue"),
    meta: {
      layout: "user",
      title: "Kho câu hỏi của tôi",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/dashboard/questions/create",
    name: "user-question-create",
    component: () => import("@/views/user/CreateExamView.vue"),
    meta: {
      layout: "user",
      title: "Tạo quiz",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/quizzes/:id/edit",
    name: "quiz-edit",
    component: () => import("@/views/user/UserQuizEdit.vue"),
    meta: {
      layout: "user",
      title: "Chỉnh sửa quiz",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/dashboard/questions/edit/:id",
    name: "user-question-edit",
    component: () => import("@/views/user/UserQuizEdit.vue"),
    meta: {
      layout: "user",
      title: "Sửa quiz",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/dashboard/questions/ai",
    name: "user-question-ai",
    component: () => import("@/views/admin/AiQuiz.vue"),
    meta: {
      layout: "user",
      title: "AI Generator",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/dashboard/questions/ocr",
    name: "user-question-ocr",
    component: () => import("@/views/admin/OcrUpload.vue"),
    meta: {
      layout: "user",
      title: "OCR Upload",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  {
    path: "/dashboard/rooms",
    name: "user-rooms",
    component: () => import("@/views/admin/Rooms.vue"),
    meta: {
      layout: "user",
      title: "Room của tôi",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },

  {
    path: "/admin",
    name: "admin-dashboard",
    component: () => import("@/views/admin/Dashboard.vue"),
    meta: {
      layout: "admin",
      title: "Dashboard admin",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  {
    path: "/admin/questions",
    name: "admin-questions",
    component: () => import("@/views/admin/Question.vue"),
    meta: {
      layout: "admin",
      title: "Kho quiz admin",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  { path: "/admin/questions/create", redirect: "/admin/questions" },
  { path: "/admin/questions/edit/:id", redirect: "/admin/questions" },
  { path: "/admin/questions/ai", redirect: "/admin/questions" },
  { path: "/admin/questions/ocr", redirect: "/admin/questions" },
  {
    path: "/admin/subjects",
    name: "admin-subjects",
    component: () => import("@/views/admin/AdminSubjectManager.vue"),
    meta: {
      layout: "admin",
      title: "Quản lý Bộ môn",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  {
    path: "/admin/reports",
    name: "admin-reports",
    component: () => import("@/views/admin/ReportManager.vue"),
    meta: {
      layout: "admin",
      title: "Quản lý Báo cáo",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  {
    path: "/admin/report-tickets",
    redirect: (to) => ({ path: "/admin/reports", query: to.query }),
  },
  {
    path: "/admin/question-bank-requests",
    redirect: (to) => ({ path: "/admin/question-bank", query: { ...to.query, tab: "pending" } }),
  },
  {
    path: "/admin/quiz-review-requests",
    redirect: (to) => ({ path: "/admin/quizzes", query: { ...to.query, tab: "pending" } }),
  },
  {
    path: "/admin/rooms",
    name: "admin-rooms",
    component: () => import("@/views/admin/Rooms.vue"),
    meta: {
      layout: "admin",
      title: "Quản lý phòng",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  { path: '/admin/rooms/homework',
    name: 'admin-homework-rooms', 
    component: () => import('@/views/admin/AdminHomeworkRooms.vue'), 
    meta: { 
      layout: 'admin', 
      title: 'Quản lý phòng bài tập', 
      requiresAuth: true, 
      roles: adminRoles, 
    },
  },
  { path: '/admin/rooms/live', 
    name: 'admin-live-rooms', 
    component: () => import('@/views/admin/AdminLiveRooms.vue'), 
    meta: { 
      layout: 'admin', 
      title: 'Quản lý phòng thi đấu', 
      requiresAuth: true, 
      roles: adminRoles, 
    }, 
  },
  {
    path: "/admin/payments",
    name: "admin-payments",
    component: () => import("@/views/admin/Payments.vue"),
    meta: {
      layout: "admin",
      title: "Quản lý Giao dịch & Doanh thu",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  {
    path: "/admin/users",
    name: "admin-users",
    component: () => import("@/views/admin/Users.vue"),
    meta: {
      layout: "admin",
      title: "Quản lý Người dùng",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  {
    path: "/admin/users/:id",
    name: "admin-user-detail",
    component: () => import("@/views/admin/UserDetail.vue"),
    meta: {
      layout: "admin",
      title: "Chi tiết người dùng",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  {
    path: "/admin/unlock-requests",
    name: "admin-unlock-requests",
    component: () => import("@/views/admin/UnlockRequests.vue"),
    meta: {
      layout: "admin",
      title: "Kháng cáo tài khoản",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  {
    path: "/admin/settings",
    name: "admin-settings",
    component: () => import("@/views/admin/Settings.vue"),
    meta: {
      layout: "admin",
      title: "Cài đặt hệ thống",
      requiresAuth: true,
      roles: adminRoles,
    },
  },
  

  // Admin Quiz & Question Management
  { path: '/admin/questions', redirect: '/admin/question-bank' },
  { path: '/admin/question-bank', name: 'admin-question-bank', component: () => import('@/views/admin/AdminQuestionManager.vue'), meta: { layout: 'admin', title: 'Ngân hàng câu hỏi', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/questions-trash', name: 'admin-questions-trash', component: () => import('@/views/admin/AdminQuestionsTrash.vue'), meta: { layout: 'admin', title: 'Thùng rác câu hỏi', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/questions/:id', name: 'admin-question-detail', component: () => import('@/views/admin/AdminQuestionDetail.vue'), meta: { layout: 'admin', title: 'Chi tiết câu hỏi', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/questions/:id/edit', redirect: to => `/admin/questions/${to.params.id}` },
  { path: '/admin/quizzes', name: 'admin-quizzes', component: () => import('@/views/admin/quizzes/QuizList.vue'), meta: { layout: 'admin', title: 'Quản lý Quiz', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/quizzes-trash', redirect: '/admin/quizzes' },
  { path: '/admin/quizzes/:id', name: 'admin-quiz-detail', component: () => import('@/views/admin/quizzes/QuizDetail.vue'), meta: { layout: 'admin', title: 'Chi tiết Quiz', requiresAuth: true, roles: adminRoles } },
  { path: '/admin/quizzes/:id/edit', redirect: to => `/admin/quizzes/${to.params.id}` },

  {
    path: "/gamification",
    name: "gamification",
    component: () => import("@/views/user/GamificationStats.vue"),
    meta: {
      layout: "user",
      title: "Thành tích & Huy hiệu",
      requiresAuth: true,
      roles: workspaceRoles,
    },
  },
  { path: '/leaderboard', component: () => import('@/views/user/Leaderboard.vue') },
  { path: '/:pathMatch(.*)*', name: 'not-found', redirect: '/' },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: "smooth" };
    return { top: 0, behavior: "smooth" };
  },
});

const safeRedirect = (value) => {
  if (!value || typeof value !== "string") return "";
  if (!value.startsWith("/") || value.startsWith("//")) return "";
  return value;
};

router.beforeEach(async (to) => {
  let user = currentUserStorage.get();
  const token = tokenStorage.get();
  const isAuthLayout = to.meta.layout === "auth";
  const requiresAuth = Boolean(to.meta.requiresAuth);

  if (!token && user) {
    authApi.clearSession();
    user = null;
  }

  if (token && !user) {
    try {
      user = await authApi.me();
    } catch {
      authApi.clearSession();
      user = null;
    }
  }

  if (user && user.is_locked) {
    if (to.path !== "/account-locked") {
      return { path: "/account-locked" };
    }
    return true;
  }

  if (user && isAuthLayout) {
    return getDefaultRouteForRole(user.role);
  }

  if (!user && requiresAuth) {
    return { path: "/login", query: { redirect: to.fullPath } };
  }

  if (
    user &&
    Array.isArray(to.meta.roles) &&
    to.meta.roles.length &&
    !hasAnyRole(user, to.meta.roles)
  ) {
    return getDefaultRouteForRole(user.role);
  }

  return true;
});

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} | QuizFlex` : "QuizFlex";
});

export default router;
