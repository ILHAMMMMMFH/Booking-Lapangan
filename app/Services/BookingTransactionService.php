<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingEquipment;
use App\Models\Equipment;
use App\Models\Field;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingTransactionService
{
    private const PRIME_TIME_START = '18:00:00';
    private const PENALTY_MULTIPLIER = 2; // Denda = 2x rental_price per unit hilang/rusak

    /**
     * 1. Buat Booking Baru + Stock Hold Alat + Kalkulasi Tarif Dinamis
     */
    public function createBooking(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock row Field dulu agar request konkuren di lapangan sama antre (anti race condition)
            $field = Field::with('venue')
                ->where('id', $data['field_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $field->is_active) {
                throw ValidationException::withMessages(['field_id' => 'Lapangan sedang tidak aktif.']);
            }

            $bookingDate = $data['booking_date'];
            $startTime   = Carbon::parse($data['start_time'])->format('H:i:s');
            $endTime     = Carbon::parse($data['end_time'])->format('H:i:s');

            $this->validateScheduleAndHours($field, $bookingDate, $startTime, $endTime);

            // Kalkulasi tarif reguler vs prime time
            $fieldSubtotal = $this->calculateFieldSubtotal($field, $bookingDate, $startTime, $endTime);

            // Validasi & Stock Hold perlengkapan (sort by ID untuk mencegah deadlock InnoDB)
            $equipmentSubtotal = 0;
            $preparedEquipments = [];

            if (! empty($data['equipments'])) {
                $requested = collect($data['equipments'])->sortBy('equipment_id');

                foreach ($requested as $item) {
                    $equipment = Equipment::where('id', $item['equipment_id'])
                        ->where('venue_id', $field->venue_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $equipment) {
                        throw ValidationException::withMessages([
                            'equipments' => "Alat ID {$item['equipment_id']} tidak tersedia di venue ini.",
                        ]);
                    }

                    if ($equipment->available_stock < $item['qty']) {
                        throw ValidationException::withMessages([
                            'equipments' => "Stok {$equipment->name} tidak cukup (tersisa {$equipment->available_stock}).",
                        ]);
                    }

                    // Kurangi available_stock langsung (stock hold)
                    $equipment->decrement('available_stock', $item['qty']);

                    $lineTotal = (float) $equipment->rental_price * $item['qty'];
                    $equipmentSubtotal += $lineTotal;

                    $preparedEquipments[] = [
                        'equipment_id' => $equipment->id,
                        'qty'          => $item['qty'],
                        'unit_price'   => $equipment->rental_price,
                        'returned_qty' => 0,
                    ];
                }
            }

            $booking = Booking::create([
                'booking_code'       => 'BKG-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'user_id'            => $user->id,
                'field_id'           => $field->id,
                'booking_date'       => $bookingDate,
                'start_time'         => $startTime,
                'end_time'           => $endTime,
                'field_subtotal'     => $fieldSubtotal,
                'equipment_subtotal' => $equipmentSubtotal,
                'penalty_fee'        => 0,
                'grand_total'        => $fieldSubtotal + $equipmentSubtotal,
                'status'             => 'waiting_payment',
            ]);

            foreach ($preparedEquipments as $eq) {
                $booking->bookingEquipments()->create($eq);
            }

            return $booking->load(['field.venue', 'bookingEquipments.equipment', 'payments']);
        });
    }

    /**
     * 2. Reschedule Jadwal Booking + Re-kalkulasi Tarif
     */
    public function rescheduleBooking(Booking $booking, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $data) {
            $booking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            if (! in_array($booking->status, ['waiting_payment', 'confirmed'])) {
                throw ValidationException::withMessages(['status' => 'Booking tidak bisa di-reschedule pada status ini.']);
            }

            $field = Field::with('venue')
                ->where('id', $booking->field_id)
                ->lockForUpdate()
                ->firstOrFail();

            $newDate  = $data['booking_date'];
            $newStart = Carbon::parse($data['start_time'])->format('H:i:s');
            $newEnd   = Carbon::parse($data['end_time'])->format('H:i:s');

            $this->validateScheduleAndHours($field, $newDate, $newStart, $newEnd, $booking->id);

            $newFieldSubtotal = $this->calculateFieldSubtotal($field, $newDate, $newStart, $newEnd);

            $booking->update([
                'booking_date'   => $newDate,
                'start_time'     => $newStart,
                'end_time'       => $newEnd,
                'field_subtotal' => $newFieldSubtotal,
                'grand_total'    => $newFieldSubtotal + (float) $booking->equipment_subtotal + (float) $booking->penalty_fee,
            ]);

            return $booking->fresh(['field.venue', 'bookingEquipments.equipment', 'payments']);
        });
    }

    /**
     * 3. User Mengajukan Pembayaran (DP / Settlement / Penalty)
     */
    public function submitPayment(Booking $booking, array $data, ?string $proofPath = null): Payment
    {
        return DB::transaction(function () use ($booking, $data, $proofPath) {
            $booking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            if ($booking->status === 'cancelled') {
                throw ValidationException::withMessages(['booking' => 'Booking sudah dibatalkan.']);
            }

            return Payment::create([
                'booking_id'   => $booking->id,
                'payment_type' => $data['payment_type'],
                'amount'       => $data['amount'],
                'proof_image'  => $proofPath,
                'status'       => 'pending',
            ]);
        });
    }

    /**
     * 4. Owner Memverifikasi / Menolak Pembayaran
     */
    public function verifyPayment(Payment $payment, User $owner, string $decision): Payment
    {
        return DB::transaction(function () use ($payment, $owner, $decision) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
            $booking = Booking::with('field.venue')
                ->where('id', $payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->field->venue->owner_id !== $owner->id) {
                abort(403, 'Anda bukan pemilik venue untuk transaksi ini.');
            }

            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Pembayaran ini sudah diproses sebelumnya.']);
            }

            $payment->update([
                'status'      => $decision, // 'verified' atau 'rejected'
                'verified_by' => $owner->id,
            ]);

            // Kunci jadwal menjadi confirmed jika DP / settlement diverifikasi
            if (
                $decision === 'verified'
                && in_array($payment->payment_type, ['dp', 'settlement'])
                && $booking->status === 'waiting_payment'
            ) {
                $booking->update(['status' => 'confirmed']);
            }

            return $payment->fresh(['booking', 'verifier']);
        });
    }

    /**
     * 5. Owner Check-In (confirmed -> ongoing)
     */
    public function checkIn(Booking $booking, User $owner): Booking
    {
        return DB::transaction(function () use ($booking, $owner) {
            $booking = Booking::with('field.venue')
                ->where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->field->venue->owner_id !== $owner->id) {
                abort(403, 'Anda bukan pemilik venue ini.');
            }

            if ($booking->status !== 'confirmed') {
                throw ValidationException::withMessages(['status' => 'Check-in hanya untuk booking berstatus confirmed.']);
            }

            $booking->update(['status' => 'ongoing']);

            return $booking->fresh(['field.venue', 'bookingEquipments.equipment', 'payments']);
        });
    }

    /**
     * 6. Selesaikan Sesi + Pengembalian Alat + Hitung Denda Otomatis
     */
    public function completeBooking(Booking $booking, User $owner, array $returns = []): Booking
    {
        return DB::transaction(function () use ($booking, $owner, $returns) {
            $booking = Booking::with(['field.venue', 'bookingEquipments'])
                ->where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->field->venue->owner_id !== $owner->id) {
                abort(403, 'Anda bukan pemilik venue ini.');
            }

            if ($booking->status !== 'ongoing') {
                throw ValidationException::withMessages(['status' => 'Hanya booking ongoing yang dapat diselesaikan.']);
            }

            $returnMap = collect($returns)->keyBy('equipment_id');
            $totalPenalty = 0;

            foreach ($booking->bookingEquipments->sortBy('equipment_id') as $item) {
                $equipment = Equipment::where('id', $item->equipment_id)->lockForUpdate()->firstOrFail();

                $input       = $returnMap->get($item->equipment_id);
                $returnedQty = isset($input['returned_qty']) ? (int) $input['returned_qty'] : $item->qty;
                $notes       = $input['notes'] ?? null;

                if ($returnedQty < 0 || $returnedQty > $item->qty) {
                    throw ValidationException::withMessages([
                        'returns' => "Jumlah kembali {$equipment->name} harus antara 0 sampai {$item->qty}.",
                    ]);
                }

                // Kembalikan stok utuh ke available_stock
                if ($returnedQty > 0) {
                    $equipment->increment('available_stock', $returnedQty);
                }

                // Hitung denda jika ada alat hilang/rusak
                $missingQty = $item->qty - $returnedQty;
                if ($missingQty > 0) {
                    $totalPenalty += $missingQty * (float) $item->unit_price * self::PENALTY_MULTIPLIER;
                }

                $item->update([
                    'returned_qty' => $returnedQty,
                    'notes'        => $notes,
                ]);
            }

            $booking->update([
                'penalty_fee' => $totalPenalty,
                'grand_total' => (float) $booking->field_subtotal + (float) $booking->equipment_subtotal + $totalPenalty,
                'status'      => 'completed',
            ]);

            // Buat tagihan denda otomatis di tabel payments jika ada selisih alat
            if ($totalPenalty > 0) {
                Payment::create([
                    'booking_id'   => $booking->id,
                    'payment_type' => 'penalty',
                    'amount'       => $totalPenalty,
                    'status'       => 'pending',
                ]);
            }

            return $booking->fresh(['field.venue', 'bookingEquipments.equipment', 'payments']);
        });
    }

    /**
     * 7. Pembatalan Booking + Release Stok Alat + Catat Refund
     */
    public function cancelBooking(Booking $booking, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $actor) {
            $booking = Booking::with(['field.venue', 'bookingEquipments', 'payments'])
                ->where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $isOwner  = $booking->field->venue->owner_id === $actor->id;
            $isRenter = $booking->user_id === $actor->id;

            if (! $isOwner && ! $isRenter) {
                abort(403, 'Tidak memiliki akses untuk membatalkan booking ini.');
            }

            if (! in_array($booking->status, ['waiting_payment', 'confirmed'])) {
                throw ValidationException::withMessages(['status' => 'Booking tidak dapat dibatalkan pada status ini.']);
            }

            // Kembalikan seluruh stok alat yang sempat di-hold
            foreach ($booking->bookingEquipments->sortBy('equipment_id') as $item) {
                Equipment::where('id', $item->equipment_id)
                    ->lockForUpdate()
                    ->increment('available_stock', $item->qty);
            }

            // Hitung total uang yang sudah diverifikasi untuk dicatat sebagai refund
            $verifiedPaid = (float) $booking->payments
                ->where('status', 'verified')
                ->whereIn('payment_type', ['dp', 'settlement'])
                ->sum('amount');

            if ($verifiedPaid > 0) {
                Payment::create([
                    'booking_id'   => $booking->id,
                    'payment_type' => 'refund',
                    'amount'       => $verifiedPaid,
                    'status'       => 'pending',
                ]);
            }

            $booking->update(['status' => 'cancelled']);

            return $booking->fresh(['field.venue', 'bookingEquipments.equipment', 'payments']);
        });
    }

    /**
     * Validasi Jam Operasional Venue & Anti-Bentrok Jadwal
     */
    private function validateScheduleAndHours(
        Field $field,
        string $bookingDate,
        string $startTime,
        string $endTime,
        ?int $ignoreBookingId = null
    ): void {
        if ($startTime >= $endTime) {
            throw ValidationException::withMessages(['end_time' => 'Jam selesai harus lebih besar dari jam mulai.']);
        }

        $openTime  = Carbon::parse($field->venue->open_time)->format('H:i:s');
        $closeTime = Carbon::parse($field->venue->close_time)->format('H:i:s');

        if ($startTime < $openTime || $endTime > $closeTime) {
            throw ValidationException::withMessages([
                'start_time' => "Jadwal di luar jam operasional venue ({$openTime} - {$closeTime}).",
            ]);
        }

        $conflictQuery = Booking::where('field_id', $field->id)
            ->where('booking_date', $bookingDate)
            ->whereIn('status', ['waiting_payment', 'confirmed', 'ongoing'])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($ignoreBookingId) {
            $conflictQuery->where('id', '!=', $ignoreBookingId);
        }

        if ($conflictQuery->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                'start_time' => 'Jadwal bentrok dengan booking lain yang masih aktif.',
            ]);
        }
    }

    /**
     * Hitung Subtotal Lapangan (Reguler < 18:00 vs Prime Time >= 18:00)
     */
    private function calculateFieldSubtotal(Field $field, string $date, string $startTime, string $endTime): float
    {
        $startTs = Carbon::parse("{$date} {$startTime}")->timestamp;
        $endTs   = Carbon::parse("{$date} {$endTime}")->timestamp;
        $primeTs = Carbon::parse("{$date} " . self::PRIME_TIME_START)->timestamp;

        $regularSeconds = max(0, min($endTs, $primeTs) - $startTs);
        $primeSeconds   = max(0, $endTs - max($startTs, $primeTs));

        $regularTotal = ($regularSeconds / 3600) * (float) $field->regular_rate;
        $primeTotal   = ($primeSeconds / 3600) * (float) $field->prime_rate;

        return round($regularTotal + $primeTotal, 2);
    }
}