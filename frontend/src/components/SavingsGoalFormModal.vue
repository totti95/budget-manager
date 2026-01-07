<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.esc="close">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="close" />

    <!-- Modal -->
    <div
      class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 p-6 max-h-[90vh] overflow-y-auto"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold">
          {{ goal ? "Modifier l'objectif" : "Nouvel objectif d'épargne" }}
        </h3>
        <button
          type="button"
          @click="close"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="name" class="block text-sm font-medium mb-1">Nom *</label>
            <input
              id="name"
              v-model="formData.name"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
              placeholder="Ex: Vacances au Japon"
            />
          </div>

          <div>
            <label for="targetAmount" class="block text-sm font-medium mb-1"
              >Montant cible (€) *</label
            >
            <input
              id="targetAmount"
              v-model="formData.targetAmountEuros"
              type="number"
              step="0.01"
              min="0.01"
              required
              class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
              placeholder="Ex: 3000.50"
            />
          </div>
        </div>

        <div>
          <label for="description" class="block text-sm font-medium mb-1">Description</label>
          <textarea
            id="description"
            v-model="formData.description"
            rows="2"
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            placeholder="Ex: Voyage de 2 semaines à Tokyo et Kyoto"
          />
        </div>

        <div>
          <label for="asset" class="block text-sm font-medium mb-1">Actif lié (optionnel)</label>
          <select
            id="asset"
            v-model="formData.assetId"
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
          >
            <option :value="null">Aucun actif lié</option>
            <option v-for="asset in allAssets" :key="asset.id" :value="asset.id">
              {{ asset.label }} ({{ formatEuros(asset.valueCents) }})
            </option>
          </select>
          <p class="text-xs text-gray-500 mt-1">
            Si vous liez un actif, le montant actuel sera synchronisé avec la valeur de l'actif
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="startDate" class="block text-sm font-medium mb-1">Date de début *</label>
            <input
              id="startDate"
              v-model="formData.startDate"
              type="date"
              required
              class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            />
          </div>

          <div>
            <label for="targetDate" class="block text-sm font-medium mb-1">Date limite</label>
            <input
              id="targetDate"
              v-model="formData.targetDate"
              type="date"
              class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            />
          </div>
        </div>

        <div>
          <label for="priority" class="block text-sm font-medium mb-1">Priorité (0-100)</label>
          <input
            id="priority"
            v-model.number="formData.priority"
            type="number"
            min="0"
            max="100"
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
          />
        </div>

        <div v-if="goal" class="grid grid-cols-2 gap-4">
          <div>
            <label for="status" class="block text-sm font-medium mb-1">Statut</label>
            <select
              id="status"
              v-model="formData.status"
              class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            >
              <option value="active">En cours</option>
              <option value="completed">Atteint</option>
              <option value="abandoned">Abandonné</option>
              <option value="paused">En pause</option>
            </select>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            @click="close"
            class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? "Enregistrement..." : "Enregistrer" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue";
import type { SavingsGoal } from "@/types";
import { useSavingsGoalsStore } from "@/stores/savingsGoals";
import { useAssetsStore } from "@/stores/assets";

const props = defineProps<{
  goal?: SavingsGoal | null;
}>();

const emit = defineEmits<{
  close: [];
  saved: [];
}>();

const store = useSavingsGoalsStore();
const assetsStore = useAssetsStore();
const loading = ref(false);

const formData = reactive({
  name: "",
  description: "",
  targetAmountEuros: "" as string | number,
  startDate: new Date().toISOString().split("T")[0],
  targetDate: "",
  priority: 50,
  status: "active" as "active" | "completed" | "abandoned" | "paused",
  assetId: null as number | null,
});

// Combiner actifs et passifs pour le sélecteur
const allAssets = computed(() => [...assetsStore.assets, ...assetsStore.liabilities]);

function formatDateForInput(dateString: string | null): string {
  if (!dateString) return "";
  // Convertir ISO date ou datetime en YYYY-MM-DD
  return dateString.split("T")[0];
}

function formatEuros(cents: number): string {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(cents / 100);
}

onMounted(async () => {
  // Charger les assets si pas encore fait
  if (allAssets.value.length === 0) {
    await assetsStore.fetchAssets();
  }

  if (props.goal) {
    formData.name = props.goal.name;
    formData.description = props.goal.description || "";
    formData.targetAmountEuros = props.goal.targetAmountCents / 100;
    formData.startDate = formatDateForInput(props.goal.startDate);
    formData.targetDate = formatDateForInput(props.goal.targetDate);
    formData.priority = props.goal.priority;
    formData.status = props.goal.status;
    formData.assetId = props.goal.assetId;
  }
});

function close() {
  emit("close");
}

async function handleSubmit() {
  const targetAmount = parseFloat(formData.targetAmountEuros.toString());
  if (isNaN(targetAmount) || targetAmount <= 0) {
    alert("Montant cible invalide");
    return;
  }

  loading.value = true;
  try {
    const data = {
      name: formData.name,
      description: formData.description || null,
      targetAmountCents: Math.round(targetAmount * 100),
      startDate: formData.startDate,
      targetDate: formData.targetDate || null,
      priority: formData.priority,
      assetId: formData.assetId,
      ...(props.goal && { status: formData.status }),
    };

    if (props.goal) {
      await store.updateGoal(props.goal.id, data);
    } else {
      await store.createGoal(data);
    }
    emit("saved");
  } catch (error) {
    console.error("Error saving goal:", error);
  } finally {
    loading.value = false;
  }
}
</script>
