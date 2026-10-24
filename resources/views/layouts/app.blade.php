@php
    $pageTitle = isset($title) && filled($title)
        ? $title.' — '.config('app.name', 'SuratAnonim')
        : config('app.name', 'SuratAnonim').' — Surat Anonim Berbasis Consent';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="SuratAnonim adalah layanan surat anonim berbasis consent. Identitas akun tidak ditampilkan kepada penerima, dan setiap publikasi melewati moderasi.">
        <meta name="theme-color" content="#38A9F5">

        <title>{{ $pageTitle }}</title>

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-[#172B3A] bg-[#F7FAFC]">
        <a href="#main-content" class="skip-link">Lewati ke konten utama</a>

        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            <!-- Global Toast / Alerts -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4" aria-live="polite" aria-atomic="true">
                @if (session('success'))
                    <div class="p-4 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start justify-between gap-4 text-sm shadow-sm" role="status">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 shrink-0" aria-label="Tutup notifikasi">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="p-4 mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start justify-between gap-4 text-sm shadow-sm" role="alert">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 shrink-0" aria-label="Tutup peringatan">&times;</button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="p-4 mb-4 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 flex items-start justify-between gap-4 text-sm shadow-sm" role="status">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('info') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-sky-500 hover:text-sky-700 shrink-0" aria-label="Tutup informasi">&times;</button>
                    </div>
                @endif

                @if ($errors->any() && !request()->routeIs('letters.create'))
                    <div class="p-4 mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm" role="alert">
                        <p class="font-semibold mb-1">Ada beberapa hal yang perlu diperbaiki:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white border-b border-gray-100">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main id="main-content" class="flex-1" tabindex="-1">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="mt-auto py-6 border-t border-gray-100 bg-white text-center text-xs text-gray-500">
                <div class="max-w-7xl mx-auto px-4 space-y-1">
                    <p>&copy; {{ date('Y') }} SuratAnonim — Anonim bagi lawan bicara, akuntabel bagi platform.</p>
                    <p class="text-[#617586]">Tautan rahasia dapat diteruskan. Surat privat tidak pernah tampil di feed publik.</p>
                </div>
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>
