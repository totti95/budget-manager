<?php

namespace App\Console\Commands;

use App\Models\SavingsGoal;
use App\Services\SavingsGoalService;
use Illuminate\Console\Command;

class CheckSavingsGoalsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'savings-goals:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check savings goals for risk notifications and monthly reminders';

    /**
     * Execute the console command.
     */
    public function handle(SavingsGoalService $service)
    {
        $this->info('Checking savings goals status...');

        // Vérifier les risques pour tous les objectifs actifs
        $activeGoals = SavingsGoal::where('status', 'active')
            ->whereNotNull('target_date')
            ->where('notify_risk', true)
            ->get();

        $this->info("Found {$activeGoals->count()} active goals with risk notifications enabled");

        foreach ($activeGoals as $goal) {
            $service->checkRiskNotifications($goal);
        }

        // Envoyer les rappels mensuels
        $remindersSent = $service->sendMonthlyReminders();

        $this->info("Risk checks completed. {$remindersSent} reminders sent.");

        return Command::SUCCESS;
    }
}
