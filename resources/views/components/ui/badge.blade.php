@props([
    'tone' => 'neutral', // success, danger, warning, brand, neutral
    'dot' => true,
    'size' => 'sm',
])

@php
    $tones = [
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
        'danger' => 'bg-rose-50 text-rose-800 border-rose-200/80',
        'warning' => 'bg-amber-50 text-amber-900 border-amber-200/80',
        'brand' => 'bg-sky-50 text-sky-800 border-sky-200/80',
        'neutral' => 'bg-slate-100 text-slate-700 border-slate-200',
    ];

    $dots = [
        'success' => 'bg-emerald-500',
        'danger' => 'bg-rose-500',
        'warning' => 'bg-amber-500',
        'brand' => 'bg-sky-500',
        'neutral' => 'bg-slate-400',
    ];

    $sizes = [
        'xs' => 'text-[11px] px-2 py-0.5',
        'sm' => 'text-xs px-2.5 py-1',
        'md' => 'text-sm px-3 py-1.5',
    ];

    $badgeTone = $tones[$tone] ?? $tones['neutral'];
    $badgeDot = $dots[$tone] ?? $dots['neutral'];
    $badgeSize = $sizes[$size] ?? $sizes['sm'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-semibold rounded-full border {$badgeTone} {$badgeSize}"]) }}>
    @if($dot)
        <span class="size-1.5 rounded-full {{ $badgeDot }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>

