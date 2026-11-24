# Sport Venue Booking & Equipment Rental

Platform web dan REST API untuk reservasi lapangan olahraga multi-venue (Mini Soccer, Futsal, Badminton) sekaligus peminjaman perlengkapan tambahan dengan dukungan transaksi konkuren di MySQL InnoDB.

## Fitur Utama
- **Multi-Role (`owner` & `user`)**: Autentikasi web (Session) dan REST API (Laravel Sanctum) dengan `RoleMiddleware`.
- **Anti-Bentrok Jadwal**: Validasi irisan jam pada lapangan dan tanggal yang sama menggunakan `DB::transaction` dan `lockForUpdate`.
- **Kalkulasi Tarif Dinamis**: Pemisahan otomatis tarif **Reguler** (< 18:00) dan **Prime Time** (>= 18:00).
- **Mutasi Stok & Denda Otomatis**: *Stock hold* alat saat booking dibuat; saat sesi selesai (`completed`), alat yang kembali utuh menambah stok kembali, sedangkan alat hilang/rusak otomatis menghitung denda (2x harga sewa) dan membuat tagihan `penalty`.
- **Siklus Pembayaran Bertahap & Pembatalan**: Mendukung pembayaran `dp`, `settlement`, `penalty`, reschedule jadwal, serta pembatalan yang melepas slot, mengembalikan stok alat, dan mencatat `refund`.
- **Manajemen Venue (Owner)**: CRUD venue, lapangan, dan perlengkapan (dengan proteksi riwayat transaksi), verifikasi pembayaran, check-in, dan penyelesaian sesi.

## Tech Stack
- **Backend**: PHP >= 8.2, Laravel 12, Laravel Sanctum
- **Database**: MySQL (InnoDB — 8 tabel relasional)
- **Frontend**: Blade, Tailwind CSS, Vite (Node.js & NPM)

## Instalasi Lokal
```bash
git clone [https://github.com/ILHAMMMMMFH/Booking-Lapangan.git](https://github.com/ILHAMMMMMFH/Booking-Lapangan.git)
cd Booking-Lapangan
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Sesuaikan koneksi database di `.env`, lalu jalankan:
```bash
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve
```

## Akun Demo (Seeder)
Password semua akun: `password123`
- **Owner 1**: `owner1@sportbooking.test`
- **Owner 2**: `owner2@sportbooking.test`
- **User 1**: `user1@sportbooking.test`
- **User 2**: `user2@sportbooking.test`

## Ringkasan Endpoint REST API (`/api`)
- `POST /login`, `POST /register`, `POST /logout`
- `GET /venues`, `GET /sport-categories`, `GET /fields/{field}/schedule?date=YYYY-MM-DD`
- `GET /bookings`, `POST /bookings`, `PUT /bookings/{booking}/reschedule`, `POST /bookings/{booking}/cancel`
- `POST /bookings/{booking}/payments`, `PATCH /payments/{payment}/verify`
- `POST /bookings/{booking}/check-in`, `POST /bookings/{booking}/complete`
- `GET/POST/PUT/DELETE /owner/venues`, `/owner/fields`, `/owner/equipments`S