<div role="status">
    <!-- Search Box -->
    <div class="mb-6 animate-pulse space-y-4 rounded-xl bg-white p-4 shadow-sm">
        <div class="flex w-full flex-col justify-between gap-4 md:flex-row md:items-center">
            <div class="relative h-12 w-full shrink rounded-md bg-neutral-200"></div>
            <div class="ml-auto inline-flex shrink-0 items-center gap-2">
                <div class="block h-12 w-16 rounded-md bg-neutral-200"></div>
                <div class="h-3 w-28 rounded-md bg-neutral-200"></div>
            </div>
        </div>
        <div class="flex flex-row gap-x-2 pb-2">
            <div class="h-12 w-full shrink rounded-md bg-neutral-200"></div>
            <div class="h-12 w-full shrink rounded-md bg-neutral-200"></div>
            <div class="h-12 w-full shrink rounded-md bg-neutral-200"></div>
            <div class="h-12 w-full shrink rounded-md bg-neutral-200"></div>
            <div class="h-12 w-full shrink rounded-md bg-neutral-200"></div>
            <div class="h-12 w-full shrink rounded-md bg-neutral-200"></div>
            <div class="h-12 w-full shrink rounded-md bg-neutral-200"></div>
            <div class="h-12 w-full shrink rounded-md bg-neutral-200"></div>
        </div>
    </div>
    <div class="space-y-4">
        @for ($order = 0; $order < 5; $order++)
            <div class="animate-pulse rounded-xl bg-white shadow-sm">
                <div class="mb-4 flex flex-col gap-2 border-b border-b-neutral-300 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-2 text-sm text-neutral-500">Nomor Pesanan:</div>
                            <div class="h-5 w-48 rounded bg-neutral-300"></div>
                        </div>
                        <div class="h-6 w-24 rounded-full bg-neutral-300"></div>
                    </div>
                    <div class="flex items-center">
                        <div class="mr-2 text-neutral-300">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="size-5 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"
                                />
                            </svg>
                        </div>
                        <div class="h-4 w-40 rounded bg-neutral-300"></div>
                    </div>
                </div>
                <div class="mb-8 space-y-4 p-4">
                    @for ($item = 0; $item < 2; $item++)
                        <div class="mb-6 flex">
                            <div class="size-20 rounded-md bg-neutral-300"></div>
                            <div class="ml-4 flex-1">
                                <div class="mb-2 h-5 w-2/3 rounded bg-neutral-300"></div>
                                <div class="flex items-center">
                                    <div class="me-2 h-4 w-6 rounded bg-neutral-300"></div>
                                    <div class="text-neutral-500">×</div>
                                    <div class="ms-2 h-4 w-24 rounded bg-neutral-300"></div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
                <div class="flex flex-col items-start justify-between gap-6 border-t border-t-neutral-300 p-4">
                    <div class="grid w-full grid-cols-2 place-items-baseline gap-2 md:grid-cols-4">
                        <div class="inline-flex items-center text-sm font-medium tracking-tight text-neutral-300">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <div>Nama Pembeli:</div>
                        </div>
                        <div class="h-4 w-32 rounded bg-neutral-300"></div>
                        <div class="inline-flex items-center text-sm font-medium tracking-tight text-neutral-300">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <div>Nomor Telefon:</div>
                        </div>
                        <div class="h-4 w-32 rounded bg-neutral-300"></div>
                        <div class="inline-flex items-center text-sm font-medium tracking-tight text-neutral-300">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25ZM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 1 1 6 0h3a.75.75 0 0 0 .75-.75V15Z"
                                />
                                <path
                                    d="M8.25 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0ZM15.75 6.75a.75.75 0 0 0-.75.75v11.25c0 .087.015.17.042.248a3 3 0 0 1 5.958.464c.853-.175 1.522-.935 1.464-1.883a18.659 18.659 0 0 0-3.732-10.104 1.837 1.837 0 0 0-1.47-.725H15.75Z"
                                />
                                <path d="M19.5 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" />
                            </svg>
                            <div>Ekspedisi & Layanan Kurir :</div>
                        </div>
                        <div class="h-4 w-32 rounded bg-neutral-300"></div>
                        <div class="inline-flex items-center text-sm font-medium tracking-tight text-neutral-300">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z"
                                    clip-rule="evenodd"
                                />
                                <path
                                    d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z"
                                />
                            </svg>
                            <div>Catatan Pesanan :</div>
                        </div>
                        <div class="h-4 w-32 rounded bg-neutral-300"></div>
                        <div class="inline-flex items-center text-sm font-medium tracking-tight text-neutral-300">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"
                                />
                                <path
                                    d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"
                                />
                            </svg>
                            <div>Alamat Pengiriman :</div>
                        </div>
                        <div class="inline-flex flex-col gap-y-2">
                            <div class="h-4 w-56 rounded bg-neutral-300"></div>
                            <div class="h-4 w-40 rounded bg-neutral-300"></div>
                            <div class="h-4 w-32 rounded bg-neutral-300"></div>
                        </div>
                    </div>
                    <div class="flex w-full items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-2 text-base text-neutral-500">Total Pembayaran:</div>
                            <div class="h-5 w-32 rounded bg-neutral-300"></div>
                        </div>
                        <div class="h-10 w-32 rounded-full bg-neutral-300"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>
