@props([
    'options' => [],
    'name' => '',
    'selectedOption' => null,
])

<div
    x-data="combobox({
                allOptions: {{ $options->isEmpty() ? '[]' : $options->toJson() }},
                selectedOption: '{{ $selectedOption }}',
                instanceName: '{{ $name }}',
            })"
    x-on:keydown="handleKeydownOnOptions($event)"
    x-on:keydown.esc.window="isOpen = false, openedWithKeyboard = false"
>
    <div class="relative">
        <button
            type="button"
            class="inline-flex w-full items-center justify-between gap-2 rounded-md border border-neutral-300 bg-white px-4 py-3 text-sm font-medium tracking-wide text-neutral-600 transition hover:opacity-75 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black"
            role="combobox"
            aria-controls="{{ $name }}-list"
            aria-haspopup="listbox"
            x-on:click="isOpen = ! isOpen"
            x-on:keydown.down.prevent="openedWithKeyboard = true"
            x-on:keydown.enter.prevent="openedWithKeyboard = true"
            x-on:keydown.space.prevent="openedWithKeyboard = true"
            x-bind:aria-expanded="isOpen || openedWithKeyboard"
            x-bind:aria-label="selectedOption ? selectedOption.label : 'Silakan pilih {{ $name }}'"
        >
            <span
                class="text-sm font-normal capitalize text-black"
                x-text="selectedOption ? selectedOption.label : 'Silakan pilih {{ $name }}'"
            ></span>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                class="size-5"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>
        <input {{ $attributes }} x-ref="hiddenTextField" hidden />
        <div
            x-show="isOpen || openedWithKeyboard"
            id="{{ $name }}-list"
            class="absolute left-0 top-12 z-10 w-full overflow-hidden rounded-md border border-neutral-300 bg-white shadow-lg"
            role="listbox"
            aria-label="industries list"
            x-on:click.outside="isOpen = false, openedWithKeyboard = false"
            x-on:keydown.down.prevent="$focus.wrap().next()"
            x-on:keydown.up.prevent="$focus.wrap().previous()"
            x-transition
            x-trap="openedWithKeyboard"
            x-cloak
        >
            <div class="relative">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    fill="none"
                    stroke-width="1.5"
                    class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-black opacity-75"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                    />
                </svg>
                <input
                    type="text"
                    class="w-full border-b border-transparent border-b-neutral-300 bg-white py-2.5 pl-11 pr-4 text-sm text-black focus:border-transparent focus:border-b-neutral-300 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-75"
                    name="searchField"
                    x-ref="searchField"
                    aria-label="Cari {{ $name }}"
                    x-on:input="getFilteredOptions($el.value)"
                    placeholder="Cari {{ $name }} berdasarkan nama..."
                />
            </div>
            <ul class="flex max-h-44 flex-col overflow-y-auto p-2">
                <li class="hidden px-4 py-2 text-sm text-black" x-ref="noResultsMessage">
                    <span>{{ ucfirst($name) }} yang anda cari tidak ditemukan.</span>
                </li>
                <template x-for="(item, index) in options" x-bind:key="item.value">
                    <li
                        class="combobox-option inline-flex cursor-pointer justify-between gap-6 rounded-md bg-white px-4 py-2 text-sm capitalize text-black hover:bg-neutral-900/5 focus-visible:bg-neutral-900/5 focus-visible:text-neutral-900 focus-visible:outline-none"
                        role="option"
                        x-on:click="setSelectedOption(item)"
                        x-on:keydown.enter="setSelectedOption(item)"
                        x-bind:id="'option-' + index"
                        tabindex="0"
                    >
                        <span
                            x-bind:class="selectedOption == item ? 'font-bold text-primary' : null"
                            x-text="item.label"
                        ></span>
                        <span class="sr-only" x-text="selectedOption == item ? 'selected' : null"></span>
                        <svg
                            x-cloak
                            x-show="selectedOption == item"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            fill="none"
                            stroke-width="2"
                            class="size-4 text-primary"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
