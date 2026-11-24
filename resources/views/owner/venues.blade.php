@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-6">Kelola Venue, Lapangan & Perlengkapan</h1>

<form method="POST" action="{{ route('owner.venues.store') }}" class="bg-white p-5 rounded-xl border shadow-sm mb-6 grid md:grid-cols-6 gap-3 items-end">
    @csrf
    <div class="md:col-span-2">
        <label class="text-xs font-semibold">Nama Venue</label>
        <input type="text" name="name" required class="w-full border rounded px-3 py-1.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold">Kota</label>
        <input type="text" name="city" required class="w-full border rounded px-3 py-1.5 text-sm">
    </div>
    <div class="md:col-span-2">
        <label class="text-xs font-semibold">Alamat</label>
        <input type="text" name="address" required class="w-full border rounded px-3 py-1.5 text-sm">
    </div>
    <div class="flex gap-1">
        <input type="time" name="open_time" value="07:00" required class="w-1/2 border rounded px-1 py-1.5 text-xs">
        <input type="time" name="close_time" value="23:00" required class="w-1/2 border rounded px-1 py-1.5 text-xs">
    </div>
    <button class="md:col-span-6 bg-slate-900 text-white py-2 rounded text-sm font-semibold">+ Tambah Venue Baru</button>
</form>

<div class="space-y-6">
    @foreach($venues as $venue)
        <div class="bg-white p-5 rounded-xl border shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">
                    {{ $venue->name }} <span class="text-sm font-normal text-slate-500">({{ $venue->city }})</span>
                </h2>
                <form method="POST" action="{{ route('owner.venues.destroy', $venue) }}">
                    @csrf
                    @method('DELETE')
                    <button onclick="return confirm('Hapus venue ini beserta seluruh lapangan dan alat di dalamnya?')"
                        class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1 rounded text-xs font-semibold">
                        Hapus Venue
                    </button>
                </form>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                {{-- Daftar & Tambah Lapangan --}}
                <div class="space-y-3">
                    <h3 class="font-semibold text-sm">Lapangan</h3>
                    @foreach($venue->fields as $f)
                        <div class="text-sm bg-slate-50 p-2.5 rounded border flex items-center justify-between gap-2">
                            <div>
                                <div><b>{{ $f->name }}</b> ({{ $f->sportCategory->name }})</div>
                                <div class="text-xs text-slate-500">
                                    Reg: {{ number_format($f->regular_rate,0,',','.') }} | Prime: {{ number_format($f->prime_rate,0,',','.') }}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('owner.fields.destroy', $f) }}">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('Hapus lapangan {{ $f->name }}?')"
                                    class="bg-rose-600 hover:bg-rose-700 text-white px-2.5 py-1 rounded text-xs font-semibold">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    @endforeach
                    <form method="POST" action="{{ route('owner.fields.store', $venue) }}" class="grid grid-cols-2 gap-2 pt-2 border-t">
                        @csrf
                        <input type="text" name="name" placeholder="Nama Lapangan" required class="border rounded px-2 py-1 text-xs">
                        <select name="sport_category_id" class="border rounded px-2 py-1 text-xs">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="regular_rate" placeholder="Tarif Reguler" required class="border rounded px-2 py-1 text-xs">
                        <input type="number" name="prime_rate" placeholder="Tarif Prime" required class="border rounded px-2 py-1 text-xs">
                        <button class="col-span-2 bg-indigo-600 text-white py-1.5 rounded text-xs font-semibold">+ Tambah Lapangan</button>
                    </form>
                </div>

                {{-- Daftar & Tambah Alat --}}
                <div class="space-y-3">
                    <h3 class="font-semibold text-sm">Perlengkapan Sewa</h3>
                    @foreach($venue->equipments as $eq)
                        <div class="text-sm bg-slate-50 p-2.5 rounded border flex justify-between">
                            <span><b>{{ $eq->name }}</b> — Rp {{ number_format($eq->rental_price,0,',','.') }}</span>
                            <span class="text-xs">Stok: <b>{{ $eq->available_stock }}</b>/{{ $eq->total_stock }}</span>
                        </div>
                    @endforeach
                    <form method="POST" action="{{ route('owner.equipments.store', $venue) }}" class="grid grid-cols-3 gap-2 pt-2 border-t">
                        @csrf
                        <input type="text" name="name" placeholder="Nama Alat" required class="border rounded px-2 py-1 text-xs">
                        <input type="number" name="total_stock" placeholder="Stok" required class="border rounded px-2 py-1 text-xs">
                        <input type="number" name="rental_price" placeholder="Harga Sewa" required class="border rounded px-2 py-1 text-xs">
                        <button class="col-span-3 bg-emerald-600 text-white py-1.5 rounded text-xs font-semibold">+ Tambah Perlengkapan</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
