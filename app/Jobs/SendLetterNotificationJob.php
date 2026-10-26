<?php

namespace App\Jobs;

use App\Mail\LetterNotificationMail;
use App\Models\DeliveryJob;
use App\Services\PrivacyService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

/**
 * Worker pengiriman notifikasi email surat anonim (PRD FR-08, FR-09, FR-23).
 *
 * - Payload antrean dienkripsi (ShouldBeEncrypted) karena memuat link rahasia;
 *   alamat email tidak pernah masuk payload, hanya ID delivery job.
 * - Idempotent: job yang sudah `sent` / `failed_permanent` tidak dikirim ulang.
 * - Retry terbatas dengan exponential backoff; bounce/penolakan permanen
 *   (SMTP 550-554) langsung ditandai gagal permanen.
 */
class SendLetterNotificationJob implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    /** Batas percobaan (FR-23: tidak ada retry tanpa batas). */
    public int $tries = 5;

    public function __construct(
        public int $deliveryJobId,
        public string $secretUrl,
    ) {}

    /** Exponential backoff dalam detik: 1m, 5m, 15m, 1j. */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(PrivacyService $privacy): void
    {
        $delivery = DeliveryJob::find($this->deliveryJobId);

        if (!$delivery || in_array($delivery->status, ['sent', 'failed_permanent'], true)) {
            return; // Idempotensi: sudah selesai atau tidak ada lagi
        }

        // Klaim job secara atomik agar dua worker tidak mengirim email yang sama.
        $claimed = DeliveryJob::whereKey($delivery->id)
            ->whereIn('status', ['queued', 'failed_retryable'])
            ->update([
                'status' => 'sending',
                'attempt_count' => $delivery->attempt_count + 1,
                'next_attempt_at' => null,
            ]);

        if ($claimed === 0) {
            return;
        }

        $delivery->refresh();

        // Jangan kirim tautan yang sudah tidak berlaku (dicabut/kedaluwarsa/takedown).
        $letter = $delivery->letter;
        if (!$letter || !$letter->isAccessible()) {
            $delivery->update([
                'status' => 'failed_permanent',
                'last_error_code' => 'letter_unavailable',
                'destination_encrypted' => null,
            ]);
            return;
        }

        $email = $privacy->decryptContact($delivery->destination_encrypted);
        if (!$email) {
            $delivery->update([
                'status' => 'failed_permanent',
                'last_error_code' => 'destination_unavailable',
            ]);
            return;
        }

        // Tautan opt-out bertanda tangan per-surat yang dikirim ke email penerima
        // (membuktikan akses mailbox; tidak dapat digenerate oleh pengirim).
        $unsubscribeUrl = URL::temporarySignedRoute(
            'letters.unsubscribe',
            Carbon::now()->addDays(30),
            ['letter' => $letter->id]
        );

        try {
            $sent = Mail::to($email)->send(
                new LetterNotificationMail($this->secretUrl, $letter->target_label ?: 'Penerima', $unsubscribeUrl)
            );
        } catch (UnexpectedResponseException $e) {
            $code = (int) $e->getCode();

            if ($code >= 550 && $code <= 554) {
                // Bounce / mailbox tidak ada: jangan diulang
                $delivery->update([
                    'status' => 'failed_permanent',
                    'last_error_code' => 'smtp_' . $code,
                    'destination_encrypted' => null,
                ]);
                return;
            }

            $this->markRetryable($delivery, 'smtp_' . ($code ?: 'unknown'));
            throw $e;
        } catch (Throwable $e) {
            $this->markRetryable($delivery, 'transport_error');
            throw $e;
        }

        $delivery->update([
            'status' => 'sent',
            'provider_message_id' => $sent?->getMessageId(),
            'last_error_code' => null,
            // Minimasi data: kontak polos tidak dibutuhkan lagi setelah terkirim
            'destination_encrypted' => null,
        ]);

        // Minimasi data: simpanan email terenkripsi di surat juga tidak diperlukan
        // setelah notifikasi berhasil dikirim.
        $letter->update(['target_email_encrypted' => null]);
    }

    /**
     * Dipanggil Laravel setelah seluruh percobaan habis.
     */
    public function failed(?Throwable $e): void
    {
        DeliveryJob::whereKey($this->deliveryJobId)
            ->whereNotIn('status', ['sent'])
            ->update(['status' => 'failed_permanent']);

        // Log aman: tanpa email, tanpa token
        Log::warning('Letter notification permanently failed', [
            'delivery_job_id' => $this->deliveryJobId,
            'error' => $e ? class_basename($e) : null,
        ]);
    }

    protected function markRetryable(DeliveryJob $delivery, string $code): void
    {
        $delays = $this->backoff();
        $delay = $delays[min($delivery->attempt_count - 1, count($delays) - 1)];

        $delivery->update([
            'status' => 'failed_retryable',
            'last_error_code' => $code,
            'next_attempt_at' => Carbon::now()->addSeconds($delay),
        ]);
    }
}
