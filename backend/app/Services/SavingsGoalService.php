<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\SavingsGoal;
use Illuminate\Support\Facades\Log;

class SavingsGoalService
{
    /**
     * Vérifie et crée les notifications de jalons (25%, 50%, 75%, 100%)
     */
    public function checkMilestones(SavingsGoal $goal, int $previousAmount): void
    {
        if (!$goal->notify_milestones) {
            return;
        }

        $user = $goal->user;
        $settings = $user->notificationSettings ?? NotificationSetting::create(['user_id' => $user->id]);

        if (!$settings->savings_goal_milestone_enabled) {
            return;
        }

        $progress = $goal->progress_percentage;
        $previousProgress = $goal->target_amount_cents > 0
            ? ($previousAmount / $goal->target_amount_cents) * 100
            : 0;

        $milestones = [25, 50, 75, 100];

        foreach ($milestones as $milestone) {
            if ($progress >= $milestone && $previousProgress < $milestone) {
                $this->createMilestoneNotification($goal, $milestone);
            }
        }

        // Si 100% atteint, marquer comme complété
        if ($progress >= 100 && $goal->status === 'active') {
            $goal->status = 'completed';
            $goal->save();
        }
    }

    /**
     * Crée une notification de jalon
     */
    protected function createMilestoneNotification(SavingsGoal $goal, int $milestone): void
    {
        $messages = [
            25 => 'Vous avez atteint 25% de votre objectif !',
            50 => 'Vous êtes à mi-chemin de votre objectif !',
            75 => 'Plus que 25% pour atteindre votre objectif !',
            100 => 'Félicitations ! Objectif atteint !',
        ];

        Notification::create([
            'user_id' => $goal->user_id,
            'type' => 'savings_goal_milestone',
            'title' => $goal->name,
            'message' => $messages[$milestone],
            'data' => [
                'goal_id' => $goal->id,
                'milestone' => $milestone,
                'current_amount_cents' => $goal->current_amount_cents,
                'target_amount_cents' => $goal->target_amount_cents,
                'progress_percentage' => $goal->progress_percentage,
            ],
        ]);

        Log::info('Savings goal milestone notification created', [
            'goal_id' => $goal->id,
            'milestone' => $milestone,
        ]);
    }

    /**
     * Vérifie si l'objectif est en retard (à mi-parcours)
     */
    public function checkRiskNotifications(SavingsGoal $goal): void
    {
        if (!$goal->notify_risk || !$goal->target_date) {
            return;
        }

        $user = $goal->user;
        $settings = $user->notificationSettings ?? NotificationSetting::create(['user_id' => $user->id]);

        if (!$settings->savings_goal_risk_enabled) {
            return;
        }

        // Vérification à mi-parcours uniquement
        $timeProgress = $goal->time_progress_percentage;

        // Si entre 48% et 52% du temps écoulé (tolérance pour ne pas rater le moment exact)
        if ($timeProgress >= 48 && $timeProgress <= 52) {
            $amountProgress = $goal->progress_percentage;

            // Si on n'a pas atteint 50% du montant
            if ($amountProgress < 50) {
                // Vérifier qu'on n'a pas déjà envoyé cette notification
                $existing = Notification::where('user_id', $goal->user_id)
                    ->where('type', 'savings_goal_risk')
                    ->whereJsonContains('data->goal_id', $goal->id)
                    ->where('read', false)
                    ->first();

                if (!$existing) {
                    $this->createRiskNotification($goal);
                }
            }
        }
    }

    /**
     * Crée une notification de risque
     */
    protected function createRiskNotification(SavingsGoal $goal): void
    {
        $deficit = 50 - $goal->progress_percentage;

        Notification::create([
            'user_id' => $goal->user_id,
            'type' => 'savings_goal_risk',
            'title' => 'Objectif en retard : ' . $goal->name,
            'message' => sprintf(
                "Attention : vous n'avez atteint que %.1f%% de votre objectif alors que 50%% du délai est écoulé.",
                $goal->progress_percentage
            ),
            'data' => [
                'goal_id' => $goal->id,
                'current_amount_cents' => $goal->current_amount_cents,
                'target_amount_cents' => $goal->target_amount_cents,
                'progress_percentage' => $goal->progress_percentage,
                'time_progress_percentage' => $goal->time_progress_percentage,
                'deficit_percentage' => $deficit,
            ],
        ]);

        Log::info('Savings goal risk notification created', [
            'goal_id' => $goal->id,
            'progress' => $goal->progress_percentage,
        ]);
    }

    /**
     * Envoie les rappels mensuels pour les objectifs actifs
     */
    public function sendMonthlyReminders(): int
    {
        $today = now();
        $dayOfMonth = $today->day;

        // Récupérer tous les objectifs actifs avec rappel activé pour ce jour
        $goals = SavingsGoal::where('status', 'active')
            ->where('notify_reminder', true)
            ->where('reminder_day_of_month', $dayOfMonth)
            ->with('user.notificationSettings')
            ->get();

        $sent = 0;

        foreach ($goals as $goal) {
            $settings = $goal->user->notificationSettings;

            if ($settings && $settings->savings_goal_reminder_enabled) {
                $this->createReminderNotification($goal);
                $sent++;
            }
        }

        Log::info('Monthly savings goal reminders sent', ['count' => $sent]);

        return $sent;
    }

    /**
     * Crée une notification de rappel mensuel
     */
    protected function createReminderNotification(SavingsGoal $goal): void
    {
        $suggested = $goal->suggested_monthly_amount_cents ?? $goal->calculateSuggestedMonthlyAmount();

        Notification::create([
            'user_id' => $goal->user_id,
            'type' => 'savings_goal_reminder',
            'title' => 'Rappel d\'épargne : ' . $goal->name,
            'message' => sprintf(
                "N'oubliez pas de verser %s pour rester sur la bonne voie !",
                number_format($suggested / 100, 2, ',', ' ') . ' €'
            ),
            'data' => [
                'goal_id' => $goal->id,
                'suggested_amount_cents' => $suggested,
                'current_amount_cents' => $goal->current_amount_cents,
                'target_amount_cents' => $goal->target_amount_cents,
            ],
        ]);
    }
}
