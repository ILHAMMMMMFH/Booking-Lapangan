@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow border">
    <h1 class="text-xl font-bold mb-4">Registrasi Akun</h1>
    <form method="POST" action="{{ route('register') }}" class="space-y-3">
        @csrf
        <input type="text" name="name" placeholder="Nama Lengkap" value="{{ old('name') }}" required class="w-full border rounded px-3 py-2">
        <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required class="w-full border rounded px-3 py-2">
        <input type="text" name="phone" placeholder="No. HP (08xxx)" value="{{ old('phone') }}" required class="w-full border rounded px-3 py-2">
        <select name="role" class="w-full border rounded px-3 py-2">
            <option value="user">Penyewa (User)</option>
            <option value="owner">Pemilik Venue (Owner)</option>
        </select>
        <input type="password" name="password" placeholder="Password (min 6 karakter)" required class="w-full border rounded px-3 py-2">
        <input type="password" name="password_confirmation" placeholder="Ulangi Password" required class="w-full border rounded px-3 py-2">
        <button class="w-full bg-emerald-600 text-white py-2 rounded font-semibold hover:bg-emerald-700">Daftar</button>
    </form>
</div>
@endsection