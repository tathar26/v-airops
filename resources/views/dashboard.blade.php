<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-vops-primary to-vops-secondary leading-tight">
            {{ __('Virtual Airline Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8 lg:py-10 flex-grow flex flex-col relative z-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full">
        @livewire('tenant-dashboard')
    </div>
</x-app-layout>
