@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-brand-red focus:ring-brand-red rounded-md shadow-sm']) }}>
