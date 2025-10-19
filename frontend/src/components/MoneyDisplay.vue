<template>
  <span :class="colorClass">{{ formattedAmount }}</span>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  cents: number;
  showSign?: boolean;
  colorize?: boolean;
}>();

const formattedAmount = computed(() => {
  const euros = props.cents / 100;
  const formatted = new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
  }).format(Math.abs(euros));

  if (props.showSign && euros > 0) {
    return "+" + formatted;
  }
  if (euros < 0) {
    return "-" + formatted;
  }
  return formatted;
});

const colorClass = computed(() => {
  if (!props.colorize) return "";
  if (props.cents > 0) return "text-green-600 dark:text-green-400";
  if (props.cents < 0) return "text-red-600 dark:text-red-400";
  return "";
});
</script>
