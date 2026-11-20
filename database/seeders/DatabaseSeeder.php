<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Field;
use App\Models\SportCategory;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users (2 Owner & 2 Penyewa/User)
        $owner1 = User::create([
            'name' => 'Budi Santoso (Owner Arena)',
            'email' => 'owner1@sportbooking.test',
            'password' => Hash::make('password123'),
            'phone' => '081234567801',
            'role' => 'owner',
        ]);

        $owner2 = User::create([
            'name' => 'Hendra Wijaya (Owner Champion)',
            'email' => 'owner2@sportbooking.test',
            'password' => Hash::make('password123'),
            'phone' => '081234567802',
            'role' => 'owner',
        ]);

        User::create([
            'name' => 'Rizky Pratama (Penyewa)',
            'email' => 'user1@sportbooking.test',
            'password' => Hash::make('password123'),
            'phone' => '085711223344',
            'role' => 'user',
        ]);

        User::create([
            'name' => 'Andi Saputra (Penyewa)',
            'email' => 'user2@sportbooking.test',
            'password' => Hash::make('password123'),
            'phone' => '085755667788',
            'role' => 'user',
        ]);

        // 2. Seed Sport Categories
        $catMiniSoccer = SportCategory::create([
            'name' => 'Mini Soccer',
            'description' => 'Lapangan sepak bola ukuran 7v7 atau 8v8 dengan rumput sintetis standar FIFA.',
        ]);

        $catFutsal = SportCategory::create([
            'name' => 'Futsal',
            'description' => 'Lapangan futsal indoor dengan lantai vinyl/interlock.',
        ]);

        $catBadminton = SportCategory::create([
            'name' => 'Badminton',
            'description' => 'Lapangan bulu tangkis indoor dengan karpet vinyl standar BWF.',
        ]);

        // 3. Seed Venues
        $venue1 = Venue::create([
            'owner_id' => $owner1->id,
            'name' => 'Surabaya Sport Center (SSC)',
            'city' => 'Surabaya',
            'address' => 'Jl. Raya Kertajaya Indah No. 88, Sukolilo, Surabaya',
            'open_time' => '07:00:00',
            'close_time' => '23:00:00',
        ]);

        $venue2 = Venue::create([
            'owner_id' => $owner2->id,
            'name' => 'Champion Badminton & Futsal Hall',
            'city' => 'Surabaya',
            'address' => 'Jl. Ahmad Yani No. 142, Wonocolo, Surabaya',
            'open_time' => '08:00:00',
            'close_time' => '22:00:00',
        ]);

        // 4. Seed Fields untuk Venue 1 (SSC)
        Field::create([
            'venue_id' => $venue1->id,
            'sport_category_id' => $catMiniSoccer->id,
            'name' => 'Mini Soccer Field A (Outdoor)',
            'regular_rate' => 350000.00, // 07:00 - 18:00
            'prime_rate' => 500000.00,   // 18:00 - 23:00
            'is_active' => true,
        ]);

        Field::create([
            'venue_id' => $venue1->id,
            'sport_category_id' => $catFutsal->id,
            'name' => 'Futsal Court 1 (Vinyl)',
            'regular_rate' => 120000.00,
            'prime_rate' => 175000.00,
            'is_active' => true,
        ]);

        Field::create([
            'venue_id' => $venue1->id,
            'sport_category_id' => $catFutsal->id,
            'name' => 'Futsal Court 2 (Interlock)',
            'regular_rate' => 100000.00,
            'prime_rate' => 150000.00,
            'is_active' => true,
        ]);

        // 5. Seed Fields untuk Venue 2 (Champion Hall)
        Field::create([
            'venue_id' => $venue2->id,
            'sport_category_id' => $catBadminton->id,
            'name' => 'Badminton Line 1 (Karpet BWF)',
            'regular_rate' => 50000.00,
            'prime_rate' => 75000.00,
            'is_active' => true,
        ]);

        Field::create([
            'venue_id' => $venue2->id,
            'sport_category_id' => $catBadminton->id,
            'name' => 'Badminton Line 2 (Karpet BWF)',
            'regular_rate' => 50000.00,
            'prime_rate' => 75000.00,
            'is_active' => true,
        ]);

        // 6. Seed Equipments untuk Venue 1 (SSC)
        Equipment::create([
            'venue_id' => $venue1->id,
            'name' => 'Rompi Tim (Set 10 Pcs)',
            'total_stock' => 10,
            'available_stock' => 10,
            'rental_price' => 25000.00,
        ]);

        Equipment::create([
            'venue_id' => $venue1->id,
            'name' => 'Bola Mini Soccer Size 5',
            'total_stock' => 15,
            'available_stock' => 15,
            'rental_price' => 20000.00,
        ]);

        Equipment::create([
            'venue_id' => $venue1->id,
            'name' => 'Sepatu Futsal Rental (All Size)',
            'total_stock' => 20,
            'available_stock' => 20,
            'rental_price' => 15000.00,
        ]);

        // 7. Seed Equipments untuk Venue 2 (Champion Hall)
        Equipment::create([
            'venue_id' => $venue2->id,
            'name' => 'Raket Badminton Yonex',
            'total_stock' => 12,
            'available_stock' => 12,
            'rental_price' => 15000.00,
        ]);

        Equipment::create([
            'venue_id' => $venue2->id,
            'name' => 'Shuttlecock (Slop isi 12)',
            'total_stock' => 30,
            'available_stock' => 30,
            'rental_price' => 85000.00,
        ]);
    }
}