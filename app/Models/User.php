<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi sebagai Owner yang memiliki banyak Venue
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'owner_id');
    }

    // Relasi sebagai User (Penyewa) yang melakukan banyak Booking
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    // Relasi sebagai Owner/Admin yang memverifikasi pembayaran
    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}