import { defineStore } from "pinia";
import { ref } from "vue";
import { savingsGoalsApi } from "@/api/savingsGoals";
import type {
  SavingsGoal,
  CreateSavingsGoalData,
  UpdateSavingsGoalData,
  CreateContributionData,
} from "@/types";
import { useToast } from "@/composables/useToast";

export const useSavingsGoalsStore = defineStore("savingsGoals", () => {
  const goals = ref<SavingsGoal[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const { success, error: errorToast } = useToast();

  async function fetchGoals() {
    loading.value = true;
    error.value = null;
    try {
      goals.value = await savingsGoalsApi.list();
    } catch (err: any) {
      const message = err.response?.data?.message || "Erreur de chargement";
      error.value = message;
      errorToast(message);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createGoal(data: CreateSavingsGoalData) {
    try {
      const newGoal = await savingsGoalsApi.create(data);
      goals.value.push(newGoal);
      success("Objectif créé avec succès");
      return newGoal;
    } catch (err: any) {
      const message = err.response?.data?.message || "Erreur de création";
      errorToast(message);
      throw err;
    }
  }

  async function updateGoal(id: number, data: UpdateSavingsGoalData) {
    try {
      const updated = await savingsGoalsApi.update(id, data);
      const index = goals.value.findIndex((g) => g.id === id);
      if (index !== -1) {
        goals.value[index] = updated;
      }
      success("Objectif mis à jour");
      return updated;
    } catch (err: any) {
      const message = err.response?.data?.message || "Erreur de modification";
      errorToast(message);
      throw err;
    }
  }

  async function deleteGoal(id: number) {
    try {
      await savingsGoalsApi.delete(id);
      goals.value = goals.value.filter((g) => g.id !== id);
      success("Objectif supprimé");
    } catch (err: any) {
      const message = err.response?.data?.message || "Erreur de suppression";
      errorToast(message);
      throw err;
    }
  }

  async function syncGoalWithAsset(id: number) {
    try {
      const updated = await savingsGoalsApi.syncAsset(id);
      const index = goals.value.findIndex((g) => g.id === id);
      if (index !== -1) {
        goals.value[index] = updated;
      }
      success("Objectif synchronisé avec l'actif");
      return updated;
    } catch (err: any) {
      const message = err.response?.data?.message || "Erreur de synchronisation";
      errorToast(message);
      throw err;
    }
  }

  async function addContribution(goalId: number, data: CreateContributionData) {
    try {
      await savingsGoalsApi.addContribution(goalId, data);
      // Recharger l'objectif pour avoir le montant à jour
      await fetchGoals();
      success("Contribution ajoutée");
    } catch (err: any) {
      const message = err.response?.data?.message || "Erreur d'ajout";
      errorToast(message);
      throw err;
    }
  }

  async function deleteContribution(goalId: number, contributionId: number) {
    try {
      await savingsGoalsApi.deleteContribution(goalId, contributionId);
      // Recharger les objectifs pour avoir le montant à jour
      await fetchGoals();
      success("Contribution supprimée");
    } catch (err: any) {
      const message = err.response?.data?.message || "Erreur de suppression";
      errorToast(message);
      throw err;
    }
  }

  return {
    goals,
    loading,
    error,
    fetchGoals,
    createGoal,
    updateGoal,
    deleteGoal,
    syncGoalWithAsset,
    addContribution,
    deleteContribution,
  };
});
