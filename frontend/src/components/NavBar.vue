<template>
  <nav
    class="fixed inset-x-0 top-0 z-50 border-b border-gray-200/60 bg-gradient-to-r from-white/90 via-primary-50/70 to-white/90 backdrop-blur dark:from-gray-900/80 dark:via-gray-900/80 dark:to-gray-900/80 dark:border-gray-700/50"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <div class="flex items-center gap-8">
          <router-link
            to="/"
            class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900 transition hover:text-primary-600 dark:text-white"
          >
            <span
              class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600 text-base font-bold text-white shadow-inner shadow-primary-900/40"
            >
              BM
            </span>
            <span>Budget Manager</span>
          </router-link>

          <div class="hidden items-center space-x-1 md:flex">
            <router-link
              v-for="link in visibleLinks"
              :key="link.to"
              :to="link.to"
              class="nav-link"
            >
              {{ link.label }}
            </router-link>
          </div>
        </div>

        <div class="hidden items-center space-x-6 md:flex">
          <NotificationBell />
          <div class="text-right">
            <p class="text-xs uppercase tracking-wide text-gray-400">Connecté</p>
            <router-link
              to="/profile"
              class="block text-sm font-medium text-gray-600 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-300"
            >
              {{ authStore.user?.name }}
            </router-link>
          </div>
          <button
            @click="handleLogout"
            class="rounded-full border border-transparent bg-gray-900/90 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
          >
            Déconnexion
          </button>
        </div>

        <div class="flex items-center gap-4 md:hidden">
          <NotificationBell />
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white/80 p-2 text-gray-500 transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-800"
            @click="toggleMobileMenu"
            aria-label="Ouvrir le menu"
          >
            <svg
              v-if="!isMobileMenuOpen"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              class="h-5 w-5"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              class="h-5 w-5"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div
        v-if="isMobileMenuOpen"
        class="border-t border-gray-200/60 bg-white/95 px-4 py-4 shadow-lg dark:border-gray-700/60 dark:bg-gray-900/95 md:hidden"
      >
        <div class="space-y-4">
          <router-link
            v-for="link in visibleLinks"
            :key="`mobile-${link.to}`"
            :to="link.to"
            class="block rounded-lg px-4 py-3 text-base font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700 dark:text-gray-200 dark:hover:bg-gray-800"
          >
            {{ link.label }}
          </router-link>

          <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/70">
            <p class="text-sm text-gray-500 dark:text-gray-300">
              {{ authStore.user?.name }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <router-link
                to="/profile"
                class="flex-1 rounded-full border border-primary-200 px-4 py-2 text-center text-sm font-medium text-primary-700 transition hover:bg-primary-600 hover:text-white dark:border-primary-700 dark:text-primary-300"
              >
                Profil
              </router-link>
              <button
                @click="handleLogout"
                class="flex-1 rounded-full border border-transparent bg-gray-900/90 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600"
              >
                Déconnexion
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </nav>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import NotificationBell from "./NotificationBell.vue";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const isMobileMenuOpen = ref(false);

const baseLinks = [
  { to: "/", label: "Dashboard" },
  { to: "/budgets-compare", label: "Comparer budgets" },
  { to: "/patrimoine", label: "Patrimoine" },
  { to: "/epargne", label: "Épargne" },
  { to: "/templates", label: "Templates" },
  { to: "/tags", label: "Tags" },
  { to: "/recurring-expenses", label: "Dépenses récurrentes" },
];

const visibleLinks = computed(() => {
  if (authStore.user?.role?.label === "admin") {
    return [...baseLinks, { to: "/admin/users", label: "Gestion utilisateurs" }];
  }
  return baseLinks;
});

function toggleMobileMenu() {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
}

watch(
  () => route.fullPath,
  () => {
    isMobileMenuOpen.value = false;
  },
);

async function handleLogout() {
  await authStore.logout();
  router.push("/login");
}
</script>

<style scoped>
.nav-link {
  @apply relative inline-flex items-center rounded-full px-4 py-2 text-sm font-medium text-gray-600 transition-all duration-200 ease-out hover:text-primary-700 dark:text-gray-300 dark:hover:text-primary-300;
}

.nav-link::after {
  content: "";
  position: absolute;
  left: 1rem;
  right: 1rem;
  bottom: 0.3rem;
  height: 2px;
  background: linear-gradient(90deg, #2563eb, #7c3aed);
  opacity: 0;
  transform: scaleX(0.3);
  transition: opacity 150ms ease, transform 150ms ease;
}
.router-link-active.nav-link {
  @apply text-primary-700 dark:text-primary-300;
}
.router-link-active.nav-link::after,
.nav-link:hover::after {
  opacity: 1;
  transform: scaleX(1);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 200ms ease, transform 200ms ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
