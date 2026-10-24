<x-app-layout :title="'Kotak Masuk Balasan'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#172B3A]">Kotak Masuk Balasan</h1>
                <p class="text-sm text-[#617586] mt-0.5">Percakapan dua arah secara anonim. Identitas lawan bicara tetap dirahasiakan.</p>
            </div>
            <a href="{{ route('letters.index') }}" class="btn-secondary text-sm self-start sm:self-auto">
                Lihat Surat Saya
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($threads->isEmpty())
                <div class="bg-white p-12 text-center rounded-2xl border border-[#E2EAF0] shadow-sm max-w-md mx-auto">
                    <div class="w-16 h-16 rounded-2xl bg-sky-50 text-[#38A9F5] flex items-center justify-center mx-auto mb-4" aria-hidden="true">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-[#172B3A]">Belum Ada Percakapan</h2>
                    <p class="text-sm text-[#617586] mt-1">Belum ada balasan yang masuk untuk suratmu, atau kamu belum membalas surat yang kamu terima.</p>
                    <a href="{{ route('dashboard') }}" class="btn-sky text-sm mt-5">Kembali ke Ringkasan</a>
                </div>
            @else
                <div class="bg-white rounded-2xl border border-[#E2EAF0] shadow-sm overflow-hidden">
                    <div class="divide-y divide-gray-100">
                        @foreach ($threads as $item)
                            @php
                                $isSender = $item->letter->sender_user_id === Auth::id();
                                $partnerRole = $isSender ? 'Penerima Surat' : 'Pengirim Anonim';
                                $latestReply = $item->visibleReplies->last();
                            @endphp

                            <div class="p-6 hover:bg-gray-50/50 transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="space-y-2 flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="badge-pill bg-sky-50 text-sky-700">
                                            {{ $partnerRole }}
                                        </span>
                                        @if ($item->status !== 'active')
                                            <span class="badge-pill bg-amber-50 text-amber-800">Ditutup</span>
                                        @endif
                                        @if ($item->expires_at && $item->expires_at->isPast())
                                            <span class="badge-pill bg-slate-100 text-slate-600">Kedaluwarsa</span>
                                        @endif
                                        <span class="text-xs text-[#617586]">
                                            Aktivitas terakhir: {{ $item->updated_at->diffForHumans() }}
                                        </span>
                                    </div>

                                    <p class="text-xs font-semibold text-gray-400">
                                        Surat Asal: &ldquo;{{ Str::limit($item->letter->body ?? 'Isi surat tidak tersedia', 60) }}&rdquo;
                                    </p>

                                    @if ($latestReply)
                                        <p class="text-sm text-[#172B3A] font-medium line-clamp-1">
                                            Pesan: {{ Str::limit($latestReply->body, 90) }}
                                        </p>
                                    @else
                                        <p class="text-xs italic text-gray-400">Belum ada pesan balasan yang dapat ditampilkan.</p>
                                    @endif
                                </div>

                                <div class="shrink-0 flex items-center gap-3">
                                    <span class="text-xs text-[#617586] font-semibold whitespace-nowrap">
                                        {{ $item->replies_count }} pesan
                                    </span>
                                    <a href="{{ route('replies.show', $item->id) }}" class="btn-sky text-xs py-2 px-4">
                                        Buka Percakapan &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($threads->hasPages())
                        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                            {{ $threads->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
