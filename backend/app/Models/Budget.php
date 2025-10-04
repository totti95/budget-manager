<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month',
        'name',
        'generated_from_template_id',
    ];

    protected $casts = [
        'month' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BudgetTemplate::class, 'generated_from_template_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class)->orderBy('sort_order');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getTotalPlannedCentsAttribute(): int
    {
        return $this->categories->sum('planned_amount_cents');
    }

    public function getTotalActualCentsAttribute(): int
    {
        return $this->expenses->sum('amount_cents');
    }

    public function getVarianceCentsAttribute(): int
    {
        return $this->total_actual_cents - $this->total_planned_cents;
    }
}
