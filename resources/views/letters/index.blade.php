<x-app-layout :title="'Surat Saya'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#172B3A]">Surat Saya</h1>
                <p class="text-sm text-[#617586] mt-0.5">Kelola seluruh surat anonim yang pernah kamu buat dan pantau status pembacaannya.</p>
            </div>
            <a href="{{ route('letters.create') }}" class="btn-sky self-start sm:self-auto text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tulis Surat Baru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($letters->isEmpty() && !request()->filled('q') && !request()->filled('visibility'))
                <div class="bg-white p-12 text-center rounded-2xl border border-[#E2EAF0] shadow-sm max-w-lg mx-auto">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#38A9F5] flex items-center justify-center mx-auto mb-4" aria-hidden="true">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-[#172B3A]">Belum Ada Surat</h3>
                    <p class="text-sm text-[#617586] mt-1">Kamu belum menulis surat apapun. Buat surat personal pertamamu sekarang.</p>
                    <a href="{{ route('letters.create') }}" class="btn-sky text-sm mt-5">Tulis Surat Pertama</a>
                </div>
            @else
                <!-- Filter & Pencarian -->
                <form method="GET" action="{{ route('letters.index') }}" class="bg-white p-4 rounded-2xl border border-[#E2EAF0] shadow-sm flex flex-col md:flex-row md:items-end gap-3">
                    <div class="flex-1">
                        <label for="letterSearch" class="block text-xs font-semibold text-[#172B3A] mb-1">Cari surat</label>
                        <input type="search"
                               id="letterSearch"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Cari isi surat atau label target..."
                               class="w-full rounded-xl border-[#E2EAF0] focus:border-[#38A9F5] focus:ring focus:ring-[#38A9F5]/20 text-sm py-2.5">
                    </div>

                    <div class="w-full md:w-52">
                        <label for="letterVisibility" class="block text-xs font-semibold text-[#172B3A] mb-1">Visibilitas</label>
                        <select id="letterVisibility" name="visibility" class="w-full rounded-xl border-[#E2EAF0] focus:border-[#38A9F5] focus:ring focus:ring-[#38A9F5]/20 text-sm py-2.5">
                            <option value="">Semua visibilitas</option>
                            <option value="private" @selected(request('visibility') === 'private')>Privat</option>
                            <option value="public_pending" @selected(request('visibility') === 'public_pending')>Menunggu moderasi</option>
                            <option value="public" @selected(request('visibility') === 'public')>Publik</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn-sky text-xs py-2.5 px-4">Terapkan</button>
                        @if (request()->filled('q') || request()->filled('visibility'))
                            <a href="{{ route('letters.index') }}" class="btn-secondary text-xs py-2.5 px-4">Reset</a>
                        @endif
                    </div>
                </form>

                <div class="flex items-center justify-between text-xs text-[#617586]">
                    <span>Menampilkan {{ $letters->count() }} dari {{ $letters->total() }} surat.</span>
                    @if (request()->filled('q') || request()->filled('visibility'))
                        <span>Filter aktif</span>
                    @endif
                </div>

                @if ($letters->isEmpty())
                    <div class="bg-white p-10 text-center rounded-2xl border border-[#E2EAF0] shadow-sm">
                        <h3 class="text-base font-bold text-[#172B3A]">Tidak ada surat yang cocok</h3>
                        <p class="text-sm text-[#617586] mt-1">Coba kata kunci lain atau hapus filter untuk melihat semua surat.</p>
                        <a href="{{ route('letters.index') }}" class="btn-secondary text-xs mt-4">Hapus filter</a>
                    </div>
                @else
                    <div class="bg-white rounded-2xl border border-[#E2EAF0] shadow-sm overflow-hidden">
                        <div class="divide-y divide-gray-100">
                            @foreach ($letters as $item)
                                @php
                                    $threadReplies = $item->thread?->visibleReplies?->count() ?? 0;
                                @endphp

                                <div class="p-6 hover:bg-gray-50/50 transition flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div class="space-y-2 flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-letter-status :letter="$item" />
                                            <x-link-status :letter="$item" />
                                            <x-delivery-status :delivery="$item->latestDeliveryJob" />
                                            <span class="text-xs text-[#617586]">&bull; {{ $item->created_at->translatedFormat('d M Y, H:i') }}</span>
                                        </div>

                                        <p class="text-sm text-[#172B3A] font-medium line-clamp-2 leading-relaxed">
                                            {{ Str::limit($item->body, 160) }}
                                        </p>

                                        <div class="flex flex-wrap items-center gap-4 text-xs text-[#617586] pt-1">
                                            <span>Target: <strong>{{ $item->target_label ?? 'Tanpa label' }}</strong></span>
                                            <span>Dibuka: <strong>{{ $item->view_count }}x</strong></span>
                                            @if ($threadReplies > 0)
                                                <span class="text-[#38A9F5] font-semibold">
                                                    {{ $threadReplies }} balasan terlihat
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                                        <a href="{{ route('letters.show', $item->id) }}" class="btn-secondary text-xs py-2 px-3">
                                            Detail Surat
                                        </a>

                                        @if ($item->thread)
                                            <a href="{{ route('replies.show', $item->thread->id) }}" class="btn-sky text-xs py-2 px-3">
                                                Buka Thread
                                            </a>
                                        @endif

                                        @if ($item->revoked_at === null && (!$item->expires_at || !$item->expires_at->isPast()))
                                            @if ($item->visibility === 'private' || $item->visibility === 'unlisted')
                                                <form action="{{ route('letters.requestPublic', $item->id) }}" method="POST" onsubmit="return confirm('Ajukan surat ini agar ditinjau moderator untuk tampil di feed publik?');">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-semibold text-amber-700 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 py-2 px-3 rounded-xl transition">
                                                        Ajukan Publik
                                                    </button>
                                                </form>
                                            @endif

                                            <form action="{{ route('letters.revoke', $item->id) }}" method="POST" onsubmit="return confirm('Apakah kamu yakin ingin mencabut link rahasia surat ini? Penerima tidak akan dapat membukanya lagi.');">
                                                @csrf
                                                <button type="submit" class="btn-danger text-xs py-2 px-3">
                                                    Cabut Link
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('letters.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus PERMANEN surat ini beserta balasan dan seluruh data terkait? Tindakan ini tidak dapat dibatalkan dan tautan rahasia langsung tidak berlaku.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger text-xs py-2 px-3">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($letters->hasPages())
                            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                                {{ $letters->links() }}
                            </div>
                        @endif
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
