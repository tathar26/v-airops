@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-5" style="background-color: var(--tenant-card-bg, #0f172a) !important; color: var(--tenant-card-text, #ffffff) !important;">
        <div class="text-lg font-bold" style="color: var(--tenant-card-text, #ffffff) !important;">
            {{ $title }}
        </div>

        <div class="mt-4 text-sm" style="color: var(--tenant-card-muted, #94a3b8) !important;">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 text-end gap-3" style="background-color: var(--tenant-card-bg, #020617) !important; border-top: 1px solid var(--tenant-input-border, #1e293b) !important;">
        {{ $footer }}
    </div>
</x-modal>
