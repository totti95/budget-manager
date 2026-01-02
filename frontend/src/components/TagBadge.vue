<script setup lang="ts">
import type { Tag } from "@/types";

defineProps<{
  tag: Tag;
  removable?: boolean;
}>();

defineEmits<{
  remove: [];
}>();

// Convert hex color to RGBA with transparency for background
function hexToRgba(hex: string, alpha: number = 0.15) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-sm font-medium border"
    :style="{
      backgroundColor: hexToRgba(tag.color),
      borderColor: tag.color,
      color: tag.color,
    }"
  >
    <span>{{ tag.name }}</span>
    <button
      v-if="removable"
      @click.stop="$emit('remove')"
      type="button"
      class="hover:opacity-70 transition-opacity"
      :aria-label="`Retirer le tag ${tag.name}`"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-3 w-3"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    </button>
  </span>
</template>
