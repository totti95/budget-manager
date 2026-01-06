<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-8">Mon Profil</h1>

    <div class="space-y-6">
      <!-- User Information -->
      <div class="card">
        <h2 class="text-xl font-semibold mb-4">Informations personnelles</h2>
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"> Nom </label>
            <p class="text-gray-900">{{ authStore.user?.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"> Email </label>
            <p class="text-gray-900">{{ authStore.user?.email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"> Rôle </label>
            <p class="text-gray-900">
              {{ authStore.user?.role?.label === "admin" ? "Administrateur" : "Utilisateur" }}
            </p>
          </div>
        </div>
      </div>

      <!-- Change Password Form -->
      <ChangePasswordForm />

      <!-- Notification Settings -->
      <div class="card">
        <h2 class="text-xl font-semibold mb-4">Paramètres de notification</h2>
        <NotificationSettingsForm />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from "@/stores/auth";
import ChangePasswordForm from "@/components/ChangePasswordForm.vue";
import NotificationSettingsForm from "@/components/NotificationSettingsForm.vue";

const authStore = useAuthStore();
</script>
