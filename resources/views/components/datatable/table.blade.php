@props([
    'searchable' => null,
])

<section class="rounded-xl border border-neutral-300 bg-white shadow">
    <div class="flex w-full flex-col justify-between gap-4 p-4 md:flex-row md:items-center">
        <div class="relative w-full shrink">
            <div class="pointer-events-none absolute inset-y-0 start-0 z-20 flex items-center ps-4">
                <svg
                    class="size-4 shrink-0 text-black/70"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </div>
            <div class="relative">
                <x-form.input
                    id="search-input"
                    name="search-input"
                    wire:model.live.debounce.250ms="search"
                    class="block w-full !border-neutral-100 !bg-neutral-100 px-12"
                    type="text"
                    placeholder="Cari {{ $searchable }} berdasarkan nama..."
                    autocomplete="off"
                />
                <div
                    wire:loading
                    wire:target="search,resetSearch"
                    class="pointer-events-none absolute end-0 top-1/2 -translate-y-1/2 pe-4"
                >
                    <svg
                        class="size-5 shrink-0 animate-spin text-black"
                        fill="currentColor"
                        viewBox="0 0 256 256"
                        aria-hidden="true"
                    >
                        <path
                            d="M232,128a104,104,0,0,1-208,0c0-41,23.81-78.36,60.66-95.27a8,8,0,0,1,6.68,14.54C60.15,61.59,40,93.27,40,128a88,88,0,0,0,176,0c0-34.73-20.15-66.41-51.34-80.73a8,8,0,0,1,6.68-14.54C208.19,49.64,232,87,232,128Z"
                        />
                    </svg>
                </div>
            </div>
        </div>
        <div class="ml-auto inline-flex shrink-0 items-center gap-2">
            <select
                id="per-page"
                class="block w-16 rounded-md border border-neutral-300 p-3 text-sm text-black focus:border-primary focus:ring-primary"
                wire:model.lazy="perPage"
            >
                @for ($i = 5; $i <= 25; $i += 5)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>
            <span class="text-sm font-medium tracking-tight text-black">data per halaman</span>
        </div>
    </div>

    @isset($sort)
        <div class="flex items-center gap-4 px-4 pb-4">{{ $sort }}</div>
    @endisset

    <div class="relative w-full overflow-hidden overflow-x-auto border-b border-b-neutral-300">
        <table class="w-full">
            <thead class="border-y border-neutral-300">
                {{ $head }}
            </thead>
            <tbody class="divide-y divide-neutral-300">
                {{ $body }}
            </tbody>
        </table>
        {{ $loader }}
    </div>
    <div class="p-4">
        {{ $pagination }}
    </div>
</section>
