<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\Budget;
use App\Models\BudgetTemplate;
use App\Models\Notification;
use App\Models\RecurringExpense;
use App\Models\SavingsGoal;
use App\Models\SavingsPlan;
use App\Models\Tag;
use App\Policies\AssetPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\BudgetTemplatePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\RecurringExpensePolicy;
use App\Policies\SavingsGoalPolicy;
use App\Policies\SavingsPlanPolicy;
use App\Policies\TagPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Asset::class => AssetPolicy::class,
        Budget::class => BudgetPolicy::class,
        BudgetTemplate::class => BudgetTemplatePolicy::class,
        Notification::class => NotificationPolicy::class,
        RecurringExpense::class => RecurringExpensePolicy::class,
        SavingsGoal::class => SavingsGoalPolicy::class,
        SavingsPlan::class => SavingsPlanPolicy::class,
        Tag::class => TagPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
