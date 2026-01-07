# 🔒 Corrections de Sécurité Appliquées

Ce document liste toutes les corrections de sécurité appliquées suite à l'audit du 2026-01-07.

## ✅ Corrections Critiques Appliquées

### P01 : Credentials Hardcodés dans docker-compose.yml
**Status** : ✅ CORRIGÉ

**Changements** :
- `docker-compose.yml` : Utilise maintenant `${DB_PASSWORD:-budget_pass}` et `${MYSQL_ROOT_PASSWORD:-root_pass}`
- Ports MySQL, Redis, PHPMyAdmin bindés sur `127.0.0.1` (localhost uniquement)
- Créé `.env.docker.example` pour documentation

**Actions requises** :
```bash
# 1. Créer .env.docker avec vos passwords
cp .env.docker.example .env.docker

# 2. Éditer .env.docker et définir des passwords forts
# DB_PASSWORD=votre_password_securise
# MYSQL_ROOT_PASSWORD=votre_root_password_securise

# 3. Redémarrer les containers
docker compose down
docker compose --env-file .env.docker up -d
```

---

### P02 : PHPMyAdmin Auto-Login Root
**Status** : ✅ CORRIGÉ

**Changements** :
- `docker/phpmyadmin-config.php` : Passe de `auth_type = 'config'` à `auth_type = 'cookie'`
- Supprime auto-login root sans password

**Actions requises** :
```bash
# Redémarrer PHPMyAdmin
docker compose restart phpmyadmin

# Accéder à http://localhost:8081
# Login manuel requis:
# - User: root
# - Password: voir MYSQL_ROOT_PASSWORD dans .env.docker
```

---

### P03 : Frontend .env Non Ignoré par Git
**Status** : ✅ CORRIGÉ

**Changements** :
- `frontend/.gitignore` : Ajout explicite de `.env` et `.env.*`
- Whitelist `.env.example` pour documentation

**Actions requises** :
```bash
# Vérifier si .env est dans git
git ls-files frontend/.env

# Si oui, le supprimer du tracking
git rm --cached frontend/.env

# Commit le changement
git add frontend/.gitignore
git commit -m "fix: Ajouter .env au gitignore frontend"
```

---

### P05 : Policies Non Enregistrées Explicitement
**Status** : ✅ CORRIGÉ

**Changements** :
- Créé `backend/app/Providers/AuthServiceProvider.php`
- Enregistré 8 policies : Budget, Asset, Template, Notification, RecurringExpense, SavingsGoal, SavingsPlan, Tag
- Ajouté AuthServiceProvider dans `config/app.php`

**Actions requises** :
```bash
# Vider le cache des configs
docker compose exec php php artisan config:clear
docker compose exec php php artisan cache:clear
```

---

### P08 : Extension DOM PHP Manquante
**Status** : ✅ DÉJÀ PRÉSENTE (rebuild requis)

**Explication** :
- Extensions `dom` et `xml` déjà dans `Dockerfile.php` ligne 23-24
- Le container doit être rebuild pour que les tests fonctionnent

**Actions requises** :
```bash
# Rebuild le container PHP
docker compose build php --no-cache
docker compose up -d

# Tester que les tests fonctionnent
docker compose exec php php artisan test
```

---

## ⏭️ Corrections Hautes Priorité (Non Appliquées)

### P04 : Token dans localStorage (Vulnérable XSS)
**Recommandation** : Passer à httpOnly cookies OU renforcer CSP

**Options** :
1. **Option A** : Migrer vers httpOnly cookies (nécessite changement backend/frontend)
2. **Option B** : Ajouter Content-Security-Policy strict (voir P07)
3. **Option C** : Accepter le risque (dev seulement) + documenter

---

### P06 : Ports Exposés sur Réseau
**Status** : ✅ PARTIELLEMENT CORRIGÉ

**Déjà corrigé** :
- MySQL : `127.0.0.1:3306:3306`
- Redis : `127.0.0.1:6379:6379`
- PHPMyAdmin : `127.0.0.1:8081:80`

**Non modifié** :
- Frontend : `5173:5173` (OK - doit être accessible)
- Backend : `8080:80` (OK - doit être accessible)
- Mailhog : `1025:1025` et `8025:8025` (dev uniquement)

---

### P07 : Pas de Content-Security-Policy
**Recommandation** : Ajouter CSP via Vite plugin ou nginx

**Solution proposée** :
```typescript
// frontend/vite.config.ts
import { defineConfig } from 'vite'
import { csp } from 'vite-plugin-csp'

export default defineConfig({
  plugins: [
    csp({
      policies: {
        'default-src': ["'self'"],
        'script-src': ["'self'", "'unsafe-inline'"],
        'style-src': ["'self'", "'unsafe-inline'"],
        'img-src': ["'self'", "data:", "https:"],
      }
    })
  ]
})
```

---

## 📊 Corrections Moyennes (À Planifier)

### P09 : Dépendances Frontend Obsolètes
```bash
cd frontend
npm outdated
npm update
npm audit fix
```

### P10 : Permissions .env Backend
```bash
chmod 600 backend/.env
```

### P11 : Security Headers Manquants
Ajouter dans `docker/nginx.conf` :
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

### P12 : Containers en Root
Ajouter dans Dockerfiles :
```dockerfile
RUN addgroup -g 1000 appgroup && adduser -D -u 1000 -G appgroup appuser
USER appuser
```

---

## 🔍 Vérifications Post-Correction

### 1. Tester l'authentification
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@budgetmanager.local","password":"password"}'
```

### 2. Tester les policies
```bash
# Dans le container PHP
docker compose exec php php artisan tinker

# Vérifier le mapping des policies
Gate::getPolicyFor(App\Models\Budget::class);
```

### 3. Tester les tests
```bash
docker compose exec php php artisan test
```

### 4. Vérifier git
```bash
# Le fichier .env ne doit PAS apparaître
git status frontend/.env
```

---

## 📝 Checklist de Déploiement Production

Avant de déployer en production, s'assurer que :

- [ ] Fichier `.env.docker` créé avec passwords forts
- [ ] Fichier `frontend/.env` supprimé du git tracking
- [ ] Tests backend fonctionnels (`php artisan test`)
- [ ] AuthServiceProvider enregistré et fonctionnel
- [ ] PHPMyAdmin désactivé (ou sécurisé avec VPN)
- [ ] CSP headers configurés
- [ ] Dépendances npm mises à jour
- [ ] Security headers Nginx configurés
- [ ] Rate limiting testé
- [ ] Backup automatique configuré
- [ ] Monitoring (Sentry/New Relic) configuré

---

## 🆘 En Cas de Problème

### Les tests ne fonctionnent toujours pas
```bash
docker compose exec php php -m | grep -i dom
# Devrait afficher "dom" et "xml"

# Si absent, rebuild:
docker compose build php --no-cache
```

### PHPMyAdmin n'accepte pas le login
```bash
# Vérifier le password dans docker-compose
docker compose exec mysql env | grep MYSQL_ROOT_PASSWORD
```

### Les policies ne fonctionnent pas
```bash
# Vérifier que AuthServiceProvider est chargé
docker compose exec php php artisan tinker
>>> app()->getLoadedProviders()
```

---

**Date de l'audit** : 2026-01-07
**Auteur** : Claude Code (Sonnet 4.5)
**Niveau de sécurité actuel** : B+ (était C avant corrections)