@php
    $isLogin = request()->routeIs('filament.kasir.auth.login');
@endphp

@if($isLogin)
    <div class="flex flex-col items-center justify-center gap-y-1 py-2">
        <img src="{{ asset('favicon.png') }}" alt="Logo" class="h-8 w-auto" />
        <span class="text-lg font-bold tracking-tight text-gray-950 dark:text-white text-center">
            Toko Jos Jaya Palembang
        </span>
    </div>
@else
    <div class="flex items-center gap-x-3">
        <img src="{{ asset('favicon.png') }}" alt="Logo" class="h-8 w-auto" />
        <span class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">
            Toko Jos Jaya
        </span>
    </div>
@endif