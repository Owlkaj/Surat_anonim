@props(['delivery' => null])

@if ($delivery)
    @php
        $status = match ($delivery->status) {
            'queued' => ['label' => 'Antrean email', 'class' => 'bg-sky-50 text-sky-700'],
            'sending' => ['label' => 'Mengirim email', 'class' => 'bg-sky-50 text-sky-700'],
            'sent' => ['label' => 'Terkirim ke provider', 'class' => 'bg-emerald-50 text-emerald-700'],
            'failed_retryable' => ['label' => 'Akan dicoba ulang', 'class' => 'bg-amber-50 text-amber-800'],
            'failed_permanent' => ['label' => 'Email gagal', 'class' => 'bg-rose-50 text-rose-700'],
            default => ['label' => 'Status email tidak diketahui', 'class' => 'bg-slate-100 text-slate-600'],
        };
    @endphp

    <span {{ $attributes->merge(['class' => 'badge-pill '.$status['class']]) }}>
        {{ $status['label'] }}
    </span>
@endif
