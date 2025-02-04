<footer class="bg-white shadow-[rgba(0,0,0,0.1)_0px_-10px_25px_-3px]">
    <div
        {{ $attributes->merge(['class' => 'flex flex-col items-start justify-between gap-12 md:flex-row']) }}
    >
        <div class="flex w-full flex-col gap-12 md:w-1/3">
            <a
                href="{{ route('home') }}"
                class="inline-flex items-center justify-center gap-6 text-4xl font-bold leading-tight tracking-tighter text-black md:justify-start"
                wire:navigate
            >
                <x-common.application-logo class="block h-16 w-auto fill-current text-primary" />
                {{ config('app.name') }}
            </a>
            <address class="flex flex-col gap-y-3 not-italic">
                <a
                    href="#"
                    class="inline-flex items-center gap-x-3 text-sm font-medium leading-tight tracking-tight text-black underline transition-colors hover:text-primary"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="size-5 shrink-0"
                        aria-hidden="true"
                    >
                        <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7" />
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
                        <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4" />
                        <path d="M2 7h20" />
                        <path
                            d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"
                        />
                    </svg>
                    Alamat
                </a>
                <a
                    href="#"
                    class="inline-flex items-center gap-x-3 text-sm font-medium leading-tight tracking-tight text-black underline transition-colors hover:text-primary"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="size-5 shrink-0"
                        aria-hidden="true"
                    >
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                        />
                    </svg>
                    WhatsApp
                </a>
                <a
                    href="#"
                    class="inline-flex items-center gap-x-3 text-sm font-medium leading-tight tracking-tight text-black underline transition-colors hover:text-primary"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="size-5 shrink-0"
                        aria-hidden="true"
                    >
                        <rect width="20" height="16" x="2" y="4" rx="2" />
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                    </svg>
                    Email
                </a>
            </address>
            <p class="hidden text-sm font-medium leading-tight tracking-tight text-black md:block">
                &copy; {{ date('Y') }} &mdash; {{ config('app.name') }}
            </p>
        </div>
        <div class="grid w-full grid-cols-2 gap-6 md:w-2/3 lg:grid-cols-4">
            <nav aria-label="Link Sosial Media">
                <h2 class="mb-3 text-balance text-base font-semibold leading-tight tracking-tight text-black">
                    Ikuti Kami
                </h2>
                <ul class="space-y-1.5">
                    <li>
                        <a
                            href="#"
                            class="inline-flex items-center gap-x-2 text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                stroke="none"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="size-5 shrink-0"
                                aria-hidden="true"
                            >
                                <path
                                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"
                                />
                            </svg>
                            WhatsApp
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="inline-flex items-center gap-x-2 text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="size-5 shrink-0"
                                aria-hidden="true"
                            >
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                            </svg>
                            Facebook
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="inline-flex items-center gap-x-2 text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="size-5 shrink-0"
                                aria-hidden="true"
                            >
                                <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                            </svg>
                            Instagram
                        </a>
                    </li>
                </ul>
            </nav>
            <nav aria-label="Link Produk dan Belanja">
                <h2 class="mb-3 text-balance text-base font-semibold leading-tight tracking-tight text-black">
                    Produk dan Belanja
                </h2>
                <ul class="space-y-1.5">
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Produk Unggulan
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Promo dan Diskon
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Produk Terbaru
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Best Seller
                        </a>
                    </li>
                    @if ($primaryCategories->isNotEmpty())
                        @foreach ($primaryCategories as $category)
                            <li>
                                <a
                                    href="#"
                                    class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                                >
                                    {{ ucwords($category->name) }}
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </nav>
            <nav aria-label="Link Informasi Perusahaan">
                <h2 class="mb-3 text-balance text-base font-semibold leading-tight tracking-tight text-black">
                    Informasi Perusahaan
                </h2>
                <ul class="space-y-1.5">
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Tentang Kami
                        </a>
                    </li>
                </ul>
            </nav>
            <nav aria-label="Link Bantuan dan Layanan Pelanggan">
                <h2 class="mb-3 text-balance text-base font-semibold leading-tight tracking-tight text-black">
                    Bantuan dan Layanan Pelanggan
                </h2>
                <ul class="space-y-1.5">
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            FAQ
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Cara Pemesanan
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Metode Pembayaran
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Kebijakan Pengiriman
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Kebijakan Pengembalian Barang
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Hubungi Kami
                        </a>
                    </li>
                </ul>
            </nav>
            <nav aria-label="Link Legalitas dan Keamanan">
                <h2 class="mb-3 text-balance text-base font-semibold leading-tight tracking-tight text-black">
                    Legalitas dan Keamanan
                </h2>
                <ul class="space-y-1.5">
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Syarat dan Ketentuan
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                        >
                            Kebijakan Privasi
                        </a>
                    </li>
                </ul>
            </nav>
            <div>
                <h2 class="mb-3 text-balance text-base font-semibold leading-tight tracking-tight text-black">
                    Metode Pembayaran yang Didukung
                </h2>
                <ul class="flex flex-row flex-wrap gap-1.5">
                    @php
                        $supportedPayments = ['qris', 'shopeepay', 'dana', 'ovo', 'linkaja', 'astrapay', 'jenius', 'bca', 'bni', 'bri', 'mandiri', 'permata', 'cimb', 'bsi', 'sampoerna', 'hana'];
                    @endphp

                    @foreach ($supportedPayments as $payment)
                        <li class="h-8 w-14 rounded-md border border-neutral-300 px-2 py-1">
                            <img
                                src="{{ asset('images/logos/payments/' . $payment . '.webp') }}"
                                alt="Logo {{ strtoupper($payment) }}"
                                class="h-full w-full object-contain"
                                loading="lazy"
                            />
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="mb-3 text-balance text-base font-semibold leading-tight tracking-tight text-black">
                    Metode Pengiriman yang Didukung
                </h2>
                <ul class="flex flex-row flex-wrap gap-1.5">
                    @php
                        $supportedExpeditions = ['jne', 'pos', 'tiki'];
                    @endphp

                    @foreach ($supportedExpeditions as $expedition)
                        <li class="h-8 w-14 rounded-md border border-neutral-300 px-2 py-1">
                            <img
                                src="{{ asset('images/logos/shipping/' . $expedition . '.webp') }}"
                                alt="Logo {{ strtoupper($expedition) }}"
                                class="h-full w-full object-contain"
                                loading="lazy"
                            />
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <p class="block w-full text-center text-sm font-medium leading-tight tracking-tight text-black md:hidden">
            &copy; {{ date('Y') }} &mdash; {{ config('app.name') }}
        </p>
    </div>
</footer>
