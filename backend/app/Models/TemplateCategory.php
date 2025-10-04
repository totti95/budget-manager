<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_template_id',
        'name',
        'sort_order',
        'planned_amount_cents',
    ];

    protected $casts = [
        'planned_amount_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function budgetTemplate(): BelongsTo
    {
        return $this->belongsTo(BudgetTemplate::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(TemplateSubcategory::class)->orderBy('sort_order');
    }
}
