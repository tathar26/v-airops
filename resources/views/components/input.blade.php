@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'bg-[#212631] border-gray-600 text-white focus:border-tenant-accent focus:ring-tenant-accent rounded-md shadow-sm']) !!}>
