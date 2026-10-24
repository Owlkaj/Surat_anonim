<x-app-layout :title="'Panel Moderasi'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#172B3A]">Panel Moderasi &amp; Keamanan</h1>
                <p class="text-sm text-[#617586] mt-0.5">Tinjau permohonan publikasi feed dan tangani laporan komunitas.</p>
            </div>
            <span class="badge-pill bg-amber-100 text-amber-800 self-start sm:self-auto">
                Role: {{ Auth::user()->adminUser->role ?? 'Moderator' }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

            <!-- Section 1: Antrean Pengajuan Feed Publik -->
            <section class="bg-white rounded-2xl border border-[#E2EAF0] shadow-sm p-6 sm:p-8 space-y-6" aria-labelledby="letterQueueTitle">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 id="letterQueueTitle" class="text-lg font-bold text-[#172B3A]">Antrean Tinjauan Surat</h2>
                        <p class="text-xs text-[#617586] mt-0.5">Surat yang diajukan ke feed publik atau otomatis ditahan karena laporan.</p>
                    </div>
                    <span class="badge-pill bg-slate-100 text-slate-700">{{ $pendingLetters->total() }} surat</span>
                </div>

                @if ($pendingLetters->isEmpty())
                    <div class="p-8 text-center text-xs text-[#617586] bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        Tidak ada surat dalam antrean tinjauan saat ini.
                    </div>
                @else
                    <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                        @foreach ($pendingLetters as $letter)
                            <div class="p-5 hover:bg-gray-50/50 transition flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                                <div class="space-y-2 flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-letter-status :letter="$letter" />
                                        <x-link-status :letter="$letter" />
                                        <span class="text-xs text-[#617586]">Dibuat: {{ $letter->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    </div>

                                    <p class="text-sm text-[#172B3A] whitespace-pre-wrap bg-gray-50 p-3 rounded-lg border border-gray-100">{{ Str::limit(trim($letter->body), 400) }}</p>

                                    <div class="flex flex-wrap items-center gap-3 text-xs text-[#617586]">
                                        <span>Target: <strong>{{ $letter->target_label ?? 'Tanpa label' }}</strong></span>
                                        <span>Dibuka: <strong>{{ $letter->view_count }}x</strong></span>
                                        @if ($letter->expires_at)
                                            <span>Kedaluwarsa: <strong>{{ $letter->expires_at->translatedFormat('d M Y') }}</strong></span>
                                        @endif
                                        @if ($letter->music_url)
                                            <a href="{{ $letter->music_url }}" target="_blank" rel="noopener noreferrer" class="text-[#38A9F5] underline">
                                                Lagu pengiring
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    <form action="{{ route('admin.letters.approve', $letter->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn-success text-xs py-2 px-3">
                                            Setujui Publik
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.letters.reject', $letter->id) }}" method="POST" onsubmit="return confirm('Tolak pengajuan publik surat ini? Surat tetap dapat diakses lewat tautan rahasia.');">
                                        @csrf
                                        <button type="submit" class="btn-secondary text-xs py-2 px-3 text-amber-700">
                                            Tolak Publik
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.letters.takedown', $letter->id) }}" method="POST" onsubmit="return confirm('Takedown surat ini? Tautan rahasia akan dicabut dan surat tidak dapat dipublikasikan kembali.');">
                                        @csrf
                                        <button type="submit" class="btn-danger text-xs py-2 px-3">
                                            Takedown
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($pendingLetters->hasPages())
                        <div class="pt-2">{{ $pendingLetters->links() }}</div>
                    @endif
                @endif
            </section>

            <!-- Section 2: Antrean Laporan Komunitas -->
            <section class="bg-white rounded-2xl border border-[#E2EAF0] shadow-sm p-6 sm:p-8 space-y-6" aria-labelledby="reportQueueTitle">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 id="reportQueueTitle" class="text-lg font-bold text-[#172B3A]">Laporan Komunitas Masuk</h2>
                        <p class="text-xs text-[#617586] mt-0.5">Laporan ancaman, pelecehan, doxxing, atau spam dari pengguna.</p>
                    </div>
                    <span class="badge-pill bg-rose-50 text-rose-700">{{ $pendingReports->total() }} laporan</span>
                </div>

                <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100 text-[11px] text-amber-900">
                    Konten otomatis ditahan setelah 3 pelapor berbeda dalam 24 jam. Menutup laporan terakhir akan memulihkan konten
                    secara otomatis jika tidak ada laporan terbuka lain.
                </div>

                @if ($pendingReports->isEmpty())
                    <div class="p-8 text-center text-xs text-[#617586] bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        Tidak ada laporan terbuka saat ini.
                    </div>
                @else
                    @php
                        $categoryLabels = [
                            'harassment' => 'Pelecehan',
                            'threat' => 'Ancaman',
                            'doxxing' => 'Doxxing',
                            'defamation' => 'Fitnah',
                            'spam' => 'Spam',
                            'fraud' => 'Penipuan',
                            'unwanted_sexual' => 'Konten seksual',
                            'other' => 'Lainnya',
                        ];
                    @endphp

                    <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                        @foreach ($pendingReports as $report)
                            <div class="p-5 hover:bg-gray-50/50 transition flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                                <div class="space-y-2 flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="badge-pill bg-rose-50 text-rose-800 uppercase">
                                            {{ $categoryLabels[$report->category] ?? $report->category }}
                                        </span>
                                        <span class="text-xs font-semibold text-[#172B3A]">
                                            Tipe: {{ $report->content_type === 'letter' ? 'Surat' : 'Balasan' }} #{{ $report->content_id }}
                                        </span>
                                        <span class="text-xs text-[#617586]">&bull; {{ $report->created_at->diffForHumans() }}</span>
                                    </div>

                                    @if ($report->details)
                                        <p class="text-xs text-gray-700 bg-amber-50/50 border border-amber-100 p-2.5 rounded-lg whitespace-pre-wrap"><strong>Keterangan pelapor:</strong> &ldquo;{{ Str::limit(trim($report->details), 600) }}&rdquo;</p>
                                    @endif

                                    @if ($report->content_type === 'letter' && $report->letter)
                                        <div class="text-xs text-[#172B3A] bg-gray-50 p-3 rounded-lg border border-gray-100 whitespace-pre-wrap"><strong>Konten Surat:</strong> {{ Str::limit(trim($report->letter->body), 500) }}</div>
                                    @elseif ($report->content_type === 'reply' && $report->reply)
                                        <div class="text-xs text-[#172B3A] bg-gray-50 p-3 rounded-lg border border-gray-100 whitespace-pre-wrap"><strong>Konten Balasan:</strong> {{ Str::limit(trim($report->reply->body), 500) }}</div>
                                    @else
                                        <div class="text-xs text-gray-500 italic bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            Konten sudah tidak tersedia (mungkin telah dihapus atau kedaluwarsa).
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    <form action="{{ route('admin.reports.resolve', $report->id) }}" method="POST" onsubmit="return confirm('Abaikan laporan ini? Jika tidak ada laporan terbuka lain, konten akan dipulihkan otomatis.');">
                                        @csrf
                                        <input type="hidden" name="action" value="dismiss">
                                        <button type="submit" class="btn-secondary text-xs py-2 px-3">
                                            Abaikan (Dismiss)
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.reports.resolve', $report->id) }}" method="POST" onsubmit="return confirm('Tindaki laporan ini dengan takedown konten?');">
                                        @csrf
                                        <input type="hidden" name="action" value="takedown">
                                        <button type="submit" class="btn-danger text-xs py-2 px-3">
                                            Takedown Konten
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($pendingReports->hasPages())
                        <div class="pt-2">{{ $pendingReports->links() }}</div>
                    @endif
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
