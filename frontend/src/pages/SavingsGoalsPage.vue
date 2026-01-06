<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Objectifs d'Épargne</h1>
      <button
        @click="openCreateModal"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
      >
        + Nouvel objectif
      </button>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <p class="text-sm text-gray-500 dark:text-gray-400">Objectifs actifs</p>
        <p class="text-2xl font-bold">{{ activeGoalsCount }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <p class="text-sm text-gray-500 dark:text-gray-400">Total épargné</p>
        <p class="text-2xl font-bold">{{ formatEuros(totalSavedCents) }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <p class="text-sm text-gray-500 dark:text-gray-400">Total objectifs</p>
        <p class="text-2xl font-bold">
          {{ formatEuros(totalTargetCents) }}
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-8">
      <p class="text-gray-500">Chargement...</p>
    </div>

    <!-- Liste des objectifs -->
    <div v-else-if="goals.length > 0" class="space-y-4">
      <!-- Objectifs actifs -->
      <div v-if="activeGoals.length > 0">
        <h2 class="text-xl font-semibold mb-3">En cours</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <SavingsGoalCard
            v-for="goal in activeGoals"
            :key="goal.id"
            :goal="goal"
            @edit="openEditModal"
            @delete="handleDelete"
            @add-contribution="openContributionModal"
            @sync-asset="handleSyncAsset"
            @view-contributions="openContributionsHistory"
          />
        </div>
      </div>

      <!-- Objectifs complétés -->
      <div v-if="completedGoals.length > 0" class="mt-8">
        <h2 class="text-xl font-semibold mb-3">Complétés</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <SavingsGoalCard
            v-for="goal in completedGoals"
            :key="goal.id"
            :goal="goal"
            @edit="openEditModal"
            @delete="handleDelete"
            @view-contributions="openContributionsHistory"
          />
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500 mb-4">Aucun objectif d'épargne. Commencez par en créer un !</p>
      <button
        @click="openCreateModal"
        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg"
      >
        Créer mon premier objectif
      </button>
    </div>

    <!-- Modals -->
    <SavingsGoalFormModal
      v-if="showFormModal"
      :goal="selectedGoal"
      @close="closeFormModal"
      @saved="handleSaved"
    />

    <ContributionFormModal
      v-if="showContributionModal"
      :goal="selectedGoal!"
      @close="closeContributionModal"
      @saved="handleContributionSaved"
    />

    <ContributionsHistoryModal
      v-if="showHistoryModal"
      :goal="selectedGoal!"
      @close="closeHistoryModal"
      @updated="handleHistoryUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useSavingsGoalsStore } from "@/stores/savingsGoals";
import SavingsGoalCard from "@/components/SavingsGoalCard.vue";
import SavingsGoalFormModal from "@/components/SavingsGoalFormModal.vue";
import ContributionFormModal from "@/components/ContributionFormModal.vue";
import ContributionsHistoryModal from "@/components/ContributionsHistoryModal.vue";
import type { SavingsGoal } from "@/types";

const store = useSavingsGoalsStore();

const showFormModal = ref(false);
const showContributionModal = ref(false);
const showHistoryModal = ref(false);
const selectedGoal = ref<SavingsGoal | null>(null);

const goals = computed(() => store.goals);
const loading = computed(() => store.loading);

const activeGoals = computed(() =>
  goals.value.filter((g) => g.status === "active").sort((a, b) => b.priority - a.priority)
);

const completedGoals = computed(() => goals.value.filter((g) => g.status === "completed"));

const activeGoalsCount = computed(() => activeGoals.value.length);

const totalSavedCents = computed(() =>
  activeGoals.value.reduce((sum, g) => sum + g.currentAmountCents, 0)
);

const totalTargetCents = computed(() =>
  activeGoals.value.reduce((sum, g) => sum + g.targetAmountCents, 0)
);

function formatEuros(cents: number): string {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
  }).format(cents / 100);
}

function openCreateModal() {
  selectedGoal.value = null;
  showFormModal.value = true;
}

function openEditModal(goal: SavingsGoal) {
  selectedGoal.value = goal;
  showFormModal.value = true;
}

function closeFormModal() {
  showFormModal.value = false;
  selectedGoal.value = null;
}

function openContributionModal(goal: SavingsGoal) {
  selectedGoal.value = goal;
  showContributionModal.value = true;
}

function closeContributionModal() {
  showContributionModal.value = false;
  selectedGoal.value = null;
}

function openContributionsHistory(goal: SavingsGoal) {
  selectedGoal.value = goal;
  showHistoryModal.value = true;
}

function closeHistoryModal() {
  showHistoryModal.value = false;
  selectedGoal.value = null;
}

async function handleSaved() {
  closeFormModal();
  await store.fetchGoals();
}

async function handleContributionSaved() {
  closeContributionModal();
  await store.fetchGoals();
}

async function handleHistoryUpdated() {
  await store.fetchGoals();
}

async function handleDelete(goal: SavingsGoal) {
  if (confirm(`Supprimer l'objectif "${goal.name}" ?`)) {
    await store.deleteGoal(goal.id);
  }
}

async function handleSyncAsset(goal: SavingsGoal) {
  await store.syncGoalWithAsset(goal.id);
}

onMounted(() => {
  store.fetchGoals();
});
</script>
