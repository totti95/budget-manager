# Fonctionnalité "Mot de Passe Oublié"

Cette documentation explique la fonctionnalité de réinitialisation de mot de passe implémentée dans Budget Manager.

## 📋 Aperçu

La fonctionnalité permet aux utilisateurs de réinitialiser leur mot de passe en cas d'oubli via un lien sécurisé envoyé par email.

### Flux Utilisateur

1. **Demande de réinitialisation** : L'utilisateur clique sur "Mot de passe oublié ?" sur la page de connexion
2. **Saisie de l'email** : L'utilisateur entre son adresse email
3. **Réception de l'email** : Un email avec un lien sécurisé est envoyé (valide 60 minutes)
4. **Clic sur le lien** : L'utilisateur clique sur le lien dans l'email
5. **Nouveau mot de passe** : L'utilisateur définit un nouveau mot de passe
6. **Confirmation** : Redirection automatique vers la page de connexion

## 🏗️ Architecture

### Backend (Laravel)

#### 1. Table de Base de Données

La table `password_reset_tokens` existe déjà dans Laravel 11 :

```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(191) PRIMARY KEY,
    token VARCHAR(191),
    created_at TIMESTAMP NULL
);
```

#### 2. Controller - `PasswordResetController`

**Fichier** : `backend/app/Http/Controllers/PasswordResetController.php`

**Méthodes** :

##### `forgotPassword(Request $request)`
- **Route** : `POST /api/auth/forgot-password`
- **Validation** : Email requis et existant
- **Logique** :
  1. Vérifie que l'utilisateur existe et n'est pas désactivé
  2. Génère un token sécurisé (60 caractères)
  3. Hash le token avant stockage (sécurité)
  4. Supprime les anciens tokens pour cet email
  5. Stocke le nouveau token
  6. Envoie l'email avec le lien de réinitialisation
- **Réponse** : Message de confirmation

##### `resetPassword(Request $request)`
- **Route** : `POST /api/auth/reset-password`
- **Validation** : Email, token, password (min 8), password_confirmation
- **Logique** :
  1. Récupère le token de la base de données
  2. Vérifie l'expiration (60 minutes max)
  3. Vérifie que le token correspond (via Hash::check)
  4. Met à jour le mot de passe de l'utilisateur
  5. Supprime le token utilisé
- **Réponse** : Message de succès

#### 3. Template d'Email

**Fichier** : `backend/resources/views/emails/password-reset.blade.php`

**Caractéristiques** :
- Design responsive
- Lien cliquable + copie manuelle possible
- Avertissement sur la validité (60 minutes)
- Message de sécurité si non-demandé

**Variables** :
- `$user` : L'utilisateur concerné
- `$resetUrl` : L'URL complète avec token et email

#### 4. Routes API

**Fichier** : `backend/routes/api.php`

```php
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
    });
});
```

**Protection** : Rate limiting (5 tentatives par minute)

### Frontend (Vue 3 + TypeScript)

#### 1. API Client

**Fichier** : `frontend/src/api/password-reset.ts`

```typescript
export const passwordResetApi = {
  forgotPassword(data: ForgotPasswordData): Promise<{ message: string }>,
  resetPassword(data: ResetPasswordData): Promise<{ message: string }>
};
```

#### 2. Schémas de Validation (Zod)

**Fichier** : `frontend/src/schemas/password-reset.ts`

```typescript
// Formulaire "Mot de passe oublié"
const forgotPasswordSchema = z.object({
  email: z.string().email("Email invalide"),
});

// Formulaire de réinitialisation
const resetPasswordSchema = z.object({
  email: z.string().email("Email invalide"),
  token: z.string().min(1, "Token requis"),
  password: z.string().min(8, "Au moins 8 caractères"),
  passwordConfirmation: z.string(),
}).refine(...);
```

#### 3. Page "Mot de Passe Oublié"

**Fichier** : `frontend/src/pages/ForgotPasswordPage.vue`

**Route** : `/forgot-password`

**Fonctionnalités** :
- Formulaire avec validation email
- Indicateur de chargement pendant l'envoi
- Message de succès après envoi
- Possibilité de renvoyer un email
- Lien retour vers connexion
- Utilise les composants FormInput et FormButton

**États** :
- `emailSent` : Affiche le message de confirmation
- `isSubmitting` : Affiche l'indicateur de chargement

#### 4. Page "Réinitialiser Mot de Passe"

**Fichier** : `frontend/src/pages/ResetPasswordPage.vue`

**Route** : `/reset-password?token=xxx&email=xxx`

**Fonctionnalités** :
- Récupération automatique du token et email depuis l'URL
- Formulaire de nouveau mot de passe
- Validation de correspondance des mots de passe
- Détection de token invalide ou expiré
- Message de succès avec redirection automatique (3s)
- Lien vers demander un nouveau lien
- Lien retour vers connexion

**États** :
- `resetSuccess` : Affiche le succès et redirige
- `invalidToken` : Affiche l'erreur et le bouton "Nouveau lien"
- `isSubmitting` : Affiche l'indicateur de chargement

#### 5. Modification LoginPage

Ajout d'un lien "Mot de passe oublié ?" au-dessus du bouton de connexion.

#### 6. Routes Frontend

**Fichier** : `frontend/src/router/index.ts`

```typescript
{
  path: "/forgot-password",
  name: "forgot-password",
  component: () => import("@/pages/ForgotPasswordPage.vue"),
  meta: { requiresGuest: true },
},
{
  path: "/reset-password",
  name: "reset-password",
  component: () => import("@/pages/ResetPasswordPage.vue"),
  meta: { requiresGuest: true },
}
```

**Protection** : `requiresGuest` empêche les utilisateurs connectés d'accéder

## 🔒 Sécurité

### Mesures de Sécurité Implémentées

1. **Token Hashé** : Le token est hashé avant stockage en base de données
2. **Token Unique** : Un seul token valide par email à la fois
3. **Expiration** : Tokens valides 60 minutes maximum
4. **Rate Limiting** : 5 tentatives par minute maximum
5. **Validation Email** : Vérification que l'utilisateur existe et n'est pas désactivé
6. **Token Long** : 60 caractères aléatoires sécurisés
7. **Suppression Après Usage** : Token supprimé après utilisation
8. **HTTPS Recommandé** : Les liens doivent être envoyés via HTTPS en production

### Flux de Sécurité

```
1. Utilisateur demande réinitialisation
   ↓
2. Backend génère token (60 chars aléatoires)
   ↓
3. Token hashé avant stockage DB
   ↓
4. Email envoyé avec token en clair dans URL
   ↓
5. Utilisateur clique sur lien (token en clair)
   ↓
6. Backend vérifie Hash::check(token_clair, token_hashé)
   ↓
7. Si valide : mot de passe mis à jour + token supprimé
```

## 📧 Configuration Email

### Variables d'Environnement

**Fichier** : `backend/.env`

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@budgetmanager.local"
MAIL_FROM_NAME="Budget Manager"

# URL frontend pour les liens de réinitialisation
FRONTEND_URL=http://localhost:5173
```

### Mailhog (Développement)

En développement, Mailhog capture tous les emails :
- **Interface** : http://localhost:8025
- **Pas d'envoi réel** : Les emails sont interceptés
- **Test facile** : Voir tous les emails envoyés

### Production

Pour la production, configurez un service SMTP réel :
- Gmail SMTP
- SendGrid
- Amazon SES
- Mailgun
- Etc.

## 🧪 Tests

### Test Manuel Complet

1. **Démarrer l'application** :
   ```bash
   make up
   ```

2. **Accéder à la page de connexion** : http://localhost:5173/login

3. **Cliquer sur "Mot de passe oublié ?"**

4. **Entrer un email** : `demo@budgetmanager.local`

5. **Vérifier Mailhog** : http://localhost:8025
   - Voir l'email reçu
   - Copier le lien de réinitialisation

6. **Cliquer sur le lien** ou coller dans le navigateur

7. **Entrer un nouveau mot de passe**

8. **Vérifier la redirection** vers `/login`

9. **Se connecter** avec le nouveau mot de passe

### Scénarios de Test

#### ✅ Cas Nominal
- [x] Email valide → Email envoyé
- [x] Clic sur lien → Page de réinitialisation
- [x] Nouveau mot de passe → Succès
- [x] Connexion avec nouveau mot de passe → OK

#### ⚠️ Cas d'Erreur
- [x] Email inexistant → Erreur de validation
- [x] Utilisateur désactivé → Erreur appropriée
- [x] Token expiré (>60 min) → Message d'erreur
- [x] Token invalide → Message d'erreur
- [x] Mots de passe différents → Erreur de validation
- [x] Mot de passe trop court (<8) → Erreur de validation

#### 🔁 Cas Limites
- [x] Plusieurs demandes successives → Seul le dernier token est valide
- [x] Rate limiting → Bloqué après 5 tentatives/minute
- [x] Token déjà utilisé → Erreur (token supprimé)

## 📁 Fichiers Créés/Modifiés

### Backend

**Créés** :
- `app/Http/Controllers/PasswordResetController.php`
- `resources/views/emails/password-reset.blade.php`

**Modifiés** :
- `routes/api.php` - Ajout des routes
- `database/migrations/2025_10_29_181111_create_password_reset_tokens_table.php` - Supprimé (table déjà existante)

### Frontend

**Créés** :
- `src/api/password-reset.ts`
- `src/schemas/password-reset.ts`
- `src/pages/ForgotPasswordPage.vue`
- `src/pages/ResetPasswordPage.vue`

**Modifiés** :
- `src/router/index.ts` - Ajout des routes
- `src/pages/LoginPage.vue` - Ajout du lien "Mot de passe oublié ?"

## 🎨 UI/UX

### Design Cohérent

- Utilise les composants FormInput et FormButton
- Messages d'état visuels (succès, erreur, avertissement)
- Icônes SVG pour feedback visuel
- Support du dark mode
- Responsive mobile

### Messages Utilisateur

**Succès** :
- "Un email de réinitialisation a été envoyé à votre adresse."
- "Votre mot de passe a été réinitialisé avec succès."

**Erreurs** :
- "Email invalide"
- "Cet utilisateur n'existe pas ou a été désactivé."
- "Ce lien de réinitialisation est invalide ou a expiré."
- "Les mots de passe ne correspondent pas"

**Informations** :
- "Entrez votre email et nous vous enverrons un lien..."
- "Vérifiez votre boîte de réception..."
- "Ce lien est valable pendant 60 minutes."

## 🚀 Déploiement

### Checklist Production

- [ ] Configurer MAIL_* avec un vrai service SMTP
- [ ] Définir FRONTEND_URL avec l'URL de production
- [ ] Activer HTTPS pour sécuriser les tokens dans les URLs
- [ ] Tester l'envoi d'emails réels
- [ ] Vérifier les logs d'erreurs email
- [ ] Documenter pour les utilisateurs finaux

### Configuration SMTP Exemple (Gmail)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Budget Manager"
FRONTEND_URL=https://app.yourdomain.com
```

## 💡 Améliorations Futures Possibles

1. **Notification par SMS** : Ajouter une option SMS pour réinitialisation
2. **Double authentification** : 2FA pour plus de sécurité
3. **Historique** : Logger les tentatives de réinitialisation
4. **Personnalisation Email** : Templates d'emails configurables
5. **Multi-langue** : Support de plusieurs langues dans les emails
6. **Rate limiting progressif** : Augmenter la durée de blocage après plusieurs tentatives
7. **Question de sécurité** : Ajouter une question secrète optionnelle

## 📞 Support

En cas de problème :
1. Vérifier les logs Laravel : `make logs`
2. Vérifier Mailhog : http://localhost:8025
3. Consulter les logs frontend (console navigateur)
4. Vérifier la configuration .env
5. Tester avec le compte démo : demo@budgetmanager.local

## ✅ Résumé

La fonctionnalité "Mot de passe oublié" est **complète et fonctionnelle** :

- ✅ Backend sécurisé avec tokens hashés et expiration
- ✅ Frontend avec validation et feedback utilisateur
- ✅ Email HTML responsive et professionnel
- ✅ Rate limiting et protection contre abus
- ✅ Gestion d'erreurs complète
- ✅ UI/UX cohérente avec l'application
- ✅ Support dark mode
- ✅ Tests passés avec succès
- ✅ Documentation complète

**La fonctionnalité est prête pour la production** après configuration SMTP et HTTPS. 🎉
