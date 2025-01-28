<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
    <div class="flex flex-1 justify-between sm:hidden">
        @if ($paginator->onFirstPage())
            <span
                class="relative inline-flex cursor-not-allowed items-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium tracking-tight text-black opacity-50"
            >
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a
                class="relative inline-flex cursor-pointer items-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium tracking-tight text-black ring-primary-300 transition duration-150 ease-in-out hover:border-primary-300 hover:bg-primary-50 hover:text-primary focus:border-primary focus:outline-none focus:ring active:bg-neutral-50 active:text-primary"
                wire:click="previousPage"
            >
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a
                class="relative ml-3 inline-flex cursor-pointer items-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium tracking-tight text-black ring-primary-300 transition duration-150 ease-in-out hover:border-primary-300 hover:bg-primary-50 hover:text-primary focus:border-primary focus:outline-none focus:ring active:bg-neutral-50 active:text-primary"
                wire:click="nextPage"
            >
                {!! __('pagination.next') !!}
            </a>
        @else
            <span
                class="relative ml-3 inline-flex cursor-not-allowed items-center rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium tracking-tight text-black opacity-50"
            >
                {!! __('pagination.next') !!}
            </span>
        @endif
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
            <p class="text-sm tracking-tight text-black/70">
                Menampilkan:

                @if ($paginator->firstItem())
                    <span class="mx-0.5 font-semibold text-black">{{ $paginator->firstItem() }}</span>
                    -
                    <span class="mx-0.5 font-semibold text-black">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                dari
                <span class="mx-0.5 font-semibold text-black">{{ $paginator->total() }}</span>
                hasil
            </p>
        </div>
        <div>
            <span class="relative z-0 inline-flex rounded-md shadow-sm rtl:flex-row-reverse">
                {{-- Previous Page Link --}}

                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <span
                            class="relative inline-flex cursor-not-allowed items-center rounded-l-md border border-neutral-300 bg-white p-2 text-sm font-medium tracking-tight text-black opacity-50"
                            aria-hidden="true"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </span>
                    </span>
                @else
                    <a
                        rel="prev"
                        class="relative inline-flex cursor-pointer items-center rounded-l-md border border-neutral-300 bg-white p-2 text-sm font-medium tracking-tight text-black ring-primary-300 transition duration-150 ease-in-out hover:border-primary-300 hover:bg-primary-50 hover:text-primary focus:border-primary focus:outline-none focus:ring active:bg-neutral-50 active:text-primary"
                        aria-label="{{ __('pagination.previous') }}"
                        wire:click="prevPage"
                    >
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span
                                class="relative -ml-px inline-flex cursor-default items-center border border-neutral-300 bg-white px-4 py-2 text-sm font-medium tracking-tight text-black"
                            >
                                {{ $element }}
                            </span>
                        </span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span
                                        class="relative z-[1] -ml-px inline-flex cursor-default items-center border border-primary-300 bg-primary-50 px-4 py-2 text-sm font-medium tracking-tight text-primary"
                                    >
                                        {{ $page }}
                                    </span>
                                </span>
                            @else
                                <a
                                    class="relative -ml-px inline-flex cursor-pointer items-center border border-neutral-300 bg-white px-4 py-2 text-sm font-medium tracking-tight text-black ring-primary-300 transition duration-150 ease-in-out hover:border-primary-300 hover:bg-primary-50 hover:text-primary focus:border-primary focus:outline-none focus:ring active:bg-neutral-50 active:text-primary"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                    wire:click="gotoPage({{ $page }})"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}

                @if ($paginator->hasMorePages())
                    <a
                        rel="next"
                        class="relative -ml-px inline-flex cursor-pointer items-center rounded-r-md border border-neutral-300 bg-white p-2 text-sm font-medium tracking-tight text-black ring-primary-300 transition duration-150 ease-in-out hover:border-primary-300 hover:bg-primary-50 hover:text-primary focus:border-primary focus:outline-none focus:ring active:bg-neutral-50 active:text-primary"
                        aria-label="{{ __('pagination.next') }}"
                        wire:click="nextPage"
                    >
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <span
                            class="relative -ml-px inline-flex cursor-not-allowed items-center rounded-r-md border border-neutral-300 bg-white p-2 text-sm font-medium tracking-tight text-black opacity-50"
                            aria-hidden="true"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </div>
</nav>
