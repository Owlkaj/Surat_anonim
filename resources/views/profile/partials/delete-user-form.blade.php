<section class="space-y-4">
    <header>
        <h2 class="text-lg font-bold text-rose-700">
            Hapus Akun
        </h2>

        <p class="mt-1 text-sm text-[#617586]">
            Setelah akun dihapus, seluruh surat, balasan, dan data akun akan dihapus secara permanen.
            Tindakan ini tidak dapat dibatalkan.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-[#172B3A]">
                Yakin ingin menghapus akun?
            </h2>

            <p class="mt-1 text-sm text-[#617586]">
                Semua surat dan balasan yang tersimpan akan dihapus permanen. Masukkan kata sandi untuk mengonfirmasi.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Kata sandi" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Kata sandi"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button>
                    Hapus Akun
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
