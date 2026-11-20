<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    use HasFactory;

    protected $table = 'fields';

    protected $fillable = [
        'venue_id',
        'sport_category_id',
        'name',
        'regular_rate',
        'prime_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'regular_rate' => 'decimal:2',
            'prime_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class, 'sport_category_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'field_id');
    }
}