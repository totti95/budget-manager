import { defineStore } from "pinia";
import { ref } from "vue";
import { savingsGoalsApi } from "@/api/savingsGoals";
import type {
  SavingsGoal,
  CreateSavingsGoalData,
  UpdateSavingsGoalData,
  CreateContributionData,
} from "@/types";
import { executeStoreAction } from "@/composables/useStoreAction";

export const useSavingsGoalsStore = defineStore("savingsGoals", () => {
  const goals = ref<SavingsGoal[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchGoals() {
    return await executeStoreAction(
      async () => {
        goals.value = await savingsGoalsApi.list();
      },
      loading,
      error,
      { errorMessage: "Erreur de chargement" }
    );
  }

  async function createGoal(data: CreateSavingsGoalData) {
    return await executeStoreAction(
      async () => {
        const newGoal = await savingsGoalsApi.create(data);
        goals.value.push(newGoal);
        return newGoal;
      },
      loading,
      error,
      {
        successMessage: "Objectif créé avec succès",
        errorMessage: "Erreur de création",
      }
    );
  }

  async function updateGoal(id: number, data: UpdateSavingsGoalData) {
    return await executeStoreAction(
      async () => {
        const updated = await savingsGoalsApi.update(id, data);
        const index = goals.value.findIndex((g) => g.id === id);
        if (index !== -1) {
          goals.value[index] = updated;
        }
        return updated;
      },
      loading,
      error,
      {
        successMessage: "Objectif mis à jour",
        errorMessage: "Erreur de modification",
      }
    );
  }

  async function deleteGoal(id: number) {
    return await executeStoreAction(
      async () => {
        await savingsGoalsApi.delete(id);
        goals.value = goals.value.filter((g) => g.id !== id);
      },
      loading,
      error,
      {
        successMessage: "Objectif supprimé",
        errorMessage: "Erreur de suppression",
      }
    );
  }

  async function syncGoalWithAsset(id: number) {
    return await executeStoreAction(
      async () => {
        const updated = await savingsGoalsApi.syncAsset(id);
        const index = goals.value.findIndex((g) => g.id === id);
        if (index !== -1) {
          goals.value[index] = updated;
        }
        return updated;
      },
      loading,
      error,
      {
        successMessage: "Objectif synchronisé avec l'actif",
        errorMessage: "Erreur de synchronisation",
      }
    );
  }

  async function addContribution(goalId: number, data: CreateContributionData) {
    return await executeStoreAction(
      async () => {
        await savingsGoalsApi.addContribution(goalId, data);
        await fetchGoals();
      },
      loading,
      error,
      {
        successMessage: "Contribution ajoutée",
        errorMessage: "Erreur d'ajout",
      }
    );
  }

  async function deleteContribution(goalId: number, contributionId: number) {
    return await executeStoreAction(
      async () => {
        await savingsGoalsApi.deleteContribution(goalId, contributionId);
        await fetchGoals();
      },
      loading,
      error,
      {
        successMessage: "Contribution supprimée",
        errorMessage: "Erreur de suppression",
      }
    );
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
