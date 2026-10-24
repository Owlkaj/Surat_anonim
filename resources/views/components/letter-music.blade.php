@props(['mediaInfo'])

@if ($mediaInfo)
    @php
        $musicPanelId = 'music-panel-' . substr(md5($mediaInfo['url']), 0, 8);
    @endphp

    <div class="music-dock"
         data-music-player
         data-player="{{ $mediaInfo['player'] }}"
         data-provider="{{ $mediaInfo['provider'] }}"
         data-embed-url="{{ $mediaInfo['embed_url'] }}"
         data-youtube-id="{{ $mediaInfo['youtube_id'] ?? '' }}"
         data-uri="{{ $mediaInfo['uri'] ?? '' }}"
         data-autoplay="{{ $mediaInfo['autoplay'] ? '1' : '0' }}"
         data-volume-supported="{{ $mediaInfo['volume'] ? '1' : '0' }}"
         data-storage-key="suratanonim.music.volume">

        {{-- Mesin pemutar provider: sengaja tersembunyi agar tidak memakan ruang surat. --}}
        <div class="music-dock__engine" data-music-engine aria-hidden="true"></div>

        <div class="music-dock__panel" id="{{ $musicPanelId }}" data-music-panel hidden>
            <div class="music-dock__head">
                <div>
                    <p class="music-dock__eyebrow">Lagu pengiring</p>
                    <p class="music-dock__provider">{{ $mediaInfo['provider'] }}</p>
                </div>
                <button type="button"
                        class="music-dock__close"
                        data-music-close
                        aria-label="Tutup panel musik">
                    &times;
                </button>
            </div>

            <p class="music-dock__status" data-music-status aria-live="polite">
                Menyiapkan musik&hellip;
            </p>

            <div class="music-dock__volume" data-music-volume-wrap>
                <button type="button"
                        class="music-dock__icon-button"
                        data-music-mute
                        aria-pressed="false"
                        aria-label="Bisukan musik">
                    <svg class="music-dock__speaker-on" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5L6 9H2v6h4l5 4V5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.54 8.46a5 5 0 010 7.07M19.07 4.93a10 10 0 010 14.14"/>
                    </svg>
                    <svg class="music-dock__speaker-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5L6 9H2v6h4l5 4V5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 9l-6 6m0-6l6 6"/>
                    </svg>
                </button>

                <input type="range"
                       min="0"
                       max="100"
                       step="1"
                       value="70"
                       class="music-dock__range"
                       data-music-volume
                       aria-label="Volume musik">

                <span class="music-dock__volume-value" data-music-volume-value>70%</span>
            </div>

            <p class="music-dock__note" data-music-volume-note hidden>
                Volume {{ $mediaInfo['provider'] }} tidak dapat diatur dari halaman ini. Gunakan pemutar {{ $mediaInfo['provider'] }}.
            </p>

            <div class="music-dock__actions">
                <button type="button"
                        class="music-dock__play"
                        data-music-play
                        aria-label="Putar musik">
                    <span data-music-play-label>Putar</span>
                </button>

                <a href="{{ $mediaInfo['url'] }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="music-dock__link">
                    Buka lagu
                </a>
            </div>
        </div>

        <button type="button"
                class="music-dock__button"
                data-music-toggle
                aria-expanded="false"
                aria-controls="{{ $musicPanelId }}"
                aria-label="Kontrol musik latar">
            <span class="music-dock__eq" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </span>
            <span class="sr-only">Kontrol musik latar</span>
        </button>
    </div>
@endif
