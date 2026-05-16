import { createRouter, createWebHistory } from "vue-router";

const routes = [
  // AUTH
  {
    path: "/login",
    component: () => import("@/views/auth/Login.vue"),
    meta: {
      layout: "auth",
    },
  },

  // USER
  {
    path: "/",
    component: () => import("@/views/user/Home.vue"),
    meta: {
      layout: "user",
    },
  },
  {
    path: "/quiz/:id",
    component: () => import("@/views/user/Quiz.vue"),
    meta: {
      layout: "user",
    },
  },

  // ADMIN
  {
    path: "/admin",
    component: () => import("@/views/admin/Dashboard.vue"),
    meta: {
      layout: "admin",
    },
  },
  {
    path: "/admin/questions",
    component: () => import("@/views/admin/Question.vue"),
    meta: {
      layout: "admin",
    },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
