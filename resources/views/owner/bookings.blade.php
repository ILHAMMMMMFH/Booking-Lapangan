@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-6">Dashboard Transaksi Owner</h1>

<div class="space-y-5">
    @forelse($bookings as $booking)
        <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">
            <div class="flex justify-between items-start">
                <div>
                    <span class="font-mono font-bold text-indigo-700">{{ $booking->booking_code }}</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded font-semibold bg-slate-200 uppercase">{{ $booking->status }}</span>
                    <h2 class="font-bold mt-1">{{ $booking->field->venue->name }} — {{ $booking->field->name }}</h2>
                    <p class="text-sm text-slate-600">
                        Penyewa: <b>{{ $booking->user->name }}</b> ({{ $booking->user->phone }}) &bull;
                        {{ $booking->booking_date->format('d M Y') }} {{ substr($booking->start_time,0,5) }}-{{ substr($booking->end_time,0,5) }}
                    </p>
                </div>
                <div class="text-right text-sm font-bold">
                    Total: Rp {{ number_format($booking->grand_total,0,',','.') }}
                    @if($booking->penalty_fee > 0)
                        <div class="text-rose-600 text-xs">Termasuk Denda: Rp {{ number_format($booking->penalty_fee,0,',','.') }}</div>
                    @endif
                </div>
            </div>

            {{-- Verifikasi Pembayaran --}}
            <div class="space-y-2 border-t pt-3">
                <h3 class="text-xs font-bold uppercase text-slate-500">Pembayaran</h3>
                @forelse($booking->payments as $pay)
                    <div class="flex items-center justify-between bg-slate-50 px-3 py-2 rounded border text-sm">
                        <div>
                            <span class="font-semibold uppercase">{{ $pay->payment_type }}</span> —
                            Rp {{ number_format($pay->amount,0,',','.') }}
                            <span class="ml-2 text-xs px-2 py-0.5 rounded bg-slate-200 uppercase">{{ $pay->status }}</span>
                            @if($pay->proof_image)
                                <a href="{{ asset('storage/' . $pay->proof_image) }}" target="_blank" class="ml-2 text-xs text-blue-600 underline">Lihat Bukti</a>
                            @endif
                        </div>
                        @if($pay->status === 'pending' && $pay->payment_type !== 'refund')
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('owner.payments.verify', $pay) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="verified">
                                    <button class="bg-emerald-600 text-white px-3 py-1 rounded text-xs">Verifikasi</button>
                                </form>
                                <form method="POST" action="{{ route('owner.payments.verify', $pay) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button class="bg-rose-600 text-white px-3 py-1 rounded text-xs">Tolak</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Belum ada pembayaran masuk.</p>
                @endforelse
            </div>

            {{-- Check-In & Complete + Pengembalian Alat --}}
            <div class="border-t pt-3">
                @if($booking->status === 'confirmed')
                    <form method="POST" action="{{ route('owner.bookings.checkin', $booking) }}">
                        @csrf
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded text-sm font-semibold">
                            Proses Check-In (Mulai Sesi)
                        </button>
                    </form>
                @elseif($booking->status === 'ongoing')
                    <form method="POST" action="{{ route('owner.bookings.complete', $booking) }}" class="space-y-3 bg-amber-50 p-3 rounded border border-amber-200">
                        @csrf
                        <h4 class="text-xs font-bold uppercase text-amber-800">Konfirmasi Pengembalian Alat & Selesaikan Sesi</h4>
                        @foreach($booking->bookingEquipments as $be)
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <span class="w-48 font-medium">{{ $be->equipment->name }} (Sewa: {{ $be->qty }})</span>
                                <label class="text-xs">Kembali Utuh:</label>
                                <input type="number" name="returns[{{ $be->equipment_id }}][returned_qty]" value="{{ $be->qty }}" min="0" max="{{ $be->qty }}" class="w-20 border rounded px-2 py-1 bg-white">
                                <input type="text" name="returns[{{ $be->equipment_id }}][notes]" placeholder="Catatan (mis. robek/hilang)" class="flex-1 border rounded px-2 py-1 bg-white text-xs">
                            </div>
                        @endforeach
                        <button class="bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-1.5 rounded text-sm font-semibold">
                            Selesaikan Sesi & Hitung Denda
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-slate-500">Belum ada transaksi booking masuk.</p>
    @endforelse
</div>
@endsection