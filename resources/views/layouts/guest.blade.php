@php
    $pageTitle = isset($title) && filled($title)
        ? $title.' — '.config('app.name', 'SuratAnonim')
        : 'Masuk — '.config('app.name', 'SuratAnonim');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Masuk atau daftar akun SuratAnonim untuk menulis dan membalas surat anonim berbasis consent.">
        <meta name="theme-color" content="#38A9F5">

        <title>{{ $pageTitle }}</title>

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[#172B3A] antialiased bg-[#F7FAFC]">
        <a href="#auth-form" class="skip-link">Lewati ke formulir</a>

        <div class="min-h-screen flex">
            <!-- Brand & privacy panel (desktop) -->
            <aside class="hidden lg:flex lg:w-[46%] xl:w-[42%] bg-gradient-to-br from-[#EAF6FF] via-white to-[#F7FAFC] border-r border-[#E2EAF0] p-12 flex-col justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 w-fit" aria-label="SuratAnonim — beranda">
                    <span class="w-10 h-10 rounded-xl bg-[#38A9F5] flex items-center justify-center text-white font-bold text-xl shadow-sm" aria-hidden="true">S</span>
                    <span class="font-bold text-2xl tracking-tight text-[#172B3A]">Surat<span class="text-[#38A9F5]">Anonim</span></span>
                </a>

                <div class="max-w-md space-y-6">
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-[#D9EAF5] text-[#126FB3] text-xs font-semibold">
                        Anonim bagi lawan bicara, akuntabel bagi platform
                    </span>
                    <h1 class="text-3xl font-extrabold tracking-tight leading-tight text-[#172B3A]">
                        Kata yang sulit diucapkan, kini bisa sampai dengan tenang.
                    </h1>
                    <p class="text-sm text-[#617586] leading-relaxed">
                        Tulis surat personal tanpa menampilkan identitas akun atau email kepada penerima. Penerima dapat membaca lewat tautan rahasia tanpa perlu membuat akun.
                    </p>

                    <ul class="space-y-3 text-sm text-[#617586]">
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-[#38A9F5] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Surat baru selalu privat secara default.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-[#38A9F5] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span>Tautan rahasia dapat diteruskan; bagikan hanya kepada orang yang kamu percaya.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-[#38A9F5] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h10a2 2 0 012 2v6a2 2 0 01-2 2H7l-4 4V4z"/></svg>
                            <span>Balasan tetap anonim. Kamu tetap bisa melaporkan atau berhenti menerima.</span>
                        </li>
                    </ul>
                </div>

                <p class="text-[11px] text-[#8BA0AF] leading-relaxed max-w-sm">
                    Anonimitas berlaku terhadap pengguna lain, bukan mutlak terhadap operator, penyedia infrastruktur, atau proses hukum yang sah.
                </p>
            </aside>

            <!-- Form panel -->
            <main id="auth-form" class="flex-1 flex flex-col min-h-screen" tabindex="-1">
                <div class="lg:hidden px-6 pt-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 w-fit" aria-label="SuratAnonim — beranda">
                        <span class="w-9 h-9 rounded-xl bg-[#38A9F5] flex items-center justify-center text-white font-bold text-lg shadow-sm" aria-hidden="true">S</span>
                        <span class="font-bold text-xl tracking-tight text-[#172B3A]">Surat<span class="text-[#38A9F5]">Anonim</span></span>
                    </a>
                </div>

                <div class="flex-1 flex items-center justify-center px-5 sm:px-8 py-10">
                    <div class="w-full max-w-md">
                        <div class="bg-white border border-[#E2EAF0] rounded-2xl shadow-sm p-6 sm:p-8">
                            {{ $slot }}
                        </div>

                        <div class="mt-6 text-center text-xs text-[#617586] space-y-2">
                            <p>
                                Dengan masuk, kamu menyetujui prinsip privasi dan ketentuan komunitas SuratAnonim.
                            </p>
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-1 font-semibold text-[#126FB3] hover:text-[#168DE0]">
                                &larr; Kembali ke beranda
                            </a>
                        </div>
                    </div>
                </div>

                <footer class="py-5 text-center text-[11px] text-gray-500 px-4">
                    &copy; {{ date('Y') }} SuratAnonim — Anonim bagi lawan bicara, akuntabel bagi platform.
                </footer>
            </main>
        </div>
    </body>
</html>
