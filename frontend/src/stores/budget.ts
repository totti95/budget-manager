import { defineStore } from "pinia";
import { ref } from "vue";
import { budgetsApi } from "@/api/budgets";
import type { Budget } from "@/types";

export const useBudgetStore = defineStore("budget", () => {
  const budgets = ref<Budget[]>([]);
  const currentBudget = ref<Budget | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchBudgets(month?: string) {
    loading.value = true;
    error.value = null;
    try {
      const response = await budgetsApi.list(month);
      budgets.value = response.data;
      return response;
    } catch (err) {
      error.value = "Erreur lors du chargement des budgets";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchBudget(id: number) {
    loading.value = true;
    error.value = null;
    try {
      const budget = await budgetsApi.get(id);
      currentBudget.value = budget;
      return budget;
    } catch (err) {
      error.value = "Erreur lors du chargement du budget";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function generateBudget(month: string) {
    loading.value = true;
    error.value = null;
    try {
      const budget = await budgetsApi.generate(month);
      budgets.value.unshift(budget);
      currentBudget.value = budget;
      return budget;
    } catch (err) {
      error.value = "Erreur lors de la génération du budget";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateBudget(id: number, data: { name?: string; revenueCents?: number }) {
    loading.value = true;
    error.value = null;
    try {
      const budget = await budgetsApi.update(id, data);
      const index = budgets.value.findIndex((b) => b.id === id);
      if (index !== -1) {
        budgets.value[index] = budget;
      }
      if (currentBudget.value?.id === id) {
        currentBudget.value = budget;
      }
      return budget;
    } catch (err) {
      error.value = "Erreur lors de la mise à jour du budget";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteBudget(id: number) {
    loading.value = true;
    error.value = null;
    try {
      await budgetsApi.delete(id);
      budgets.value = budgets.value.filter((b) => b.id !== id);
      if (currentBudget.value?.id === id) {
        currentBudget.value = null;
      }
    } catch (err) {
      error.value = "Erreur lors de la suppression du budget";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function exportPdf(id: number) {
    loading.value = true;
    error.value = null;
    try {
      const result = await budgetsApi.exportPdf(id);
      return result; // { blob, filename }
    } catch (err) {
      error.value = "Erreur lors de l'export PDF";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    budgets,
    currentBudget,
    loading,
    error,
    fetchBudgets,
    fetchBudget,
    generateBudget,
    updateBudget,
    deleteBudget,
    exportPdf,
  };
});
