<template>
  <div class="expenses-by-tag-chart">
    <h3 class="text-lg font-semibold mb-4">Dépenses par Tag</h3>
    <div v-if="loading" class="text-center py-8">Chargement...</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else-if="tagStats && tagStats.length > 0" class="h-96">
      <canvas ref="chartCanvas"></canvas>
    </div>
    <div v-else class="text-gray-500 text-center py-8">Aucune dépense avec tags pour ce budget</div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from "vue";
import { Chart, registerables, type TooltipItem } from "chart.js";
import { statsApi } from "@/api/stats";
import type { TagStats } from "@/types";

Chart.register(...registerables);

interface Props {
  budgetId: number;
}

const props = defineProps<Props>();

const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart<"bar", number[], string> | null = null;
const tagStats = ref<TagStats[]>([]);
const loading = ref(false);
const error = ref("");

const centsToEuros = (cents: number) => cents / 100;

const loadData = async () => {
  loading.value = true;
  error.value = "";

  try {
    tagStats.value = await statsApi.byTag(props.budgetId);
  } catch (err: any) {
    tagStats.value = [];
    error.value = err.response?.data?.message || "Erreur de chargement";
  } finally {
    loading.value = false;
    await nextTick();
    if (tagStats.value.length > 0) {
      renderChart();
    } else {
      chartInstance?.destroy();
      chartInstance = null;
    }
  }
};

const renderChart = () => {
  if (!chartCanvas.value || tagStats.value.length === 0) return;

  chartInstance?.destroy();

  const ctx = chartCanvas.value.getContext("2d");
  if (!ctx) return;

  chartInstance = new Chart(ctx, {
    type: "bar",
    data: {
      labels: tagStats.value.map((stat) => stat.tagName),
      datasets: [
        {
          label: "Dépenses (€)",
          data: tagStats.value.map((stat) => centsToEuros(stat.totalAmountCents)),
          backgroundColor: tagStats.value.map((stat) => stat.tagColor),
          borderColor: tagStats.value.map((stat) => stat.tagColor),
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: (context: TooltipItem<"bar">) => {
              const value = context.parsed.y ?? 0;
              const index = context.dataIndex;
              const count = tagStats.value[index].expenseCount;
              return `${new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
              }).format(value)} (${count} dépense${count > 1 ? "s" : ""})`;
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) =>
              new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
              }).format(value as number),
          },
        },
      },
    },
  });
};

onMounted(() => {
  loadData();
});

watch(
  () => props.budgetId,
  () => {
    loadData();
  }
);
</script>

<style scoped>
.expenses-by-tag-chart {
  background: white;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>
