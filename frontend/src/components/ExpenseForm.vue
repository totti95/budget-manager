<template>
  <form @submit="onSubmit" class="space-y-4">
    <div>
      <label for="date" class="label">Date</label>
      <input
        id="date"
        v-model="date"
        v-bind="dateAttrs"
        type="date"
        class="input"
        :class="{ 'border-red-500': errors.date }"
      />
      <p v-if="errors.date" class="mt-1 text-sm text-red-600">
        {{ errors.date }}
      </p>
    </div>

    <!-- Catégorie -->
    <div>
      <label for="category" class="label">Catégorie</label>
      <select
        id="category"
        v-model="selectedCategoryId"
        class="input"
        :class="{ 'border-red-500': !selectedCategoryId && hasTriedSubmit }"
      >
        <option value="">Sélectionner une catégorie</option>
        <option v-for="category in categories" :key="category.id" :value="category.id">
          {{ category.name }}
        </option>
      </select>
      <p v-if="!selectedCategoryId && hasTriedSubmit" class="mt-1 text-sm text-red-600">
        Veuillez sélectionner une catégorie
      </p>
    </div>

    <!-- Sous-catégorie (affichée uniquement si une catégorie est sélectionnée) -->
    <div v-if="selectedCategoryId">
      <div class="flex items-center justify-between mb-2">
        <label for="subcategory" class="label">Sous-catégorie</label>
        <button
          type="button"
          @click="showNewSubcategoryInput = !showNewSubcategoryInput"
          class="text-sm text-blue-600 hover:text-blue-700"
        >
          {{ showNewSubcategoryInput ? "↩ Choisir existante" : "+ Nouvelle sous-catégorie" }}
        </button>
      </div>

      <!-- Mode sélection existante -->
      <div v-if="!showNewSubcategoryInput">
        <select
          id="subcategory"
          v-model="budgetSubcategoryId"
          v-bind="budgetSubcategoryIdAttrs"
          class="input"
          :class="{ 'border-red-500': errors.budget_subcategory_id }"
        >
          <option value="">Sélectionner une sous-catégorie</option>
          <option
            v-for="subcategory in availableSubcategories"
            :key="subcategory.id"
            :value="subcategory.id"
          >
            {{ subcategory.name }}
          </option>
        </select>
        <p v-if="errors.budget_subcategory_id" class="mt-1 text-sm text-red-600">
          {{ errors.budget_subcategory_id }}
        </p>
      </div>

      <!-- Mode création nouvelle sous-catégorie -->
      <div v-else class="space-y-2">
        <input
          v-model="newSubcategoryName"
          type="text"
          class="input"
          placeholder="Nom de la nouvelle sous-catégorie"
          @keydown.enter.prevent="addNewSubcategory"
        />
        <input
          v-model.number="newSubcategoryAmount"
          type="number"
          step="0.01"
          min="0"
          class="input"
          placeholder="Montant prévu (€)"
        />
        <button
          type="button"
          @click="addNewSubcategory"
          class="btn btn-secondary w-full"
          :disabled="!newSubcategoryName || newSubcategoryAmount <= 0"
        >
          Ajouter la sous-catégorie
        </button>
        <p v-if="subcategoryError" class="text-sm text-red-600">
          {{ subcategoryError }}
        </p>
      </div>
    </div>

    <div>
      <label for="label" class="label">Libellé</label>
      <input
        id="label"
        v-model="label"
        v-bind="labelAttrs"
        type="text"
        class="input"
        :class="{ 'border-red-500': errors.label }"
        placeholder="Description de la dépense"
      />
      <p v-if="errors.label" class="mt-1 text-sm text-red-600">
        {{ errors.label }}
      </p>
    </div>

    <div>
      <label for="amount" class="label">Montant (€)</label>
      <input
        id="amount"
        v-model="amount"
        v-bind="amountAttrs"
        type="number"
        step="0.01"
        min="0"
        class="input"
        :class="{ 'border-red-500': errors.amount_cents }"
        placeholder="0.00"
      />
      <p v-if="errors.amount_cents" class="mt-1 text-sm text-red-600">
        {{ errors.amount_cents }}
      </p>
    </div>

    <div>
      <label for="payment_method" class="label">Moyen de paiement (optionnel)</label>
      <select id="payment_method" v-model="paymentMethod" v-bind="paymentMethodAttrs" class="input">
        <option value="">Aucun</option>
        <option value="CB">Carte bancaire</option>
        <option value="Espèces">Espèces</option>
        <option value="Virement">Virement</option>
        <option value="Prélèvement">Prélèvement</option>
        <option value="Chèque">Chèque</option>
      </select>
    </div>

    <div>
      <label for="notes" class="label">Notes (optionnel)</label>
      <textarea
        id="notes"
        v-model="notes"
        v-bind="notesAttrs"
        rows="3"
        class="input"
        :class="{ 'border-red-500': errors.notes }"
        placeholder="Notes supplémentaires..."
      />
    </div>

    <TagInput v-model="tagIds" label="Tags (optionnel)" :error="errors.tag_ids" />

    <div class="flex gap-2">
      <button
        type="submit"
        :disabled="isSubmitting || isCreatingSubcategory"
        class="flex-1 btn btn-primary"
      >
        {{ isSubmitting ? "Enregistrement..." : expense ? "Modifier" : "Ajouter la dépense" }}
      </button>
      <button v-if="onCancel" type="button" @click="onCancel" class="flex-1 btn btn-secondary">
        Annuler
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted } from "vue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import { expenseSchema } from "@/schemas/expense";
import type { Expense, BudgetCategory } from "@/types";
import apiClient from "@/api/axios";
import TagInput from "./TagInput.vue";
import { useTagsStore } from "@/stores/tags";

interface Props {
  expense?: Expense;
  categories: BudgetCategory[];
  budgetId?: number;
  onCancel?: () => void;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  submit: [
    values: {
      budget_subcategory_id: number;
      date: string;
      label: string;
      amount_cents: number;
      payment_method?: string;
      notes?: string;
      tag_ids?: number[];
    },
  ];
  categoryUpdated: [];
}>();

// State
const selectedCategoryId = ref<number | "">("");
const showNewSubcategoryInput = ref(false);
const newSubcategoryName = ref("");
const newSubcategoryAmount = ref(0);
const hasTriedSubmit = ref(false);
const subcategoryError = ref("");
const isCreatingSubcategory = ref(false);

// Tags store
const tagsStore = useTagsStore();

// Load tags on mount
onMounted(async () => {
  if (tagsStore.tags.length === 0) {
    await tagsStore.fetchTags();
  }
});

// Convert euros to cents
const amountToCents = (value: number) => Math.round(value * 100);
const centsToAmount = (cents: number) => cents / 100;

const { errors, defineField, handleSubmit, isSubmitting } = useForm({
  validationSchema: toTypedSchema(expenseSchema),
  initialValues: props.expense
    ? {
        budget_subcategory_id: props.expense.budgetSubcategoryId,
        date: props.expense.date,
        label: props.expense.label,
        amount_cents: props.expense.amountCents,
        payment_method: props.expense.paymentMethod || "",
        notes: props.expense.notes || "",
        tag_ids: props.expense.tags?.map((t) => t.id) || [],
      }
    : {
        date: new Date().toISOString().split("T")[0],
        budget_subcategory_id: undefined as any,
        label: "",
        amount_cents: 0,
        payment_method: "",
        notes: "",
        tag_ids: [],
      },
});

const [date, dateAttrs] = defineField("date");
const [budgetSubcategoryId, budgetSubcategoryIdAttrs] = defineField("budget_subcategory_id");
const [label, labelAttrs] = defineField("label");
const [paymentMethod, paymentMethodAttrs] = defineField("payment_method");
const [notes, notesAttrs] = defineField("notes");
const [tagIdsField] = defineField("tag_ids");

// Computed wrapper for tagIds to ensure it's always an array
const tagIds = computed({
  get: () => tagIdsField.value || [],
  set: (value) => {
    tagIdsField.value = value;
  },
});

// Handle amount separately to convert between euros and cents
const [amountCents, amountAttrs] = defineField("amount_cents");
const amount = computed({
  get: () => (amountCents.value ? centsToAmount(amountCents.value as number) : 0),
  set: (value) => {
    amountCents.value = amountToCents(Number(value));
  },
});

// Computed: Sous-catégories disponibles pour la catégorie sélectionnée
const availableSubcategories = computed(() => {
  if (!selectedCategoryId.value) return [];
  const category = props.categories.find((c) => c.id === selectedCategoryId.value);
  return category?.subcategories || [];
});

// Si édition, pré-sélectionner la catégorie
watch(
  () => props.expense,
  (expense) => {
    if (expense) {
      const category = props.categories.find((c) =>
        c.subcategories?.some((s) => s.id === expense.budgetSubcategoryId)
      );
      if (category) {
        selectedCategoryId.value = category.id;
      }
    }
  },
  { immediate: true }
);

// Reset subcategory quand on change de catégorie
watch(selectedCategoryId, () => {
  if (!props.expense) {
    budgetSubcategoryId.value = undefined as any;
  }
  showNewSubcategoryInput.value = false;
  newSubcategoryName.value = "";
  newSubcategoryAmount.value = 0;
  subcategoryError.value = "";
});

// Ajouter une nouvelle sous-catégorie
async function addNewSubcategory() {
  if (!newSubcategoryName.value || newSubcategoryAmount.value <= 0) {
    subcategoryError.value = "Nom et montant requis";
    return;
  }

  if (!selectedCategoryId.value || !props.budgetId) {
    subcategoryError.value = "Catégorie ou budget manquant";
    return;
  }

  isCreatingSubcategory.value = true;
  subcategoryError.value = "";

  try {
    const response = await apiClient.post(
      `/budgets/${props.budgetId}/categories/${selectedCategoryId.value}/subcategories`,
      {
        name: newSubcategoryName.value,
        planned_amount_cents: Math.round(newSubcategoryAmount.value * 100),
        sort_order: availableSubcategories.value.length,
      }
    );

    // Informer le parent que les catégories ont changé
    emit("categoryUpdated");

    // Sélectionner automatiquement la nouvelle sous-catégorie
    budgetSubcategoryId.value = response.data.id;

    // Reset et retour au mode sélection
    newSubcategoryName.value = "";
    newSubcategoryAmount.value = 0;
    showNewSubcategoryInput.value = false;
  } catch (error) {
    subcategoryError.value = "Erreur lors de la création";
  } finally {
    isCreatingSubcategory.value = false;
  }
}

const onSubmit = handleSubmit((values) => {
  hasTriedSubmit.value = true;
  if (!selectedCategoryId.value) {
    return;
  }
  emit("submit", values);
});
</script>

<style scoped>
.label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1;
}

.input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.btn {
  @apply px-4 py-2 rounded-md font-medium transition-colors;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-800 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>
