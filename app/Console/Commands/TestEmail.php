<?php

namespace App\Console\Commands;

use App\Mail\LetterNotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surat:test-email {to : Alamat email penerima pengujian}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menguji pengiriman email notifikasi via konfigurasi SMTP saat ini';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $to = $this->argument('to');
        $this->info("Mengirim email uji coba ke: {$to}...");
        $this->info("Host: " . config('mail.mailers.smtp.host'));
        $this->info("Port: " . config('mail.mailers.smtp.port'));
        $this->info("Username: " . config('mail.mailers.smtp.username'));

        try {
            $secretUrl = url('/buka/token_demo_uji_coba_123');
            Mail::to($to)->send(new LetterNotificationMail($secretUrl, 'Penerima Uji Coba'));
            $this->info("✅ BERHASIL! Email notifikasi berhasil dikirim ke {$to}.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ GAGAL: " . $e->getMessage());
            $this->line("");
            $this->warn("Catatan: Jika error BadCredentials (535), Gmail mewajibkan 16-karakter 'Sandi Aplikasi' (App Password), bukan password akun Gmail biasa.");
            return Command::FAILURE;
        }
    }
}
