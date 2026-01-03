<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsGoal extends Model
{
    protected $fillable = [
        'user_id',
        'asset_id',
        'name',
        'description',
        'target_amount_cents',
        'current_amount_cents',
        'start_date',
        'target_date',
        'status',
        'priority',
        'notify_milestones',
        'notify_risk',
        'notify_reminder',
        'reminder_day_of_month',
        'suggested_monthly_amount_cents',
    ];

    protected $casts = [
        'target_amount_cents' => 'integer',
        'current_amount_cents' => 'integer',
        'suggested_monthly_amount_cents' => 'integer',
        'start_date' => 'date',
        'target_date' => 'date',
        'priority' => 'integer',
        'reminder_day_of_month' => 'integer',
        'notify_milestones' => 'boolean',
        'notify_risk' => 'boolean',
        'notify_reminder' => 'boolean',
    ];

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(SavingsGoalContribution::class);
    }

    /**
     * Computed Attributes
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount_cents === 0) {
            return 0;
        }
        return min(($this->current_amount_cents / $this->target_amount_cents) * 100, 100);
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->target_date) {
            return null;
        }
        return now()->diffInDays($this->target_date, false);
    }

    public function getDaysElapsedAttribute(): int
    {
        return $this->start_date->diffInDays(now());
    }

    public function getTimeProgressPercentageAttribute(): float
    {
        if (!$this->target_date) {
            return 0;
        }

        $total = $this->start_date->diffInDays($this->target_date);
        if ($total === 0) {
            return 100;
        }

        $elapsed = $this->start_date->diffInDays(now());
        return min(($elapsed / $total) * 100, 100);
    }

    public function getIsOnTrackAttribute(): bool
    {
        // Si pas de date limite, toujours on track
        if (!$this->target_date) {
            return true;
        }

        // Comparer progression montant vs progression temps
        return $this->progress_percentage >= $this->time_progress_percentage;
    }

    /**
     * Calcul du montant mensuel nécessaire
     */
    public function calculateSuggestedMonthlyAmount(): int
    {
        if (!$this->target_date || $this->status !== 'active') {
            return 0;
        }

        $remaining = $this->target_amount_cents - $this->current_amount_cents;
        if ($remaining <= 0) {
            return 0;
        }

        $monthsRemaining = max(now()->diffInMonths($this->target_date), 1);
        return (int) ceil($remaining / $monthsRemaining);
    }
}
