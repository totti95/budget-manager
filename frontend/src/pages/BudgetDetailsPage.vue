<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
      <router-link
        to="/"
        class="text-primary-600 hover:text-primary-700 mb-2 inline-block"
      >
        ← Retour au dashboard
      </router-link>
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">{{ currentBudget?.name }}</h1>
        <div v-if="currentBudget" class="flex gap-2">
          <FormButton
            variant="secondary"
            @click="handleExportPdf"
            :loading="isExportingPdf"
            :disabled="isExportingPdf"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5 mr-2 inline-block"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            Télécharger PDF
          </FormButton>
          <button
            @click="handleDeleteBudget"
            class="btn bg-red-600 hover:bg-red-700 text-white"
            :disabled="budgetStore.loading"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5 mr-2 inline-block"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="budgetStore.loading || expenseStore.loading"
      class="text-center py-12"
    >
      <p>Chargement...</p>
    </div>

    <div v-else-if="currentBudget" class="space-y-6">
      <!-- Budget Summary -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- Revenu -->
        <div class="card text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Revenu</p>
          <div v-if="!editingRevenue" class="flex flex-col items-center">
            <p class="text-2xl font-bold text-green-600">
              <MoneyDisplay :cents="currentBudget.revenueCents || 0" />
            </p>
            <button
              @click="startEditRevenue"
              class="text-xs text-blue-600 hover:text-blue-700 mt-1"
            >
              Modifier
            </button>
          </div>
          <div v-else class="flex flex-col items-center gap-2">
            <input
              v-model.number="revenueEuros"
              type="number"
              step="0.01"
              min="0"
              class="input text-center py-1 w-full"
              @keyup.enter="saveRevenue"
              @keydown.esc="cancelEditRevenue"
            />
            <div class="flex gap-2">
              <button @click="saveRevenue" class="text-xs text-green-600">
                ✓
              </button>
              <button @click="cancelEditRevenue" class="text-xs text-red-600">
                ✗
              </button>
            </div>
          </div>
        </div>

        <!-- Prévu -->
        <div class="card text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Prévu</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            <MoneyDisplay :cents="totalPlanned" />
          </p>
        </div>

        <!-- Dépensé -->
        <div class="card text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Dépensé</p>
          <p class="text-2xl font-bold text-blue-600">
            <MoneyDisplay :cents="totalSpent" />
          </p>
        </div>

        <!-- Restant -->
        <div class="card text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Restant</p>
          <p
            class="text-2xl font-bold"
            :class="remaining >= 0 ? 'text-green-600' : 'text-red-600'"
          >
            <MoneyDisplay :cents="remaining" />
          </p>
        </div>

        <!-- Taux d'épargne -->
        <div class="card text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Épargne</p>
          <p
            v-if="currentBudget.savingsRatePercent !== null && currentBudget.savingsRatePercent !== undefined"
            class="text-2xl font-bold"
            :class="getSavingsRateColor(currentBudget.savingsRatePercent)"
          >
            {{ currentBudget.savingsRatePercent.toFixed(1) }}%
          </p>
          <p v-else class="text-2xl font-bold text-gray-400">
            N/A
          </p>
        </div>
      </div>

      <!-- Expenses by Tag Chart -->
      <ExpensesByTagChart
        v-if="currentBudget"
        :budget-id="currentBudget.id"
      />

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

        <!-- Tag Filter -->
        <div v-if="tagsStore.tags.length > 0" class="mb-4 flex gap-2 items-center">
          <label class="font-medium text-sm">Filtrer par tag :</label>
          <select
            v-model="selectedTagFilter"
            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option :value="null">Tous les tags</option>
            <option
              v-for="tag in tagsStore.tags"
              :key="tag.id"
              :value="tag.id"
            >
              {{ tag.name }}
            </option>
          </select>
          <button
            v-if="selectedTagFilter"
            @click="selectedTagFilter = null"
            class="text-sm text-blue-600 hover:underline"
          >
            Réinitialiser
          </button>
        </div>

        <div
          v-if="expenseStore.expenses.length === 0"
          class="text-center py-8 text-gray-600"
        >
          <p>Aucune dépense enregistrée</p>
        </div>

        <div v-else-if="filteredExpenses.length === 0" class="text-center py-8 text-gray-600">
          <p>Aucune dépense trouvée pour ce filtre</p>
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
                <th>Tags</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="expense in filteredExpenses" :key="expense.id">
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
                  <div class="flex flex-wrap gap-1">
                    <TagBadge
                      v-for="tag in expense.tags"
                      :key="tag.id"
                      :tag="tag"
                      :removable="false"
                    />
                    <span v-if="!expense.tags || expense.tags.length === 0" class="text-gray-400">-</span>
                  </div>
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
import { useRoute, useRouter } from "vue-router";
import { useBudgetStore } from "@/stores/budget";
import { useExpenseStore } from "@/stores/expense";
import { useTagsStore } from "@/stores/tags";
import { useToast } from "@/composables/useToast";
import ExpenseForm from "@/components/ExpenseForm.vue";
import MoneyDisplay from "@/components/MoneyDisplay.vue";
import EditExpenseModal from "@/components/EditExpenseModal.vue";
import FormButton from "@/components/FormButton.vue";
import TagBadge from "@/components/TagBadge.vue";
import ExpensesByTagChart from "@/components/ExpensesByTagChart.vue";
import type { CreateExpenseData } from "@/api/expenses";
import type { Expense } from "@/types";

const route = useRoute();
const router = useRouter();
const budgetStore = useBudgetStore();
const expenseStore = useExpenseStore();
const tagsStore = useTagsStore();
const toast = useToast();
const showExpenseForm = ref(false);
const showEditModal = ref(false);
const expenseToEdit = ref<Expense | null>(null);
const isExportingPdf = ref(false);
const selectedTagFilter = ref<number | null>(null);
const editingRevenue = ref(false);
const revenueEuros = ref<number>(0);

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

const filteredExpenses = computed(() => {
  let expenses = expenseStore.expenses;

  if (selectedTagFilter.value) {
    expenses = expenses.filter((e) =>
      e.tags?.some((tag) => tag.id === selectedTagFilter.value),
    );
  }

  return expenses;
});

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
    // Load tags
    if (tagsStore.tags.length === 0) {
      await tagsStore.fetchTags();
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

async function handleExportPdf() {
  if (!currentBudget.value) return;

  isExportingPdf.value = true;

  try {
    // Get PDF blob and filename from API
    const result = await budgetStore.exportPdf(currentBudget.value.id);

    if (!result || !result.blob) {
      throw new Error("Résultat invalide du store");
    }

    const { blob, filename } = result;

    // Create download link
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename || "budget.pdf"; // Use filename from backend with fallback

    // Trigger download
    document.body.appendChild(link);
    link.click();

    // Cleanup
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    toast.success("PDF téléchargé avec succès");
  } catch (err) {
    console.error("Erreur lors de l'export PDF:", err);
    const errorMessage =
      err instanceof Error ? err.message : "Erreur inconnue";
    toast.error(`Erreur lors de la génération du PDF: ${errorMessage}`);
  } finally {
    isExportingPdf.value = false;
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

function startEditRevenue() {
  if (currentBudget.value) {
    revenueEuros.value = (currentBudget.value.revenueCents || 0) / 100;
    editingRevenue.value = true;
  }
}

function cancelEditRevenue() {
  editingRevenue.value = false;
}

async function saveRevenue() {
  if (!currentBudget.value) return;

  try {
    const revenueCents = Math.round(revenueEuros.value * 100);
    await budgetStore.updateBudget(currentBudget.value.id, { revenueCents });
    editingRevenue.value = false;
    // Recharger le budget pour avoir le savingsRatePercent recalculé
    await budgetStore.fetchBudget(currentBudget.value.id);
  } catch (error) {
    console.error("Erreur lors de la mise à jour du revenu:", error);
  }
}

function getSavingsRateColor(rate: number): string {
  if (rate >= 20) return "text-green-600";
  if (rate >= 10) return "text-blue-600";
  if (rate >= 0) return "text-yellow-600";
  return "text-red-600";
}

async function handleDeleteBudget() {
  if (!currentBudget.value) return;

  const budgetName = currentBudget.value.name;

  if (
    confirm(
      `Êtes-vous sûr de vouloir supprimer le budget "${budgetName}" ?\n\nToutes les dépenses associées seront également supprimées.\n\nCette action est irréversible.`
    )
  ) {
    try {
      await budgetStore.deleteBudget(currentBudget.value.id);
      toast.success("Budget supprimé avec succès");
      // Rediriger vers le dashboard
      await router.push("/");
    } catch (error) {
      console.error("Erreur lors de la suppression du budget:", error);
      toast.error("Erreur lors de la suppression du budget");
    }
  }
}
</script>
