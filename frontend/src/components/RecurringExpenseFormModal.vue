<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="close"
  >
    <div
      class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 p-6 max-h-[90vh] overflow-y-auto"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
          {{ isEditing ? "Modifier" : "Ajouter" }} une dépense récurrente
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          ✕
        </button>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-4">
        <!-- Label -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Libellé *
          </label>
          <input
            v-model="formData.label"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            :class="{ 'border-red-500': errors.label }"
            placeholder="Ex: Loyer, Abonnement Netflix..."
          />
          <p v-if="errors.label" class="text-sm text-red-600 mt-1">
            {{ errors.label }}
          </p>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Montant (€) *
          </label>
          <input
            v-model.number="formData.amount"
            type="number"
            step="0.01"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            :class="{ 'border-red-500': errors.amount }"
            placeholder="0.00"
          />
          <p v-if="errors.amount" class="text-sm text-red-600 mt-1">
            {{ errors.amount }}
          </p>
        </div>

        <!-- Frequency -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Fréquence *
          </label>
          <select
            v-model="formData.frequency"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            :class="{ 'border-red-500': errors.frequency }"
          >
            <option value="">Choisir...</option>
            <option value="monthly">Mensuelle</option>
            <option value="weekly">Hebdomadaire</option>
            <option value="yearly">Annuelle</option>
          </select>
          <p v-if="errors.frequency" class="text-sm text-red-600 mt-1">
            {{ errors.frequency }}
          </p>
        </div>

        <!-- Monthly: Day of Month -->
        <div v-if="formData.frequency === 'monthly'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Jour du mois *
          </label>
          <input
            v-model.number="formData.dayOfMonth"
            type="number"
            min="1"
            max="31"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            :class="{ 'border-red-500': errors.dayOfMonth }"
            placeholder="1-31"
          />
          <p v-if="errors.dayOfMonth" class="text-sm text-red-600 mt-1">
            {{ errors.dayOfMonth }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Ex: 1 pour le 1er du mois, 15 pour le 15
          </p>
        </div>

        <!-- Weekly: Day of Week -->
        <div v-if="formData.frequency === 'weekly'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Jour de la semaine *
          </label>
          <select
            v-model="formData.dayOfWeek"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            :class="{ 'border-red-500': errors.dayOfWeek }"
          >
            <option value="">Choisir...</option>
            <option value="monday">Lundi</option>
            <option value="tuesday">Mardi</option>
            <option value="wednesday">Mercredi</option>
            <option value="thursday">Jeudi</option>
            <option value="friday">Vendredi</option>
            <option value="saturday">Samedi</option>
            <option value="sunday">Dimanche</option>
          </select>
          <p v-if="errors.dayOfWeek" class="text-sm text-red-600 mt-1">
            {{ errors.dayOfWeek }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Une seule dépense sera créée par mois (première occurrence)
          </p>
        </div>

        <!-- Yearly: Month and Day -->
        <div v-if="formData.frequency === 'yearly'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Mois *
          </label>
          <select
            v-model.number="formData.monthOfYear"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            :class="{ 'border-red-500': errors.monthOfYear }"
          >
            <option value="">Choisir...</option>
            <option :value="1">Janvier</option>
            <option :value="2">Février</option>
            <option :value="3">Mars</option>
            <option :value="4">Avril</option>
            <option :value="5">Mai</option>
            <option :value="6">Juin</option>
            <option :value="7">Juillet</option>
            <option :value="8">Août</option>
            <option :value="9">Septembre</option>
            <option :value="10">Octobre</option>
            <option :value="11">Novembre</option>
            <option :value="12">Décembre</option>
          </select>
          <p v-if="errors.monthOfYear" class="text-sm text-red-600 mt-1">
            {{ errors.monthOfYear }}
          </p>

          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 mt-3">
            Jour du mois (optionnel)
          </label>
          <input
            v-model.number="formData.dayOfMonth"
            type="number"
            min="1"
            max="31"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            placeholder="1-31 (défaut: 1er)"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Laissez vide pour le 1er du mois
          </p>
        </div>

        <!-- Template Subcategory -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Sous-catégorie de template (optionnel)
          </label>
          <select
            v-model.number="formData.templateSubcategoryId"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          >
            <option :value="null">Aucune (à assigner manuellement)</option>
            <optgroup
              v-for="category in templateCategories"
              :key="category.id"
              :label="category.name"
            >
              <option v-for="subcat in category.subcategories" :key="subcat.id" :value="subcat.id">
                {{ subcat.name }}
              </option>
            </optgroup>
          </select>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Liée à une sous-catégorie pour placement automatique dans les budgets
          </p>
        </div>

        <!-- Start Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Date de début *
          </label>
          <input
            v-model="formData.startDate"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            :class="{ 'border-red-500': errors.startDate }"
          />
          <p v-if="errors.startDate" class="text-sm text-red-600 mt-1">
            {{ errors.startDate }}
          </p>
        </div>

        <!-- End Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Date de fin (optionnel)
          </label>
          <input
            v-model="formData.endDate"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Laissez vide pour une dépense récurrente indéfinie
          </p>
        </div>

        <!-- Payment Method -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Moyen de paiement (optionnel)
          </label>
          <input
            v-model="formData.paymentMethod"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            placeholder="Ex: Carte bancaire, Prélèvement..."
          />
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Notes (optionnel)
          </label>
          <textarea
            v-model="formData.notes"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            placeholder="Notes supplémentaires..."
          />
        </div>

        <!-- Auto Create -->
        <div>
          <label class="flex items-center gap-2">
            <input
              v-model="formData.autoCreate"
              type="checkbox"
              class="w-4 h-4 text-blue-600 rounded"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Création automatique lors de la génération de budgets
            </span>
          </label>
        </div>

        <!-- Is Active -->
        <div>
          <label class="flex items-center gap-2">
            <input
              v-model="formData.isActive"
              type="checkbox"
              class="w-4 h-4 text-blue-600 rounded"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">Actif</span>
          </label>
        </div>

        <!-- Buttons -->
        <div class="flex gap-2 pt-2">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ isSubmitting ? "Enregistrement..." : isEditing ? "Modifier" : "Créer" }}
          </button>
          <button
            type="button"
            @click="close"
            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
          >
            Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import type { RecurringExpense, TemplateCategory } from "@/types";
import { useTemplateStore } from "@/stores/template";

const props = defineProps<{
  isOpen: boolean;
  recurringExpense?: RecurringExpense;
}>();

const emit = defineEmits<{
  close: [];
  submit: [data: any];
}>();

const templateStore = useTemplateStore();
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const formData = ref({
  label: "",
  amount: 0,
  frequency: "",
  dayOfMonth: null as number | null,
  dayOfWeek: null as string | null,
  monthOfYear: null as number | null,
  templateSubcategoryId: null as number | null,
  startDate: new Date().toISOString().split("T")[0],
  endDate: null as string | null,
  paymentMethod: null as string | null,
  notes: null as string | null,
  autoCreate: true,
  isActive: true,
});

const templateCategories = ref<TemplateCategory[]>([]);

const isEditing = computed(() => !!props.recurringExpense);

watch(
  () => props.isOpen,
  async (isOpen) => {
    if (isOpen) {
      await loadTemplateCategories();
      resetForm();
    }
  }
);

async function loadTemplateCategories() {
  if (templateStore.templates.length === 0) {
    await templateStore.fetchTemplates();
  }
  const defaultTemplate = templateStore.templates.find((t) => t.isDefault);
  if (defaultTemplate?.categories) {
    templateCategories.value = defaultTemplate.categories;
  }
}

function resetForm() {
  if (props.recurringExpense) {
    formData.value = {
      label: props.recurringExpense.label,
      amount: props.recurringExpense.amountCents / 100,
      frequency: props.recurringExpense.frequency,
      dayOfMonth: props.recurringExpense.dayOfMonth,
      dayOfWeek: props.recurringExpense.dayOfWeek,
      monthOfYear: props.recurringExpense.monthOfYear,
      templateSubcategoryId: props.recurringExpense.templateSubcategoryId,
      startDate: props.recurringExpense.startDate.split("T")[0],
      endDate: props.recurringExpense.endDate?.split("T")[0] ?? null,
      paymentMethod: props.recurringExpense.paymentMethod,
      notes: props.recurringExpense.notes,
      autoCreate: props.recurringExpense.autoCreate,
      isActive: props.recurringExpense.isActive,
    };
  } else {
    formData.value = {
      label: "",
      amount: 0,
      frequency: "",
      dayOfMonth: null,
      dayOfWeek: null,
      monthOfYear: null,
      templateSubcategoryId: null,
      startDate: new Date().toISOString().split("T")[0],
      endDate: null,
      paymentMethod: null,
      notes: null,
      autoCreate: true,
      isActive: true,
    };
  }
  errors.value = {};
}

// Watch frequency changes to clear conditional fields
watch(
  () => formData.value.frequency,
  (newFreq) => {
    if (newFreq !== "monthly" && newFreq !== "yearly") {
      formData.value.dayOfMonth = null;
    }
    if (newFreq !== "weekly") {
      formData.value.dayOfWeek = null;
    }
    if (newFreq !== "yearly") {
      formData.value.monthOfYear = null;
    }
  }
);

function validate(): boolean {
  errors.value = {};

  if (!formData.value.label) {
    errors.value.label = "Le libellé est requis";
  }

  if (!formData.value.amount || formData.value.amount <= 0) {
    errors.value.amount = "Le montant doit être supérieur à 0";
  }

  if (!formData.value.frequency) {
    errors.value.frequency = "La fréquence est requise";
  }

  if (formData.value.frequency === "monthly" && !formData.value.dayOfMonth) {
    errors.value.dayOfMonth = "Le jour du mois est requis pour la fréquence mensuelle";
  }

  if (formData.value.frequency === "weekly" && !formData.value.dayOfWeek) {
    errors.value.dayOfWeek = "Le jour de la semaine est requis pour la fréquence hebdomadaire";
  }

  if (formData.value.frequency === "yearly" && !formData.value.monthOfYear) {
    errors.value.monthOfYear = "Le mois est requis pour la fréquence annuelle";
  }

  if (!formData.value.startDate) {
    errors.value.startDate = "La date de début est requise";
  }

  return Object.keys(errors.value).length === 0;
}

async function onSubmit() {
  if (!validate()) {
    return;
  }

  isSubmitting.value = true;

  try {
    // Utiliser camelCase - le middleware backend convertira en snake_case
    const data = {
      label: formData.value.label,
      amountCents: Math.round(formData.value.amount * 100),
      frequency: formData.value.frequency,
      dayOfMonth: formData.value.dayOfMonth,
      dayOfWeek: formData.value.dayOfWeek,
      monthOfYear: formData.value.monthOfYear,
      templateSubcategoryId: formData.value.templateSubcategoryId,
      startDate: formData.value.startDate,
      endDate: formData.value.endDate,
      paymentMethod: formData.value.paymentMethod,
      notes: formData.value.notes,
      autoCreate: formData.value.autoCreate,
      isActive: formData.value.isActive,
    };

    emit("submit", data);
  } finally {
    isSubmitting.value = false;
  }
}

function close() {
  emit("close");
  resetForm();
}
</script>
