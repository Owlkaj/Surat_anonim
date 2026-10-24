<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary text-sm']) }}>
    {{ $slot }}
</button>
