<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Title,
  Tooltip,
  Legend,
} from "chart.js";
import type { BudgetComparison } from "@/types";

// Register Chart.js components
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Title, Tooltip, Legend);

interface Props {
  comparison: BudgetComparison;
}

const props = defineProps<Props>();

const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

// Prepare chart data
const chartData = computed(() => {
  // Collect all unique categories
  const categoriesSet = new Set<string>();
  props.comparison.budgets.forEach((budget) => {
    budget.stats?.byCategory.forEach((cat) => {
      categoriesSet.add(cat.name);
    });
  });
  const categories = Array.from(categoriesSet);

  // Prepare datasets for each month
  const datasets = props.comparison.budgets.map((budget, index) => {
    const monthLabel = new Date(budget.month).toLocaleDateString("fr-FR", {
      month: "long",
      year: "numeric",
    });

    // Colors for each month
    const colors = [
      "rgba(59, 130, 246, 0.7)", // Blue
      "rgba(16, 185, 129, 0.7)", // Green
      "rgba(245, 158, 11, 0.7)", // Amber
    ];

    const borderColors = [
      "rgba(59, 130, 246, 1)",
      "rgba(16, 185, 129, 1)",
      "rgba(245, 158, 11, 1)",
    ];

    // Get actual amounts for each category
    const data = categories.map((categoryName) => {
      const category = budget.stats?.byCategory.find((c) => c.name === categoryName);
      return category ? category.actualCents / 100 : 0;
    });

    return {
      label: monthLabel,
      data,
      backgroundColor: colors[index % colors.length],
      borderColor: borderColors[index % borderColors.length],
      borderWidth: 1,
    };
  });

  return {
    labels: categories,
    datasets,
  };
});

function renderChart() {
  if (!chartCanvas.value) return;

  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy();
  }

  // Create new chart
  chartInstance = new Chart(chartCanvas.value, {
    type: "bar",
    data: chartData.value,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: "Dépenses par catégorie",
          font: {
            size: 16,
            weight: "bold",
          },
        },
        legend: {
          position: "top",
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              let label = context.dataset.label || "";
              if (label) {
                label += ": ";
              }
              if (context.parsed.y !== null) {
                label += new Intl.NumberFormat("fr-FR", {
                  style: "currency",
                  currency: "EUR",
                }).format(context.parsed.y);
              }
              return label;
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
                minimumFractionDigits: 0,
              }).format(value as number);
            },
          },
        },
      },
    },
  });
}

onMounted(() => {
  renderChart();
});

watch(
  () => props.comparison,
  () => {
    renderChart();
  },
  { deep: true }
);
</script>

<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold mb-4">Graphique Comparatif</h2>

    <div class="relative" style="height: 400px">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>
