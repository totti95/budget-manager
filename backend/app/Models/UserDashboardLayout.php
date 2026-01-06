<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDashboardLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'layout_config',
        'widget_settings',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'widget_settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getDefaultLayout(): array
    {
        return [
            ['i' => 'current-month-summary', 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 3],
            ['i' => 'top-5-categories', 'x' => 0, 'y' => 3, 'w' => 6, 'h' => 5],
            ['i' => 'asset-evolution', 'x' => 6, 'y' => 3, 'w' => 6, 'h' => 6],
            ['i' => 'savings-rate', 'x' => 0, 'y' => 9, 'w' => 6, 'h' => 6],
        ];
    }

    public static function getDefaultWidgetSettings(): array
    {
        return [
            'asset-evolution' => [
                'dateRange' => '12m',
            ],
            'savings-rate' => [
                'showGraph' => true,
                'months' => 12,
            ],
        ];
    }
}
