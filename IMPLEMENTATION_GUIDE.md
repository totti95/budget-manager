# Guide d'Implémentation de A à Z d'une Feature

Ce guide explique **comment implémenter une feature complète** dans Budget Manager, de la base de données au frontend, en utilisant l'exemple concret du **BudgetTemplate** (modèle de budget réutilisable).

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Backend - Laravel 11](#backend---laravel-11)
   - [Migration - Création de la table](#1-migration---création-de-la-table)
   - [Modèle - Eloquent ORM](#2-modèle---eloquent-orm)
   - [Factory - Données de test](#3-factory---données-de-test)
   - [Policy - Autorisation](#4-policy---autorisation)
   - [Controller - Logique métier](#5-controller---logique-métier)
   - [Routes API](#6-routes-api)
3. [Frontend - Vue 3 + TypeScript](#frontend---vue-3--typescript)
   - [Types TypeScript](#1-types-typescript)
   - [Schémas Valibot](#2-schémas-valibot)
   - [Client API](#3-client-api)
   - [Store Pinia](#4-store-pinia)
   - [Composant Vue](#5-composant-vue)
4. [Flow complet d'une requête](#flow-complet-dune-requête)
5. [Conversion automatique camelCase/snake_case](#conversion-automatique-camelcasesnake_case)
6. [Checklist complète](#checklist-complète)

---

## Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────────┐
│                         FRONTEND (Vue 3)                         │
│                                                                  │
│  Component.vue  →  Store (Pinia)  →  API Client  →  Axios       │
│       ↓                                                    ↓     │
│  Valibot Schema                                    camelCase    │
└─────────────────────────────────────────────────────────────────┘
                                   │
                                   │ HTTP (JSON)
                                   │
┌─────────────────────────────────────────────────────────────────┐
│                        BACKEND (Laravel)                         │
│                                                                  │
│  Route → Middleware → Controller → Policy → Model → Database    │
│            ↓                ↓                  ↓                 │
│     snake_case       Validation          Eloquent ORM           │
└─────────────────────────────────────────────────────────────────┘
```

**Principe clé :**
- Frontend utilise **camelCase** (JavaScript/TypeScript)
- Backend utilise **snake_case** (PHP/Laravel/MySQL)
- Les **middlewares** convertissent automatiquement entre les deux

---

## Backend - Laravel 11

### 1. Migration - Création de la table

**Fichier :** `backend/database/migrations/2024_01_02_000001_create_budget_templates_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budget_templates', function (Blueprint $table) {
            $table->id();                                          // Clé primaire auto-incrémentée
            $table->foreignId('user_id')                           // Clé étrangère vers users
                  ->constrained()                                  // Ajoute la contrainte FK
                  ->onDelete('cascade');                           // Supprime en cascade
            $table->string('name');                                // Nom du template
            $table->boolean('is_default')->default(false);         // Template par défaut ?
            $table->timestamps();                                  // created_at, updated_at

            $table->index(['user_id', 'is_default']);              // Index composite pour performance
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_templates');
    }
};
```

**Points clés :**
- `$table->id()` : Crée une colonne `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `foreignId('user_id')->constrained()` : Crée la FK automatiquement vers `users.id`
- `onDelete('cascade')` : Quand un user est supprimé, ses templates le sont aussi
- `timestamps()` : Ajoute `created_at` et `updated_at` (gérés automatiquement par Eloquent)
- `index()` : Accélère les requêtes qui filtrent par user_id et is_default

**Commandes :**
```bash
# Créer la migration
make artisan CMD="make:migration create_budget_templates_table"

# Exécuter les migrations
make migrate
```

---

### 2. Modèle - Eloquent ORM

**Fichier :** `backend/app/Models/BudgetTemplate.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetTemplate extends Model
{
    use HasFactory;  // Permet d'utiliser BudgetTemplate::factory()

    // Colonnes assignables en masse (protection contre les injections)
    protected $fillable = [
        'user_id',
        'name',
        'is_default',
        'revenue_cents',
    ];

    // Conversion automatique de types (DB → PHP)
    protected $casts = [
        'is_default' => 'boolean',      // tinyint(1) → true/false
        'revenue_cents' => 'integer',   // string → int
    ];

    // RELATIONS

    // Un template appartient à un utilisateur
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un template a plusieurs catégories
    public function categories(): HasMany
    {
        return $this->hasMany(TemplateCategory::class)
                    ->orderBy('sort_order');  // Tri automatique
    }

    // Un template peut générer plusieurs budgets
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'generated_from_template_id');
    }

    // LIFECYCLE HOOKS

    protected static function boot()
    {
        parent::boot();

        // Quand on sauvegarde un template comme "défaut"
        static::saving(function ($template) {
            if ($template->is_default) {
                // Désactiver tous les autres templates de cet utilisateur
                static::where('user_id', $template->user_id)
                      ->where('id', '!=', $template->id)
                      ->update(['is_default' => false]);
            }
        });
    }
}
```

**Points clés :**
- `$fillable` : Colonnes modifiables via `create()` ou `update()` (protection contre les mass assignment)
- `$casts` : Convertit automatiquement les types (ex: `0`/`1` → `false`/`true`)
- **Relations :**
  - `belongsTo` : N-1 (un template → un user)
  - `hasMany` : 1-N (un template → plusieurs catégories)
- `boot()` : Permet d'ajouter des hooks (before/after save, create, delete, etc.)

**Utilisation :**
```php
// Créer
$template = BudgetTemplate::create([
    'user_id' => 1,
    'name' => 'Budget mensuel',
    'is_default' => true,
]);

// Lire avec relations
$template = BudgetTemplate::with('categories.subcategories')->find(1);

// Modifier
$template->update(['name' => 'Nouveau nom']);

// Supprimer
$template->delete();
```

---

### 3. Factory - Données de test

**Fichier :** `backend/database/factories/BudgetTemplateFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),                    // Crée un user automatiquement
            'name' => fake()->words(3, true),                // Ex: "Budget familial mensuel"
            'is_default' => fake()->boolean(20),             // 20% de chance d'être true
            'revenue_cents' => fake()->numberBetween(200000, 400000), // 2000€ à 4000€
        ];
    }

    // State personnalisé : template par défaut
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
```

**Utilisation :**
```php
// Dans un seeder ou test
BudgetTemplate::factory()->count(5)->create();           // 5 templates aléatoires
BudgetTemplate::factory()->default()->create();          // 1 template par défaut
BudgetTemplate::factory()->create(['name' => 'Test']);   // Surcharge de valeurs
```

---

### 4. Policy - Autorisation

**Fichier :** `backend/app/Policies/BudgetTemplatePolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\BudgetTemplate;
use App\Models\User;

class BudgetTemplatePolicy
{
    // Un utilisateur peut voir un template s'il lui appartient
    public function view(User $user, BudgetTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    // Un utilisateur peut modifier un template s'il lui appartient
    public function update(User $user, BudgetTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    // Un utilisateur peut supprimer un template s'il lui appartient
    public function delete(User $user, BudgetTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }
}
```

**Enregistrement (automatique avec Laravel 11) :**
Les policies sont découvertes automatiquement si elles suivent la convention de nommage :
- Modèle : `BudgetTemplate` → Policy : `BudgetTemplatePolicy`

**Utilisation dans le controller :**
```php
$this->authorize('view', $template);    // Lance une exception 403 si refusé
$this->authorize('update', $template);
$this->authorize('delete', $template);
```

---

### 5. Controller - Logique métier

**Fichier :** `backend/app/Http/Controllers/BudgetTemplateController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\BudgetTemplate;
use Illuminate\Http\Request;

class BudgetTemplateController extends Controller
{
    /**
     * GET /api/templates
     * Récupère tous les templates de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $templates = $request->user()                    // Utilisateur authentifié (via Sanctum)
            ->budgetTemplates()                          // Relation hasMany dans User.php
            ->with('categories.subcategories')           // Eager loading (évite N+1 queries)
            ->get();

        return response()->json($templates);
    }

    /**
     * POST /api/templates
     * Crée un nouveau template avec catégories et sous-catégories
     */
    public function store(Request $request)
    {
        // VALIDATION : Les données sont déjà en snake_case grâce au middleware
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'revenue_cents' => 'nullable|integer|min:0',
            'categories' => 'array',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.planned_amount_cents' => 'required|integer|min:0',
            'categories.*.sort_order' => 'integer|min:0',
            'categories.*.subcategories' => 'array',
            'categories.*.subcategories.*.name' => 'required|string|max:255',
            'categories.*.subcategories.*.planned_amount_cents' => 'required|integer|min:0',
            'categories.*.subcategories.*.sort_order' => 'integer|min:0',
        ]);

        // CRÉATION du template principal
        $template = $request->user()->budgetTemplates()->create([
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
            'revenue_cents' => $validated['revenue_cents'] ?? null,
        ]);

        // CRÉATION des catégories et sous-catégories
        if (isset($validated['categories'])) {
            foreach ($validated['categories'] as $catData) {
                $category = $template->categories()->create([
                    'name' => $catData['name'],
                    'planned_amount_cents' => $catData['planned_amount_cents'],
                    'sort_order' => $catData['sort_order'] ?? 0,
                ]);

                if (isset($catData['subcategories'])) {
                    foreach ($catData['subcategories'] as $subData) {
                        $category->subcategories()->create([
                            'name' => $subData['name'],
                            'planned_amount_cents' => $subData['planned_amount_cents'],
                            'sort_order' => $subData['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
        }

        // RÉPONSE : 201 Created avec les données complètes
        return response()->json(
            $template->load('categories.subcategories'),  // Recharge les relations
            201
        );
    }

    /**
     * GET /api/templates/{template}
     * Récupère un template spécifique
     */
    public function show(Request $request, BudgetTemplate $template)
    {
        // AUTORISATION : Vérifie que le template appartient à l'utilisateur
        $this->authorize('view', $template);

        return response()->json($template->load('categories.subcategories'));
    }

    /**
     * PUT /api/templates/{template}
     * Met à jour un template
     */
    public function update(Request $request, BudgetTemplate $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',          // 'sometimes' = optionnel
            'is_default' => 'sometimes|boolean',
            'revenue_cents' => 'sometimes|nullable|integer|min:0',
            'categories' => 'sometimes|array',
            'categories.*.id' => 'sometimes|exists:template_categories,id',
            'categories.*.name' => 'required_with:categories|string|max:255',
            'categories.*.planned_amount_cents' => 'required_with:categories|integer|min:0',
            // ... (validation complète des subcategories)
        ]);

        // Mise à jour du template
        $template->update([
            'name' => $validated['name'] ?? $template->name,
            'is_default' => $validated['is_default'] ?? $template->is_default,
            'revenue_cents' => $validated['revenue_cents'] ?? $template->revenue_cents,
        ]);

        // Gestion des catégories (création, update, suppression)
        if (isset($validated['categories'])) {
            $existingCategoryIds = [];

            foreach ($validated['categories'] as $index => $catData) {
                if (isset($catData['id'])) {
                    // MISE À JOUR d'une catégorie existante
                    $category = $template->categories()->findOrFail($catData['id']);
                    $category->update([
                        'name' => $catData['name'],
                        'planned_amount_cents' => $catData['planned_amount_cents'],
                        'sort_order' => $catData['sort_order'] ?? $index,
                    ]);
                    $existingCategoryIds[] = $category->id;
                } else {
                    // CRÉATION d'une nouvelle catégorie
                    $category = $template->categories()->create([
                        'name' => $catData['name'],
                        'planned_amount_cents' => $catData['planned_amount_cents'],
                        'sort_order' => $catData['sort_order'] ?? $index,
                    ]);
                    $existingCategoryIds[] = $category->id;
                }
            }

            // SUPPRESSION des catégories qui ne sont plus dans la liste
            $template->categories()->whereNotIn('id', $existingCategoryIds)->delete();
        }

        return response()->json($template->load('categories.subcategories'));
    }

    /**
     * DELETE /api/templates/{template}
     * Supprime un template
     */
    public function destroy(Request $request, BudgetTemplate $template)
    {
        $this->authorize('delete', $template);

        $template->delete();  // Les catégories/sous-catégories sont supprimées en cascade

        return response()->json(null, 204);  // 204 No Content
    }

    /**
     * POST /api/templates/{template}/set-default
     * Définit ce template comme défaut pour l'utilisateur
     */
    public function setDefault(Request $request, BudgetTemplate $template)
    {
        $this->authorize('update', $template);

        // Désactiver tous les templates de l'utilisateur
        $request->user()->budgetTemplates()->update(['is_default' => false]);

        // Activer celui-ci
        $template->update(['is_default' => true]);

        return response()->json($template);
    }
}
```

**Points clés :**
- `$request->user()` : Utilisateur authentifié via `auth:sanctum` middleware
- `$request->validate()` : Valide les données ET lance une exception 422 si invalide
- `$this->authorize()` : Vérifie les permissions via la Policy
- **Route Model Binding :** `BudgetTemplate $template` charge automatiquement le modèle par ID
- **Eager Loading :** `with('categories.subcategories')` évite les N+1 queries
- **Codes HTTP :** 200 (OK), 201 (Created), 204 (No Content), 403 (Forbidden), 422 (Validation Error)

---

### 6. Routes API

**Fichier :** `backend/routes/api.php`

```php
use App\Http\Controllers\BudgetTemplateController;

Route::middleware(['auth:sanctum'])->group(function () {
    // CRUD complet : index, store, show, update, destroy
    Route::apiResource('templates', BudgetTemplateController::class);

    // Route personnalisée : définir comme défaut
    Route::post('templates/{template}/set-default', [BudgetTemplateController::class, 'setDefault']);
});
```

**Routes générées par `apiResource` :**
| Méthode HTTP | URI | Action | Nom de la route |
|--------------|-----|--------|-----------------|
| GET | `/api/templates` | `index` | `templates.index` |
| POST | `/api/templates` | `store` | `templates.store` |
| GET | `/api/templates/{template}` | `show` | `templates.show` |
| PUT/PATCH | `/api/templates/{template}` | `update` | `templates.update` |
| DELETE | `/api/templates/{template}` | `destroy` | `templates.destroy` |

**Middlewares appliqués :**
1. `api` (groupe par défaut dans `RouteServiceProvider`)
   - Rate limiting (60 req/min)
   - JSON responses
2. `auth:sanctum` : Vérifie le token Bearer
3. `ConvertRequestToSnakeCase` : Convertit les clés JSON camelCase → snake_case
4. `ConvertResponseToCamelCase` : Convertit les clés JSON snake_case → camelCase

---

## Frontend - Vue 3 + TypeScript

### 1. Types TypeScript

**Fichier :** `frontend/src/types/index.ts`

```typescript
export interface BudgetTemplate {
  id: number;
  userId: number;
  name: string;
  isDefault: boolean;
  revenueCents?: number;
  categories?: TemplateCategory[];
  createdAt: string;
  updatedAt: string;
}

export interface TemplateCategory {
  id: number;
  budgetTemplateId: number;
  name: string;
  sortOrder: number;
  plannedAmountCents: number;
  subcategories?: TemplateSubcategory[];
  createdAt: string;
  updatedAt: string;
}

export interface TemplateSubcategory {
  id: number;
  templateCategoryId: number;
  name: string;
  plannedAmountCents: number;
  sortOrder: number;
  createdAt: string;
  updatedAt: string;
}
```

**Points clés :**
- **camelCase** partout (convention TypeScript/JavaScript)
- Les types correspondent aux colonnes de la base de données (après conversion par middleware)
- `?` indique les propriétés optionnelles
- Les dates sont des `string` (format ISO 8601 : `2024-01-08T10:30:00.000000Z`)

---

### 2. Schémas Valibot

**Fichier :** `frontend/src/schemas/template.ts`

```typescript
import * as v from "valibot";

// Schéma pour créer/modifier un template
export const budgetTemplateSchema = v.object({
  name: v.pipe(
    v.string(),
    v.minLength(1, "Le nom du template est requis"),
    v.maxLength(255, "Le nom est trop long")
  ),
  isDefault: v.optional(v.boolean()),
  revenueCents: v.optional(
    v.pipe(
      v.union([v.string(), v.number()]),    // Accepte string ou number
      v.transform(Number),                   // Convertit en number
      v.number(),
      v.minValue(0, "Le revenu ne peut pas être négatif")
    )
  ),
});

// Schéma pour une catégorie
export const templateCategorySchema = v.object({
  budgetTemplateId: v.optional(v.pipe(v.number(), v.minValue(1))),
  name: v.pipe(
    v.string(),
    v.minLength(1, "Le nom de la catégorie est requis"),
    v.maxLength(255, "Le nom est trop long")
  ),
  sortOrder: v.optional(v.pipe(v.number(), v.minValue(0))),
  plannedAmountCents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(0, "Le montant ne peut pas être négatif")
  ),
});

// Schéma pour une sous-catégorie
export const templateSubcategorySchema = v.object({
  templateCategoryId: v.optional(v.pipe(v.number(), v.minValue(1))),
  name: v.pipe(
    v.string(),
    v.minLength(1, "Le nom de la sous-catégorie est requis"),
    v.maxLength(255, "Le nom est trop long")
  ),
  plannedAmountCents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(0, "Le montant ne peut pas être négatif")
  ),
  sortOrder: v.optional(v.pipe(v.number(), v.minValue(0))),
});

// Types inférés automatiquement
export type BudgetTemplateInput = v.InferOutput<typeof budgetTemplateSchema>;
export type TemplateCategoryInput = v.InferOutput<typeof templateCategorySchema>;
export type TemplateSubcategoryInput = v.InferOutput<typeof templateSubcategorySchema>;
```

**Points clés :**
- `v.pipe()` : Enchaîne plusieurs validations
- `v.union([v.string(), v.number()])` : Accepte plusieurs types (utile pour les inputs HTML)
- `v.transform(Number)` : Convertit automatiquement la valeur
- `v.InferOutput<typeof schema>` : Génère automatiquement le type TypeScript

**Utilisation avec VeeValidate :**
```vue
<script setup lang="ts">
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/valibot';
import { budgetTemplateSchema } from '@/schemas/template';

const { handleSubmit, errors } = useForm({
  validationSchema: toTypedSchema(budgetTemplateSchema)
});

const onSubmit = handleSubmit((values) => {
  // values est de type BudgetTemplateInput
  console.log(values);
});
</script>
```

---

### 3. Client API

**Fichier :** `frontend/src/api/templates.ts`

```typescript
import apiClient from "./axios";
import type { BudgetTemplate } from "@/types";

export const templatesApi = {
  /**
   * GET /api/templates
   * Récupère tous les templates de l'utilisateur
   */
  async getAll(): Promise<BudgetTemplate[]> {
    const response = await apiClient.get("/templates");
    return response.data;
  },

  /**
   * GET /api/templates/{id}
   * Récupère un template spécifique
   */
  async getById(id: number): Promise<BudgetTemplate> {
    const response = await apiClient.get(`/templates/${id}`);
    return response.data;
  },

  /**
   * POST /api/templates
   * Crée un nouveau template
   */
  async create(data: {
    name: string;
    isDefault?: boolean;
    revenueCents?: number | null;
    categories?: Array<{
      name: string;
      plannedAmountCents: number;
      sortOrder?: number;
      subcategories?: Array<{
        name: string;
        plannedAmountCents: number;
        sortOrder?: number;
      }>;
    }>;
  }): Promise<BudgetTemplate> {
    const response = await apiClient.post("/templates", {
      name: data.name,
      isDefault: data.isDefault,
      revenueCents: data.revenueCents,
      categories: data.categories?.map((cat, catIndex) => ({
        name: cat.name,
        plannedAmountCents: cat.plannedAmountCents,
        sortOrder: cat.sortOrder ?? catIndex,
        subcategories: cat.subcategories?.map((sub, subIndex) => ({
          name: sub.name,
          plannedAmountCents: sub.plannedAmountCents,
          sortOrder: sub.sortOrder ?? subIndex,
        })),
      })),
    });
    return response.data;
  },

  /**
   * PUT /api/templates/{id}
   * Met à jour un template
   */
  async update(
    id: number,
    data: {
      name?: string;
      isDefault?: boolean;
      revenueCents?: number | null;
      categories?: Array<{
        id?: number;  // Si présent, c'est une mise à jour ; sinon, création
        name: string;
        plannedAmountCents: number;
        sortOrder?: number;
        subcategories?: Array<{
          id?: number;
          name: string;
          plannedAmountCents: number;
          sortOrder?: number;
        }>;
      }>;
    }
  ): Promise<BudgetTemplate> {
    const payload: any = {};

    if (data.name !== undefined) payload.name = data.name;
    if (data.isDefault !== undefined) payload.isDefault = data.isDefault;
    if (data.revenueCents !== undefined) payload.revenueCents = data.revenueCents;

    if (data.categories) {
      payload.categories = data.categories.map((cat, catIndex) => ({
        ...(cat.id && { id: cat.id }),  // Inclut l'ID seulement si présent
        name: cat.name,
        plannedAmountCents: cat.plannedAmountCents,
        sortOrder: cat.sortOrder ?? catIndex,
        subcategories: cat.subcategories?.map((sub, subIndex) => ({
          ...(sub.id && { id: sub.id }),
          name: sub.name,
          plannedAmountCents: sub.plannedAmountCents,
          sortOrder: sub.sortOrder ?? subIndex,
        })),
      }));
    }

    const response = await apiClient.put(`/templates/${id}`, payload);
    return response.data;
  },

  /**
   * DELETE /api/templates/{id}
   * Supprime un template
   */
  async delete(id: number): Promise<void> {
    await apiClient.delete(`/templates/${id}`);
  },

  /**
   * POST /api/templates/{id}/set-default
   * Définit ce template comme défaut
   */
  async setDefault(id: number): Promise<BudgetTemplate> {
    const response = await apiClient.post(`/templates/${id}/set-default`);
    return response.data;
  },
};
```

**Configuration axios :** `frontend/src/api/axios.ts`

```typescript
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Intercepteur REQUEST : Ajouter le token Bearer
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Intercepteur RESPONSE : Gérer les erreurs
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token invalide ou expiré
      const authStore = useAuthStore();
      authStore.logout();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default apiClient;
```

---

### 4. Store Pinia

**Fichier :** `frontend/src/stores/template.ts`

```typescript
import { defineStore } from "pinia";
import { ref } from "vue";
import { templatesApi } from "@/api/templates";
import type { BudgetTemplate } from "@/types";

export const useTemplateStore = defineStore("template", () => {
  // STATE
  const templates = ref<BudgetTemplate[]>([]);
  const currentTemplate = ref<BudgetTemplate | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // ACTIONS

  /**
   * Récupère tous les templates
   */
  async function fetchTemplates() {
    loading.value = true;
    error.value = null;
    try {
      templates.value = await templatesApi.getAll();
      return templates.value;
    } catch (err) {
      error.value = "Erreur lors du chargement des templates";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Récupère un template spécifique
   */
  async function fetchTemplate(id: number) {
    loading.value = true;
    error.value = null;
    try {
      currentTemplate.value = await templatesApi.getById(id);
      return currentTemplate.value;
    } catch (err) {
      error.value = "Erreur lors du chargement du template";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Crée un nouveau template
   */
  async function createTemplate(data: {
    name: string;
    isDefault?: boolean;
    revenueCents?: number | null;
    categories?: Array<{
      name: string;
      plannedAmountCents: number;
      sortOrder?: number;
      subcategories?: Array<{
        name: string;
        plannedAmountCents: number;
        sortOrder?: number;
      }>;
    }>;
  }) {
    loading.value = true;
    error.value = null;
    try {
      const template = await templatesApi.create(data);
      templates.value.push(template);  // Ajoute à la liste locale
      return template;
    } catch (err) {
      error.value = "Erreur lors de la création du template";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Met à jour un template
   */
  async function updateTemplate(id: number, data: any) {
    loading.value = true;
    error.value = null;
    try {
      const template = await templatesApi.update(id, data);

      // Met à jour dans la liste locale
      const index = templates.value.findIndex((t) => t.id === id);
      if (index !== -1) {
        templates.value[index] = template;
      }

      // Met à jour le template courant si c'est le même
      if (currentTemplate.value?.id === id) {
        currentTemplate.value = template;
      }

      return template;
    } catch (err) {
      error.value = "Erreur lors de la mise à jour du template";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Supprime un template
   */
  async function deleteTemplate(id: number) {
    loading.value = true;
    error.value = null;
    try {
      await templatesApi.delete(id);

      // Supprime de la liste locale
      templates.value = templates.value.filter((t) => t.id !== id);

      // Reset le template courant si c'était celui-ci
      if (currentTemplate.value?.id === id) {
        currentTemplate.value = null;
      }
    } catch (err) {
      error.value = "Erreur lors de la suppression du template";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Définit un template comme défaut
   */
  async function setDefaultTemplate(id: number) {
    loading.value = true;
    error.value = null;
    try {
      const template = await templatesApi.setDefault(id);

      // Met à jour tous les templates : seul celui-ci est défaut
      templates.value = templates.value.map((t) => ({
        ...t,
        isDefault: t.id === id,
      }));

      return template;
    } catch (err) {
      error.value = "Erreur lors de la définition du template par défaut";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // GETTERS (optionnel)
  const defaultTemplate = computed(() =>
    templates.value.find((t) => t.isDefault)
  );

  return {
    // State
    templates,
    currentTemplate,
    loading,
    error,
    // Actions
    fetchTemplates,
    fetchTemplate,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    setDefaultTemplate,
    // Getters
    defaultTemplate,
  };
});
```

**Points clés :**
- **Composition API style** : `defineStore("name", () => { ... })`
- `ref()` pour le state réactif
- `computed()` pour les getters dérivés
- Gestion centralisée de `loading` et `error`
- Synchronisation automatique entre API et state local

---

### 5. Composant Vue

**Exemple :** `frontend/src/pages/TemplatesPage.vue`

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { useTemplateStore } from '@/stores/template';
import { useToast } from '@/composables/useToast';

const templateStore = useTemplateStore();
const { showToast } = useToast();

// Charger les templates au montage du composant
onMounted(async () => {
  try {
    await templateStore.fetchTemplates();
  } catch (error) {
    showToast('Impossible de charger les templates', 'error');
  }
});

// Créer un template
async function createNewTemplate() {
  try {
    await templateStore.createTemplate({
      name: 'Mon nouveau template',
      isDefault: false,
      revenueCents: 250000,  // 2500€
      categories: [
        {
          name: 'Logement',
          plannedAmountCents: 80000,  // 800€
          subcategories: [
            { name: 'Loyer', plannedAmountCents: 60000 },
            { name: 'Électricité', plannedAmountCents: 20000 },
          ],
        },
      ],
    });
    showToast('Template créé avec succès', 'success');
  } catch (error) {
    showToast('Erreur lors de la création', 'error');
  }
}

// Supprimer un template
async function deleteTemplate(id: number) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce template ?')) return;

  try {
    await templateStore.deleteTemplate(id);
    showToast('Template supprimé', 'success');
  } catch (error) {
    showToast('Erreur lors de la suppression', 'error');
  }
}

// Définir comme défaut
async function setAsDefault(id: number) {
  try {
    await templateStore.setDefaultTemplate(id);
    showToast('Template défini par défaut', 'success');
  } catch (error) {
    showToast('Erreur', 'error');
  }
}

// Convertir centimes → euros pour affichage
function centsToEuros(cents: number): string {
  return (cents / 100).toFixed(2) + ' €';
}
</script>

<template>
  <div class="templates-page">
    <h1>Mes Templates de Budget</h1>

    <!-- Loading state -->
    <div v-if="templateStore.loading" class="loading">
      Chargement...
    </div>

    <!-- Error state -->
    <div v-else-if="templateStore.error" class="error">
      {{ templateStore.error }}
    </div>

    <!-- Templates list -->
    <div v-else class="templates-list">
      <button @click="createNewTemplate" class="btn-primary">
        Nouveau template
      </button>

      <div
        v-for="template in templateStore.templates"
        :key="template.id"
        class="template-card"
      >
        <h2>{{ template.name }}</h2>

        <span v-if="template.isDefault" class="badge">Par défaut</span>

        <p v-if="template.revenueCents">
          Revenus : {{ centsToEuros(template.revenueCents) }}
        </p>

        <div class="actions">
          <button @click="setAsDefault(template.id)" v-if="!template.isDefault">
            Définir par défaut
          </button>
          <button @click="deleteTemplate(template.id)" class="btn-danger">
            Supprimer
          </button>
        </div>

        <!-- Catégories -->
        <div v-if="template.categories" class="categories">
          <div
            v-for="category in template.categories"
            :key="category.id"
            class="category"
          >
            <h3>{{ category.name }}</h3>
            <p>{{ centsToEuros(category.plannedAmountCents) }}</p>

            <!-- Sous-catégories -->
            <ul v-if="category.subcategories">
              <li v-for="sub in category.subcategories" :key="sub.id">
                {{ sub.name }} : {{ centsToEuros(sub.plannedAmountCents) }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.templates-page {
  padding: 2rem;
}

.template-card {
  border: 1px solid #ddd;
  padding: 1rem;
  margin: 1rem 0;
  border-radius: 8px;
}

.badge {
  background: #10b981;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
}

.btn-danger {
  background: #ef4444;
  color: white;
}
</style>
```

---

## Flow complet d'une requête

Voici le parcours complet d'une requête `POST /api/templates` pour créer un template :

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. FRONTEND - Composant Vue                                     │
└─────────────────────────────────────────────────────────────────┘
  ↓ User clique sur "Créer"
  ↓ Validation Valibot (côté client)

┌─────────────────────────────────────────────────────────────────┐
│ 2. FRONTEND - Store Pinia                                       │
│    templateStore.createTemplate({ name: "Budget", ... })        │
└─────────────────────────────────────────────────────────────────┘
  ↓ Appelle l'API client

┌─────────────────────────────────────────────────────────────────┐
│ 3. FRONTEND - API Client                                        │
│    templatesApi.create({ name: "Budget", isDefault: true })    │
│    → JSON camelCase                                             │
└─────────────────────────────────────────────────────────────────┘
  ↓ axios.post() avec intercepteur (ajoute Bearer token)

┌─────────────────────────────────────────────────────────────────┐
│ 4. HTTP REQUEST                                                 │
│    POST http://localhost:8080/api/templates                     │
│    Authorization: Bearer 23|xxxxx                               │
│    Body: { "name": "Budget", "isDefault": true }                │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 5. BACKEND - Middleware ConvertRequestToSnakeCase              │
│    { "name": "Budget", "isDefault": true }                      │
│    → { "name": "Budget", "is_default": true }                   │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 6. BACKEND - Middleware auth:sanctum                            │
│    Vérifie le token Bearer → charge $request->user()            │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 7. BACKEND - Route                                              │
│    Route::post('templates', [BudgetTemplateController, 'store'])│
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 8. BACKEND - Controller::store()                                │
│    $request->validate([...])  ← Validation Laravel              │
│    $template = $user->budgetTemplates()->create([...])          │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 9. BACKEND - Model::boot() hook                                 │
│    Si is_default = true, désactive les autres templates         │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 10. DATABASE - MySQL                                            │
│     INSERT INTO budget_templates (user_id, name, is_default)    │
│     VALUES (1, 'Budget', 1)                                     │
└─────────────────────────────────────────────────────────────────┘
  ↓ Eloquent retourne le modèle créé

┌─────────────────────────────────────────────────────────────────┐
│ 11. BACKEND - Controller                                        │
│     return response()->json($template, 201);                    │
│     → { "id": 5, "user_id": 1, "name": "Budget", ... }          │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 12. BACKEND - Middleware ConvertResponseToCamelCase            │
│     { "user_id": 1, "is_default": true }                        │
│     → { "userId": 1, "isDefault": true }                        │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 13. HTTP RESPONSE                                               │
│     201 Created                                                 │
│     Body: { "id": 5, "userId": 1, "name": "Budget", ... }       │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 14. FRONTEND - API Client                                       │
│     return response.data;  → BudgetTemplate object              │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 15. FRONTEND - Store Pinia                                      │
│     templates.value.push(template);  ← Mise à jour state        │
└─────────────────────────────────────────────────────────────────┘
  ↓

┌─────────────────────────────────────────────────────────────────┐
│ 16. FRONTEND - Composant Vue                                    │
│     La liste se met à jour automatiquement (réactivité)         │
└─────────────────────────────────────────────────────────────────┘
```

---

## Conversion automatique camelCase/snake_case

### Middlewares de conversion

**Fichier :** `backend/app/Http/Middleware/ConvertRequestToSnakeCase.php`

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ConvertRequestToSnakeCase
{
    public function handle(Request $request, Closure $next)
    {
        // Convertir les clés camelCase → snake_case
        $request->replace($this->convertKeysToSnakeCase($request->all()));

        return $next($request);
    }

    private function convertKeysToSnakeCase(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            // Convertir la clé
            $snakeKey = $this->toSnakeCase($key);

            // Si la valeur est un tableau, convertir récursivement
            $result[$snakeKey] = is_array($value)
                ? $this->convertKeysToSnakeCase($value)
                : $value;
        }
        return $result;
    }

    private function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }
}
```

**Fichier :** `backend/app/Http/Middleware/ConvertResponseToCamelCase.php`

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

class ConvertResponseToCamelCase
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setData($this->convertKeysToCamelCase(
                json_decode($response->content(), true)
            ));
        }

        return $response;
    }

    private function convertKeysToCamelCase($data)
    {
        if (!is_array($data)) return $data;

        $result = [];
        foreach ($data as $key => $value) {
            $camelKey = $this->toCamelCase($key);
            $result[$camelKey] = is_array($value)
                ? $this->convertKeysToCamelCase($value)
                : $value;
        }
        return $result;
    }

    private function toCamelCase(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }
}
```

**Enregistrement :** `backend/bootstrap/app.php`

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\ConvertRequestToSnakeCase::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\ConvertResponseToCamelCase::class,
        ]);
    })
    ->create();
```

### Exemples de conversion

**Frontend → Backend (Request) :**
```json
// Frontend envoie (camelCase)
{
  "name": "Budget familial",
  "isDefault": true,
  "revenueCents": 250000,
  "categories": [
    {
      "name": "Logement",
      "plannedAmountCents": 80000
    }
  ]
}

// Backend reçoit (snake_case)
{
  "name": "Budget familial",
  "is_default": true,
  "revenue_cents": 250000,
  "categories": [
    {
      "name": "Logement",
      "planned_amount_cents": 80000
    }
  ]
}
```

**Backend → Frontend (Response) :**
```json
// Backend retourne (snake_case)
{
  "id": 5,
  "user_id": 1,
  "name": "Budget familial",
  "is_default": true,
  "revenue_cents": 250000,
  "created_at": "2024-01-08T10:30:00.000000Z",
  "updated_at": "2024-01-08T10:30:00.000000Z"
}

// Frontend reçoit (camelCase)
{
  "id": 5,
  "userId": 1,
  "name": "Budget familial",
  "isDefault": true,
  "revenueCents": 250000,
  "createdAt": "2024-01-08T10:30:00.000000Z",
  "updatedAt": "2024-01-08T10:30:00.000000Z"
}
```

---

## Checklist complète

Utilisez cette checklist pour implémenter une nouvelle feature :

### Backend

- [ ] **Migration**
  - [ ] Créer la migration : `make artisan CMD="make:migration create_things_table"`
  - [ ] Définir la structure de la table (colonnes, types, index, FK)
  - [ ] Exécuter la migration : `make migrate`

- [ ] **Modèle**
  - [ ] Créer le modèle : `make artisan CMD="make:model Thing"`
  - [ ] Définir `$fillable` (colonnes assignables)
  - [ ] Définir `$casts` (conversions de types)
  - [ ] Ajouter les relations (`belongsTo`, `hasMany`, etc.)
  - [ ] Ajouter les hooks `boot()` si nécessaire

- [ ] **Factory**
  - [ ] Créer la factory : `make artisan CMD="make:factory ThingFactory"`
  - [ ] Définir les données de test avec Faker
  - [ ] Ajouter des states personnalisés si nécessaire

- [ ] **Policy**
  - [ ] Créer la policy : `make artisan CMD="make:policy ThingPolicy --model=Thing"`
  - [ ] Définir les méthodes d'autorisation (`view`, `update`, `delete`)

- [ ] **Controller**
  - [ ] Créer le controller : `make artisan CMD="make:controller ThingController --api"`
  - [ ] Implémenter les méthodes CRUD (`index`, `store`, `show`, `update`, `destroy`)
  - [ ] Ajouter la validation avec `$request->validate()`
  - [ ] Ajouter les autorisations avec `$this->authorize()`
  - [ ] Utiliser **snake_case** pour toutes les clés

- [ ] **Routes**
  - [ ] Ajouter `Route::apiResource('things', ThingController::class)` dans `routes/api.php`
  - [ ] Ajouter des routes personnalisées si nécessaire
  - [ ] Protéger avec `auth:sanctum` middleware

- [ ] **Tests**
  - [ ] Créer le test : `make artisan CMD="make:test ThingTest"`
  - [ ] Tester les opérations CRUD
  - [ ] Tester les autorisations
  - [ ] Tester la validation

### Frontend

- [ ] **Types**
  - [ ] Définir les interfaces TypeScript dans `frontend/src/types/index.ts`
  - [ ] Utiliser **camelCase** pour toutes les propriétés
  - [ ] Marquer les propriétés optionnelles avec `?`

- [ ] **Schémas Valibot**
  - [ ] Créer le fichier schema : `frontend/src/schemas/thing.ts`
  - [ ] Définir les schémas de validation avec Valibot
  - [ ] Exporter les types inférés avec `v.InferOutput<typeof schema>`

- [ ] **API Client**
  - [ ] Créer le fichier API : `frontend/src/api/things.ts`
  - [ ] Implémenter les méthodes CRUD (`getAll`, `getById`, `create`, `update`, `delete`)
  - [ ] Utiliser **camelCase** pour toutes les propriétés
  - [ ] Typer les paramètres et retours avec les interfaces TypeScript

- [ ] **Store Pinia**
  - [ ] Créer le store : `frontend/src/stores/thing.ts`
  - [ ] Définir le state (`ref()`)
  - [ ] Implémenter les actions (appels API)
  - [ ] Gérer `loading` et `error`
  - [ ] Synchroniser le state local avec les réponses API

- [ ] **Composant Vue**
  - [ ] Créer la page : `frontend/src/pages/ThingsPage.vue`
  - [ ] Utiliser le store pour accéder aux données
  - [ ] Gérer les états loading/error
  - [ ] Implémenter les actions (créer, modifier, supprimer)
  - [ ] Ajouter la validation avec VeeValidate + Valibot

- [ ] **Route**
  - [ ] Ajouter la route dans `frontend/src/router/index.ts`
  - [ ] Protéger avec `requiresAuth` si nécessaire
  - [ ] Ajouter le lien dans la navigation

### Vérifications finales

- [ ] **Conversion camelCase/snake_case**
  - [ ] Vérifier que les middlewares sont enregistrés
  - [ ] Tester une requête complète (frontend → backend → frontend)

- [ ] **Sécurité**
  - [ ] Vérifier que les routes sont protégées par `auth:sanctum`
  - [ ] Vérifier que les policies sont appliquées
  - [ ] Tester l'accès non autorisé (doit retourner 403)

- [ ] **Performance**
  - [ ] Utiliser `with()` pour éviter les N+1 queries
  - [ ] Ajouter des index sur les colonnes fréquemment filtrées

- [ ] **Documentation**
  - [ ] Commenter le code complexe
  - [ ] Mettre à jour CLAUDE.md si nécessaire

---

## Conclusion

Ce guide couvre **l'intégralité du cycle de développement** d'une feature dans Budget Manager :

1. **Backend Laravel** : Migration → Modèle → Factory → Policy → Controller → Routes
2. **Frontend Vue** : Types → Schémas → API → Store → Composant
3. **Communication** : Middlewares de conversion automatique camelCase/snake_case

**Règle d'or :**
- Backend = **snake_case** (PHP/Laravel/MySQL)
- Frontend = **camelCase** (TypeScript/JavaScript)
- Les middlewares gèrent la conversion automatiquement

En suivant cette structure et cette checklist, vous pouvez implémenter n'importe quelle feature de manière cohérente et maintenable ! 🚀