import { defineAsyncComponent, type Component } from "vue";
import type { WidgetType } from "@/types";

export interface WidgetDefinition {
  id: WidgetType;
  label: string;
  component: Component;
  defaultSize: { w: number; h: number };
  minSize?: { w: number; h: number };
  description?: string;
}

// Type pour la configuration des widgets (sans le champ 'id' redondant)
type WidgetConfig = Omit<WidgetDefinition, "id">;

// Configuration des widgets - l'ID est derive de la cle
const widgetConfigs: Record<WidgetType, WidgetConfig> = {
  "current-month-summary": {
    label: "Résumé du mois",
    component: defineAsyncComponent(() => import("./CurrentMonthSummaryWidget.vue")),
    defaultSize: { w: 12, h: 3 },
    minSize: { w: 6, h: 2 },
    description: "Aperçu du budget du mois en cours",
  },
  "top-5-categories": {
    label: "Top 5 Catégories",
    component: defineAsyncComponent(() => import("./TopCategoriesWidget.vue")),
    defaultSize: { w: 6, h: 5 },
    minSize: { w: 4, h: 4 },
    description: "Catégories avec le plus de dépenses",
  },
  "asset-evolution": {
    label: "Évolution du Patrimoine",
    component: defineAsyncComponent(() => import("./AssetEvolutionWidget.vue")),
    defaultSize: { w: 6, h: 6 },
    minSize: { w: 4, h: 4 },
    description: "Graphique d'évolution des actifs et passifs",
  },
  "savings-rate": {
    label: "Taux d'Épargne",
    component: defineAsyncComponent(() => import("./SavingsRateWidget.vue")),
    defaultSize: { w: 6, h: 6 },
    minSize: { w: 4, h: 4 },
    description: "Taux d'épargne mensuel et évolution",
  },
  "expense-distribution": {
    label: "Répartition des Dépenses",
    component: defineAsyncComponent(() => import("@/components/ExpenseDistributionChart.vue")),
    defaultSize: { w: 6, h: 6 },
    minSize: { w: 4, h: 4 },
    description: "Graphique circulaire par catégorie",
  },
};

// Construction du registre avec les IDs derives des cles
export const widgetRegistry: Record<WidgetType, WidgetDefinition> = Object.fromEntries(
  Object.entries(widgetConfigs).map(([id, config]) => [id, { id: id as WidgetType, ...config }])
) as Record<WidgetType, WidgetDefinition>;

export function getWidgetDefinition(type: WidgetType): WidgetDefinition {
  return widgetRegistry[type];
}

export function getAllWidgetTypes(): WidgetType[] {
  return Object.keys(widgetRegistry) as WidgetType[];
}
