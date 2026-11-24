<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingTransactionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Booking::with(['user:id,name,phone', 'field.venue', 'bookingEquipments.equipment', 'payments'])
            ->latest();

        if ($user->role === 'owner') {
            $query->whereHas('field.venue', fn ($q) => $q->where('owner_id', $user->id));
        } else {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->get());
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json(
            $booking->load(['user:id,name,phone', 'field.venue', 'bookingEquipments.equipment', 'payments'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'field_id'                  => 'required|exists:fields,id',
            'booking_date'              => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time'                => 'required|date_format:H:i',
            'end_time'                  => 'required|date_format:H:i|after:start_time',
            'equipments'                => 'nullable|array',
            'equipments.*.equipment_id' => 'required_with:equipments|exists:equipments,id',
            'equipments.*.qty'          => 'required_with:equipments|integer|min:1',
        ]);

        $booking = $this->service->createBooking($request->user(), $data);

        return response()->json(['message' => 'Booking berhasil dibuat.', 'data' => $booking], 201);
    }

    public function reschedule(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Hanya penyewa yang bisa reschedule.');
        }

        $data = $request->validate([
            'booking_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
        ]);

        $updated = $this->service->rescheduleBooking($booking, $data);

        return response()->json(['message' => 'Reschedule berhasil.', 'data' => $updated]);
    }

    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        $updated = $this->service->checkIn($booking, $request->user());

        return response()->json(['message' => 'Check-in berhasil, sesi dimulai.', 'data' => $updated]);
    }

    public function complete(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            'returns'                => 'nullable|array',
            'returns.*.equipment_id' => 'required_with:returns|exists:equipments,id',
            'returns.*.returned_qty' => 'required_with:returns|integer|min:0',
            'returns.*.notes'        => 'nullable|string',
        ]);

        $updated = $this->service->completeBooking($booking, $request->user(), $data['returns'] ?? []);

        return response()->json(['message' => 'Sesi selesai & pengembalian alat diproses.', 'data' => $updated]);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $updated = $this->service->cancelBooking($booking, $request->user());

        return response()->json(['message' => 'Booking dibatalkan.', 'data' => $updated]);
    }
}