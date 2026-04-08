<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'iso2',
        'phone_code',
        'flag',
        'requires_region_district',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_region_district' => 'boolean',
        ];
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }
}
