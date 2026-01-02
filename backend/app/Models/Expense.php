<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'budget_subcategory_id',
        'date',
        'label',
        'amount_cents',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount_cents' => 'integer',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(BudgetSubcategory::class, 'budget_subcategory_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
