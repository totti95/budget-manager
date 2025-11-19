<template>
  <div class="change-password-form">
    <h3 class="text-lg font-semibold mb-4">Changer le mot de passe</h3>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label for="currentPassword" class="block text-sm font-medium mb-1">
          Mot de passe actuel
        </label>
        <input
          id="currentPassword"
          v-model="form.currentPassword"
          type="password"
          required
          class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{
            'border-red-500': errors.currentPassword,
          }"
        />
        <p v-if="errors.currentPassword" class="text-red-600 text-sm mt-1">
          {{ errors.currentPassword }}
        </p>
      </div>

      <div>
        <label for="newPassword" class="block text-sm font-medium mb-1">
          Nouveau mot de passe
        </label>
        <input
          id="newPassword"
          v-model="form.newPassword"
          type="password"
          required
          minlength="8"
          class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{
            'border-red-500': errors.newPassword,
          }"
        />
        <p v-if="errors.newPassword" class="text-red-600 text-sm mt-1">
          {{ errors.newPassword }}
        </p>
        <p class="text-gray-500 text-xs mt-1">
          Minimum 8 caractères
        </p>
      </div>

      <div>
        <label
          for="newPasswordConfirmation"
          class="block text-sm font-medium mb-1"
        >
          Confirmer le nouveau mot de passe
        </label>
        <input
          id="newPasswordConfirmation"
          v-model="form.newPasswordConfirmation"
          type="password"
          required
          class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{
            'border-red-500': errors.newPasswordConfirmation,
          }"
        />
        <p
          v-if="errors.newPasswordConfirmation"
          class="text-red-600 text-sm mt-1"
        >
          {{ errors.newPasswordConfirmation }}
        </p>
      </div>

      <div v-if="generalError" class="text-red-600 text-sm">
        {{ generalError }}
      </div>

      <div v-if="successMessage" class="text-green-600 text-sm">
        {{ successMessage }}
      </div>

      <div class="flex gap-3">
        <button
          type="submit"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? "Modification..." : "Modifier le mot de passe" }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { authApi } from "@/api/auth";

const form = ref({
  currentPassword: "",
  newPassword: "",
  newPasswordConfirmation: "",
});

const errors = ref<Record<string, string>>({});
const generalError = ref("");
const successMessage = ref("");
const loading = ref(false);

const resetMessages = () => {
  errors.value = {};
  generalError.value = "";
  successMessage.value = "";
};

const handleSubmit = async () => {
  resetMessages();

  // Client-side validation
  if (form.value.newPassword.length < 8) {
    errors.value.newPassword = "Le mot de passe doit contenir au moins 8 caractères";
    return;
  }

  if (form.value.newPassword !== form.value.newPasswordConfirmation) {
    errors.value.newPasswordConfirmation = "Les mots de passe ne correspondent pas";
    return;
  }

  loading.value = true;

  try {
    const response = await authApi.updatePassword({
      currentPassword: form.value.currentPassword,
      newPassword: form.value.newPassword,
      newPasswordConfirmation: form.value.newPasswordConfirmation,
    });

    successMessage.value = response.message;

    // Reset form
    form.value = {
      currentPassword: "",
      newPassword: "",
      newPasswordConfirmation: "",
    };
  } catch (err: any) {
    if (err.response?.status === 422) {
      // Validation errors
      const validationErrors = err.response.data.errors;
      if (validationErrors) {
        Object.keys(validationErrors).forEach((key) => {
          errors.value[key] = validationErrors[key][0];
        });
      }
    } else {
      generalError.value =
        err.response?.data?.message || "Une erreur est survenue";
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.change-password-form {
  background: white;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  max-width: 500px;
}
</style>
