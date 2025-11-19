<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'name',
        'sort_order',
        'planned_amount_cents',
    ];

    protected $casts = [
        'planned_amount_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(BudgetSubcategory::class)->orderBy('sort_order');
    }

    /**
     * Calculer le montant réel des dépenses.
     * ATTENTION: Nécessite que la relation 'subcategories.expenses' soit chargée pour éviter N+1
     */
    public function getActualAmountCentsAttribute(): int
    {
        return $this->subcategories->sum(function ($subcat) {
            return $subcat->expenses->sum('amount_cents');
        });
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
