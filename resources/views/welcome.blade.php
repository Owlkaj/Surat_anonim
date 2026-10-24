<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tulis surat anonim berbasis consent. Identitas akun tidak ditampilkan kepada penerima, tautan rahasia, dan feed publik yang dimoderasi.">
    <meta name="theme-color" content="#38A9F5">
    <title>SuratAnonim — Sistem Surat Anonim Berbasis Consent</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7FAFC] text-[#172B3A] min-h-screen flex flex-col justify-between">
    <!-- Navbar -->
    <header class="bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-[#38A9F5] flex items-center justify-center text-white font-bold text-lg shadow-sm">S</span>
                <span class="font-bold text-xl text-[#172B3A]">Surat<span class="text-[#38A9F5]">Anonim</span></span>
            </div>

            <nav class="flex items-center gap-3 sm:gap-4 text-sm font-semibold" aria-label="Navigasi utama">
                <a href="{{ route('feed.index') }}" class="text-gray-600 hover:text-[#38A9F5] transition">Feed Publik</a>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-sky py-2 px-4 text-xs">Buka Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline text-gray-600 hover:text-[#38A9F5] transition">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-sky py-2 px-4 text-xs">Mulai Sekarang</a>
                    @endauth
                @endif
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center space-y-8">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-[#38A9F5] text-xs font-semibold">
            <span>✨ Komunikasi Personal &bull; Tanpa Identitas Profil Terbuka</span>
        </div>

        <h1 class="text-4xl sm:text-6xl font-extrabold text-[#172B3A] tracking-tight max-w-3xl mx-auto leading-tight">
            Sampaikan kata yang tersimpan, <span class="text-[#38A9F5]">secara rahasia &amp; terhormat</span>.
        </h1>

        <p class="text-base sm:text-lg text-[#617586] max-w-2xl mx-auto leading-relaxed">
            SuratAnonim memungkinkanmu mengirim pesan personal tanpa menampilkan identitas akun atau email kepada penerima. Penerima dapat membaca melalui link rahasia tanpa perlu registrasi.
        </p>

        <div class="space-y-3 pt-4">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('letters.create') }}" class="btn-sky text-base py-3.5 px-8 shadow-md">
                    Tulis Surat Sekarang
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="{{ route('feed.index') }}" class="btn-secondary text-base py-3.5 px-6">
                    Jelajahi Feed Publik
                </a>
            </div>
            @guest
                <p class="text-xs text-[#617586]">Kamu akan diminta masuk atau mendaftar sebelum menulis surat.</p>
            @endguest
        </div>

        <!-- 3 Alur Kerja (UI.md 6.1) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-16 text-left">
            <div class="bg-white p-6 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#38A9F5] flex items-center justify-center font-bold text-sm">1</div>
                <h3 class="font-bold text-base text-[#172B3A]">Tulis Surat &amp; Musik</h3>
                <p class="text-xs text-[#617586] leading-relaxed">Tulis pesan hingga 5.000 karakter dan tambahkan lagu pengiring Spotify/YouTube secara opsional.</p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#38A9F5] flex items-center justify-center font-bold text-sm">2</div>
                <h3 class="font-bold text-base text-[#172B3A]">Tautan Rahasia Aman</h3>
                <p class="text-xs text-[#617586] leading-relaxed">Sistem membuat link rahasia kriptografis. Bagikan sendiri secara manual atau kirimkan notifikasi email.</p>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">3</div>
                <h3 class="font-bold text-base text-[#172B3A]">Balasan Sukarela</h3>
                <p class="text-xs text-[#617586] leading-relaxed">Penerima membaca tanpa akun. Jika ingin merespons, penerima dapat login untuk memulai thread dua arah.</p>
            </div>
        </div>

        <!-- Transparansi & Kebijakan Privasi (PRD Section 4 & UI.md 6.1) -->
        <div class="bg-white p-8 rounded-2xl border border-[#E2EAF0] shadow-sm text-left space-y-4 max-w-3xl mx-auto mt-12">
            <div class="flex items-center gap-2 font-bold text-sm text-[#172B3A]">
                <svg class="w-5 h-5 text-[#38A9F5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Komitmen Kejujuran Privasi SuratAnonim</span>
            </div>
            <p class="text-xs text-[#617586] leading-relaxed">
                Kami tidak menjanjikan "anonimitas 100% tanpa jejak". Identitasmu dirahasiakan terhadap penerima dan pengunjung publik, namun sistem mencatat log akun minimum demi menjaga keselamatan komunitas dari ancaman, fitnah, doxxing, dan penipuan.
            </p>
            <p class="text-xs text-[#617586] leading-relaxed">
                Tautan rahasia dapat diteruskan oleh pemegangnya. Surat baru selalu bersifat privat secara default dan hanya diterbitkan di feed publik jika disetujui pengirim serta diverifikasi oleh tim moderator.
            </p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-8 border-t border-gray-100 bg-white text-center text-xs text-gray-500">
        <div class="max-w-6xl mx-auto px-4 space-y-2">
            <p>&copy; {{ date('Y') }} SuratAnonim &bull; Dikembangkan dengan Laravel &amp; Supabase PostgreSQL.</p>
            <p class="text-gray-400">Dirancang dengan prinsip Consent, Anti-Abuse, dan Transparansi Etika.</p>
        </div>
    </footer>
</body>
</html>
