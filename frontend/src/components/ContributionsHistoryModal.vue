<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center"
    @keydown.esc="close"
  >
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="close" />

    <!-- Modal -->
    <div
      class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 p-6 max-h-[90vh] overflow-y-auto"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold">
          Historique des versements - {{ goal.name }}
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

      <!-- Résumé -->
      <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Nombre de versements
            </p>
            <p class="text-2xl font-bold">
              {{ contributions.length }}
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Total versé
            </p>
            <p class="text-2xl font-bold">
              {{ formatEuros(totalContributed) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Liste des contributions -->
      <div v-if="contributions.length > 0" class="space-y-3">
        <div
          v-for="contribution in sortedContributions"
          :key="contribution.id"
          class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="flex-1">
            <div class="flex items-center gap-3">
              <span class="text-lg font-semibold">
                {{ formatEuros(contribution.amountCents) }}
              </span>
              <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(contribution.contributionDate) }}
              </span>
            </div>
            <p
              v-if="contribution.note"
              class="text-sm text-gray-600 dark:text-gray-300 mt-1"
            >
              {{ contribution.note }}
            </p>
          </div>
          <button
            @click="handleDelete(contribution.id)"
            class="ml-4 text-red-500 hover:text-red-600 dark:text-red-400 p-2 rounded hover:bg-red-50 dark:hover:bg-red-900/20"
            title="Supprimer ce versement"
          >
            🗑
          </button>
        </div>
      </div>

      <div v-else class="text-center py-8 text-gray-500">
        Aucun versement enregistré
      </div>

      <!-- Bouton fermer -->
      <div class="mt-6">
        <button
          @click="close"
          class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg"
        >
          Fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { SavingsGoal } from "@/types";
import { useSavingsGoalsStore } from "@/stores/savingsGoals";

const props = defineProps<{
  goal: SavingsGoal;
}>();

const emit = defineEmits<{
  close: [];
  updated: [];
}>();

const store = useSavingsGoalsStore();

const contributions = computed(() => props.goal.contributions || []);

const sortedContributions = computed(() => {
  return [...contributions.value].sort((a, b) => {
    return (
      new Date(b.contributionDate).getTime() -
      new Date(a.contributionDate).getTime()
    );
  });
});

const totalContributed = computed(() => {
  return contributions.value.reduce(
    (sum, contrib) => sum + contrib.amountCents,
    0
  );
});

function formatEuros(cents: number): string {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
  }).format(cents / 100);
}

function formatDate(dateString: string): string {
  return new Intl.DateTimeFormat("fr-FR", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(new Date(dateString));
}

async function handleDelete(contributionId: number) {
  if (
    !confirm(
      "Êtes-vous sûr de vouloir supprimer ce versement ? Le montant sera déduit de l'objectif."
    )
  ) {
    return;
  }

  try {
    await store.deleteContribution(props.goal.id, contributionId);
    emit("updated");
    close();
  } catch (error) {
    console.error("Error deleting contribution:", error);
  }
}

function close() {
  emit("close");
}
</script>
