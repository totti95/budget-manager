<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetCategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetSubcategoryController;
use App\Http\Controllers\BudgetTemplateController;
use App\Http\Controllers\DashboardLayoutController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\SavingsGoalContributionController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\SavingsPlanController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WealthHistoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoint (public, no auth required)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Budget Manager API',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    // Protection contre brute-force : max 5 tentatives par minute
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
    });
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Budget Templates
    Route::apiResource('templates', BudgetTemplateController::class);
    Route::post('templates/{template}/set-default', [BudgetTemplateController::class, 'setDefault']);

    // Budgets
    Route::get('budgets', [BudgetController::class, 'index']);
    Route::post('budgets/generate', [BudgetController::class, 'generate']);
    Route::get('budgets/{budget}', [BudgetController::class, 'show']);
    Route::put('budgets/{budget}', [BudgetController::class, 'update']);
    Route::delete('budgets/{budget}', [BudgetController::class, 'destroy']);

    // Budget Categories (within a budget)
    Route::post('budgets/{budget}/categories', [BudgetCategoryController::class, 'store']);
    Route::put('budgets/{budget}/categories/{category}', [BudgetCategoryController::class, 'update']);
    Route::delete('budgets/{budget}/categories/{category}', [BudgetCategoryController::class, 'destroy']);

    // Budget Subcategories
    Route::post('budgets/{budget}/categories/{category}/subcategories', [BudgetSubcategoryController::class, 'store']);
    Route::put('budgets/{budget}/subcategories/{subcategory}', [BudgetSubcategoryController::class, 'update']);
    Route::delete('budgets/{budget}/subcategories/{subcategory}', [BudgetSubcategoryController::class, 'destroy']);

    // Expenses
    Route::get('budgets/{budget}/expenses', [ExpenseController::class, 'index']);
    Route::post('budgets/{budget}/expenses', [ExpenseController::class, 'store']);
    Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);

    // Tags
    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store']);
    Route::put('tags/{tag}', [TagController::class, 'update']);
    Route::delete('tags/{tag}', [TagController::class, 'destroy']);

    // Recurring Expenses
    Route::get('recurring-expenses', [RecurringExpenseController::class, 'index']);
    Route::post('recurring-expenses', [RecurringExpenseController::class, 'store']);
    Route::get('recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'show']);
    Route::put('recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'update']);
    Route::delete('recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'destroy']);
    Route::patch('recurring-expenses/{recurringExpense}/toggle-active', [RecurringExpenseController::class, 'toggleActive']);

    // Stats (moved to throttled group below)
    // Route::get('budgets/{budget}/stats/summary', [StatsController::class, 'summary']);
    // Route::get('budgets/{budget}/stats/by-category', [StatsController::class, 'byCategory']);
    // Route::get('budgets/{budget}/stats/by-subcategory', [StatsController::class, 'bySubcategory']);
    // Route::get('budgets/{budget}/stats/by-tag', [StatsController::class, 'byTag']);
    // Route::get('budgets/{budget}/stats/expense-distribution', [StatsController::class, 'expenseDistribution']);
    // Route::get('stats/wealth-evolution', [StatsController::class, 'wealthEvolution']);

    // Assets (Patrimoine)
    Route::get('assets/types', [AssetController::class, 'types']);
    Route::apiResource('assets', AssetController::class);

    // Savings Plans
    Route::get('savings', [SavingsPlanController::class, 'index']);
    Route::get('savings/{savingsPlan}', [SavingsPlanController::class, 'show']);
    Route::put('savings/{savingsPlan}', [SavingsPlanController::class, 'update']);

    // Savings Goals
    Route::apiResource('savings-goals', SavingsGoalController::class);
    Route::patch('savings-goals/{savingsGoal}/sync-asset', [SavingsGoalController::class, 'syncAsset']);

    // Savings Goal Contributions
    Route::get('savings-goals/{savingsGoal}/contributions', [SavingsGoalContributionController::class, 'index']);
    Route::post('savings-goals/{savingsGoal}/contributions', [SavingsGoalContributionController::class, 'store']);
    Route::delete('savings-goals/{savingsGoal}/contributions/{contribution}', [SavingsGoalContributionController::class, 'destroy']);

    // Wealth History
    Route::get('wealth-history', [WealthHistoryController::class, 'index']);
    Route::post('wealth-history/record', [WealthHistoryController::class, 'record']);
    Route::delete('wealth-history/{wealthHistory}', [WealthHistoryController::class, 'destroy']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/{notification}/mark-read', [NotificationController::class, 'markRead']);
        Route::put('/mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        Route::delete('/', [NotificationController::class, 'clearAll']);
    });

    // Notification Settings
    Route::get('notification-settings', [NotificationSettingController::class, 'show']);
    Route::put('notification-settings', [NotificationSettingController::class, 'update']);

    // Dashboard Layout
    Route::get('dashboard/layout', [DashboardLayoutController::class, 'show']);
    Route::put('dashboard/layout', [DashboardLayoutController::class, 'update']);
    Route::delete('dashboard/layout', [DashboardLayoutController::class, 'destroy']);

    // Enhanced Stats (moved to throttled group below)
    // Route::get('budgets/{budget}/stats/top-categories', [StatsController::class, 'topCategories']);
    // Route::get('stats/savings-rate-evolution', [StatsController::class, 'savingsRateEvolution']);

    // ===== RATE-LIMITED ENDPOINTS (100 requests/minute) =====
    // These endpoints are computationally expensive and need stricter rate limiting
    Route::middleware('throttle:100,1')->group(function () {
        // Budget comparison (expensive operation)
        Route::get('budgets/compare', [BudgetController::class, 'compare']);
        Route::get('budgets/{budget}/export-pdf', [BudgetController::class, 'exportPdf']);

        // CSV Import/Export (file operations)
        Route::post('budgets/{budget}/expenses/import-csv', [ExpenseController::class, 'importCsv']);
        Route::get('budgets/{budget}/expenses/export-csv', [ExpenseController::class, 'exportCsv']);

        // All statistics endpoints (database-intensive)
        Route::get('budgets/{budget}/stats/summary', [StatsController::class, 'summary']);
        Route::get('budgets/{budget}/stats/by-category', [StatsController::class, 'byCategory']);
        Route::get('budgets/{budget}/stats/by-subcategory', [StatsController::class, 'bySubcategory']);
        Route::get('budgets/{budget}/stats/by-tag', [StatsController::class, 'byTag']);
        Route::get('budgets/{budget}/stats/expense-distribution', [StatsController::class, 'expenseDistribution']);
        Route::get('budgets/{budget}/stats/top-categories', [StatsController::class, 'topCategories']);
        Route::get('stats/wealth-evolution', [StatsController::class, 'wealthEvolution']);
        Route::get('stats/savings-rate-evolution', [StatsController::class, 'savingsRateEvolution']);
    });
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
    Route::put('users/{user}', [AdminUserController::class, 'update']);
    Route::put('users/{user}/password', [AdminUserController::class, 'updatePassword']);
    Route::put('users/{user}/restore', [AdminUserController::class, 'restore'])->withTrashed();
    Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
});
