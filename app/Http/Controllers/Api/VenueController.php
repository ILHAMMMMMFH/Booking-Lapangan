<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Field;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $venues = Venue::with(['fields.sportCategory', 'equipments'])
            ->when($request->city, fn ($q, $city) => $q->where('city', 'like', "%{$city}%"))
            ->when($request->sport_category_id, function ($q, $catId) {
                $q->whereHas('fields', fn ($f) => $f->where('sport_category_id', $catId));
            })
            ->get();

        return response()->json($venues);
    }

    public function fieldSchedule(Request $request, Field $field): JsonResponse
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);

        $bookedSlots = Booking::where('field_id', $field->id)
            ->where('booking_date', $request->date)
            ->whereIn('status', ['waiting_payment', 'confirmed', 'ongoing'])
            ->orderBy('start_time')
            ->get(['booking_code', 'start_time', 'end_time', 'status']);

        return response()->json([
            'field'        => $field->load('venue', 'sportCategory'),
            'date'         => $request->date,
            'booked_slots' => $bookedSlots,
        ]);
    }
}