<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TemplateCategory::class)->orderBy('sort_order');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'generated_from_template_id');
    }

    protected static function boot()
    {
        parent::boot();

        // When setting a template as default, unset all others for this user
        static::saving(function ($template) {
            if ($template->is_default) {
                static::where('user_id', $template->user_id)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
