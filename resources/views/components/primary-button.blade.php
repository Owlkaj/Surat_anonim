<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-sky text-sm']) }}>
    {{ $slot }}
</button>
