@props([
    'name',
    'show' => false,
    'position' => null,
])

@php
    $positionClasses = [
        'right' => 'end-0 top-0 md:w-[32rem] md:rounded-s-lg',
        'left' => 'start-0 top-0 md:w-[32rem] md:rounded-e-lg',
        'bottom' => 'inset-x-0 bottom-0 max-h-[calc(100%-5rem)] rounded-t-lg',
    ];

    $positionClass = $positionClasses[$position] ?? $positionClasses['right'];

    $transitionClasses = [
        'enter' => 'transform transition duration-300 ease-in-out',
        'enter-start' => ($position === 'bottom' ? 'translate-y-full' : $position === 'left') ? '-translate-x-full' : 'translate-x-full',
        'enter-end' => ($position === 'bottom' ? 'translate-y-0' : $position === 'left') ? '-translate-x-0' : 'translate-x-0',
        'leave' => 'transform transition duration-300 ease-in-out',
        'leave-start' => ($position === 'bottom' ? 'translate-y-0' : $position === 'left') ? '-translate-x-0' : 'translate-x-0',
        'leave-end' => ($position === 'bottom' ? 'translate-y-full' : $position === 'left') ? '-translate-x-full' : 'translate-x-full',
    ];
@endphp

<aside
    x-data="{
        show: @js($show),
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)].filter(el => !el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1 },
    }"
    x-init="
        $watch('show', (value) => {
            if (value) {
                document.body.classList.add('overflow-y-hidden')
                {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
            } else {
                document.body.classList.remove('overflow-y-hidden')
            }
        })
    "
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-labelledby="label-{{ $name }}"
    aria-hidden="true"
    x-show="show"
    x-on:open-offcanvas.window="$event.detail == '{{ $name }}' ? (show = true) : null"
    x-on:close-offcanvas.window="$event.detail == '{{ $name }}' ? (show = false) : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-cloak
    {{ $attributes }}
>
    <div
        class="fixed inset-0 transition-opacity"
        x-show="show"
        x-on:click="show = false"
        x-transition:enter="duration-300 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="duration-300 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-black opacity-75" aria-hidden="true"></div>
    </div>
    <div
        class="{{ $positionClass }} fixed z-50 h-screen w-full overflow-y-auto bg-white"
        role="document"
        aria-labelledby="label-{{ $name }}"
        x-show="show"
        x-transition:enter="{{ $transitionClasses['enter'] }}"
        x-transition:enter-start="{{ $transitionClasses['enter-start'] }}"
        x-transition:enter-end="{{ $transitionClasses['enter-end'] }}"
        x-transition:leave="{{ $transitionClasses['leave'] }}"
        x-transition:leave-start="{{ $transitionClasses['leave-start'] }}"
        x-transition:leave-end="{{ $transitionClasses['leave-end'] }}"
    >
        <div class="mb-4 flex items-center justify-between px-6 pt-6">
            {{ $title ?? 'Offcanvas' }}
            <button
                type="button"
                class="rounded-full bg-transparent p-2 text-black transition-colors hover:bg-neutral-100"
                aria-label="tutup"
                x-on:click="show = false"
            >
                <svg
                    class="size-5 shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        {{ $slot }}
    </div>
</aside>
