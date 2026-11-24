<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SportCategory;
use App\Models\Venue;
use App\Services\BookingTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserBookingController extends Controller
{
    public function __construct(private BookingTransactionService $service) {}

    public function venues(Request $request): View
    {
        $categories = SportCategory::all();$venues = Venue::with([
                'fields' => fn ($q) =>$q->where('is_active', true)->with('sportCategory'),
                'equipments',
            ])
            ->when($request->city, fn ($q,$city) => $q->where('city', 'like', "\%{$city}%"))
            ->when($request->sport_category_id, fn ($q,$id) => $q->whereHas('fields', fn ($f) => $f->where('sport_category_id',$id)))
            ->get();

        return view('user.venues', compact('venues', 'categories'));
    }

    public function myBookings(Request $request): View
    {
        $bookings = Booking::with(['field.venue', 'bookingEquipments.equipment', 'payments'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('user.bookings', compact('bookings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data =$request->validate([
            'field_id'     => 'required|exists:fields,id',
            'booking_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'equipments'   => 'nullable|array',
            'equipments.*' => 'nullable|integer|min:0',
        ]);

        $selectedEquipments = [];
        foreach ($data['equipments'] ?? [] as $equipmentId =>$qty) {
            if ((int) $qty > 0) {$selectedEquipments[] = [
                    'equipment_id' => (int) $equipmentId,
                    'qty'          => (int) $qty,
                ];
            }
        }

        $booking = $this->service->createBooking($request->user(), [
            'field_id'     => $data['field_id'],
            'booking_date' => $data['booking_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'equipments'   => $selectedEquipments,
        ]);

        return redirect()->route('user.bookings')
            ->with('success', "Booking {$booking->booking_code} dibuat. Total: Rp " . number_format($booking->grand_total, 0, ',', '.'));
    }

    public function pay(Request $request, Booking$booking): RedirectResponse
    {
        if ($booking->user_id !==$request->user()->id) {
            abort(403);
        }

        $data =$request->validate([
            'payment_type' => 'required|in:dp,settlement,penalty',
            'amount'       => 'required|numeric|min:1',
            'proof_image'  => 'nullable|image|max:2048',
        ]);

        $proofPath =$request->hasFile('proof_image')
            ? $request->file('proof_image')->store('payment_proofs', 'public')
            : null;

        $this->service->submitPayment($booking, $data,$proofPath);

        return back()->with('success', 'Bukti pembayaran terkirim, menunggu verifikasi owner.');
    }

    public function reschedule(Request $request, Booking$booking): RedirectResponse
    {
        if ($booking->user_id !==$request->user()->id) {
            abort(403);
        }

        $data =$request->validate([
            'booking_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
        ]);

        $this->service->rescheduleBooking($booking,$data);

        return back()->with('success', 'Jadwal booking berhasil di-reschedule.');
    }

    public function cancel(Request $request, Booking$booking): RedirectResponse
    {
        $this->service->cancelBooking($booking,$request->user());

        return back()->with('success', 'Booking dibatalkan & stok alat dikembalikan.');
    }
}