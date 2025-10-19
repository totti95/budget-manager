import { defineStore } from "pinia";
import { ref } from "vue";
import {
  expensesApi,
  type CreateExpenseData,
  type ExpenseFilters,
} from "@/api/expenses";
import type { Expense } from "@/types";

export const useExpenseStore = defineStore("expense", () => {
  const expenses = ref<Expense[]>([]);
  const currentBudgetId = ref<number | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchExpenses(budgetId: number, filters?: ExpenseFilters) {
    loading.value = true;
    error.value = null;
    currentBudgetId.value = budgetId;
    try {
      const response = await expensesApi.list(budgetId, filters);
      expenses.value = response.data;
      return response;
    } catch (err) {
      error.value = "Erreur lors du chargement des dépenses";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createExpense(budgetId: number, data: CreateExpenseData) {
    loading.value = true;
    error.value = null;
    try {
      const expense = await expensesApi.create(budgetId, data);
      expenses.value.unshift(expense);
      return expense;
    } catch (err) {
      error.value = "Erreur lors de la création de la dépense";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateExpense(id: number, data: Partial<CreateExpenseData>) {
    loading.value = true;
    error.value = null;
    try {
      const expense = await expensesApi.update(id, data);
      const index = expenses.value.findIndex((e) => e.id === id);
      if (index !== -1) {
        expenses.value[index] = expense;
      }
      return expense;
    } catch (err) {
      error.value = "Erreur lors de la mise à jour de la dépense";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteExpense(id: number) {
    loading.value = true;
    error.value = null;
    try {
      await expensesApi.delete(id);
      expenses.value = expenses.value.filter((e) => e.id !== id);
    } catch (err) {
      error.value = "Erreur lors de la suppression de la dépense";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function importCsv(budgetId: number, file: File) {
    loading.value = true;
    error.value = null;
    try {
      const result = await expensesApi.importCsv(budgetId, file);
      // Refresh expenses after import
      if (result.imported > 0) {
        await fetchExpenses(budgetId);
      }
      return result;
    } catch (err) {
      error.value = "Erreur lors de l'import CSV";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function exportCsv(budgetId: number) {
    loading.value = true;
    error.value = null;
    try {
      const blob = await expensesApi.exportCsv(budgetId);
      // Créer un lien de téléchargement
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `expenses-${new Date().toISOString().slice(0, 7)}.csv`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (err) {
      error.value = "Erreur lors de l'export CSV";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    expenses,
    currentBudgetId,
    loading,
    error,
    fetchExpenses,
    createExpense,
    updateExpense,
    deleteExpense,
    importCsv,
    exportCsv,
  };
});
