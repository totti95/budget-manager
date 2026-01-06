<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
    <div class="max-w-md w-full">
      <div class="card">
        <div class="text-center mb-6">
          <h2 class="text-3xl font-bold">Mot de passe oublié ?</h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Entrez votre email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
          </p>
        </div>

        <div
          v-if="emailSent"
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
                Email envoyé avec succès !
              </p>
              <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                Vérifiez votre boîte de réception et cliquez sur le lien pour réinitialiser votre
                mot de passe.
              </p>
            </div>
          </div>
        </div>

        <form v-if="!emailSent" @submit="onSubmit" class="space-y-4">
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

          <FormButton
            type="submit"
            variant="primary"
            size="md"
            :loading="isSubmitting"
            loading-text="Envoi en cours..."
            full-width
          >
            Envoyer le lien de réinitialisation
          </FormButton>
        </form>

        <div v-else class="space-y-3">
          <FormButton variant="secondary" size="md" full-width @click="resetForm">
            Envoyer un autre email
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
import { ref } from "vue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import { forgotPasswordSchema } from "@/schemas/password-reset";
import { passwordResetApi } from "@/api/password-reset";
import { errorHandler } from "@/utils/errorHandler";
import FormInput from "@/components/FormInput.vue";
import FormButton from "@/components/FormButton.vue";

const emailSent = ref(false);

const {
  errors,
  defineField,
  handleSubmit,
  isSubmitting,
  resetForm: veeResetForm,
} = useForm({
  validationSchema: toTypedSchema(forgotPasswordSchema),
});

const [email, emailAttrs] = defineField("email");

const onSubmit = handleSubmit(async (values) => {
  try {
    await passwordResetApi.forgotPassword(values);
    emailSent.value = true;
  } catch (error) {
    errorHandler.handle(error, "Impossible d'envoyer l'email. Veuillez vérifier l'adresse saisie.");
  }
});

const resetForm = () => {
  emailSent.value = false;
  veeResetForm();
};
</script>
