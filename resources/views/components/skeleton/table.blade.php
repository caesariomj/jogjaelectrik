@props([
    'totalRows',
])

<div role="status" class="animate-pulse rounded-xl bg-white shadow-sm">
    <div class="flex w-full flex-col justify-between gap-4 p-4 md:flex-row md:items-center">
        <div class="relative h-12 w-full shrink rounded-md bg-neutral-200"></div>
        <div class="ml-auto inline-flex shrink-0 items-center gap-2">
            <div class="block h-12 w-16 rounded-md bg-neutral-200"></div>
            <div class="h-3 w-28 rounded-md bg-neutral-200"></div>
        </div>
    </div>
    <div class="border-b border-b-neutral-300">
        <div class="flex w-full flex-row items-center justify-between border-t border-t-neutral-300 px-4 py-5">
            @for ($row = 0; $row < $totalRows; $row++)
                <div class="h-3 w-28 rounded-md bg-neutral-200"></div>
            @endfor
        </div>
        @for ($column = 0; $column < 10; $column++)
            <div class="flex w-full flex-row items-center justify-between border-t border-t-neutral-300 px-4 py-2.5">
                @for ($row = 0; $row < $totalRows; $row++)
                    <div class="h-3 w-28 rounded-md bg-neutral-200"></div>
                @endfor
            </div>
        @endfor
    </div>
    <div class="flex items-center justify-between p-4">
        <div class="h-3 w-48 rounded-md bg-neutral-200"></div>
        <div class="block h-8 w-32 rounded-md bg-neutral-200"></div>
    </div>
    <span class="sr-only">Sedang diproses...</span>
</div>
