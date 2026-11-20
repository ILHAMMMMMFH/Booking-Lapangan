<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    // Wajib didefinisikan karena plural default Eloquent untuk Equipment adalah 'equipment'
    protected $table = 'equipments';

    protected $fillable = [
        'venue_id',
        'name',
        'total_stock',
        'available_stock',
        'rental_price',
    ];

    protected function casts(): array
    {
        return [
            'total_stock' => 'integer',
            'available_stock' => 'integer',
            'rental_price' => 'decimal:2',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    public function bookingEquipments(): HasMany
    {
        return $this->hasMany(BookingEquipment::class, 'equipment_id');
    }
}