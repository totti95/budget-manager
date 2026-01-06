<script setup lang="ts">
import { ref, computed } from "vue";
import { budgetsApi } from "@/api/budgets";
import type { BudgetComparison } from "@/types";
import BudgetComparisonTable from "@/components/BudgetComparisonTable.vue";
import BudgetComparisonChart from "@/components/BudgetComparisonChart.vue";

const selectedMonths = ref<string[]>([]);
const comparison = ref<BudgetComparison | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

// Generate available months for selection (last 12 months)
const availableMonths = computed(() => {
  const months: { value: string; label: string }[] = [];
  const today = new Date();

  for (let i = 0; i < 12; i++) {
    const date = new Date(today.getFullYear(), today.getMonth() - i, 1);
    const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
    const label = date.toLocaleDateString("fr-FR", {
      month: "long",
      year: "numeric",
    });
    months.push({ value, label });
  }

  return months;
});

const canCompare = computed(() => {
  return selectedMonths.value.length >= 2 && selectedMonths.value.length <= 3;
});

function toggleMonth(month: string) {
  const index = selectedMonths.value.indexOf(month);

  if (index > -1) {
    selectedMonths.value.splice(index, 1);
  } else {
    if (selectedMonths.value.length < 3) {
      selectedMonths.value.push(month);
    }
  }
}

async function comparebudgets() {
  if (!canCompare.value) return;

  loading.value = true;
  error.value = null;

  try {
    // Sort months chronologically (oldest to newest)
    const sortedMonths = [...selectedMonths.value].sort((a, b) => a.localeCompare(b));
    comparison.value = await budgetsApi.compare(sortedMonths);
  } catch (e: any) {
    error.value = e.response?.data?.message || "Erreur lors de la comparaison des budgets";
    comparison.value = null;
  } finally {
    loading.value = false;
  }
}

function clearComparison() {
  selectedMonths.value = [];
  comparison.value = null;
  error.value = null;
}

function centsToEuros(cents: number): string {
  return (cents / 100).toFixed(2);
}
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Comparaison de Budgets</h1>

    <!-- Selection des mois -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">Sélectionnez 2 ou 3 mois à comparer</h2>

      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-4">
        <button
          v-for="month in availableMonths"
          :key="month.value"
          @click="toggleMonth(month.value)"
          :class="[
            'px-4 py-2 rounded-md border-2 transition-colors capitalize',
            selectedMonths.includes(month.value)
              ? 'border-blue-600 bg-blue-50 text-blue-700 font-semibold'
              : 'border-gray-300 bg-white text-gray-700 hover:border-blue-400',
            selectedMonths.length >= 3 && !selectedMonths.includes(month.value)
              ? 'opacity-50 cursor-not-allowed'
              : 'cursor-pointer',
          ]"
          :disabled="selectedMonths.length >= 3 && !selectedMonths.includes(month.value)"
        >
          {{ month.label }}
        </button>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="comparebudgets"
          :disabled="!canCompare || loading"
          class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
        >
          <span v-if="loading">Chargement...</span>
          <span v-else>Comparer</span>
        </button>

        <button
          v-if="selectedMonths.length > 0"
          @click="clearComparison"
          class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-100 transition-colors"
        >
          Réinitialiser
        </button>

        <p class="text-sm text-gray-600">
          {{ selectedMonths.length }} mois sélectionné{{ selectedMonths.length > 1 ? "s" : "" }}
        </p>
      </div>
    </div>

    <!-- Error message -->
    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
      {{ error }}
    </div>

    <!-- Comparison results -->
    <div v-if="comparison && comparison.budgets.length > 0">
      <!-- Cartes de statistiques -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div
          v-for="budget in comparison.budgets"
          :key="budget.id"
          class="bg-white rounded-lg shadow-md p-6"
        >
          <h3 class="text-lg font-semibold mb-3 capitalize">
            {{
              new Date(budget.month).toLocaleDateString("fr-FR", { month: "long", year: "numeric" })
            }}
          </h3>

          <div v-if="budget.stats" class="space-y-2">
            <div>
              <p class="text-sm text-gray-600">Budget prévu</p>
              <p class="text-xl font-bold text-gray-800">
                {{ centsToEuros(budget.stats.totalPlannedCents) }} €
              </p>
            </div>

            <div>
              <p class="text-sm text-gray-600">Dépenses réelles</p>
              <p
                class="text-xl font-bold"
                :class="
                  budget.stats.totalActualCents > budget.stats.totalPlannedCents
                    ? 'text-red-600'
                    : 'text-green-600'
                "
              >
                {{ centsToEuros(budget.stats.totalActualCents) }} €
              </p>
            </div>

            <div>
              <p class="text-sm text-gray-600">Différence</p>
              <p
                class="text-lg font-semibold"
                :class="budget.stats.varianceCents > 0 ? 'text-red-600' : 'text-green-600'"
              >
                {{ budget.stats.varianceCents > 0 ? "+" : ""
                }}{{ centsToEuros(budget.stats.varianceCents) }} € ({{
                  budget.stats.variancePercent > 0 ? "+" : ""
                }}{{ budget.stats.variancePercent }}%)
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Table comparative -->
      <BudgetComparisonTable :comparison="comparison" class="mb-6" />

      <!-- Graphiques -->
      <BudgetComparisonChart :comparison="comparison" />
    </div>

    <!-- Empty state -->
    <div v-else-if="!loading" class="bg-white rounded-lg shadow-md p-12 text-center">
      <svg
        class="mx-auto h-16 w-16 text-gray-400 mb-4"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
        />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune comparaison</h3>
      <p class="text-gray-600">Sélectionnez 2 ou 3 mois ci-dessus pour comparer vos budgets.</p>
    </div>
  </div>
</template>
