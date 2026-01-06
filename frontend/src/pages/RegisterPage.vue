<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
    <div class="max-w-md w-full">
      <div class="card">
        <h2 class="text-3xl font-bold text-center mb-6">Inscription</h2>

        <form @submit="onSubmit" class="space-y-4">
          <FormInput
            id="name"
            v-model="name"
            v-bind="nameAttrs"
            type="text"
            label="Nom"
            placeholder="Votre nom"
            :error="errors.name"
            required
            autocomplete="name"
          />

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
            hint="Au moins 8 caractères"
            required
            autocomplete="new-password"
          />

          <FormInput
            id="password_confirmation"
            v-model="password_confirmation"
            v-bind="password_confirmationAttrs"
            type="password"
            label="Confirmer le mot de passe"
            placeholder="••••••••"
            :error="errors.password_confirmation"
            required
            autocomplete="new-password"
          />

          <FormButton
            type="submit"
            variant="primary"
            size="md"
            :loading="authStore.loading || isSubmitting"
            loading-text="Inscription en cours..."
            full-width
          >
            S'inscrire
          </FormButton>
        </form>

        <div class="mt-4 text-center text-sm">
          <router-link
            to="/login"
            class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
          >
            Déjà un compte ? Se connecter
          </router-link>
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
import { registerSchema } from "@/schemas/auth";
import { errorHandler } from "@/utils/errorHandler";
import FormInput from "@/components/FormInput.vue";
import FormButton from "@/components/FormButton.vue";

const router = useRouter();
const authStore = useAuthStore();

const { errors, defineField, handleSubmit, isSubmitting } = useForm({
  validationSchema: toTypedSchema(registerSchema),
});

const [name, nameAttrs] = defineField("name");
const [email, emailAttrs] = defineField("email");
const [password, passwordAttrs] = defineField("password");
const [password_confirmation, password_confirmationAttrs] = defineField("password_confirmation");

const onSubmit = handleSubmit(async (values) => {
  try {
    await authStore.register(values);
    router.push("/");
  } catch (error) {
    errorHandler.handle(error, "Erreur lors de l'inscription. Veuillez réessayer.");
  }
});
</script>
