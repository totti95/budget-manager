<template>
  <div v-if="categoryId">
    <div class="flex items-center justify-between mb-2">
      <label for="subcategory" class="label">Sous-catégorie</label>
      <button
        type="button"
        @click="toggleCreateMode"
        class="text-sm text-blue-600 hover:text-blue-700"
      >
        {{ showNewSubcategoryInput ? "↩ Choisir existante" : "+ Nouvelle sous-catégorie" }}
      </button>
    </div>

    <!-- Mode sélection existante -->
    <div v-if="!showNewSubcategoryInput">
      <select
        id="subcategory"
        :value="modelValue"
        @input="handleSubcategorySelect"
        class="input"
        :class="{ 'border-red-500': error }"
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
      <p v-if="error" class="mt-1 text-sm text-red-600">
        {{ error }}
      </p>
    </div>

    <!-- Mode création nouvelle sous-catégorie -->
    <div v-else class="space-y-2">
      <input
        v-model="newSubcategoryName"
        type="text"
        class="input"
        placeholder="Nom de la nouvelle sous-catégorie"
        @keydown.enter.prevent="createSubcategory"
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
        @click="createSubcategory"
        class="btn btn-secondary w-full"
        :disabled="!canCreate || isCreating"
      >
        {{ isCreating ? "Création..." : "Ajouter la sous-catégorie" }}
      </button>
      <p v-if="createError" class="text-sm text-red-600">
        {{ createError }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import type { BudgetCategory } from "@/types";
import apiClient from "@/api/axios";

interface Props {
  modelValue: number | string | undefined;
  categoryId: number | "";
  categories: BudgetCategory[];
  budgetId?: number;
  error?: string;
}

interface Emits {
  (e: "update:modelValue", value: number | ""): void;
  (e: "subcategoryCreated"): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// État local
const showNewSubcategoryInput = ref(false);
const newSubcategoryName = ref("");
const newSubcategoryAmount = ref(0);
const createError = ref("");
const isCreating = ref(false);

// Computed
const availableSubcategories = computed(() => {
  if (!props.categoryId) return [];
  const category = props.categories.find((c) => c.id === props.categoryId);
  return category?.subcategories || [];
});

const canCreate = computed(() => {
  return newSubcategoryName.value.trim() && newSubcategoryAmount.value > 0;
});

// Watchers
watch(
  () => props.categoryId,
  () => {
    // Reset lors du changement de catégorie
    showNewSubcategoryInput.value = false;
    newSubcategoryName.value = "";
    newSubcategoryAmount.value = 0;
    createError.value = "";
  }
);

// Methods
function handleSubcategorySelect(event: Event) {
  const target = event.target as HTMLSelectElement;
  const value = target.value === "" || !target.value ? "" : Number(target.value);
  emit("update:modelValue", value);
}

function toggleCreateMode() {
  showNewSubcategoryInput.value = !showNewSubcategoryInput.value;
  createError.value = "";
}

async function createSubcategory() {
  if (!canCreate.value || !props.budgetId || !props.categoryId) {
    createError.value = "Informations manquantes";
    return;
  }

  isCreating.value = true;
  createError.value = "";

  try {
    const response = await apiClient.post(
      `/budgets/${props.budgetId}/categories/${props.categoryId}/subcategories`,
      {
        name: newSubcategoryName.value,
        plannedAmountCents: Math.round(newSubcategoryAmount.value * 100),
        sortOrder: availableSubcategories.value.length,
      }
    );

    // Informer le parent que les catégories ont changé
    emit("subcategoryCreated");

    // Sélectionner automatiquement la nouvelle sous-catégorie
    emit("update:modelValue", response.data.id);

    // Reset et retour au mode sélection
    newSubcategoryName.value = "";
    newSubcategoryAmount.value = 0;
    showNewSubcategoryInput.value = false;
  } catch (error) {
    createError.value = "Erreur lors de la création";
  } finally {
    isCreating.value = false;
  }
}
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

.btn-secondary {
  @apply bg-gray-200 text-gray-800 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>
