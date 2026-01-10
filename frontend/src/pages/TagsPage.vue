<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Gestion des Tags</h1>
      <button @click="openCreateModal" class="btn btn-primary">+ Nouveau tag</button>
    </div>

    <!-- Barre de recherche -->
    <div class="mb-6">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Rechercher un tag..."
        class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      />
    </div>

    <!-- Loading State -->
    <div v-if="tagsStore.loading" class="text-center py-8">
      <p>Chargement...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="tagsStore.error" class="text-center py-8 text-red-600">
      <p>{{ tagsStore.error }}</p>
    </div>

    <!-- No Results -->
    <div v-else-if="filteredTags.length === 0" class="text-center py-8 text-gray-500">
      <p>
        {{
          searchQuery
            ? "Aucun tag trouvé pour cette recherche"
            : "Aucun tag créé. Commencez par créer votre premier tag !"
        }}
      </p>
    </div>

    <!-- Tags Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="tag in filteredTags"
        :key="tag.id"
        v-memo="[tag.name, tag.color, tag.createdAt]"
        class="card hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between mb-3">
          <TagBadge :tag="tag" />
          <div class="flex gap-2">
            <button
              @click="openEditModal(tag)"
              class="text-blue-600 hover:text-blue-700 text-sm font-medium"
            >
              Modifier
            </button>
            <button
              @click="openDeleteModal(tag)"
              class="text-red-600 hover:text-red-700 text-sm font-medium"
            >
              Supprimer
            </button>
          </div>
        </div>
        <div class="text-sm text-gray-600">
          Créé le {{ new Date(tag.createdAt).toLocaleDateString("fr-FR") }}
        </div>
      </div>
    </div>

    <!-- Modal Création -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeCreateModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h2 class="text-xl font-bold mb-4">Nouveau tag</h2>
        <form @submit.prevent="handleCreate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Nom</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Ex: Courses, Transport..."
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Couleur</label>
            <div class="flex gap-2">
              <input
                v-model="form.color"
                type="color"
                class="h-10 w-16 rounded border border-gray-300 cursor-pointer"
              />
              <input
                v-model="form.color"
                type="text"
                pattern="^#[0-9A-Fa-f]{6}$"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="#3B82F6"
              />
            </div>
            <p class="text-xs text-gray-500 mt-1">Format hexadécimal (ex: #3B82F6)</p>
          </div>
          <div v-if="createError" class="text-red-600 text-sm">
            {{ createError }}
          </div>
          <div class="flex gap-2 justify-end">
            <button
              type="button"
              @click="closeCreateModal"
              class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ creating ? "Création..." : "Créer" }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Édition -->
    <div
      v-if="editingTag"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeEditModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h2 class="text-xl font-bold mb-4">Modifier le tag</h2>
        <form @submit.prevent="handleUpdate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Nom</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Couleur</label>
            <div class="flex gap-2">
              <input
                v-model="form.color"
                type="color"
                class="h-10 w-16 rounded border border-gray-300 cursor-pointer"
              />
              <input
                v-model="form.color"
                type="text"
                pattern="^#[0-9A-Fa-f]{6}$"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
          <div v-if="updateError" class="text-red-600 text-sm">
            {{ updateError }}
          </div>
          <div class="flex gap-2 justify-end">
            <button
              type="button"
              @click="closeEditModal"
              class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="updating"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ updating ? "Modification..." : "Enregistrer" }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Suppression -->
    <div
      v-if="deletingTag"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeDeleteModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h2 class="text-xl font-bold mb-4">Supprimer le tag ?</h2>
        <p class="mb-4 text-gray-700">
          Voulez-vous vraiment supprimer le tag
          <strong>"{{ deletingTag.name }}"</strong> ? Cette action est irréversible.
        </p>
        <p class="mb-6 text-sm text-gray-600">
          Note : Les dépenses associées ne seront pas supprimées, seul le tag sera retiré.
        </p>
        <div v-if="deleteError" class="text-red-600 text-sm mb-4">
          {{ deleteError }}
        </div>
        <div class="flex gap-2 justify-end">
          <button
            type="button"
            @click="closeDeleteModal"
            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Annuler
          </button>
          <button
            @click="handleDelete"
            :disabled="deleting"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ deleting ? "Suppression..." : "Supprimer" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useTagsStore } from "@/stores/tags";
import TagBadge from "@/components/TagBadge.vue";
import type { Tag } from "@/types";

const tagsStore = useTagsStore();
const searchQuery = ref("");
const showCreateModal = ref(false);
const editingTag = ref<Tag | null>(null);
const deletingTag = ref<Tag | null>(null);

const form = ref({
  name: "",
  color: "#3B82F6",
});

const creating = ref(false);
const updating = ref(false);
const deleting = ref(false);
const createError = ref("");
const updateError = ref("");
const deleteError = ref("");

onMounted(() => {
  tagsStore.fetchTags();
});

const filteredTags = computed(() => {
  if (!searchQuery.value) return tagsStore.tags;
  const query = searchQuery.value.toLowerCase();
  return tagsStore.tags.filter((t) => t.name.toLowerCase().includes(query));
});

function openCreateModal() {
  resetForm();
  showCreateModal.value = true;
}

function closeCreateModal() {
  showCreateModal.value = false;
  createError.value = "";
  resetForm();
}

function openEditModal(tag: Tag) {
  editingTag.value = tag;
  form.value = { name: tag.name, color: tag.color };
  updateError.value = "";
}

function closeEditModal() {
  editingTag.value = null;
  updateError.value = "";
  resetForm();
}

function openDeleteModal(tag: Tag) {
  deletingTag.value = tag;
  deleteError.value = "";
}

function closeDeleteModal() {
  deletingTag.value = null;
  deleteError.value = "";
}

async function handleCreate() {
  creating.value = true;
  createError.value = "";

  try {
    await tagsStore.createTag(form.value);
    closeCreateModal();
  } catch (error: any) {
    createError.value =
      error.response?.data?.message ||
      "Erreur lors de la création du tag. Vérifiez que le nom n'existe pas déjà.";
  } finally {
    creating.value = false;
  }
}

async function handleUpdate() {
  if (!editingTag.value) return;

  updating.value = true;
  updateError.value = "";

  try {
    await tagsStore.updateTag(editingTag.value.id, form.value);
    closeEditModal();
  } catch (error: any) {
    updateError.value =
      error.response?.data?.message ||
      "Erreur lors de la modification. Vérifiez que le nom n'existe pas déjà.";
  } finally {
    updating.value = false;
  }
}

async function handleDelete() {
  if (!deletingTag.value) return;

  deleting.value = true;
  deleteError.value = "";

  try {
    await tagsStore.deleteTag(deletingTag.value.id);
    closeDeleteModal();
  } catch (error: any) {
    deleteError.value = error.response?.data?.message || "Erreur lors de la suppression";
  } finally {
    deleting.value = false;
  }
}

function resetForm() {
  form.value = { name: "", color: "#3B82F6" };
}
</script>
