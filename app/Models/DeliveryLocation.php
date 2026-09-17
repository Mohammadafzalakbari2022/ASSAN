<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryLocation extends Model
{
    protected $fillable = [
        'delivery_user_id',
        'assignment_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function deliveryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_user_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class, 'assignment_id');
    }
}