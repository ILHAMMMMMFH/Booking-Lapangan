@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-6">Riwayat Booking Saya</h1>

<div class="space-y-4">
    @forelse($bookings as $booking)
        <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">
            <div class="flex flex-wrap justify-between items-start gap-2">
                <div>
                    <span class="font-mono font-bold text-emerald-700">{{ $booking->booking_code }}</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded font-semibold bg-slate-200 uppercase">{{ $booking->status }}</span>
                    <h2 class="font-bold text-lg mt-1">{{ $booking->field->venue->name }} — {{ $booking->field->name }}</h2>
                    <p class="text-sm text-slate-600">
                        {{ $booking->booking_date->format('d M Y') }} | {{ substr($booking->start_time,0,5) }} - {{ substr($booking->end_time,0,5) }}
                    </p>
                </div>
                <div class="text-right text-sm">
                    <div>Lapangan: Rp {{ number_format($booking->field_subtotal,0,',','.') }}</div>
                    <div>Alat: Rp {{ number_format($booking->equipment_subtotal,0,',','.') }}</div>
                    @if($booking->penalty_fee > 0)
                        <div class="text-rose-600 font-semibold">Denda: Rp {{ number_format($booking->penalty_fee,0,',','.') }}</div>
                    @endif
                    <div class="text-base font-bold mt-1">Total: Rp {{ number_format($booking->grand_total,0,',','.') }}</div>
                </div>
            </div>

            @if($booking->bookingEquipments->isNotEmpty())
                <div class="text-xs bg-slate-50 p-3 rounded border">
                    <b>Alat Disewa:</b>
                    @foreach($booking->bookingEquipments as $be)
                        {{ $be->equipment->name }} ({{ $be->qty }}x, kembali: {{ $be->returned_qty }}){{ !$loop->last ? ',' : '' }}
                    @endforeach
                </div>
            @endif

            @if($booking->payments->isNotEmpty())
                <div class="text-xs space-y-1">
                    <b>Riwayat Pembayaran:</b>
                    @foreach($booking->payments as $pay)
                        <div class="flex justify-between bg-slate-50 px-3 py-1.5 rounded border">
                            <span>{{ strtoupper($pay->payment_type) }} — Rp {{ number_format($pay->amount,0,',','.') }}</span>
                            <span class="font-semibold uppercase">{{ $pay->status }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!in_array($booking->status, ['cancelled']))
                <div class="grid md:grid-cols-2 gap-4 pt-3 border-t">
                    <form method="POST" action="{{ route('user.bookings.pay', $booking) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2 items-center">
                        @csrf
                        <select name="payment_type" class="border rounded px-2 py-1.5 text-xs">
                            <option value="dp">DP</option>
                            <option value="settlement">Pelunasan</option>
                            <option value="penalty">Bayar Denda</option>
                        </select>
                        <input type="number" name="amount" placeholder="Nominal" required class="w-28 border rounded px-2 py-1.5 text-xs">
                        <input type="file" name="proof_image" accept="image/*" class="text-xs w-44">
                        <button class="bg-emerald-600 text-white px-3 py-1.5 rounded text-xs font-semibold">Kirim Bayar</button>
                    </form>

                    @if(in_array($booking->status, ['waiting_payment', 'confirmed']))
                        <div class="flex flex-wrap gap-2 justify-end items-center">
                            <form method="POST" action="{{ route('user.bookings.reschedule', $booking) }}" class="flex gap-1 items-center">
                                @csrf @method('PUT')
                                <input type="date" name="booking_date" value="{{ $booking->booking_date->format('Y-m-d') }}" class="border rounded px-1.5 py-1 text-xs">
                                <input type="time" name="start_time" value="{{ substr($booking->start_time,0,5) }}" class="border rounded px-1.5 py-1 text-xs">
                                <input type="time" name="end_time" value="{{ substr($booking->end_time,0,5) }}" class="border rounded px-1.5 py-1 text-xs">
                                <button class="bg-amber-500 text-white px-2.5 py-1.5 rounded text-xs">Reschedule</button>
                            </form>
                            <form method="POST" action="{{ route('user.bookings.cancel', $booking) }}">
                                @csrf
                                <button onclick="return confirm('Batalkan booking ini?')" class="bg-rose-600 text-white px-2.5 py-1.5 rounded text-xs">Batal</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <p class="text-slate-500">Belum ada transaksi booking.</p>
    @endforelse
</div>
@endsection