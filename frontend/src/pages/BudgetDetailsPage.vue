<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
      <router-link
        to="/"
        class="text-primary-600 hover:text-primary-700 mb-2 inline-block"
      >
        ← Retour au dashboard
      </router-link>
      <h1 class="text-3xl font-bold">{{ currentBudget?.name }}</h1>
    </div>

    <div
      v-if="budgetStore.loading || expenseStore.loading"
      class="text-center py-12"
    >
      <p>Chargement...</p>
    </div>

    <div v-else-if="currentBudget" class="space-y-6">
      <!-- Budget Summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Prévu</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            <MoneyDisplay :cents="totalPlanned" />
          </p>
        </div>
        <div class="card text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Dépensé</p>
          <p class="text-2xl font-bold text-blue-600">
            <MoneyDisplay :cents="totalSpent" />
          </p>
        </div>
        <div class="card text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Restant</p>
          <p
            class="text-2xl font-bold"
            :class="remaining >= 0 ? 'text-green-600' : 'text-red-600'"
          >
            <MoneyDisplay :cents="remaining" />
          </p>
        </div>
      </div>

      <!-- Add Expense Form -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Ajouter une dépense</h2>
          <button
            v-if="!showExpenseForm"
            @click="showExpenseForm = true"
            class="btn btn-primary"
          >
            Nouvelle dépense
          </button>
        </div>

        <div v-if="showExpenseForm">
          <ExpenseForm
            :categories="currentBudget.categories || []"
            :budget-id="currentBudget.id"
            @submit="handleAddExpense"
            @category-updated="handleCategoryUpdated"
            :on-cancel="() => (showExpenseForm = false)"
          />
        </div>
      </div>

      <!-- Expenses List -->
      <div class="card">
        <h2 class="text-xl font-bold mb-4">Dépenses récentes</h2>

        <div v-if="expenseStore.error" class="text-red-600 mb-4">
          {{ expenseStore.error }}
        </div>

        <div
          v-if="expenseStore.expenses.length === 0"
          class="text-center py-8 text-gray-600"
        >
          <p>Aucune dépense enregistrée</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="table">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th>Date</th>
                <th>Catégorie</th>
                <th>Libellé</th>
                <th>Montant</th>
                <th>Paiement</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="expense in expenseStore.expenses" :key="expense.id">
                <td>
                  {{ new Date(expense.date).toLocaleDateString("fr-FR") }}
                </td>
                <td>
                  <div class="text-sm">
                    <div class="font-medium">
                      {{ getCategoryName(expense.budgetSubcategoryId) }}
                    </div>
                    <div class="text-gray-500">
                      {{ getSubcategoryName(expense.budgetSubcategoryId) }}
                    </div>
                  </div>
                </td>
                <td class="font-medium">{{ expense.label }}</td>
                <td><MoneyDisplay :cents="expense.amountCents" /></td>
                <td>
                  <span
                    v-if="expense.paymentMethod"
                    class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700"
                  >
                    {{ formatPaymentMethod(expense.paymentMethod) }}
                  </span>
                  <span v-else>-</span>
                </td>
                <td>
                  <div class="flex gap-2">
                    <button
                      @click="handleEditExpense(expense)"
                      class="text-blue-600 hover:text-blue-700 dark:text-blue-400 text-sm"
                    >
                      Modifier
                    </button>
                    <button
                      @click="handleDeleteExpense(expense.id)"
                      class="text-red-600 hover:text-red-700 dark:text-red-400 text-sm"
                    >
                      Supprimer
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Edit Expense Modal -->
    <EditExpenseModal
      :isOpen="showEditModal"
      :expense="expenseToEdit"
      :categories="currentBudget?.categories || []"
      @close="closeEditModal"
      @updated="handleExpenseUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed, ref } from "vue";
import { useRoute } from "vue-router";
import { useBudgetStore } from "@/stores/budget";
import { useExpenseStore } from "@/stores/expense";
import ExpenseForm from "@/components/ExpenseForm.vue";
import MoneyDisplay from "@/components/MoneyDisplay.vue";
import EditExpenseModal from "@/components/EditExpenseModal.vue";
import type { CreateExpenseData } from "@/api/expenses";
import type { Expense } from "@/types";

const route = useRoute();
const budgetStore = useBudgetStore();
const expenseStore = useExpenseStore();
const showExpenseForm = ref(false);
const showEditModal = ref(false);
const expenseToEdit = ref<Expense | null>(null);

const currentBudget = computed(() => budgetStore.currentBudget);

const totalPlanned = computed(() => {
  if (!currentBudget.value?.categories) return 0;
  return currentBudget.value.categories.reduce((total, category) => {
    return (
      total +
      (category.subcategories || []).reduce(
        (catTotal, sub) => catTotal + sub.plannedAmountCents,
        0,
      )
    );
  }, 0);
});

const totalSpent = computed(() => {
  if (!currentBudget.value?.categories) return 0;
  // Calculer à partir des expenses réelles
  return expenseStore.expenses.reduce(
    (total, expense) => total + expense.amountCents,
    0,
  );
});

const remaining = computed(() => totalPlanned.value - totalSpent.value);

onMounted(async () => {
  const month = route.params.month as string;
  try {
    const response = await budgetStore.fetchBudgets(month);
    if (response.data.length > 0) {
      const budgetId = response.data[0].id;
      await budgetStore.fetchBudget(budgetId);
      // Fetch expenses for this budget
      await expenseStore.fetchExpenses(budgetId);
    }
  } catch (error) {
    console.error("Erreur lors du chargement du budget:", error);
  }
});

async function handleAddExpense(values: CreateExpenseData) {
  if (!currentBudget.value) return;

  try {
    await expenseStore.createExpense(currentBudget.value.id, values);
    showExpenseForm.value = false;
    // Pas besoin de refresh le budget, les dépenses sont déjà dans le store
  } catch (error) {
    console.error("Erreur lors de l'ajout de la dépense:", error);
  }
}

async function handleCategoryUpdated() {
  // Refresh budget to get updated categories with new subcategory
  if (currentBudget.value) {
    await budgetStore.fetchBudget(currentBudget.value.id);
  }
}

function handleEditExpense(expense: Expense) {
  expenseToEdit.value = expense;
  showEditModal.value = true;
}

function closeEditModal() {
  showEditModal.value = false;
  expenseToEdit.value = null;
}

async function handleExpenseUpdated(updatedExpense: Expense) {
  // Refresh expenses to get the updated list
  if (currentBudget.value) {
    await expenseStore.fetchExpenses(currentBudget.value.id);
  }
}

async function handleDeleteExpense(id: number) {
  if (confirm("Êtes-vous sûr de vouloir supprimer cette dépense ?")) {
    try {
      await expenseStore.deleteExpense(id);
      // Le store met à jour automatiquement la liste
    } catch (error) {
      console.error("Erreur lors de la suppression:", error);
    }
  }
}

function getCategoryName(subcategoryId: number): string {
  if (!currentBudget.value?.categories) return "";
  for (const category of currentBudget.value.categories) {
    if (category.subcategories?.some((sub) => sub.id === subcategoryId)) {
      return category.name;
    }
  }
  return "";
}

function getSubcategoryName(subcategoryId: number): string {
  if (!currentBudget.value?.categories) return "";
  for (const category of currentBudget.value.categories) {
    const subcategory = category.subcategories?.find(
      (sub) => sub.id === subcategoryId,
    );
    if (subcategory) return subcategory.name;
  }
  return "";
}

function formatPaymentMethod(method: string): string {
  const methods: Record<string, string> = {
    cb: "CB",
    especes: "Espèces",
    virement: "Virement",
    prelevement: "Prélèvement",
    cheque: "Chèque",
  };
  return methods[method] || method;
}
</script>
