<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private BookingTransactionService $service) {}

    public function store(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Hanya penyewa terkait yang bisa mengunggah bukti bayar.');
        }

        $data = $request->validate([
            'payment_type' => 'required|in:dp,settlement,penalty',
            'amount'       => 'required|numeric|min:1',
            'proof_image'  => 'nullable|image|max:2048',
        ]);

        $proofPath = $request->hasFile('proof_image')
            ? $request->file('proof_image')->store('payment_proofs', 'public')
            : null;

        $payment = $this->service->submitPayment($booking, $data, $proofPath);

        return response()->json(['message' => 'Pembayaran dikirim, menunggu verifikasi.', 'data' => $payment], 201);
    }

    public function verify(Request $request, Payment $payment): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:verified,rejected',
        ]);

        $updated = $this->service->verifyPayment($payment, $request->user(), $data['status']);

        return response()->json(['message' => "Pembayaran {$data['status']}.", 'data' => $updated]);
    }
}