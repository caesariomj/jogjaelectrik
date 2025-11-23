@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
    <section class="container mx-auto h-auto max-w-md p-6 md:max-w-[96rem] md:p-12">
        <h1 class="mb-4 text-black">Pertanyaan yang Sering Diajukan</h1>
        <div class="flex flex-col-reverse justify-between gap-6 md:flex-row">
            <div class="relative h-full w-full text-center md:sticky md:top-20 md:w-1/2">
                <div class="mx-auto mb-6 h-auto w-60 md:w-[28rem]">
                    {!! file_get_contents(public_path('images/illustrations/help.svg')) !!}
                </div>
                <p
                    class="mx-auto mb-6 w-full max-w-sm text-pretty text-sm font-medium tracking-tight text-black lg:text-base"
                >
                    Tidak menemukan pertanyaan yang anda cari? Anda dapat bertanya kepada kami melalui:
                </p>
                <x-common.button href="{{ config('business.whatsapp') }}" variant="secondary" target="_blank">
                    <svg
                        class="size-5 shrink-0 text-[#25D366]"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        x="0px"
                        y="0px"
                        viewBox="0 0 50 50"
                    >
                        <path
                            d="M25,2C12.318,2,2,12.318,2,25c0,3.96,1.023,7.854,2.963,11.29L2.037,46.73c-0.096,0.343-0.003,0.711,0.245,0.966 C2.473,47.893,2.733,48,3,48c0.08,0,0.161-0.01,0.24-0.029l10.896-2.699C17.463,47.058,21.21,48,25,48c12.682,0,23-10.318,23-23 S37.682,2,25,2z M36.57,33.116c-0.492,1.362-2.852,2.605-3.986,2.772c-1.018,0.149-2.306,0.213-3.72-0.231 c-0.857-0.27-1.957-0.628-3.366-1.229c-5.923-2.526-9.791-8.415-10.087-8.804C15.116,25.235,13,22.463,13,19.594 s1.525-4.28,2.067-4.864c0.542-0.584,1.181-0.73,1.575-0.73s0.787,0.005,1.132,0.021c0.363,0.018,0.85-0.137,1.329,1.001 c0.492,1.168,1.673,4.037,1.819,4.33c0.148,0.292,0.246,0.633,0.05,1.022c-0.196,0.389-0.294,0.632-0.59,0.973 s-0.62,0.76-0.886,1.022c-0.296,0.291-0.603,0.606-0.259,1.19c0.344,0.584,1.529,2.493,3.285,4.039 c2.255,1.986,4.158,2.602,4.748,2.894c0.59,0.292,0.935,0.243,1.279-0.146c0.344-0.39,1.476-1.703,1.869-2.286 s0.787-0.487,1.329-0.292c0.542,0.194,3.445,1.604,4.035,1.896c0.59,0.292,0.984,0.438,1.132,0.681 C37.062,30.587,37.062,31.755,36.57,33.116z"
                        />
                    </svg>
                    WhatsApp
                </x-common.button>
            </div>
            <div x-data="faqAccordion(@js($faqItems))" class="w-full divide-y divide-neutral-300 md:w-1/2">
                <div class="relative mb-4">
                    <div class="pointer-events-none absolute inset-y-0 start-4 flex items-center">
                        <svg
                            class="size-5 shrink-0 text-neutral-600"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </div>
                    <x-form.input
                        x-model="search"
                        class="w-full ps-12 text-black"
                        type="text"
                        name="search-question"
                        id="search-question"
                        placeholder="Apa yang ingin anda cari? (contoh: refund, pembayaran, resi)"
                        autocomplete="off"
                    />
                </div>
                <template x-for="item in items" :key="item.id">
                    <div
                        x-show="
                            item.title.toLowerCase().includes(search.toLowerCase()) ||
                                item.content.toLowerCase().includes(search.toLowerCase())
                        "
                        x-transition.opacity
                    >
                        <x-common.accordion>
                            <x-slot name="title">
                                <h3 class="text-lg tracking-tight text-black lg:text-xl" x-text="item.title"></h3>
                            </x-slot>
                            <p
                                class="mb-4 text-pretty text-sm font-medium tracking-tight text-black lg:text-base"
                                x-html="item.content"
                            ></p>
                        </x-common.accordion>
                    </div>
                </template>
                <p
                    x-show="filteredItems.length === 0"
                    class="text-pretty py-4 text-center text-sm font-medium tracking-tight text-black lg:text-base"
                >
                    Pertanyaan yang anda cari tidak ditemukan.
                </p>
            </div>
        </div>
    </section>
@endsection
