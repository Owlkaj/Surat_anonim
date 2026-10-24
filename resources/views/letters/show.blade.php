<x-app-layout :title="'Detail Surat'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#172B3A]">Detail Surat Anonim</h1>
                <p class="text-sm text-[#617586] mt-0.5">
                    Dibuat pada {{ $letter->created_at->translatedFormat('d M Y, H:i') }}
                    &bull;
                    @if ($letter->expires_at)
                        Berlaku hingga {{ $letter->expires_at->translatedFormat('d M Y') }}
                    @else
                        Tanpa masa kedaluwarsa
                    @endif
                </p>
            </div>
            <a href="{{ route('letters.index') }}" class="btn-secondary text-sm self-start sm:self-auto">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Email Notification Queued Banner -->
            @if (session('email_target'))
                <div class="p-6 rounded-2xl bg-gradient-to-r from-sky-50 via-blue-50 to-emerald-50 border border-sky-200 shadow-sm space-y-3">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-sky-500 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0" aria-hidden="true">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-sky-100 text-sky-800 text-[11px] font-bold">
                                Notifikasi Email Dalam Antrean
                            </span>
                            <h2 class="text-base font-bold text-[#172B3A]">Email untuk {{ session('email_target') }} sedang diproses.</h2>
                            <p class="text-xs text-sky-900 leading-relaxed">
                                Sistem akan mengirimkan tautan rahasia ke alamat target melalui antrean email.
                                Status pengiriman terbaru dapat dilihat pada kartu surat di bawah. Isi surat tidak pernah disertakan di dalam email.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Link Rahasia Box -->
            @if (session('secret_url'))
                <div class="p-6 rounded-2xl bg-gradient-to-r from-blue-50 to-sky-50 border border-[#38A9F5]/30 shadow-sm space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#38A9F5] text-white text-xs font-bold">
                                {{ session('email_target') ? 'Tautan Rahasia (Arsip / Cadangan)' : 'Tautan Rahasia Dibuat' }}
                            </span>
                            <h2 class="text-base font-bold text-[#172B3A]">
                                {{ session('email_target') ? 'Simpan Tautan Rahasia Ini' : 'Bagikan Tautan Rahasia Ini' }}
                            </h2>
                            <p class="text-xs text-[#617586] leading-relaxed">
                                {{ session('email_target')
                                    ? 'Notifikasi email sedang diproses. Tautan di bawah adalah salinan cadangan untuk arsip pribadimu.'
                                    : 'Siapa pun yang memiliki tautan ini dapat membaca isi surat tanpa perlu membuat akun. Identitas akunmu tetap dirahasiakan.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <label for="secretUrlInput" class="sr-only">Tautan rahasia surat</label>
                        <input type="text"
                               readonly
                               id="secretUrlInput"
                               value="{{ session('secret_url') }}"
                               class="w-full text-xs font-mono bg-white border border-[#E2EAF0] rounded-xl p-3 text-[#172B3A]">
                        <button type="button"
                                id="btnCopyLink"
                                class="btn-sky text-xs whitespace-nowrap w-full sm:w-auto px-4 py-3">
                            Salin Link
                        </button>
                    </div>

                    <p id="copyStatus" class="sr-only" aria-live="polite"></p>

                    @if (!session('email_target'))
                        <!-- Manual Share Options (UI.md 6.6 & FR-11) only for non-email letters -->
                        <div class="flex flex-wrap items-center gap-2 pt-2 text-xs">
                            <span class="text-[#617586] font-medium">Bagikan via:</span>
                            <a href="https://api.whatsapp.com/send?text={{ urlencode('Ada surat rahasia untukmu di: ' . session('secret_url')) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn-secondary text-xs py-1.5 px-3 text-emerald-700 hover:bg-emerald-50">
                                WhatsApp
                            </a>
                            <a href="https://t.me/share/url?url={{ urlencode(session('secret_url')) }}&text={{ urlencode('Ada surat anonim untukmu') }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn-secondary text-xs py-1.5 px-3 text-sky-700 hover:bg-sky-50">
                                Telegram
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Letter Card Details -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-6">
                <!-- Status Row -->
                <div class="flex flex-wrap items-center justify-between gap-3 pb-6 border-b border-gray-100">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-letter-status :letter="$letter" />
                        <x-link-status :letter="$letter" />
                        <x-delivery-status :delivery="$delivery" />
                    </div>

                    <div class="text-xs text-[#617586] flex flex-wrap items-center gap-4">
                        <span>Dibuka: <strong>{{ $letter->view_count }} kali</strong></span>
                        <span>Target: <strong>{{ $letter->target_label ?? 'Manual / Tanpa Kontak' }}</strong></span>
                    </div>
                </div>

                @if ($delivery && $delivery->status === 'failed_permanent')
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs" role="alert">
                        Notifikasi email gagal dikirim secara permanen. Surat tetap dapat dibagikan secara manual menggunakan tautan rahasia di atas (bila masih tersedia).
                    </div>
                @endif

                <!-- Letter Body -->
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#617586] mb-3">Isi Surat</h2>
                    <div class="letter-body-text p-6 rounded-xl bg-gray-50/60 border border-gray-100">{{ trim($letter->body) }}</div>
                </div>

                <!-- Music Player if provided -->
                @if ($mediaInfo)
                    <div class="p-4 rounded-xl border border-blue-100 bg-blue-50/40 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-semibold text-[#172B3A]">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#38A9F5]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                Lagu Pengiring ({{ $mediaInfo['provider'] }})
                            </span>
                            <a href="{{ $mediaInfo['url'] }}" target="_blank" rel="noopener noreferrer" class="text-[#38A9F5] hover:underline">
                                Buka Tautan Eksternal &rarr;
                            </a>
                        </div>

                        @if ($mediaInfo['embed_url'])
                            <div class="mt-2 rounded-xl overflow-hidden aspect-video max-h-48">
                                <iframe src="{{ $mediaInfo['embed_url'] }}"
                                        title="Pemutar musik {{ $mediaInfo['provider'] }}"
                                        class="w-full h-full border-0"
                                        allow="encrypted-media"
                                        loading="lazy"></iframe>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Thread section if exists -->
                @if ($thread)
                    <div class="p-6 rounded-2xl border border-sky-100 bg-sky-50/30 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-bold text-[#172B3A]">Thread Balasan Anonim</h2>
                                <p class="text-xs text-[#617586]">
                                    @if ($thread->visibleReplies->count() > 0)
                                        Penerima telah membalas surat ini secara anonim.
                                    @else
                                        Percakapan sudah dibuat, tetapi belum ada balasan yang dapat ditampilkan.
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('replies.show', $thread->id) }}" class="btn-sky text-xs py-2 px-4 self-start sm:self-auto">
                                Buka Percakapan ({{ $thread->visibleReplies->count() }} pesan)
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Actions row -->
                <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($letter->revoked_at === null && (!$letter->expires_at || !$letter->expires_at->isPast()))
                            <form action="{{ route('letters.revoke', $letter->id) }}" method="POST" onsubmit="return confirm('Cabut tautan rahasia surat ini? Penerima tidak akan dapat membukanya lagi.');">
                                @csrf
                                <button type="submit" class="btn-danger text-xs py-2 px-4">
                                    Cabut Tautan Rahasia
                                </button>
                            </form>
                        @endif

                        @if (($letter->visibility === 'private' || $letter->visibility === 'unlisted') && $letter->revoked_at === null)
                            <form action="{{ route('letters.requestPublic', $letter->id) }}" method="POST" onsubmit="return confirm('Ajukan surat ini agar ditinjau moderator untuk tampil di feed publik?');">
                                @csrf
                                <button type="submit" class="btn-secondary text-xs py-2 px-4 text-amber-700">
                                    Ajukan ke Feed Publik
                                </button>
                            </form>
                        @endif
                    </div>

                    <span class="text-xs text-[#617586]">
                        Privasi: identitas pengirim dan penerima disembunyikan.
                    </span>
                </div>
            </div>

        </div>
    </div>

    <!-- Copy Link Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnCopy = document.getElementById('btnCopyLink');
            const urlInput = document.getElementById('secretUrlInput');
            const copyStatus = document.getElementById('copyStatus');

            if (!btnCopy || !urlInput) return;

            const showCopyResult = (message, success) => {
                if (copyStatus) copyStatus.textContent = message;
                const originalText = btnCopy.textContent;
                btnCopy.textContent = success ? 'Berhasil Disalin!' : 'Gagal Menyalin';
                btnCopy.classList.toggle('is-success', success);
                btnCopy.classList.toggle('is-error', !success);

                window.setTimeout(() => {
                    btnCopy.textContent = originalText;
                    btnCopy.classList.remove('is-success', 'is-error');
                }, 2500);
            };

            btnCopy.addEventListener('click', async () => {
                urlInput.focus();
                urlInput.select();

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(urlInput.value);
                    } else {
                        const copied = document.execCommand('copy');
                        if (!copied) throw new Error('copy_failed');
                    }

                    showCopyResult('Tautan berhasil disalin ke clipboard.', true);
                } catch (error) {
                    showCopyResult('Tautan gagal disalin. Pilih teks lalu salin secara manual.', false);
                }
            });
        });
    </script>
</x-app-layout>
