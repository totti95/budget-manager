<template>
  <form @submit="onSubmit" class="space-y-4">
    <!-- Date -->
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
    <CategorySelector
      v-model="selectedCategoryId"
      :categories="categories"
      :show-error="hasTriedSubmit"
    />

    <!-- Sous-catégorie -->
    <SubcategoryManager
      v-model="budgetSubcategoryId"
      :category-id="selectedCategoryId"
      :categories="categories"
      :budget-id="budgetId"
      :error="errors.budget_subcategory_id"
      @subcategory-created="emit('categoryUpdated')"
    />

    <!-- Libellé -->
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

    <!-- Montant -->
    <AmountInput v-model="amountCents" :error="errors.amount_cents" />

    <!-- Moyen de paiement -->
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

    <!-- Notes -->
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

    <!-- Tags -->
    <TagInput v-model="tagIds" label="Tags (optionnel)" :error="errors.tag_ids" />

    <!-- Boutons -->
    <div class="flex gap-2">
      <button type="submit" :disabled="isSubmitting" class="flex-1 btn btn-primary">
        {{ isSubmitting ? "Enregistrement..." : expense ? "Modifier" : "Ajouter la dépense" }}
      </button>
      <button v-if="onCancel" type="button" @click="onCancel" class="flex-1 btn btn-secondary">
        Annuler
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/valibot";
import { expenseSchema } from "@/schemas/expense";
import type { Expense, BudgetCategory } from "@/types";
import TagInput from "./TagInput.vue";
import CategorySelector from "./form/CategorySelector.vue";
import SubcategoryManager from "./form/SubcategoryManager.vue";
import AmountInput from "./form/AmountInput.vue";
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

// État
const selectedCategoryId = ref<number | "">("");
const hasTriedSubmit = ref(false);

// Tags store
const tagsStore = useTagsStore();

// Load tags on mount
onMounted(async () => {
  if (tagsStore.tags.length === 0) {
    await tagsStore.fetchTags();
  }
});

// Formulaire avec validation
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
        budget_subcategory_id: "",
        label: "",
        amount_cents: 0,
        payment_method: "",
        notes: "",
        tag_ids: [],
      },
});

// Champs du formulaire
const [date, dateAttrs] = defineField("date");
const [budgetSubcategoryId] = defineField("budget_subcategory_id");
const [label, labelAttrs] = defineField("label");
const [amountCents] = defineField("amount_cents");
const [paymentMethod, paymentMethodAttrs] = defineField("payment_method");
const [notes, notesAttrs] = defineField("notes");
const [tagIdsField] = defineField("tag_ids");

// Computed wrapper pour tagIds
const tagIds = computed({
  get: () => tagIdsField.value || [],
  set: (value) => {
    tagIdsField.value = value;
  },
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
    budgetSubcategoryId.value = "";
  }
});

// Soumission du formulaire
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
