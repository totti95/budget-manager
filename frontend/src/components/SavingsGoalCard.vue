<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow"
  >
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-semibold">{{ goal.name }}</h3>
        <p v-if="goal.description" class="text-sm text-gray-500 mt-1">
          {{ goal.description }}
        </p>
      </div>
      <span class="px-2 py-1 text-xs rounded-full" :class="statusClass">
        {{ statusLabel }}
      </span>
    </div>

    <!-- Montants -->
    <div class="flex justify-between items-baseline mb-2">
      <span class="text-2xl font-bold">{{
        formatEuros(goal.currentAmountCents)
      }}</span>
      <span class="text-gray-500"
        >/ {{ formatEuros(goal.targetAmountCents) }}</span
      >
    </div>

    <!-- Barre de progression -->
    <div class="mb-4">
      <div class="flex items-center justify-between mb-1">
        <span class="text-sm font-medium">{{ progressPercentage }}%</span>
        <span v-if="goal.targetDate" class="text-xs text-gray-500">
          {{ daysRemainingText }}
        </span>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
        <div
          class="h-3 rounded-full transition-all"
          :class="progressBarClass"
          :style="{ width: `${Math.min(progressPercentage, 100)}%` }"
        />
      </div>
    </div>

    <!-- Indicateur de rythme -->
    <div v-if="goal.targetDate && goal.status === 'active'" class="mb-4">
      <div class="flex items-center text-sm">
        <span v-if="isOnTrack" class="text-green-600 dark:text-green-400">
          ✓ Sur la bonne voie
        </span>
        <span v-else class="text-orange-600 dark:text-orange-400">
          ⚠ En retard
        </span>
      </div>
    </div>

    <!-- Montant mensuel suggéré -->
    <div
      v-if="suggestedMonthly && goal.status === 'active'"
      class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded"
    >
      <p class="text-sm">
        <span class="font-medium">Suggestion :</span>
        {{ formatEuros(suggestedMonthly) }}/mois
      </p>
    </div>

    <!-- Asset lié -->
    <div
      v-if="goal.asset"
      class="mb-4 flex items-center text-sm text-gray-600 dark:text-gray-400"
    >
      <span>🏦 {{ goal.asset.label }}</span>
      <button
        v-if="goal.status === 'active'"
        @click="$emit('sync-asset', goal)"
        class="ml-2 text-blue-600 hover:text-blue-700 text-xs"
      >
        Synchroniser
      </button>
    </div>

    <!-- Historique des contributions -->
    <div
      v-if="goal.contributions && goal.contributions.length > 0"
      class="mb-4"
    >
      <button
        @click="$emit('view-contributions', goal)"
        class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
      >
        📊 Voir l'historique ({{ goal.contributions.length }} versement{{
          goal.contributions.length > 1 ? "s" : ""
        }})
      </button>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button
        v-if="goal.status === 'active'"
        @click="$emit('add-contribution', goal)"
        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded"
      >
        + Verser
      </button>
      <button
        @click="$emit('edit', goal)"
        class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 py-2 px-4 rounded"
      >
        Modifier
      </button>
      <button
        @click="$emit('delete', goal)"
        class="bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-600 py-2 px-4 rounded"
      >
        🗑
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { SavingsGoal } from "@/types";

const props = defineProps<{
  goal: SavingsGoal;
}>();

defineEmits<{
  edit: [goal: SavingsGoal];
  delete: [goal: SavingsGoal];
  "add-contribution": [goal: SavingsGoal];
  "sync-asset": [goal: SavingsGoal];
  "view-contributions": [goal: SavingsGoal];
}>();

const progressPercentage = computed(() =>
  Math.round(props.goal.progressPercentage ?? 0)
);

const isOnTrack = computed(() => props.goal.isOnTrack ?? true);

const daysRemainingText = computed(() => {
  const days = props.goal.daysRemaining;
  if (days === null || days === undefined) return "";
  if (days < 0) return "Échéance dépassée";
  if (days === 0) return "Aujourd'hui";
  if (days === 1) return "1 jour restant";
  return `${days} jours restants`;
});

const suggestedMonthly = computed(() => {
  if (!props.goal.targetDate || props.goal.status !== "active")
    return null;
  return props.goal.suggestedMonthlyAmountCents;
});

const statusLabel = computed(() => {
  const labels = {
    active: "En cours",
    completed: "Atteint",
    abandoned: "Abandonné",
    paused: "En pause",
  };
  return labels[props.goal.status] || props.goal.status;
});

const statusClass = computed(() => {
  const classes = {
    active:
      "bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300",
    completed:
      "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300",
    abandoned:
      "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
    paused:
      "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300",
  };
  return classes[props.goal.status] || "";
});

const progressBarClass = computed(() => {
  if (props.goal.status === "completed") return "bg-green-500";
  if (progressPercentage.value >= 75) return "bg-green-500";
  if (progressPercentage.value >= 50) return "bg-yellow-500";
  return "bg-orange-500";
});

function formatEuros(cents: number): string {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(cents / 100);
}
</script>
