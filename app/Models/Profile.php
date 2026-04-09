<?php

namespace App\Models;

use App\Enums\BodyType;
use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\ProfileApprovalStatus;
use App\Enums\Religion;
use App\Enums\SkinTone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'display_name',
        'age',
        'date_of_birth',
        'country_id',
        'region_id',
        'district_id',
        'current_residence',
        'height_cm',
        'employment_status',
        'job_title',
        'marital_status',
        'has_children',
        'children_count',
        'religion',
        'body_type',
        'skin_tone',
        'about_me',
        'life_outlook',
        'approval_status',
        'is_profile_complete',
        'is_visible',
        'approved_by',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_status' => EmploymentStatus::class,
            'marital_status' => MaritalStatus::class,
            'religion' => Religion::class,
            'body_type' => BodyType::class,
            'skin_tone' => SkinTone::class,
            'date_of_birth' => 'date',
            'has_children' => 'boolean',
            'is_profile_complete' => 'boolean',
            'is_visible' => 'boolean',
            'approval_status' => ProfileApprovalStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class);
    }

    public function getAgeAttribute(mixed $value): int
    {
        if ($this->date_of_birth) {
            return Carbon::parse($this->date_of_birth)->age;
        }

        return max(18, (int) ($value ?? 18));
    }
}
