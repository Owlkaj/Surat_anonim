@props(['letter'])

@php
    if ($letter->revoked_at !== null) {
        $status = ['label' => 'Link dicabut', 'class' => 'bg-rose-50 text-rose-700'];
    } elseif ($letter->expires_at !== null && $letter->expires_at->isPast()) {
        $status = ['label' => 'Kedaluwarsa', 'class' => 'bg-slate-100 text-slate-600'];
    } else {
        $status = ['label' => 'Link aktif', 'class' => 'bg-emerald-50 text-emerald-700'];
    }
@endphp

<span {{ $attributes->merge(['class' => 'badge-pill '.$status['class']]) }}>
    {{ $status['label'] }}
</span>
