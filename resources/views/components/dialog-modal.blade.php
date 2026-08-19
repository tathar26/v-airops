@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-5" style="background-color: #0f172a !important;">
        <div class="text-lg font-bold text-white">
            {{ $title }}
        </div>

        <div class="mt-4 text-sm text-slate-300">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 text-end" style="background-color: #020617 !important; border-top: 1px solid #1e293b !important;">
        {{ $footer }}
    </div>
</x-modal>
