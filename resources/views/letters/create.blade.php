<x-app-layout :title="'Buat Surat'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#172B3A]">Buat Surat Anonim</h1>
                <p class="text-sm text-[#617586] mt-0.5">Tulis pesanmu dengan aman. Identitas akun dan emailmu tidak akan ditampilkan kepada penerima.</p>
            </div>
            <a href="{{ route('letters.index') }}" class="btn-secondary text-sm self-start sm:self-auto">
                &larr; Batal &amp; Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('letters.store') }}" method="POST" class="space-y-8" id="letterForm"
                  x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                @if ($errors->any())
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm" role="alert">
                        <p class="font-semibold mb-1">Surat belum bisa dibuat. Periksa bagian berikut:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Bagian A: Isi Surat -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <label for="body" class="block text-base font-bold text-[#172B3A]">
                            1. Isi Surat <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <span id="charCounter" class="text-xs font-semibold text-[#617586]" aria-live="polite">0 / 5.000 karakter</span>
                    </div>

                    <p class="text-xs text-[#617586]">Tulis pesan yang ingin kamu sampaikan. Dilarang mengirim ancaman kekerasan, pelecehan, penipuan, atau menyebarkan data pribadi orang lain.</p>

                    <textarea id="body"
                              name="body"
                              rows="8"
                              required
                              aria-describedby="bodyHelp charCounter"
                              placeholder="Tuliskan kata-kata yang ingin kamu sampaikan di sini..."
                              class="w-full rounded-xl border-[#E2EAF0] focus:border-[#38A9F5] focus:ring focus:ring-[#38A9F5]/20 text-[#172B3A] text-sm p-4 leading-relaxed transition resize-y"
                              maxlength="5000">{{ old('body') }}</textarea>
                    <p id="bodyHelp" class="text-[11px] text-[#617586]">Teks diperlakukan sebagai teks biasa; HTML atau script tidak akan dijalankan.</p>

                    @error('body')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bagian B: Target Penerima -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-5" x-data="{ targetType: '{{ old('target_type', 'manual') }}' }">
                    <div>
                        <label class="block text-base font-bold text-[#172B3A]">
                            2. Pilih Target Penerima <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <p class="text-xs text-[#617586] mt-0.5">Pilih bagaimana surat rahasia ini akan dibagikan kepada penerima.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex flex-col p-4 rounded-xl border cursor-pointer transition"
                               :class="targetType === 'manual' ? 'border-[#38A9F5] bg-blue-50/50' : 'border-[#E2EAF0] hover:bg-gray-50'">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="radio" name="target_type" value="manual" x-model="targetType" class="text-[#38A9F5] focus:ring-[#38A9F5]">
                                <span class="text-sm font-bold text-[#172B3A]">Bagikan Manual</span>
                            </div>
                            <span class="text-xs text-[#617586] leading-relaxed">Dapatkan link rahasia dan kirimkan sendiri ke target secara manual.</span>
                        </label>

                        <label class="flex flex-col p-4 rounded-xl border cursor-pointer transition"
                               :class="targetType === 'email' ? 'border-[#38A9F5] bg-blue-50/50' : 'border-[#E2EAF0] hover:bg-gray-50'">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="radio" name="target_type" value="email" x-model="targetType" class="text-[#38A9F5] focus:ring-[#38A9F5]">
                                <span class="text-sm font-bold text-[#172B3A]">Notifikasi Email</span>
                            </div>
                            <span class="text-xs text-[#617586] leading-relaxed">Kirim notifikasi berisi link rahasia ke email target (isi surat tidak disertakan).</span>
                        </label>
                    </div>

                    @error('target_type')
                        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror

                    <!-- Email Input (Conditional) -->
                    <div x-show="targetType === 'email'" x-cloak x-transition class="pt-3 border-t border-gray-100">
                        <label for="target_email" class="block text-xs font-bold text-[#172B3A] mb-1">
                            Alamat Email Target <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="email"
                               id="target_email"
                               name="target_email"
                               value="{{ old('target_email') }}"
                               placeholder="nama@email.com"
                               autocomplete="off"
                               class="w-full sm:w-80 rounded-xl border-[#E2EAF0] focus:border-[#38A9F5] focus:ring focus:ring-[#38A9F5]/20 text-sm p-3">
                        <p class="text-[11px] text-[#617586] mt-1.5">Email target disimpan sementara secara terenkripsi dan dihapus setelah notifikasi berhasil dikirim.</p>
                        @error('target_email')
                            <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Label target manual (opsional) -->
                    <div x-show="targetType !== 'email'" x-cloak class="pt-3 border-t border-gray-100">
                        <label for="target_label" class="block text-xs font-bold text-[#172B3A] mb-1">
                            Label Target (Opsional)
                        </label>
                        <input type="text"
                               id="target_label"
                               name="target_label"
                               value="{{ old('target_label') }}"
                               maxlength="100"
                               placeholder="mis. @sahabat, rekan kerja, atau kosongkan saja"
                               class="w-full sm:w-80 rounded-xl border-[#E2EAF0] focus:border-[#38A9F5] focus:ring focus:ring-[#38A9F5]/20 text-sm p-3">
                        <p class="text-[11px] text-[#617586] mt-1.5">Label ini hanya catatan pribadimu. Label bukan bukti bahwa akun target telah diverifikasi.</p>
                        @error('target_label')
                            <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Bagian C: Link Musik Opsional -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-4">
                    <div>
                        <label for="music_url" class="block text-base font-bold text-[#172B3A]">
                            3. Lagu Pengiring (Opsional)
                        </label>
                        <p class="text-xs text-[#617586] mt-0.5">Tambahkan tautan lagu untuk memberi nuansa emosional pada suratmu.</p>
                    </div>

                    <input type="url"
                           id="music_url"
                           name="music_url"
                           value="{{ old('music_url') }}"
                           placeholder="https://open.spotify.com/track/... atau https://youtu.be/..."
                           class="w-full rounded-xl border-[#E2EAF0] focus:border-[#38A9F5] focus:ring focus:ring-[#38A9F5]/20 text-sm p-3">

                    <div class="flex items-start gap-2 text-xs text-[#617586]">
                        <svg class="w-4 h-4 text-[#38A9F5] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Hanya mendukung URL <strong>HTTPS</strong> dari <strong>Spotify, YouTube, Apple Music, atau SoundCloud</strong>. Musik diputar otomatis saat surat dibuka; penerima dapat mengatur volume lewat tombol musik yang mengambang.</span>
                    </div>

                    @error('music_url')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bagian D: Visibilitas Surat -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-4" x-data="{ vis: '{{ old('visibility', 'private') }}' }">
                    <div>
                        <label class="block text-base font-bold text-[#172B3A]">
                            4. Visibilitas Surat <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <p class="text-xs text-[#617586] mt-0.5">Tentukan tingkat keterbukaan surat ini.</p>
                    </div>

                    <div class="space-y-3">
                        <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                               :class="vis === 'private' ? 'border-[#38A9F5] bg-blue-50/50' : 'border-[#E2EAF0] hover:bg-gray-50'">
                            <input type="radio" name="visibility" value="private" x-model="vis" class="mt-0.5 text-[#38A9F5] focus:ring-[#38A9F5]">
                            <div>
                                <span class="text-sm font-bold text-[#172B3A] block">Privat (Default)</span>
                                <span class="text-xs text-[#617586] leading-relaxed block mt-0.5">Hanya kamu dan pemegang link rahasia yang dapat mengakses. Surat tidak akan pernah tampil di feed publik.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                               :class="vis === 'public_pending' ? 'border-[#38A9F5] bg-blue-50/50' : 'border-[#E2EAF0] hover:bg-gray-50'">
                            <input type="radio" name="visibility" value="public_pending" x-model="vis" class="mt-0.5 text-[#38A9F5] focus:ring-[#38A9F5]">
                            <div>
                                <span class="text-sm font-bold text-[#172B3A] block">Ajukan ke Feed Publik</span>
                                <span class="text-xs text-[#617586] leading-relaxed block mt-0.5">Surat akan ditinjau oleh moderator sebelum dipublikasikan di feed. Jika disetujui, orang lain dapat membaca tanpa melihat identitasmu.</span>
                            </div>
                        </label>
                    </div>

                    @error('visibility')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bagian E: Konfirmasi & Kirim -->
                <div class="bg-gray-50 p-6 rounded-2xl border border-[#E2EAF0] flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-[#617586] leading-relaxed">
                        Dengan membuat surat, kamu menyetujui prinsip privasi dan ketentuan komunitas SuratAnonim.
                        Siapa pun yang memperoleh tautan aktif dapat membaca surat ini.
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto sm:shrink-0">
                        <a href="{{ route('letters.index') }}" class="btn-secondary w-full sm:w-auto text-sm">Batal</a>
                        <button type="submit" class="btn-sky w-full sm:w-auto text-sm px-6" :disabled="submitting">
                            <span x-show="! submitting">Buat Surat &amp; Tautan</span>
                            <span x-show="submitting" x-cloak>Memproses...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Character counter script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const textarea = document.getElementById('body');
            const counter = document.getElementById('charCounter');
            if (textarea && counter) {
                const updateCounter = () => {
                    const len = textarea.value.length;
                    counter.textContent = `${len.toLocaleString('id-ID')} / 5.000 karakter`;
                    counter.classList.toggle('text-amber-600', len > 4800);
                };
                textarea.addEventListener('input', updateCounter);
                updateCounter();
            }
        });
    </script>
</x-app-layout>
