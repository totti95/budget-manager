<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold mb-4">Tableau de bord</h1>

      <div class="flex items-center gap-4 mb-6">
        <select
          v-model="selectedMonth"
          @change="loadBudget"
          class="input max-w-xs"
        >
          <option v-for="month in availableMonths" :key="month" :value="month">
            {{ formatMonth(month) }}
          </option>
        </select>

        <button
          v-if="!currentBudget"
          @click="handleGenerateBudget"
          class="btn btn-primary"
          :disabled="budgetStore.loading"
        >
          Générer le budget
        </button>
      </div>
    </div>

    <div v-if="budgetStore.loading" class="text-center py-12">
      <p>Chargement...</p>
    </div>

    <div v-else-if="currentBudget" class="space-y-6">
      <!-- Stats Summary -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="card">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Prévu</p>
          <p class="text-2xl font-bold">
            <MoneyDisplay :cents="statsStore.summary?.totalPlannedCents || 0" />
          </p>
        </div>

        <div class="card">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Réel</p>
          <p class="text-2xl font-bold">
            <MoneyDisplay :cents="statsStore.summary?.totalActualCents || 0" />
          </p>
        </div>

        <div class="card">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Économie</p>
          <p class="text-2xl font-bold">
            <MoneyDisplay
              :cents="-(statsStore.summary?.varianceCents || 0)"
              :colorize="true"
              :show-sign="true"
            />
          </p>
        </div>

        <div class="card">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Dépenses</p>
          <p class="text-2xl font-bold">
            {{ statsStore.summary?.expenseCount || 0 }}
          </p>
        </div>
      </div>

      <!-- Categories Table -->
      <div class="card">
        <h2 class="text-xl font-bold mb-4">Catégories</h2>
        <div class="overflow-x-auto">
          <table class="table">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th>Catégorie</th>
                <th>Prévu</th>
                <th>Réel</th>
                <th>Économie</th>
                <th>%</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="cat in statsStore.categoryStats" :key="cat.id">
                <td class="font-medium">{{ cat.name }}</td>
                <td><MoneyDisplay :cents="cat.plannedAmountCents" /></td>
                <td><MoneyDisplay :cents="cat.actualAmountCents" /></td>
                <td>
                  <MoneyDisplay
                    :cents="-cat.varianceCents"
                    :colorize="true"
                    :show-sign="true"
                  />
                </td>
                <td>
                  <span
                    v-if="cat.variancePercentage !== null"
                    :class="
                      -cat.varianceCents > 0 ? 'text-green-600' : 'text-red-600'
                    "
                  >
                    {{ (-cat.variancePercentage).toFixed(1) }}%
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex justify-center">
        <router-link :to="`/budgets/${selectedMonth}`" class="btn btn-primary">
          Voir le détail du budget
        </router-link>
      </div>
    </div>

    <div v-else class="card text-center py-12">
      <p class="text-gray-600 dark:text-gray-400 mb-4">
        Aucun budget pour ce mois
      </p>
      <button @click="handleGenerateBudget" class="btn btn-primary">
        Générer le budget
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useBudgetStore } from "@/stores/budget";
import { useStatsStore } from "@/stores/stats";
import MoneyDisplay from "@/components/MoneyDisplay.vue";

const budgetStore = useBudgetStore();
const statsStore = useStatsStore();

const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const currentBudget = computed(() => budgetStore.currentBudget);

const availableMonths = computed(() => {
  const months = [];
  const now = new Date();
  for (let i = -2; i <= 2; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() + i, 1);
    months.push(d.toISOString().slice(0, 7));
  }
  return months;
});

function formatMonth(month: string): string {
  const [year, monthNum] = month.split("-");
  const date = new Date(parseInt(year), parseInt(monthNum) - 1, 1);
  return date.toLocaleDateString("fr-FR", { year: "numeric", month: "long" });
}

async function loadBudget() {
  try {
    const response = await budgetStore.fetchBudgets(selectedMonth.value);
    if (response.data.length > 0) {
      await budgetStore.fetchBudget(response.data[0].id);
      await statsStore.fetchSummary(response.data[0].id);
      await statsStore.fetchCategoryStats(response.data[0].id);
    } else {
      budgetStore.currentBudget = null;
    }
  } catch (error) {
    console.error("Error loading budget:", error);
  }
}

async function handleGenerateBudget() {
  try {
    const budget = await budgetStore.generateBudget(selectedMonth.value);
    await statsStore.fetchSummary(budget.id);
    await statsStore.fetchCategoryStats(budget.id);
  } catch (error) {
    console.error("Error generating budget:", error);
  }
}

onMounted(() => {
  loadBudget();
});
</script>
