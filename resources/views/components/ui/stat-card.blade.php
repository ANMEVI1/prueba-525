@props([
    'label',
    'value',
    'hint' => null,
    'icon' => 'activity',
    'tone' => 'brand', // brand, success, warning, neutral, danger
])

@php
    $tones = [
        'brand' => 'bg-sky-50 text-sky-700 border-sky-100',
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-100',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-100',
        'neutral' => 'bg-slate-50 text-slate-700 border-slate-200',
    ];
    $iconTone = $tones[$tone] ?? $tones['brand'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs transition hover:border-slate-300']) }}>
    <div class="flex items-center justify-between gap-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</p>
        <span class="grid size-9 place-items-center rounded-lg border {{ $iconTone }}">
            <x-ui.icon :name="$icon" class="size-5" />
        </span>
    </div>
    <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $value }}</p>
    @if($hint)
        <p class="mt-1 text-xs text-slate-500 font-medium">{{ $hint }}</p>
    @endif
</div>

