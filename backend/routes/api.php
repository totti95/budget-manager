<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetTemplateController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetCategoryController;
use App\Http\Controllers\BudgetSubcategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\SavingsPlanController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\AdminUserController;

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
        'timestamp' => now()->toIso8601String()
    ]);
});

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    // Protection contre brute-force : max 5 tentatives par minute
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
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
    Route::post('budgets/{budget}/expenses/import-csv', [ExpenseController::class, 'importCsv']);
    Route::get('budgets/{budget}/expenses/export-csv', [ExpenseController::class, 'exportCsv']);

    // Stats
    Route::get('budgets/{budget}/stats/summary', [StatsController::class, 'summary']);
    Route::get('budgets/{budget}/stats/by-category', [StatsController::class, 'byCategory']);
    Route::get('budgets/{budget}/stats/by-subcategory', [StatsController::class, 'bySubcategory']);

    // Assets (Patrimoine)
    Route::get('assets/types', [AssetController::class, 'types']);
    Route::apiResource('assets', AssetController::class);

    // Savings Plans
    Route::get('savings', [SavingsPlanController::class, 'index']);
    Route::get('savings/{savingsPlan}', [SavingsPlanController::class, 'show']);
    Route::put('savings/{savingsPlan}', [SavingsPlanController::class, 'update']);
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
