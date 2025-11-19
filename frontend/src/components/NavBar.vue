<template>
  <nav
    class="bg-white dark:bg-gray-800 shadow-lg fixed top-0 left-0 right-0 z-50"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center space-x-8">
          <router-link to="/" class="text-xl font-bold text-primary-600">
            Budget Manager
          </router-link>

          <div class="hidden md:flex space-x-4">
            <router-link to="/" class="nav-link">Dashboard</router-link>
            <router-link to="/patrimoine" class="nav-link"
              >Patrimoine</router-link
            >
            <router-link to="/epargne" class="nav-link">Épargne</router-link>
            <router-link to="/templates" class="nav-link"
              >Templates</router-link
            >
            <router-link
              v-if="authStore.user?.role?.label === 'admin'"
              to="/admin/users"
              class="nav-link"
            >
              Gestion utilisateurs
            </router-link>
          </div>
        </div>

        <div class="flex items-center space-x-4">
          <router-link
            to="/profile"
            class="text-sm text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
          >
            {{ authStore.user?.name }}
          </router-link>
          <button @click="handleLogout" class="btn btn-secondary text-sm">
            Déconnexion
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

async function handleLogout() {
  await authStore.logout();
  router.push("/login");
}
</script>

<style scoped>
.nav-link {
  @apply px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}
.router-link-active.nav-link {
  @apply bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300;
}
</style>
