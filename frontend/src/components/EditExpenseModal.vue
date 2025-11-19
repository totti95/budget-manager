<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="handleClose"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold">Modifier la dépense</h3>
        <button
          @click="handleClose"
          class="text-gray-400 hover:text-gray-600"
          type="button"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label for="date" class="block text-sm font-medium mb-1">
            Date
          </label>
          <input
            id="date"
            v-model="form.date"
            type="date"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label for="label" class="block text-sm font-medium mb-1">
            Description
          </label>
          <input
            id="label"
            v-model="form.label"
            type="text"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label for="amount" class="block text-sm font-medium mb-1">
            Montant (€)
          </label>
          <input
            id="amount"
            v-model.number="form.amountEuros"
            type="number"
            step="0.01"
            min="0.01"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label for="subcategory" class="block text-sm font-medium mb-1">
            Sous-catégorie
          </label>
          <select
            id="subcategory"
            v-model="form.budgetSubcategoryId"
            required
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="" disabled>Sélectionner une sous-catégorie</option>
            <optgroup
              v-for="category in categories"
              :key="category.id"
              :label="category.name"
            >
              <option
                v-for="subcategory in category.subcategories"
                :key="subcategory.id"
                :value="subcategory.id"
              >
                {{ subcategory.name }}
              </option>
            </optgroup>
          </select>
        </div>

        <div>
          <label for="paymentMethod" class="block text-sm font-medium mb-1">
            Moyen de paiement
          </label>
          <input
            id="paymentMethod"
            v-model="form.paymentMethod"
            type="text"
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label for="notes" class="block text-sm font-medium mb-1">
            Notes
          </label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="3"
            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>

        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>

        <div class="flex gap-3 justify-end">
          <button
            type="button"
            @click="handleClose"
            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ loading ? "Modification..." : "Enregistrer" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { expensesApi } from "@/api/expenses";
import type { Expense, BudgetCategory } from "@/types";

interface Props {
  isOpen: boolean;
  expense: Expense | null;
  categories: BudgetCategory[];
}

interface Emits {
  (e: "close"): void;
  (e: "updated", expense: Expense): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const form = ref({
  date: "",
  label: "",
  amountEuros: 0,
  budgetSubcategoryId: null as number | null,
  paymentMethod: "",
  notes: "",
});

const loading = ref(false);
const error = ref("");

const centsToEuros = (cents: number) => cents / 100;
const eurosToCents = (euros: number) => Math.round(euros * 100);

watch(
  () => props.expense,
  (expense) => {
    if (expense) {
      form.value = {
        date: expense.date.split("T")[0], // Extract date part
        label: expense.label,
        amountEuros: centsToEuros(expense.amountCents),
        budgetSubcategoryId: expense.budgetSubcategoryId,
        paymentMethod: expense.paymentMethod || "",
        notes: expense.notes || "",
      };
    }
  },
  { immediate: true },
);

const handleClose = () => {
  error.value = "";
  emit("close");
};

const handleSubmit = async () => {
  if (!props.expense) return;

  error.value = "";
  loading.value = true;

  try {
    const updatedExpense = await expensesApi.update(props.expense.id, {
      date: form.value.date,
      label: form.value.label,
      amount_cents: eurosToCents(form.value.amountEuros),
      budget_subcategory_id: form.value.budgetSubcategoryId!,
      payment_method: form.value.paymentMethod || undefined,
      notes: form.value.notes || undefined,
    });

    emit("updated", updatedExpense);
    handleClose();
  } catch (err: any) {
    error.value = err.response?.data?.message || "Erreur lors de la modification";
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Modal styles are inline */
</style>
