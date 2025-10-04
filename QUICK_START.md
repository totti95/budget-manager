# 🚀 Quick Start - Budget Manager

## Générer les données de test

### ⚡ Méthode rapide (recommandée)

```bash
./reset-data.sh fresh
```

Ou avec Makefile :

```bash
make fresh
```

### 🔧 Méthode manuelle

```bash
docker compose exec php php artisan migrate:fresh --seed
```

---

## 📊 Données créées

✅ **1 utilisateur démo**
- Email: `demo@budgetmanager.local`
- Password: `password`

✅ **1 template de budget** avec 7 catégories et 20+ sous-catégories

✅ **3 budgets mensuels** avec données réalistes

✅ **150+ dépenses** réparties sur 3 mois

✅ **4 actifs patrimoniaux** (comptes, épargne, immobilier)

---

## 🎯 Se connecter

1. Ouvrir : http://localhost:5173
2. Email : `demo@budgetmanager.local`
3. Password : `password`

---

## 🔄 Vider les données

### Tout supprimer et régénérer

```bash
./reset-data.sh fresh
```

### Vider sans régénérer

```bash
./reset-data.sh wipe
```

### Supprimer TOUT (conteneurs + volumes)

```bash
make clean
make init
```

---

## 📖 Plus d'informations

- **Guide complet** : [DONNEES_TEST.md](./DONNEES_TEST.md)
- **README principal** : [README.md](./README.md)
- **Script helper** : `./reset-data.sh help`

---

## 🛠️ Commandes utiles

```bash
# Voir l'état de la base
./reset-data.sh status

# Sauvegarder avant de tester
./reset-data.sh backup

# Restaurer un backup
./reset-data.sh restore backup_YYYYMMDD_HHMMSS.sql

# Voir les logs
make logs

# Tests backend
make test
```

---

**Dernière mise à jour** : 2025-10-04
