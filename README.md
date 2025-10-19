# Budget Manager 💰

Un outil web complet de gestion de budget personnel avec Laravel, Vue 3 et Docker.

## 🚀 Fonctionnalités

- **Templates de budget** : Créez des modèles réutilisables avec catégories et sous-catégories
- **Budget mensuel** : Générez automatiquement votre budget mensuel depuis vos templates
- **Suivi des dépenses** : Enregistrez toutes vos dépenses avec catégorisation détaillée
- **Comparaison prévu/réel** : Visualisez les écarts entre budget prévisionnel et dépenses réelles
- **Gestion du patrimoine** : Suivez vos actifs (épargne, investissements, immobilier)
- **Import/Export CSV** : Importez et exportez vos dépenses
- **Statistiques & Graphiques** : Analysez vos finances avec des graphiques par catégorie
- **Multi-utilisateurs** : Chaque utilisateur a ses propres budgets et données
- **Système de rôles** : Administration avec gestion des utilisateurs et permissions
- **Interface moderne** : Dark mode, composants réactifs, notifications toast, modales de confirmation

## 🛠️ Stack Technique

### Backend
- **Laravel 11** (PHP 8.3)
- **MySQL 8.0**
- **Laravel Sanctum** (authentication SPA)
- **Middlewares** de conversion automatique camelCase ↔ snake_case
- **Pest** (tests)

### Frontend
- **Vue 3** avec TypeScript (Composition API)
- **Vite** (build tool)
- **Pinia** (state management)
- **Vue Router** (routing)
- **TailwindCSS** (styling)
- **VeeValidate + Zod** (validation de formulaires)
- **Chart.js** (graphiques)
- **ESLint + Prettier** (qualité de code)

### Infrastructure
- **Docker & Docker Compose**
- **Nginx**
- **PHP-FPM**
- **phpMyAdmin** (gestion de base de données en dev)

## 📋 Prérequis

- **Docker** et **Docker Compose** installés
- **Make** (optionnel mais recommandé)
- Au moins 4 GB de RAM disponible

## 🚀 Installation

### 1. Cloner le repository

```bash
git clone https://github.com/votre-username/budget-manager.git
cd budget-manager
```

### 2. Initialiser le projet

```bash
make init
```

Cette commande va :
- Construire les images Docker
- Installer les dépendances PHP (composer)
- Installer les dépendances Node (npm)
- Générer la clé d'application Laravel
- Exécuter les migrations de base de données
- Insérer les données de démonstration

### 3. Démarrer les services

```bash
make up
```

## 🌐 URLs d'accès

Une fois les services démarrés :

- **Frontend (Vue)** : http://localhost:5173
- **Backend API (Laravel)** : http://localhost:8080/api
- **phpMyAdmin** : http://localhost:8081

## 👤 Comptes de démonstration

### Compte utilisateur standard
- **Email** : `demo@budgetmanager.local`
- **Mot de passe** : `password`

Le compte contient :
- Un template de budget par défaut avec 7 catégories
- 3 budgets mensuels (mois actuel + 2 précédents)
- Des dépenses d'exemple dans chaque budget
- 4 actifs patrimoniaux (comptes, épargne, immobilier)

### Compte administrateur
- **Email** : `admin@budgetmanager.local`
- **Mot de passe** : `password`

Accès aux fonctionnalités d'administration :
- Gestion des utilisateurs (création, modification, désactivation)
- Attribution des rôles (user/admin)
- Changement de mot de passe utilisateur

## 📘 Commandes disponibles

> 💡 **Pour la gestion détaillée des données de test** (générer, vider, restaurer), consultez [DONNEES_TEST.md](./DONNEES_TEST.md)

### Commandes Make

```bash
make help              # Affiche toutes les commandes disponibles
make init              # Installation initiale complète
make up                # Démarre tous les conteneurs
make down              # Arrête tous les conteneurs
make migrate           # Exécute les migrations
make seed              # Insère les données de démo
make fresh             # Reset complet de la BDD avec seeds ⭐
make test              # Exécute les tests Pest
make build             # Build le frontend pour production
make logs              # Affiche les logs en temps réel
make clean             # Supprime tous les conteneurs et volumes
make shell-php         # Ouvre un shell dans le conteneur PHP
make shell-node        # Ouvre un shell dans le conteneur Node
```

### Commandes Artisan

```bash
make artisan CMD="migrate:status"
make artisan CMD="route:list"
make artisan CMD="cache:clear"
```

### Commandes NPM (depuis le host ou dans le conteneur)

```bash
# Dans le conteneur Node
make shell-node
npm run lint              # Vérifier le code avec ESLint
npm run lint:fix          # Corriger automatiquement les erreurs ESLint
npm run format            # Formater le code avec Prettier
npm run type-check        # Vérifier les types TypeScript
npm run build             # Build pour production

# Via Make
make npm CMD="install axios"
make npm CMD="run build"
```

## 🗂️ Structure du projet

```
budget-manager/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/  # Contrôleurs API
│   │   ├── Models/            # Modèles Eloquent
│   │   └── Policies/          # Politiques d'autorisation
│   ├── database/
│   │   ├── migrations/        # Migrations de BDD
│   │   ├── seeders/           # Seeders de données
│   │   └── factories/         # Factories pour tests
│   ├── routes/api.php         # Routes API
│   ├── tests/                 # Tests Pest
│   └── openapi.yaml           # Spécification OpenAPI
├── frontend/                  # Vue 3 SPA
│   ├── src/
│   │   ├── api/              # Clients API
│   │   ├── components/       # Composants Vue (modales, formulaires, etc.)
│   │   ├── composables/      # Composables Vue (useToast, useConfirm, etc.)
│   │   ├── pages/            # Pages/Vues
│   │   ├── router/           # Configuration router
│   │   ├── stores/           # Stores Pinia
│   │   ├── styles/           # Styles globaux
│   │   └── types/            # Types TypeScript
│   ├── .eslintrc.cjs         # Configuration ESLint
│   ├── index.html
│   └── package.json
├── docker/                    # Configuration Docker
│   ├── Dockerfile.php
│   ├── Dockerfile.node
│   ├── nginx.conf
│   └── php.ini
├── docker-compose.yml
├── Makefile
├── CLAUDE.md                  # Documentation pour développement avec Claude Code
└── README.md
```

## 💾 Modèle de données

### Tables principales

- **users** : Utilisateurs de l'application
- **roles** : Rôles système (user, admin)
- **budget_templates** : Templates de budget réutilisables
- **template_categories** : Catégories dans un template
- **template_subcategories** : Sous-catégories dans un template
- **budgets** : Budgets mensuels générés
- **budget_categories** : Catégories d'un budget (snapshot du template)
- **budget_subcategories** : Sous-catégories d'un budget
- **expenses** : Dépenses enregistrées
- **assets** : Actifs patrimoniaux
- **savings_plans** : Plans d'épargne mensuels

### Relations

- Un utilisateur appartient à un rôle (role_id)
- Un utilisateur a plusieurs templates et budgets
- Un template a plusieurs catégories
- Une catégorie a plusieurs sous-catégories
- Un budget est généré depuis un template
- Les dépenses sont liées à une sous-catégorie d'un budget

### Architecture de conversion camelCase/snake_case

Le projet utilise une architecture middleware pour gérer automatiquement la conversion entre les conventions de nommage :

- **Frontend** : Utilise exclusivement **camelCase** (JavaScript/TypeScript convention)
- **Backend** : Utilise exclusivement **snake_case** (PHP/Laravel convention)
- **Conversion automatique** :
  - `ConvertRequestToSnakeCase` : camelCase → snake_case pour les requêtes entrantes
  - `ConvertResponseToCamelCase` : snake_case → camelCase pour les réponses sortantes

Cela permet au frontend et au backend de suivre leurs conventions respectives sans conversion manuelle.

## 🔑 Fonctionnalités clés

### 1. Workflow typique

1. **Créer un template** avec vos catégories standards (Logement, Alimentation, etc.)
2. **Définir comme défaut** ce template
3. **Générer le budget du mois** en un clic
4. **Ajouter vos dépenses** au fur et à mesure
5. **Consulter les statistiques** pour suivre vos écarts

### 2. Import CSV de dépenses

Format CSV attendu :
```csv
date,label,amount_cents,subcategory,payment_method,notes
2025-01-15,Courses Carrefour,4500,Courses,CB,
2025-01-16,Restaurant,2500,Restaurants,CB,Avec amis
```

### 3. Export des données

Exportez vos dépenses mensuelles en CSV pour analyse externe.

### 4. Gestion du patrimoine

Suivez l'évolution de votre patrimoine :
- Comptes bancaires
- Livrets d'épargne
- Investissements (PEA, assurance-vie)
- Biens immobiliers

## 🧪 Tests

Le projet utilise **Pest** pour les tests.

```bash
# Exécuter tous les tests
make test

# Dans le conteneur PHP
make shell-php
php artisan test

# Tests spécifiques
php artisan test --filter=AuthTest
```

### Types de tests

- **Tests unitaires** : Calculs de variance, logique métier
- **Tests d'intégration** : Endpoints API, authentification
- **Tests e2e** : Flux complets (génération budget → ajout dépenses → stats)

## 📊 API REST

Documentation complète disponible dans `backend/openapi.yaml`

### Endpoints principaux

#### Authentication
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `GET /api/auth/me` - Utilisateur connecté
- `POST /api/auth/logout` - Déconnexion

#### Administration (admin uniquement)
- `GET /api/admin/users` - Liste des utilisateurs avec filtres et pagination
- `POST /api/admin/users` - Créer un utilisateur
- `PUT /api/admin/users/{id}` - Modifier un utilisateur
- `DELETE /api/admin/users/{id}` - Désactiver un utilisateur (soft delete)
- `PUT /api/admin/users/{id}/password` - Changer le mot de passe
- `GET /api/admin/roles` - Liste des rôles disponibles

#### Templates
- `GET /api/templates` - Liste des templates
- `POST /api/templates` - Créer un template
- `PUT /api/templates/{id}` - Modifier un template
- `DELETE /api/templates/{id}` - Supprimer un template
- `POST /api/templates/{id}/set-default` - Définir comme défaut

#### Budgets
- `GET /api/budgets` - Liste des budgets
- `POST /api/budgets/generate` - Générer un budget
- `GET /api/budgets/{id}` - Détails d'un budget

#### Dépenses
- `GET /api/budgets/{id}/expenses` - Liste des dépenses
- `POST /api/budgets/{id}/expenses` - Créer une dépense
- `PUT /api/expenses/{id}` - Modifier une dépense
- `DELETE /api/expenses/{id}` - Supprimer une dépense
- `POST /api/budgets/{id}/expenses/import-csv` - Import CSV
- `GET /api/budgets/{id}/expenses/export-csv` - Export CSV

#### Statistiques
- `GET /api/budgets/{id}/stats/summary` - Résumé global
- `GET /api/budgets/{id}/stats/by-category` - Stats par catégorie
- `GET /api/budgets/{id}/stats/by-subcategory` - Stats par sous-catégorie

#### Patrimoine
- `GET /api/assets` - Liste des actifs
- `POST /api/assets` - Créer un actif
- `PUT /api/assets/{id}` - Modifier un actif
- `DELETE /api/assets/{id}` - Supprimer un actif

## 🔒 Sécurité

- **Laravel Sanctum** pour l'authentification SPA
- **CORS** configuré pour autoriser uniquement le frontend
- **Rate limiting** sur les endpoints API
- **Policies** Laravel pour l'autorisation
- **Validation** stricte des données entrantes
- **Protection CSRF** pour les requêtes sensibles

## 🌍 Internationalisation

- **Langue** : Français (fr-FR)
- **Devise** : EUR (€)
- **Timezone** : Europe/Paris
- **Format de date** : ISO 8601 (API) / fr-FR (UI)

## 🐛 Dépannage

### Les conteneurs ne démarrent pas

```bash
# Vérifier les logs
make logs

# Nettoyer et redémarrer
make clean
make init
make up
```

**Causes fréquentes :**
- Ports déjà utilisés (8080, 5173, 5432, 6379)
- RAM insuffisante (minimum 4GB requis)
- Docker daemon non démarré

### La base de données est vide

```bash
make migrate
make seed
```

### Erreurs de permissions

```bash
# Dans le conteneur PHP
docker compose exec php sh
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Le frontend ne se connecte pas au backend

Vérifier que la variable d'environnement est correcte :
- Frontend : Créer `.env` avec `VITE_API_URL=http://localhost:8080/api`
- Backend : Vérifier `FRONTEND_URL=http://localhost:5173` dans `.env`

**Test rapide :**
```bash
curl http://localhost:8080/api/health
# Devrait retourner: {"status":"ok"}
```

### Session expirée après rechargement de page

Ce problème est normalement résolu. Si vous le rencontrez encore :
1. Vider le localStorage : `localStorage.clear()` dans la console navigateur
2. Se reconnecter

### TypeScript build errors

```bash
cd frontend
npm run type-check  # Vérifier les erreurs TypeScript
npm run lint        # Vérifier les erreurs ESLint
npm run build       # Build complet
```

### Tests backend échouent

```bash
# Vérifier que la config de test est correcte
docker compose exec php php artisan test --testsuite=Feature

# Reset de la DB de test si nécessaire
docker compose exec php php artisan migrate:fresh --env=testing
```

### CORS errors dans le navigateur

Vérifier dans `backend/config/cors.php` :
- `allowed_origins` contient bien `http://localhost:5173`
- `supports_credentials` est à `true`

### Erreur "Too many requests" (429)

Le rate limiting est actif. Attendre 1 minute ou augmenter les limites dans `backend/bootstrap/app.php`

### Accès à la base de données

Utilisez phpMyAdmin pour explorer la base de données :
- URL : http://localhost:8081
- Serveur : `mysql`
- Utilisateur : `budget_user`
- Mot de passe : `budget_password`
- Base de données : `budget_manager`

## 📝 Développement

### Ajouter une nouvelle fonctionnalité

1. **Backend** :
   - Créer la migration
   - Créer/modifier le modèle
   - Créer le contrôleur
   - Ajouter la route dans `routes/api.php`
   - Écrire les tests

2. **Frontend** :
   - Ajouter les types TypeScript
   - Créer le client API
   - Créer/modifier le store Pinia
   - Créer les composants/pages Vue
   - Ajouter les routes

### Conventions de code

- **PHP** : PSR-12, snake_case pour les noms de variables/colonnes
- **JavaScript/TypeScript** : ESLint + Prettier, camelCase pour les variables
- **Vue 3** : Composition API avec `<script setup>`, TypeScript strict
- **Validation** : VeeValidate + Zod pour les formulaires
- **Commits** : Messages en français, clairs et descriptifs
- **Branches** : `feature/nom-fonctionnalite`, `fix/nom-bug`

### Ressources pour le développement

- **CLAUDE.md** : Documentation détaillée pour le développement avec Claude Code (patterns, conventions, commandes courantes)
- **DONNEES_TEST.md** : Guide complet pour la gestion des données de test

## 🚀 Déploiement en production

### Build du frontend

```bash
make build
```

Les fichiers de production seront dans `frontend/dist/`

### Optimisations Laravel

```bash
make artisan CMD="config:cache"
make artisan CMD="route:cache"
make artisan CMD="view:cache"
make artisan CMD="optimize"
```

### Variables d'environnement

Modifier `.env` pour la production :
- `APP_ENV=production`
- `APP_DEBUG=false`
- Changer les credentials de base de données
- Configurer un vrai serveur SMTP

## 📄 Licence

MIT

## 👥 Support

Pour toute question ou problème :
1. Vérifier la documentation
2. Consulter les issues GitHub
3. Créer une nouvelle issue si nécessaire

## 🎉 Scénario de test rapide

### En tant qu'utilisateur
1. Se connecter avec `demo@budgetmanager.local` / `password`
2. Voir le tableau de bord avec les statistiques du mois
3. Générer un budget pour le mois suivant
4. Ajouter une dépense dans une catégorie
5. Voir la variance se mettre à jour
6. Aller dans "Patrimoine" pour voir vos actifs
7. Consulter les templates pour comprendre la structure

### En tant qu'administrateur
1. Se connecter avec `admin@budgetmanager.local` / `password`
2. Accéder à la page "Gestion des utilisateurs" dans le menu
3. Créer un nouvel utilisateur (un mot de passe sera généré automatiquement)
4. Modifier le rôle d'un utilisateur (user ↔ admin)
5. Changer le mot de passe d'un utilisateur
6. Désactiver un utilisateur (soft delete)

---

**Généré avec ❤️ par Claude Code**
