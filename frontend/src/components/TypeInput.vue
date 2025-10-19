<template>
  <div class="relative">
    <input
      v-model="inputValue"
      @input="handleInput"
      @focus="showSuggestions = true"
      @blur="handleBlur"
      @keydown.down.prevent="navigateDown"
      @keydown.up.prevent="navigateUp"
      @keydown.enter.prevent="selectHighlighted"
      @keydown.escape="showSuggestions = false"
      type="text"
      class="input"
      :class="inputClass"
      placeholder="Ex: immobilier, épargne..."
      autocomplete="off"
    />

    <!-- Suggestions dropdown -->
    <div
      v-if="showSuggestions && filteredSuggestions.length > 0"
      class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto"
    >
      <button
        v-for="(suggestion, index) in filteredSuggestions"
        :key="suggestion"
        type="button"
        @mousedown.prevent="selectSuggestion(suggestion)"
        class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 first:rounded-t-lg last:rounded-b-lg"
        :class="{
          'bg-gray-100 dark:bg-gray-700': index === highlightedIndex,
        }"
      >
        {{ formatType(suggestion) }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";

interface Props {
  modelValue: string;
  suggestions: string[];
  inputClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
  inputClass: "",
});

const emit = defineEmits<{
  "update:modelValue": [value: string];
}>();

const inputValue = ref(props.modelValue);
const showSuggestions = ref(false);
const highlightedIndex = ref(-1);

watch(
  () => props.modelValue,
  (newValue) => {
    inputValue.value = newValue;
  },
);

const filteredSuggestions = computed(() => {
  if (!inputValue.value) {
    return props.suggestions;
  }
  const search = inputValue.value.toLowerCase();
  return props.suggestions.filter((s) => s.toLowerCase().includes(search));
});

function handleInput(event: Event) {
  const value = (event.target as HTMLInputElement).value;
  inputValue.value = value;
  emit("update:modelValue", value);
  showSuggestions.value = true;
  highlightedIndex.value = -1;
}

function selectSuggestion(suggestion: string) {
  inputValue.value = suggestion;
  emit("update:modelValue", suggestion);
  showSuggestions.value = false;
  highlightedIndex.value = -1;
}

function handleBlur() {
  // Delay to allow click on suggestion to register
  setTimeout(() => {
    showSuggestions.value = false;
    highlightedIndex.value = -1;
  }, 200);
}

function navigateDown() {
  if (highlightedIndex.value < filteredSuggestions.value.length - 1) {
    highlightedIndex.value++;
  }
}

function navigateUp() {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--;
  }
}

function selectHighlighted() {
  if (
    highlightedIndex.value >= 0 &&
    highlightedIndex.value < filteredSuggestions.value.length
  ) {
    selectSuggestion(filteredSuggestions.value[highlightedIndex.value]);
  }
}

function formatType(type: string): string {
  return type.charAt(0).toUpperCase() + type.slice(1);
}
</script>
