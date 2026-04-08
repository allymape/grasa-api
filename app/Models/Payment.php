<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'connection_request_id',
        'payer_id',
        'amount',
        'method',
        'reference',
        'status',
        'confirmed_by',
        'confirmed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function connectionRequest(): BelongsTo
    {
        return $this->belongsTo(ConnectionRequest::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
