<x-app-layout :title="'Ringkasan'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#172B3A]">Ringkasan</h1>
                <p class="text-sm text-[#617586] mt-0.5">Ada kata yang ingin kamu sampaikan hari ini?</p>
            </div>
            <a href="{{ route('letters.create') }}" class="btn-sky self-start sm:self-auto text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tulis Surat Baru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- 3 Stat Cards (PRD & UI.md 6.3) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-6 rounded-2xl border border-[#E2EAF0] shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#617586]">Total Surat</p>
                        <p class="text-3xl font-extrabold text-[#172B3A] mt-1">{{ $totalLetters }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#38A9F5] flex items-center justify-center" aria-hidden="true">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-[#E2EAF0] shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#617586]">Sudah Dibuka</p>
                        <p class="text-3xl font-extrabold text-emerald-600 mt-1">{{ $openedLetters }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center" aria-hidden="true">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-[#E2EAF0] shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#617586]">Balasan Masuk</p>
                        <p class="text-3xl font-extrabold text-[#38A9F5] mt-1">{{ $totalReplies }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-[#38A9F5] flex items-center justify-center" aria-hidden="true">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Main Layout: Canvas + Companion Panel -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

                <!-- Left 2 Cols: Canvas "Surat-suratmu" (Hybrid Stacked Cards) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-[#172B3A]">Surat-suratmu</h2>
                            <p class="text-xs text-[#617586]">Tumpukan kartu surat fisik. Geser kartu bebas atau atur ulang susunan.</p>
                        </div>
                        @if ($canvasLetters->isNotEmpty())
                            <button id="btnResetCanvas" type="button" class="btn-secondary text-xs py-1.5 px-3 self-start sm:self-auto">
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Atur Ulang
                            </button>
                        @endif
                    </div>

                    <p id="canvasHelp" class="sr-only">
                        Kartu surat dapat dipindahkan dengan mouse atau sentuhan pada layar lebar.
                        Fokuskan kartu lalu gunakan tombol panah untuk memindahkannya, dan tombol Atur Ulang untuk mengembalikan susunan.
                    </p>

                    <!-- Canvas Container -->
                    <div id="letterCanvas" class="letter-canvas relative shadow-inner">
                        @if ($canvasLetters->isEmpty())
                            <div class="letter-canvas-empty absolute inset-0 flex flex-col items-center justify-center text-center p-6">
                                <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#38A9F5] flex items-center justify-center mb-4" aria-hidden="true">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <h3 class="text-base font-semibold text-[#172B3A]">Belum Ada Surat</h3>
                                <p class="text-xs text-[#617586] max-w-sm mt-1">Kamu belum menulis surat apapun. Tulis surat pertamamu sekarang dan bagikan link rahasianya.</p>
                                <a href="{{ route('letters.create') }}" class="btn-sky text-xs mt-4">Mulai Menulis</a>
                            </div>
                        @else
                            @php
                                // Staggered initial placements
                                $positions = [
                                    ['x' => 30, 'y' => 30, 'rot' => -4],
                                    ['x' => 180, 'y' => 50, 'rot' => 3],
                                    ['x' => 90, 'y' => 150, 'rot' => -2],
                                    ['x' => 260, 'y' => 120, 'rot' => 5],
                                    ['x' => 50, 'y' => 240, 'rot' => 2],
                                    ['x' => 220, 'y' => 230, 'rot' => -3],
                                    ['x' => 340, 'y' => 60, 'rot' => -5],
                                    ['x' => 320, 'y' => 200, 'rot' => 4],
                                ];
                            @endphp

                            @foreach ($canvasLetters as $idx => $item)
                                @php
                                    $pos = $positions[$idx % count($positions)];
                                @endphp

                                <div class="letter-card-stacked"
                                     data-index="{{ $idx }}"
                                     data-initial-x="{{ $pos['x'] }}"
                                     data-initial-y="{{ $pos['y'] }}"
                                     data-initial-rot="{{ $pos['rot'] }}"
                                     tabindex="0"
                                     role="group"
                                     aria-describedby="canvasHelp"
                                     aria-label="Surat {{ $idx + 1 }}, dibuat {{ $item->created_at->translatedFormat('d M Y') }}"
                                     style="left: {{ $pos['x'] }}px; top: {{ $pos['y'] }}px; transform: rotate({{ $pos['rot'] }}deg); z-index: {{ $idx + 1 }};">

                                    <div class="flex items-center justify-between mb-3">
                                        <x-letter-status :letter="$item" class="text-[11px]" />
                                        <span class="text-[11px] text-[#617586]">
                                            {{ $item->created_at->translatedFormat('d M') }}
                                        </span>
                                    </div>

                                    <p class="text-sm font-medium text-[#172B3A] line-clamp-3 mb-4 leading-relaxed">
                                        &ldquo;{{ Str::limit($item->body, 90) }}&rdquo;
                                    </p>

                                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-xs">
                                        <span class="text-[#617586] flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            {{ $item->view_count }}x
                                        </span>
                                        <a href="{{ route('letters.show', $item->id) }}" class="card-action-btn text-[#38A9F5] hover:text-[#168DE0] font-semibold inline-flex items-center gap-0.5">
                                            Buka Detail &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Right Col: Companion Panel (PRD & UI.md 6.3) -->
                <div class="space-y-6">
                    <!-- Mulai Menulis Panel -->
                    <div class="bg-gradient-to-br from-blue-50 to-sky-50/40 p-6 rounded-2xl border border-blue-100/60 shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-[#38A9F5] text-white flex items-center justify-center mb-3" aria-hidden="true">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </div>
                        <h3 class="font-bold text-base text-[#172B3A]">Mulai Menulis</h3>
                        <p class="text-xs text-[#617586] mt-1 leading-relaxed">Sampaikan pesan personal tanpa membuka identitas akun atau email kepada penerima.</p>
                        <a href="{{ route('letters.create') }}" class="btn-sky w-full mt-4 text-xs font-semibold py-2.5">
                            Buat Surat Sekarang
                        </a>
                    </div>

                    <!-- Catatan Privasi Panel (UI.md 6.3 & 12) -->
                    <div class="bg-white p-5 rounded-2xl border border-[#E2EAF0] shadow-sm text-xs space-y-3">
                        <div class="flex items-center gap-2 font-bold text-[#172B3A]">
                            <svg class="w-4 h-4 text-[#38A9F5]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Prinsip Keamanan &amp; Privasi</span>
                        </div>
                        <p class="text-[#617586] leading-relaxed">
                            Siapa pun yang memiliki tautan rahasia aktif dapat membaca surat. Penerima tidak perlu akun untuk membaca, namun wajib login untuk membalas.
                        </p>
                        <p class="text-[#617586] leading-relaxed">
                            Anonimitas berlaku terhadap lawan bicara. Sistem tetap menyimpan log minimum untuk keamanan dan penanganan laporan pelecehan/spam.
                        </p>
                    </div>

                    <!-- Navigasi Cepat -->
                    <div class="bg-white p-5 rounded-2xl border border-[#E2EAF0] shadow-sm space-y-2 text-xs">
                        <div class="font-bold text-[#172B3A] mb-2">Akses Cepat</div>
                        <a href="{{ route('letters.index') }}" class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 text-[#172B3A]">
                            <span>Daftar Seluruh Surat</span>
                            <span class="text-gray-400" aria-hidden="true">&rarr;</span>
                        </a>
                        <a href="{{ route('replies.index') }}" class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 text-[#172B3A]">
                            <span>Kotak Masuk Balasan</span>
                            <span class="text-gray-400" aria-hidden="true">&rarr;</span>
                        </a>
                        <a href="{{ route('feed.index') }}" class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 text-[#172B3A]">
                            <span>Jelajahi Feed Publik</span>
                            <span class="text-gray-400" aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Hybrid Canvas Free-Drag 2D Script (UI.md section 6.3 & 9) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('letterCanvas');
            const cards = Array.from(document.querySelectorAll('.letter-card-stacked'));
            const resetBtn = document.getElementById('btnResetCanvas');
            const desktopQuery = window.matchMedia('(min-width: 640px)');
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!canvas || cards.length === 0) {
                if (resetBtn) resetBtn.disabled = true;
                return;
            }

            let highestZ = cards.reduce((max, card) => Math.max(max, parseInt(card.style.zIndex || '0', 10)), 0);

            const dragState = new WeakMap();
            const interactiveSelector = 'a, button, input, textarea, select, [role="button"]';

            const clampCard = (card, left, top) => {
                const pad = 12;
                const maxLeft = Math.max(pad, canvas.clientWidth - card.offsetWidth - pad);
                const maxTop = Math.max(pad, canvas.clientHeight - card.offsetHeight - pad);

                return {
                    left: Math.min(Math.max(left, pad), maxLeft),
                    top: Math.min(Math.max(top, pad), maxTop),
                };
            };

            const initialPosition = (card) => ({
                left: parseFloat(card.getAttribute('data-initial-x')) || 12,
                top: parseFloat(card.getAttribute('data-initial-y')) || 12,
                rotation: parseFloat(card.getAttribute('data-initial-rot')) || 0,
            });

            const applyRotation = (card) => {
                const rotation = initialPosition(card).rotation;
                card.style.transform = reduceMotion ? 'none' : `rotate(${rotation}deg)`;
            };

            const keepCardsInBounds = () => {
                if (!desktopQuery.matches) return;

                cards.forEach((card) => {
                    const currentLeft = parseFloat(card.style.left) || initialPosition(card).left;
                    const currentTop = parseFloat(card.style.top) || initialPosition(card).top;
                    const position = clampCard(card, currentLeft, currentTop);

                    card.style.left = `${position.left}px`;
                    card.style.top = `${position.top}px`;
                });
            };

            const resetCanvas = () => {
                cards.forEach((card, index) => {
                    const position = initialPosition(card);
                    const clamped = desktopQuery.matches
                        ? clampCard(card, position.left, position.top)
                        : { left: position.left, top: position.top };

                    card.style.left = `${clamped.left}px`;
                    card.style.top = `${clamped.top}px`;
                    card.style.zIndex = String(index + 1);
                    applyRotation(card);
                    card.classList.remove('dragging');
                    delete card.dataset.suppressClick;
                });

                highestZ = cards.length;
            };

            cards.forEach((card) => {
                card.addEventListener('click', (event) => {
                    if (card.dataset.suppressClick === '1') {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                }, true);

                card.addEventListener('pointerdown', (event) => {
                    if (!desktopQuery.matches || event.button !== 0) return;
                    if (event.target.closest(interactiveSelector)) return;

                    const position = initialPosition(card);
                    const currentLeft = parseFloat(card.style.left);
                    const currentTop = parseFloat(card.style.top);

                    dragState.set(card, {
                        pointerId: event.pointerId,
                        startX: event.clientX,
                        startY: event.clientY,
                        initialLeft: Number.isFinite(currentLeft) ? currentLeft : position.left,
                        initialTop: Number.isFinite(currentTop) ? currentTop : position.top,
                        moved: false,
                    });

                    // Pointer capture memastikan gerakan cepat tetap diterima.
                    try {
                        card.setPointerCapture(event.pointerId);
                    } catch (error) {
                        // Browser lama dapat menolak pointer capture; drag tetap berjalan.
                    }
                });

                card.addEventListener('pointermove', (event) => {
                    const state = dragState.get(card);
                    if (!state || state.pointerId !== event.pointerId) return;

                    const dx = event.clientX - state.startX;
                    const dy = event.clientY - state.startY;

                    if (!state.moved && Math.abs(dx) < 4 && Math.abs(dy) < 4) {
                        return;
                    }

                    if (!state.moved) {
                        state.moved = true;
                        highestZ += 1;
                        card.style.zIndex = String(highestZ);
                        card.classList.add('dragging');
                        card.style.transform = 'scale(1.03)';
                        document.body.classList.add('select-none');
                    }

                    event.preventDefault();

                    const position = clampCard(card, state.initialLeft + dx, state.initialTop + dy);
                    card.style.left = `${position.left}px`;
                    card.style.top = `${position.top}px`;
                });

                const endDrag = (event) => {
                    const state = dragState.get(card);
                    if (!state || state.pointerId !== event.pointerId) return;

                    const moved = state.moved;
                    dragState.delete(card);
                    card.classList.remove('dragging');
                    document.body.classList.remove('select-none');
                    applyRotation(card);

                    try {
                        card.releasePointerCapture(event.pointerId);
                    } catch (error) {
                        // Abaikan bila capture sudah dilepas browser.
                    }

                    if (moved) {
                        // Cegah klik setelah drag tanpa menghalangi klik biasa.
                        card.dataset.suppressClick = '1';
                        window.setTimeout(() => delete card.dataset.suppressClick, 250);
                    }
                };

                card.addEventListener('pointerup', endDrag);
                card.addEventListener('pointercancel', endDrag);

                // Alternatif keyboard: tombol panah memindahkan kartu 12px.
                card.addEventListener('keydown', (event) => {
                    if (!desktopQuery.matches) return;
                    if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;

                    event.preventDefault();
                    const step = event.shiftKey ? 24 : 12;
                    const left = parseFloat(card.style.left) || initialPosition(card).left;
                    const top = parseFloat(card.style.top) || initialPosition(card).top;
                    let nextLeft = left;
                    let nextTop = top;

                    if (event.key === 'ArrowLeft') nextLeft -= step;
                    if (event.key === 'ArrowRight') nextLeft += step;
                    if (event.key === 'ArrowUp') nextTop -= step;
                    if (event.key === 'ArrowDown') nextTop += step;

                    const position = clampCard(card, nextLeft, nextTop);
                    card.style.left = `${position.left}px`;
                    card.style.top = `${position.top}px`;
                    highestZ += 1;
                    card.style.zIndex = String(highestZ);
                });
            });

            if (resetBtn) {
                resetBtn.addEventListener('click', resetCanvas);
            }

            // Pastikan kartu tidak tertinggal di luar kanvas saat ukuran berubah.
            keepCardsInBounds();
            window.addEventListener('resize', keepCardsInBounds);
        });
    </script>
</x-app-layout>
