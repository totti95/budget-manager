# 🚀 Guide de Déploiement en Production

Ce document décrit les étapes pour déployer Budget Manager en production de manière sécurisée.

## 📋 Checklist Pré-Déploiement

### 1. Sécurité

- [ ] Générer une nouvelle `APP_KEY` : `php artisan key:generate`
- [ ] Définir `APP_ENV=production`
- [ ] Définir `APP_DEBUG=false`
- [ ] Configurer des mots de passe forts pour :
  - Base de données (`DB_PASSWORD`)
  - Redis (si activé)
  - Root MySQL (`MYSQL_ROOT_PASSWORD`)
- [ ] Configurer HTTPS avec certificats SSL valides
- [ ] Définir `SANCTUM_STATEFUL_DOMAINS` avec votre domaine production
- [ ] Configurer `FRONTEND_URL` avec l'URL de production
- [ ] Activer l'expiration des tokens Sanctum (déjà fait : 7 jours)

### 2. Base de Données

- [ ] Sauvegarder la base de données avant migration
- [ ] Exécuter les migrations : `php artisan migrate --force`
- [ ] Configurer des sauvegardes automatiques quotidiennes
- [ ] Vérifier les index sur les tables principales

### 3. Performance

- [ ] Activer le cache de configuration : `php artisan config:cache`
- [ ] Activer le cache des routes : `php artisan route:cache`
- [ ] Activer le cache des vues : `php artisan view:cache`
- [ ] Compiler les assets frontend : `npm run build`
- [ ] Activer OPcache PHP (déjà configuré dans `php.ini`)
- [ ] Configurer Redis pour le cache et les sessions

### 4. Monitoring

- [ ] Configurer un service de monitoring (Sentry, New Relic, etc.)
- [ ] Configurer les alertes email pour les erreurs critiques
- [ ] Mettre en place des logs centralisés
- [ ] Configurer des healthchecks externes

### 5. Serveur Web

- [ ] Configurer Nginx avec HTTPS (Let's Encrypt)
- [ ] Activer les security headers (déjà dans nginx.conf)
- [ ] Configurer les limits de rate limiting au niveau du serveur
- [ ] Désactiver l'exposition des versions de logiciels

---

## 🔧 Configuration Production

### 1. Variables d'Environnement Backend (`.env`)

```env
APP_NAME="Budget Manager"
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_GENEREE_ICI
APP_DEBUG=false
APP_TIMEZONE=Europe/Paris
APP_URL=https://votredomaine.com
APP_LOCALE=fr

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=budget_manager_prod
DB_USERNAME=budget_user_prod
DB_PASSWORD=MOT_DE_PASSE_FORT_ICI

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.votreserveur.com
MAIL_PORT=587
MAIL_USERNAME=votre@email.com
MAIL_PASSWORD=votre_mot_de_passe
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@votredomaine.com"
MAIL_FROM_NAME="${APP_NAME}"

SANCTUM_STATEFUL_DOMAINS=votredomaine.com,www.votredomaine.com

FRONTEND_URL=https://votredomaine.com
```

### 2. Variables d'Environnement Frontend (`.env.production`)

```env
VITE_API_URL=https://votredomaine.com/api
```

---

## 🐳 Déploiement avec Docker (Production)

### 1. Créer un `docker-compose.prod.yml`

```yaml
services:
  nginx:
    image: nginx:alpine
    container_name: budget_nginx_prod
    ports:
      - "443:443"
      - "80:80"
    volumes:
      - ./backend/public:/var/www/html/public:ro
      - ./docker/nginx.prod.conf:/etc/nginx/conf.d/default.conf:ro
      - ./ssl:/etc/nginx/ssl:ro
    depends_on:
      php:
        condition: service_healthy
    restart: unless-stopped
    networks:
      - budget_network

  php:
    build:
      context: .
      dockerfile: docker/Dockerfile.php
    container_name: budget_php_prod
    volumes:
      - ./backend:/var/www/html
      - ./docker/php.prod.ini:/usr/local/etc/php/conf.d/custom.ini:ro
    environment:
      - APP_ENV=production
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    restart: unless-stopped
    healthcheck:
      test: ["CMD-SHELL", "php-fpm-healthcheck || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3
    networks:
      - budget_network

  mysql:
    image: mysql:8.0
    container_name: budget_mysql_prod
    environment:
      - MYSQL_DATABASE=budget_manager_prod
      - MYSQL_USER=budget_user_prod
      - MYSQL_PASSWORD=${DB_PASSWORD}
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
    volumes:
      - mysql_data_prod:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
    restart: unless-stopped
    networks:
      - budget_network

  redis:
    image: redis:7-alpine
    container_name: budget_redis_prod
    volumes:
      - redis_data_prod:/data
    command: redis-server --appendonly yes
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3
    restart: unless-stopped
    networks:
      - budget_network

volumes:
  mysql_data_prod:
  redis_data_prod:

networks:
  budget_network:
    driver: bridge
```

### 2. Déploiement

```bash
# 1. Build et démarrage
docker-compose -f docker-compose.prod.yml up -d --build

# 2. Installation des dépendances
docker-compose -f docker-compose.prod.yml exec php composer install --no-dev --optimize-autoloader

# 3. Générer la clé
docker-compose -f docker-compose.prod.yml exec php php artisan key:generate

# 4. Migrations
docker-compose -f docker-compose.prod.yml exec php php artisan migrate --force

# 5. Optimisations
docker-compose -f docker-compose.prod.yml exec php php artisan config:cache
docker-compose -f docker-compose.prod.yml exec php php artisan route:cache
docker-compose -f docker-compose.prod.yml exec php php artisan view:cache

# 6. Build frontend
cd frontend && npm ci && npm run build
```

---

## 🔒 Sécurité Avancée

### 1. Firewall

```bash
# Autoriser uniquement HTTP/HTTPS et SSH
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. Fail2Ban

Installer et configurer Fail2Ban pour bloquer les tentatives de brute-force :

```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
```

### 3. Sauvegardes Automatiques

Script de sauvegarde quotidienne :

```bash
#!/bin/bash
# /opt/backup-budget-manager.sh

BACKUP_DIR="/backups/budget-manager"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup MySQL
docker exec budget_mysql_prod mysqldump -u root -p${MYSQL_ROOT_PASSWORD} budget_manager_prod > $BACKUP_DIR/db_$DATE.sql

# Backup Redis
docker exec budget_redis_prod redis-cli SAVE
docker cp budget_redis_prod:/data/dump.rdb $BACKUP_DIR/redis_$DATE.rdb

# Rotation (garder 7 jours)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.rdb" -mtime +7 -delete
```

Ajouter au crontab :

```bash
0 2 * * * /opt/backup-budget-manager.sh
```

---

## 📊 Monitoring

### 1. Healthchecks

Créer un endpoint de healthcheck :

```php
// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() !== null,
        'redis' => Cache::getStore() instanceof \Illuminate\Cache\RedisStore,
    ];

    $healthy = !in_array(false, $checks, true);

    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});
```

### 2. Logs Structurés

Configurer les logs en JSON dans `config/logging.php` :

```php
'production' => [
    'driver' => 'stack',
    'channels' => ['daily', 'slack'],
    'formatter' => \Monolog\Formatter\JsonFormatter::class,
],
```

---

## 🚨 Plan de Rollback

En cas de problème après déploiement :

1. Restaurer le backup de base de données
2. Revenir à la version précédente du code
3. Vider les caches :
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

---

## 📞 Support

En cas de problème, contacter l'équipe de développement avec :
- Logs d'erreur (`storage/logs/laravel.log`)
- Résultat du healthcheck
- Description du problème rencontré
