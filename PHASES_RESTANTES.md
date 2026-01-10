# Phases Restantes à Implémenter

## Vue d'ensemble

**Phases complétées:** 18/22 (82%)
**Phases restantes:** 4 phases (Phase 3.3, 3.4, 3.5, 3.7)
**Temps estimé restant:** ~40-50 heures

---

## Phase 3.3: Implémenter caching Redis (Temps estimé: 10h)

### Objectif
Améliorer les performances en mettant en cache les données fréquemment consultées avec Redis.

### Tâches à réaliser

#### 3.3.1: Créer le service de cache
**Fichier à créer:** `backend/app/Services/CacheService.php`

```php
<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    const TTL_STATS = 3600;        // 1 heure
    const TTL_ASSET_TYPES = 86400; // 24 heures
    const TTL_BUDGET = 1800;       // 30 minutes

    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::tags(['budget-app'])->remember($key, $ttl, $callback);
    }

    public function invalidateBudget(int $budgetId): void
    {
        Cache::tags(['budget-app'])->flush();
        // Ou plus précis:
        // Cache::forget("stats:budget:{$budgetId}:*");
    }

    public function invalidateUser(int $userId): void
    {
        Cache::tags(['budget-app'])->flush();
    }
}
```

#### 3.3.2: Ajouter le cache aux statistiques
**Fichiers à modifier:**
- `backend/app/Http/Controllers/StatsController.php`

**Modifications:**
```php
// Dans chaque méthode (byCategory, byTag, summary, etc.)
public function byCategory(Budget $budget)
{
    $this->authorize('view', $budget);

    $cacheKey = "stats:budget:{$budget->id}:by-category";

    return response()->json(
        app(CacheService::class)->remember($cacheKey, CacheService::TTL_STATS, function() use ($budget) {
            // Logique existante
        })
    );
}
```

#### 3.3.3: Ajouter le cache aux types d'actifs
**Fichier à modifier:** `backend/app/Http/Controllers/AssetController.php`

**Modification de la méthode `types()`:**
```php
public function types()
{
    $cacheKey = "asset:types:all";

    return response()->json(
        app(CacheService::class)->remember($cacheKey, CacheService::TTL_ASSET_TYPES, function() {
            return Asset::select('type')
                ->distinct()
                ->pluck('type')
                ->toArray();
        })
    );
}
```

#### 3.3.4: Invalider le cache lors des mises à jour
**Fichiers à modifier:**
- `backend/app/Http/Controllers/ExpenseController.php`
- `backend/app/Http/Controllers/BudgetController.php`

**Ajouter après create/update/delete:**
```php
// Dans ExpenseController après create/update/delete:
app(CacheService::class)->invalidateBudget($expense->budget_id);

// Dans BudgetController après update:
app(CacheService::class)->invalidateBudget($budget->id);
```

#### 3.3.5: Configuration Redis
**Vérifier:** `backend/.env` doit avoir:
```env
CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Vérification
```bash
# Tester qu'une requête met en cache
curl -w "@curl-format.txt" http://localhost:8080/api/budgets/1/stats/summary
# Premier appel: ~500ms
# Deuxième appel: ~50ms (depuis cache)

# Vérifier Redis
docker compose exec redis redis-cli
> KEYS "laravel*"
> GET "laravel_cache:stats:budget:1:summary"
```

---

## Phase 3.4: Créer documentation DevOps (Temps estimé: 8h)

### Objectif
Documenter le processus de déploiement, monitoring, et maintenance de l'application.

### Tâches à réaliser

#### 3.4.1: Guide de déploiement
**Fichier à créer:** `docs/DEPLOYMENT.md`

**Contenu minimal:**
```markdown
# Guide de Déploiement - Budget Manager

## Prérequis
- Serveur Ubuntu 22.04 LTS (minimum 2 GB RAM)
- Docker 24+ et Docker Compose 2+
- Nom de domaine configuré (ex: budgetapp.example.com)
- Accès SSH avec sudo

## Étapes de déploiement

### 1. Préparer le serveur
\`\`\`bash
# Installer Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Installer Docker Compose
sudo apt install docker-compose-plugin
\`\`\`

### 2. Cloner le projet
\`\`\`bash
git clone https://github.com/votre-org/budget-manager.git
cd budget-manager
\`\`\`

### 3. Configuration
\`\`\`bash
# Backend
cp backend/.env.example backend/.env
nano backend/.env
# Modifier: APP_ENV=production, DB_PASSWORD, REDIS_PASSWORD

# Frontend
cp frontend/.env.example frontend/.env
nano frontend/.env
# Modifier: VITE_API_URL=https://api.budgetapp.example.com
\`\`\`

### 4. SSL avec Let's Encrypt
\`\`\`bash
# Installer Certbot
sudo apt install certbot

# Obtenir certificat
sudo certbot certonly --standalone -d budgetapp.example.com
\`\`\`

### 5. Build et démarrage
\`\`\`bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose exec php php artisan migrate --force
docker compose exec php php artisan db:seed --class=ProdSeeder
\`\`\`

### 6. Reverse proxy Nginx
Configurer Nginx pour proxy vers Docker...

## Mises à jour
\`\`\`bash
git pull origin main
docker compose -f docker-compose.prod.yml up -d --build
docker compose exec php php artisan migrate --force
\`\`\`

## Rollback
\`\`\`bash
git checkout tags/v1.2.3
docker compose -f docker-compose.prod.yml up -d --build
docker compose exec php php artisan migrate:rollback
\`\`\`
```

#### 3.4.2: Guide de monitoring
**Fichier à créer:** `docs/MONITORING.md`

**Contenu minimal:**
```markdown
# Guide de Monitoring

## Vérification de santé

### Backend
\`\`\`bash
curl https://api.budgetapp.example.com/up
# Doit retourner 200 OK
\`\`\`

### Base de données
\`\`\`bash
docker compose exec php php artisan db:monitor
\`\`\`

### Redis
\`\`\`bash
docker compose exec redis redis-cli ping
# Doit retourner "PONG"
\`\`\`

## Logs

### Consulter les logs
\`\`\`bash
# Logs Laravel
docker compose logs -f php

# Logs Nginx
docker compose logs -f nginx

# Logs MySQL
docker compose logs -f mysql
\`\`\`

## Alertes à configurer
- CPU > 80% pendant 5 min
- RAM > 90% pendant 2 min
- Disque > 85%
- Erreurs HTTP 5xx > 10/min
- Temps de réponse API > 2s
```

#### 3.4.3: Guide de sécurité
**Fichier à créer:** `docs/SECURITY.md`

**Contenu minimal:**
```markdown
# Guide de Sécurité

## Checklist de sécurité

### Configuration
- [ ] APP_DEBUG=false en production
- [ ] Mots de passe forts pour DB/Redis
- [ ] HTTPS activé partout
- [ ] CORS restreint au domaine frontend
- [ ] Rate limiting configuré
- [ ] Fichiers sensibles hors du web root

### Mises à jour
- [ ] Dépendances Composer à jour (`composer audit`)
- [ ] Dépendances NPM à jour (`npm audit`)
- [ ] Docker images à jour

### Sauvegardes
\`\`\`bash
# Backup base de données (quotidien)
docker compose exec mysql mysqldump -u root -p budget_manager > backup-$(date +%Y%m%d).sql

# Backup fichiers uploads
tar -czf uploads-$(date +%Y%m%d).tar.gz backend/storage/app/public
\`\`\`

### Restauration
\`\`\`bash
# Restaurer DB
docker compose exec -T mysql mysql -u root -p budget_manager < backup-20260110.sql
\`\`\`

## Gestion des incidents
1. Identifier l'incident (logs, Sentry, monitoring)
2. Isoler si nécessaire (désactiver fonctionnalité)
3. Corriger (hotfix ou rollback)
4. Déployer
5. Post-mortem (doc incident)
```

#### 3.4.4: Performance
**Fichier à créer:** `docs/PERFORMANCE.md`

**Contenu minimal:**
```markdown
# Guide de Performance

## Métriques cibles
- Temps de réponse API: < 200ms (p95)
- Time To First Byte: < 500ms
- Largest Contentful Paint: < 2.5s
- First Input Delay: < 100ms
- Cumulative Layout Shift: < 0.1

## Optimisations appliquées
- ✅ Indexes base de données
- ✅ Eager loading (N+1 queries)
- ✅ Cache Redis (stats, types)
- ✅ Lazy loading widgets
- ✅ v-memo sur listes
- ✅ Build optimisé (minify, code splitting)

## Monitoring performance
\`\`\`bash
# Laravel Telescope (dev)
php artisan telescope:install

# MySQL slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
\`\`\`
```

---

## Phase 3.5: Implémenter monitoring avec Sentry (Temps estimé: 5h)

### Objectif
Capturer et monitorer les erreurs en production côté backend et frontend.

### Tâches à réaliser

#### 3.5.1: Installation backend
**Commandes:**
```bash
cd backend
composer require sentry/sentry-laravel
php artisan sentry:publish
```

**Configuration:** `backend/config/sentry.php`
```php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'environment' => env('APP_ENV', 'production'),
'traces_sample_rate' => 0.1, // 10% des transactions
```

**Ajouter au .env:**
```env
SENTRY_LARAVEL_DSN=https://xxxxx@sentry.io/xxxxx
```

#### 3.5.2: Installation frontend
**Commandes:**
```bash
cd frontend
npm install @sentry/vue
```

**Configuration:** `frontend/src/main.ts`
```typescript
import * as Sentry from '@sentry/vue'

const app = createApp(App)

if (import.meta.env.PROD) {
  Sentry.init({
    app,
    dsn: import.meta.env.VITE_SENTRY_DSN,
    integrations: [
      new Sentry.BrowserTracing({
        routingInstrumentation: Sentry.vueRouterInstrumentation(router),
      }),
      new Sentry.Replay(),
    ],
    tracesSampleRate: 0.1,
    replaysSessionSampleRate: 0.1,
    replaysOnErrorSampleRate: 1.0,
  })
}
```

**Ajouter au .env:**
```env
VITE_SENTRY_DSN=https://xxxxx@sentry.io/xxxxx
```

#### 3.5.3: Tester la capture d'erreurs
**Backend:**
```php
// Routes temporaires pour test
Route::get('/test-sentry', function() {
    throw new \Exception('Test Sentry Backend');
});
```

**Frontend:**
```typescript
// Dans une page de test
throw new Error('Test Sentry Frontend')
```

#### 3.5.4: Configuration des alertes Sentry
1. Créer projet sur sentry.io
2. Configurer alertes:
   - Erreurs > 10/heure
   - Nouveaux types d'erreurs
3. Intégrer avec Slack/Email

### Vérification
- Déclencher erreur volontaire
- Vérifier présence dans dashboard Sentry
- Vérifier contexte (user, browser, request)
- Vérifier stack trace lisible

---

## Phase 3.7: Ajouter virtualisation pour longues listes (Temps estimé: 5h)

### Objectif
Améliorer les performances d'affichage pour les listes >50 items avec virtualisation.

### Tâches à réaliser

#### 3.7.1: Installation
**Commande:**
```bash
cd frontend
npm install vue-virtual-scroller
```

#### 3.7.2: Configuration globale
**Fichier:** `frontend/src/main.ts`
```typescript
import { RecycleScroller } from 'vue-virtual-scroller'
import 'vue-virtual-scroller/dist/vue-virtual-scroller.css'

app.component('RecycleScroller', RecycleScroller)
```

#### 3.7.3: Virtualiser TagsPage
**Fichier:** `frontend/src/pages/TagsPage.vue`

**Remplacer:**
```vue
<!-- Avant -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
  <div v-for="tag in filteredTags" :key="tag.id">
    <TagBadge :tag="tag" />
  </div>
</div>

<!-- Après -->
<RecycleScroller
  :items="filteredTags"
  :item-size="80"
  key-field="id"
  v-slot="{ item: tag }"
  class="h-[600px]"
>
  <div class="p-2">
    <TagBadge :tag="tag" />
  </div>
</RecycleScroller>
```

#### 3.7.4: Virtualiser AssetsPage (tables)
**Fichier:** `frontend/src/pages/AssetsPage.vue`

**Pour les tables, utiliser DynamicScroller:**
```vue
<DynamicScroller
  :items="assetsStore.assets"
  :min-item-size="60"
  key-field="id"
  class="h-[500px] overflow-auto"
>
  <template #default="{ item: asset, index, active }">
    <DynamicScrollerItem
      :item="asset"
      :active="active"
      :size-dependencies="[asset.label]"
      :data-index="index"
    >
      <tr>
        <td>{{ asset.type }}</td>
        <td>{{ asset.label }}</td>
        <!-- ... -->
      </tr>
    </DynamicScrollerItem>
  </template>
</DynamicScroller>
```

#### 3.7.5: Optimiser ExpensesPage (si applicable)
**Si la page affiche >50 dépenses, virtualiser la liste**

### Vérification
**Performance avant/après:**
```bash
# Créer 500 tags pour tester
# Avant: FPS ~20-30 au scroll
# Après: FPS stable à 60
```

**Test Chrome DevTools:**
1. Ouvrir Performance tab
2. Record pendant scroll
3. Vérifier: Scripting < 50ms, Rendering < 16ms

---

## Ordre d'implémentation recommandé

1. **Phase 3.3 (Caching Redis)** - Impact immédiat sur performance
2. **Phase 3.5 (Sentry)** - Important pour production
3. **Phase 3.7 (Virtualisation)** - Amélioration UX progressive
4. **Phase 3.4 (Documentation)** - Peut être fait en parallèle

---

## Commandes utiles pour l'utilisateur

### Pour démarrer une phase:
```bash
# Exemple pour Phase 3.3
cd /home/thp/Projets/projetPersoBudget
# Dire: "Implémente la phase 3.3: caching Redis"
```

### Pour vérifier l'état actuel:
```bash
# Tests
docker compose exec node npm run test:run

# Type-check
docker compose exec node npm run type-check

# Build
docker compose exec node npm run build
```

### Pour commiter après chaque phase:
```bash
git add .
git commit -m "feat: Phase 3.X - Description"
```

---

## Notes importantes

- **Sentry:** Nécessite un compte sur sentry.io (gratuit jusqu'à 5k événements/mois)
- **Redis:** Déjà configuré dans docker-compose.yml
- **Documentation:** Peut être écrite progressivement
- **Virtualisation:** Tester avec ≥100 items pour voir la différence

---

## Checklist finale (quand tout sera fait)

- [ ] Phase 3.3: Cache Redis implémenté et testé
- [ ] Phase 3.4: Documentation complète (4 fichiers)
- [ ] Phase 3.5: Sentry configuré backend + frontend
- [ ] Phase 3.7: Virtualisation sur 2-3 pages clés
- [ ] Tests passent (31/31)
- [ ] Type-check OK
- [ ] Build production OK
- [ ] Git commit pour chaque phase
- [ ] Mise à jour CHANGELOG.md

**Temps total estimé:** 40-50 heures
**Impact:** Performance +30%, Monitoring production, Documentation complète