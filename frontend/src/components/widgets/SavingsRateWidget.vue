<template>
  <WidgetWrapper
    title="Taux d'Épargne"
    :loading="loading"
    :error="error"
    :on-refresh="loadData"
  >
    <!-- Current month rate -->
    <div
      v-if="currentMonthData"
      class="mb-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg"
    >
      <p class="text-sm text-gray-600 dark:text-gray-400">Ce mois</p>
      <p class="text-3xl font-bold" :class="rateColorClass">
        {{ currentMonthData.savingsRatePercent?.toFixed(1) ?? "N/A" }}%
      </p>
      <p class="text-sm mt-1">
        <MoneyDisplay
          :cents="currentMonthData.savingsCents"
          :colorize="true"
          :show-sign="true"
        />
        sur <MoneyDisplay :cents="currentMonthData.revenueCents" />
      </p>
    </div>

    <!-- Evolution chart -->
    <div v-if="chartData.length > 1" class="h-64">
      <canvas ref="chartCanvas"></canvas>
    </div>
    <div v-else class="text-center py-8 text-gray-500">
      Pas assez de données pour afficher l'évolution
    </div>
  </WidgetWrapper>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, onUnmounted, watch } from "vue";
import { Chart, registerables } from "chart.js";
import { useStatsStore } from "@/stores/stats";
import WidgetWrapper from "./WidgetWrapper.vue";
import MoneyDisplay from "@/components/MoneyDisplay.vue";
import type { SavingsRateDataPoint } from "@/types";

Chart.register(...registerables);

interface Props {
  months?: number;
}

const props = withDefaults(defineProps<Props>(), {
  months: 12,
});

const statsStore = useStatsStore();
const chartData = ref<SavingsRateDataPoint[]>([]);
const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart<"line"> | null = null;
const loading = ref(false);
const error = ref<string | null>(null);

const currentMonthData = computed(
  () => chartData.value[chartData.value.length - 1] || null,
);

const rateColorClass = computed(() => {
  const rate = currentMonthData.value?.savingsRatePercent;
  if (rate === null || rate === undefined) return "text-gray-500";
  if (rate >= 20) return "text-green-600";
  if (rate >= 10) return "text-blue-600";
  if (rate >= 0) return "text-yellow-600";
  return "text-red-600";
});

async function loadData() {
  loading.value = true;
  error.value = null;
  try {
    chartData.value = await statsStore.fetchSavingsRateEvolution({
      months: props.months,
    });
    await nextTick();
    renderChart();
  } catch (err: any) {
    error.value = "Erreur de chargement";
  } finally {
    loading.value = false;
  }
}

function renderChart() {
  if (!chartCanvas.value || chartData.value.length < 2) return;

  chartInstance?.destroy();

  const ctx = chartCanvas.value.getContext("2d");
  if (!ctx) return;

  chartInstance = new Chart(ctx, {
    type: "line",
    data: {
      labels: chartData.value.map((d) => d.monthLabel),
      datasets: [
        {
          label: "Taux d'épargne (%)",
          data: chartData.value.map((d) => d.savingsRatePercent || 0),
          borderColor: "rgb(59, 130, 246)",
          backgroundColor: "rgba(59, 130, 246, 0.1)",
          fill: true,
          tension: 0.4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (context) => `${context.parsed.y.toFixed(1)}%`,
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => `${value}%`,
          },
        },
      },
    },
  });
}

onMounted(() => loadData());
watch(() => props.months, () => loadData());
onUnmounted(() => chartInstance?.destroy());
</script>
