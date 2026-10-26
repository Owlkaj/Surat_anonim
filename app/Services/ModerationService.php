<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\Reply;
use App\Models\Report;
use Carbon\Carbon;

class ModerationService
{
    /**
     * Common prohibited patterns (threats, doxxing, harassment keywords).
     */
    protected array $highRiskKeywords = [
        'bunuh',
        'mati kau',
        'santet',
        'bom',
        'teror',
        'doxing',
        'perkosa',
        'open bo',
    ];

    /**
     * Inspect text for basic automated moderation rules.
     * Returns ['passed' => bool, 'reason' => ?string, 'flagged' => bool].
     */
    public function inspectText(string $text): array
    {
        $trimmed = trim($text);

        if (mb_strlen($trimmed) < 1) {
            return [
                'passed' => false,
                'flagged' => false,
                'reason' => 'Teks surat tidak boleh kosong.',
            ];
        }

        if (mb_strlen($trimmed) > 5000) {
            return [
                'passed' => false,
                'flagged' => false,
                'reason' => 'Teks surat melebihi batas maksimum 5.000 karakter.',
            ];
        }

        $lower = mb_strtolower($trimmed);

        // Check for direct high risk threats
        foreach ($this->highRiskKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return [
                    'passed' => true,
                    'flagged' => true,
                    'reason' => 'Konten mengandung kata kunci berisiko tinggi dan ditahan untuk tinjauan.',
                ];
            }
        }

        return [
            'passed' => true,
            'flagged' => false,
            'reason' => null,
        ];
    }

    /**
     * Check if a letter has reached the auto-hide threshold (>= 3 independent reports in 24h).
     */
    public function checkReportThreshold(Letter $letter): void
    {
        $since = Carbon::now()->subHours(24);

        $reportCount = $this->countIndependentReports('letter', $letter->id, $since);

        if ($reportCount >= 3 && $letter->moderation_status !== 'under_review') {
            $letter->update([
                'moderation_status' => 'under_review',
            ]);
        }
    }

    /**
     * Check if a reply has reached the auto-hide threshold (>= 3 reports in 24h).
     */
    public function checkReplyReportThreshold(Reply $reply): void
    {
        $since = Carbon::now()->subHours(24);

        $reportCount = $this->countIndependentReports('reply', $reply->id, $since);

        if ($reportCount >= 3 && $reply->moderation_status !== 'under_review') {
            $reply->update([
                'moderation_status' => 'under_review',
            ]);
        }
    }

    /**
     * Pulihkan surat setelah semua laporan terbuka untuk konten tersebut selesai.
     */
    public function restoreLetterIfClear(Letter $letter): bool
    {
        if ($letter->moderation_status !== 'under_review') {
            return false;
        }

        $openReports = Report::where('content_type', 'letter')
            ->where('content_id', $letter->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->exists();

        if ($openReports) {
            return false;
        }

        $letter->update(['moderation_status' => 'approved']);

        return true;
    }

    /**
     * Pulihkan balasan setelah semua laporan terbuka untuk konten tersebut selesai.
     */
    public function restoreReplyIfClear(Reply $reply): bool
    {
        if ($reply->moderation_status !== 'under_review') {
            return false;
        }

        $openReports = Report::where('content_type', 'reply')
            ->where('content_id', $reply->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->exists();

        if ($openReports) {
            return false;
        }

        $reply->update(['moderation_status' => 'approved']);

        return true;
    }

    /**
     * Hitung laporan independen: satu pelapor hanya dihitung sekali per jendela waktu.
     *
     * Hanya laporan dari pengguna TERDAFTAR (reporter_user_id tidak null) yang
     * dapat memicu penyembunyian konten. Laporan tamu tidak dihitung, sehingga
     * laporan anonim tidak dapat digunakan untuk memblokir/menyembunyikan
     * konten siapa pun (H-02).
     */
    protected function countIndependentReports(string $contentType, int $contentId, Carbon $since): int
    {
        return Report::where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->whereIn('status', ['pending', 'under_review'])
            ->whereNotNull('reporter_user_id')
            ->where('created_at', '>=', $since)
            ->distinct('reporter_user_id')
            ->count('reporter_user_id');
    }
}
