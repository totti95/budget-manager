<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Gestion des utilisateurs</h1>

    <!-- Filters and Search -->
    <div class="mb-6 flex flex-wrap gap-4">
      <input
        v-model="usersStore.searchQuery"
        type="text"
        placeholder="Rechercher par nom ou email..."
        class="flex-1 min-w-[250px] px-4 py-2 border rounded-lg"
        @input="debouncedSearch"
      />

      <select
        v-model="usersStore.roleFilter"
        class="px-4 py-2 border rounded-lg"
        @change="handleFilterChange"
      >
        <option value="">Tous les rôles</option>
        <option value="user">Utilisateur</option>
        <option value="admin">Administrateur</option>
      </select>

      <select
        v-model="usersStore.statusFilter"
        class="px-4 py-2 border rounded-lg"
        @change="handleFilterChange"
      >
        <option value="">Tous les statuts</option>
        <option value="active">Actifs</option>
        <option value="deleted">Désactivés</option>
      </select>

      <button
        @click="openCreateModal"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Créer un utilisateur
      </button>
    </div>

    <!-- Loading state -->
    <div v-if="usersStore.loading" class="text-center py-12">
      <p>Chargement...</p>
    </div>

    <!-- Error state -->
    <div
      v-else-if="usersStore.error"
      class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"
    >
      {{ usersStore.error }}
    </div>

    <!-- Users Table -->
    <div v-else class="bg-white shadow-md rounded-lg overflow-hidden">
      <table class="min-w-full">
        <thead class="bg-gray-100">
          <tr>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
            >
              Nom
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
            >
              Email
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
            >
              Rôle
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
            >
              Date de création
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="user in usersStore.users"
            :key="user.id"
            :class="{ 'opacity-60': user.deletedAt }"
          >
            <td class="px-6 py-4">{{ user.name }}</td>
            <td class="px-6 py-4">{{ user.email }}</td>
            <td class="px-6 py-4">
              <span
                :class="[
                  'px-2 py-1 rounded text-sm',
                  user.role?.label === 'admin'
                    ? 'bg-purple-100 text-purple-800'
                    : 'bg-gray-100 text-gray-800',
                ]"
              >
                {{
                  user.role?.label === "admin"
                    ? "Administrateur"
                    : "Utilisateur"
                }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">
              {{ new Date(user.createdAt).toLocaleDateString("fr-FR") }}
            </td>
            <td class="px-6 py-4 space-x-2">
              <template v-if="user.deletedAt">
                <!-- Utilisateur désactivé : afficher bouton Réactiver -->
                <button
                  @click="confirmRestore(user)"
                  class="text-green-600 hover:text-green-800"
                >
                  Réactiver
                </button>
              </template>
              <template v-else>
                <!-- Utilisateur actif : afficher boutons normaux -->
                <button
                  @click="openEditModal(user)"
                  class="text-blue-600 hover:text-blue-800"
                >
                  Modifier
                </button>
                <button
                  @click="openPasswordModal(user)"
                  class="text-green-600 hover:text-green-800"
                >
                  Mot de passe
                </button>
                <button
                  @click="confirmDelete(user)"
                  class="text-red-600 hover:text-red-800"
                >
                  Désactiver
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="usersStore.paginationInfo.lastPage > 1"
        class="px-6 py-4 flex justify-between items-center border-t"
      >
        <button
          :disabled="usersStore.paginationInfo.currentPage === 1"
          @click="handlePageChange(usersStore.paginationInfo.currentPage - 1)"
          class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
        >
          Précédent
        </button>
        <span class="text-sm text-gray-600">
          Page {{ usersStore.paginationInfo.currentPage }} sur
          {{ usersStore.paginationInfo.lastPage }}
        </span>
        <button
          :disabled="
            usersStore.paginationInfo.currentPage ===
            usersStore.paginationInfo.lastPage
          "
          @click="handlePageChange(usersStore.paginationInfo.currentPage + 1)"
          class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
        >
          Suivant
        </button>
      </div>
    </div>

    <!-- Modal Créer/Modifier Utilisateur -->
    <div
      v-if="showUserModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <h2 class="text-2xl font-bold mb-4">
          {{ editingUser ? "Modifier l'utilisateur" : "Créer un utilisateur" }}
        </h2>

        <form @submit.prevent="handleUserSubmit">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Nom</label>
            <input
              v-model="userForm.name"
              type="text"
              class="w-full px-3 py-2 border rounded-lg"
              required
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Email</label>
            <input
              v-model="userForm.email"
              type="email"
              class="w-full px-3 py-2 border rounded-lg"
              required
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Rôle</label>
            <select
              v-model="userForm.roleId"
              class="w-full px-3 py-2 border rounded-lg"
              required
            >
              <option value="">Sélectionner un rôle</option>
              <option
                v-for="role in usersStore.roles"
                :key="role.id"
                :value="role.id"
              >
                {{ role.label === "admin" ? "Administrateur" : "Utilisateur" }}
              </option>
            </select>
          </div>

          <p v-if="!editingUser" class="text-sm text-gray-600 mb-4 italic">
            Un mot de passe sera généré automatiquement
          </p>

          <div class="flex gap-3">
            <button
              type="submit"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              {{ editingUser ? "Modifier" : "Créer" }}
            </button>
            <button
              type="button"
              @click="closeUserModal"
              class="flex-1 px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400"
            >
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Mot de passe généré -->
    <div
      v-if="showGeneratedPasswordModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <h2 class="text-2xl font-bold mb-4">Mot de passe généré</h2>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
          <p class="text-sm text-yellow-800 mb-2">
            Attention: notez ce mot de passe, il ne sera plus affiché
          </p>
          <div class="flex items-center gap-2">
            <code
              class="flex-1 text-xl font-mono bg-gray-100 px-3 py-2 rounded"
              >{{ generatedPassword }}</code
            >
            <button
              @click="copyPassword"
              class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Copier
            </button>
          </div>
        </div>

        <button
          @click="closeGeneratedPasswordModal"
          class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        >
          J'ai noté le mot de passe
        </button>
      </div>
    </div>

    <!-- Modal Changement de mot de passe -->
    <div
      v-if="showPasswordModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <h2 class="text-2xl font-bold mb-4">Changer le mot de passe</h2>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
          <p class="text-sm text-yellow-800">
            Vous allez changer le mot de passe de
            <strong>{{ passwordUser?.name }}</strong
            >. L'utilisateur ne sera pas notifié de ce changement.
          </p>
        </div>

        <form @submit.prevent="handlePasswordSubmit">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2"
              >Nouveau mot de passe</label
            >
            <input
              v-model="passwordForm.password"
              type="password"
              class="w-full px-3 py-2 border rounded-lg"
              required
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium mb-2"
              >Confirmer le mot de passe</label
            >
            <input
              v-model="passwordForm.password_confirmation"
              type="password"
              class="w-full px-3 py-2 border rounded-lg"
              required
            />
          </div>

          <div class="flex gap-3">
            <button
              type="submit"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Modifier
            </button>
            <button
              type="button"
              @click="closePasswordModal"
              class="flex-1 px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400"
            >
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useUsersStore } from "@/stores/users";
import { useToast } from "@/composables/useToast";
import { useConfirm } from "@/composables/useConfirm";
import type { User } from "@/types";

const usersStore = useUsersStore();
const toast = useToast();
const { confirm } = useConfirm();

const showUserModal = ref(false);
const showPasswordModal = ref(false);
const showGeneratedPasswordModal = ref(false);
const editingUser = ref<User | null>(null);
const passwordUser = ref<User | null>(null);
const generatedPassword = ref("");

const userForm = ref({
  name: "",
  email: "",
  roleId: undefined as number | undefined,
});

const passwordForm = ref({
  password: "",
  password_confirmation: "",
});

let searchTimeout: number | null = null;

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    usersStore.fetchUsers(1);
  }, 300);
};

const handleFilterChange = () => {
  usersStore.fetchUsers(1);
};

const handlePageChange = (page: number) => {
  usersStore.fetchUsers(page);
};

const openCreateModal = () => {
  editingUser.value = null;
  userForm.value = { name: "", email: "", roleId: undefined };
  showUserModal.value = true;
};

const openEditModal = (user: User) => {
  editingUser.value = user;
  userForm.value = {
    name: user.name,
    email: user.email,
    roleId: user.roleId,
  };
  showUserModal.value = true;
};

const closeUserModal = () => {
  showUserModal.value = false;
  editingUser.value = null;
};

const handleUserSubmit = async () => {
  try {
    if (editingUser.value) {
      await usersStore.updateUser(editingUser.value.id, userForm.value);
      toast.success("Utilisateur modifié avec succès");
    } else {
      if (!userForm.value.roleId) {
        toast.error("Le rôle est requis");
        return;
      }
      const result = await usersStore.createUser({
        name: userForm.value.name,
        email: userForm.value.email,
        roleId: userForm.value.roleId,
      });
      generatedPassword.value = result.password;
      showGeneratedPasswordModal.value = true;
      toast.success("Utilisateur créé avec succès");
    }
    closeUserModal();
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Une erreur est survenue";
    toast.error(errorMessage);
  }
};

const openPasswordModal = (user: User) => {
  passwordUser.value = user;
  passwordForm.value = { password: "", password_confirmation: "" };
  showPasswordModal.value = true;
};

const closePasswordModal = () => {
  showPasswordModal.value = false;
  passwordUser.value = null;
};

const handlePasswordSubmit = async () => {
  if (!passwordUser.value) return;

  try {
    await usersStore.updatePassword(passwordUser.value.id, passwordForm.value);
    toast.success("Mot de passe modifié avec succès");
    closePasswordModal();
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Une erreur est survenue";
    toast.error(errorMessage);
  }
};

const confirmDelete = async (user: User) => {
  const result = await confirm({
    title: "Désactiver l'utilisateur",
    message: `Voulez-vous vraiment désactiver l'utilisateur ${user.name} ?`,
    confirmText: "Désactiver",
    cancelText: "Annuler",
    confirmClass: "bg-red-600 hover:bg-red-700",
  });

  if (!result) {
    return;
  }

  try {
    await usersStore.deleteUser(user.id);
    toast.success("Utilisateur désactivé avec succès");
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Une erreur est survenue";
    toast.error(errorMessage);
  }
};

const confirmRestore = async (user: User) => {
  const result = await confirm({
    title: "Réactiver l'utilisateur",
    message: `Voulez-vous vraiment réactiver l'utilisateur ${user.name} ?`,
    confirmText: "Réactiver",
    cancelText: "Annuler",
    confirmClass: "bg-green-600 hover:bg-green-700",
  });

  if (!result) {
    return;
  }

  try {
    await usersStore.restoreUser(user.id);
    toast.success("Utilisateur réactivé avec succès");
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Une erreur est survenue";
    toast.error(errorMessage);
  }
};

const closeGeneratedPasswordModal = () => {
  showGeneratedPasswordModal.value = false;
  generatedPassword.value = "";
};

const copyPassword = () => {
  navigator.clipboard.writeText(generatedPassword.value);
  toast.success("Mot de passe copié dans le presse-papier");
};

onMounted(async () => {
  await usersStore.fetchRoles();
  await usersStore.fetchUsers();
});
</script>
