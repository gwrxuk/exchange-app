<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const STATUS_OPEN = 1;

    const STATUS_FILLED = 2;

    const STATUS_CANCELLED = 3;

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'remaining_amount',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'remaining_amount' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
