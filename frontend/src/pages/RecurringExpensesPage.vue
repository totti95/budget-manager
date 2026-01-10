<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Dépenses récurrentes</h1>
      <button
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        @click="openCreateModal"
      >
        Ajouter une dépense récurrente
      </button>
    </div>

    <div v-if="store.loading" class="text-center py-8">
      <p class="text-gray-600 dark:text-gray-400">Chargement...</p>
    </div>

    <div v-else-if="store.error" class="text-center py-8 text-red-600">
      <p>{{ store.error }}</p>
    </div>

    <div
      v-else-if="store.recurringExpenses.length === 0"
      class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow"
    >
      <p class="text-gray-600 dark:text-gray-400 mb-4">Aucune dépense récurrente configurée</p>
      <button
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        @click="openCreateModal"
      >
        Créer la première
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="expense in store.recurringExpenses"
        :key="expense.id"
        class="bg-white dark:bg-gray-800 rounded-lg shadow p-4"
        :class="{ 'opacity-60': !expense.isActive }"
      >
        <div class="flex justify-between items-start mb-2">
          <div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100">
              {{ expense.label }}
            </h3>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ (expense.amountCents / 100).toFixed(2) }} €
            </p>
          </div>
          <div class="flex gap-2">
            <button @click="editExpense(expense)" class="text-sm text-blue-600 hover:text-blue-700">
              Modifier
            </button>
            <button
              @click="toggleActive(expense.id)"
              class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
              {{ expense.isActive ? "Désactiver" : "Activer" }}
            </button>
            <button
              @click="deleteExpense(expense.id)"
              class="text-sm text-red-600 hover:text-red-700"
            >
              Supprimer
            </button>
          </div>
        </div>

        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
          <p><strong>Fréquence:</strong> {{ formatFrequency(expense) }}</p>
          <p v-if="expense.templateSubcategory">
            <strong>Catégorie:</strong>
            {{ expense.templateSubcategory.templateCategory?.name || "-" }}
            / {{ expense.templateSubcategory.name }}
          </p>
          <p>
            <strong>Du:</strong> {{ expense.startDate }}
            <span v-if="expense.endDate"> au {{ expense.endDate }}</span>
          </p>
          <p v-if="expense.notes" class="mt-2 text-gray-500 dark:text-gray-500">
            {{ expense.notes }}
          </p>
        </div>

        <div class="mt-2 flex gap-2">
          <span
            v-if="expense.autoCreate"
            class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
          >
            Création auto
          </span>
          <span
            v-if="expense.isActive"
            class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
          >
            Actif
          </span>
        </div>
      </div>
    </div>

    <RecurringExpenseFormModal
      :is-open="showFormModal"
      :recurring-expense="selectedExpense"
      @close="closeFormModal"
      @submit="handleSubmit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRecurringExpenseStore } from "@/stores/recurringExpenses";
import RecurringExpenseFormModal from "@/components/RecurringExpenseFormModal.vue";
import { useToast } from "@/composables/useToast";
import type { RecurringExpense } from "@/types";

const store = useRecurringExpenseStore();
const showFormModal = ref(false);
const selectedExpense = ref<RecurringExpense | undefined>();
const { success, error } = useToast();

onMounted(() => {
  store.fetchRecurringExpenses();
});

function openCreateModal() {
  selectedExpense.value = undefined;
  showFormModal.value = true;
}

function editExpense(expense: RecurringExpense) {
  selectedExpense.value = expense;
  showFormModal.value = true;
}

function closeFormModal() {
  showFormModal.value = false;
  selectedExpense.value = undefined;
}

async function handleSubmit(data: any) {
  try {
    if (selectedExpense.value) {
      await store.updateRecurringExpense(selectedExpense.value.id, data);
      closeFormModal();
      success("Dépense récurrente modifiée avec succès !");
    } else {
      await store.createRecurringExpense(data);
      closeFormModal();
      success("Dépense récurrente créée avec succès !");
    }
  } catch (err) {
    closeFormModal();
    error("Erreur lors de l'enregistrement de la dépense récurrente");
  }
}

function formatFrequency(expense: RecurringExpense): string {
  const dayNames = {
    monday: "Lundi",
    tuesday: "Mardi",
    wednesday: "Mercredi",
    thursday: "Jeudi",
    friday: "Vendredi",
    saturday: "Samedi",
    sunday: "Dimanche",
  };
  const monthNames = [
    "Janvier",
    "Février",
    "Mars",
    "Avril",
    "Mai",
    "Juin",
    "Juillet",
    "Août",
    "Septembre",
    "Octobre",
    "Novembre",
    "Décembre",
  ];

  if (expense.frequency === "monthly" && expense.dayOfMonth) {
    return `Mensuelle (le ${expense.dayOfMonth})`;
  }

  if (expense.frequency === "weekly" && expense.dayOfWeek) {
    return `Hebdomadaire (${dayNames[expense.dayOfWeek]})`;
  }

  if (expense.frequency === "yearly") {
    const month = expense.monthOfYear ? monthNames[expense.monthOfYear - 1] : "";
    const day = expense.dayOfMonth ? ` le ${expense.dayOfMonth}` : "";
    return `Annuelle (${month}${day})`;
  }

  return expense.frequency;
}

async function toggleActive(id: number) {
  try {
    await store.toggleActive(id);
    success("Statut modifié avec succès");
  } catch (err) {
    error("Erreur lors du changement de statut");
  }
}

async function deleteExpense(id: number) {
  if (!confirm("Êtes-vous sûr de vouloir supprimer cette dépense récurrente ?")) {
    return;
  }

  try {
    await store.deleteRecurringExpense(id);
    success("Dépense récurrente supprimée avec succès");
  } catch (err) {
    error("Erreur lors de la suppression");
  }
}
</script>
