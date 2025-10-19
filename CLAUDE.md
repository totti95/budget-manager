# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Budget Manager is a personal budget management web application built with Laravel 11 (backend API) and Vue 3 + TypeScript (frontend SPA), running on Docker.

**Key Features:**
- Budget templates with categories and subcategories
- Monthly budget generation from templates
- Expense tracking with CSV import/export
- Asset/wealth management (savings, investments, real estate)
- Statistics and charts
- Multi-user support with role-based access control

## Architecture

### Backend API (Laravel 11)

**Stack:** PHP 8.3, Laravel 11, MySQL 8.0, Redis, Laravel Sanctum

**Key Architectural Patterns:**

1. **Automatic Case Conversion (CRITICAL):**
   - `ConvertRequestToSnakeCase` middleware: Converts incoming camelCase JSON to snake_case for Laravel/database
   - `ConvertResponseToCamelCase` middleware: Converts outgoing snake_case JSON to camelCase for frontend
   - **Important:** Never manually convert cases in controllers - middlewares handle this globally
   - Order matters: Request conversion → Process → Response conversion

2. **Authentication:**
   - Uses Laravel Sanctum with Bearer tokens (NOT session-based)
   - Token stored in frontend localStorage
   - Middleware: `auth:sanctum` for protected routes
   - Admin routes use additional `admin` middleware

3. **Role System:**
   - `roles` table: Contains "user" and "admin" roles
   - `users.role_id` foreign key to roles
   - Soft deletes on users (`deleted_at` column)
   - Admin protection: Cannot delete last admin user

4. **Data Model Pattern:**
   - **Templates** (reusable): `budget_templates → template_categories → template_subcategories`
   - **Budgets** (monthly snapshots): `budgets → budget_categories → budget_subcategories`
   - **Expenses** linked to budget subcategories
   - When generating a budget from template, data is copied (not referenced) for historical integrity

### Frontend SPA (Vue 3 + TypeScript)

**Stack:** Vue 3, TypeScript, Vite, Pinia, Vue Router, TailwindCSS, Chart.js

**Key Architectural Patterns:**

1. **State Management (Pinia):**
   - Separate stores: `auth`, `budget`, `expense`, `template`, `assets`, `stats`, `users`
   - Each store handles loading states, errors, and API calls
   - Stores use camelCase for all properties

2. **API Layer:**
   - `frontend/src/api/axios.ts`: Centralized axios instance with interceptors
   - Request interceptor: Adds Bearer token to all requests
   - Response interceptor: Handles 401 (redirect to login), 422 (validation), 429 (rate limit), 5xx (server errors)
   - Individual API modules: `auth.ts`, `budgets.ts`, `expenses.ts`, `templates.ts`, `assets.ts`, `users.ts`, `stats.ts`

3. **Form Validation:**
   - Uses VeeValidate + Zod schemas
   - Schemas in `frontend/src/schemas/`
   - All schemas use camelCase field names

4. **Notification System:**
   - `useToast` composable for global toast notifications
   - `Toast.vue` and `ToastContainer.vue` components
   - Types: success, error, warning, info
   - Auto-dismiss with configurable duration

5. **Routing:**
   - Protected routes require authentication (check `authStore.isAuthenticated`)
   - Admin routes additionally check `authStore.user?.role?.label === 'admin'`
   - Unauthorized users redirected to login

## Development Commands

### Quick Start
```bash
make init              # First-time setup (build, install deps, migrate, seed)
make up                # Start all containers
make down              # Stop all containers
```

### Database
```bash
make migrate           # Run migrations
make seed              # Insert demo data
make fresh             # Reset database with fresh data (migrate:fresh --seed)
```

### Backend
```bash
make shell-php         # Open shell in PHP container
make test              # Run Pest tests
make artisan CMD="..."  # Run artisan command
```

### Frontend
```bash
make shell-node        # Open shell in Node container
make build             # Production build
make npm CMD="..."     # Run npm command

# Inside Node container:
npm run dev            # Start dev server
npm run build          # Production build
npm run type-check     # TypeScript validation
npm run lint           # ESLint with auto-fix
npm run lint:check     # ESLint without fixing
npm run format         # Prettier formatting
npm run format:check   # Prettier check only
```

### Development Workflow
```bash
# Frontend development (hot reload)
make up
# Frontend available at http://localhost:5173

# Backend API testing
curl http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@budgetmanager.local","password":"password"}'
```

## Code Conventions

### Backend (PHP/Laravel)
- Follow PSR-12 coding standard
- **Database columns:** snake_case (`created_at`, `role_id`)
- **Request validation:** Accept snake_case (middleware converts from camelCase)
- **Eloquent models:** Use snake_case attributes
- **API responses:** Return snake_case (middleware converts to camelCase)
- **Example:**
  ```php
  // In controller - use snake_case
  $request->validate([
      'role_id' => 'required|exists:roles,id',
  ]);

  User::create([
      'role_id' => $request->role_id,
  ]);
  ```

### Frontend (TypeScript/Vue)
- **All TypeScript:** camelCase for properties, variables, functions
- **API requests:** Send camelCase (middleware converts to snake_case)
- **API responses:** Receive camelCase (middleware converts from snake_case)
- **Types:** Define all interfaces in `src/types/index.ts`
- **Example:**
  ```typescript
  // API call - use camelCase
  await usersApi.create({
    name: 'John',
    email: 'john@example.com',
    roleId: 1  // Will be converted to role_id by middleware
  });

  // Response will have camelCase
  const user: User = response.data;
  console.log(user.roleId, user.createdAt);
  ```

### Money Handling
- **Database:** Store amounts in cents (integer) with suffix `_cents`
- **API:** Transfer cents values
- **Frontend Display:** Convert to euros for display (`centsToEuros = cents / 100`)
- **Frontend Input:** Convert euros to cents before sending (`eurosToCents = euros * 100`)

## Important Patterns

### Adding a New Entity

1. **Backend:**
   ```bash
   # Create migration
   make artisan CMD="make:migration create_things_table"

   # Create model
   make artisan CMD="make:model Thing"

   # Create controller
   make artisan CMD="make:controller ThingController --api"

   # Add routes in routes/api.php
   # Use snake_case in validation and database operations
   ```

2. **Frontend:**
   ```typescript
   // Add type in src/types/index.ts (camelCase)
   export interface Thing {
     id: number;
     userId: number;
     createdAt: string;
   }

   // Create API client in src/api/things.ts
   export const thingsApi = {
     async list(): Promise<Thing[]> { ... }
   };

   // Create Pinia store in src/stores/things.ts
   // Create Vue page in src/pages/ThingsPage.vue
   // Add route in src/router/index.ts
   ```

### Working with Roles

- **Check admin access in Vue:**
  ```typescript
  const authStore = useAuthStore();
  const isAdmin = authStore.user?.role?.label === 'admin';
  ```

- **Protect backend routes:**
  ```php
  Route::middleware(['auth:sanctum', 'admin'])->group(function () {
      // Admin-only routes
  });
  ```

### Budget Generation Pattern

Budgets are **snapshots** of templates:
1. User creates a reusable template with categories/subcategories
2. When generating monthly budget, template data is **copied** (not referenced)
3. Users can modify budget without affecting template
4. Expenses link to budget subcategories, not template subcategories
5. This preserves historical data integrity

## Testing

### Demo Account
- Email: `demo@budgetmanager.local`
- Password: `password`
- Has: 7-category template, 3 monthly budgets with expenses, 4 assets

### Reset Data
```bash
make fresh             # Complete reset with demo data
./reset-data.sh        # Alternative script for data reset
```

### Backend Tests (Pest)
```bash
make test                           # All tests
make shell-php
php artisan test --filter=AuthTest  # Specific test
```

## Common Issues

### Case Conversion Issues
- If you see validation errors like "role_id is required" when sending `roleId`, check that `ConvertRequestToSnakeCase` middleware is registered
- If frontend receives snake_case instead of camelCase, check that `ConvertResponseToCamelCase` middleware is registered
- Both middlewares are in `backend/bootstrap/app.php`

### Authentication Issues
- Token stored in `localStorage.getItem('token')`
- 401 responses auto-redirect to `/login`
- Check CORS settings in `backend/config/cors.php` allow `http://localhost:5173`

### Docker Issues
- Ports: Frontend (5173), Backend (8080), MySQL (3306), Redis (6379), Mailhog (8025)
- Use `make logs` to debug container issues
- Use `make clean` then `make init` for complete reset

## URLs & Access

- Frontend: http://localhost:5173
- Backend API: http://localhost:8080/api
- Mailhog: http://localhost:8025 (test emails)
- API Health Check: http://localhost:8080/up

## Database

- **Type:** MySQL 8.0 (configurable to PostgreSQL via docker-compose)
- **Access:** Port 3306, credentials in `backend/.env`
- **Migration strategy:** Sequential migrations, never modify existing migrations
- **Seeder:** `DatabaseSeeder.php` creates complete demo dataset

## Additional Notes

- All UI text is in French (fr-FR)
- Currency is EUR (€)
- Timezone: Europe/Paris
- Date format: ISO 8601 in API, localized in UI
- Rate limiting: 60 requests/minute per IP on API routes
