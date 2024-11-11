@props([
    "variant" => "primary",
    "type" => "button",
    "disabled" => false,
    "href" => null,
])

@php
    $baseClasses = "inline-flex items-center justify-center gap-x-2 rounded-full px-8 py-3 text-sm font-semibold transition-all focus:outline-none disabled:cursor-not-allowed disabled:opacity-50";

    $variants = [
        "primary" => "bg-primary text-white hover:bg-primary-600 focus:ring-2 focus:ring-primary-400 focus:ring-offset-2",
        "secondary" => "bg-neutral-100 text-black hover:bg-neutral-200 focus:ring-2 focus:ring-neutral-300 focus:ring-offset-2",
        "danger" => "bg-red-500 text-white hover:bg-red-600 focus:ring-2 focus:ring-red-400 focus:ring-offset-2",
        "success" => "bg-teal-500 text-white hover:bg-teal-600 focus:ring-2 focus:ring-teal-400 focus:ring-offset-2",
        "outline" => "border border-black text-black hover:bg-neutral-100",
    ];

    $disabledClasses = $disabled ? "cursor-not-allowed opacity-50" : "cursor-pointer";

    $classes = $baseClasses . " " . ($variants[$variant] ?? $variants["primary"]) . " " . $disabledClasses;
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{
            $attributes->merge([
                "class" => $classes,
            ])
        }}
        @if ($disabled)
            onclick="return false;"
            tabindex="-1"
            aria-disabled="true"
        @endif
    >
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(["class" => $classes, "type" => $type]) }} @disabled($disabled)>
        {{ $slot }}
    </button>
@endif
