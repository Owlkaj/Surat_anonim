<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DeliveryJob;
use App\Models\Letter;
use App\Models\Reply;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RetentionService
{
    /**
     * Purge or anonymize expired data conforming to PRD 10.2.
     */
    public function runRetentionCleanup(): array
    {
        $now = Carbon::now();
        $results = [
            'expired_letters' => 0,
            'expired_replies' => 0,
            'purged_jobs' => 0,
            'purged_reports' => 0,
            'purged_audits' => 0,
        ];

        // 1. Expire letters past their expires_at date
        $results['expired_letters'] = Letter::where('expires_at', '<', $now)
            ->whereNull('deleted_at')
            ->update([
                'revoked_at' => $now,
                'body' => null,
                'music_url' => null,
                'target_email_encrypted' => null, // Wipe encrypted contact
                'deleted_at' => $now,
            ]);

        // 2. Hapus isi balasan yang sudah melewati masa retensi.
        //    Balasan tetap ditandai terhapus agar thread tidak kehilangan konteks.
        $results['expired_replies'] = Reply::where(function ($query) use ($now) {
            $query->where('purge_at', '<', $now)
                ->orWhereHas('thread', function ($threadQuery) use ($now) {
                    $threadQuery->where('expires_at', '<', $now);
                });
        })
            ->whereNull('deleted_at')
            ->update([
                'body' => '',
                'deleted_at' => $now,
            ]);

        // 3. Clear sensitive destination fields in delivery jobs after their purge date.
        $results['purged_jobs'] = DeliveryJob::where(function ($query) use ($now) {
            $query->where('purge_at', '<', $now)
                ->orWhere('created_at', '<', $now->copy()->subDays(7));
        })
            ->whereNotNull('destination_encrypted')
            ->update([
                'destination_encrypted' => null,
            ]);

        // 4. Purge reports after their retention date.
        $results['purged_reports'] = Report::where('purge_at', '<', $now)->delete();

        // 5. Purge audit logs after their retention date.
        $results['purged_audits'] = AuditLog::where('purge_at', '<', $now)->delete();

        Log::info('Data retention cleanup executed', $results);

        return $results;
    }
}
