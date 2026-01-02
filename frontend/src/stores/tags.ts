import { defineStore } from "pinia";
import { ref } from "vue";
import { tagsApi, type CreateTagData, type UpdateTagData } from "@/api/tags";
import type { Tag } from "@/types";

export const useTagsStore = defineStore("tags", () => {
  const tags = ref<Tag[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchTags() {
    loading.value = true;
    error.value = null;
    try {
      tags.value = await tagsApi.list();
      return tags.value;
    } catch (err) {
      error.value = "Erreur lors du chargement des tags";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createTag(data: CreateTagData) {
    loading.value = true;
    error.value = null;
    try {
      const tag = await tagsApi.create(data);
      tags.value.push(tag);
      // Sort tags alphabetically
      tags.value.sort((a, b) => a.name.localeCompare(b.name));
      return tag;
    } catch (err) {
      error.value = "Erreur lors de la création du tag";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateTag(id: number, data: UpdateTagData) {
    loading.value = true;
    error.value = null;
    try {
      const updatedTag = await tagsApi.update(id, data);
      const index = tags.value.findIndex((t) => t.id === id);
      if (index !== -1) {
        tags.value[index] = updatedTag;
        // Re-sort after update
        tags.value.sort((a, b) => a.name.localeCompare(b.name));
      }
      return updatedTag;
    } catch (err) {
      error.value = "Erreur lors de la mise à jour du tag";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteTag(id: number) {
    loading.value = true;
    error.value = null;
    try {
      await tagsApi.delete(id);
      tags.value = tags.value.filter((t) => t.id !== id);
    } catch (err) {
      error.value = "Erreur lors de la suppression du tag";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    tags,
    loading,
    error,
    fetchTags,
    createTag,
    updateTag,
    deleteTag,
  };
});
