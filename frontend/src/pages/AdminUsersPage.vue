<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Gestion des utilisateurs</h1>

    <!-- Filters and Search -->
    <div class="mb-6 flex flex-wrap gap-4">
      <FormInput
        v-model="usersStore.searchQuery"
        type="text"
        placeholder="Rechercher par nom ou email..."
        class="flex-1 min-w-[250px]"
        @input="debouncedSearch"
      />

      <FormSelect
        v-model="usersStore.roleFilter"
        placeholder="Tous les rôles"
        @change="handleFilterChange"
      >
        <option value="user">Utilisateur</option>
        <option value="admin">Administrateur</option>
      </FormSelect>

      <FormSelect
        v-model="usersStore.statusFilter"
        placeholder="Tous les statuts"
        @change="handleFilterChange"
      >
        <option value="active">Actifs</option>
        <option value="deleted">Désactivés</option>
      </FormSelect>

      <FormButton
        variant="primary"
        size="md"
        @click="openCreateModal"
      >
        Créer un utilisateur
      </FormButton>
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
                <FormButton
                  variant="success"
                  size="sm"
                  @click="confirmRestore(user)"
                >
                  Réactiver
                </FormButton>
              </template>
              <template v-else>
                <!-- Utilisateur actif : afficher boutons normaux -->
                <FormButton
                  variant="ghost"
                  size="sm"
                  @click="openEditModal(user)"
                >
                  Modifier
                </FormButton>
                <FormButton
                  variant="ghost"
                  size="sm"
                  @click="openPasswordModal(user)"
                >
                  Mot de passe
                </FormButton>
                <FormButton
                  variant="danger"
                  size="sm"
                  @click="confirmDelete(user)"
                >
                  Désactiver
                </FormButton>
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
        <FormButton
          variant="secondary"
          size="sm"
          :disabled="usersStore.paginationInfo.currentPage === 1"
          @click="handlePageChange(usersStore.paginationInfo.currentPage - 1)"
        >
          Précédent
        </FormButton>
        <span class="text-sm text-gray-600 dark:text-gray-400">
          Page {{ usersStore.paginationInfo.currentPage }} sur
          {{ usersStore.paginationInfo.lastPage }}
        </span>
        <FormButton
          variant="secondary"
          size="sm"
          :disabled="
            usersStore.paginationInfo.currentPage ===
            usersStore.paginationInfo.lastPage
          "
          @click="handlePageChange(usersStore.paginationInfo.currentPage + 1)"
        >
          Suivant
        </FormButton>
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
          <FormInput
            v-model="userForm.name"
            type="text"
            label="Nom"
            :error="formErrors.name"
            required
            class="mb-4"
          />

          <FormInput
            v-model="userForm.email"
            type="email"
            label="Email"
            :error="formErrors.email"
            required
            class="mb-4"
          />

          <FormSelect
            v-model="userForm.roleId"
            label="Rôle"
            placeholder="Sélectionner un rôle"
            :error="formErrors.roleId"
            required
            class="mb-4"
          >
            <option
              v-for="role in usersStore.roles"
              :key="role.id"
              :value="role.id"
            >
              {{ role.label === "admin" ? "Administrateur" : "Utilisateur" }}
            </option>
          </FormSelect>

          <p v-if="!editingUser" class="text-sm text-gray-600 dark:text-gray-400 mb-4 italic">
            Un mot de passe sera généré automatiquement
          </p>

          <div class="flex gap-3">
            <FormButton
              type="submit"
              variant="primary"
              size="md"
              :loading="isSubmitting"
              loading-text="Enregistrement..."
              class="flex-1"
            >
              {{ editingUser ? "Modifier" : "Créer" }}
            </FormButton>
            <FormButton
              type="button"
              variant="secondary"
              size="md"
              @click="closeUserModal"
              class="flex-1"
            >
              Annuler
            </FormButton>
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

        <FormButton
          variant="success"
          size="md"
          full-width
          @click="closeGeneratedPasswordModal"
        >
          J'ai noté le mot de passe
        </FormButton>
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
          <FormInput
            v-model="passwordForm.password"
            type="password"
            label="Nouveau mot de passe"
            :error="passwordErrors.password"
            required
            class="mb-4"
          />

          <FormInput
            v-model="passwordForm.password_confirmation"
            type="password"
            label="Confirmer le mot de passe"
            :error="passwordErrors.password_confirmation"
            required
            class="mb-4"
          />

          <div class="flex gap-3">
            <FormButton
              type="submit"
              variant="primary"
              size="md"
              :loading="isPasswordSubmitting"
              loading-text="Modification..."
              class="flex-1"
            >
              Modifier
            </FormButton>
            <FormButton
              type="button"
              variant="secondary"
              size="md"
              @click="closePasswordModal"
              class="flex-1"
            >
              Annuler
            </FormButton>
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
import { errorHandler } from "@/utils/errorHandler";
import type { User } from "@/types";
import FormInput from "@/components/FormInput.vue";
import FormSelect from "@/components/FormSelect.vue";
import FormButton from "@/components/FormButton.vue";

const usersStore = useUsersStore();
const toast = useToast();
const { confirm } = useConfirm();

const showUserModal = ref(false);
const showPasswordModal = ref(false);
const showGeneratedPasswordModal = ref(false);
const editingUser = ref<User | null>(null);
const passwordUser = ref<User | null>(null);
const generatedPassword = ref("");
const isSubmitting = ref(false);
const isPasswordSubmitting = ref(false);

const userForm = ref({
  name: "",
  email: "",
  roleId: undefined as number | undefined,
});

const passwordForm = ref({
  password: "",
  password_confirmation: "",
});

const formErrors = ref<Record<string, string>>({});
const passwordErrors = ref<Record<string, string>>({});

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
  formErrors.value = {};
  isSubmitting.value = true;

  try {
    if (editingUser.value) {
      await usersStore.updateUser(editingUser.value.id, userForm.value);
      toast.success("Utilisateur modifié avec succès");
      closeUserModal();
    } else {
      if (!userForm.value.roleId) {
        formErrors.value.roleId = "Le rôle est requis";
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
      closeUserModal();
    }
  } catch (error) {
    const validationErrors = errorHandler.handleValidation(error);
    if (validationErrors) {
      formErrors.value = validationErrors;
    } else {
      errorHandler.handle(error, "Erreur lors de l'enregistrement de l'utilisateur");
    }
  } finally {
    isSubmitting.value = false;
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

  passwordErrors.value = {};
  isPasswordSubmitting.value = true;

  try {
    await usersStore.updatePassword(passwordUser.value.id, passwordForm.value);
    toast.success("Mot de passe modifié avec succès");
    closePasswordModal();
  } catch (error) {
    const validationErrors = errorHandler.handleValidation(error);
    if (validationErrors) {
      passwordErrors.value = validationErrors;
    } else {
      errorHandler.handle(error, "Erreur lors du changement de mot de passe");
    }
  } finally {
    isPasswordSubmitting.value = false;
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
    errorHandler.handle(error, "Erreur lors de la désactivation de l'utilisateur");
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
    errorHandler.handle(error, "Erreur lors de la réactivation de l'utilisateur");
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
