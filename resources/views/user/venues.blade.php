@extends('layouts.app')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <h1 class="text-2xl font-bold">Cari & Booking Lapangan</h1>
    <form method="GET" action="{{ route('user.venues') }}" class="flex gap-2">
        <input type="text" name="city" value="{{ request('city') }}" placeholder="Kota (mis. Surabaya)" class="border rounded px-3 py-1.5 text-sm bg-white">
        <select name="sport_category_id" class="border rounded px-3 py-1.5 text-sm bg-white">
            <option value="">Semua Olahraga</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('sport_category_id') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button class="bg-slate-900 text-white px-4 py-1.5 rounded text-sm">Filter</button>
    </form>
</div>

<div class="space-y-6">
    @foreach($venues as $venue)
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-xl font-bold">{{ $venue->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $venue->address }}, {{ $venue->city }} &bull; Jam Buka: {{ substr($venue->open_time,0,5) }} - {{ substr($venue->close_time,0,5) }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('user.bookings.store') }}" class="grid md:grid-cols-2 gap-6 border-t pt-4">
                @csrf
                <div class="space-y-3">
                    <h3 class="font-semibold text-sm text-slate-700">1. Pilih Lapangan & Jadwal</h3>
                    <select name="field_id" required class="w-full border rounded px-3 py-2 text-sm">
                        @foreach($venue->fields as $field)
                            <option value="{{ $field->id }}">
                                {{ $field->name }} ({{ $field->sportCategory->name }}) — Reg: Rp {{ number_format($field->regular_rate,0,',','.') }}/jam | Prime >=18:00: Rp {{ number_format($field->prime_rate,0,',','.') }}/jam
                            </option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-xs text-slate-500">Tanggal</label>
                            <input type="date" name="booking_date" value="{{ now()->addDay()->format('Y-m-d') }}" required class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Mulai</label>
                            <input type="time" name="start_time" value="17:00" required class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Selesai</label>
                            <input type="time" name="end_time" value="19:00" required class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="font-semibold text-sm text-slate-700">2. Sewa Alat Tambahan (Opsional)</h3>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        @forelse($venue->equipments as $eq)
                            <div class="flex items-center justify-between text-sm bg-slate-50 px-3 py-2 rounded border">
                                <div>
                                    <div class="font-medium">{{ $eq->name }}</div>
                                    <div class="text-xs text-slate-500">Rp {{ number_format($eq->rental_price,0,',','.') }} &bull; Tersedia: <b>{{ $eq->available_stock }}</b>/{{ $eq->total_stock }}</div>
                                </div>
                                <input type="number" name="equipments[{{ $eq->id }}]" value="0" min="0" max="{{ $eq->available_stock }}" class="w-20 border rounded px-2 py-1 text-center">
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Tidak ada perlengkapan tambahan.</p>
                        @endforelse
                    </div>

                    @auth
                        <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded font-semibold text-sm">
                            Booking Sekarang
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="block text-center w-full bg-slate-800 text-white py-2 rounded font-semibold text-sm">
                            Login untuk Booking
                        </a>
                    @endauth
                </div>
            </form>
        </div>
    @endforeach
</div>
@endsection