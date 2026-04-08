<?php

namespace App\Models;

use App\Enums\ConnectionRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectionRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status',
        'message',
        'responded_at',
        'connected_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ConnectionRequestStatus::class,
            'responded_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ConnectionRequestStatus::activeValues());
    }
}
