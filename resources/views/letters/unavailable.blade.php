<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#38A9F5">
    <meta name="robots" content="noindex, nofollow">
    <title>Surat Tidak Tersedia — SuratAnonim</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7FAFC] text-[#172B3A] min-h-screen flex flex-col items-center justify-center p-4">
    <main class="max-w-md w-full bg-white p-8 rounded-2xl border border-[#E2EAF0] text-center shadow-sm space-y-5">
        <div class="w-16 h-16 rounded-2xl bg-gray-50 border border-gray-100 text-gray-400 flex items-center justify-center mx-auto" aria-hidden="true">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>

        <div>
            <h1 class="text-xl font-bold text-[#172B3A]">Surat Tidak Tersedia</h1>
            <p class="text-sm text-[#617586] mt-2 leading-relaxed">
                Tautan yang kamu akses mungkin sudah kedaluwarsa, telah dicabut oleh pengirim, atau aksesnya dihentikan oleh sistem demi kepatuhan komunitas.
            </p>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-secondary w-full sm:w-auto text-xs py-2.5 px-4">
                Kembali ke Beranda
            </a>
            <a href="{{ route('feed.index') }}" class="btn-sky w-full sm:w-auto text-xs py-2.5 px-4">
                Jelajahi Feed Publik
            </a>
        </div>

        <p class="text-[11px] text-gray-400 pt-4 border-t border-gray-100">
            SuratAnonim &bull; Privasi &amp; Komunikasi Berbasis Consent
        </p>
    </main>
</body>
</html>
