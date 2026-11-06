@props([
    'variant' => 'primary', // primary, secondary, danger, outline, ghost, white
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'icon' => null,
    'href' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold transition rounded-lg focus:outline-none focus:ring-4 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed select-none';

    $variants = [
        'primary' => 'bg-brand-700 text-white hover:bg-brand-800 focus:ring-brand-500/25 shadow-sm',
        'secondary' => 'bg-navy-900 text-white hover:bg-navy-800 focus:ring-navy-900/25 shadow-sm',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500/25 shadow-sm',
        'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-900/10 shadow-xs',
        'ghost' => 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:ring-slate-900/10',
        'white' => 'bg-white text-slate-900 hover:bg-slate-100 focus:ring-white/30 shadow-sm',
        'brand-soft' => 'bg-brand-50 text-brand-800 border border-brand-200 hover:bg-brand-100 focus:ring-brand-500/20',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-5 py-3 text-base',
    ];

    $classes = "{$base} " . ($variants[$variant] ?? $variants['primary']) . " " . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <x-ui.icon :name="$icon" class="{{ $size === 'sm' ? 'size-3.5' : 'size-4' }}" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <x-ui.icon :name="$icon" class="{{ $size === 'sm' ? 'size-3.5' : 'size-4' }}" />
        @endif
        {{ $slot }}
    </button>
@endif

