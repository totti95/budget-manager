<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    <div class="relative">
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :autocomplete="autocomplete"
        :class="[
          'form-input',
          {
            'border-red-500 focus:border-red-500 focus:ring-red-500': error,
            'opacity-50 cursor-not-allowed': disabled,
          },
        ]"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        @blur="$emit('blur')"
      />
      <div
        v-if="error"
        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
      >
        <svg
          class="h-5 w-5 text-red-500"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
    </div>
    <p v-if="error" class="form-error">
      {{ error }}
    </p>
    <p v-else-if="hint" class="form-hint">
      {{ hint }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

interface Props {
  id?: string;
  label?: string;
  type?: string;
  modelValue?: string | number;
  placeholder?: string;
  error?: string;
  hint?: string;
  disabled?: boolean;
  required?: boolean;
  autocomplete?: string;
}

const props = withDefaults(defineProps<Props>(), {
  type: "text",
  disabled: false,
  required: false,
});

defineEmits<{
  (e: "update:modelValue", value: string): void;
  (e: "blur"): void;
}>();

const id = computed(() => props.id || `input-${Math.random().toString(36).substr(2, 9)}`);
</script>

<style scoped>
.form-group {
  @apply w-full;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5;
}

.form-input {
  @apply w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm
         bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
         placeholder-gray-400 dark:placeholder-gray-500
         focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         transition-colors duration-200;
}

.form-error {
  @apply mt-1.5 text-sm text-red-600 dark:text-red-400 flex items-start;
}

.form-error::before {
  content: "⚠ ";
  @apply mr-1;
}

.form-hint {
  @apply mt-1.5 text-sm text-gray-500 dark:text-gray-400 italic;
}
</style>
