<template>
  <WidgetWrapper
    title="Évolution du Patrimoine"
    :loading="false"
    :error="null"
  >
    <div class="h-full">
      <WealthEvolutionChart :from="from" :to="to" />
    </div>
  </WidgetWrapper>
</template>

<script setup lang="ts">
import WealthEvolutionChart from "@/components/WealthEvolutionChart.vue";
import WidgetWrapper from "./WidgetWrapper.vue";

interface Props {
  from?: string;
  to?: string;
}

defineProps<Props>();
</script>
