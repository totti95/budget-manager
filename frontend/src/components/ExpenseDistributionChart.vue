<template>
  <div class="expense-distribution-chart">
    <h3 class="text-lg font-semibold mb-4">Répartition des Dépenses</h3>
    <div v-if="loading" class="text-center py-8">Chargement...</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else-if="chartData && chartData.length > 0">
      <canvas ref="chartCanvas"></canvas>
    </div>
    <div v-else class="text-gray-500 text-center py-8">
      Aucune dépense enregistrée pour ce budget
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { Chart, registerables } from "chart.js";
import { statsApi } from "@/api/stats";

Chart.register(...registerables);

interface Props {
  budgetId: number;
}

const props = defineProps<Props>();

const chartCanvas = ref<HTMLCanvasElement | null>(null);
const chartInstance = ref<Chart | null>(null);
const chartData = ref<any[]>([]);
const loading = ref(false);
const error = ref("");

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
    const data = await statsApi.expenseDistribution(props.budgetId);
    chartData.value = data;
    renderChart();
  } catch (err: any) {
    error.value = err.response?.data?.message || "Erreur de chargement";
  } finally {
    loading.value = false;
  }
};

const renderChart = () => {
  if (!chartCanvas.value || chartData.value.length === 0) return;

  if (chartInstance.value) {
    chartInstance.value.destroy();
  }

  const ctx = chartCanvas.value.getContext("2d");
  if (!ctx) return;

  chartInstance.value = new Chart(ctx, {
    type: "pie",
    data: {
      labels: chartData.value.map((item) => item.label),
      datasets: [
        {
          data: chartData.value.map((item) => centsToEuros(item.value)),
          backgroundColor: colors.slice(0, chartData.value.length),
          borderColor: "white",
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: "right",
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.label || "";
              const value = context.parsed;
              const total = context.dataset.data.reduce(
                (sum: number, val: any) => sum + val,
                0,
              );
              const percentage = ((value / total) * 100).toFixed(1);
              return `${label}: ${new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
              }).format(value)} (${percentage}%)`;
            },
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
  },
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
