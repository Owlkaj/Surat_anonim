@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[#38A9F5] text-sm font-semibold leading-5 text-[#172B3A] focus:outline-none focus:border-[#126FB3] transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[#617586] hover:text-[#172B3A] hover:border-[#BFD9E8] focus:outline-none focus:text-[#172B3A] focus:border-[#BFD9E8] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
