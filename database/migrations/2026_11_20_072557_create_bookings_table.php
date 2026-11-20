<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('booking_code', 30)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('field_id')->constrained('fields')->restrictOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('field_subtotal', 12, 2)->default(0);
            $table->decimal('equipment_subtotal', 12, 2)->default(0);
            $table->decimal('penalty_fee', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->enum('status', [
                'waiting_payment',
                'confirmed',
                'ongoing',
                'completed',
                'cancelled'
            ])->default('waiting_payment');
            $table->timestamps();

            // Composite index untuk mempercepat validasi anti-bentrok jadwal & row locking
            $table->index(['field_id', 'booking_date', 'status'], 'idx_field_schedule_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};