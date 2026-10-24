<?php

namespace App\Console\Commands;

use App\Services\RetentionService;
use Illuminate\Console\Command;

class PurgeExpiredData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'letters:purge-expired {--alias=surat:purge-expired}';
    protected $aliases = ['surat:purge-expired'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menjalankan pembersihan retensi data kadaluwarsa sesuai PRD';

    /**
     * Execute the console command.
     */
    public function handle(RetentionService $retentionService): int
    {
        $this->info('Memulai pembersihan retensi data...');
        $results = $retentionService->runRetentionCleanup();

        $this->info("Surat kedaluwarsa dinonaktifkan: {$results['expired_letters']}");
        $this->info("Balasan kedaluwarsa dibersihkan: {$results['expired_replies']}");
        $this->info("Kontak delivery job dibersihkan: {$results['purged_jobs']}");
        $this->info("Laporan usang dibersihkan: {$results['purged_reports']}");
        $this->info("Audit log usang dibersihkan: {$results['purged_audits']}");

        $this->info('Pembersihan data retensi selesai dengan sukses.');
        return Command::SUCCESS;
    }
}
