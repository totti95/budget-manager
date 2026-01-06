import type { Component } from "vue";
import type { WidgetType } from "@/types";

import CurrentMonthSummaryWidget from "./CurrentMonthSummaryWidget.vue";
import TopCategoriesWidget from "./TopCategoriesWidget.vue";
import AssetEvolutionWidget from "./AssetEvolutionWidget.vue";
import SavingsRateWidget from "./SavingsRateWidget.vue";
import ExpenseDistributionChart from "@/components/ExpenseDistributionChart.vue";

export interface WidgetDefinition {
  id: WidgetType;
  label: string;
  component: Component;
  defaultSize: { w: number; h: number };
  minSize?: { w: number; h: number };
  description?: string;
}

export const widgetRegistry: Record<WidgetType, WidgetDefinition> = {
  "current-month-summary": {
    id: "current-month-summary",
    label: "Résumé du mois",
    component: CurrentMonthSummaryWidget,
    defaultSize: { w: 12, h: 3 },
    minSize: { w: 6, h: 2 },
    description: "Aperçu du budget du mois en cours",
  },
  "top-5-categories": {
    id: "top-5-categories",
    label: "Top 5 Catégories",
    component: TopCategoriesWidget,
    defaultSize: { w: 6, h: 5 },
    minSize: { w: 4, h: 4 },
    description: "Catégories avec le plus de dépenses",
  },
  "asset-evolution": {
    id: "asset-evolution",
    label: "Évolution du Patrimoine",
    component: AssetEvolutionWidget,
    defaultSize: { w: 6, h: 6 },
    minSize: { w: 4, h: 4 },
    description: "Graphique d'évolution des actifs et passifs",
  },
  "savings-rate": {
    id: "savings-rate",
    label: "Taux d'Épargne",
    component: SavingsRateWidget,
    defaultSize: { w: 6, h: 6 },
    minSize: { w: 4, h: 4 },
    description: "Taux d'épargne mensuel et évolution",
  },
  "expense-distribution": {
    id: "expense-distribution",
    label: "Répartition des Dépenses",
    component: ExpenseDistributionChart,
    defaultSize: { w: 6, h: 6 },
    minSize: { w: 4, h: 4 },
    description: "Graphique circulaire par catégorie",
  },
};

export function getWidgetDefinition(type: WidgetType): WidgetDefinition {
  return widgetRegistry[type];
}

export function getAllWidgetTypes(): WidgetType[] {
  return Object.keys(widgetRegistry) as WidgetType[];
}
