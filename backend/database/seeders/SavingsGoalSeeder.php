<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalContribution;
use App\Models\User;
use Illuminate\Database\Seeder;

class SavingsGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'demo@budgetmanager.local')->first();

        if (!$demoUser) {
            return;
        }

        // Objectif 1 : Ordinateur (court terme, en cours)
        $goal1 = SavingsGoal::create([
            'user_id' => $demoUser->id,
            'name' => 'Nouvel ordinateur portable',
            'description' => 'MacBook Pro pour le développement',
            'target_amount_cents' => 200000, // 2000€
            'current_amount_cents' => 85000, // 850€
            'start_date' => now()->subMonths(1),
            'target_date' => now()->addMonths(3),
            'status' => 'active',
            'priority' => 80,
            'notify_milestones' => true,
            'notify_risk' => true,
            'notify_reminder' => true,
            'reminder_day_of_month' => 1,
            'suggested_monthly_amount_cents' => 40000, // 400€/mois
        ]);

        // Contributions pour goal1
        SavingsGoalContribution::create([
            'savings_goal_id' => $goal1->id,
            'user_id' => $demoUser->id,
            'amount_cents' => 50000,
            'contribution_date' => now()->subMonths(1),
            'note' => 'Versement initial',
        ]);

        SavingsGoalContribution::create([
            'savings_goal_id' => $goal1->id,
            'user_id' => $demoUser->id,
            'amount_cents' => 35000,
            'contribution_date' => now()->subDays(15),
            'note' => 'Bonus de fin d\'année',
        ]);

        // Objectif 2 : Vacances (moyen terme)
        SavingsGoal::create([
            'user_id' => $demoUser->id,
            'name' => 'Vacances d\'été au Japon',
            'description' => 'Voyage de 2 semaines à Tokyo et Kyoto',
            'target_amount_cents' => 300000, // 3000€
            'current_amount_cents' => 120000, // 1200€
            'start_date' => now()->subMonths(2),
            'target_date' => now()->addMonths(6),
            'status' => 'active',
            'priority' => 60,
            'notify_milestones' => true,
            'notify_risk' => true,
            'notify_reminder' => true,
            'reminder_day_of_month' => 5,
            'suggested_monthly_amount_cents' => 30000, // 300€/mois
        ]);

        // Objectif 3 : Fonds d'urgence (long terme, lié à un actif)
        $savingsAccount = Asset::where('user_id', $demoUser->id)
            ->where('type', 'épargne')
            ->first();

        SavingsGoal::create([
            'user_id' => $demoUser->id,
            'asset_id' => $savingsAccount ? $savingsAccount->id : null,
            'name' => 'Fonds d\'urgence',
            'description' => '6 mois de dépenses en réserve',
            'target_amount_cents' => 1200000, // 12000€
            'current_amount_cents' => $savingsAccount ? $savingsAccount->value_cents : 0,
            'start_date' => now()->subMonths(6),
            'target_date' => now()->addMonths(18),
            'status' => 'active',
            'priority' => 100,
            'notify_milestones' => true,
            'notify_risk' => false,
            'notify_reminder' => false,
        ]);

        // Objectif 4 : Objectif complété
        SavingsGoal::create([
            'user_id' => $demoUser->id,
            'name' => 'Nouveau téléphone',
            'target_amount_cents' => 80000, // 800€
            'current_amount_cents' => 80000,
            'start_date' => now()->subMonths(4),
            'target_date' => now()->subMonth(),
            'status' => 'completed',
            'priority' => 50,
        ]);
    }
}
