<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    use HasFactory;

    protected $table = 'venues';

    protected $fillable = [
        'owner_id',
        'name',
        'city',
        'address',
        'open_time',
        'close_time',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class, 'venue_id');
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class, 'venue_id');
    }
}