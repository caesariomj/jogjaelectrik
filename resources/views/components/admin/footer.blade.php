<footer
    {{ $attributes->merge(['class' => 'flex items-center justify-center gap-x-2 text-sm leading-none tracking-tight text-black/70']) }}
>
    <a href="{{ route('home') }}">{{ config('app.name') }}</a>
    <span>&dash;</span>
    <p>{{ date('Y') }}</p>
</footer>
