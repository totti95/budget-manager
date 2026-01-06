# Composants de Formulaire

Ce dossier contient des composants de formulaire réutilisables pour garantir une UX cohérente dans toute l'application.

## 📦 Composants Disponibles

### FormInput

Champ de saisie avec gestion d'erreurs intégrée.

**Props** :

```typescript
interface Props {
  id?: string; // ID unique (auto-généré si absent)
  label?: string; // Libellé du champ
  type?: string; // Type d'input (défaut: "text")
  modelValue?: string | number; // Valeur v-model
  placeholder?: string; // Texte de placeholder
  error?: string; // Message d'erreur
  hint?: string; // Texte d'aide
  disabled?: boolean; // Désactivation
  required?: boolean; // Champ requis (affiche *)
  autocomplete?: string; // Attribut autocomplete
}
```

**Exemple** :

```vue
<FormInput
  v-model="email"
  type="email"
  label="Email"
  placeholder="votre@email.com"
  :error="errors.email"
  required
  autocomplete="email"
/>
```

**Avec hint** :

```vue
<FormInput
  v-model="password"
  type="password"
  label="Mot de passe"
  hint="Au moins 8 caractères"
  :error="errors.password"
/>
```

---

### FormSelect

Liste déroulante avec style cohérent.

**Props** :

```typescript
interface Props {
  id?: string;
  label?: string;
  modelValue?: string | number;
  placeholder?: string; // Option vide initiale
  error?: string;
  hint?: string;
  disabled?: boolean;
  required?: boolean;
}
```

**Exemple** :

```vue
<FormSelect
  v-model="roleId"
  label="Rôle"
  placeholder="Sélectionner un rôle"
  :error="errors.roleId"
  required
>
  <option :value="1">Utilisateur</option>
  <option :value="2">Administrateur</option>
</FormSelect>
```

**Avec données dynamiques** :

```vue
<FormSelect v-model="selectedRole" label="Rôle" placeholder="Tous les rôles">
  <option
    v-for="role in roles"
    :key="role.id"
    :value="role.id"
  >
    {{ role.label }}
  </option>
</FormSelect>
```

---

### FormButton

Bouton avec indicateur de chargement et variantes de style.

**Props** :

```typescript
interface Props {
  type?: "button" | "submit" | "reset"; // Défaut: "button"
  variant?: "primary" | "secondary" | "danger" | "success" | "warning" | "ghost";
  size?: "sm" | "md" | "lg"; // Défaut: "md"
  disabled?: boolean;
  loading?: boolean; // Affiche spinner
  loadingText?: string; // Texte pendant loading
  fullWidth?: boolean; // Largeur 100%
}
```

**Variantes** :

```vue
<!-- Bouton principal -->
<FormButton variant="primary">
  Enregistrer
</FormButton>

<!-- Bouton secondaire -->
<FormButton variant="secondary">
  Annuler
</FormButton>

<!-- Bouton danger (actions destructives) -->
<FormButton variant="danger">
  Supprimer
</FormButton>

<!-- Bouton success -->
<FormButton variant="success">
  Valider
</FormButton>

<!-- Bouton warning -->
<FormButton variant="warning">
  Attention
</FormButton>

<!-- Bouton ghost (transparent) -->
<FormButton variant="ghost">
  Modifier
</FormButton>
```

**Tailles** :

```vue
<FormButton size="sm">Petit</FormButton>
<FormButton size="md">Moyen</FormButton>
<FormButton size="lg">Grand</FormButton>
```

**Avec chargement** :

```vue
<FormButton
  type="submit"
  variant="primary"
  :loading="isSubmitting"
  loading-text="Enregistrement..."
>
  Enregistrer
</FormButton>
```

**Largeur complète** :

```vue
<FormButton variant="primary" full-width>
  Se connecter
</FormButton>
```

---

## 🎨 Exemples Complets

### Formulaire de Connexion

```vue
<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <FormInput
      v-model="form.email"
      type="email"
      label="Email"
      placeholder="votre@email.com"
      :error="errors.email"
      required
      autocomplete="email"
    />

    <FormInput
      v-model="form.password"
      type="password"
      label="Mot de passe"
      placeholder="••••••••"
      :error="errors.password"
      required
      autocomplete="current-password"
    />

    <FormButton
      type="submit"
      variant="primary"
      :loading="isSubmitting"
      loading-text="Connexion..."
      full-width
    >
      Se connecter
    </FormButton>
  </form>
</template>

<script setup lang="ts">
import { ref } from "vue";
import FormInput from "@/components/FormInput.vue";
import FormButton from "@/components/FormButton.vue";
import { errorHandler } from "@/utils/errorHandler";

const form = ref({ email: "", password: "" });
const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const handleSubmit = async () => {
  errors.value = {};
  isSubmitting.value = true;

  try {
    // Votre logique de soumission
  } catch (error) {
    const validationErrors = errorHandler.handleValidation(error);
    if (validationErrors) {
      errors.value = validationErrors;
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>
```

### Formulaire avec Select

```vue
<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <FormInput v-model="form.name" label="Nom" :error="errors.name" required />

    <FormSelect
      v-model="form.roleId"
      label="Rôle"
      placeholder="Sélectionner un rôle"
      :error="errors.roleId"
      required
    >
      <option :value="1">Utilisateur</option>
      <option :value="2">Administrateur</option>
    </FormSelect>

    <div class="flex gap-3">
      <FormButton type="submit" variant="primary" :loading="isSubmitting" class="flex-1">
        Créer
      </FormButton>
      <FormButton type="button" variant="secondary" @click="cancel" class="flex-1">
        Annuler
      </FormButton>
    </div>
  </form>
</template>
```

### Actions dans un Tableau

```vue
<template>
  <td class="space-x-2">
    <FormButton variant="ghost" size="sm" @click="edit(item)"> Modifier </FormButton>
    <FormButton variant="danger" size="sm" @click="confirmDelete(item)"> Supprimer </FormButton>
  </td>
</template>
```

---

## 🎯 Bonnes Pratiques

### 1. Gestion des Erreurs

**✅ À FAIRE** :

```vue
<FormInput
  v-model="email"
  :error="errors.email"  <!-- Erreur directement sous le champ -->
/>
```

**❌ À ÉVITER** :

```vue
<input v-model="email" />
<div v-if="errors.email">{{ errors.email }}</div>
<!-- Séparé du champ -->
```

### 2. États de Chargement

**✅ À FAIRE** :

```vue
<FormButton :loading="isSubmitting" loading-text="Enregistrement...">
  Enregistrer
</FormButton>
```

**❌ À ÉVITER** :

```vue
<button :disabled="isSubmitting">
  {{ isSubmitting ? 'Enregistrement...' : 'Enregistrer' }}
</button>
```

### 3. Variantes de Boutons

| Action                                     | Variante    |
| ------------------------------------------ | ----------- |
| Action principale (soumettre, créer)       | `primary`   |
| Action secondaire (annuler, fermer)        | `secondary` |
| Action destructive (supprimer, désactiver) | `danger`    |
| Action positive (valider, activer)         | `success`   |
| Avertissement                              | `warning`   |
| Action légère dans tableau                 | `ghost`     |

### 4. Autocomplete

Toujours spécifier l'attribut `autocomplete` approprié :

```vue
<FormInput autocomplete="name" />
<FormInput autocomplete="email" />
<FormInput autocomplete="current-password" />
<FormInput autocomplete="new-password" />
```

### 5. Required et Labels

```vue
<!-- ✅ Avec label et required -->
<FormInput
  label="Email"
  required
  v-model="email"
/>

<!-- ❌ Sans label -->
<FormInput
  placeholder="Email"  <!-- Ne remplace pas le label ! -->
  v-model="email"
/>
```

---

## 🌙 Support du Dark Mode

Tous les composants supportent automatiquement le dark mode via TailwindCSS :

- Bordures adaptées
- Textes lisibles
- Backgrounds appropriés
- Contraste maintenu

Aucune configuration supplémentaire n'est nécessaire.

---

## 🔧 Personnalisation

### Styling Additionnel

Vous pouvez ajouter des classes TailwindCSS :

```vue
<FormInput
  v-model="email"
  class="mb-4"  <!-- Margin bottom -->
/>

<FormButton
  variant="primary"
  class="w-48"  <!-- Largeur fixe -->
>
  Enregistrer
</FormButton>
```

### Événements

Les composants émettent des événements standard :

```vue
<FormInput v-model="search" @input="handleSearch" @blur="handleBlur" />

<FormButton @click="handleClick">
  Cliquer
</FormButton>
```
