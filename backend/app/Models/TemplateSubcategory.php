<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateSubcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_category_id',
        'name',
        'planned_amount_cents',
        'sort_order',
    ];

    protected $casts = [
        'planned_amount_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function templateCategory(): BelongsTo
    {
        return $this->belongsTo(TemplateCategory::class);
    }
}
