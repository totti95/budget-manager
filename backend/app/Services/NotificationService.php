<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Check if budget exceeded after expense creation/update
     */
    public function checkBudgetExceeded(Expense $expense): void
    {
        // Load necessary relationships
        $expense->load('budget.user', 'subcategory.budgetCategory');

        $user = $expense->budget->user;
        $subcategory = $expense->subcategory;

        // Get user's notification settings
        $settings = $user->notificationSettings;

        // If no settings exist, create defaults
        if (!$settings) {
            $settings = $user->notificationSettings()->create([
                'budget_exceeded_enabled' => true,
                'budget_exceeded_threshold_percent' => 100,
                'savings_goal_enabled' => true,
            ]);
        }

        // Check if budget exceeded alerts are enabled
        if (!$settings->budget_exceeded_enabled) {
            return;
        }

        // Calculate actual vs planned
        $plannedCents = $subcategory->planned_amount_cents;

        // Avoid division by zero
        if ($plannedCents === 0) {
            return;
        }

        // Get actual amount (sum of all expenses in this subcategory)
        $actualCents = $subcategory->expenses()->sum('amount_cents');

        $percentageUsed = ($actualCents / $plannedCents) * 100;

        // Check if threshold is reached
        if ($percentageUsed < $settings->budget_exceeded_threshold_percent) {
            return;
        }

        // Check for duplicate notification
        // Avoid spam: Only create if no similar unread notification exists
        $existingNotification = Notification::where('user_id', $user->id)
            ->where('type', 'budget_exceeded')
            ->where('read', false)
            ->whereJsonContains('data->budget_id', $expense->budget_id)
            ->whereJsonContains('data->subcategory_id', $subcategory->id)
            ->first();

        if ($existingNotification) {
            // Update existing notification with new percentage
            $existingNotification->update([
                'data' => array_merge($existingNotification->data, [
                    'percentage_used' => round($percentageUsed, 1),
                    'actual_cents' => $actualCents,
                    'updated_at' => now()->toIso8601String(),
                ]),
                'message' => $this->buildBudgetExceededMessage(
                    $subcategory->name,
                    $actualCents,
                    $plannedCents,
                    $percentageUsed
                ),
            ]);
            return;
        }

        // Create new notification
        $this->createNotification(
            $user,
            'budget_exceeded',
            'Dépassement de budget détecté',
            $this->buildBudgetExceededMessage(
                $subcategory->name,
                $actualCents,
                $plannedCents,
                $percentageUsed
            ),
            [
                'budget_id' => $expense->budget_id,
                'budget_month' => $expense->budget->month->format('Y-m'),
                'subcategory_id' => $subcategory->id,
                'subcategory_name' => $subcategory->name,
                'category_name' => $subcategory->budgetCategory->name,
                'planned_cents' => $plannedCents,
                'actual_cents' => $actualCents,
                'percentage_used' => round($percentageUsed, 1),
                'threshold_percent' => $settings->budget_exceeded_threshold_percent,
            ]
        );

        Log::info('Budget exceeded notification created', [
            'user_id' => $user->id,
            'budget_id' => $expense->budget_id,
            'subcategory_id' => $subcategory->id,
            'percentage_used' => round($percentageUsed, 1),
        ]);
    }

    /**
     * Build human-readable message for budget exceeded notification
     */
    private function buildBudgetExceededMessage(
        string $subcategoryName,
        int $actualCents,
        int $plannedCents,
        float $percentageUsed
    ): string {
        $actualEuros = $actualCents / 100;
        $plannedEuros = $plannedCents / 100;
        $percentageRounded = round($percentageUsed, 0);

        return sprintf(
            'La sous-catégorie "%s" a atteint %d%% de son budget (%.2f€ / %.2f€).',
            $subcategoryName,
            $percentageRounded,
            $actualEuros,
            $plannedEuros
        );
    }

    /**
     * Create a notification
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'read' => false,
        ]);
    }
}
