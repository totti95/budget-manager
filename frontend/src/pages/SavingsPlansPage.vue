<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-6">Plans d'épargne</h1>

    <!-- Summary Card -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Épargne prévue totale</p>
        <p class="text-2xl font-bold text-blue-600">
          <MoneyDisplay :cents="totalPlanned" />
        </p>
      </div>
      <div class="card text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Épargne réelle totale</p>
        <p class="text-2xl font-bold text-green-600">
          <MoneyDisplay :cents="totalActual" />
        </p>
      </div>
      <div class="card text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Écart</p>
        <p class="text-2xl font-bold" :class="variance >= 0 ? 'text-green-600' : 'text-red-600'">
          <MoneyDisplay :cents="variance" />
          <span class="text-sm ml-1">({{ variancePercentage.toFixed(1) }}%)</span>
        </p>
      </div>
    </div>

    <!-- Chart -->
    <div v-if="!savingsStore.loading && savingsStore.plans.length > 0" class="card mb-6">
      <h2 class="text-xl font-bold mb-4">Évolution de l'épargne</h2>
      <SavingsEvolutionChart :plans="savingsStore.plans" />
    </div>

    <!-- Table -->
    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Historique mensuel</h2>
      </div>

      <div v-if="savingsStore.loading" class="text-center py-8">
        <p>Chargement...</p>
      </div>

      <div v-else-if="savingsStore.error" class="text-center py-8 text-red-600">
        <p>{{ savingsStore.error }}</p>
      </div>

      <div v-else-if="savingsStore.plans.length === 0" class="text-center py-8 text-gray-600">
        <p>Aucun plan d'épargne enregistré</p>
        <p class="text-sm mt-2">Les plans d'épargne sont créés automatiquement avec vos budgets</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="table">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th>Mois</th>
              <th>Épargne prévue</th>
              <th>Épargne réelle</th>
              <th>Écart</th>
              <th>Taux de réalisation</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="plan in savingsStore.plans" :key="plan.id">
              <td class="font-medium">
                {{
                  new Date(plan.month).toLocaleDateString("fr-FR", {
                    year: "numeric",
                    month: "long",
                  })
                }}
              </td>
              <td>
                <MoneyDisplay :cents="plan.plannedCents" />
              </td>
              <td>
                <MoneyDisplay :cents="plan.actualCents || 0" />
              </td>
              <td
                :class="
                  getPlanVariance(plan) >= 0
                    ? 'text-green-600 font-semibold'
                    : 'text-red-600 font-semibold'
                "
              >
                <MoneyDisplay :cents="getPlanVariance(plan)" />
              </td>
              <td>
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                      class="h-2 rounded-full transition-all"
                      :class="
                        getPlanRealizationRate(plan) >= 100
                          ? 'bg-green-500'
                          : getPlanRealizationRate(plan) >= 50
                            ? 'bg-yellow-500'
                            : 'bg-red-500'
                      "
                      :style="{
                        width: Math.min(getPlanRealizationRate(plan), 100) + '%',
                      }"
                    />
                  </div>
                  <span class="text-sm font-medium w-12">
                    {{ getPlanRealizationRate(plan).toFixed(0) }}%
                  </span>
                </div>
              </td>
              <td>
                <button
                  @click="openEditModal(plan)"
                  class="text-blue-600 hover:text-blue-700 dark:text-blue-400 text-sm"
                >
                  Modifier
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeEditModal" />

      <!-- Modal -->
      <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold">Modifier l'épargne prévue</h3>
          <button
            type="button"
            @click="closeEditModal"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleUpdate" class="space-y-4">
          <div>
            <label class="label">Mois</label>
            <p class="text-lg font-medium">
              {{
                selectedPlan
                  ? new Date(selectedPlan.month).toLocaleDateString("fr-FR", {
                      year: "numeric",
                      month: "long",
                    })
                  : ""
              }}
            </p>
          </div>

          <div>
            <label for="planned" class="label">Épargne prévue (€)</label>
            <input
              id="planned"
              v-model.number="editPlannedValue"
              type="number"
              min="0"
              step="0.01"
              class="input"
              required
            />
          </div>

          <div class="flex gap-2 pt-2">
            <button type="submit" :disabled="isSubmitting" class="flex-1 btn btn-primary">
              {{ isSubmitting ? "Enregistrement..." : "Modifier" }}
            </button>
            <button type="button" @click="closeEditModal" class="flex-1 btn btn-secondary">
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useSavingsPlansStore } from "@/stores/savingsPlans";
import MoneyDisplay from "@/components/MoneyDisplay.vue";
import SavingsEvolutionChart from "@/components/SavingsEvolutionChart.vue";
import type { SavingsPlan } from "@/types";

const savingsStore = useSavingsPlansStore();
const isEditModalOpen = ref(false);
const selectedPlan = ref<SavingsPlan | null>(null);
const editPlannedValue = ref(0);
const isSubmitting = ref(false);

onMounted(() => {
  savingsStore.fetchPlans();
});

const totalPlanned = computed(() => {
  return savingsStore.plans.reduce((sum, plan) => sum + plan.plannedCents, 0);
});

const totalActual = computed(() => {
  return savingsStore.plans.reduce((sum, plan) => sum + (plan.actualCents || 0), 0);
});

const variance = computed(() => totalActual.value - totalPlanned.value);

const variancePercentage = computed(() => {
  if (totalPlanned.value === 0) return 0;
  return (variance.value / totalPlanned.value) * 100;
});

function getPlanVariance(plan: SavingsPlan): number {
  return (plan.actualCents || 0) - plan.plannedCents;
}

function getPlanRealizationRate(plan: SavingsPlan): number {
  if (plan.plannedCents === 0) return 0;
  return ((plan.actualCents || 0) / plan.plannedCents) * 100;
}

function openEditModal(plan: SavingsPlan) {
  selectedPlan.value = plan;
  editPlannedValue.value = plan.plannedCents / 100; // Convert cents to euros
  isEditModalOpen.value = true;
}

function closeEditModal() {
  isEditModalOpen.value = false;
  selectedPlan.value = null;
  editPlannedValue.value = 0;
}

async function handleUpdate() {
  if (!selectedPlan.value) return;

  isSubmitting.value = true;
  try {
    await savingsStore.updatePlan(selectedPlan.value.id, {
      plannedCents: Math.round(editPlannedValue.value * 100),
    });
    closeEditModal();
  } catch (error) {
    // Error handled in store
  } finally {
    isSubmitting.value = false;
  }
}
</script>
