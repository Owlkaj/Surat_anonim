<x-app-layout :title="'Feed Publik'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#172B3A]">Feed Publik Terkurasi</h1>
                <p class="text-sm text-[#617586] mt-0.5">Surat-surat anonim yang telah disetujui pengirim dan diverifikasi oleh moderator.</p>
            </div>
            <a href="{{ route('letters.create') }}" class="btn-sky text-sm self-start sm:self-auto">
                {{ Auth::check() ? 'Tulis Surat Sendiri' : 'Mulai Menulis' }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-start gap-3 p-4 rounded-xl bg-sky-50/70 border border-sky-100 text-xs text-sky-900">
                <svg class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="leading-relaxed">
                    Hanya surat dengan izin pengirim dan status <strong>approved</strong> yang tampil di sini.
                    Surat privat dan unlisted tidak pernah ditampilkan di feed publik.
                </p>
            </div>

            @if ($letters->isEmpty())
                <div class="bg-white p-12 text-center rounded-2xl border border-[#E2EAF0] shadow-sm max-w-md mx-auto">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#38A9F5] flex items-center justify-center mx-auto mb-4" aria-hidden="true">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-[#172B3A]">Belum Ada Surat Publik</h2>
                    <p class="text-sm text-[#617586] mt-1">Saat ini belum ada surat publik yang telah disetujui. Surat privat tidak ditampilkan di feed ini.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($letters as $item)
                        <article class="bg-white p-6 rounded-2xl border border-[#E2EAF0] shadow-sm flex flex-col justify-between hover:shadow-md transition">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-[#617586]">
                                    <span class="badge-pill bg-sky-50 text-sky-700">Terverifikasi Moderator</span>
                                    <span>{{ $item->published_at ? $item->published_at->translatedFormat('d M Y') : $item->created_at->translatedFormat('d M Y') }}</span>
                                </div>

                                <p class="text-sm text-[#172B3A] line-clamp-4 leading-relaxed font-normal">&ldquo;{{ Str::limit(trim($item->body), 280) }}&rdquo;</p>
                            </div>

                            <div class="pt-4 mt-4 border-t border-gray-100 flex items-center justify-between text-xs gap-2">
                                <span class="text-[#617586] flex items-center gap-1 whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    {{ $item->view_count }}x dibaca
                                </span>

                                <a href="{{ route('feed.show', $item->id) }}" class="text-[#38A9F5] hover:text-[#168DE0] font-semibold text-right">
                                    Baca Selengkapnya &rarr;
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($letters->hasPages())
                    <div class="p-4 border border-[#E2EAF0] bg-white rounded-2xl shadow-sm">
                        {{ $letters->links() }}
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
