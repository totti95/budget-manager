<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4"
  >
    <div class="max-w-md w-full">
      <div class="card">
        <h2 class="text-3xl font-bold text-center mb-6">Inscription</h2>

        <form @submit="onSubmit" class="space-y-4">
          <div>
            <label for="name" class="label">Nom</label>
            <input
              id="name"
              v-model="name"
              v-bind="nameAttrs"
              type="text"
              class="input"
              :class="{ 'border-red-500': errors.name }"
              placeholder="Votre nom"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">
              {{ errors.name }}
            </p>
          </div>

          <div>
            <label for="email" class="label">Email</label>
            <input
              id="email"
              v-model="email"
              v-bind="emailAttrs"
              type="email"
              class="input"
              :class="{ 'border-red-500': errors.email }"
              placeholder="votre@email.com"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">
              {{ errors.email }}
            </p>
          </div>

          <div>
            <label for="password" class="label">Mot de passe</label>
            <input
              id="password"
              v-model="password"
              v-bind="passwordAttrs"
              type="password"
              class="input"
              :class="{ 'border-red-500': errors.password }"
              placeholder="••••••••"
            />
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>

          <div>
            <label for="password_confirmation" class="label"
              >Confirmer le mot de passe</label
            >
            <input
              id="password_confirmation"
              v-model="password_confirmation"
              v-bind="password_confirmationAttrs"
              type="password"
              class="input"
              :class="{ 'border-red-500': errors.password_confirmation }"
              placeholder="••••••••"
            />
            <p
              v-if="errors.password_confirmation"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.password_confirmation }}
            </p>
          </div>

          <div v-if="authStore.error" class="text-red-600 text-sm">
            {{ authStore.error }}
          </div>

          <button
            type="submit"
            :disabled="authStore.loading || isSubmitting"
            class="w-full btn btn-primary"
          >
            {{
              authStore.loading || isSubmitting
                ? "Inscription..."
                : "S'inscrire"
            }}
          </button>
        </form>

        <div class="mt-4 text-center text-sm">
          <router-link
            to="/login"
            class="text-primary-600 hover:text-primary-700"
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

const router = useRouter();
const authStore = useAuthStore();

const { errors, defineField, handleSubmit, isSubmitting } = useForm({
  validationSchema: toTypedSchema(registerSchema),
});

const [name, nameAttrs] = defineField("name");
const [email, emailAttrs] = defineField("email");
const [password, passwordAttrs] = defineField("password");
const [password_confirmation, password_confirmationAttrs] = defineField(
  "password_confirmation",
);

const onSubmit = handleSubmit(async (values) => {
  try {
    await authStore.register(values);
    router.push("/");
  } catch (error) {
    // Error is already handled in the store
  }
});
</script>
