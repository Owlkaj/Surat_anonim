<x-guest-layout :title="'Verifikasi Email'">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-[#172B3A]">Verifikasi email</h1>
        <p class="text-sm text-[#617586] mt-1">
            Terima kasih sudah mendaftar. Sebelum mulai, verifikasi email melalui tautan yang baru saja kami kirimkan.
            Jika tidak menerima email, kami dapat mengirim ulang.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm" role="status">
            Tautan verifikasi baru telah dikirim ke alamat email yang kamu daftarkan.
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-sky w-full py-3">
                Kirim ulang email verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary w-full py-2.5">
                Keluar dari akun
            </button>
        </form>
    </div>
</x-guest-layout>
