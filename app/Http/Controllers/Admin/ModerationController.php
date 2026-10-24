<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Letter;
use App\Models\Reply;
use App\Models\Report;
use App\Services\ModerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModerationController extends Controller
{
    public function __construct(
        protected ModerationService $moderationService
    ) {}

    protected function authorizeStaff(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isModerator()) {
            abort(403, 'Akses terbatas untuk admin dan moderator.');
        }
    }

    public function index()
    {
        $this->authorizeStaff();

        $pendingLetters = Letter::where(function ($query) {
            $query->where('visibility', 'public_pending')
                ->orWhere('moderation_status', 'under_review');
        })
            ->whereNotIn('moderation_status', ['takedown', 'rejected'])
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'letters_page');

        $pendingReports = Report::whereIn('status', ['pending', 'under_review'])
            ->with(['letter', 'reply'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'reports_page');

        return view('admin.moderation', compact('pendingLetters', 'pendingReports'));
    }

    public function approveLetter(Letter $letter)
    {
        $this->authorizeStaff();

        if ($letter->revoked_at !== null || ($letter->expires_at && $letter->expires_at->isPast())) {
            return back()->with('error', 'Surat yang telah dicabut atau kedaluwarsa tidak dapat disetujui untuk feed publik.');
        }

        if ($letter->moderation_status === 'takedown') {
            return back()->with('error', 'Surat yang telah ditakedown tidak dapat dipublikasikan kembali.');
        }

        $letter->update([
            'visibility' => 'public',
            'moderation_status' => 'approved',
            'published_at' => Carbon::now(),
        ]);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'approve_public_letter',
            'object_type' => 'letter',
            'object_id' => (string)$letter->id,
            'reason_code' => 'moderator_approved',
            'purge_at' => Carbon::now()->addDays(180),
        ]);

        return back()->with('success', 'Surat berhasil disetujui dan diterbitkan di feed publik.');
    }

    public function rejectLetter(Request $request, Letter $letter)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $letter->update([
            'visibility' => 'private',
            'moderation_status' => 'rejected',
            'published_at' => null,
        ]);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'reject_public_letter',
            'object_type' => 'letter',
            'object_id' => (string)$letter->id,
            'reason_code' => $validated['reason'] ?? 'moderator_rejected',
            'purge_at' => Carbon::now()->addDays(180),
        ]);

        return back()->with('success', 'Pengajuan publikasi ditolak. Surat dikembalikan ke status privat.');
    }

    public function takedownLetter(Request $request, Letter $letter)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $letter->update([
            'moderation_status' => 'takedown',
            'revoked_at' => Carbon::now(),
            'published_at' => null,
        ]);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'takedown_letter',
            'object_type' => 'letter',
            'object_id' => (string)$letter->id,
            'reason_code' => $validated['reason'] ?? 'takedown_violation',
            'purge_at' => Carbon::now()->addDays(180),
        ]);

        return back()->with('success', 'Surat berhasil ditakedown dan akses dicabut.');
    }

    public function resolveReport(Request $request, Report $report)
    {
        $this->authorizeStaff();

        if (!in_array($report->status, ['pending', 'under_review'], true)) {
            return back()->with('info', 'Laporan ini sudah diselesaikan sebelumnya.');
        }

        $validated = $request->validate([
            'action' => ['required', 'in:dismiss,takedown'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $action = $validated['action'];
        $restored = false;

        if ($action === 'takedown') {
            $report->update([
                'status' => 'resolved_takedown',
                'resolved_at' => Carbon::now(),
            ]);

            if ($report->content_type === 'letter') {
                $letter = Letter::find($report->content_id);
                if ($letter) {
                    $letter->update([
                        'moderation_status' => 'takedown',
                        'revoked_at' => Carbon::now(),
                        'published_at' => null,
                    ]);
                }
            } elseif ($report->content_type === 'reply') {
                $reply = Reply::find($report->content_id);
                if ($reply) {
                    $reply->update(['moderation_status' => 'takedown']);
                }
            }
        } else {
            $report->update([
                'status' => 'resolved_dismissed',
                'resolved_at' => Carbon::now(),
            ]);

            // Jika tidak ada laporan terbuka lain, kembalikan konten dari status
            // under_review agar tidak "terkunci" selamanya setelah auto-hide.
            if ($report->content_type === 'letter') {
                $letter = Letter::find($report->content_id);
                $restored = $letter
                    ? $this->moderationService->restoreLetterIfClear($letter)
                    : false;
            } elseif ($report->content_type === 'reply') {
                $reply = Reply::find($report->content_id);
                $restored = $reply
                    ? $this->moderationService->restoreReplyIfClear($reply)
                    : false;
            }
        }

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'resolve_report_'.$action,
            'object_type' => 'report',
            'object_id' => (string)$report->id,
            'reason_code' => $validated['reason'] ?? 'reviewed',
            'metadata' => ['content_restored' => $restored],
            'purge_at' => Carbon::now()->addDays(180),
        ]);

        return back()->with('success', $restored
            ? 'Laporan diselesaikan dan konten dipulihkan karena tidak ada laporan terbuka lain.'
            : 'Laporan berhasil diselesaikan.');
    }
}
