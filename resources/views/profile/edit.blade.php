<x-app-layout :title="'Pengaturan Akun'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#172B3A]">Pengaturan Akun</h1>
                <p class="text-sm text-[#617586] mt-0.5">Kelola identitas akun dan keamanan kata sandi. Nama serta email tidak ditampilkan kepada lawan bicara.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary text-sm self-start sm:self-auto">
                &larr; Kembali ke Ringkasan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-[#E2EAF0] shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-[#E2EAF0] shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-rose-100 shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
