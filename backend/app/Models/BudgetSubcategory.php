<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetSubcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_category_id',
        'name',
        'planned_amount_cents',
        'sort_order',
    ];

    protected $casts = [
        'planned_amount_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function budgetCategory(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getActualAmountCentsAttribute(): int
    {
        return $this->expenses->sum('amount_cents');
    }

    public function getVarianceCentsAttribute(): int
    {
        return $this->actual_amount_cents - $this->planned_amount_cents;
    }

    public function getVariancePercentageAttribute(): ?float
    {
        if ($this->planned_amount_cents === 0) {
            return null;
        }
        return ($this->actual_amount_cents / $this->planned_amount_cents - 1) * 100;
    }
}
