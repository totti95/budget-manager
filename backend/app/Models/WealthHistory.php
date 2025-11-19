<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WealthHistory extends Model
{
    use HasFactory;

    protected $table = 'wealth_history';

    protected $fillable = [
        'user_id',
        'recorded_at',
        'total_assets_cents',
        'total_liabilities_cents',
        'net_worth_cents',
    ];

    protected $casts = [
        'recorded_at' => 'date',
        'total_assets_cents' => 'integer',
        'total_liabilities_cents' => 'integer',
        'net_worth_cents' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
