<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsGoalContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_goal_id',
        'user_id',
        'amount_cents',
        'contribution_date',
        'note',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'contribution_date' => 'date',
    ];

    /**
     * Relations
     */
    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
