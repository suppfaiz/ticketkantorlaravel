@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] rounded-md shadow-sm']) }}>
