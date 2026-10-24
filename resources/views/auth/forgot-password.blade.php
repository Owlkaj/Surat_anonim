<x-guest-layout :title="'Lupa Kata Sandi'">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-[#172B3A]">Lupa kata sandi?</h1>
        <p class="text-sm text-[#617586] mt-1">Masukkan email akunmu. Kami akan mengirim tautan untuk membuat kata sandi baru.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6">
            <button type="submit" class="btn-sky w-full py-3">
                Kirim tautan reset
            </button>
        </div>

        <p class="mt-5 text-center text-sm text-[#617586]">
            Ingat kata sandimu?
            <a href="{{ route('login') }}" class="font-semibold text-[#126FB3] hover:text-[#168DE0]">Kembali masuk</a>
        </p>
    </form>
</x-guest-layout>
