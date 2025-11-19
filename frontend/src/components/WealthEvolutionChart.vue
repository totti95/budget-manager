<template>
  <div class="wealth-evolution-chart">
    <h3 class="text-lg font-semibold mb-4">Évolution du Patrimoine</h3>
    <div v-if="loading" class="text-center py-8">Chargement...</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else-if="chartData">
      <canvas ref="chartCanvas"></canvas>
    </div>
    <div v-else class="text-gray-500 text-center py-8">
      Aucune donnée d'historique disponible
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { Chart, registerables } from "chart.js";
import { statsApi } from "@/api/stats";

Chart.register(...registerables);

interface Props {
  from?: string;
  to?: string;
}

const props = defineProps<Props>();

const chartCanvas = ref<HTMLCanvasElement | null>(null);
const chartInstance = ref<Chart | null>(null);
const chartData = ref<any>(null);
const loading = ref(false);
const error = ref("");

const centsToEuros = (cents: number) => cents / 100;

const loadData = async () => {
  loading.value = true;
  error.value = "";

  try {
    const data = await statsApi.wealthEvolution({
      from: props.from,
      to: props.to,
    });
    chartData.value = data;
    renderChart();
  } catch (err: any) {
    error.value = err.response?.data?.message || "Erreur de chargement";
  } finally {
    loading.value = false;
  }
};

const renderChart = () => {
  if (!chartCanvas.value || !chartData.value) return;

  if (chartInstance.value) {
    chartInstance.value.destroy();
  }

  const ctx = chartCanvas.value.getContext("2d");
  if (!ctx) return;

  chartInstance.value = new Chart(ctx, {
    type: "line",
    data: {
      labels: chartData.value.labels,
      datasets: chartData.value.datasets.map((dataset: any, index: number) => ({
        label: dataset.label,
        data: dataset.data.map(centsToEuros),
        borderColor:
          index === 0
            ? "rgb(34, 197, 94)" // green for assets
            : index === 1
              ? "rgb(239, 68, 68)" // red for liabilities
              : "rgb(59, 130, 246)", // blue for net worth
        backgroundColor:
          index === 0
            ? "rgba(34, 197, 94, 0.1)"
            : index === 1
              ? "rgba(239, 68, 68, 0.1)"
              : "rgba(59, 130, 246, 0.1)",
        fill: true,
        tension: 0.4,
      })),
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: "top",
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              let label = context.dataset.label || "";
              if (label) {
                label += ": ";
              }
              label += new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
              }).format(context.parsed.y);
              return label;
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
                maximumFractionDigits: 0,
              }).format(value as number);
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

watch([() => props.from, () => props.to], () => {
  loadData();
});
</script>

<style scoped>
.wealth-evolution-chart {
  background: white;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>
