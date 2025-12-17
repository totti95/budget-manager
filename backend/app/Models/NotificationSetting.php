<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'budget_exceeded_enabled',
        'budget_exceeded_threshold_percent',
        'savings_goal_enabled',
    ];

    protected $casts = [
        'budget_exceeded_enabled' => 'boolean',
        'budget_exceeded_threshold_percent' => 'integer',
        'savings_goal_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
