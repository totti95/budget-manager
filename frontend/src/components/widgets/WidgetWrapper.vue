<template>
  <div class="widget-wrapper card h-full flex flex-col">
    <div class="widget-header flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">{{ title }}</h3>
      <div class="widget-actions flex gap-2">
        <button
          v-if="onRefresh"
          @click="handleRefresh"
          class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded"
          :disabled="loading"
        >
          <svg
            class="w-5 h-5"
            :class="{ 'animate-spin': loading }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
        </button>
      </div>
    </div>

    <div
      v-if="loading && !allowLoadingWithContent"
      class="flex-1 flex items-center justify-center"
    >
      <p class="text-gray-500">Chargement...</p>
    </div>

    <div v-else-if="error" class="flex-1 flex flex-col items-center justify-center">
      <p class="text-red-600 mb-4">{{ error }}</p>
      <button v-if="onRefresh" @click="handleRefresh" class="btn btn-primary">
        Réessayer
      </button>
    </div>

    <div v-else class="widget-content flex-1 overflow-auto">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string;
  loading?: boolean;
  error?: string | null;
  allowLoadingWithContent?: boolean;
  onRefresh?: () => void;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: null,
  allowLoadingWithContent: false,
});

function handleRefresh() {
  if (props.onRefresh) {
    props.onRefresh();
  }
}
</script>
