<?php

namespace App\Models;

use App\Enums\BodyType;
use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\SkinTone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPreference extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'preferred_gender',
        'min_age',
        'max_age',
        'preferred_religion',
        'must_have_job',
        'must_be_calm',
        'must_love_children',
        'must_be_modest',
        'must_be_respectful',
        'preferred_skin_tone',
        'preferred_body_type',
        'additional_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_gender' => Gender::class,
            'preferred_religion' => Religion::class,
            'preferred_skin_tone' => SkinTone::class,
            'preferred_body_type' => BodyType::class,
            'must_have_job' => 'boolean',
            'must_be_calm' => 'boolean',
            'must_love_children' => 'boolean',
            'must_be_modest' => 'boolean',
            'must_be_respectful' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
