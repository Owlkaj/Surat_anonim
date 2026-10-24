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
    protected $description = 'Menguji pengiriman email notifikasi dan memeriksa kesiapan deliverability';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $to = $this->argument('to');
        $from = (string) config('mail.from.address');
        $fromDomain = str_contains($from, '@') ? substr(strrchr($from, '@'), 1) : '';
        $appUrl = (string) config('app.public_url', config('app.url'));
        $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));

        $this->info("Mengirim email uji coba ke: {$to}...");
        $this->line('Mailer    : ' . config('mail.default'));
        $this->line('SMTP host : ' . config('mail.mailers.smtp.host'));
        $this->line('SMTP port : ' . config('mail.mailers.smtp.port'));
        $this->line('Dari      : ' . $from);
        $this->line('Reply-To  : ' . config('mail.reply_to.address'));
        $this->line('URL publik: ' . $appUrl);

        $this->newLine();
        $this->checkDeliverability($appHost, $fromDomain);

        try {
            $secretUrl = url('/buka/token_demo_uji_coba_123');
            Mail::to($to)->send(new LetterNotificationMail($secretUrl, 'Penerima Uji Coba'));
            $this->info("✅ Email berhasil dikirim ke {$to}.");
            $this->line('Periksa folder Inbox dan Spam. Jika masuk Spam, selesaikan checklist di atas.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ GAGAL: ' . $e->getMessage());
            $this->line('');
            $this->warn("Catatan: Jika error BadCredentials (535), Gmail mewajibkan 16-karakter 'Sandi Aplikasi' (App Password), bukan password akun Gmail biasa.");

            return Command::FAILURE;
        }
    }

    /**
     * Tampilkan peringatan konfigurasi yang paling sering menyebabkan spam.
     */
    protected function checkDeliverability(string $appHost, string $fromDomain): void
    {
        $isLocal = in_array($appHost, ['localhost', '127.0.0.1', '::1', ''], true);
        $isTunnel = str_ends_with($appHost, '.trycloudflare.com')
            || str_ends_with($appHost, '.ngrok-free.app')
            || str_ends_with($appHost, '.loca.lt');

        if ($isLocal) {
            $this->warn('⚠️  URL publik masih lokal. Tautan di email tidak dapat dibuka penerima dan merupakan sinyal spam kuat. Set PUBLIC_APP_URL ke domain https.');
        } elseif ($isTunnel) {
            $this->warn('⚠️  URL publik memakai domain tunnel sementara. Domain tunnel sering diblokir filter spam. Gunakan domain sendiri.');
        }

        if ($fromDomain === 'gmail.com' || $fromDomain === 'yahoo.com' || $fromDomain === 'hotmail.com') {
            $this->warn('⚠️  Pengirim memakai domain email gratis. Untuk email otomatis, gunakan domain sendiri (mis. noreply@domainkamu.com) dan penyedia email transaksional.');
        }

        if (config('mail.default') === 'smtp' && empty(config('mail.reply_to.address'))) {
            $this->warn('⚠️  Reply-To belum diatur. Isi MAIL_REPLY_TO_ADDRESS dengan alamat resmi platform.');
        }

        $this->line('Checklist deliverability:');
        $this->line('  1. Domain pengirim memiliki record SPF yang mengizinkan penyedia email.');
        $this->line('  2. DKIM aktif dan domain pada header d= sejalan dengan domain pengirim.');
        $this->line('  3. DMARC dipasang minimal p=none, lalu naikkan ke quarantine/reject.');
        $this->line('  4. Tautan memakai domain https yang sama/terpercaya, bukan localhost/tunnel/shortener.');
        $this->line('  5. Kirim volume kecil dulu (warm-up), pantau bounce dan keluhan spam.');
        $this->newLine();
    }
}
