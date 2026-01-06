<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Templates de budget</h1>
      <button @click="openCreateForm" class="btn btn-primary">
        + Nouveau template
      </button>
    </div>

    <!-- Liste des templates -->
    <div
      v-if="templateStore.loading && !showCreateForm"
      class="text-center py-12"
    >
      <p>Chargement...</p>
    </div>

    <div
      v-else-if="templateStore.templates.length === 0 && !showCreateForm"
      class="card text-center py-12"
    >
      <p class="text-gray-600 dark:text-gray-400 mb-4">
        Vous n'avez pas encore de template de budget.
      </p>
      <button @click="openCreateForm" class="btn btn-primary">
        Créer mon premier template
      </button>
    </div>

    <div v-else class="space-y-4">
      <!-- Formulaire de création/édition -->
      <div
        v-if="showCreateForm || editingTemplate"
        class="card bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800"
      >
        <h2 class="text-xl font-bold mb-4">
          {{ editingTemplate ? "Modifier le template" : "Nouveau template" }}
        </h2>

        <form @submit.prevent="handleSubmit">
          <!-- Nom du template -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2"
              >Nom du template</label
            >
            <input
              v-model="formData.name"
              type="text"
              required
              class="input w-full"
              placeholder="Ex: Budget Mensuel Standard"
            />
          </div>

          <!-- Définir par défaut -->
          <div class="mb-6">
            <label class="flex items-center">
              <input
                v-model="formData.isDefault"
                type="checkbox"
                class="mr-2"
              />
              <span class="text-sm">Définir comme template par défaut</span>
            </label>
          </div>

          <!-- Revenu mensuel par défaut -->
          <div class="mb-6">
            <label class="block text-sm font-medium mb-2">
              Revenu mensuel par défaut (optionnel)
            </label>
            <div class="flex items-center gap-2">
              <input
                v-model.number="revenueEuros"
                type="number"
                step="0.01"
                min="0"
                class="input w-full"
                placeholder="Ex: 3000"
              />
              <span class="text-sm text-gray-600 dark:text-gray-400">€</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              Si défini, ce revenu sera utilisé lors de la génération de nouveaux
              budgets
            </p>
          </div>

          <!-- Catégories -->
          <div class="mb-6">
            <div class="flex justify-between items-center mb-3">
              <h3 class="font-semibold">Catégories</h3>
              <button
                type="button"
                @click="addCategory"
                class="text-sm text-blue-600 hover:text-blue-700"
              >
                + Ajouter une catégorie
              </button>
            </div>

            <p
              v-if="formData.categories.length === 0"
              class="text-sm text-gray-500 italic py-4 text-center"
            >
              Cliquez sur "+ Ajouter une catégorie" pour commencer
            </p>

            <div
              v-for="(category, catIndex) in formData.categories"
              :key="catIndex"
              class="mb-4 p-4 bg-white dark:bg-gray-800 rounded border"
            >
              <div class="flex gap-3 mb-3">
                <input
                  v-model="category.name"
                  type="text"
                  required
                  class="input flex-1"
                  placeholder="Nom de la catégorie"
                />
                <div class="flex items-center gap-1">
                  <input
                    :value="getCategoryTotal(category) / 100"
                    type="number"
                    disabled
                    class="input w-28 bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
                    placeholder="Total"
                  />
                  <span class="text-sm text-gray-600">€</span>
                </div>
                <button
                  type="button"
                  @click="removeCategory(catIndex)"
                  class="text-red-600 hover:text-red-700 px-2"
                  title="Supprimer"
                >
                  ✕
                </button>
              </div>

              <!-- Sous-catégories -->
              <div class="ml-4">
                <button
                  type="button"
                  @click="addSubcategory(catIndex)"
                  class="text-xs text-blue-600 hover:text-blue-700 mb-2"
                >
                  + Ajouter une sous-catégorie
                </button>

                <div
                  v-for="(subcategory, subIndex) in category.subcategories"
                  :key="subIndex"
                  class="flex gap-2 mb-2"
                >
                  <input
                    v-model="subcategory.name"
                    type="text"
                    required
                    class="input flex-1 text-sm"
                    placeholder="Nom de la sous-catégorie"
                  />
                  <div class="flex items-center gap-1">
                    <input
                      :value="subcategory.plannedAmountCents / 100"
                      @input="subcategory.plannedAmountCents = Math.round(Number(($event.target as HTMLInputElement).value) * 100)"
                      type="number"
                      required
                      min="0"
                      step="0.01"
                      class="input w-24 text-sm"
                      placeholder="Montant"
                    />
                    <span class="text-xs text-gray-600">€</span>
                  </div>
                  <button
                    type="button"
                    @click="removeSubcategory(catIndex, subIndex)"
                    class="text-red-600 hover:text-red-700 px-2 text-sm"
                    title="Supprimer"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-3">
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="templateStore.loading"
            >
              {{ editingTemplate ? "Mettre à jour" : "Créer le template" }}
            </button>
            <button type="button" @click="cancelForm" class="btn btn-secondary">
              Annuler
            </button>
          </div>
        </form>
      </div>

      <!-- Templates existants -->
      <div
        v-for="template in templateStore.templates"
        :key="template.id"
        class="card"
      >
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-xl font-bold flex items-center gap-2">
              {{ template.name }}
              <span
                v-if="template.isDefault"
                class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded"
              >
                Par défaut
              </span>
            </h3>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              <p>{{ template.categories?.length || 0 }} catégorie(s)</p>
              <p v-if="template.revenueCents" class="mt-1">
                <span class="font-medium">Revenu par défaut :</span>
                {{ (template.revenueCents / 100).toFixed(2) }} €
              </p>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              v-if="!template.isDefault"
              @click="handleSetDefault(template.id)"
              class="btn btn-secondary text-sm"
              :disabled="templateStore.loading"
            >
              Définir par défaut
            </button>
            <button
              @click="startEdit(template)"
              class="btn btn-secondary text-sm"
            >
              Modifier
            </button>
            <button
              @click="handleDelete(template.id, template.name)"
              class="btn bg-red-600 hover:bg-red-700 text-white text-sm"
              :disabled="templateStore.loading"
            >
              Supprimer
            </button>
          </div>
        </div>

        <!-- Détails des catégories -->
        <div
          v-if="template.categories && template.categories.length > 0"
          class="mt-4"
        >
          <div
            v-for="category in template.categories"
            :key="category.id"
            class="mb-3"
          >
            <div class="flex justify-between items-center py-2 border-b">
              <span class="font-medium">{{ category.name }}</span>
              <span class="text-gray-600">
                {{ (category.plannedAmountCents / 100).toFixed(2) }} €
              </span>
            </div>
            <div
              v-if="category.subcategories && category.subcategories.length > 0"
              class="ml-4 mt-2"
            >
              <div
                v-for="subcategory in category.subcategories"
                :key="subcategory.id"
                class="flex justify-between py-1 text-sm text-gray-600 dark:text-gray-400"
              >
                <span>{{ subcategory.name }}</span>
                <span
                  >{{
                    (subcategory.plannedAmountCents / 100).toFixed(2)
                  }}
                  €</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from "vue";
import { useTemplateStore } from "@/stores/template";
import type { BudgetTemplate } from "@/types";

const templateStore = useTemplateStore();

const showCreateForm = ref(false);
const editingTemplate = ref<BudgetTemplate | null>(null);
const revenueEuros = ref<number | null>(null);

interface FormCategory {
  id?: number; // ID présent lors de l'édition
  name: string;
  plannedAmountCents: number;
  sortOrder?: number;
  subcategories: Array<{
    id?: number; // ID présent lors de l'édition
    name: string;
    plannedAmountCents: number;
    sortOrder?: number;
  }>;
}

const formData = reactive<{
  name: string;
  isDefault: boolean;
  categories: FormCategory[];
}>({
  name: "",
  isDefault: false,
  categories: [],
});

function resetForm() {
  formData.name = "";
  formData.isDefault = false;
  formData.categories = [];
  revenueEuros.value = null;
}

function openCreateForm() {
  showCreateForm.value = true;
  // Ajouter une catégorie par défaut pour guider l'utilisateur
  if (formData.categories.length === 0) {
    addCategory();
  }
}

function addCategory() {
  formData.categories.push({
    name: "",
    plannedAmountCents: 0,
    subcategories: [],
  });
}

function removeCategory(index: number) {
  formData.categories.splice(index, 1);
}

function addSubcategory(categoryIndex: number) {
  formData.categories[categoryIndex].subcategories.push({
    name: "",
    plannedAmountCents: 0,
  });
}

function removeSubcategory(categoryIndex: number, subcategoryIndex: number) {
  formData.categories[categoryIndex].subcategories.splice(subcategoryIndex, 1);
}

function getCategoryTotal(category: FormCategory): number {
  return category.subcategories.reduce((sum, sub) => sum + sub.plannedAmountCents, 0);
}

async function handleSubmit() {
  try {
    // Calculer le total de chaque catégorie à partir des sous-catégories
    const categoriesWithTotal = formData.categories.map(cat => ({
      ...cat,
      plannedAmountCents: getCategoryTotal(cat),
    }));

    const payload = {
      name: formData.name,
      isDefault: formData.isDefault,
      revenueCents: revenueEuros.value ? Math.round(revenueEuros.value * 100) : null,
      categories: categoriesWithTotal,
    };

    if (editingTemplate.value) {
      // Mode édition (nom, isDefault, revenueCents ET catégories)
      await templateStore.updateTemplate(editingTemplate.value.id, payload);
    } else {
      // Mode création
      await templateStore.createTemplate(payload);
    }

    cancelForm();
    await templateStore.fetchTemplates();
  } catch (error) {
    console.error("Erreur lors de la soumission:", error);
  }
}

function cancelForm() {
  showCreateForm.value = false;
  editingTemplate.value = null;
  resetForm();
}

function startEdit(template: BudgetTemplate) {
  editingTemplate.value = template;
  formData.name = template.name;
  formData.isDefault = template.isDefault;
  revenueEuros.value = template.revenueCents ? template.revenueCents / 100 : null;

  // Charger les catégories existantes pour édition
  formData.categories = (template.categories || []).map((cat) => ({
    id: cat.id,
    name: cat.name,
    plannedAmountCents: cat.plannedAmountCents,
    sortOrder: cat.sortOrder,
    subcategories: (cat.subcategories || []).map((sub) => ({
      id: sub.id,
      name: sub.name,
      plannedAmountCents: sub.plannedAmountCents,
      sortOrder: sub.sortOrder,
    })),
  }));

  showCreateForm.value = false;
}

async function handleSetDefault(id: number) {
  if (confirm("Définir ce template comme défaut ?")) {
    try {
      await templateStore.setDefaultTemplate(id);
    } catch (error) {
      console.error("Erreur:", error);
    }
  }
}

async function handleDelete(id: number, name: string) {
  if (
    confirm(
      `Supprimer le template "${name}" ?\n\nCette action est irréversible.`,
    )
  ) {
    try {
      await templateStore.deleteTemplate(id);
    } catch (error) {
      console.error("Erreur:", error);
    }
  }
}

onMounted(async () => {
  await templateStore.fetchTemplates();
});
</script>

<style scoped>
.btn {
  @apply px-4 py-2 rounded font-medium transition-colors;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-800 hover:bg-gray-300;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.input {
  @apply border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.card {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow p-6;
}
</style>
