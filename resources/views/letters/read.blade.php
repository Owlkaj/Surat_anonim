@php
    $replyRedirect = route('replies.initiate', $letter->id, false);
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Surat anonim personal untukmu. Identitas pengirim dirahasiakan oleh SuratAnonim.">
    <meta name="theme-color" content="#38A9F5">
    <title>Surat Anonim Personal — SuratAnonim</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F7FAFC] text-[#172B3A] min-h-screen py-8 sm:py-10 px-4 flex flex-col">
    <a href="#letter-content" class="skip-link">Lewati ke isi surat</a>

    <div class="max-w-3xl mx-auto w-full space-y-6 flex-1">

        <!-- Top Header Navigation -->
        <div class="flex items-center justify-between gap-3 pb-4 border-b border-gray-200">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="SuratAnonim — beranda">
                <span class="w-7 h-7 rounded-lg bg-[#38A9F5] flex items-center justify-center text-white font-bold text-sm shadow-sm" aria-hidden="true">S</span>
                <span class="font-bold text-lg text-[#172B3A]">Surat<span class="text-[#38A9F5]">Anonim</span></span>
            </a>

            <div class="flex items-center gap-2 text-xs">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-secondary text-xs py-1.5 px-3">Dashboard</a>
                @else
                    <a href="{{ route('login', ['redirect' => $replyRedirect]) }}" class="btn-secondary text-xs py-1.5 px-3">Masuk</a>
                @endauth
            </div>
        </div>

        <!-- Global Toast / Alerts -->
        <div aria-live="polite" aria-atomic="true" class="space-y-2">
            @if (session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs" role="status">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('info'))
                <div class="p-4 rounded-xl bg-sky-50 border border-sky-200 text-sky-800 text-xs" role="status">
                    {{ session('info') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs" role="alert">
                    <p class="font-semibold mb-1">Laporan belum bisa dikirim:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Main Reading Card (UI.md 6.7) -->
        <main id="letter-content" class="letter-read-panel space-y-8" tabindex="-1">
            <!-- Header of letter -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-6 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 text-[#38A9F5] flex items-center justify-center font-bold text-sm" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-[#172B3A]">Surat Anonim Personal</h1>
                        <p class="text-xs text-[#617586]">Identitas pengirim dirahasiakan oleh platform.</p>
                    </div>
                </div>
                <div class="text-xs text-[#617586] text-right">
                    <div>{{ $letter->created_at->translatedFormat('d M Y') }}</div>
                    <div class="mt-0.5">{{ $letter->view_count }} kali dibaca</div>
                </div>
            </div>

            <!-- Letter Body Content -->
            <div class="letter-body-text">{{ trim($letter->body) }}</div>

            <!-- Musik latar: autoplay tanpa kotak pemutar di dalam surat. -->
            <x-letter-music :media-info="$mediaInfo" />

            <!-- Recipient Action Buttons (UI.md 6.7) -->
            <div class="pt-6 border-t border-gray-100 space-y-3">
                <div class="flex flex-wrap items-center gap-3 w-full">
                    <!-- Balas Button -->
                    @auth
                        <a href="{{ route('replies.initiate', $letter->id) }}" class="btn-sky text-sm py-2.5 px-6 w-full sm:w-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            Balas Anonim
                        </a>
                    @else
                        <a href="{{ route('login', ['redirect' => $replyRedirect]) }}" class="btn-sky text-sm py-2.5 px-6 w-full sm:w-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            Masuk untuk Membalas
                        </a>
                    @endauth

                    <!-- Laporkan Button -->
                    <button type="button" id="btnOpenReport" class="btn-secondary text-xs py-2.5 px-4 text-gray-600 hover:text-red-600">
                        Laporkan
                    </button>
                </div>

                <p class="text-[11px] text-[#617586]">
                    Tautan rahasia dapat dibagikan atau kedaluwarsa. Penerima email dapat berhenti menerima pemberitahuan melalui tautan resmi yang disertakan di dalam email.
                </p>
            </div>

            @guest
                <p class="text-center text-xs text-[#617586]">
                    Belum punya akun?
                    <a href="{{ route('register', ['redirect' => $replyRedirect]) }}" class="font-semibold text-[#126FB3] hover:text-[#168DE0]">Daftar untuk membalas</a>.
                    Identitas akunmu tidak akan ditampilkan kepada pengirim.
                </p>
            @endguest
        </main>

        <!-- Privacy Transparency Note (UI.md 6.7) -->
        <p class="text-center text-xs text-[#617586] max-w-lg mx-auto leading-relaxed">
            Penerima dapat membaca surat tanpa membuat akun. Jika kamu membalas, identitas profilmu tetap dirahasiakan dari pengirim oleh SuratAnonim.
        </p>
    </div>

    <footer class="max-w-3xl mx-auto w-full pt-6 pb-2 text-center text-[11px] text-gray-400">
        &copy; {{ date('Y') }} SuratAnonim — Tautan ini bersifat rahasia; siapa pun yang memilikinya dapat membaca surat.
    </footer>

    <!-- Report Modal (UI.md 6.10 & 7.4) -->
    <div id="reportModal"
         class="fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4 hidden"
         role="dialog"
         aria-modal="true"
         aria-labelledby="reportModalTitle"
         aria-hidden="true">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-gray-100">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h2 id="reportModalTitle" class="font-bold text-base text-[#172B3A]">Laporkan Surat Ini</h2>
                <button type="button" data-close-report class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1" aria-label="Tutup dialog laporan">&times;</button>
            </div>

            <form action="{{ route('reports.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="content_type" value="letter">
                <input type="hidden" name="content_id" value="{{ $letter->id }}">
                <!-- Tautan rahasia surat yang sedang dibaca: membuktikan pelapor
                     memiliki akses ke surat ini, dan mencegah oracle keberadaan konten. -->
                <input type="hidden" name="access_token" value="{{ $token }}">

                <div>
                    <label for="reportCategory" class="block text-xs font-bold text-[#172B3A] mb-1">Kategori Masalah <span class="text-red-500" aria-hidden="true">*</span></label>
                    <select id="reportCategory" name="category" required class="w-full rounded-xl border-[#E2EAF0] text-sm p-2.5">
                        <option value="harassment">Pelecehan / Kata-kata merendahkan</option>
                        <option value="threat">Ancaman kekerasan / intimidasi</option>
                        <option value="doxxing">Penyebaran data pribadi (Doxxing)</option>
                        <option value="defamation">Fitnah / Serangan personal</option>
                        <option value="spam">Spam / Tautan mencurigakan</option>
                        <option value="fraud">Penipuan / Pemerasan</option>
                        <option value="unwanted_sexual">Konten seksual tidak diinginkan</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label for="reportDetails" class="block text-xs font-bold text-[#172B3A] mb-1">Keterangan Tambahan (Opsional)</label>
                    <textarea id="reportDetails" name="details" rows="3" placeholder="Jelaskan secara singkat bagian yang melanggar..." class="w-full rounded-xl border-[#E2EAF0] text-xs p-3" maxlength="1000"></textarea>
                </div>

                <p class="text-[11px] text-[#617586]">Identitas pelapor tidak ditampilkan kepada pengirim konten. Tim kami akan meninjau laporan ini.</p>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" data-close-report class="btn-secondary text-xs py-2 px-4">Batal</button>
                    <button type="submit" class="btn-danger text-xs py-2 px-4">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('reportModal');
            const openButton = document.getElementById('btnOpenReport');
            const closeButtons = document.querySelectorAll('[data-close-report]');

            if (!modal || !openButton) return;

            let lastFocusedElement = null;

            const openModal = () => {
                lastFocusedElement = document.activeElement;
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                const firstField = document.getElementById('reportCategory');
                if (firstField) firstField.focus();
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (lastFocusedElement) lastFocusedElement.focus();
            };

            openButton.addEventListener('click', openModal);
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });

            document.addEventListener('keydown', (event) => {
                if (modal.classList.contains('hidden')) return;

                if (event.key === 'Escape') {
                    closeModal();
                    return;
                }

                // Focus trap sederhana agar fokus tidak keluar dari dialog.
                if (event.key === 'Tab') {
                    const focusables = modal.querySelectorAll('button, select, textarea, input, a[href]');
                    if (focusables.length === 0) return;
                    const first = focusables[0];
                    const last = focusables[focusables.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });
        });
    </script>
</body>
</html>
