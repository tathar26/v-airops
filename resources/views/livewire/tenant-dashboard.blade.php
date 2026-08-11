<div class="space-y-6 max-w-[1600px] mx-auto w-full">
    
    <!-- Top Stats Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- PIREPs -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>PIREPs</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-green-500">{{ $totalPireps }}</div>
                <div class="text-xl font-bold text-orange-400">0</div>
                <div class="text-xl font-bold text-red-400">0</div>
                <div class="text-xl font-bold text-blue-400">0</div>
            </div>
        </div>

        <!-- Points -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Points</span>
                <span class="text-[10px]">~253 per flight</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-blue-500">0</div>
                <div class="text-lg font-bold text-green-500">0</div>
            </div>
        </div>

        <!-- Time & Distance -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Time & Distance</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-blue-500">0h 0m</div>
                <div class="text-lg font-bold text-blue-500">0 NM</div>
            </div>
        </div>
    </div>

    <!-- Top Stats Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Pax & Freight -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Pax & Freight</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-blue-500">0</div>
                <div class="text-lg font-bold text-blue-500">0 kg</div>
            </div>
        </div>

        <!-- Fuel & Landing Rate -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Fuel & Landing Rate</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-blue-500">0 kg</div>
                <div class="text-lg font-bold text-blue-500">0 FPM</div>
            </div>
        </div>

        <!-- Pilot -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
            <div class="px-4 py-2 bg-[#2c323f] border-b border-[#3f475a] text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                <span>Pilot</span>
                <span class="text-[10px]">Your Username & Location</span>
            </div>
            <div class="px-6 py-5 flex justify-between items-center bg-[#212631]">
                <div class="text-2xl font-bold text-gray-200">{{ auth()->user()->name }}</div>
                <div class="text-lg font-bold text-blue-500">EGLL</div>
            </div>
        </div>
    </div>

    <!-- Info Banners -->
    <div class="bg-[#fcd34d] text-black p-4 rounded-md shadow-sm text-sm font-medium">
        <h4 class="font-bold mb-1">Welcome to {{ auth()->user()->tenant->name ?? 'our VA' }}!</h4>
        <p class="mb-2">It's great to see you!</p>
        <p>Please ensure you book and fly the same aircraft type. This tracking software is PC compatible only. Enjoy your flights with us!</p>
    </div>

</div>
