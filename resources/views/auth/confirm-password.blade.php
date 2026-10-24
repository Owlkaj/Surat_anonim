<x-guest-layout :title="'Konfirmasi Kata Sandi'">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-[#172B3A]">Konfirmasi kata sandi</h1>
        <p class="text-sm text-[#617586] mt-1">Ini area yang dilindungi. Masukkan kata sandi untuk melanjutkan.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" x-data="{ showPassword: false }">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="'Kata sandi'" />

            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-24"
                                type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                name="password"
                                required autocomplete="current-password" />
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

        <div class="mt-6">
            <button type="submit" class="btn-sky w-full py-3">Konfirmasi</button>
        </div>
    </form>
</x-guest-layout>
