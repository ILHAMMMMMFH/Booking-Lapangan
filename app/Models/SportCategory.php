<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportCategory extends Model
{
    use HasFactory;

    protected $table = 'sport_categories';

    protected $fillable = [
        'name',
        'description',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class, 'sport_category_id');
    }
}