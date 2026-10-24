<x-app-layout :title="'Surat Publik'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-[#172B3A]">Surat Publik</h1>
                <p class="text-xs text-[#617586] mt-0.5">Diterbitkan pada {{ $letter->published_at ? $letter->published_at->translatedFormat('d M Y') : $letter->created_at->translatedFormat('d M Y') }}</p>
            </div>
            <a href="{{ route('feed.index') }}" class="btn-secondary text-xs self-start sm:self-auto">
                &larr; Kembali ke Feed
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <main class="letter-read-panel space-y-8">
                <div class="flex flex-wrap items-center justify-between gap-2 pb-6 border-b border-gray-100">
                    <span class="badge-pill bg-sky-50 text-sky-700">Telah Diverifikasi Moderator</span>
                    <span class="text-xs text-[#617586]">{{ $letter->view_count }} kali dibaca</span>
                </div>

                <div class="letter-body-text">{{ trim($letter->body) }}</div>

                @if ($mediaInfo)
                    <div class="p-5 rounded-2xl border border-blue-100 bg-blue-50/40 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-semibold text-[#172B3A]">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#38A9F5]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                Lagu Pengiring: {{ $mediaInfo['provider'] }}
                            </span>
                            <a href="{{ $mediaInfo['url'] }}" target="_blank" rel="noopener noreferrer" class="text-[#38A9F5] hover:underline">
                                Buka Tautan &rarr;
                            </a>
                        </div>

                        @if ($mediaInfo['embed_url'])
                            <div class="rounded-xl overflow-hidden aspect-video max-h-48">
                                <iframe src="{{ $mediaInfo['embed_url'] }}"
                                        title="Pemutar musik {{ $mediaInfo['provider'] }}"
                                        class="w-full h-full border-0"
                                        allow="encrypted-media"
                                        loading="lazy"></iframe>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                    <button type="button" id="btnOpenReport" class="btn-secondary text-xs text-gray-500 hover:text-red-600 self-start">
                        Laporkan Surat
                    </button>
                    <span class="text-[#617586]">SuratAnonim &bull; Feed Publik</span>
                </div>
            </main>

        </div>
    </div>

    <!-- Report Modal -->
    <div id="reportModal"
         class="fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4 hidden"
         role="dialog"
         aria-modal="true"
         aria-labelledby="reportModalTitle"
         aria-hidden="true">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-gray-100">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h2 id="reportModalTitle" class="font-bold text-base text-[#172B3A]">Laporkan Surat Publik</h2>
                <button type="button" data-close-report class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1" aria-label="Tutup dialog laporan">&times;</button>
            </div>

            <form action="{{ route('reports.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="content_type" value="letter">
                <input type="hidden" name="content_id" value="{{ $letter->id }}">

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

                <p class="text-[11px] text-[#617586]">Identitas pelapor tidak ditampilkan kepada penulis konten. Tim moderasi akan meninjau laporan ini.</p>

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
</x-app-layout>
