<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold mb-2">Tableau de bord</h1>
        <select v-model="selectedMonth" @change="loadBudget" class="input max-w-xs">
          <option v-for="month in availableMonths" :key="month" :value="month">
            {{ formatMonth(month) }}
          </option>
        </select>
      </div>

      <div class="flex gap-2 flex-wrap">
        <router-link
          v-if="selectedMonth && currentBudget"
          :to="`/budgets/${selectedMonth}`"
          class="btn btn-primary"
        >
          Voir les détails
        </router-link>
        <button
          v-if="dashboardStore.hasUnsavedChanges"
          @click="saveDashboardLayout"
          class="btn btn-primary"
          :disabled="dashboardStore.loading"
        >
          Enregistrer
        </button>
        <button @click="toggleEditMode" class="btn btn-secondary">
          {{ editMode ? "Terminer" : "Personnaliser" }}
        </button>
        <button v-if="editMode" @click="openWidgetSelector" class="btn btn-primary">Ajouter</button>
        <button v-if="editMode" @click="resetDashboard" class="btn btn-danger">
          Réinitialiser
        </button>
      </div>
    </div>

    <!-- Grid Layout -->
    <grid-layout
      v-if="currentBudget && dashboardStore.layout.length > 0"
      v-model:layout="dashboardStore.layout"
      :col-num="12"
      :row-height="30"
      :margin="[16, 16]"
      :is-draggable="editMode"
      :is-resizable="editMode"
      :vertical-compact="true"
      :use-css-transforms="true"
      @layout-updated="onLayoutUpdated"
    >
      <grid-item
        v-for="item in dashboardStore.layout"
        :key="item.i"
        v-memo="[item.x, item.y, item.w, item.h, editMode, getWidgetProps(item.i)]"
        :x="item.x"
        :y="item.y"
        :w="item.w"
        :h="item.h"
        :i="item.i"
        :min-w="getWidgetMinSize(item.i).w"
        :min-h="getWidgetMinSize(item.i).h"
        class="relative"
      >
        <div class="h-full p-2">
          <component :is="getWidgetComponent(item.i)" v-bind="getWidgetProps(item.i)" />
        </div>

        <button
          v-if="editMode"
          @click="removeWidget(item.i)"
          class="absolute top-4 right-4 bg-red-600 text-white rounded-full p-1 hover:bg-red-700 z-10"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </grid-item>
    </grid-layout>

    <!-- No budget state -->
    <div v-else-if="!budgetStore.loading && !currentBudget" class="card text-center py-12">
      <p class="text-gray-600 dark:text-gray-400 mb-4">Aucun budget pour ce mois</p>
      <button @click="handleGenerateBudget" class="btn btn-primary">Générer le budget</button>
    </div>

    <!-- Loading state -->
    <div v-else-if="budgetStore.loading" class="text-center py-12">
      <p>Chargement...</p>
    </div>

    <!-- Widget Selector Modal -->
    <WidgetSelectorModal
      v-if="showWidgetSelector"
      :existing-widgets="existingWidgetIds"
      @add-widget="addWidget"
      @close="showWidgetSelector = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useBudgetStore } from "@/stores/budget";
import { useStatsStore } from "@/stores/stats";
import { useDashboardStore } from "@/stores/dashboard";
import { useToast } from "@/composables/useToast";
import { getWidgetDefinition } from "@/components/widgets/widgetRegistry";
import type { WidgetType, WidgetLayoutItem } from "@/types";
import WidgetSelectorModal from "@/components/WidgetSelectorModal.vue";

const budgetStore = useBudgetStore();
const statsStore = useStatsStore();
const dashboardStore = useDashboardStore();
const toast = useToast();

const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const currentBudget = computed(() => budgetStore.currentBudget);
const editMode = ref(false);
const showWidgetSelector = ref(false);

const availableMonths = computed(() => {
  const months = [];
  const now = new Date();
  for (let i = -2; i <= 2; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() + i, 1);
    months.push(d.toISOString().slice(0, 7));
  }
  return months;
});

const existingWidgetIds = computed(() => dashboardStore.layout.map((item) => item.i));

function formatMonth(month: string): string {
  const [year, monthNum] = month.split("-");
  const date = new Date(parseInt(year), parseInt(monthNum) - 1, 1);
  return date.toLocaleDateString("fr-FR", { year: "numeric", month: "long" });
}

async function loadBudget() {
  try {
    const response = await budgetStore.fetchBudgets(selectedMonth.value);
    if (response.data.length > 0) {
      // Définir directement le budget depuis la réponse (pas besoin de refaire une requête)
      budgetStore.currentBudget = response.data[0];
      await statsStore.fetchSummary(response.data[0].id);
      await statsStore.fetchCategoryStats(response.data[0].id);
    } else {
      budgetStore.currentBudget = null;
    }
  } catch (error) {
    console.error("Error loading budget:", error);
  }
}

async function handleGenerateBudget() {
  try {
    const budget = await budgetStore.generateBudget(selectedMonth.value);
    await statsStore.fetchSummary(budget.id);
    await statsStore.fetchCategoryStats(budget.id);
  } catch (error) {
    console.error("Error generating budget:", error);
  }
}

async function saveDashboardLayout() {
  try {
    await dashboardStore.saveLayout();
    toast.success("Configuration du tableau de bord sauvegardée");
  } catch (err) {
    toast.error("Erreur lors de la sauvegarde de la configuration");
    console.error(err);
  }
}

function toggleEditMode() {
  editMode.value = !editMode.value;
}

function openWidgetSelector() {
  showWidgetSelector.value = true;
}

function addWidget(widgetType: WidgetType) {
  const definition = getWidgetDefinition(widgetType);

  // Calculer la position Y en trouvant le point le plus bas du layout actuel
  const maxY = dashboardStore.layout.reduce((max, item) => {
    return Math.max(max, item.y + item.h);
  }, 0);

  const newItem: WidgetLayoutItem = {
    i: widgetType,
    x: 0,
    y: maxY,
    w: definition.defaultSize.w,
    h: definition.defaultSize.h,
    minW: definition.minSize?.w,
    minH: definition.minSize?.h,
  };
  dashboardStore.layout = [...dashboardStore.layout, newItem];
  dashboardStore.hasUnsavedChanges = true;
  showWidgetSelector.value = false;
}

function removeWidget(widgetId: WidgetType) {
  dashboardStore.layout = dashboardStore.layout.filter((item) => item.i !== widgetId);
  dashboardStore.hasUnsavedChanges = true;
}

function onLayoutUpdated(newLayout: WidgetLayoutItem[]) {
  dashboardStore.updateLayout(newLayout);
}

function getWidgetComponent(widgetType: WidgetType) {
  return getWidgetDefinition(widgetType).component;
}

function getWidgetMinSize(widgetType: WidgetType) {
  const definition = getWidgetDefinition(widgetType);
  return definition.minSize || { w: 2, h: 2 };
}

function getWidgetProps(widgetType: WidgetType) {
  const baseProps: any = {};

  if (currentBudget.value) {
    baseProps.budgetId = currentBudget.value.id;
  }

  const settings = dashboardStore.widgetSettings[widgetType] || {};

  return { ...baseProps, ...settings };
}

async function resetDashboard() {
  if (confirm("Réinitialiser la disposition du tableau de bord ?")) {
    try {
      await dashboardStore.resetLayout();
      editMode.value = false;
    } catch (err) {
      console.error(err);
    }
  }
}

onMounted(async () => {
  await dashboardStore.fetchLayout();
  await loadBudget();
});
</script>
