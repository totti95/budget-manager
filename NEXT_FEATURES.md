# NEXT_FEATURES.md - Feuille de route des fonctionnalités à implémenter

Ce document liste les fonctionnalités prioritaires à développer pour Budget Manager, avec spécifications complètes backend + frontend.

---

## 🎯 Feature 4 : Alertes de Dépassement Budgétaire

### Description
Système de notifications pour alerter l'utilisateur lorsqu'une sous-catégorie ou catégorie dépasse le budget prévu.

### Spécifications Backend

#### 1. Migration `create_notifications_table`
```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('type'); // 'budget_exceeded', 'savings_goal_reached', etc.
    $table->string('title');
    $table->text('message');
    $table->json('data')->nullable(); // { budgetId, subcategoryId, amount, etc. }
    $table->boolean('read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'read']);
});
```

#### 2. Migration `create_notification_settings_table`
```php
Schema::create('notification_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->boolean('budget_exceeded_enabled')->default(true);
    $table->integer('budget_exceeded_threshold_percent')->default(100); // Alert at 100%
    $table->boolean('savings_goal_enabled')->default(true);
    $table->timestamps();

    $table->unique('user_id');
});
```

#### 3. Model `Notification`
```php
protected $fillable = [
    'user_id',
    'type',
    'title',
    'message',
    'data',
    'read',
    'read_at',
];

protected $casts = [
    'data' => 'array',
    'read' => 'boolean',
    'read_at' => 'datetime',
];

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

#### 4. Model `NotificationSetting`
```php
protected $fillable = [
    'user_id',
    'budget_exceeded_enabled',
    'budget_exceeded_threshold_percent',
    'savings_goal_enabled',
];

protected $casts = [
    'budget_exceeded_enabled' => 'boolean',
    'budget_exceeded_threshold_percent' => 'integer',
    'savings_goal_enabled' => 'boolean',
];

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

#### 5. `NotificationController`
Endpoints :
- `GET /api/notifications` - Liste des notifications (avec pagination)
- `GET /api/notifications/unread-count` - Nombre de non-lues
- `PUT /api/notifications/{id}/mark-read` - Marquer comme lu
- `PUT /api/notifications/mark-all-read` - Tout marquer comme lu
- `DELETE /api/notifications/{id}` - Supprimer
- `DELETE /api/notifications/clear-all` - Tout supprimer

#### 6. `NotificationSettingController`
Endpoints :
- `GET /api/notification-settings` - Récupérer paramètres
- `PUT /api/notification-settings` - Mettre à jour paramètres

#### 7. Service `NotificationService`
Méthodes :
```php
public function checkBudgetExceeded(Expense $expense): void
{
    // 1. Récupérer settings utilisateur
    // 2. Si alertes activées, vérifier dépassement
    // 3. Calculer % dépensé vs prévu pour subcategory
    // 4. Si >= threshold, créer notification
    // 5. Vérifier qu'une alerte similaire n'existe pas déjà (éviter spam)
}

public function createNotification(User $user, string $type, string $title, string $message, array $data = []): Notification
{
    // Créer et retourner notification
}
```

#### 8. Event Listener
Dans `ExpenseController::store()` et `ExpenseController::update()` :
```php
// Après création/mise à jour expense
event(new ExpenseCreated($expense));
// OU
NotificationService::checkBudgetExceeded($expense);
```

#### 9. Routes à ajouter dans `routes/api.php`
```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('notifications/{notification}/mark-read', [NotificationController::class, 'markRead']);
    Route::put('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::delete('notifications/clear-all', [NotificationController::class, 'clearAll']);

    Route::get('notification-settings', [NotificationSettingController::class, 'show']);
    Route::put('notification-settings', [NotificationSettingController::class, 'update']);
});
```

### Spécifications Frontend

#### 1. Types TypeScript (`src/types/index.ts`)
```typescript
export interface Notification {
  id: number;
  userId: number;
  type: 'budget_exceeded' | 'savings_goal_reached';
  title: string;
  message: string;
  data: Record<string, any> | null;
  read: boolean;
  readAt: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface NotificationSettings {
  id: number;
  userId: number;
  budgetExceededEnabled: boolean;
  budgetExceededThresholdPercent: number;
  savingsGoalEnabled: boolean;
  createdAt: string;
  updatedAt: string;
}
```

#### 2. API Client (`src/api/notifications.ts`)
```typescript
export const notificationsApi = {
  async list(page?: number): Promise<PaginatedResponse<Notification>>,
  async unreadCount(): Promise<number>,
  async markRead(id: number): Promise<Notification>,
  async markAllRead(): Promise<void>,
  async delete(id: number): Promise<void>,
  async clearAll(): Promise<void>,
}

export const notificationSettingsApi = {
  async get(): Promise<NotificationSettings>,
  async update(data: UpdateNotificationSettingsData): Promise<NotificationSettings>,
}
```

#### 3. Store Pinia (`src/stores/notifications.ts`)
```typescript
export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<Notification[]>([]);
  const unreadCount = ref(0);
  const settings = ref<NotificationSettings | null>(null);
  const loading = ref(false);

  async function fetchNotifications();
  async function fetchUnreadCount();
  async function markRead(id: number);
  async function markAllRead();
  async function deleteNotification(id: number);
  async function clearAll();
  async function fetchSettings();
  async function updateSettings(data: UpdateNotificationSettingsData);

  // Poll toutes les 30 secondes pour nouvelles notifications
  function startPolling();
  function stopPolling();

  return { ... };
});
```

#### 4. Composant `NotificationBell.vue`
Composant dans NavBar.vue avec :
- Icône cloche
- Badge avec nombre de notifications non lues
- Dropdown au clic avec liste notifications
- Bouton "Tout marquer comme lu"
- Lien "Voir toutes les notifications"

#### 5. Page `NotificationsPage.vue`
- Liste complète des notifications (paginée)
- Filtre lu/non-lu
- Actions : marquer lu, supprimer
- Bouton "Tout effacer"

#### 6. Page `NotificationSettingsPage.vue` (ou section dans ProfilePage)
- Toggle "Activer alertes dépassement budget"
- Slider "Seuil d'alerte" (50%, 75%, 90%, 100%, 110%)
- Toggle "Activer alertes objectif épargne"

#### 7. Composant `NotificationItem.vue`
- Icône selon type (⚠️ dépassement, ✅ objectif atteint)
- Titre et message
- Date relative (il y a 2h, hier, etc.)
- Actions : marquer lu, supprimer
- Clic pour voir détails (navigation vers budget concerné)

### Tests recommandés
1. Créer dépense qui dépasse budget → notification créée
2. Marquer notification comme lue
3. Badge mis à jour en temps réel
4. Désactiver alertes dans settings → pas de nouvelles notifications
5. Changer seuil à 90% → alerte à 90% au lieu de 100%

---

## 🔄 Feature 5 : Dépenses Récurrentes Automatiques

### Description
Permettre la création automatique de dépenses récurrentes (loyer, abonnements, salaire) chaque mois lors de la génération du budget.

### Spécifications Backend

#### 1. Migration `create_recurring_expenses_table`
```php
Schema::create('recurring_expenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('template_subcategory_id')->nullable()->constrained()->onDelete('set null');
    $table->string('label');
    $table->bigInteger('amount_cents');
    $table->enum('frequency', ['monthly', 'weekly', 'yearly']);
    $table->integer('day_of_month')->nullable(); // 1-31 pour monthly, null pour weekly
    $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable(); // Pour weekly
    $table->integer('month_of_year')->nullable(); // 1-12 pour yearly
    $table->boolean('auto_create')->default(true);
    $table->boolean('is_active')->default(true);
    $table->date('start_date');
    $table->date('end_date')->nullable(); // Optionnel pour limiter dans le temps
    $table->string('payment_method')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'is_active']);
});
```

#### 2. Model `RecurringExpense`
```php
protected $fillable = [
    'user_id',
    'template_subcategory_id',
    'label',
    'amount_cents',
    'frequency',
    'day_of_month',
    'day_of_week',
    'month_of_year',
    'auto_create',
    'is_active',
    'start_date',
    'end_date',
    'payment_method',
    'notes',
];

protected $casts = [
    'amount_cents' => 'integer',
    'day_of_month' => 'integer',
    'month_of_year' => 'integer',
    'auto_create' => 'boolean',
    'is_active' => 'boolean',
    'start_date' => 'date',
    'end_date' => 'date',
];

public function user(): BelongsTo;
public function templateSubcategory(): BelongsTo;

// Vérifier si dépense doit être créée pour un mois donné
public function shouldCreateForMonth(Carbon $month): bool;
```

#### 3. `RecurringExpenseController`
Endpoints :
- `GET /api/recurring-expenses` - Liste
- `POST /api/recurring-expenses` - Créer
- `GET /api/recurring-expenses/{id}` - Détail
- `PUT /api/recurring-expenses/{id}` - Modifier
- `DELETE /api/recurring-expenses/{id}` - Supprimer
- `PUT /api/recurring-expenses/{id}/toggle-active` - Activer/Désactiver

#### 4. Service `RecurringExpenseService`
```php
public function createRecurringExpensesForBudget(Budget $budget): int
{
    // 1. Récupérer toutes les recurring expenses actives de l'utilisateur
    // 2. Pour chaque recurring expense :
    //    - Vérifier si shouldCreateForMonth(budget->month)
    //    - Trouver la budget_subcategory correspondante (via template_subcategory_id)
    //    - Créer l'expense avec la date appropriée
    // 3. Retourner nombre de dépenses créées
}
```

#### 5. Modification `BudgetController::generate()`
Après création du budget et avant le return :
```php
$createdCount = RecurringExpenseService::createRecurringExpensesForBudget($budget);
// Optionnel : retourner info dans response
```

#### 6. Commande Artisan `CreateRecurringExpenses`
```bash
php artisan make:command CreateRecurringExpenses
```

```php
// Pour créer les dépenses récurrentes manuellement si besoin
php artisan expenses:create-recurring --month=2025-01
```

Cette commande :
- Accepte `--month` en option (sinon mois courant)
- Trouve ou crée le budget du mois
- Appelle `RecurringExpenseService::createRecurringExpensesForBudget()`

#### 7. Routes
```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('recurring-expenses', [RecurringExpenseController::class, 'index']);
    Route::post('recurring-expenses', [RecurringExpenseController::class, 'store']);
    Route::get('recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'show']);
    Route::put('recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'update']);
    Route::delete('recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'destroy']);
    Route::put('recurring-expenses/{recurringExpense}/toggle-active', [RecurringExpenseController::class, 'toggleActive']);
});
```

### Spécifications Frontend

#### 1. Types (`src/types/index.ts`)
```typescript
export type RecurringFrequency = 'monthly' | 'weekly' | 'yearly';
export type DayOfWeek = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday';

export interface RecurringExpense {
  id: number;
  userId: number;
  templateSubcategoryId: number | null;
  label: string;
  amountCents: number;
  frequency: RecurringFrequency;
  dayOfMonth: number | null;
  dayOfWeek: DayOfWeek | null;
  monthOfYear: number | null;
  autoCreate: boolean;
  isActive: boolean;
  startDate: string;
  endDate: string | null;
  paymentMethod: string | null;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}
```

#### 2. API Client (`src/api/recurringExpenses.ts`)
```typescript
export const recurringExpensesApi = {
  async list(): Promise<RecurringExpense[]>,
  async create(data: CreateRecurringExpenseData): Promise<RecurringExpense>,
  async get(id: number): Promise<RecurringExpense>,
  async update(id: number, data: UpdateRecurringExpenseData): Promise<RecurringExpense>,
  async delete(id: number): Promise<void>,
  async toggleActive(id: number): Promise<RecurringExpense>,
}
```

#### 3. Store (`src/stores/recurringExpenses.ts`)
```typescript
export const useRecurringExpensesStore = defineStore('recurringExpenses', () => {
  const expenses = ref<RecurringExpense[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchExpenses();
  async function createExpense(data: CreateRecurringExpenseData);
  async function updateExpense(id: number, data: UpdateRecurringExpenseData);
  async function deleteExpense(id: number);
  async function toggleActive(id: number);

  return { ... };
});
```

#### 4. Page `RecurringExpensesPage.vue`
- Liste des dépenses récurrentes avec filtres (actives/inactives)
- Tableau avec colonnes :
  - Libellé
  - Montant
  - Fréquence (icône + texte : "Tous les mois le 1er", "Tous les lundis", "Chaque année en janvier")
  - Sous-catégorie liée
  - Statut (actif/inactif avec toggle)
  - Actions (modifier, supprimer)
- Bouton "Ajouter une dépense récurrente"

#### 5. Composant `RecurringExpenseFormModal.vue`
Formulaire avec :
- Libellé (texte)
- Montant (€)
- Fréquence (select : Mensuelle, Hebdomadaire, Annuelle)
- **Si mensuelle** : Jour du mois (1-31)
- **Si hebdomadaire** : Jour de la semaine (select)
- **Si annuelle** : Mois (select) + jour du mois
- Sous-catégorie template (select optionnel)
- Date début (date picker)
- Date fin (date picker optionnel)
- Création automatique (checkbox)
- Actif (checkbox)
- Méthode de paiement (select optionnel)
- Notes (textarea optionnel)

#### 6. Composant `RecurringExpenseCard.vue`
Carte affichant :
- Icône selon fréquence (🔄 mensuelle, 📅 hebdomadaire, 📆 annuelle)
- Libellé et montant
- Description fréquence lisible
- Badge actif/inactif
- Actions rapides

#### 7. Route
```typescript
{
  path: '/recurring-expenses',
  name: 'recurring-expenses',
  component: () => import('@/pages/RecurringExpensesPage.vue'),
  meta: { requiresAuth: true },
}
```

#### 8. Lien dans NavBar
Ajouter lien "Dépenses récurrentes" dans menu

### Tests recommandés
1. Créer dépense récurrente mensuelle le 1er du mois
2. Générer budget → vérifier dépense auto-créée
3. Désactiver dépense → générer nouveau budget → pas de création
4. Créer dépense hebdomadaire tous les lundis
5. Créer dépense annuelle (impôts en avril)
6. Modifier montant → futurs budgets utilisent nouveau montant

---

## 📊 Feature 6 : Comparaison Multi-Budgets

### Description
Comparer 2 ou 3 budgets mensuels côte-à-côte pour analyser l'évolution des dépenses.

### Spécifications Backend

#### 1. `BudgetController` - Nouvel endpoint
```php
public function compare(Request $request)
{
    $validated = $request->validate([
        'months' => 'required|array|min:2|max:3',
        'months.*' => 'required|date_format:Y-m',
    ]);

    $user = $request->user();
    $budgets = [];

    foreach ($validated['months'] as $month) {
        $monthDate = Carbon::parse($month . '-01');
        $budget = $user->budgets()
            ->where('month', $monthDate)
            ->with(['categories.subcategories.expenses'])
            ->first();

        if ($budget) {
            // Calculer stats pour chaque budget
            $budget->stats = [
                'totalPlanned' => ...,
                'totalActual' => ...,
                'variance' => ...,
                'byCategory' => [...],
            ];
            $budgets[] = $budget;
        }
    }

    return response()->json([
        'budgets' => $budgets,
        'comparison' => [
            'evolution' => [...], // Évolution % entre mois
        ],
    ]);
}
```

#### 2. Route
```php
Route::get('budgets/compare', [BudgetController::class, 'compare']);
```

### Spécifications Frontend

#### 1. Types (`src/types/index.ts`)
```typescript
export interface BudgetComparison {
  budgets: Budget[];
  comparison: {
    evolution: Array<{
      categoryName: string;
      values: number[];
      evolution: number; // % d'évolution
    }>;
  };
}
```

#### 2. API Client (`src/api/budgets.ts`)
Ajouter :
```typescript
async compare(months: string[]): Promise<BudgetComparison>
```

#### 3. Page `BudgetComparisonPage.vue`
- Sélecteur de 2-3 mois (multi-select)
- Bouton "Comparer"
- **Tableau comparatif** :
  - Colonnes : Catégorie | Mois 1 | Mois 2 | Mois 3 (optionnel) | Évolution
  - Lignes : Chaque catégorie + total
  - Cellules : Prévu / Réel avec différence
  - Colonne évolution : % et flèche (↗️ ↘️ →)
- **Graphiques** :
  - Bar chart : Dépenses par catégorie pour chaque mois
  - Line chart : Évolution du total sur les mois sélectionnés

#### 4. Composant `BudgetComparisonTable.vue`
Tableau responsive avec :
- En-têtes collantes (sticky)
- Tri par colonne
- Couleurs conditionnelles (rouge si dépassement)
- Export CSV

#### 5. Composant `BudgetComparisonChart.vue`
Chart.js avec :
- Type : Grouped bar chart
- X: Catégories
- Y: Montants
- Groupes : Mois comparés
- Légende

#### 6. Route
```typescript
{
  path: '/budgets/compare',
  name: 'budget-compare',
  component: () => import('@/pages/BudgetComparisonPage.vue'),
  meta: { requiresAuth: true },
}
```

### Tests recommandés
1. Comparer 2 budgets consécutifs
2. Comparer 3 budgets (janvier, mars, mai)
3. Vérifier calculs d'évolution %
4. Exporter en CSV
5. Trier par catégorie, par évolution

---

## 📄 Feature 7 : Export PDF de Budget

### Description
Générer un PDF récapitulatif professionnel d'un budget mensuel.

### Spécifications Backend

#### 1. Installation package PDF
```bash
composer require barryvdh/laravel-dompdf
```

#### 2. `BudgetController` - Endpoint export
```php
use Barryvdh\DomPDF\Facade\Pdf;

public function exportPdf(Request $request, Budget $budget)
{
    $this->authorize('view', $budget);

    $budget->load([
        'categories.subcategories.expenses',
        'expenses',
    ]);

    // Calculer stats
    $stats = [
        'totalPlanned' => ...,
        'totalActual' => ...,
        'variance' => ...,
        'byCategory' => [...],
        'topExpenses' => $budget->expenses()->orderBy('amount_cents', 'desc')->limit(10)->get(),
    ];

    $pdf = Pdf::loadView('budgets.pdf', [
        'budget' => $budget,
        'stats' => $stats,
        'user' => $request->user(),
        'generatedAt' => now(),
    ]);

    return $pdf->download('budget-' . $budget->month->format('Y-m') . '.pdf');
}
```

#### 3. Vue Blade `resources/views/budgets/pdf.blade.php`
Template HTML/CSS pour PDF avec :
- En-tête avec logo et infos utilisateur
- Date de génération
- Titre : "Budget [Mois Année]"
- Section récapitulatif (cartes stats)
- Tableau par catégorie avec sous-catégories
- Top 10 des dépenses
- Pied de page avec pagination

#### 4. Route
```php
Route::get('budgets/{budget}/export-pdf', [BudgetController::class, 'exportPdf']);
```

### Spécifications Frontend

#### 1. API Client (`src/api/budgets.ts`)
```typescript
async exportPdf(budgetId: number): Promise<Blob> {
  const response = await api.get(`/budgets/${budgetId}/export-pdf`, {
    responseType: 'blob',
  });
  return response.data;
}
```

#### 2. Dans `BudgetDetailsPage.vue`
Ajouter bouton "Télécharger PDF" avec icône 📄

Handler :
```typescript
async function downloadPdf() {
  try {
    const blob = await budgetsApi.exportPdf(budget.value.id);
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `budget-${budget.value.month}.pdf`;
    link.click();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    // Gérer erreur
  }
}
```

### Tests recommandés
1. Télécharger PDF d'un budget
2. Vérifier contenu du PDF (stats, catégories, top dépenses)
3. Vérifier formatage professionnel
4. Test avec budget vide
5. Test avec budget très rempli (plusieurs pages)

---

## 🏷️ Feature 8 : Tags pour Dépenses

### Description
Ajouter un système de tags libres aux dépenses pour filtrage et statistiques avancées.

### Spécifications Backend

#### 1. Migration `create_tags_table`
```php
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name')->unique();
    $table->string('color')->default('#3B82F6'); // Couleur hex
    $table->timestamps();

    $table->unique(['user_id', 'name']);
});
```

#### 2. Migration `create_expense_tag_table`
```php
Schema::create('expense_tag', function (Blueprint $table) {
    $table->id();
    $table->foreignId('expense_id')->constrained()->onDelete('cascade');
    $table->foreignId('tag_id')->constrained()->onDelete('cascade');
    $table->timestamps();

    $table->unique(['expense_id', 'tag_id']);
});
```

#### 3. Model `Tag`
```php
protected $fillable = [
    'user_id',
    'name',
    'color',
];

public function user(): BelongsTo;
public function expenses(): BelongsToMany {
    return $this->belongsToMany(Expense::class);
}

// Méthode helper pour générer couleur aléatoire
public static function randomColor(): string;
```

#### 4. Modifier Model `Expense`
Ajouter relation :
```php
public function tags(): BelongsToMany {
    return $this->belongsToMany(Tag::class);
}
```

#### 5. `TagController`
Endpoints :
- `GET /api/tags` - Liste des tags de l'utilisateur
- `POST /api/tags` - Créer tag
- `PUT /api/tags/{id}` - Modifier tag (nom, couleur)
- `DELETE /api/tags/{id}` - Supprimer tag

#### 6. Modifier `ExpenseController`
Dans `store()` et `update()` :
```php
$validated = $request->validate([
    // ... existant
    'tag_ids' => 'nullable|array',
    'tag_ids.*' => 'exists:tags,id',
]);

$expense = Expense::create(...);

if (isset($validated['tag_ids'])) {
    $expense->tags()->sync($validated['tag_ids']);
}
```

Dans `index()` :
```php
$query = $request->user()->expenses()->with('tags');

if ($request->has('tag_id')) {
    $query->whereHas('tags', function ($q) use ($request) {
        $q->where('tags.id', $request->tag_id);
    });
}
```

#### 7. `StatsController` - Nouveau endpoint
```php
public function byTag(Request $request)
{
    $validated = $request->validate([
        'month' => 'nullable|date_format:Y-m',
        'tag_id' => 'nullable|exists:tags,id',
    ]);

    // Calculer stats par tag
    $tags = Tag::where('user_id', $request->user()->id)
        ->withCount(['expenses' => function ($q) use ($validated) {
            if (isset($validated['month'])) {
                $month = Carbon::parse($validated['month'] . '-01');
                $q->whereYear('date', $month->year)
                  ->whereMonth('date', $month->month);
            }
        }])
        ->withSum(['expenses' => function ($q) use ($validated) {
            // Même filtre
        }], 'amount_cents')
        ->get();

    return response()->json($tags);
}
```

#### 8. Routes
```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store']);
    Route::put('tags/{tag}', [TagController::class, 'update']);
    Route::delete('tags/{tag}', [TagController::class, 'destroy']);

    Route::get('stats/by-tag', [StatsController::class, 'byTag']);
});
```

### Spécifications Frontend

#### 1. Types (`src/types/index.ts`)
```typescript
export interface Tag {
  id: number;
  userId: number;
  name: string;
  color: string;
  createdAt: string;
  updatedAt: string;
  expensesCount?: number; // Pour stats
  expensesSumCents?: number; // Pour stats
}

// Modifier Expense interface
export interface Expense {
  // ... existant
  tags?: Tag[];
}
```

#### 2. API Clients
`src/api/tags.ts` :
```typescript
export const tagsApi = {
  async list(): Promise<Tag[]>,
  async create(data: CreateTagData): Promise<Tag>,
  async update(id: number, data: UpdateTagData): Promise<Tag>,
  async delete(id: number): Promise<void>,
}
```

Modifier `src/api/stats.ts` :
```typescript
async byTag(month?: string, tagId?: number): Promise<Tag[]>
```

#### 3. Stores
`src/stores/tags.ts` :
```typescript
export const useTagsStore = defineStore('tags', () => {
  const tags = ref<Tag[]>([]);
  const loading = ref(false);

  async function fetchTags();
  async function createTag(data: CreateTagData);
  async function updateTag(id: number, data: UpdateTagData);
  async function deleteTag(id: number);

  return { ... };
});
```

#### 4. Composant `TagInput.vue`
Input avec :
- Multi-select de tags existants
- Création rapide de nouveau tag (taper + Enter)
- Affichage badges colorés
- Autocomplete

#### 5. Composant `TagBadge.vue`
Badge stylisé avec :
- Couleur de fond selon tag.color
- Nom du tag
- Optionnel : icône ❌ pour retirer

#### 6. Modifier `ExpenseForm.vue` et `EditExpenseModal.vue`
Ajouter champ :
```vue
<TagInput v-model="selectedTags" :available-tags="tagsStore.tags" />
```

#### 7. Page `TagsPage.vue` (optionnelle mais recommandée)
- Liste des tags
- Stats par tag (nombre dépenses, total)
- Actions : renommer, changer couleur, supprimer
- Créer nouveau tag

#### 8. Dans `BudgetDetailsPage.vue`
Ajouter filtre par tag dans liste des dépenses

#### 9. Graphique `ExpensesByTagChart.vue`
Pie chart montrant répartition par tag

#### 10. Route
```typescript
{
  path: '/tags',
  name: 'tags',
  component: () => import('@/pages/TagsPage.vue'),
  meta: { requiresAuth: true },
}
```

### Tests recommandés
1. Créer tag "Vacances" avec couleur verte
2. Ajouter tag à une dépense
3. Filtrer dépenses par tag
4. Voir stats par tag
5. Supprimer tag → vérifie relation supprimée
6. Créer dépense avec plusieurs tags
7. Graphique répartition par tag

---

## 📝 Notes d'implémentation générales

### Ordre recommandé
1. **Feature 4** (Alertes) - Impact utilisateur immédiat, complexité moyenne
2. **Feature 5** (Récurrentes) - Très utile, complexité moyenne-haute
3. **Feature 8** (Tags) - Flexibilité++, complexité basse-moyenne
4. **Feature 7** (PDF) - Simple, bonne finition
5. **Feature 6** (Comparaison) - Analytique avancé, complexité moyenne

### Conventions à respecter
- **Backend** : snake_case pour DB, validation, modèles
- **Frontend** : camelCase pour TypeScript, Vue, API
- **Middlewares** : Conversion automatique request/response
- **Montants** : Toujours en cents dans DB et API
- **Dates** : Format ISO 8601 en API
- **Validation** : Backend (Laravel) + Frontend (Zod)
- **Erreurs** : Gestion dans stores + toast notifications

### Migrations
- Toujours créer migrations séquentielles
- Ne jamais modifier migrations existantes
- Utiliser `foreignId()->constrained()->onDelete('cascade')`
- Indexer les colonnes souvent requêtées

### Tests manuels après chaque feature
1. Vérifier compilation TypeScript (`npm run type-check`)
2. Vérifier build (`npm run build`)
3. Tester CRUD complet
4. Tester cas limites (données vides, erreurs)
5. Vérifier responsive design

### Performance
- Utiliser `with()` pour eager loading (éviter N+1)
- Paginer les listes longues
- Indexer colonnes fréquemment filtrées
- Lazy load les composants lourds

---

## ✅ Checklist par feature

Pour chaque feature, vérifier :
- [ ] Migrations créées et exécutées
- [ ] Models avec relations et casts
- [ ] Controllers avec validation
- [ ] Routes ajoutées dans `api.php`
- [ ] Types TypeScript définis
- [ ] API client créé
- [ ] Store Pinia créé
- [ ] Composants Vue créés
- [ ] Page créée (si nécessaire)
- [ ] Route frontend ajoutée
- [ ] Lien NavBar ajouté (si pertinent)
- [ ] Tests manuels effectués
- [ ] Build réussi sans erreur
- [ ] Documentation mise à jour

---

**Pour implémenter une feature, demande simplement : "Implémente la Feature X du fichier NEXT_FEATURES.md"**
