<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Field;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerManagementController extends Controller
{
    // --- VENUES ---
    public function venues(Request $request): JsonResponse
    {
        $venues = Venue::with(['fields.sportCategory', 'equipments'])
            ->where('owner_id', $request->user()->id)
            ->get();

        return response()->json($venues);
    }

    public function storeVenue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'city'       => 'required|string|max:100',
            'address'    => 'required|string',
            'open_time'  => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
        ]);

        $venue = $request->user()->venues()->create($data);

        return response()->json(['message' => 'Venue ditambahkan.', 'data' => $venue], 201);
    }

    public function updateVenue(Request $request, Venue $venue): JsonResponse
    {
        $this->authorizeOwner($venue, $request->user()->id);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:150',
            'city'       => 'sometimes|string|max:100',
            'address'    => 'sometimes|string',
            'open_time'  => 'sometimes|date_format:H:i',
            'close_time' => 'sometimes|date_format:H:i|after:open_time',
        ]);

        $venue->update($data);

        return response()->json(['message' => 'Venue diperbarui.', 'data' => $venue]);
    }

    // --- FIELDS ---
    public function storeField(Request $request, Venue $venue): JsonResponse
    {
        $this->authorizeOwner($venue, $request->user()->id);

        $data = $request->validate([
            'sport_category_id' => 'required|exists:sport_categories,id',
            'name'              => 'required|string|max:100',
            'regular_rate'      => 'required|numeric|min:0',
            'prime_rate'        => 'required|numeric|min:0',
            'is_active'         => 'sometimes|boolean',
        ]);

        $field = $venue->fields()->create($data);

        return response()->json(['message' => 'Lapangan ditambahkan.', 'data' => $field->load('sportCategory')], 201);
    }

    public function updateField(Request $request, Field $field): JsonResponse
    {
        $this->authorizeOwner($field->venue, $request->user()->id);

        $data = $request->validate([
            'sport_category_id' => 'sometimes|exists:sport_categories,id',
            'name'              => 'sometimes|string|max:100',
            'regular_rate'      => 'sometimes|numeric|min:0',
            'prime_rate'        => 'sometimes|numeric|min:0',
            'is_active'         => 'sometimes|boolean',
        ]);

        $field->update($data);

        return response()->json(['message' => 'Lapangan diperbarui.', 'data' => $field->load('sportCategory')]);
    }

    // --- EQUIPMENTS ---
    public function storeEquipment(Request $request, Venue $venue): JsonResponse
    {
        $this->authorizeOwner($venue, $request->user()->id);

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'total_stock'  => 'required|integer|min:1',
            'rental_price' => 'required|numeric|min:0',
        ]);

        $equipment = $venue->equipments()->create([
            ...$data,
            'available_stock' => $data['total_stock'],
        ]);

        return response()->json(['message' => 'Perlengkapan ditambahkan.', 'data' => $equipment], 201);
    }

    public function updateEquipment(Request $request, Equipment $equipment): JsonResponse
    {
        $this->authorizeOwner($equipment->venue, $request->user()->id);

        $data = $request->validate([
            'name'            => 'sometimes|string|max:100',
            'total_stock'     => 'sometimes|integer|min:0',
            'available_stock' => 'sometimes|integer|min:0|lte:total_stock',
            'rental_price'    => 'sometimes|numeric|min:0',
        ]);

        $equipment->update($data);

        return response()->json(['message' => 'Perlengkapan diperbarui.', 'data' => $equipment]);
    }

    private function authorizeOwner(Venue $venue, int $userId): void
    {
        if ($venue->owner_id !== $userId) {
            abort(403, 'Anda bukan pemilik venue ini.');
        }
    }
}