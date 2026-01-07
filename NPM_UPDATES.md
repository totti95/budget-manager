# Recommandations de mise à jour npm

**Date:** 2026-01-07
**Statut:** 14 packages obsolètes, 5 vulnérabilités modérées

## Vulnérabilités de sécurité (npm audit)

### 1. esbuild (≤0.24.2)
- **Gravité:** Modérée
- **Description:** Problème de sécurité dans les versions ≤0.24.2
- **Package affecté:** `esbuild` (dépendance de Vite)
- **Version actuelle:** 0.21.5 (via Vite)
- **Correction:** Mise à jour automatique avec Vite 7.x

### 2. vue-template-compiler
- **Gravité:** Modérée (4 vulnérabilités)
- **Description:** XSS potentiel dans le compilateur de templates
- **Version actuelle:** 2.7.16
- **Correction:** Vérifier si ce package est encore nécessaire (Vue 3 utilise @vue/compiler-sfc)

## Packages obsolètes

### ⚠️ Mises à jour majeures (BREAKING CHANGES)

#### 1. Vite (5.4.21 → 7.3.1)
- **Impact:** Majeur - changements de configuration possibles
- **Recommandation:**
  - Lire le changelog: https://github.com/vitejs/vite/releases
  - Tester en environnement de développement
  - Vérifier la compatibilité des plugins
- **Commande:**
  ```bash
  npm install vite@^7.3.1 --save-dev
  ```

#### 2. ESLint (8.57.1 → 9.18.0)
- **Impact:** Majeur - nouvelle configuration flat config
- **Recommandation:**
  - Migration vers flat config requise
  - Documentation: https://eslint.org/docs/latest/use/configure/migration-guide
  - Nécessite refonte de `.eslintrc.cjs`
- **Commande:**
  ```bash
  npm install eslint@^9.18.0 --save-dev
  ```

#### 3. TailwindCSS (3.4.17 → 4.1.7)
- **Impact:** Majeur - breaking changes dans la configuration
- **Recommandation:**
  - Consulter le guide de migration v3→v4
  - Tester tous les composants visuels
  - Vérifier les classes custom
- **Commande:**
  ```bash
  npm install tailwindcss@^4.1.7 --save-dev
  ```

#### 4. Pinia (2.3.1 → 3.0.4)
- **Impact:** Majeur - API changes possibles
- **Recommandation:**
  - Lire le changelog de Pinia 3.0
  - Tester tous les stores (auth, budget, expense, etc.)
- **Commande:**
  ```bash
  npm install pinia@^3.0.4
  ```

### ✅ Mises à jour mineures (SAFE)

Ces packages peuvent être mis à jour sans risque majeur :

```bash
# Mises à jour sûres
npm install \
  @types/node@^22.10.6 \
  @vitejs/plugin-vue@^5.2.1 \
  @vue/test-utils@^2.4.6 \
  prettier@^3.4.2 \
  typescript@~5.6.3 \
  vue-router@^4.5.0 \
  @typescript-eslint/parser@^8.20.0 \
  @typescript-eslint/eslint-plugin@^8.20.0 \
  eslint-plugin-vue@^9.32.0 \
  postcss@^8.5.1 \
  --save-dev
```

## Stratégies de mise à jour

### Option 1 : Mise à jour progressive (RECOMMANDÉE)

1. **Phase 1 - Sécurité immédiate:**
   ```bash
   cd frontend
   # Supprimer vue-template-compiler si non utilisé
   npm uninstall vue-template-compiler

   # Mises à jour mineures sûres
   npm update
   ```

2. **Phase 2 - Vite 7.x:**
   ```bash
   npm install vite@^7.3.1 --save-dev
   npm run build  # Tester le build
   npm run dev    # Tester le dev server
   ```

3. **Phase 3 - ESLint 9.x:**
   - Migrer vers flat config
   - Créer `eslint.config.js`
   - Tester `npm run lint`

4. **Phase 4 - TailwindCSS 4.x:**
   - Migrer la configuration
   - Tester tous les composants visuels
   - Vérifier le responsive design

5. **Phase 5 - Pinia 3.x:**
   - Tester tous les stores
   - Vérifier la persistance des données

### Option 2 : `npm audit fix` (RISQUÉ)

```bash
# ⚠️ ATTENTION : Peut casser l'application
npm audit fix --force
```

**Conséquences:**
- Installe les dernières versions majeures de tous les packages
- Risque élevé de breaking changes
- Nécessite tests approfondis après

**Ne PAS utiliser** sans sauvegarde et plan de rollback.

## Checklist de test après mise à jour

Après chaque phase de mise à jour :

### Tests automatiques
- [ ] `npm run type-check` - Pas d'erreurs TypeScript
- [ ] `npm run lint:check` - Pas d'erreurs ESLint
- [ ] `npm run build` - Build production réussit
- [ ] `make test` (backend) - Tous les tests passent

### Tests manuels
- [ ] Connexion/Déconnexion
- [ ] Création budget depuis template
- [ ] Ajout/Modification/Suppression dépense
- [ ] Import CSV
- [ ] Graphiques et statistiques
- [ ] Dashboard personnalisé
- [ ] Assets et objectifs d'épargne
- [ ] Notifications

### Tests de régression visuelle
- [ ] Layout responsive (mobile, tablet, desktop)
- [ ] Thème et couleurs TailwindCSS
- [ ] Formulaires et validation
- [ ] Modals et toasts

## État actuel vs. recommandations

| Package | Actuel | Disponible | Priorité | Risque |
|---------|--------|------------|----------|--------|
| vite | 5.4.21 | 7.3.1 | Haute | Moyen |
| eslint | 8.57.1 | 9.18.0 | Moyenne | Élevé |
| tailwindcss | 3.4.17 | 4.1.7 | Basse | Élevé |
| pinia | 2.3.1 | 3.0.4 | Basse | Moyen |
| esbuild | 0.21.5 | 0.24.6 | Haute | Faible |
| vue-router | 4.4.5 | 4.5.0 | Basse | Faible |

## Notes importantes

1. **Environnement de test requis:**
   - Toujours tester dans une branche Git séparée
   - Ne jamais appliquer en production sans tests complets

2. **Dépendances liées:**
   - Vite et esbuild sont liés (Vite 7 inclut esbuild 0.24.x)
   - ESLint nécessite plugins compatibles (@typescript-eslint, eslint-plugin-vue)

3. **Compatibilité Vue 3:**
   - Tous les packages cités sont compatibles Vue 3.5
   - `vue-template-compiler` est pour Vue 2 → À supprimer

4. **Breaking changes TypeScript:**
   - TypeScript 5.6 peut révéler de nouveaux warnings
   - Prévoir du temps pour corrections de types

## Commandes de rollback

Si une mise à jour casse l'application :

```bash
# Annuler les modifications package.json
git checkout package.json package-lock.json

# Réinstaller les dépendances précédentes
rm -rf node_modules
npm install
```

## Prochaines étapes suggérées

1. **Immédiat (aujourd'hui):**
   - Supprimer `vue-template-compiler`
   - Appliquer mises à jour mineures sûres

2. **Cette semaine:**
   - Mettre à jour Vite 7.x
   - Tester en développement

3. **Ce mois:**
   - Planifier migration ESLint 9
   - Évaluer besoin de TailwindCSS 4

4. **Trimestre prochain:**
   - Migration complète vers dernières versions
   - Audit de sécurité complet
