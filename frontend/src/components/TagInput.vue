<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useTagsStore } from "@/stores/tags";
import TagBadge from "./TagBadge.vue";
import type { Tag } from "@/types";

const props = defineProps<{
  modelValue: number[];
  label?: string;
  error?: string;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: number[]];
}>();

const tagsStore = useTagsStore();
const searchQuery = ref("");
const showDropdown = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);

// Load tags on mount
onMounted(async () => {
  if (tagsStore.tags.length === 0) {
    await tagsStore.fetchTags();
  }
});

// Selected tags
const selectedTags = computed(() => {
  return tagsStore.tags.filter((tag) => props.modelValue.includes(tag.id));
});

// Available tags for autocomplete (not already selected)
const availableTags = computed(() => {
  const selected = new Set(props.modelValue);
  return tagsStore.tags.filter((tag) => !selected.has(tag.id));
});

// Filtered tags based on search query
const filteredTags = computed(() => {
  if (!searchQuery.value) return availableTags.value;
  const query = searchQuery.value.toLowerCase();
  return availableTags.value.filter((tag) =>
    tag.name.toLowerCase().includes(query),
  );
});

// Check if search query matches an existing tag name exactly
const exactMatch = computed(() => {
  if (!searchQuery.value) return null;
  const query = searchQuery.value.toLowerCase();
  return tagsStore.tags.find((tag) => tag.name.toLowerCase() === query);
});

function selectTag(tag: Tag) {
  emit("update:modelValue", [...props.modelValue, tag.id]);
  searchQuery.value = "";
  inputRef.value?.focus();
}

function removeTag(tagId: number) {
  emit("update:modelValue", props.modelValue.filter((id) => id !== tagId));
}

async function createAndSelectTag() {
  if (!searchQuery.value.trim()) return;
  if (exactMatch.value) {
    // If exact match exists, just select it
    selectTag(exactMatch.value);
    return;
  }

  try {
    const newTag = await tagsStore.createTag({
      name: searchQuery.value.trim(),
    });
    emit("update:modelValue", [...props.modelValue, newTag.id]);
    searchQuery.value = "";
    inputRef.value?.focus();
  } catch (error) {
    console.error("Error creating tag:", error);
  }
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === "Enter") {
    event.preventDefault();
    if (filteredTags.value.length === 1) {
      selectTag(filteredTags.value[0]);
    } else if (!exactMatch.value && searchQuery.value.trim()) {
      createAndSelectTag();
    }
  } else if (
    event.key === "Backspace" &&
    !searchQuery.value &&
    selectedTags.value.length > 0
  ) {
    // Remove last tag on backspace if input is empty
    const lastTag = selectedTags.value[selectedTags.value.length - 1];
    removeTag(lastTag.id);
  }
}

function handleBlur() {
  setTimeout(() => {
    showDropdown.value = false;
  }, 200);
}
</script>

<template>
  <div class="space-y-2">
    <label v-if="label" class="block text-sm font-medium text-gray-700">
      {{ label }}
    </label>

    <div
      class="relative border rounded-md p-2 bg-white focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500"
      :class="{ 'border-red-500': error }"
    >
      <!-- Selected tags -->
      <div class="flex flex-wrap gap-2 mb-2" v-if="selectedTags.length > 0">
        <TagBadge
          v-for="tag in selectedTags"
          :key="tag.id"
          :tag="tag"
          removable
          @remove="removeTag(tag.id)"
        />
      </div>

      <!-- Input field -->
      <input
        ref="inputRef"
        v-model="searchQuery"
        @focus="showDropdown = true"
        @blur="handleBlur"
        @keydown="handleKeydown"
        type="text"
        placeholder="Rechercher ou créer un tag..."
        class="w-full outline-none text-sm"
      />

      <!-- Dropdown -->
      <div
        v-if="showDropdown && (filteredTags.length > 0 || searchQuery)"
        class="absolute left-0 right-0 top-full mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-y-auto z-10"
      >
        <!-- Existing tags -->
        <button
          v-for="tag in filteredTags"
          :key="tag.id"
          @click="selectTag(tag)"
          type="button"
          class="w-full px-3 py-2 text-left hover:bg-gray-100 flex items-center gap-2"
        >
          <span
            class="w-3 h-3 rounded-full"
            :style="{ backgroundColor: tag.color }"
          ></span>
          <span class="text-sm">{{ tag.name }}</span>
        </button>

        <!-- Create new tag option -->
        <button
          v-if="searchQuery && !exactMatch"
          @click="createAndSelectTag"
          type="button"
          class="w-full px-3 py-2 text-left hover:bg-gray-100 flex items-center gap-2 border-t text-blue-600"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          <span class="text-sm">Créer "{{ searchQuery }}"</span>
        </button>

        <!-- No results -->
        <div
          v-if="filteredTags.length === 0 && !searchQuery"
          class="px-3 py-2 text-sm text-gray-500"
        >
          Aucun tag disponible
        </div>
      </div>
    </div>

    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
  </div>
</template>
