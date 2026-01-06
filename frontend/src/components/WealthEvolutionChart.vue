<template>
  <div class="wealth-evolution-chart">
    <div v-if="loading" class="text-center py-8">Chargement...</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else-if="chartData" class="h-96">
      <canvas ref="chartCanvas"></canvas>
    </div>
    <div v-else class="text-gray-500 text-center py-8">
      Aucune donnée d'historique disponible
    </div>
  </div>
</template>

<script setup lang="ts">
import {nextTick, onMounted, ref, watch} from "vue";
import {Chart, registerables, type TooltipItem} from "chart.js";
import {statsApi} from "@/api/stats";

Chart.register(...registerables);

interface Props {
  from?: string;
  to?: string;
}

const props = defineProps<Props>();

const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart<"line", number[], string> | null = null;
type WealthDataset = { label: string; data: number[] };
type WealthChartData = { labels: string[]; datasets: WealthDataset[] };
const chartData = ref<WealthChartData | null>(null);
const loading = ref(false);
const error = ref("");

const centsToEuros = (cents: number) => cents / 100;

const loadData = async () => {
  loading.value = true;
  error.value = "";

  try {
    chartData.value = await statsApi.wealthEvolution({
      from: props.from,
      to: props.to,
    });
  } catch (err: any) {
    chartData.value = null;
    error.value = err.response?.data?.message || "Erreur de chargement";
  } finally {
    loading.value = false;
    await nextTick(); // wait for canvas before drawing
    if (chartData.value) {
      renderChart();
    } else {
      chartInstance?.destroy();
      chartInstance = null;
    }
  }
};

const renderChart = () => {
  if (!chartCanvas.value || !chartData.value) return;

  chartInstance?.destroy();

  const ctx = chartCanvas.value.getContext("2d");
  if (!ctx) return;

  chartInstance = new Chart(ctx, {
    type: "line",
    data: {
      labels: chartData.value.labels,
      datasets: chartData.value.datasets.map((dataset, index) => ({
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
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: "top",
        },
        tooltip: {
          callbacks: {
            label: (context: TooltipItem<"line">) => {
              const label = context.dataset.label
                ? `${context.dataset.label}: `
                : "";
              return (
                label +
                new Intl.NumberFormat("fr-FR", {
                  style: "currency",
                  currency: "EUR",
                }).format(context.parsed.y ?? 0)
              );
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
                maximumFractionDigits: 0,
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
