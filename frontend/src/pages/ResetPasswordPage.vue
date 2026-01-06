<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
    <div class="max-w-md w-full">
      <div class="card">
        <div class="text-center mb-6">
          <h2 class="text-3xl font-bold">Réinitialiser votre mot de passe</h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Entrez votre nouveau mot de passe ci-dessous.
          </p>
        </div>

        <div
          v-if="resetSuccess"
          class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"
        >
          <div class="flex items-start">
            <svg
              class="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5 mr-3"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              />
            </svg>
            <div>
              <p class="text-sm font-medium text-green-800 dark:text-green-200">
                Mot de passe réinitialisé avec succès !
              </p>
              <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                Vous allez être redirigé vers la page de connexion...
              </p>
            </div>
          </div>
        </div>

        <div
          v-if="invalidToken"
          class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"
        >
          <div class="flex items-start">
            <svg
              class="h-5 w-5 text-red-600 dark:text-red-400 mt-0.5 mr-3"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clip-rule="evenodd"
              />
            </svg>
            <div>
              <p class="text-sm font-medium text-red-800 dark:text-red-200">
                Lien invalide ou expiré
              </p>
              <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                Ce lien de réinitialisation n'est pas valide ou a expiré. Veuillez demander un
                nouveau lien.
              </p>
            </div>
          </div>
        </div>

        <form v-if="!resetSuccess && !invalidToken" @submit="onSubmit" class="space-y-4">
          <FormInput
            id="email"
            v-model="emailValue"
            type="email"
            label="Email"
            :error="errors.email"
            disabled
            class="opacity-75"
          />

          <FormInput
            id="password"
            v-model="password"
            v-bind="passwordAttrs"
            type="password"
            label="Nouveau mot de passe"
            placeholder="••••••••"
            :error="errors.password"
            hint="Au moins 8 caractères"
            required
            autocomplete="new-password"
          />

          <FormInput
            id="passwordConfirmation"
            v-model="passwordConfirmation"
            v-bind="passwordConfirmationAttrs"
            type="password"
            label="Confirmer le mot de passe"
            placeholder="••••••••"
            :error="errors.passwordConfirmation"
            required
            autocomplete="new-password"
          />

          <FormButton
            type="submit"
            variant="primary"
            size="md"
            :loading="isSubmitting"
            loading-text="Réinitialisation..."
            full-width
          >
            Réinitialiser le mot de passe
          </FormButton>
        </form>

        <div v-if="invalidToken" class="space-y-3">
          <FormButton variant="primary" size="md" full-width @click="goToForgotPassword">
            Demander un nouveau lien
          </FormButton>
        </div>

        <div class="mt-6 text-center">
          <router-link
            to="/login"
            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
          >
            ← Retour à la connexion
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import { resetPasswordSchema } from "@/schemas/password-reset";
import { passwordResetApi } from "@/api/password-reset";
import { errorHandler } from "@/utils/errorHandler";
import { useToast } from "@/composables/useToast";
import FormInput from "@/components/FormInput.vue";
import FormButton from "@/components/FormButton.vue";

const router = useRouter();
const route = useRoute();
const toast = useToast();

const resetSuccess = ref(false);
const invalidToken = ref(false);
const tokenValue = ref("");
const emailValue = ref("");

const { errors, defineField, handleSubmit, isSubmitting, setFieldValue } = useForm({
  validationSchema: toTypedSchema(resetPasswordSchema),
});

const [password, passwordAttrs] = defineField("password");
const [passwordConfirmation, passwordConfirmationAttrs] = defineField("passwordConfirmation");

onMounted(() => {
  // Récupérer le token et l'email depuis l'URL
  const token = route.query.token as string;
  const email = route.query.email as string;

  if (!token || !email) {
    invalidToken.value = true;
    return;
  }

  tokenValue.value = token;
  emailValue.value = email;

  // Pré-remplir les champs cachés
  setFieldValue("token", token);
  setFieldValue("email", email);
});

const onSubmit = handleSubmit(async (values) => {
  try {
    await passwordResetApi.resetPassword({
      email: values.email,
      token: values.token,
      password: values.password,
      passwordConfirmation: values.passwordConfirmation,
    });

    resetSuccess.value = true;

    // Rediriger vers la page de connexion après 3 secondes
    setTimeout(() => {
      router.push("/login");
    }, 3000);
  } catch (error: any) {
    // Vérifier si le token est invalide ou expiré
    if (
      error.response?.status === 422 &&
      (error.response?.data?.errors?.token || error.response?.data?.errors?.email)
    ) {
      invalidToken.value = true;
    } else {
      errorHandler.handle(error, "Impossible de réinitialiser le mot de passe.");
    }
  }
});

const goToForgotPassword = () => {
  router.push("/forgot-password");
};
</script>
