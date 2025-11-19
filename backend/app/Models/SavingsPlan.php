<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month',
        'planned_cents',
    ];

    protected $casts = [
        'month' => 'date',
        'planned_cents' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActualCentsAttribute(): int
    {
        // Calculate actual savings from expenses in "Épargne" category for this month
        $budget = Budget::where('user_id', $this->user_id)
            ->where('month', $this->month)
            ->first();

        if (! $budget) {
            return 0;
        }

        $savingsCategory = $budget->categories()->where('name', 'Épargne')->first();

        if (! $savingsCategory) {
            return 0;
        }

        return $savingsCategory->subcategories->sum(function ($subcat) {
            return $subcat->expenses->sum('amount_cents');
        });
    }
}
