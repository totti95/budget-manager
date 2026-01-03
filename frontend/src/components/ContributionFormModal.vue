<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center"
    @keydown.esc="close"
  >
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="close" />

    <!-- Modal -->
    <div
      class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold">
          Ajouter une contribution - {{ goal.name }}
        </h3>
        <button
          type="button"
          @click="close"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label for="amount" class="block text-sm font-medium mb-1"
            >Montant (€)</label
          >
          <input
            id="amount"
            v-model="amountEuros"
            type="number"
            step="0.01"
            min="0.01"
            required
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            placeholder="Ex: 100"
          />
        </div>

        <div>
          <label for="date" class="block text-sm font-medium mb-1"
            >Date de contribution</label
          >
          <input
            id="date"
            v-model="contributionDate"
            type="date"
            required
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
          />
        </div>

        <div>
          <label for="note" class="block text-sm font-medium mb-1"
            >Note (optionnel)</label
          >
          <input
            id="note"
            v-model="note"
            type="text"
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            placeholder="Ex: Versement mensuel"
          />
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            @click="close"
            class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            {{ loading ? "Ajout..." : "Ajouter" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { SavingsGoal } from "@/types";
import { useSavingsGoalsStore } from "@/stores/savingsGoals";

const props = defineProps<{
  goal: SavingsGoal;
}>();

const emit = defineEmits<{
  close: [];
  saved: [];
}>();

const store = useSavingsGoalsStore();
const amountEuros = ref("");
const contributionDate = ref(
  new Date().toISOString().split("T")[0]
);
const note = ref("");
const loading = ref(false);

function close() {
  emit("close");
}

async function handleSubmit() {
  const amount = parseFloat(amountEuros.value);
  if (isNaN(amount) || amount <= 0) {
    alert("Montant invalide");
    return;
  }

  loading.value = true;
  try {
    await store.addContribution(props.goal.id, {
      amountCents: Math.round(amount * 100),
      contributionDate: contributionDate.value,
      note: note.value || null,
    });
    emit("saved");
  } catch (error) {
    console.error("Error adding contribution:", error);
  } finally {
    loading.value = false;
  }
}
</script>
