<nav x-data="{ open: false }" class="bg-white/95 backdrop-blur border-b border-gray-100 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Wordmark Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ Auth::check() ? route('dashboard') : route('home') }}" class="flex items-center gap-2" aria-label="SuratAnonim — beranda">
                        <span class="w-8 h-8 rounded-xl bg-[#38A9F5] flex items-center justify-center text-white font-bold text-lg shadow-sm" aria-hidden="true">S</span>
                        <span class="font-bold text-xl tracking-tight text-[#172B3A]">Surat<span class="text-[#38A9F5]">Anonim</span></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden xl:-my-px xl:ms-8 xl:flex xl:space-x-4 2xl:space-x-6">
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            Ringkasan
                        </x-nav-link>
                        <x-nav-link :href="route('letters.index')" :active="request()->routeIs('letters.index') || request()->routeIs('letters.show') || request()->routeIs('letters.create')">
                            Surat Saya
                        </x-nav-link>
                        <x-nav-link :href="route('replies.index')" :active="request()->routeIs('replies.*')">
                            Inbox Balasan
                        </x-nav-link>
                    @endauth

                    <x-nav-link :href="route('feed.index')" :active="request()->routeIs('feed.*')">
                        Feed Publik
                    </x-nav-link>

                    @auth
                        @if(Auth::user()->isModerator())
                            <x-nav-link :href="route('admin.moderation.index')" :active="request()->routeIs('admin.*')" class="text-amber-700 font-semibold">
                                Moderasi Admin
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="hidden xl:flex xl:items-center xl:gap-4 xl:ms-6">
                @auth
                    <!-- CTA Buat Surat -->
                    <a href="{{ route('letters.create') }}" class="btn-sky text-sm py-2 px-4 shadow-sm whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tulis Surat
                    </a>

                    <!-- User Dropdown -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-200 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition" aria-label="Menu akun {{ Auth::user()->name }}">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-600 text-xs flex items-center justify-center font-bold" aria-hidden="true">
                                        {{ mb_substr(Auth::user()->name, 0, 1) }}
                                    </span>
                                    <span class="max-w-[10rem] truncate">{{ Auth::user()->name }}</span>
                                </div>
                                <svg class="ms-1 fill-current h-4 w-4 text-gray-400" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                Pengaturan Akun
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    Keluar (Logout)
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-600 hover:text-[#38A9F5] transition">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn-sky text-xs py-2 px-4 shadow-sm">
                        Mulai Sekarang
                    </a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center xl:hidden">
                <button type="button"
                        @click="open = ! open"
                        :aria-expanded="open.toString()"
                        aria-controls="mobile-navigation"
                        class="p-2 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none"
                        aria-label="Buka menu navigasi">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div id="mobile-navigation" :class="{'block': open, 'hidden': ! open}" class="hidden xl:hidden border-t border-gray-100 bg-white">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Ringkasan
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('letters.index')" :active="request()->routeIs('letters.index') || request()->routeIs('letters.show')">
                    Surat Saya
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('replies.index')" :active="request()->routeIs('replies.*')">
                    Inbox Balasan
                </x-responsive-nav-link>
            @endauth

            <x-responsive-nav-link :href="route('feed.index')" :active="request()->routeIs('feed.*')">
                Feed Publik
            </x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('letters.create')" :active="request()->routeIs('letters.create')">
                    Tulis Surat
                </x-responsive-nav-link>
                @if(Auth::user()->isModerator())
                    <x-responsive-nav-link :href="route('admin.moderation.index')" :active="request()->routeIs('admin.*')">
                        Moderasi Admin
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        @auth
            <div class="pt-4 pb-4 border-t border-gray-200 px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500 break-all">{{ Auth::user()->email }}</div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        Pengaturan Akun
                    </x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            Keluar (Logout)
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-4 border-t border-gray-200 px-4 space-y-2">
                <a href="{{ route('login') }}" class="block text-center btn-secondary text-xs py-2 w-full">
                    Masuk
                </a>
                <a href="{{ route('register') }}" class="block text-center btn-sky text-xs py-2 w-full">
                    Mulai Sekarang
                </a>
            </div>
        @endauth
    </div>
</nav>
