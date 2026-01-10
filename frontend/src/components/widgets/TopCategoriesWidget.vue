<template>
  <WidgetWrapper title="Top 5 Catégories" :loading="loading" :error="error" :on-refresh="loadData">
    <div v-if="categories.length > 0" class="space-y-3">
      <div
        v-for="(cat, index) in categories"
        :key="cat.id"
        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
      >
        <div class="flex items-center gap-3">
          <span class="text-2xl font-bold text-gray-400">#{{ index + 1 }}</span>
          <div>
            <p class="font-medium">{{ cat.name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ cat.expenseCount }} dépense(s)
            </p>
          </div>
        </div>
        <div class="text-right">
          <MoneyDisplay :cents="cat.actualCents" class="text-lg font-bold" />
          <p class="text-sm" :class="cat.varianceCents > 0 ? 'text-red-600' : 'text-green-600'">
            {{ cat.varianceCents > 0 ? "+" : "" }}
            <MoneyDisplay :cents="Math.abs(cat.varianceCents)" :show-sign="false" />
          </p>
        </div>
      </div>
    </div>
    <div v-else class="text-center py-8 text-gray-500">Aucune dépense enregistrée</div>
  </WidgetWrapper>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { useStatsStore } from "@/stores/stats";
import WidgetWrapper from "./WidgetWrapper.vue";
import MoneyDisplay from "@/components/MoneyDisplay.vue";
import type { TopCategoryStats } from "@/types";

interface Props {
  budgetId: number;
  limit?: number;
}

const props = withDefaults(defineProps<Props>(), {
  limit: 5,
});

const statsStore = useStatsStore();
const categories = ref<TopCategoryStats[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

async function loadData() {
  if (!props.budgetId) return;

  loading.value = true;
  error.value = null;
  try {
    categories.value = await statsStore.fetchTopCategories(props.budgetId, props.limit);
  } catch (err: any) {
    error.value = "Erreur de chargement";
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  if (props.budgetId) loadData();
});

watch(
  () => props.budgetId,
  (newId, oldId) => {
    if (newId !== oldId && newId) loadData();
  },
  { immediate: false }
);
</script>
