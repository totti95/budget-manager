<template>
  <WidgetWrapper
    title="Résumé du mois"
    :loading="false"
    :error="null"
  >
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Prévu</p>
        <p class="text-2xl font-bold">
          <MoneyDisplay :cents="statsStore.summary?.totalPlannedCents || 0" />
        </p>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Réel</p>
        <p class="text-2xl font-bold">
          <MoneyDisplay :cents="statsStore.summary?.totalActualCents || 0" />
        </p>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Économie</p>
        <p class="text-2xl font-bold">
          <MoneyDisplay
            :cents="-(statsStore.summary?.varianceCents || 0)"
            :colorize="true"
            :show-sign="true"
          />
        </p>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Dépenses</p>
        <p class="text-2xl font-bold">
          {{ statsStore.summary?.expenseCount || 0 }}
        </p>
      </div>
    </div>
  </WidgetWrapper>
</template>

<script setup lang="ts">
import { useStatsStore } from "@/stores/stats";
import WidgetWrapper from "./WidgetWrapper.vue";
import MoneyDisplay from "@/components/MoneyDisplay.vue";

const statsStore = useStatsStore();
</script>
