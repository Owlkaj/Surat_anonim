@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-[#172B3A]']) }}>
    {{ $value ?? $slot }}
</label>
