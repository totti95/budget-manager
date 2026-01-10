<template>
  <div>
    <label for="category" class="label">Catégorie</label>
    <select
      id="category"
      :value="modelValue"
      @input="handleInput"
      class="input"
      :class="{ 'border-red-500': showError && !modelValue }"
    >
      <option value="">Sélectionner une catégorie</option>
      <option v-for="category in categories" :key="category.id" :value="category.id">
        {{ category.name }}
      </option>
    </select>
    <p v-if="showError && !modelValue" class="mt-1 text-sm text-red-600">
      Veuillez sélectionner une catégorie
    </p>
  </div>
</template>

<script setup lang="ts">
import type { BudgetCategory } from "@/types";

interface Props {
  modelValue: number | "";
  categories: BudgetCategory[];
  showError?: boolean;
}

interface Emits {
  (e: "update:modelValue", value: number | ""): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

function handleInput(event: Event) {
  const target = event.target as HTMLSelectElement;
  const value = target.value === "" ? "" : Number(target.value);
  emit("update:modelValue", value);
}
</script>

<style scoped>
.label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1;
}

.input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}
</style>
