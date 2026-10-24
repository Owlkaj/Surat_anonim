@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#38A9F5] focus:ring-[#38A9F5] rounded-xl shadow-sm']) }}>
