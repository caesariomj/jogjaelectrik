@php
    if (request()->is('/')) {
        return;
    }

    $segments = request()->segments();
    $breadcrumbs = [];
    $path = '';

    $breadcrumbs[] = [
        'name' => 'Beranda',
        'url' => url('/'),
    ];

    if ($segments[0] === 'admin') {
        if (count($segments) > 1 && $segments[1] !== 'dashboard') {
            $breadcrumbs[] = [
                'name' => 'Dashboard',
                'url' => url('/admin/dashboard'),
            ];
        }

        array_shift($segments);

        foreach ($segments as $key => $segment) {
            $path .= '/' . $segment;

            if ($key == 1 && preg_match('/^manajemen-[\w-]+$/', $segments[0]) && ! in_array($segment, ['tambah', 'ubah', 'edit'])) {
                if (! in_array(str_replace('manajemen-', '', $segments[0]), ['produk', 'kategori', 'subkategori', 'diskon'])) {
                    $breadcrumbs[] = [
                        'name' => ucwords($segment),
                        'url' => url('/admin' . $path . '/detail'),
                    ];
                } else {
                    $breadcrumbs[] = [
                        'name' => ucwords(str_replace('-', ' ', $segment)),
                        'url' => url('/admin' . $path . '/detail'),
                    ];
                }
            } else {
                $breadcrumbs[] = [
                    'name' => ucwords(str_replace('-', ' ', $segment)),
                    'url' => url('/admin' . $path),
                ];
            }
        }
    } else {
        foreach ($segments as $key => $segment) {
            $path .= '/' . $segment;

            if ($key == 1 && in_array(str_replace('-saya', '', $segments[0]), ['pesanan', 'riwayat-transaksi'])) {
                $breadcrumbs[] = [
                    'name' => ucwords($segment),
                    'url' => url($path . '/detail'),
                ];
            } else {
                $breadcrumbs[] = [
                    'name' => ucwords(str_replace('-', ' ', $segment)),
                    'url' => url($path),
                ];
            }
        }
    }
@endphp

<nav
    {{ $attributes->merge(['aria-label' => 'breadcrumb']) }}
>
    <ol class="flex flex-wrap items-center gap-y-4 whitespace-nowrap">
        @foreach ($breadcrumbs as $index => $breadcrumb)
            @if ($index + 1 < count($breadcrumbs))
                <li class="inline-flex items-center">
                    <a
                        class="flex items-center text-sm font-medium tracking-tight text-black/70 transition-colors hover:text-black"
                        href="{{ $breadcrumb['url'] }}"
                        wire:navigate
                    >
                        {{ $breadcrumb['name'] }}
                    </a>
                    <svg
                        class="mx-4 size-4 shrink-0 text-black/40"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </li>
            @else
                <li class="inline-flex items-center text-sm font-medium tracking-tight text-black" aria-current="page">
                    {{ $breadcrumb['name'] }}
                </li>
            @endif
        @endforeach
    </ol>
</nav>
