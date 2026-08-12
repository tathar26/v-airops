<x-app-layout>
    <div class="space-y-6 max-w-[1600px] mx-auto w-full">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Flight Centre</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('flight-centre.book') }}" class="block glass-panel p-6 hover:bg-white/5 transition rounded-lg">
                <h3 class="text-xl font-bold text-tenant-accent mb-2">Book Flight Map</h3>
                <p class="text-gray-400 text-sm">Interactive map showing available flights from your current location.</p>
            </a>

            <a href="{{ route('flight-centre.flights') }}" class="block glass-panel p-6 hover:bg-white/5 transition rounded-lg">
                <h3 class="text-xl font-bold text-tenant-accent mb-2">Flights List</h3>
                <p class="text-gray-400 text-sm">Data table view of available flights.</p>
            </a>

            <a href="{{ route('flight-centre.destinations') }}" class="block glass-panel p-6 hover:bg-white/5 transition rounded-lg">
                <h3 class="text-xl font-bold text-tenant-accent mb-2">Destination Map</h3>
                <p class="text-gray-400 text-sm">Explore the entire VA network dynamically.</p>
            </a>
        </div>
    </div>
</x-app-layout>
