import { defineStore } from "pinia";
import { ref } from "vue";
import { savingsPlansApi } from "@/api/savingsPlans";
import type { SavingsPlan } from "@/types";
import type { UpdateSavingsPlanData } from "@/api/savingsPlans";

export const useSavingsPlansStore = defineStore("savingsPlans", () => {
  const plans = ref<SavingsPlan[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchPlans(month?: string) {
    loading.value = true;
    error.value = null;
    try {
      plans.value = await savingsPlansApi.list(month);
      return plans.value;
    } catch (err) {
      error.value = "Erreur lors du chargement des plans d'épargne";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updatePlan(id: number, data: UpdateSavingsPlanData) {
    loading.value = true;
    error.value = null;
    try {
      const plan = await savingsPlansApi.update(id, data);
      const index = plans.value.findIndex((p) => p.id === id);
      if (index !== -1) {
        plans.value[index] = plan;
      }
      return plan;
    } catch (err) {
      error.value = "Erreur lors de la mise à jour du plan d'épargne";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    plans,
    loading,
    error,
    fetchPlans,
    updatePlan,
  };
});
