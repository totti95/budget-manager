<template>
  <div>
    <!-- Loading State -->
    <div
      v-if="notificationsStore.loading && !notificationsStore.settings"
      class="text-center py-8"
    >
      <div
        class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"
      ></div>
    </div>

    <!-- Settings Form -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Budget Exceeded Alerts -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Alertes de dépassement budgétaire
          </label>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="formData.budgetExceededEnabled"
              class="sr-only peer"
            />
            <div
              class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"
            ></div>
          </label>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Recevoir une notification lorsqu'une sous-catégorie atteint ou dépasse
          le seuil défini
        </p>
      </div>

      <!-- Threshold Slider -->
      <div v-if="formData.budgetExceededEnabled">
        <label
          class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
        >
          Seuil d'alerte : {{ formData.budgetExceededThresholdPercent }}%
        </label>
        <input
          type="range"
          v-model.number="formData.budgetExceededThresholdPercent"
          min="50"
          max="150"
          step="5"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
        <div
          class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1"
        >
          <span>50%</span>
          <span>75%</span>
          <span>100%</span>
          <span>125%</span>
          <span>150%</span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
          Vous serez alerté lorsque les dépenses atteignent
          <strong>{{ formData.budgetExceededThresholdPercent }}%</strong> du
          budget prévu
        </p>
      </div>

      <!-- Savings Goal Alerts (for future feature) -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Alertes d'objectifs d'épargne
          </label>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="formData.savingsGoalEnabled"
              class="sr-only peer"
            />
            <div
              class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"
            ></div>
          </label>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Recevoir une notification lorsqu'un objectif d'épargne est atteint (à
          venir)
        </p>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="notificationsStore.loading"
          class="btn btn-primary"
        >
          <span v-if="notificationsStore.loading">Enregistrement...</span>
          <span v-else>Enregistrer les paramètres</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, watch } from "vue";
import { useNotificationsStore } from "@/stores/notifications";
import { useToast } from "@/composables/useToast";

const notificationsStore = useNotificationsStore();
const toast = useToast();

const formData = reactive({
  budgetExceededEnabled: true,
  budgetExceededThresholdPercent: 100,
  savingsGoalEnabled: true,
});

// Watch for settings changes
watch(
  () => notificationsStore.settings,
  (settings) => {
    if (settings) {
      formData.budgetExceededEnabled = settings.budgetExceededEnabled;
      formData.budgetExceededThresholdPercent =
        settings.budgetExceededThresholdPercent;
      formData.savingsGoalEnabled = settings.savingsGoalEnabled;
    }
  },
  { immediate: true }
);

async function handleSubmit() {
  try {
    await notificationsStore.updateSettings(formData);
    toast.success("Paramètres de notification mis à jour");
  } catch (error) {
    // Error handled by store
  }
}

onMounted(() => {
  if (!notificationsStore.settings) {
    notificationsStore.fetchSettings();
  }
});
</script>

<style scoped>
/* Custom range slider styling */
input[type="range"]::-webkit-slider-thumb {
  appearance: none;
  width: 20px;
  height: 20px;
  background: #2563eb;
  cursor: pointer;
  border-radius: 50%;
}

input[type="range"]::-moz-range-thumb {
  width: 20px;
  height: 20px;
  background: #2563eb;
  cursor: pointer;
  border-radius: 50%;
  border: none;
}
</style>
