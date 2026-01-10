<?php

namespace App\Providers;

use App\Contracts\BudgetRepositoryInterface;
use App\Contracts\ExpenseRepositoryInterface;
use App\Repositories\BudgetRepository;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to their concrete implementations
        $this->app->bind(BudgetRepositoryInterface::class, BudgetRepository::class);
        $this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
