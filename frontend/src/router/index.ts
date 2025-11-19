import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/login",
      name: "login",
      component: () => import("@/pages/LoginPage.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/register",
      name: "register",
      component: () => import("@/pages/RegisterPage.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/forgot-password",
      name: "forgot-password",
      component: () => import("@/pages/ForgotPasswordPage.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/reset-password",
      name: "reset-password",
      component: () => import("@/pages/ResetPasswordPage.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/",
      name: "dashboard",
      component: () => import("@/pages/DashboardPage.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/budgets/:month",
      name: "budget-details",
      component: () => import("@/pages/BudgetDetailsPage.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/patrimoine",
      name: "assets",
      component: () => import("@/pages/AssetsPage.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/templates",
      name: "templates",
      component: () => import("@/pages/TemplatesPage.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/profile",
      name: "profile",
      component: () => import("@/pages/ProfilePage.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/admin/users",
      name: "admin-users",
      component: () => import("@/pages/AdminUsersPage.vue"),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
  ],
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Si on a un token mais pas d'utilisateur, charger l'utilisateur d'abord
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser();
    } catch (error) {
      // Token invalide, nettoyer et rediriger vers login
      authStore.logout();
    }
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: "login" });
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next({ name: "dashboard" });
  } else if (to.meta.requiresAdmin && authStore.user?.role?.label !== "admin") {
    // Rediriger vers dashboard si non-admin tente d'accéder aux pages admin
    next({ name: "dashboard" });
  } else {
    next();
  }
});

export default router;
