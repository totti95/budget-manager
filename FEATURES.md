# 📊 Budget Manager - Documentation des Fonctionnalités

## 📌 Table des Matières

1. [Introduction](#introduction)
2. [Authentification & Gestion des Utilisateurs](#authentification--gestion-des-utilisateurs)
3. [Gestion des Budgets](#gestion-des-budgets)
4. [Gestion des Dépenses](#gestion-des-dépenses)
   - [Catégories vs Tags : Quelle différence ?](#️-catégories-vs-tags--quelle-différence-)
5. [Patrimoine & Épargne](#patrimoine--épargne)
6. [Statistiques & Visualisations](#statistiques--visualisations)
7. [Notifications & Paramètres](#notifications--paramètres)
8. [Architecture Technique](#architecture-technique)

---

## 🎯 Introduction

**Budget Manager** est une application web complète de gestion budgétaire personnelle permettant de suivre ses finances, planifier ses dépenses, gérer son patrimoine et visualiser l'évolution de sa situation financière.

### Technologies utilisées

**Backend :**
- Laravel 11 (PHP 8.3)
- MySQL 8.0
- Redis
- Laravel Sanctum (authentification API)

**Frontend :**
- Vue 3 (Composition API)
- TypeScript
- Vite
- Pinia (state management)
- TailwindCSS
- Chart.js
- VeeValidate + Zod

**Architecture :**
- API REST (backend Laravel)
- SPA (Single Page Application - frontend Vue)
- Authentification par tokens Bearer
- Docker containerization

---

## 🔐 Authentification & Gestion des Utilisateurs

### 1. Système d'authentification

#### Inscription
- **Page** : `/register`
- **Fonctionnalités** :
  - Création de compte avec nom, email et mot de passe
  - Validation sécurisée (minimum 8 caractères)
  - Redirection automatique vers le dashboard après inscription
- **API** : `POST /api/auth/register`

#### Connexion
- **Page** : `/login`
- **Fonctionnalités** :
  - Connexion via email et mot de passe
  - Affichage des identifiants de démonstration
  - Token Bearer généré pour les requêtes API
  - Lien vers inscription et réinitialisation de mot de passe
- **API** : `POST /api/auth/login`

#### Réinitialisation de mot de passe
- **Pages** :
  - `/forgot-password` : Demande de réinitialisation
  - `/reset-password` : Formulaire de nouveau mot de passe
- **Fonctionnalités** :
  - Envoi d'email avec lien de réinitialisation
  - Validation du token et de l'email
  - Redirection vers login après succès
- **API** :
  - `POST /api/auth/forgot-password`
  - `POST /api/auth/reset-password`

### 2. Gestion du profil

#### Profil utilisateur
- **Page** : `/profile`
- **Fonctionnalités** :
  - Affichage des informations personnelles (nom, email, rôle)
  - Changement de mot de passe
  - Paramètres de notifications
- **API** :
  - `GET /api/auth/me` : Récupération du profil
  - `PUT /api/auth/password` : Modification du mot de passe

### 3. Système de rôles

**Rôles disponibles :**
- **User** : Utilisateur standard avec accès à ses données personnelles
- **Admin** : Accès complet + gestion des utilisateurs

**Sécurité :**
- Soft delete des utilisateurs (conservation de l'historique)
- Protection du dernier compte administrateur
- Contrôle d'accès via policies Laravel

### 4. Gestion des utilisateurs (Admin uniquement)

- **Page** : `/admin/users` (nécessite rôle Admin)
- **Fonctionnalités** :
  - Liste paginée de tous les utilisateurs
  - Recherche par nom ou email
  - Filtres par rôle (User/Admin) et statut (Actif/Désactivé)
  - Création d'utilisateurs avec mot de passe généré automatiquement
  - Modification des informations (nom, email, rôle)
  - Changement de mot de passe administrateur
  - Désactivation/réactivation de comptes
  - Protection contre la suppression du dernier admin
- **API** :
  - `GET /api/admin/users` : Liste des utilisateurs
  - `POST /api/admin/users` : Créer utilisateur
  - `PUT /api/admin/users/{user}` : Modifier utilisateur
  - `PUT /api/admin/users/{user}/password` : Changer mot de passe
  - `DELETE /api/admin/users/{user}` : Désactiver
  - `PUT /api/admin/users/{user}/restore` : Réactiver

---

## 💰 Gestion des Budgets

### 1. Templates de budget (Modèles réutilisables)

#### Qu'est-ce qu'un template ?
Un template est un modèle de budget réutilisable qui définit la structure de vos catégories et les montants planifiés. Il sert de base pour générer vos budgets mensuels.

#### Fonctionnalités
- **Page** : `/templates`
- **Caractéristiques** :
  - Création de templates avec nom personnalisé
  - Structure hiérarchique à 2 niveaux :
    - **Catégories** (ex: Logement, Alimentation, Transport)
    - **Sous-catégories** (ex: Loyer, Électricité, Eau)
  - Définition de montants planifiés pour chaque niveau
  - Marquage d'un template comme "par défaut"
  - Modification et suppression
  - Tri des catégories et sous-catégories

- **API** :
  - `GET /api/templates` : Lister les templates
  - `POST /api/templates` : Créer template
  - `GET /api/templates/{template}` : Détails
  - `PUT /api/templates/{template}` : Modifier
  - `DELETE /api/templates/{template}` : Supprimer
  - `POST /api/templates/{template}/set-default` : Définir par défaut

### 2. Budgets mensuels

#### Dashboard mensuel
- **Page** : `/` (page d'accueil)
- **Fonctionnalités** :
  - Sélecteur de mois (navigation ±2 mois)
  - Vue d'ensemble du budget du mois
  - **Statistiques** :
    - Montant total prévu
    - Montant total dépensé
    - Économies/Surplus
    - Nombre de dépenses
  - **Tableaux** :
    - Catégories avec montants prévus vs réels
    - Sous-catégories détaillées
  - **Graphiques** :
    - Distribution des dépenses par catégorie (pie chart)
    - Évolution du patrimoine (line chart)
  - Bouton "Générer budget" si aucun budget n'existe
  - Bouton "Voir le détail" pour accès à la page de détails

- **API** :
  - `GET /api/budgets` : Liste des budgets (filtre par mois)
  - `POST /api/budgets/generate` : Générer depuis template par défaut

#### Page de détails du budget
- **Page** : `/budgets/:month`
- **Fonctionnalités** :
  - Résumé : Prévu / Dépensé / Restant
  - Export PDF du budget complet
  - Formulaire d'ajout de dépense intégré
  - Création de sous-catégories à la volée
  - Graphique des dépenses par tag
  - **Tableau des dépenses** :
    - Date, Catégorie/Sous-catégorie
    - Libellé, Montant
    - Méthode de paiement
    - Tags associés
    - Actions : Modifier, Supprimer
  - Filtrage des dépenses par tag
  - Modal d'édition de dépense

- **API** :
  - `GET /api/budgets/{budget}` : Détails du budget
  - `PUT /api/budgets/{budget}` : Modifier nom
  - `DELETE /api/budgets/{budget}` : Supprimer
  - `GET /api/budgets/{budget}/export-pdf` : Export PDF
  - `POST /api/budgets/{budget}/categories` : Créer catégorie
  - `POST /api/budgets/{budget}/categories/{category}/subcategories` : Créer sous-catégorie

#### Génération de budget
**Processus :**
1. Le système copie la structure du template par défaut
2. Crée un nouveau budget pour le mois sélectionné
3. Les catégories et sous-catégories sont dupliquées (pas de référence au template)
4. Les montants planifiés sont copiés
5. Le budget devient indépendant du template

**Avantages :**
- Modification du template n'affecte pas les budgets existants
- Intégrité historique préservée
- Chaque budget peut être personnalisé sans impact sur les autres

### 3. Comparaison de budgets

- **Page** : `/budgets-compare`
- **Fonctionnalités** :
  - Sélection de 2 à 3 budgets mensuels (12 derniers mois)
  - Vue comparative côte-à-côte :
    - Budget prévu
    - Dépenses réelles
    - Différence (€ et %)
  - Tableau comparatif par catégorie
  - Graphiques de comparaison
  - Identification des tendances

- **API** :
  - `GET /api/budgets/compare` : Comparer 2-3 budgets (query params: budgetIds)

---

## 💸 Gestion des Dépenses

### 🏷️ Catégories vs Tags : Quelle différence ?

Avant de détailler les fonctionnalités de gestion des dépenses, il est important de comprendre la différence entre **catégories/sous-catégories** et **tags**, deux systèmes complémentaires pour organiser vos dépenses.

#### Catégories et Sous-catégories

**Nature** : Structure hiérarchique obligatoire et budgétaire

Les catégories forment l'ossature de votre budget. Elles représentent les grands postes de dépenses :

- **Organisation fixe** du budget (Logement, Alimentation, Transport, Loisirs...)
- **Planification budgétaire** : montants prévus définis par catégorie
- **Une seule sous-catégorie par dépense** (relation 1:1)
- **Définies au niveau du template ou du budget**
- **Objectif** : Suivi budgétaire et statistiques par poste de dépense

**Exemples de structure** :
- **Catégorie "Alimentation"** → Sous-catégories : "Supermarché", "Restaurants", "Snacks"
- **Catégorie "Transport"** → Sous-catégories : "Essence", "Transports en commun", "Parking"
- **Catégorie "Logement"** → Sous-catégories : "Loyer", "Électricité", "Eau", "Assurance"

#### Tags (Étiquettes)

**Nature** : Labels flexibles et transversaux, optionnels

Les tags sont des étiquettes libres que vous créez pour marquer vos dépenses selon vos besoins :

- **Créés librement** par l'utilisateur, sans structure imposée
- **Multi-sélection possible** : plusieurs tags par dépense
- **Transversaux aux catégories** : un tag peut s'appliquer à n'importe quelle catégorie
- **Personnalisables** avec couleurs pour identification visuelle
- **Objectif** : Filtrage flexible et analyse croisée

**Exemples d'utilisation** :
- **"Vacances"** : peut s'appliquer à plusieurs catégories (Alimentation, Transport, Loisirs)
- **"Professionnel"** : pour distinguer dépenses pro/perso dans différentes catégories
- **"Déductible"** : marquer les dépenses déductibles fiscalement
- **"Urgent"** : identifier les dépenses importantes quelle que soit la catégorie
- **"Cadeau"** : tracker tous les cadeaux offerts

#### Tableau comparatif

| Aspect | Catégories/Sous-catégories | Tags |
|--------|---------------------------|------|
| **Obligation** | ✅ Obligatoire (chaque dépense doit avoir une sous-catégorie) | ⭕ Optionnel |
| **Quantité** | 1️⃣ Une seule sous-catégorie par dépense | ♾️ Plusieurs tags possibles |
| **Structure** | 📊 Hiérarchique (2 niveaux fixes) | 📋 Plat (liste simple) |
| **Planification** | 💰 Montants planifiés par catégorie | ❌ Pas de planification budgétaire |
| **Objectif** | 📈 Organisation budgétaire, suivi des postes | 🔍 Filtrage, analyse transversale |
| **Portée** | 📑 Définis au niveau budget/template | 👤 Personnels à l'utilisateur |
| **Modification** | ⚠️ Impact sur la structure du budget | ✅ Aucun impact budgétaire |
| **Statistiques** | 📊 Par catégorie et sous-catégorie | 📊 Par tag avec graphique coloré |

#### Exemple concret : Utilisation combinée

**Dépense** : "Restaurant sushi - repas d'affaires avec client - 85€"

- **Catégorie** : Alimentation
- **Sous-catégorie** : Restaurants *(pour le suivi budgétaire alimentaire)*
- **Tags** : "Professionnel" + "Client X" + "Déductible" *(pour filtrage et reporting)*

**Avantages de cette combinaison** :
- ✅ **Structure budgétaire claire** : La dépense compte dans le budget "Alimentation → Restaurants"
- ✅ **Flexibilité d'analyse** : Vous pouvez filtrer toutes les dépenses "Professionnel" ou "Client X"
- ✅ **Filtrage multi-critères** : "Toutes les dépenses Restaurants qui sont Professionnelles"
- ✅ **Rapports personnalisés** : "Toutes mes dépenses Vacances" (Alimentation + Transport + Loisirs)

#### 💡 Conseil d'utilisation

**Utilisez les catégories pour** :
- Organiser votre budget par grands postes de dépense
- Planifier vos montants mensuels
- Suivre votre budget prévu vs réel
- Comparer vos dépenses d'un mois à l'autre

**Utilisez les tags pour** :
- Marquer des dépenses transversales (ex: "Vacances", "Travail", "Maison")
- Suivre des projets spécifiques (ex: "Rénovation", "Mariage")
- Identifier des types de dépenses (ex: "Déductible", "Remboursable")
- Faire des analyses personnalisées sans modifier votre structure budgétaire

---

### 1. Dépenses manuelles

#### Création et modification
- **Fonctionnalités** :
  - Formulaire complet avec :
    - Date de la dépense
    - Sélection catégorie → sous-catégorie
    - Libellé descriptif
    - Montant en euros
    - Méthode de paiement (CB, Espèces, Virement, Prélèvement, Chèque)
    - Notes optionnelles
    - **Tags** (multi-sélection avec création inline)
  - Validation avec VeeValidate + Zod
  - Modal d'édition pour modification rapide
  - Suppression avec confirmation

- **API** :
  - `GET /api/budgets/{budget}/expenses` : Liste des dépenses (pagination 50)
  - `POST /api/budgets/{budget}/expenses` : Créer dépense
  - `PUT /api/expenses/{expense}` : Modifier
  - `DELETE /api/expenses/{expense}` : Supprimer

#### Filtres et recherche
- Filtrage par sous-catégorie
- Filtrage par tag
- Recherche textuelle (libellé)
- Filtrage par plage de dates (from/to)

### 2. Étiquettes (Tags)

#### Gestion des tags
- **Page** : `/tags`
- **Fonctionnalités** :
  - Création de tags personnalisés
  - **Propriétés** :
    - Nom du tag (unique par utilisateur)
    - Couleur personnalisée (picker + code hex)
  - Barre de recherche pour filtrer
  - Affichage en grille avec badges colorés
  - Modification nom et couleur
  - Suppression (conserve les dépenses associées)
  - Date de création affichée

- **API** :
  - `GET /api/tags` : Liste des tags
  - `POST /api/tags` : Créer tag
  - `PUT /api/tags/{tag}` : Modifier
  - `DELETE /api/tags/{tag}` : Supprimer

#### Utilisation des tags
- Association de plusieurs tags à une dépense
- Autocomplete avec création inline dans les formulaires
- Filtrage des dépenses par tag
- Statistiques et graphiques par tag
- Couleurs personnalisées pour visualisation

### 3. Import/Export CSV

#### Export
- **Fonctionnalité** : Export de toutes les dépenses d'un budget en CSV
- **Format** : Date, Libellé, Montant, Catégorie, Sous-catégorie, Paiement, Notes
- **API** : `GET /api/budgets/{budget}/expenses/export-csv`

#### Import
- **Fonctionnalité** : Import de dépenses depuis fichier CSV
- **Validation** : Vérification des formats et montants
- **Mapping** : Association automatique aux sous-catégories
- **API** : `POST /api/budgets/{budget}/expenses/import-csv`

### 4. Dépenses récurrentes

#### Concept
Les dépenses récurrentes sont des transactions automatiques qui se répètent selon une fréquence définie. Elles sont créées automatiquement dans les budgets mensuels.

#### Gestion
- **Page** : `/recurring-expenses`
- **Fonctionnalités** :
  - Affichage en cartes avec toutes les informations
  - **Création** :
    - Libellé et montant
    - **Fréquence** :
      - Mensuelle (jour du mois 1-31)
      - Hebdomadaire (jour de la semaine)
      - Annuelle (mois + jour)
    - Association à une sous-catégorie de template
    - Date de début (obligatoire)
    - Date de fin (optionnelle)
    - Méthode de paiement et notes
    - Option "Création automatique" (activée par défaut)
  - **Modification** : Tous les paramètres modifiables
  - **Activation/Désactivation** : Toggle pour activer/désactiver sans supprimer
  - **Suppression** : Suppression définitive
  - **Badges** :
    - Indicateur "Création auto" si activé
    - Statut Actif/Inactif

#### Création automatique
- Lors de la génération d'un budget mensuel
- Vérifie les dépenses récurrentes actives
- Applique la logique de fréquence
- Crée automatiquement les dépenses correspondantes
- Respecte les dates de début/fin

- **API** :
  - `GET /api/recurring-expenses` : Liste
  - `POST /api/recurring-expenses` : Créer
  - `GET /api/recurring-expenses/{recurringExpense}` : Détails
  - `PUT /api/recurring-expenses/{recurringExpense}` : Modifier
  - `DELETE /api/recurring-expenses/{recurringExpense}` : Supprimer
  - `PATCH /api/recurring-expenses/{recurringExpense}/toggle-active` : Activer/Désactiver

---

## 🏠 Patrimoine & Épargne

### 1. Gestion du patrimoine

#### Vue d'ensemble
- **Page** : `/patrimoine`
- **Résumé** :
  - Total des actifs (vert)
  - Total des passifs (rouge)
  - **Patrimoine net** (bleu) = Actifs - Passifs

#### Actifs
- **Types d'actifs** :
  - Immobilier (maison, appartement...)
  - Épargne (livrets, comptes épargne...)
  - Investissement (actions, cryptos, assurance-vie...)
  - Autre
- **Propriétés** :
  - Type et libellé
  - Institution (banque, organisme...)
  - Valeur en euros
  - Notes descriptives
  - Date de mise à jour automatique

#### Passifs (Dettes)
- Même structure que les actifs
- Flag `is_liability` pour distinction
- Types identiques pour classification

#### Fonctionnalités
- Tableau avec tri et filtres
- Création et modification via modal
- Suppression avec confirmation
- Mise à jour automatique de la date de modification

- **API** :
  - `GET /api/assets` : Liste (séparée actifs/passifs avec totaux)
  - `POST /api/assets` : Créer
  - `GET /api/assets/{asset}` : Détails
  - `PUT /api/assets/{asset}` : Modifier
  - `DELETE /api/assets/{asset}` : Supprimer
  - `GET /api/assets/types` : Types utilisés

### 2. Historique du patrimoine

#### Suivi dans le temps
- Enregistrement de snapshots du patrimoine
- **Données enregistrées** :
  - Total des actifs
  - Total des passifs
  - Patrimoine net
  - Date d'enregistrement
- Graphique d'évolution du patrimoine
- Visualisation des tendances

- **API** :
  - `GET /api/wealth-history` : Liste historique (filtres from/to)
  - `POST /api/wealth-history/record` : Enregistrer snapshot actuel
  - `DELETE /api/wealth-history/{wealthHistory}` : Supprimer entrée

### 3. Plans d'épargne

#### Objectifs mensuels
- **Page** : `/epargne`
- **Fonctionnalités** :
  - Définition d'objectifs d'épargne mensuels
  - **Résumé** :
    - Épargne prévue totale
    - Épargne réelle totale (calculée automatiquement)
    - Écart en euros et pourcentage
  - **Calcul automatique** :
    - Épargne réelle = Revenus - Dépenses du mois
    - Basé sur les budgets et dépenses réelles
  - **Tableau historique** :
    - Liste par mois
    - Épargne prévue vs réelle
    - Écart calculé
    - Taux de réalisation avec barre de progression
    - Actions : Modifier l'objectif
  - Graphique d'évolution mensuelle

- **API** :
  - `GET /api/savings` : Liste des plans (filtre mois optionnel)
  - `GET /api/savings/{savingsPlan}` : Détails
  - `PUT /api/savings/{savingsPlan}` : Modifier objectif

---

## 📊 Statistiques & Visualisations

### 1. Dashboard et tableaux de bord

#### Résumé mensuel (Dashboard)
- Budget total prévu
- Total des dépenses réelles
- Économies ou surplus
- Nombre total de dépenses
- Variance en euros et pourcentage

#### Détails par catégorie
- Montant planifié vs réel
- Écart et pourcentage de réalisation
- Nombre de dépenses par catégorie
- Tri par montant dépensé

#### Détails par sous-catégorie
- Décomposition complète
- Filtrage par catégorie parent
- Identification des postes problématiques

### 2. Graphiques et visualisations

#### Distribution des dépenses (Pie Chart)
- Répartition par catégorie principale
- Pourcentages calculés
- Couleurs distinctes par catégorie
- Tooltip avec montants détaillés
- **API** : `GET /api/budgets/{budget}/stats/expense-distribution`

#### Dépenses par catégorie (Bar Chart)
- Comparaison prévu vs réel
- Identification visuelle des dépassements
- **API** : `GET /api/budgets/{budget}/stats/by-category`

#### Dépenses par tag (Bar Chart)
- Utilise les couleurs personnalisées des tags
- Total des dépenses par tag
- Nombre de dépenses associées
- Affichage uniquement si des dépenses ont des tags
- **API** : `GET /api/budgets/{budget}/stats/by-tag`

#### Évolution du patrimoine (Line Chart)
- Courbe du patrimoine net dans le temps
- Séparation actifs et passifs
- Filtrage par période (from/to)
- Tendances et variations
- **API** : `GET /stats/wealth-evolution`

#### Progression de l'épargne
- Suivi mensuel objectif vs réalisation
- Taux de réalisation visuel
- Identification des mois performants

### 3. Endpoints statistiques

- `GET /api/budgets/{budget}/stats/summary` : Résumé global
- `GET /api/budgets/{budget}/stats/by-category` : Stats par catégorie
- `GET /api/budgets/{budget}/stats/by-subcategory` : Stats par sous-catégorie
- `GET /api/budgets/{budget}/stats/by-tag` : Stats par tag
- `GET /api/budgets/{budget}/stats/expense-distribution` : Distribution pour graphique

---

## 🔔 Notifications & Paramètres

### 1. Système de notifications

#### Types de notifications
- **Dépassement de budget** :
  - Déclenchée quand une sous-catégorie dépasse le montant prévu
  - Seuil configurable (par défaut 100%)
  - Affiche le pourcentage de dépassement
- **Objectif d'épargne atteint** :
  - Notification quand l'épargne réelle ≥ objectif
  - Affiche le montant épargné

#### Gestion des notifications
- **Page** : `/notifications`
- **Fonctionnalités** :
  - Liste paginée des notifications
  - **Filtres** :
    - Toutes
    - Non lues uniquement
    - Lues uniquement
  - **Actions** :
    - Marquer comme lu (individuellement)
    - Marquer tout comme lu
    - Supprimer notification
    - Tout effacer
  - Badge avec nombre de notifications non lues
  - Clic sur notification navigate vers le budget concerné
  - Affichage du détail (titre, message, données)

- **API** :
  - `GET /api/notifications` : Liste (pagination, filtres)
  - `GET /api/notifications/unread-count` : Compteur non lues
  - `PUT /api/notifications/{notification}/mark-read` : Marquer lu
  - `PUT /api/notifications/mark-all-read` : Tout marquer lu
  - `DELETE /api/notifications/{notification}` : Supprimer
  - `DELETE /api/notifications` : Tout supprimer

### 2. Paramètres de notifications

#### Configuration
- **Accès** : Via page profil (`/profile`)
- **Paramètres disponibles** :
  - **Dépassement de budget** :
    - Activation/désactivation
    - Seuil de déclenchement (pourcentage)
    - Défaut : 100% (alerte dès dépassement)
  - **Objectif d'épargne** :
    - Activation/désactivation
    - Alerte quand objectif atteint

- **API** :
  - `GET /api/notification-settings` : Récupérer préférences
  - `PUT /api/notification-settings` : Mettre à jour

### 3. Composant NotificationBell

- Icône cloche dans la barre de navigation
- Badge avec nombre de notifications non lues
- Clic ouvre le panneau de notifications
- Mise à jour en temps réel du compteur

---

## 🏗️ Architecture Technique

### Stack technologique

#### Backend (Laravel 11)
```
PHP 8.3
├── Laravel 11
├── MySQL 8.0
├── Redis (cache & sessions)
└── Laravel Sanctum (auth API)
```

#### Frontend (Vue 3)
```
TypeScript
├── Vue 3 (Composition API)
├── Vite (bundler)
├── Pinia (state management)
├── Vue Router
├── TailwindCSS (styling)
├── Chart.js (visualisations)
├── VeeValidate + Zod (validation)
└── Axios (HTTP client)
```

### Structure du projet

```
projetPersoBudget/
├── backend/              # API Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   └── Policies/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── config/
│
├── frontend/             # SPA Vue
│   ├── src/
│   │   ├── api/         # Clients API
│   │   ├── components/  # Composants Vue
│   │   ├── pages/       # Pages/Routes
│   │   ├── stores/      # Pinia stores
│   │   ├── types/       # Types TypeScript
│   │   ├── schemas/     # Validation Zod
│   │   └── router/      # Configuration routes
│   └── vite.config.ts
│
└── docker-compose.yml   # Configuration Docker
```

### Modèle de données

#### Entités principales
- **Users & Roles** : Authentification et autorisations
- **BudgetTemplates** : Modèles réutilisables
  - TemplateCategories
  - TemplateSubcategories
- **Budgets** : Budgets mensuels (snapshots)
  - BudgetCategories
  - BudgetSubcategories
- **Expenses** : Transactions individuelles
- **Tags** : Étiquettes pour dépenses
- **RecurringExpenses** : Dépenses automatiques
- **Assets** : Actifs et passifs
- **WealthHistory** : Historique patrimoine
- **SavingsPlans** : Objectifs d'épargne
- **Notifications** : Alertes utilisateur
- **NotificationSettings** : Préférences

#### Relations clés
```
User (1) ──→ (*) BudgetTemplate ──→ (*) TemplateCategory ──→ (*) TemplateSubcategory
     │                                                                    │
     │                                                                    ↓
     └──→ (*) Budget ──→ (*) BudgetCategory ──→ (*) BudgetSubcategory ──→ (*) Expense
                                                                                │
User (1) ──→ (*) Tag ←──────────────────────────────────────────────── (*)────┘
     │                                                           (Many-to-Many)
     ├──→ (*) Asset
     ├──→ (*) SavingsPlan
     ├──→ (*) WealthHistory
     ├──→ (*) RecurringExpense
     └──→ (*) Notification
```

### Patterns architecturaux

#### Backend
- **MVC** : Séparation logique présentation/métier/données
- **Repository Pattern** : Eloquent ORM comme repositories
- **Policy Pattern** : Autorisations via policies Laravel
- **Middleware** :
  - Conversion automatique snake_case ↔ camelCase
  - Authentification Sanctum
  - Rate limiting
- **Resource Controllers** : CRUD standardisés

#### Frontend
- **Composition API** : Vue 3 avec `<script setup>`
- **State Management** : Pinia stores pour chaque domaine
- **Form Validation** : VeeValidate + Zod schemas
- **API Layer** : Clients axios centralisés
- **Component-driven** : Composants réutilisables

### Conventions de nommage

#### Backend (PHP/Laravel)
- Database columns : `snake_case`
- Models : `PascalCase`
- Methods : `camelCase`
- Routes : `/api/resource-name`

#### Frontend (TypeScript/Vue)
- Variables/Props : `camelCase`
- Components : `PascalCase`
- Files : `PascalCase.vue`
- Types/Interfaces : `PascalCase`

#### Conversion automatique
- **Middleware Request** : `camelCase` → `snake_case`
- **Middleware Response** : `snake_case` → `camelCase`
- Transparent pour le développeur frontend

### Gestion des montants

Tous les montants financiers :
- **Database** : Stockés en **cents** (integer/bigInteger)
- **Naming** : Suffixe `_cents` (ex: `amount_cents`)
- **API** : Transfert en cents
- **Frontend** :
  - Affichage : Conversion en euros (`cents / 100`)
  - Input : Conversion en cents (`euros * 100`)
- **Avantages** : Évite les erreurs de précision des nombres décimaux

### Sécurité

- **Authentification** : Laravel Sanctum avec tokens Bearer
- **Autorisations** : Policies Laravel (ownership checks)
- **Validation** : Backend (FormRequest) + Frontend (Zod)
- **CORS** : Configuration stricte
- **Rate Limiting** : 60 req/min sur routes auth
- **Soft Deletes** : Conservation historique utilisateurs
- **Password Hashing** : Bcrypt
- **CSRF Protection** : Désactivé pour API (tokens Bearer)

### Performance

- **Eager Loading** : `->with()` pour éviter N+1 queries
- **Pagination** : 12-50 items selon contextes
- **Indexes** : Sur colonnes fréquemment filtrées
- **Caching** : Redis pour sessions et cache applicatif
- **Code Splitting** : Lazy loading des routes Vue
- **Image Optimization** : (à implémenter)

---

## 📝 Résumé des fonctionnalités

### Par domaine

| Domaine | Fonctionnalités | Pages | API Endpoints |
|---------|----------------|-------|---------------|
| **Authentification** | Inscription, Connexion, Reset password, Profil | 4 | 7 |
| **Budgets & Templates** | Templates, Budgets mensuels, Comparaison, PDF | 3 | 14 |
| **Dépenses** | CRUD, Import/Export CSV, Tags, Récurrentes | 3 | 16 |
| **Patrimoine** | Actifs/Passifs, Historique, Épargne | 2 | 9 |
| **Statistiques** | Graphiques, Résumés, Distribution | - | 6 |
| **Notifications** | Alertes, Paramètres | 1 | 6 |
| **Administration** | Gestion utilisateurs | 1 | 6 |

### Totaux

- **15 pages** frontend
- **~74 endpoints** API
- **16 modèles** de données
- **24 migrations** database
- **12 stores** Pinia
- **30+ composants** Vue réutilisables

---

## 🎯 Compte de démonstration

**Email** : `demo@budgetmanager.local`
**Mot de passe** : `password`

**Contenu de démo** :
- 1 template avec 7 catégories
- 3 budgets mensuels (août, septembre, octobre 2025)
- ~48 dépenses réparties
- 4 actifs patrimoniaux
- Plans d'épargne configurés
- Dépenses récurrentes actives

---

## 📞 Support & Développement

**Version** : 1.0.0
**Environnement** : Docker (compose)
**Ports** :
- Frontend : `5173`
- Backend : `8080`
- MySQL : `3306`
- Redis : `6379`
- Mailhog : `8025`

**Commandes utiles** :
```bash
make up          # Démarrer les conteneurs
make down        # Arrêter les conteneurs
make migrate     # Lancer les migrations
make seed        # Insérer les données de démo
make fresh       # Reset complet avec démo
make test        # Lancer les tests backend
```

---

*Document généré le 2026-01-02 - Budget Manager v1.0.0*
