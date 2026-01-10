<template>
  <div>
    <label for="amount" class="label">Montant (€)</label>
    <input
      id="amount"
      v-model="amountInEuros"
      type="number"
      step="0.01"
      min="0"
      class="input"
      :class="{ 'border-red-500': error }"
      placeholder="0.00"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

interface Props {
  modelValue: number | string | undefined; // en cents
  error?: string;
}

interface Emits {
  (e: "update:modelValue", value: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Conversion cents ↔ euros
const centsToEuros = (cents: number) => cents / 100;
const eurosToCents = (euros: number) => Math.round(euros * 100);

// Computed bidirectionnel pour la conversion
const amountInEuros = computed({
  get: () => {
    const value =
      typeof props.modelValue === "number" ? props.modelValue : Number(props.modelValue) || 0;
    return value ? centsToEuros(value) : 0;
  },
  set: (value) => {
    emit("update:modelValue", eurosToCents(Number(value)));
  },
});
</script>

<style scoped>
.label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1;
}

.input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}
</style>
