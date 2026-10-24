/**
 * Pemutar musik latar SuratAnonim.
 *
 * Tidak ada kotak pemutar besar di dalam surat. Mesin pemutar provider
 * ditempatkan di kontainer tersembunyi, lalu dikendalikan dari tombol
 * mengambang (play/pause + volume).
 *
 * Catatan autoplay: browser modern memblokir audio bersuara sebelum ada
 * interaksi pengguna. Karena itu pemutar mencoba autoplay lebih dulu, lalu
 * melanjutkan pada interaksi pertama jika diblokir.
 */

const YOUTUBE_API = 'https://www.youtube.com/iframe_api';
const SOUNDCLOUD_API = 'https://w.soundcloud.com/player/api.js';
const SPOTIFY_API = 'https://open.spotify.com/embed/iframe-api/v1';
const DEFAULT_VOLUME = 70;

const scriptPromises = new Map();

function loadScriptOnce(src) {
    if (scriptPromises.has(src)) {
        return scriptPromises.get(src);
    }

    const promise = new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);

        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
                return;
            }

            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error(`Gagal memuat ${src}`)), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.addEventListener('load', () => {
            script.dataset.loaded = 'true';
            resolve();
        }, { once: true });
        script.addEventListener('error', () => reject(new Error(`Gagal memuat ${src}`)), { once: true });
        document.head.appendChild(script);
    });

    scriptPromises.set(src, promise);

    return promise;
}

let youtubeApiPromise = null;

function ensureYouTubeApi() {
    if (youtubeApiPromise) {
        return youtubeApiPromise;
    }

    youtubeApiPromise = new Promise((resolve, reject) => {
        window.onYouTubeIframeAPIReady = () => resolve(window.YT);
        loadScriptOnce(YOUTUBE_API).catch(reject);
    });

    return youtubeApiPromise;
}

let spotifyApiPromise = null;

function ensureSpotifyApi() {
    if (spotifyApiPromise) {
        return spotifyApiPromise;
    }

    spotifyApiPromise = new Promise((resolve, reject) => {
        window.onSpotifyIframeApiReady = (api) => resolve(api);
        loadScriptOnce(SPOTIFY_API).catch(reject);
    });

    return spotifyApiPromise;
}

function readStoredVolume(key) {
    try {
        const raw = window.localStorage.getItem(key);

        if (raw === null || raw === '') {
            return DEFAULT_VOLUME;
        }

        const value = Number(raw);

        return Number.isFinite(value) && value >= 0 && value <= 100 ? value : DEFAULT_VOLUME;
    } catch (error) {
        return DEFAULT_VOLUME;
    }
}

function storeVolume(key, value) {
    try {
        window.localStorage.setItem(key, String(value));
    } catch (error) {
        // localStorage bisa diblokir pada mode privat; abaikan.
    }
}

function clampVolume(value) {
    return Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
}

function createYouTubeAdapter(root, engine, data, hooks) {
    let player = null;
    let volume = hooks.volume();
    let muted = false;
    let pendingPlay = false;

    const applyVolume = (nextVolume, isMuted) => {
        volume = clampVolume(nextVolume);
        muted = isMuted;

        if (!player || typeof player.setVolume !== 'function') {
            return;
        }

        player.setVolume(volume);

        if (muted || volume === 0) {
            player.mute();
        } else {
            player.unMute();
        }
    };

    ensureYouTubeApi().then((YT) => {
        const host = document.createElement('div');
        host.className = 'music-dock__engine-host';
        engine.appendChild(host);

        player = new YT.Player(host, {
            videoId: data.videoId,
            width: 200,
            height: 200,
            playerVars: {
                autoplay: data.autoplay ? 1 : 0,
                controls: 0,
                disablekb: 1,
                playsinline: 1,
                rel: 0,
                modestbranding: 1,
            },
            events: {
                onReady: (event) => {
                    applyVolume(volume, muted);

                    if (data.autoplay) {
                        pendingPlay = true;
                        event.target.playVideo();

                        window.setTimeout(() => {
                            if (pendingPlay && typeof event.target.getPlayerState === 'function') {
                                const state = event.target.getPlayerState();

                                if (state !== YT.PlayerState.PLAYING) {
                                    hooks.blocked();
                                }
                            }
                        }, 1800);
                    }

                    hooks.ready();
                },
                onStateChange: (event) => {
                    if (event.data === YT.PlayerState.PLAYING) {
                        pendingPlay = false;
                        hooks.state(true);
                    } else if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) {
                        hooks.state(false);
                    }
                },
                onAutoplayBlocked: () => hooks.blocked(),
                onError: (event) => {
                    // Kode error YouTube membantu diagnosis (150/101 = embed ditolak).
                    console.warn('[SuratAnonim] YouTube player error:', event?.data);
                    hooks.error();
                },
            },
        });
    }).catch(() => {
        hooks.error();
    });

    return {
        play() {
            pendingPlay = true;

            if (player && typeof player.playVideo === 'function') {
                player.playVideo();
                window.setTimeout(() => {
                    if (pendingPlay && typeof player.getPlayerState === 'function') {
                        if (player.getPlayerState() !== 1) {
                            hooks.blocked();
                        }
                    }
                }, 1200);
            }
        },
        pause() {
            pendingPlay = false;

            if (player && typeof player.pauseVideo === 'function') {
                player.pauseVideo();
            }
        },
        toggle() {
            if (player && typeof player.getPlayerState === 'function' && player.getPlayerState() === 1) {
                this.pause();
            } else {
                this.play();
            }
        },
        setVolume(nextVolume) {
            applyVolume(nextVolume, muted);
        },
        setMuted(nextMuted) {
            applyVolume(volume, nextMuted);
        },
        isPlaying() {
            return Boolean(player && typeof player.getPlayerState === 'function' && player.getPlayerState() === 1);
        },
        supportsVolume: true,
    };
}

function createSoundCloudAdapter(root, engine, data, hooks) {
    let widget = null;
    let volume = hooks.volume();
    let muted = false;
    let playing = false;

    const applyVolume = (nextVolume, isMuted) => {
        volume = clampVolume(nextVolume);
        muted = isMuted;

        if (!widget) {
            return;
        }

        widget.setVolume(muted ? 0 : volume);
    };

    const iframe = document.createElement('iframe');
    iframe.width = '100%';
    iframe.height = '166';
    iframe.title = 'Pemutar SoundCloud';
    iframe.allow = 'autoplay';
    iframe.loading = 'lazy';
    iframe.src = data.embedUrl;
    engine.appendChild(iframe);

    loadScriptOnce(SOUNDCLOUD_API).then(() => {
        if (!window.SC || !window.SC.Widget) {
            hooks.error();
            return;
        }

        widget = window.SC.Widget(iframe);

        widget.bind(window.SC.Widget.Events.READY, () => {
            applyVolume(volume, muted);
            hooks.ready();

            if (data.autoplay) {
                widget.play();
            }
        });

        widget.bind(window.SC.Widget.Events.PLAY, () => {
            playing = true;
            hooks.state(true);
        });
        widget.bind(window.SC.Widget.Events.PAUSE, () => {
            playing = false;
            hooks.state(false);
        });
        widget.bind(window.SC.Widget.Events.FINISH, () => {
            playing = false;
            hooks.state(false);
        });
        widget.bind(window.SC.Widget.Events.ERROR, () => hooks.error());
    }).catch(() => {
        hooks.error();
    });

    return {
        play() {
            if (widget) {
                widget.play();
            } else {
                hooks.blocked();
            }
        },
        pause() {
            if (widget) {
                widget.pause();
            }
        },
        toggle() {
            if (this.isPlaying()) {
                this.pause();
            } else {
                this.play();
            }
        },
        setVolume(nextVolume) {
            applyVolume(nextVolume, muted);
        },
        setMuted(nextMuted) {
            applyVolume(volume, nextMuted);
        },
        isPlaying() {
            return playing;
        },
        supportsVolume: true,
    };
}

function createSpotifyAdapter(root, engine, data, hooks) {
    let controller = null;
    let isPlaying = false;

    const host = document.createElement('div');
    host.className = 'music-dock__engine-host';
    engine.appendChild(host);

    ensureSpotifyApi().then((api) => {
        api.createController(host, {
            uri: data.uri,
            width: '100%',
            height: 152,
        }, (embedController) => {
            controller = embedController;

            controller.addListener('playback_update', (event) => {
                isPlaying = !event.data.isPaused;
                hooks.state(isPlaying);
            });

            hooks.ready();

            if (data.autoplay) {
                controller.play();

                window.setTimeout(() => {
                    if (!isPlaying) {
                        hooks.blocked();
                    }
                }, 1800);
            }
        });
    }).catch(() => {
        hooks.error();
    });

    return {
        play() {
            if (controller) {
                controller.play();
            } else {
                hooks.blocked();
            }
        },
        pause() {
            if (controller) {
                controller.pause();
            }
        },
        toggle() {
            if (isPlaying) {
                this.pause();
            } else {
                this.play();
            }
        },
        setVolume() {
            // Spotify tidak menyediakan kontrol volume dari halaman induk.
        },
        setMuted() {
            // Spotify tidak menyediakan kontrol mute dari halaman induk.
        },
        isPlaying() {
            return isPlaying;
        },
        supportsVolume: false,
    };
}

function initMusicPlayer(root) {
    if (root.dataset.musicReady === 'true') {
        return;
    }

    root.dataset.musicReady = 'true';

    const engine = root.querySelector('[data-music-engine]');
    const panel = root.querySelector('[data-music-panel]');
    const toggleButton = root.querySelector('[data-music-toggle]');
    const closeButton = root.querySelector('[data-music-close]');
    const playButton = root.querySelector('[data-music-play]');
    const playLabel = root.querySelector('[data-music-play-label]');
    const muteButton = root.querySelector('[data-music-mute]');
    const volumeInput = root.querySelector('[data-music-volume]');
    const volumeValue = root.querySelector('[data-music-volume-value]');
    const volumeWrap = root.querySelector('[data-music-volume-wrap]');
    const volumeNote = root.querySelector('[data-music-volume-note]');
    const status = root.querySelector('[data-music-status]');

    if (!engine || !panel || !toggleButton || !playButton || !status) {
        return;
    }

    const provider = root.dataset.player || 'link';
    const data = {
        autoplay: root.dataset.autoplay === '1',
        embedUrl: root.dataset.embedUrl || '',
        videoId: root.dataset.youtubeId || '',
        uri: root.dataset.uri || '',
    };

    const storageKey = root.dataset.storageKey || 'suratanonim.music.volume';
    let volume = readStoredVolume(storageKey);
    let muted = false;
    let playing = false;
    let ready = false;
    let panelOpen = false;

    const setStatus = (text) => {
        status.textContent = text;
    };

    const renderVolume = () => {
        if (volumeInput) {
            volumeInput.value = String(volume);
        }

        if (volumeValue) {
            volumeValue.textContent = `${volume}%`;
        }

        if (muteButton) {
            muteButton.setAttribute('aria-pressed', muted ? 'true' : 'false');
            muteButton.setAttribute('aria-label', muted ? 'Aktifkan suara musik' : 'Bisukan musik');
        }

        root.classList.toggle('is-muted', muted);
    };

    const renderPlaying = (nextPlaying) => {
        playing = nextPlaying;
        root.classList.toggle('is-playing', playing);
        playButton.setAttribute('aria-label', playing ? 'Jeda musik' : 'Putar musik');
        playButton.dataset.state = playing ? 'playing' : 'paused';

        if (playLabel) {
            playLabel.textContent = playing ? 'Jeda' : 'Putar';
        }
    };

    const hooks = {
        volume: () => volume,
        ready: () => {
            ready = true;
            root.classList.add('is-ready');

            if (data.autoplay && !playing) {
                setStatus('Mencoba memutar musik…');
            }
        },
        state: (isPlaying) => {
            renderPlaying(isPlaying);

            if (isPlaying) {
                setStatus('Musik sedang diputar');
                root.classList.remove('needs-gesture');
            } else if (ready) {
                setStatus('Musik dijeda');
            }
        },
        blocked: () => {
            if (root.classList.contains('has-error')) {
                return;
            }

            root.classList.add('needs-gesture');
            setStatus('Autoplay diblokir browser. Ketuk di mana saja atau tombol putar.');
        },
        error: () => {
            root.classList.remove('needs-gesture');
            root.classList.add('has-error');
            setStatus('Musik tidak dapat diputar. Buka tautan lagu langsung.');
        },
    };

    let adapter = null;

    if (provider === 'youtube' && data.videoId) {
        adapter = createYouTubeAdapter(root, engine, data, hooks);
    } else if (provider === 'soundcloud' && data.embedUrl) {
        adapter = createSoundCloudAdapter(root, engine, data, hooks);
    } else if (provider === 'spotify' && data.uri) {
        adapter = createSpotifyAdapter(root, engine, data, hooks);
    }

    if (!adapter) {
        hooks.ready();
        root.classList.add('is-external-only');
        setStatus('Provider ini tidak mendukung pemutaran otomatis. Gunakan tautan lagu.');
    }

    if (adapter && !adapter.supportsVolume) {
        if (volumeWrap) {
            volumeWrap.hidden = true;
        }

        if (volumeNote) {
            volumeNote.hidden = false;
        }

        root.classList.add('is-volume-unsupported');
    }

    renderVolume();
    renderPlaying(false);

    const openPanel = () => {
        panelOpen = true;
        panel.hidden = false;
        toggleButton.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');

        const firstControl = (volumeWrap && !volumeWrap.hidden) ? volumeInput : playButton;

        if (firstControl) {
            firstControl.focus({ preventScroll: true });
        }
    };

    const closePanel = (returnFocus = true) => {
        panelOpen = false;
        panel.hidden = true;
        toggleButton.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');

        if (returnFocus) {
            toggleButton.focus({ preventScroll: true });
        }
    };

    toggleButton.addEventListener('click', () => {
        if (panelOpen) {
            closePanel();
        } else {
            openPanel();
        }
    });

    if (closeButton) {
        closeButton.addEventListener('click', () => closePanel());
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panelOpen) {
            closePanel();
        }
    });

    document.addEventListener('pointerdown', (event) => {
        if (panelOpen && !root.contains(event.target)) {
            closePanel(false);
        }
    });

    playButton.addEventListener('click', () => {
        if (!adapter) {
            return;
        }

        if (playing) {
            adapter.pause();
            renderPlaying(false);
            setStatus('Musik dijeda');
        } else {
            adapter.play();
        }
    });

    if (muteButton && adapter && adapter.supportsVolume) {
        muteButton.addEventListener('click', () => {
            muted = !muted;
            adapter.setMuted(muted);
            renderVolume();
        });
    }

    if (volumeInput && adapter && adapter.supportsVolume) {
        const onVolumeInput = () => {
            volume = clampVolume(volumeInput.value);

            if (volume > 0 && muted) {
                muted = false;
            }

            adapter.setVolume(volume);
            storeVolume(storageKey, volume);
            renderVolume();
        };

        volumeInput.addEventListener('input', onVolumeInput);
        volumeInput.addEventListener('change', onVolumeInput);
    }

    // Fallback autoplay: mulai pada interaksi pertama di luar panel pemutar.
    if (data.autoplay && adapter) {
        const events = [
            ['pointerdown', { passive: true }],
            ['keydown', { passive: true }],
            ['touchstart', { passive: true }],
        ];

        const cleanup = () => {
            events.forEach(([type]) => {
                document.removeEventListener(type, startOnInteraction);
            });
        };

        function startOnInteraction(event) {
            if (root.contains(event.target)) {
                return;
            }

            cleanup();

            if (!playing) {
                adapter.play();
            }
        }

        events.forEach(([type, options]) => {
            document.addEventListener(type, startOnInteraction, options);
        });
    }
}

function boot() {
    document.querySelectorAll('[data-music-player]').forEach(initMusicPlayer);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
