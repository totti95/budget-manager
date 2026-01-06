import { defineStore } from "pinia";
import { ref } from "vue";
import { recurringExpensesApi, type CreateRecurringExpenseData } from "@/api/recurringExpenses";
import type { RecurringExpense } from "@/types";

export const useRecurringExpenseStore = defineStore("recurringExpenses", () => {
  const recurringExpenses = ref<RecurringExpense[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchRecurringExpenses() {
    loading.value = true;
    error.value = null;
    try {
      recurringExpenses.value = await recurringExpensesApi.list();
    } catch (err) {
      error.value = "Erreur lors du chargement des dépenses récurrentes";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createRecurringExpense(data: CreateRecurringExpenseData) {
    loading.value = true;
    error.value = null;
    try {
      const recurringExpense = await recurringExpensesApi.create(data);
      recurringExpenses.value.push(recurringExpense);
      return recurringExpense;
    } catch (err) {
      error.value = "Erreur lors de la création de la dépense récurrente";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateRecurringExpense(id: number, data: Partial<CreateRecurringExpenseData>) {
    loading.value = true;
    error.value = null;
    try {
      const updated = await recurringExpensesApi.update(id, data);
      const index = recurringExpenses.value.findIndex((re) => re.id === id);
      if (index !== -1) {
        recurringExpenses.value[index] = updated;
      }
      return updated;
    } catch (err) {
      error.value = "Erreur lors de la mise à jour de la dépense récurrente";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteRecurringExpense(id: number) {
    loading.value = true;
    error.value = null;
    try {
      await recurringExpensesApi.delete(id);
      recurringExpenses.value = recurringExpenses.value.filter((re) => re.id !== id);
    } catch (err) {
      error.value = "Erreur lors de la suppression de la dépense récurrente";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function toggleActive(id: number) {
    loading.value = true;
    error.value = null;
    try {
      const updated = await recurringExpensesApi.toggleActive(id);
      const index = recurringExpenses.value.findIndex((re) => re.id === id);
      if (index !== -1) {
        recurringExpenses.value[index] = updated;
      }
      return updated;
    } catch (err) {
      error.value = "Erreur lors du changement de statut";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    recurringExpenses,
    loading,
    error,
    fetchRecurringExpenses,
    createRecurringExpense,
    updateRecurringExpense,
    deleteRecurringExpense,
    toggleActive,
  };
});
