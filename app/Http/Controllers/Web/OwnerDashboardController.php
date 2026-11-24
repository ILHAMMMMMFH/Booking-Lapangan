<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\SportCategory;
use App\Models\Venue;
use App\Services\BookingTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    public function __construct(private BookingTransactionService $service) {}

    public function bookings(Request $request): View
    {
        $bookings = Booking::with(['user', 'field.venue', 'bookingEquipments.equipment', 'payments'])
            ->whereHas('field.venue', fn ($q) => $q->where('owner_id', $request->user()->id))
            ->latest()
            ->get();

        return view('owner.bookings', compact('bookings'));
    }

    public function venues(Request $request): View
    {
        $categories = SportCategory::all();
        $venues = Venue::with(['fields.sportCategory', 'equipments'])
            ->where('owner_id', $request->user()->id)
            ->get();

        return view('owner.venues', compact('venues', 'categories'));
    }

    public function storeVenue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'city'       => 'required|string|max:100',
            'address'    => 'required|string',
            'open_time'  => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
        ]);

        $request->user()->venues()->create($data);

        return back()->with('success', 'Venue berhasil ditambahkan.');
    }

    public function storeField(Request $request, Venue $venue): RedirectResponse
    {
        abort_if($venue->owner_id !== $request->user()->id, 403);

        $data = $request->validate([
            'sport_category_id' => 'required|exists:sport_categories,id',
            'name'              => 'required|string|max:100',
            'regular_rate'      => 'required|numeric|min:0',
            'prime_rate'        => 'required|numeric|min:0',
        ]);

        $venue->fields()->create([...$data, 'is_active' => true]);

        return back()->with('success', 'Lapangan berhasil ditambahkan.');
    }

    public function storeEquipment(Request $request, Venue $venue): RedirectResponse
    {
        abort_if($venue->owner_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'total_stock'  => 'required|integer|min:1',
            'rental_price' => 'required|numeric|min:0',
        ]);

        $venue->equipments()->create([...$data, 'available_stock' => $data['total_stock']]);

        return back()->with('success', 'Perlengkapan berhasil ditambahkan.');
    }

    public function verifyPayment(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:verified,rejected']);
        $this->service->verifyPayment($payment, $request->user(), $data['status']);

        return back()->with('success', "Pembayaran di-{$data['status']}.");
    }

    public function checkIn(Request $request, Booking $booking): RedirectResponse
    {
        $this->service->checkIn($booking, $request->user());

        return back()->with('success', "Booking {$booking->booking_code} status menjadi ongoing.");
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        $returns = [];
        foreach ($request->input('returns', []) as $equipmentId => $row) {
            $returns[] = [
                'equipment_id' => (int) $equipmentId,
                'returned_qty' => (int) ($row['returned_qty'] ?? 0),
                'notes'        => $row['notes'] ?? null,
            ];
        }

        $done = $this->service->completeBooking($booking, $request->user(), $returns);

        $msg = "Booking {$done->booking_code} selesai.";
        if ((float) $done->penalty_fee > 0) {
            $msg .= ' Denda tercatat: Rp ' . number_format($done->penalty_fee, 0, ',', '.');
        }

        return back()->with('success', $msg);
    }
    public function destroyVenue(Request $request, Venue $venue): RedirectResponse
{
    abort_if($venue->owner_id !== $request->user()->id, 403);

    $hasBookings = $venue->fields()->whereHas('bookings')->exists()
        || $venue->equipments()->whereHas('bookingEquipments')->exists();

    if ($hasBookings) {
        return back()->withErrors(['venue' => 'Venue tidak bisa dihapus karena masih memiliki riwayat booking.']);
    }

    $venue->delete();

    return back()->with('success', 'Venue berhasil dihapus.');
    }

    public function destroyField(Request $request, Field $field): RedirectResponse
{
    abort_if($field->venue->owner_id !== $request->user()->id, 403);

    if ($field->bookings()->exists()) {
        return back()->withErrors(['field' => 'Lapangan tidak bisa dihapus karena masih memiliki riwayat booking.']);
    }

    $field->delete();

    return back()->with('success', 'Lapangan berhasil dihapus.');
}
}