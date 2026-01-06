<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div
      class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto"
    >
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Ajouter un widget</h2>
        <button
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <button
          v-for="widgetType in availableWidgets"
          :key="widgetType"
          @click="$emit('addWidget', widgetType)"
          class="p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 text-left transition-colors"
          :disabled="existingWidgets.includes(widgetType)"
          :class="{
            'opacity-50 cursor-not-allowed': existingWidgets.includes(widgetType),
          }"
        >
          <h3 class="font-semibold mb-1">
            {{ getWidgetDefinition(widgetType).label }}
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ getWidgetDefinition(widgetType).description }}
          </p>
          <span
            v-if="existingWidgets.includes(widgetType)"
            class="text-xs text-blue-600 mt-2 block"
          >
            Déjà ajouté
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { getAllWidgetTypes, getWidgetDefinition } from "./widgets/widgetRegistry";
import type { WidgetType } from "@/types";

interface Props {
  existingWidgets: WidgetType[];
}

defineProps<Props>();

defineEmits<{
  addWidget: [widgetType: WidgetType];
  close: [];
}>();

const availableWidgets = computed(() => getAllWidgetTypes());
</script>
