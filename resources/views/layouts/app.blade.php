<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sport Venue Booking</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
    <nav class="bg-slate-900 text-white px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <a href="{{ route('user.venues') }}" class="font-bold text-lg text-emerald-400">SportBooking</a>
            <a href="{{ route('user.venues') }}" class="text-sm hover:text-emerald-300">Cari Lapangan</a>
            @auth
                @if(auth()->user()->role === 'user')
                    <a href="{{ route('user.bookings') }}" class="text-sm hover:text-emerald-300">Booking Saya</a>
                @else
                    <a href="{{ route('owner.bookings') }}" class="text-sm hover:text-emerald-300">Transaksi Masuk</a>
                    <a href="{{ route('owner.venues') }}" class="text-sm hover:text-emerald-300">Kelola Venue</a>
                @endif
            @endauth
        </div>
        <div class="flex items-center gap-4 text-sm">
            @auth
                <span class="text-slate-300">{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bg-rose-600 hover:bg-rose-700 px-3 py-1 rounded">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hover:text-emerald-300">Login</a>
                <a href="{{ route('register') }}" class="bg-emerald-600 hover:bg-emerald-700 px-3 py-1 rounded">Daftar</a>
            @endauth
        </div>
    </nav>

    <main class="max-w-6xl mx-auto p-6">
        @if(session('success'))
            <div class="mb-4 p-3 rounded bg-emerald-100 border border-emerald-300 text-emerald-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded bg-rose-100 border border-rose-300 text-rose-800 text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>