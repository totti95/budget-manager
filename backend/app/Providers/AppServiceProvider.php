<?php

namespace App\Providers;

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Auto-create notification settings when user is created
        User::created(function (User $user) {
            NotificationSetting::create([
                'user_id' => $user->id,
                'budget_exceeded_enabled' => true,
                'budget_exceeded_threshold_percent' => 100,
                'savings_goal_enabled' => true,
            ]);
        });
    }
}
