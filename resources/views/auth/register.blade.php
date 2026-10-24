<x-guest-layout :title="'Daftar'">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-[#172B3A]">Buat akun SuratAnonim</h1>
        <p class="text-sm text-[#617586] mt-1">Akun diperlukan untuk menulis surat dan membalas. Identitasmu tidak ditampilkan kepada penerima.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{ showPassword: false, submitting: false }" @submit="submitting = true">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="'Nama panggilan'" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nama yang kamu gunakan" />
            <p class="text-[11px] text-[#617586] mt-1">Nama ini hanya untuk akunmu dan tidak pernah ditampilkan ke lawan bicara.</p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="'Kata sandi'" />
            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-24"
                                type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                name="password"
                                required autocomplete="new-password" placeholder="Minimal 8 karakter" />
                <button type="button"
                        @click="showPassword = ! showPassword"
                        class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-[#617586] hover:text-[#172B3A]"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        x-text="showPassword ? 'Sembunyikan' : 'Tampilkan'">
                    Tampilkan
                </button>
            </div>
            <p class="text-[11px] text-[#617586] mt-1">Gunakan minimal 8 karakter, sebaiknya kombinasi huruf dan angka.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="'Ulangi kata sandi'" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" placeholder="Tulis ulang kata sandi" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <button type="submit" class="btn-sky w-full py-3" :disabled="submitting">
                <span x-show="! submitting">Daftar &amp; Masuk</span>
                <span x-show="submitting" x-cloak>Memproses...</span>
            </button>
        </div>

        <p class="mt-5 text-center text-sm text-[#617586]">
            Sudah punya akun?
            <a href="{{ route('login', request('redirect') ? ['redirect' => request('redirect')] : []) }}" class="font-semibold text-[#126FB3] hover:text-[#168DE0]">
                Masuk di sini
            </a>
        </p>
    </form>
</x-guest-layout>
