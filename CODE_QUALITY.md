# Guide de Qualité du Code - Budget Manager

Ce document explique comment utiliser les outils de qualité du code (linters, formatters, hooks git) dans le projet Budget Manager.

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Commandes Makefile](#commandes-makefile)
3. [Hooks Pre-commit](#hooks-pre-commit)
4. [GitHub Actions CI](#github-actions-ci)
5. [Configuration des outils](#configuration-des-outils)
6. [Dépannage](#dépannage)

---

## Vue d'ensemble

Le projet utilise plusieurs outils pour maintenir la qualité du code :

### Backend (Laravel/PHP)
- **Laravel Pint** : Formateur de code suivant PSR-12
- **Pest** : Framework de tests

### Frontend (Vue/TypeScript)
- **ESLint** : Détection de problèmes de code
- **Prettier** : Formatage automatique
- **TypeScript** : Vérification des types

### Automatisation
- **Husky** : Gestion des hooks git
- **lint-staged** : Exécution des linters sur les fichiers modifiés
- **GitHub Actions** : CI/CD automatique

---

## Commandes Makefile

Toutes les commandes doivent être exécutées depuis la racine du projet.

### Commandes générales

```bash
make help          # Affiche toutes les commandes disponibles
make up            # Démarre tous les conteneurs Docker
make down          # Arrête tous les conteneurs
make init          # Initialisation complète du projet (première fois)
```

### Commandes de qualité du code

#### Vérification (check only - pas de modification)

```bash
# Vérifier TOUT
make lint-all      # Lance tous les linters (backend + frontend)
                   # ✓ Backend : Laravel Pint (PSR-12)
                   # ✓ Frontend : ESLint + Prettier

# Backend uniquement
make lint-back     # Vérifie le style PHP avec Laravel Pint
                   # Exemple de sortie :
                   # ✓ PASS ................. 109 files

# Frontend uniquement
make lint-front    # Vérifie le code JavaScript/TypeScript avec ESLint
                   # Détecte : variables non utilisées, erreurs TypeScript, etc.

make format-front  # Vérifie le formatage avec Prettier
                   # Détecte : indentation, quotes, virgules, etc.
```

#### Correction automatique (auto-fix)

```bash
# Corriger TOUT
make fix-all       # Corrige automatiquement tous les problèmes
                   # ⚠️ Modifie vos fichiers !

# Backend uniquement
make lint-back-fix # Corrige automatiquement le style PHP
                   # Applique PSR-12, ordonne les imports, etc.

# Frontend uniquement
make lint-front-fix   # Corrige les problèmes ESLint auto-corrigeables
                      # Ajoute des points-virgules, supprime imports inutilisés, etc.

make format-front-fix # Formate tous les fichiers avec Prettier
                      # Indentation, largeur de ligne, quotes, etc.
```

### Autres commandes utiles

```bash
# Tests
make test          # Lance les tests backend (Pest)

# Base de données
make migrate       # Lance les migrations
make seed          # Insère les données de démo
make fresh         # Réinitialise la DB avec données fraîches

# Conteneurs
make shell-php     # Ouvre un shell dans le conteneur PHP
make shell-node    # Ouvre un shell dans le conteneur Node
make logs          # Affiche les logs de tous les conteneurs

# Construction
make build         # Build le frontend pour production
```

---

## Hooks Pre-commit

### Qu'est-ce qu'un hook pre-commit ?

Un **hook pre-commit** est un script qui s'exécute **automatiquement avant chaque commit git**. Il permet de :

- ✅ Vérifier la qualité du code avant qu'il n'entre dans l'historique
- ✅ Détecter les erreurs tôt (avant la CI)
- ✅ Maintenir un style de code cohérent dans l'équipe
- ✅ Éviter de perdre du temps avec des échecs CI

### Comment ça fonctionne dans ce projet ?

#### 1. Déclenchement

```bash
git add frontend/src/components/MyComponent.vue
git commit -m "feat: add new component"
```

Dès que vous lancez `git commit`, le hook **s'exécute automatiquement** :

```
Running lint-staged on frontend files...
✔ Preparing lint-staged...
✔ Running tasks for staged files...
✔ Applying modifications from tasks...
✔ Cleaning up temporary files...

Running Laravel Pint on staged PHP files...
✓ Fixed 2 style issues

[master abc1234] feat: add new component
 1 file changed, 50 insertions(+)
```

#### 2. Vérifications effectuées

**Pour les fichiers frontend (`.js`, `.ts`, `.vue`)** :
- ESLint s'exécute et corrige automatiquement ce qu'il peut
- Prettier reformate le code (indentation, quotes, etc.)

**Pour les fichiers backend (`.php`)** :
- Laravel Pint applique le style PSR-12 et corrige automatiquement

#### 3. Résultats possibles

**✅ Cas 1 : Tout est OK (ou corrigé automatiquement)**
```bash
✓ Les fichiers sont conformes
✓ Le commit est créé
```

**⚠️ Cas 2 : Corrections automatiques appliquées**
```bash
✓ ESLint a corrigé 3 problèmes
✓ Prettier a reformaté 2 fichiers
✓ Les fichiers modifiés sont automatiquement ajoutés au commit
✓ Le commit est créé avec les corrections
```

**❌ Cas 3 : Erreurs non auto-corrigeables**
```bash
✖ ESLint found 2 errors that require manual fixes:
  - src/components/MyComponent.vue:15:34
    error: 'undefined variable'

✖ Le commit est ANNULÉ
```

Vous devez corriger manuellement, puis recommiter.

#### 4. Où est le code du hook ?

Le hook est dans le fichier : **`frontend/.husky/pre-commit`**

```bash
#!/usr/bin/env sh

# Get the root directory of the git repo
REPO_ROOT=$(git rev-parse --show-toplevel)

# Navigate to frontend directory
cd "$REPO_ROOT/frontend"

# Run lint-staged on frontend files
echo "Running lint-staged on frontend files..."
npx lint-staged

# Navigate to backend directory
cd "$REPO_ROOT/backend"

# Run Pint on staged PHP files
STAGED_PHP_FILES=$(git diff --cached --name-only --diff-filter=ACM -- backend/ | grep "\.php$" | sed 's|^backend/||' || true)

if [ -n "$STAGED_PHP_FILES" ]; then
  echo "Running Laravel Pint on staged PHP files..."
  docker compose run --rm php ./vendor/bin/pint $STAGED_PHP_FILES
  git add $STAGED_PHP_FILES
fi
```

**Comment ça marche ?**
1. Récupère la racine du repo git
2. Va dans `frontend/` et lance `lint-staged` (qui lit `lint-staged.config.js`)
3. Va dans `backend/` et vérifie les fichiers PHP modifiés
4. Lance Pint sur ces fichiers si besoin
5. Ajoute les fichiers corrigés au commit

#### 5. Configuration de lint-staged

Le fichier **`frontend/lint-staged.config.js`** définit quelles commandes exécuter :

```javascript
module.exports = {
  // Pour les fichiers code JavaScript/TypeScript/Vue
  '*.{js,jsx,ts,tsx,vue}': [
    'eslint --fix',      // Corrige les problèmes ESLint
    'prettier --write'   // Reformate avec Prettier
  ],
  // Pour les autres fichiers
  '*.{json,md,html,css}': [
    'prettier --write'   // Reformate uniquement
  ]
}
```

**Important** : lint-staged exécute les commandes **uniquement sur les fichiers stagés** (ajoutés avec `git add`), pas sur tout le projet. C'est plus rapide !

### Bypasser le hook (urgence uniquement)

**⚠️ À utiliser EXCEPTIONNELLEMENT (urgence de production, etc.)**

```bash
git commit --no-verify -m "fix: hotfix urgent"
```

Le flag `--no-verify` saute le pre-commit hook.

**Pourquoi c'est déconseillé ?**
- Le code peut ne pas passer la CI
- Incohérence de style dans l'historique
- Risque d'introduire des bugs

---

## GitHub Actions CI

### Qu'est-ce que la CI ?

La **CI (Continuous Integration)** exécute automatiquement les tests et vérifications **à chaque push ou pull request** sur GitHub.

### Quand la CI se déclenche-t-elle ?

```yaml
on:
  push:
    branches: [ master ]    # À chaque push sur master
  pull_request:
    branches: [ master ]    # À chaque PR vers master
```

### Les 6 jobs de la CI

La CI exécute 6 jobs en parallèle pour vérifier votre code :

```
backend-lint (30s) ──→ backend-tests (2-3min) ──┐
                                                  ├─→ docker-build (3-5min)
frontend-lint (30s) ──┐                          │
                       ├─→ frontend-build (1-2min) ┘
frontend-format (20s) ─┘
```

#### 1️⃣ **backend-lint** (Laravel Pint)
```yaml
- Installe PHP 8.3
- Installe les dépendances Composer
- Lance : ./vendor/bin/pint --test
- ✓ Vérifie que le code suit PSR-12
- ❌ Échoue si des violations de style
```

#### 2️⃣ **backend-tests** (Pest)
```yaml
- Démarre PostgreSQL + Redis
- Installe les dépendances
- Lance les migrations
- Exécute : php artisan test
- ✓ Tous les tests passent
- ❌ Échoue si un test échoue
```

**Dépendance** : Ne s'exécute que si `backend-lint` réussit.

#### 3️⃣ **frontend-lint** (ESLint)
```yaml
- Installe Node.js 20
- Installe les dépendances npm
- Lance : npm run lint:check
- ✓ Pas d'erreurs ESLint
- ❌ Échoue si erreurs (warnings acceptés)
```

#### 4️⃣ **frontend-format** (Prettier)
```yaml
- Installe Node.js 20
- Installe les dépendances npm
- Lance : npm run format:check
- ✓ Tous les fichiers sont bien formatés
- ❌ Échoue si des fichiers mal formatés
```

#### 5️⃣ **frontend-build** (TypeScript + Vite)
```yaml
- Installe Node.js 20
- Installe les dépendances npm
- Lance : npm run build (vue-tsc + vite build)
- ✓ Build réussit sans erreurs TypeScript
- ❌ Échoue si erreurs de build
```

**Dépendance** : Ne s'exécute que si `frontend-lint` ET `frontend-format` réussissent.

#### 6️⃣ **docker-build** (Test d'intégration)
```yaml
- Build les images Docker
- Démarre tous les services (PHP, Node, MySQL, Redis, Nginx)
- Attend 30s que les services démarrent
- Teste le health check : curl http://localhost:8080/api/health
- ✓ L'application démarre correctement
- ❌ Échoue si l'app ne répond pas
```

**Dépendance** : Ne s'exécute que si `backend-tests` ET `frontend-build` réussissent.

### Durée totale de la CI

**~5-8 minutes** grâce à la parallélisation des jobs.

Sans parallélisation, ce serait ~10-12 minutes.

### Voir les résultats de la CI

1. Allez sur votre repo GitHub
2. Onglet **"Actions"**
3. Cliquez sur le workflow en cours
4. Vous verrez l'état de chaque job :
   - ✅ Vert = réussi
   - ❌ Rouge = échoué
   - 🟡 Jaune = en cours
   - ⚪ Gris = pas encore lancé (attend une dépendance)

### Que faire si la CI échoue ?

#### Exemple d'échec : backend-lint

```
❌ backend-lint
   ✓ Setup PHP
   ✓ Install Dependencies
   ❌ Run Laravel Pint
      Error: Found 3 style violations in AdminController.php
```

**Solution** :
```bash
# En local, corriger le problème
make lint-back-fix

# Vérifier
make lint-back

# Commiter et pusher
git add backend/
git commit -m "style: fix PHP style violations"
git push
```

La CI va se relancer automatiquement.

---

## Configuration des outils

### Laravel Pint (Backend)

**Fichier** : `backend/pint.json`

```json
{
  "preset": "psr12",       // Standard PSR-12 (Laravel officiel)
  "rules": {
    "concat_space": {
      "spacing": "one"     // Espaces autour des concaténations
    },
    "no_unused_imports": true,  // Supprimer imports inutilisés
    "single_quote": true,       // Utiliser des quotes simples
    "ordered_imports": {
      "sort_algorithm": "alpha" // Trier les imports
    }
    // ... autres règles
  },
  "exclude": [
    "vendor",              // Ne pas formater les dépendances
    "storage",
    "bootstrap/cache"
  ]
}
```

**Lancer manuellement** :
```bash
make lint-back-fix    # Corrige automatiquement
make lint-back        # Vérifie seulement
```

### Prettier (Frontend)

**Fichier** : `frontend/.prettierrc`

```json
{
  "semi": true,              // Points-virgules obligatoires
  "singleQuote": false,      // Double quotes (")
  "trailingComma": "es5",    // Virgules finales (arrays, objects)
  "printWidth": 100,         // Largeur maximale de ligne
  "tabWidth": 2,             // 2 espaces d'indentation
  "useTabs": false,          // Espaces (pas de tabs)
  "arrowParens": "always",   // Parenthèses autour des params fléchées
  "endOfLine": "lf",         // Unix line endings
  "bracketSpacing": true,    // Espaces dans les objets: { foo }
  "vueIndentScriptAndStyle": false  // Pas d'indentation dans <script> et <style>
}
```

**Fichier** : `frontend/.prettierignore`

```
node_modules
dist
dist-ssr
coverage
*.min.js
*.min.css
package-lock.json
```

**Lancer manuellement** :
```bash
make format-front-fix    # Formate automatiquement
make format-front        # Vérifie seulement
```

### ESLint (Frontend)

**Fichier** : `frontend/.eslintrc.cjs`

```javascript
module.exports = {
  root: true,
  extends: [
    'plugin:vue/vue3-essential',      // Règles Vue 3
    'eslint:recommended',             // Règles ESLint de base
    '@vue/eslint-config-typescript',  // Support TypeScript
    '@vue/eslint-config-prettier/skip-formatting'  // Délègue le formatage à Prettier
  ],
  rules: {
    'vue/multi-word-component-names': 'off',  // Autorise noms composants simples
    '@typescript-eslint/no-explicit-any': 'warn',  // any = warning (pas erreur)
    '@typescript-eslint/no-unused-vars': ['warn', {
      argsIgnorePattern: '^_',    // Ignorer args commençant par _
      varsIgnorePattern: '^_'     // Ignorer vars commençant par _
    }]
  }
}
```

**Lancer manuellement** :
```bash
make lint-front-fix    # Corrige automatiquement
make lint-front        # Vérifie seulement
```

---

## Dépannage

### Problème : Le hook ne s'exécute pas

**Symptôme** : Vous commitez mais le hook ne se lance pas.

**Solution 1** : Vérifier la config git
```bash
git config core.hooksPath
# Devrait afficher : frontend/.husky
```

Si vide ou différent :
```bash
git config core.hooksPath frontend/.husky
```

**Solution 2** : Vérifier que le hook est exécutable
```bash
ls -la frontend/.husky/pre-commit
# Devrait avoir le flag 'x' : -rwxr-xr-x

# Si pas exécutable :
chmod +x frontend/.husky/pre-commit
```

### Problème : "npx: command not found" dans le hook

**Symptôme** : Le hook échoue avec "npx: command not found"

**Cause** : Node.js n'est pas dans le PATH du shell

**Solution** :
```bash
# Vérifier que Node est installé
node --version
npm --version

# Si pas installé, installer Node.js (nvm recommandé)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 20
```

### Problème : Le hook est trop lent

**Symptôme** : Le commit prend 30+ secondes

**Cause** : Le hook lance Docker pour Pint, ce qui peut être lent

**Solution 1** : Installer Pint localement (plus rapide)
```bash
# Dans le hook, remplacer :
docker compose run --rm php ./vendor/bin/pint $STAGED_PHP_FILES

# Par :
./vendor/bin/pint $STAGED_PHP_FILES
```

**Solution 2** : Bypass temporairement
```bash
git commit --no-verify -m "message"
```

### Problème : Conflits entre Prettier et ESLint

**Symptôme** : Prettier formate le code, puis ESLint le casse

**Cause** : Règles conflictuelles entre les deux outils

**Solution** : Vérifier que la config ESLint utilise bien :
```javascript
'@vue/eslint-config-prettier/skip-formatting'
```

Cette config désactive les règles de formatage d'ESLint pour laisser Prettier gérer.

### Problème : "Permission denied" sur les fichiers créés

**Symptôme** : Impossible d'éditer des fichiers après les avoir créés

**Cause** : Fichiers créés via Docker avec l'utilisateur root

**Solution** :
```bash
# Corriger les permissions
sudo chown -R $(id -u):$(id -g) backend/ frontend/

# Ne JAMAIS créer de fichiers avec docker compose exec
# Toujours créer directement dans l'IDE ou avec des commandes locales
```

### Problème : La CI échoue mais pas en local

**Symptôme** : `make lint-all` passe, mais la CI échoue

**Causes possibles** :

1. **Fichiers non commités**
```bash
git status
# Vérifier qu'il n'y a pas de fichiers modifiés non commités
```

2. **Cache npm/composer différent**
```bash
# Supprimer les caches locaux
rm -rf frontend/node_modules backend/vendor
make install-frontend
make install-backend
```

3. **Version de Node/PHP différente**
```bash
# Vérifier les versions
docker compose run --rm node node --version  # Devrait être v20.x
docker compose run --rm php php --version    # Devrait être 8.3.x
```

### Problème : Warnings ESLint acceptés mais énervants

**Symptôme** : 26 warnings sur `any`, variables inutilisées, etc.

**Ce sont des WARNINGS, pas des erreurs** : La CI passe quand même.

**Pour les corriger progressivement** :
```bash
# Voir tous les warnings
make lint-front

# Les corriger un par un
# Pour 'any' : typer correctement
# Pour variables inutilisées : préfixer avec _
const _unusedVar = 'something'
```

---

## Récapitulatif des commandes

| Commande | Description | Modifie les fichiers ? |
|----------|-------------|------------------------|
| `make lint-all` | Vérifie backend + frontend | ❌ Non |
| `make fix-all` | Corrige backend + frontend | ✅ Oui |
| `make lint-back` | Vérifie style PHP (Pint) | ❌ Non |
| `make lint-back-fix` | Corrige style PHP | ✅ Oui |
| `make lint-front` | Vérifie code JS/TS (ESLint) | ❌ Non |
| `make lint-front-fix` | Corrige code JS/TS | ✅ Oui |
| `make format-front` | Vérifie formatage (Prettier) | ❌ Non |
| `make format-front-fix` | Formate le code | ✅ Oui |
| `git commit` | Déclenche le pre-commit hook | ✅ Oui (auto-fix) |
| `git commit --no-verify` | Bypass le hook | ❌ Non |
| `git push` | Déclenche la CI sur GitHub | ❌ Non (sauf si échec CI) |

---

## Workflow recommandé

### Au quotidien

```bash
# 1. Avant de commencer à coder
git pull
make up

# 2. Développer normalement
# ... éditer des fichiers ...

# 3. Avant de commiter, vérifier
make lint-all

# 4. Si des problèmes, corriger automatiquement
make fix-all

# 5. Commiter (le hook va s'exécuter automatiquement)
git add .
git commit -m "feat: my feature"

# 6. Pusher (la CI va s'exécuter sur GitHub)
git push
```

### Avant une PR

```bash
# Vérifier TOUT une dernière fois
make lint-all
make test
make build

# Si tout passe, créer la PR
git push origin ma-branche
```

### Lors d'un code review

Si on vous demande de corriger le style :
```bash
# Corriger automatiquement
make fix-all

# Vérifier
make lint-all

# Commiter
git add .
git commit -m "style: fix code style issues"
git push
```

---

## Liens utiles

- [Documentation Laravel Pint](https://laravel.com/docs/11.x/pint)
- [Documentation Prettier](https://prettier.io/docs/en/)
- [Documentation ESLint](https://eslint.org/docs/latest/)
- [Documentation Husky](https://typicode.github.io/husky/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

---

**Questions ?** Consultez ce guide ou lancez `make help` pour voir toutes les commandes disponibles.
