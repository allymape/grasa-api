<?php

namespace App\Models;

use App\Enums\Gender;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'gender',
        'password',
        'is_admin',
        'is_active',
        'is_blocked',
        'blocked_at',
        'blocked_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'gender' => Gender::class,
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function partnerPreference(): HasOne
    {
        return $this->hasOne(PartnerPreference::class);
    }

    public function profilePhotos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class);
    }

    public function sentConnectionRequests(): HasMany
    {
        return $this->hasMany(ConnectionRequest::class, 'sender_id');
    }

    public function receivedConnectionRequests(): HasMany
    {
        return $this->hasMany(ConnectionRequest::class, 'receiver_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payer_id');
    }

    public function confirmedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'confirmed_by');
    }

    public function approvedProfiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'approved_by');
    }

    public function settingUpdates(): HasMany
    {
        return $this->hasMany(SystemSetting::class, 'updated_by');
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportsReceived(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    public function reviewedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'reviewed_by');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim(implode(' ', array_filter([$this->first_name, $this->last_name])))
        );
    }

    public function isSuspended(): bool
    {
        return $this->is_blocked || ! $this->is_active;
    }
}
