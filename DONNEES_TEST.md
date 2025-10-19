# Gestion des données de test

Ce guide explique comment gérer les données de test (seed) pour le projet Budget Manager.

## 📊 Données générées par le seeder

Le seeder (`backend/database/seeders/DatabaseSeeder.php`) crée automatiquement :

### Utilisateur démo
- **Email** : `demo@budgetmanager.local`
- **Mot de passe** : `password`

### Template de budget par défaut
- **7 catégories** principales :
  1. 🏠 Logement
  2. 🍕 Alimentation
  3. 🚗 Transport
  4. 💡 Factures & Abonnements
  5. 🎉 Loisirs
  6. 🏥 Santé
  7. 💰 Épargne & Investissement

- Chaque catégorie contient **2-3 sous-catégories** avec montants prévus

### Budgets mensuels
- **3 budgets** générés :
  - Mois actuel
  - Mois précédent
  - Il y a 2 mois

### Dépenses d'exemple
- **15-25 dépenses** par budget
- Réparties dans différentes catégories
- Dates et montants réalistes

### Patrimoine (Assets)
- **4 actifs** :
  1. Compte courant (~5000€)
  2. Livret A (~15000€)
  3. PEA (~8000€)
  4. Résidence principale (~250000€)

---

## 🔄 Commandes de gestion

### Générer les données de test (méthode recommandée)

```bash
# Via Makefile (recommandé)
make fresh

# Ou directement avec Docker Compose
docker compose exec php php artisan migrate:fresh --seed

# Ou sans Docker (si PHP local)
cd backend
php artisan migrate:fresh --seed
```

**Ce que fait cette commande :**
1. ✅ Supprime toutes les tables existantes
2. ✅ Recrée toutes les tables (migrations)
3. ✅ Insert les données de démonstration (seeders)

---

### Vider uniquement les données (garder la structure)

```bash
# Via Docker Compose
docker compose exec php php artisan db:wipe

# Puis recréer les tables
docker compose exec php php artisan migrate
```

---

### Ajouter plus de données sans tout effacer

```bash
# Exécuter uniquement les seeders (sans reset)
docker compose exec php php artisan db:seed

# ⚠️ Attention : cela peut créer des doublons !
```

---

### Vider TOUTES les données (reset complet)

```bash
# Via Makefile
make clean          # Supprime conteneurs + volumes Docker
make init           # Réinstalle tout de zéro

# Ou manuellement
docker compose down -v  # Supprime volumes (données perdues !)
docker compose up -d
make migrate
make seed
```

---

## 🎯 Scénarios d'usage

### Scénario 1 : Je veux repartir de zéro avec données fraîches

```bash
make fresh
```

Ou :

```bash
docker compose exec php php artisan migrate:fresh --seed
```

---

### Scénario 2 : J'ai cassé mes données, je veux les restaurer

```bash
# Option 1 : Reset complet (le plus propre)
make fresh

# Option 2 : Vider et recréer
docker compose exec php php artisan db:wipe
docker compose exec php php artisan migrate --seed
```

---

### Scénario 3 : Je veux tester avec une base vide

```bash
# Créer les tables sans données
docker compose exec php php artisan migrate:fresh

# Puis connectez-vous et créez manuellement votre compte
```

---

### Scénario 4 : J'ai modifié le seeder et veux le tester

```bash
# Refresh complet
docker compose exec php php artisan migrate:fresh --seed

# Vérifier dans l'interface web que tout est OK
```

---

## 🔍 Vérifier l'état de la base de données

### Lister les tables

```bash
docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -e "SHOW TABLES;"
```

### Compter les enregistrements

```bash
# Users
docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -e "SELECT COUNT(*) FROM users;"

# Budgets
docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -e "SELECT COUNT(*) FROM budgets;"

# Expenses
docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -e "SELECT COUNT(*) FROM expenses;"

# Assets
docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -e "SELECT COUNT(*) FROM assets;"
```

### Voir toutes les données d'une table

```bash
# Exemple : voir tous les utilisateurs
docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -e "SELECT id, name, email FROM users;"
```

### Accéder à phpMyAdmin

Vous pouvez aussi visualiser et gérer toutes les données via phpMyAdmin :

- **URL** : http://localhost:8081
- **Connexion automatique** en tant que root
- Interface graphique pour consulter, modifier et exporter les données

---

## 🛠️ Personnaliser les données de test

### Modifier le seeder

Éditez le fichier : `backend/database/seeders/DatabaseSeeder.php`

```php
// Exemple : changer l'email du compte démo
$demoUser = User::create([
    'name' => 'Utilisateur Démo',
    'email' => 'votre.email@exemple.com', // ← Modifier ici
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
```

Puis rechargez :

```bash
make fresh
```

---

### Créer votre propre seeder

```bash
# Créer un nouveau seeder
docker compose exec php php artisan make:seeder CustomDataSeeder

# Éditer : backend/database/seeders/CustomDataSeeder.php
# Puis l'appeler dans DatabaseSeeder.php :
$this->call([
    CustomDataSeeder::class,
]);
```

---

## ⚠️ Précautions

### Environnement de production

**JAMAIS** exécuter `migrate:fresh` ou `db:wipe` en production !

Ces commandes **suppriment toutes les données**.

En production, utilisez uniquement :
```bash
php artisan migrate  # Ajoute nouvelles migrations seulement
```

---

### Backup avant reset

Si vous avez des données importantes :

```bash
# Backup MySQL
docker compose exec mysql mysqldump -u budget_user -pbudget_pass budget_manager > backup.sql

# Restaurer plus tard
docker compose exec -T mysql mysql -u budget_user -pbudget_pass budget_manager < backup.sql
```

---

## 📝 Récapitulatif des commandes

| Action | Commande | Détruit les données ? |
|--------|----------|----------------------|
| Générer données test | `make fresh` | ✅ Oui (complet) |
| Seeders seulement | `make seed` | ❌ Non (ajoute) |
| Vider base | `docker compose down -v` | ✅ Oui (tout) |
| Migrations seulement | `make migrate` | ❌ Non |
| Voir l'état | `make artisan CMD="migrate:status"` | ❌ Non |

---

## 🎓 Exemple de workflow quotidien

```bash
# Matin : démarrer le projet
make up

# Tester une fonctionnalité
# ... modifications de code ...

# Besoin de données fraîches ?
make fresh

# Fin de journée : tout arrêter
make down
```

---

**Généré le** : 2025-10-04

---

## 🚀 Script Helper : reset-data.sh

Un script bash pratique pour simplifier la gestion des données :

```bash
# Rendre le script exécutable (une seule fois)
chmod +x reset-data.sh

# Utiliser le script
./reset-data.sh [commande]
```

### Commandes disponibles

| Commande | Description | Détruit données ? |
|----------|-------------|-------------------|
| `fresh` ou `reset` | Régénérer données fraîches | ✅ Oui |
| `seed` | Ajouter données (sans reset) | ❌ Non (peut doubler) |
| `wipe` | Vider toutes les données | ✅ Oui |
| `migrate` | Créer tables sans données | ❌ Non |
| `status` | Voir nombre d'enregistrements | ❌ Non |
| `backup` | Sauvegarder la base | ❌ Non |
| `restore FILE` | Restaurer un backup | ✅ Oui |
| `help` | Aide | ❌ Non |

### Exemples d'utilisation

```bash
# Voir l'état actuel
./reset-data.sh status

# Générer données fraîches (le plus courant)
./reset-data.sh fresh

# Sauvegarder avant de faire des tests
./reset-data.sh backup

# Restaurer le backup
./reset-data.sh restore backup_20251004_123456.sql
```

---

## 📸 Exemple de sortie

```
🔄 Budget Manager - Gestion des données de test
================================================

📊 État de la base de données

=== Nombre d'enregistrements ===

👤 Users      : 1
📋 Templates  : 1
📅 Budgets    : 3
💸 Expenses   : 158
💰 Assets     : 4
```

---

## 🎯 Cas d'usage réels

### Vous avez ajouté une nouvelle fonctionnalité

```bash
# Tester avec données fraîches
./reset-data.sh fresh

# Tester dans le navigateur
# Si tout fonctionne, commiter le code
```

### Vous voulez faire des tests destructifs

```bash
# Backup d'abord
./reset-data.sh backup

# Faire vos tests...
# Si ça se passe mal :
./reset-data.sh fresh
```

### La base est dans un état incohérent

```bash
# Solution rapide : tout refaire
./reset-data.sh fresh
```

### Vous développez un nouveau seeder

```bash
# 1. Modifier backend/database/seeders/DatabaseSeeder.php
# 2. Tester
./reset-data.sh fresh

# 3. Vérifier
./reset-data.sh status
```

---

## 🔗 Liens utiles

- **phpMyAdmin** : http://localhost:8081 (connexion automatique)
- **Makefile** : `make fresh` fait la même chose que `./reset-data.sh fresh`
- **Laravel Docs** : https://laravel.com/docs/11.x/seeding
- **MySQL Docs** : https://dev.mysql.com/doc/

