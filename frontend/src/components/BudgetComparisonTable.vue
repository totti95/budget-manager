<script setup lang="ts">
import { computed } from "vue";
import type { BudgetComparison, CategoryEvolution } from "@/types";

interface Props {
  comparison: BudgetComparison;
}

const props = defineProps<Props>();

function centsToEuros(cents: number): string {
  return (cents / 100).toFixed(2);
}

// Prepare data for the table
const tableData = computed(() => {
  const categoriesMap = new Map<string, any>();

  // Collect all categories from all budgets
  props.comparison.budgets.forEach((budget, budgetIndex) => {
    if (budget.stats?.byCategory) {
      budget.stats.byCategory.forEach((category) => {
        if (!categoriesMap.has(category.name)) {
          categoriesMap.set(category.name, {
            name: category.name,
            budgets: [],
          });
        }

        const categoryData = categoriesMap.get(category.name)!;
        categoryData.budgets[budgetIndex] = {
          plannedCents: category.plannedCents,
          actualCents: category.actualCents,
          varianceCents: category.varianceCents,
          variancePercent: category.variancePercent,
        };
      });
    }
  });

  // Convert map to array and fill missing budget data
  const categories = Array.from(categoriesMap.values()).map((category) => {
    // Fill missing budgets with zeros
    for (let i = 0; i < props.comparison.budgets.length; i++) {
      if (!category.budgets[i]) {
        category.budgets[i] = {
          plannedCents: 0,
          actualCents: 0,
          varianceCents: 0,
          variancePercent: 0,
        };
      }
    }
    return category;
  });

  // Calculate totals
  const totals = {
    name: "TOTAL",
    budgets: props.comparison.budgets.map((budget) => ({
      plannedCents: budget.stats?.totalPlannedCents || 0,
      actualCents: budget.stats?.totalActualCents || 0,
      varianceCents: budget.stats?.varianceCents || 0,
      variancePercent: budget.stats?.variancePercent || 0,
    })),
  };

  return { categories, totals };
});

// Get evolution for a category
function getEvolution(categoryName: string): CategoryEvolution | undefined {
  return props.comparison.comparison.evolution.find(
    (e) => e.categoryName === categoryName
  );
}

// Get month label
function getMonthLabel(month: string): string {
  const date = new Date(month);
  return date.toLocaleDateString("fr-FR", {
    month: "short",
    year: "numeric",
  });
}

// Get evolution icon
function getEvolutionIcon(evolutionPercent: number): string {
  if (evolutionPercent > 5) return "↗️";
  if (evolutionPercent < -5) return "↘️";
  return "→";
}

// Get evolution color
function getEvolutionColor(evolutionPercent: number): string {
  if (evolutionPercent > 10) return "text-red-600";
  if (evolutionPercent < -10) return "text-green-600";
  return "text-gray-600";
}

// Calculate evolution between two periods
function calculateEvolution(fromValue: number, toValue: number): number {
  if (fromValue === 0) return 0;
  return Math.round(((toValue - fromValue) / fromValue) * 100);
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6">
      <h2 class="text-xl font-semibold mb-4">Tableau Comparatif</h2>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50"
              >
                Catégorie
              </th>

              <th
                v-for="(budget, index) in comparison.budgets"
                :key="budget.id"
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                <div class="capitalize">
                  {{ getMonthLabel(budget.month) }}
                </div>
              </th>

              <!-- Evolution columns -->
              <th
                v-for="index in comparison.budgets.length - 1"
                :key="`evolution-${index}`"
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Évol. {{ index }} → {{ index + 1 }}
              </th>

              <th
                v-if="comparison.budgets.length >= 3"
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Évol. globale
              </th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-200">
            <!-- Categories -->
            <tr
              v-for="category in tableData.categories"
              :key="category.name"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white">
                {{ category.name }}
              </td>

              <td
                v-for="(budgetData, index) in category.budgets"
                :key="index"
                class="px-6 py-4 whitespace-nowrap text-sm"
              >
                <div class="space-y-1">
                  <div class="text-gray-600">
                    Prévu: {{ centsToEuros(budgetData.plannedCents) }} €
                  </div>
                  <div
                    :class="[
                      'font-semibold',
                      budgetData.actualCents > budgetData.plannedCents
                        ? 'text-red-600'
                        : 'text-green-600'
                    ]"
                  >
                    Réel: {{ centsToEuros(budgetData.actualCents) }} €
                  </div>
                  <div
                    :class="[
                      'text-xs',
                      budgetData.varianceCents > 0
                        ? 'text-red-500'
                        : 'text-green-500'
                    ]"
                  >
                    {{ budgetData.varianceCents > 0 ? '+' : '' }}{{ centsToEuros(budgetData.varianceCents) }} €
                    ({{ budgetData.variancePercent > 0 ? '+' : '' }}{{ budgetData.variancePercent }}%)
                  </div>
                </div>
              </td>

              <!-- Evolution cells (consecutive periods) -->
              <td
                v-for="evolutionIndex in comparison.budgets.length - 1"
                :key="`cat-evol-${category.name}-${evolutionIndex}`"
                class="px-6 py-4 whitespace-nowrap text-sm"
              >
                <div
                  :class="[
                    'flex items-center gap-2 font-semibold',
                    getEvolutionColor(
                      calculateEvolution(
                        category.budgets[evolutionIndex - 1].actualCents,
                        category.budgets[evolutionIndex].actualCents
                      )
                    )
                  ]"
                >
                  <span class="text-xl">
                    {{
                      getEvolutionIcon(
                        calculateEvolution(
                          category.budgets[evolutionIndex - 1].actualCents,
                          category.budgets[evolutionIndex].actualCents
                        )
                      )
                    }}
                  </span>
                  <span>
                    {{
                      (() => {
                        const evol = calculateEvolution(
                          category.budgets[evolutionIndex - 1].actualCents,
                          category.budgets[evolutionIndex].actualCents
                        );
                        return evol > 0 ? `+${evol}` : evol;
                      })()
                    }}%
                  </span>
                </div>
              </td>

              <!-- Global evolution (for 3+ budgets) -->
              <td
                v-if="comparison.budgets.length >= 3"
                class="px-6 py-4 whitespace-nowrap text-sm"
              >
                <div
                  :class="[
                    'flex items-center gap-2 font-semibold',
                    getEvolutionColor(
                      calculateEvolution(
                        category.budgets[0].actualCents,
                        category.budgets[category.budgets.length - 1].actualCents
                      )
                    )
                  ]"
                >
                  <span class="text-xl">
                    {{
                      getEvolutionIcon(
                        calculateEvolution(
                          category.budgets[0].actualCents,
                          category.budgets[category.budgets.length - 1].actualCents
                        )
                      )
                    }}
                  </span>
                  <span>
                    {{
                      (() => {
                        const evol = calculateEvolution(
                          category.budgets[0].actualCents,
                          category.budgets[category.budgets.length - 1].actualCents
                        );
                        return evol > 0 ? `+${evol}` : evol;
                      })()
                    }}%
                  </span>
                </div>
              </td>
            </tr>

            <!-- Total row -->
            <tr class="bg-gray-100 font-bold">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-gray-100">
                {{ tableData.totals.name }}
              </td>

              <td
                v-for="(budgetData, index) in tableData.totals.budgets"
                :key="index"
                class="px-6 py-4 whitespace-nowrap text-sm"
              >
                <div class="space-y-1">
                  <div class="text-gray-700">
                    Prévu: {{ centsToEuros(budgetData.plannedCents) }} €
                  </div>
                  <div
                    :class="[
                      'font-bold text-base',
                      budgetData.actualCents > budgetData.plannedCents
                        ? 'text-red-600'
                        : 'text-green-600'
                    ]"
                  >
                    Réel: {{ centsToEuros(budgetData.actualCents) }} €
                  </div>
                  <div
                    :class="[
                      'text-xs',
                      budgetData.varianceCents > 0
                        ? 'text-red-500'
                        : 'text-green-500'
                    ]"
                  >
                    {{ budgetData.varianceCents > 0 ? '+' : '' }}{{ centsToEuros(budgetData.varianceCents) }} €
                    ({{ budgetData.variancePercent > 0 ? '+' : '' }}{{ budgetData.variancePercent }}%)
                  </div>
                </div>
              </td>

              <!-- Evolution cells for totals (consecutive periods) -->
              <td
                v-for="evolutionIndex in comparison.budgets.length - 1"
                :key="`total-evol-${evolutionIndex}`"
                class="px-6 py-4 whitespace-nowrap text-sm"
              >
                <div
                  :class="[
                    'flex items-center gap-2',
                    getEvolutionColor(
                      calculateEvolution(
                        tableData.totals.budgets[evolutionIndex - 1].actualCents,
                        tableData.totals.budgets[evolutionIndex].actualCents
                      )
                    )
                  ]"
                >
                  <span class="text-xl">
                    {{
                      getEvolutionIcon(
                        calculateEvolution(
                          tableData.totals.budgets[evolutionIndex - 1].actualCents,
                          tableData.totals.budgets[evolutionIndex].actualCents
                        )
                      )
                    }}
                  </span>
                  <span>
                    {{
                      (() => {
                        const evol = calculateEvolution(
                          tableData.totals.budgets[evolutionIndex - 1].actualCents,
                          tableData.totals.budgets[evolutionIndex].actualCents
                        );
                        return evol > 0 ? `+${evol}` : evol;
                      })()
                    }}%
                  </span>
                </div>
              </td>

              <!-- Global evolution for totals (for 3+ budgets) -->
              <td
                v-if="comparison.budgets.length >= 3"
                class="px-6 py-4 whitespace-nowrap text-sm"
              >
                <div
                  :class="[
                    'flex items-center gap-2',
                    getEvolutionColor(
                      calculateEvolution(
                        tableData.totals.budgets[0].actualCents,
                        tableData.totals.budgets[tableData.totals.budgets.length - 1].actualCents
                      )
                    )
                  ]"
                >
                  <span class="text-xl">
                    {{
                      getEvolutionIcon(
                        calculateEvolution(
                          tableData.totals.budgets[0].actualCents,
                          tableData.totals.budgets[tableData.totals.budgets.length - 1].actualCents
                        )
                      )
                    }}
                  </span>
                  <span>
                    {{
                      (() => {
                        const evol = calculateEvolution(
                          tableData.totals.budgets[0].actualCents,
                          tableData.totals.budgets[tableData.totals.budgets.length - 1].actualCents
                        );
                        return evol > 0 ? `+${evol}` : evol;
                      })()
                    }}%
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.sticky {
  position: sticky;
}
</style>