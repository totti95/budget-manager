import { ref, computed } from "vue";
import { defineStore } from "pinia";
import {
  usersApi,
  rolesApi,
  type UserFilters,
  type CreateUserData,
  type UpdateUserData,
  type UpdatePasswordData,
} from "@/api/users";
import type { User, Role, UserWithPassword } from "@/types";

export const useUsersStore = defineStore("users", () => {
  // State
  const users = ref<User[]>([]);
  const roles = ref<Role[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Pagination
  const currentPage = ref(1);
  const lastPage = ref(1);
  const perPage = ref(12);
  const total = ref(0);

  // Filters
  const searchQuery = ref("");
  const roleFilter = ref<string>("");
  const statusFilter = ref<"active" | "deleted" | "">("active");

  // Getters
  const paginationInfo = computed(() => ({
    currentPage: currentPage.value,
    lastPage: lastPage.value,
    perPage: perPage.value,
    total: total.value,
  }));

  // Actions
  async function fetchUsers(page: number = 1) {
    loading.value = true;
    error.value = null;

    try {
      const filters: UserFilters = {
        page,
        search: searchQuery.value || undefined,
        role: roleFilter.value || undefined,
        status: statusFilter.value || undefined,
      };

      const response = await usersApi.list(filters);

      users.value = response.data;
      currentPage.value = response.current_page;
      lastPage.value = response.last_page;
      perPage.value = response.per_page;
      total.value = response.total;
    } catch (err) {
      error.value = "Erreur lors du chargement des utilisateurs";
      console.error("Error fetching users:", err);
    } finally {
      loading.value = false;
    }
  }

  async function fetchRoles() {
    try {
      roles.value = await rolesApi.list();
    } catch (err) {
      console.error("Error fetching roles:", err);
    }
  }

  async function createUser(data: CreateUserData): Promise<UserWithPassword> {
    loading.value = true;
    error.value = null;

    try {
      const result = await usersApi.create(data);
      await fetchUsers(currentPage.value);
      return result;
    } catch (err) {
      error.value = "Erreur lors de la création de l'utilisateur";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateUser(id: number, data: UpdateUserData): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const updatedUser = await usersApi.update(id, data);

      // Mettre � jour l'utilisateur dans la liste
      const index = users.value.findIndex((u) => u.id === id);
      if (index !== -1) {
        users.value[index] = updatedUser;
      }
    } catch (err) {
      error.value = "Erreur lors de la mise à jour de l'utilisateur";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updatePassword(
    id: number,
    data: UpdatePasswordData,
  ): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      await usersApi.updatePassword(id, data);
    } catch (err) {
      error.value = "Erreur lors du changement de mot de passe";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteUser(id: number): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      console.log("Deleting user with id:", id);
      await usersApi.delete(id);
      await fetchUsers(currentPage.value);
    } catch (err) {
      error.value = "Erreur lors de la désactivation de l'utilisateur";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function setSearchQuery(query: string) {
    searchQuery.value = query;
  }

  function setRoleFilter(role: string) {
    roleFilter.value = role;
  }

  function setStatusFilter(status: "active" | "deleted" | "") {
    statusFilter.value = status;
  }

  function clearFilters() {
    searchQuery.value = "";
    roleFilter.value = "";
    statusFilter.value = "active";
  }

  return {
    // State
    users,
    roles,
    loading,
    error,
    searchQuery,
    roleFilter,
    statusFilter,

    // Getters
    paginationInfo,

    // Actions
    fetchUsers,
    fetchRoles,
    createUser,
    updateUser,
    updatePassword,
    deleteUser,
    setSearchQuery,
    setRoleFilter,
    setStatusFilter,
    clearFilters,
  };
});
