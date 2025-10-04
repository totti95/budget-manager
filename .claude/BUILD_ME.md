Super idée ! Voici un **prompt prêt à l’emploi** (en français) que tu peux coller dans un fichier `BUILD_ME.md` et donner à Claude Code / Codex pour qu’il génère **tout le projet** : Docker, Laravel, Vue, base de données, API, front, tests, seeds, etc.

---

# Génère un projet complet “Budget Manager” (Laravel + Vue + Docker)

Tu es un assistant de génération de code. **Rends un dépôt complet prêt à cloner**, avec tous les fichiers nécessaires. Le but est un outil web de gestion de budget perso : budget prévisionnel mensuel, suivi réel, comparaisons, récap’ patrimoine, graphiques par catégories/sous-catégories.

## 🧱 Stack imposée

* **Backend**: Laravel 11 (PHP 8.3), Eloquent, Pest pour tests, Laravel Sanctum (auth SPA).
* **Frontend**: Vue 3 + Vite, TypeScript, Pinia, Vue Router, TailwindCSS, Chart.js.
* **Base de données**: PostgreSQL 16.
* **Cache/Queue**: Redis.
* **Conteneurs**: Docker Compose (nginx, php-fpm, node pour build, postgres, redis, mailhog).
* **i18n & formats**: fr-FR, devise **EUR**, fuseau **Europe/Paris** (dates en ISO 8601 côté API).
* **API**: **REST** (JSON). Fournir un **OpenAPI (YAML)**.
* **Sécurité**: CORS pour SPA, rate limiting, validation form request, politiques/guards Laravel.

> Optionnel (si simple): un endpoint GraphQL (Lighthouse) qui *miroite* 3–4 requêtes clés (liste catégories, création dépense, stats mensuelles). Le REST reste la source principale.

## ✅ Fonctionnalités

1. **Templates de budget mensuel prévisionnel**

    * Création/édition d’un **BudgetTemplate** avec catégories et montants prévus.
    * Possibilité de définir un template par défaut.

2. **Création d'un nouveau budget mensuel**

    * Bouton “Générer le budget de <mois>” si non présent.

3. **Saisie des dépenses réelles**

    * Ajouter des **Dépenses** avec date, montant, **catégorie** et **sous-catégorie**.
    * Comparaison vs prévisionnel : variance (montant & %), par catégorie et global.

4. **Patrimoine (éditable)**

    * Page de **patrimoine** (actifs: immobilier, épargne, investissements; passifs si simple).
    * Champs: libellé, type, valeur, institution/compte, notes, dernière mise à jour.
    * Suivi “Dépenses Épargne Prévue” (objectif mensuel et réel).

5. **Tableaux de bord & Graphiques**

    * Vue mensuelle: total dépenses, reste vs prévu, top catégories/sous-catégories.
    * Graphiques: camembert par catégorie/sous-catégorie, barres comparant prévu/réel.
    * Filtres: mois, plage de dates, catégorie.

6. **Catégories & sous-catégories**

    * Hiérarchie **Catégorie -> Sous-catégories** (ex: Restaurants -> McDo).
    * Dépenses attachées à une sous-catégorie (obligatoire), reliée à une catégorie.

7. **Qualité de vie**

    * Import CSV de dépenses (colonnes standard, mapping simple).
    * Export CSV (dépenses du mois, patrimoine).
    * Recherche/filtre/pagination côté API & UI.

## 📦 Modèle de données (Eloquent)

* `users` (Sanctum)
* `budget_templates` (user_id, name, is_default)
* `template_categories` (budget_template_id, name, sort_order)
* `budgets` (user_id, month (YYYY-MM-01), name, generated_from_template_id)
* `budget_categories` (budget_id, name, sort_order, planned_amount_cents) — **dénormalisé** pour figer le prévu du mois
* `budget_subcategories` (budget_category_id, name, planned_amount_cents, sort_order)
* `expenses` (budget_id, budget_subcategory_id, date, label, amount_cents, payment_method, notes)
* `assets` (user_id, type: [immobilier|épargne|investissement|autre], label, institution, value_cents, updated_at, notes)
* `savings_plans` (user_id, month, planned_cents, actual_cents calculé via dépenses cat. “Épargne”)
* (Optionnel) `attachments` (expense_id, path, original_name) si tu ajoutes des reçus.

> **Raisons** : on matérialise les montants prévus **dans le budget du mois** (cat/subcat) pour que les changements de template n’affectent pas l’historique.

## 🔒 Règles métier

* Un seul **template par défaut** par utilisateur.
* Un **budget** par utilisateur et par **mois** (clé unique user_id + month).
* Les **dépenses** d’un mois doivent pointer vers des **subcats du budget du même mois**.
* Variance = réel – prévu (peut être négatif). % variance = réel / prévu – 1 (gérer prévu=0).
* Devise **EUR**, stockée en **cents** (entier). Arrondis banque simples.

## 🛣️ API REST à implémenter (exemples)

Préfixe `/api`. Toutes protégées (sauf auth). Réponses JSON camelCase.

* Auth: `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`, `GET /auth/me`
* Templates:

    * `GET /templates`, `POST /templates`
    * `GET /templates/{id}`, `PUT /templates/{id}`, `DELETE /templates/{id}`
    * `POST /templates/{id}/set-default`
* Budget mensuel:

    * `GET /budgets?month=YYYY-MM` (ou liste paginée)
    * `POST /budgets/generate?month=YYYY-MM` (depuis template défaut)
    * `GET /budgets/{id}` (incl. catégories/sous-catégories & agrégats)
* Catégories/sous-catégories (dans un budget):

    * `POST /budgets/{id}/categories`
    * `POST /budgets/{id}/categories/{catId}/subcategories`
    * `PUT /budgets/{id}/categories/{catId}` / `DELETE ...`
    * `PUT /budgets/{id}/subcategories/{subId}` / `DELETE ...`
* Dépenses:

    * `GET /budgets/{id}/expenses?subcatId=&q=&page=&from=&to=`
    * `POST /budgets/{id}/expenses`
    * `PUT /expenses/{id}`, `DELETE /expenses/{id}`
    * `POST /budgets/{id}/expenses/import-csv` (multipart)
    * `GET /budgets/{id}/expenses/export-csv`
* Stats:

    * `GET /budgets/{id}/stats/summary` (total prévu, total réel, variance, etc.)
    * `GET /budgets/{id}/stats/by-category`
    * `GET /budgets/{id}/stats/by-subcategory?categoryId=`
* Patrimoine:

    * `GET /assets`, `POST /assets`
    * `PUT /assets/{id}`, `DELETE /assets/{id}`
* Épargne prévue:

    * `GET /savings?month=YYYY-MM`
    * `PUT /savings/{id}` (maj objectif), `GET /savings/{id}`

**Fournir** un fichier **`openapi.yaml`** décrivant ces routes, schémas et exemples.

## 🖥️ Frontend (Vue 3)

Pages (Vue Router) :

1. **/login /register**
2. **/dashboard** (sélecteur de mois, cartes KPI: prévu, réel, variance; graphiques)
3. **/budgets/:month**

    * Tableau catégories/sous-catégories : prévu, réel, variance (inline edit prévu)
    * Formulaire ajout dépense (auto-suggestion sous-catégorie)
    * Liste dépenses filtrable (recherche, dates)
    * Import/Export CSV
4. **/templates**

    * CRUD d’un template et de ses cat (drag & drop sort order)
    * Définir par défaut
5. **/patrimoine**

    * Tableau actifs (CRUD inline), total patrimoine, badge “dernière mise à jour”
6. **/settings** (profil, devise affichage, préférences)

Composants clés :

* `BudgetTable.vue`, `ExpenseForm.vue`, `CategoryChart.vue` (Chart.js), `SubcategoryChart.vue`, `MonthPicker.vue`, `AssetsTable.vue`

Store (Pinia) :

* `useAuthStore`, `useBudgetStore`, `useTemplateStore`, `useAssetsStore`, `useStatsStore`

UI/UX :

* Tailwind, dark mode, toasts (vue-sonner ou équivalent), form validation (vee-validate + zod), accessibilité (labels, focus ring).

## 🐳 Docker & scripts

**docker-compose.yml** (services) :

* `nginx` (ports 8080→80)
* `php` (php-fpm, ext pgsql, redis, gd)
* `node` (pour `npm install` / `npm run dev/build`)
* `postgres` (port 5432, volume)
* `redis`
* `mailhog` (8025)

**Makefile** (ou scripts npm) : `make init`, `make up`, `make down`, `make migrate`, `make seed`, `make test`, `make fresh`.

Entrées attendues :

* `.env.example` (Laravel) configuré Postgres/Redis/Mailhog + CORS origin `http://localhost:5173`.
* Script d’init qui exécute : composer install, npm install, migrations, seeds, génération clé app, vite build (ou dev avec HMR).

## 🧪 Tests (Pest)

* Tests unitaires : services de stats (variance), parsers CSV.
* Tests d’intégration API : auth, budgets, dépenses, patrimoine.
* Tests e2e minimal (Pest + Laravel http tests) pour flux “générer budget → ajouter dépenses → vérifier stats”.

## 🌱 Données de démo (Seeders)

* Utilisateur demo (email/pwd connus).
* Template par défaut avec catégories (Logement, Transports, Alimentation, Restaurants, Épargne, Loisirs, Divers)
* Budgets pour 2–3 mois récents + quelques dépenses réalistes.
* 3–4 actifs (compte courant, livret A, PEA, appart).

## 📂 Arborescence attendue (extrait)

```
budget-manager/
  backend/
    app/
    database/migrations/
    database/seeders/
    app/Http/Controllers/
    app/Http/Requests/
    app/Models/
    app/Policies/
    routes/api.php
    tests/
    openapi.yaml
    composer.json
    .env.example
  frontend/
    src/
      api/
      stores/
      components/
      pages/
      router/
      styles/
      main.ts
      App.vue
    index.html
    package.json
    tailwind.config.ts
    postcss.config.js
    tsconfig.json
  docker/
    nginx.conf
    php.ini
    Dockerfile.php
    Dockerfile.node
  docker-compose.yml
  Makefile
  README.md
```

## 📝 README attendu

* Prérequis, installation, commandes Docker/Make, URL locales (API: [http://localhost:8080/api](http://localhost:8080/api), Front: [http://localhost:5173](http://localhost:5173)), comptes démo, scénario de test rapide, capture(s) d’écran si possible.

## 🔎 Qualité & Outillage

* Lint: PHP-CS-Fixer, ESLint + Prettier.
* CI simple (GitHub Actions) : lint + tests.
* Logger les erreurs (Monolog), gestion des exceptions API (handler JSON propre).
* Pagination standard (Laravel), tri & filtres.
* Couvrir les validations (montants > 0, date dans le mois sélectionné, etc.)

## 📤 Format de ta réponse (IMPORTANT)

* Rends **l’intégralité du dépôt** sous forme de **sections par fichier** avec blocs de code **annotés par chemin**, par ex. :

```text
// FILE: docker-compose.yml
<contenu>

// FILE: backend/routes/api.php
<contenu>
```

* N’omets pas les fichiers clés (Dockerfiles, migrations, seeders, .env.example, openapi.yaml, composants Vue, stores, tests, README).
* Fournis au moins **1 migration** par table décrite, **1 seeder** complet, et des **tests Pest** significatifs.
* Le projet doit **se lancer sans modification** après `make init && make up`.

---

**Objectif final** : un projet Dockerisé **prêt à l’emploi**, où je peux générer mon budget du mois, ajouter des dépenses avec catégorie/sous-catégorie (ex: Restaurants → McDo), voir les comparaisons prévu/réel, et gérer mon patrimoine avec graphiques clairs.
