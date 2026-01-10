<template>
  <div class="expense-distribution-chart">
    <h3 class="text-lg font-semibold mb-4">Répartition des Dépenses</h3>
    <div v-if="loading" class="text-center py-8">Chargement...</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else-if="chartData && chartData.length > 0" class="h-96">
      <canvas ref="chartCanvas"></canvas>
    </div>
    <div v-else class="text-gray-500 text-center py-8">
      Aucune dépense enregistrée pour ce budget
    </div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from "vue";
import { Chart, registerables, type ChartConfiguration, type TooltipItem } from "chart.js";
import { statsApi } from "@/api/stats";

Chart.register(...registerables);

interface Props {
  budgetId: number;
}

const props = defineProps<Props>();

type ExpenseItem = { label: string; value: number };

const chartCanvas = ref<HTMLCanvasElement | null>(null);
const chartData = ref<ExpenseItem[]>([]);
const loading = ref(false);
const error = ref("");

let chartInstance: Chart<"pie", number[], string> | null = null;
const centsToEuros = (cents: number) => cents / 100;

// Color palette for categories
const colors = [
  "rgb(239, 68, 68)", // red
  "rgb(249, 115, 22)", // orange
  "rgb(234, 179, 8)", // yellow
  "rgb(34, 197, 94)", // green
  "rgb(59, 130, 246)", // blue
  "rgb(99, 102, 241)", // indigo
  "rgb(168, 85, 247)", // purple
  "rgb(236, 72, 153)", // pink
  "rgb(156, 163, 175)", // gray
  "rgb(20, 184, 166)", // teal
];

const loadData = async () => {
  loading.value = true;
  error.value = "";

  try {
    chartData.value = await statsApi.expenseDistribution(props.budgetId);
  } catch (err: any) {
    chartData.value = [];
    error.value = err.response?.data?.message || "Erreur de chargement";
  } finally {
    loading.value = false;
    await nextTick(); // wait for DOM to switch from loader to canvas
    if (chartData.value.length > 0) {
      renderChart();
    } else {
      chartInstance?.destroy();
      chartInstance = null;
    }
  }
};

const renderChart = () => {
  if (!chartCanvas.value || chartData.value.length === 0) return;

  chartInstance?.destroy();

  const ctx = chartCanvas.value.getContext("2d");
  if (!ctx) return;

  const labels = chartData.value.map((item) => item.label);
  const values = chartData.value.map((item) => centsToEuros(item.value));

  const config = {
    type: "pie",
    data: {
      labels,
      datasets: [
        {
          data: values,
          backgroundColor: colors.slice(0, values.length),
          borderColor: "white",
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: "right" as const, // ok (union type)
        },
        tooltip: {
          callbacks: {
            label: (context: TooltipItem<"pie">) => {
              const label = context.label ?? "";
              const value = context.parsed; // number pour un pie
              const total = (context.dataset.data as number[]).reduce((sum, v) => sum + v, 0);

              const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : "0.0";

              return `${label}: ${new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
              }).format(value)} (${percentage}%)`;
            },
          },
        },
      },
    },
  } satisfies ChartConfiguration<"pie", number[], string>;

  chartInstance = new Chart(ctx, config);
};

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

<style scoped>
.expense-distribution-chart {
  background: white;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>
