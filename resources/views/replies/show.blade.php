<x-app-layout :title="'Percakapan Anonim'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-[#172B3A]">Percakapan Anonim</h1>
                <p class="text-xs text-[#617586] mt-0.5">Identitas kedua pihak dirahasiakan oleh platform SuratAnonim.</p>
            </div>
            <a href="{{ route('replies.index') }}" class="btn-secondary text-xs self-start sm:self-auto">
                &larr; Kembali ke Kotak Masuk
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Context: Original Letter Snippet -->
            <div class="bg-white p-5 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-2">
                <div class="flex items-center justify-between text-xs text-[#617586]">
                    <span class="font-bold uppercase tracking-wider text-[#38A9F5]">Surat Rujukan Awal</span>
                    <span>{{ $thread->letter->created_at->translatedFormat('d M Y') }}</span>
                </div>
                <p class="text-sm text-[#172B3A] italic bg-gray-50/60 p-4 rounded-xl border border-gray-100 whitespace-pre-wrap">&ldquo;{{ trim($thread->letter->body ?? 'Isi surat sudah tidak tersedia (kedaluwarsa).') }}&rdquo;</p>
                @if ($thread->expires_at)
                    <p class="text-[11px] text-[#617586]">
                        Percakapan berlaku hingga {{ $thread->expires_at->translatedFormat('d M Y, H:i') }}.
                    </p>
                @endif
            </div>

            @if ($thread->status !== 'active')
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs" role="status">
                    Percakapan ini sudah ditutup. Riwayat pesan tetap dapat dibaca, tetapi pesan baru tidak dapat dikirim.
                </div>
            @elseif ($thread->expires_at && $thread->expires_at->isPast())
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs" role="status">
                    Percakapan ini sudah kedaluwarsa. Pesan baru tidak dapat dikirim.
                </div>
            @endif

            @if ($hiddenRepliesCount > 0)
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs" role="status">
                    {{ $hiddenRepliesCount }} pesan sedang ditinjau moderator dan belum ditampilkan kepada peserta percakapan.
                </div>
            @endif

            <!-- Thread Messages Stream (UI.md 6.8) -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-6">
                <div class="space-y-4" aria-live="polite">
                    @if ($thread->visibleReplies->isEmpty())
                        <div class="text-center py-8 text-xs text-gray-400">
                            Belum ada pesan dalam percakapan ini. Kirim balasan pertamamu di bawah.
                        </div>
                    @else
                        @foreach ($thread->visibleReplies as $reply)
                            @php
                                $isMe = $reply->author_user_id === Auth::id();
                                $senderRole = $reply->author_user_id === $thread->letter->sender_user_id ? 'Pengirim Anonim' : 'Penerima';
                            @endphp

                            <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }} space-y-1">
                                <div class="flex items-center gap-2 px-1 text-[11px] text-[#617586]">
                                    <span class="font-bold {{ $isMe ? 'text-[#38A9F5]' : 'text-gray-700' }}">
                                        {{ $isMe ? 'Kamu (' . $senderRole . ')' : $senderRole }}
                                    </span>
                                    <span>&bull; {{ $reply->created_at->translatedFormat('H:i, d M') }}</span>
                                </div>

                                <div class="max-w-lg p-4 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap break-words {{ $isMe ? 'bg-[#38A9F5] text-white rounded-br-sm' : 'bg-[#F1F6FA] text-[#172B3A] rounded-bl-sm border border-[#E2EAF0]' }}">{{ trim($reply->body) }}</div>

                                @if (!$isMe)
                                    <button type="button"
                                            data-report-reply="{{ $reply->id }}"
                                            class="text-[10px] text-gray-400 hover:text-red-500 transition px-1">
                                        Laporkan Pesan
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Reply Composer Form (UI.md 6.8) -->
                @if ($thread->status === 'active' && (!$thread->expires_at || !$thread->expires_at->isPast()))
                    <form action="{{ route('replies.store', $thread->id) }}" method="POST" class="pt-6 border-t border-gray-100 space-y-3"
                          x-data="{ submitting: false }" @submit="submitting = true">
                        @csrf
                        <div class="flex items-center justify-between">
                            <label for="replyBody" class="text-xs font-bold text-[#172B3A]">Kirim Balasan Anonim</label>
                            <span id="replyCounter" class="text-[11px] text-[#617586]" aria-live="polite">0 / 5.000</span>
                        </div>

                        <textarea id="replyBody"
                                  name="body"
                                  rows="3"
                                  required
                                  aria-describedby="replyHelp replyCounter"
                                  placeholder="Tulis pesan balasanmu di sini..."
                                  class="w-full rounded-xl border-[#E2EAF0] focus:border-[#38A9F5] focus:ring focus:ring-[#38A9F5]/20 text-sm p-3.5"
                                  maxlength="5000">{{ old('body') }}</textarea>

                        @error('body')
                            <p class="text-xs text-red-600 font-medium" role="alert">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-1">
                            <span id="replyHelp" class="text-[11px] text-[#617586]">Pesan teks biasa &bull; Dilarang mengirim ujaran kebencian/spam</span>
                            <button type="submit" class="btn-sky text-xs py-2 px-6" :disabled="submitting">
                                <span x-show="! submitting">Kirim Balasan</span>
                                <span x-show="submitting" x-cloak>Mengirim...</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

        </div>
    </div>

    <!-- Report Reply Modal -->
    <div id="replyReportModal"
         class="fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4 hidden"
         role="dialog"
         aria-modal="true"
         aria-labelledby="replyReportTitle"
         aria-hidden="true">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-gray-100">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h2 id="replyReportTitle" class="font-bold text-base text-[#172B3A]">Laporkan Pesan Balasan</h2>
                <button type="button" data-close-reply-report class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1" aria-label="Tutup dialog laporan">&times;</button>
            </div>

            <form action="{{ route('reports.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="content_type" value="reply">
                <input type="hidden" name="content_id" id="modalReplyContentId" value="">

                <div>
                    <label for="replyReportCategory" class="block text-xs font-bold text-[#172B3A] mb-1">Kategori Masalah <span class="text-red-500" aria-hidden="true">*</span></label>
                    <select id="replyReportCategory" name="category" required class="w-full rounded-xl border-[#E2EAF0] text-sm p-2.5">
                        <option value="harassment">Pelecehan / Kata-kata kasar</option>
                        <option value="threat">Ancaman kekerasan / intimidasi</option>
                        <option value="doxxing">Penyebaran data pribadi</option>
                        <option value="defamation">Fitnah / Serangan personal</option>
                        <option value="spam">Spam / Tautan berbahaya</option>
                        <option value="fraud">Penipuan / Pemerasan</option>
                        <option value="unwanted_sexual">Konten seksual tidak diinginkan</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label for="replyReportDetails" class="block text-xs font-bold text-[#172B3A] mb-1">Keterangan Tambahan (Opsional)</label>
                    <textarea id="replyReportDetails" name="details" rows="3" placeholder="Jelaskan alasan pelaporan..." class="w-full rounded-xl border-[#E2EAF0] text-xs p-3" maxlength="1000"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" data-close-reply-report class="btn-secondary text-xs py-2 px-4">Batal</button>
                    <button type="submit" class="btn-danger text-xs py-2 px-4">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('replyReportModal');
            const contentIdInput = document.getElementById('modalReplyContentId');
            const openButtons = document.querySelectorAll('[data-report-reply]');
            const closeButtons = document.querySelectorAll('[data-close-reply-report]');
            const replyInput = document.getElementById('replyBody');
            const counter = document.getElementById('replyCounter');

            if (!modal || !contentIdInput) return;

            let lastFocusedElement = null;

            const openModal = (replyId) => {
                lastFocusedElement = document.activeElement;
                contentIdInput.value = replyId;
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                const firstField = document.getElementById('replyReportCategory');
                if (firstField) firstField.focus();
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                if (lastFocusedElement) lastFocusedElement.focus();
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', () => openModal(button.dataset.reportReply));
            });
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

            if (replyInput && counter) {
                const updateCounter = () => {
                    counter.textContent = `${replyInput.value.length.toLocaleString('id-ID')} / 5.000`;
                };
                replyInput.addEventListener('input', updateCounter);
                updateCounter();
            }
        });
    </script>
</x-app-layout>
