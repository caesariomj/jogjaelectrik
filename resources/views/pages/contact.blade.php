@extends('layouts.app')

@section('title', 'Kontak Kami')

@section('content')
    <section class="container mx-auto flex h-auto max-w-md flex-row justify-between gap-6 p-6 md:max-w-[96rem] md:p-12">
        <div class="w-full md:w-1/2">
            <h1 class="mb-4 text-black">Kontak Kami</h1>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Anda dapat menghubungi kami melalui kontak di bawah ini
            </p>
            <div class="flex flex-col gap-y-4">
                <div class="flex flex-row items-start justify-start gap-6">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-50 p-2">
                        <svg
                            class="size-6 text-primary"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 0 0-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
                    <div class="flex flex-col gap-1">
                        <h2 class="text-pretty !text-lg leading-none text-black">Alamat Kami</h2>
                        <a
                            href="{{ config('business.map_link') }}"
                            class="text-base font-normal tracking-tight text-black underline transition-colors hover:text-primary"
                            target="_blank"
                        >
                            {{ config('business.address') }}
                        </a>
                    </div>
                </div>
                <div class="flex flex-row items-start justify-start gap-6">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-50 p-2">
                        <svg
                            class="size-6 text-primary"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
                    <div class="flex flex-col gap-1">
                        <h2 class="text-pretty !text-lg leading-none text-black">Nomor Telefon / WhatsApp Kami</h2>
                        <a
                            href="{{ config('business.whatsapp') }}"
                            class="text-base font-normal tracking-tight text-black underline transition-colors hover:text-primary"
                            target="_blank"
                        >
                            {{ config('business.phone') }}
                        </a>
                    </div>
                </div>
                <div class="flex flex-row items-start justify-start gap-6">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-50 p-2">
                        <svg
                            class="size-6 text-primary"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                        >
                            <path
                                d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z"
                            />
                            <path
                                d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z"
                            />
                        </svg>
                    </span>
                    <div class="flex flex-col gap-1">
                        <h2 class="text-pretty !text-lg leading-none text-black">Email Kami</h2>
                        <a
                            href="mailto:{{ config('business.email') }}"
                            class="text-base font-normal tracking-tight text-black underline transition-colors hover:text-primary"
                            target="_blank"
                        >
                            {{ config('business.email') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <figure class="hidden md:flex md:w-1/2 md:items-center md:justify-center">
            <img src="https://placehold.co/400" class="size-72 object-cover" alt="Gambar ilustrasi halaman kontak" />
        </figure>
    </section>
@endsection
