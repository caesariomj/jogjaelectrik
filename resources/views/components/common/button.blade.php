@props([
    "variant" => "primary",
    "type" => "button",
    "disabled" => false,
    "href" => null,
])

@php
    $baseClasses = "inline-flex items-center justify-center rounded-md font-medium transition-all focus:outline-none";

    $variants = [
        "primary" => "bg-primary text-white hover:bg-blue-600 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2",
        "secondary" => "bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2",
        "danger" => "bg-rose-500 text-white hover:bg-red-600 focus:ring-2 focus:ring-red-400 focus:ring-offset-2",
        "success" => "bg-emerald-500 text-white hover:bg-green-600 focus:ring-2 focus:ring-green-400 focus:ring-offset-2",
        "outline" => "border-2 border-gray-300 text-gray-600 hover:bg-gray-50 focus:ring-2 focus:ring-offset-2",
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
