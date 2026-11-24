@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow border">
    <h1 class="text-xl font-bold mb-4">Login</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required class="w-full border rounded px-3 py-2">
        </div>
        <button class="w-full bg-slate-900 text-white py-2 rounded font-semibold hover:bg-slate-800">Masuk</button>
    </form>
    <p class="text-xs text-slate-500 mt-4">
        Akun Seeder: <b>user1@sportbooking.test</b> / <b>owner1@sportbooking.test</b> (pw: <b>password123</b>)
    </p>
</div>
@endsection