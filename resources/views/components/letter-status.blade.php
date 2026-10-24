@props(['letter'])

@php
    $status = match ($letter->moderation_status) {
        'under_review' => ['label' => 'Dalam peninjauan', 'class' => 'badge-review'],
        'takedown' => ['label' => 'Ditakedown', 'class' => 'badge-review'],
        'rejected' => ['label' => 'Ditolak moderator', 'class' => 'badge-review'],
        default => match ($letter->visibility) {
            'public' => ['label' => 'Publik', 'class' => 'badge-public'],
            'public_pending' => ['label' => 'Menunggu moderasi', 'class' => 'badge-pending'],
            'unlisted' => ['label' => 'Unlisted', 'class' => 'badge-unlisted'],
            default => ['label' => 'Privat', 'class' => 'badge-privat'],
        },
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge-pill '.$status['class']]) }}>
    {{ $status['label'] }}
</span>
