<x-guest-layout :title="'Masuk'">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-[#172B3A]">Masuk ke akunmu</h1>
        <p class="text-sm text-[#617586] mt-1">Lanjutkan menulis dan membalas surat anonim dengan aman.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false, submitting: false }" @submit="submitting = true">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', request('email'))" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="'Kata sandi'" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-[#126FB3] hover:text-[#168DE0] rounded-md focus:outline-none" href="{{ route('password.request', request('redirect') ? ['redirect' => request('redirect')] : []) }}">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>

            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-24"
                                type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                name="password"
                                required autocomplete="current-password" placeholder="Masukkan kata sandi" />
                <button type="button"
                        @click="showPassword = ! showPassword"
                        class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-[#617586] hover:text-[#172B3A]"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        x-text="showPassword ? 'Sembunyikan' : 'Tampilkan'">
                    Tampilkan
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-[#38A9F5] shadow-sm focus:ring-[#38A9F5]" name="remember">
                <span class="ms-2 text-sm text-gray-600">Ingat saya di perangkat ini</span>
            </label>
        </div>

        <div class="mt-6">
            <button type="submit" class="btn-sky w-full py-3" :disabled="submitting">
                <span x-show="! submitting">Masuk</span>
                <span x-show="submitting" x-cloak>Memproses...</span>
            </button>
        </div>

        <p class="mt-5 text-center text-sm text-[#617586]">
            Belum punya akun?
            <a href="{{ route('register', request('redirect') ? ['redirect' => request('redirect')] : []) }}" class="font-semibold text-[#126FB3] hover:text-[#168DE0]">
                Daftar sekarang
            </a>
        </p>
    </form>
</x-guest-layout>
