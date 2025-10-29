<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4"
  >
    <div class="max-w-md w-full">
      <div class="card">
        <h2 class="text-3xl font-bold text-center mb-6">Connexion</h2>

        <form @submit="onSubmit" class="space-y-4">
          <FormInput
            id="email"
            v-model="email"
            v-bind="emailAttrs"
            type="email"
            label="Email"
            placeholder="votre@email.com"
            :error="errors.email"
            required
            autocomplete="email"
          />

          <FormInput
            id="password"
            v-model="password"
            v-bind="passwordAttrs"
            type="password"
            label="Mot de passe"
            placeholder="••••••••"
            :error="errors.password"
            required
            autocomplete="current-password"
          />

          <div class="flex items-center justify-end mb-4">
            <router-link
              to="/forgot-password"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
              Mot de passe oublié ?
            </router-link>
          </div>

          <FormButton
            type="submit"
            variant="primary"
            size="md"
            :loading="authStore.loading || isSubmitting"
            loading-text="Connexion en cours..."
            full-width
          >
            Se connecter
          </FormButton>
        </form>

        <div class="mt-4 text-center text-sm">
          <router-link
            to="/register"
            class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
          >
            Pas encore de compte ? S'inscrire
          </router-link>
        </div>

        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm border border-blue-200 dark:border-blue-800">
          <p class="font-semibold mb-2 text-blue-900 dark:text-blue-100">Compte de démo :</p>
          <p class="text-blue-800 dark:text-blue-200">Email: demo@budgetmanager.local</p>
          <p class="text-blue-800 dark:text-blue-200">Mot de passe: password</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import { loginSchema } from "@/schemas/auth";
import { errorHandler } from "@/utils/errorHandler";
import FormInput from "@/components/FormInput.vue";
import FormButton from "@/components/FormButton.vue";

const router = useRouter();
const authStore = useAuthStore();

const { errors, defineField, handleSubmit, isSubmitting } = useForm({
  validationSchema: toTypedSchema(loginSchema),
});

const [email, emailAttrs] = defineField("email");
const [password, passwordAttrs] = defineField("password");

const onSubmit = handleSubmit(async (values) => {
  try {
    await authStore.login(values);
    router.push("/");
  } catch (error) {
    errorHandler.handle(error, "Identifiants invalides. Veuillez réessayer.");
  }
});
</script>
