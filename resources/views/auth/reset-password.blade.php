<x-guest-layout :title="'Reset Kata Sandi'">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-[#172B3A]">Buat kata sandi baru</h1>
        <p class="text-sm text-[#617586] mt-1">Pilih kata sandi yang kuat dan belum pernah kamu gunakan di layanan lain.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" x-data="{ showPassword: false }">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="'Kata sandi baru'" />
            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-24" type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
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

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="'Ulangi kata sandi baru'" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" placeholder="Tulis ulang kata sandi baru" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <button type="submit" class="btn-sky w-full py-3">
                Simpan kata sandi baru
            </button>
        </div>
    </form>
</x-guest-layout>
