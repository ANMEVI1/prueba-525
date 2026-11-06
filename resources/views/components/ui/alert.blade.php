@props([
    'tone' => 'info', // info, success, warning, danger
    'title' => null,
    'dismissible' => false,
])

@php
    $id = 'alert-' . uniqid();
    $tones = [
        'info' => [
            'box' => 'border-sky-200 bg-sky-50 text-sky-950',
            'icon' => 'activity',
            'iconColor' => 'text-sky-700',
        ],
        'success' => [
            'box' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
            'icon' => 'check-circle',
            'iconColor' => 'text-emerald-700',
        ],
        'warning' => [
            'box' => 'border-amber-200 bg-amber-50 text-amber-950',
            'icon' => 'alert-triangle',
            'iconColor' => 'text-amber-700',
        ],
        'danger' => [
            'box' => 'border-rose-200 bg-rose-50 text-rose-950',
            'icon' => 'x-circle',
            'iconColor' => 'text-rose-700',
        ],
    ];
    $selected = $tones[$tone] ?? $tones['info'];
@endphp

<div id="{{ $id }}" {{ $attributes->merge(['class' => "rounded-xl border px-4 py-3.5 sm:px-5 flex items-start gap-3 {$selected['box']}"]) }} role="alert">
    <div class="mt-0.5 shrink-0 {{ $selected['iconColor'] }}">
        <x-ui.icon :name="$selected['icon']" class="size-5" />
    </div>
    <div class="flex-1 text-sm leading-6">
        @if($title)
            <p class="font-bold mb-0.5">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>
    @if($dismissible)
        <button type="button" data-dismiss-target="#{{ $id }}" class="shrink-0 text-slate-400 hover:text-slate-700 p-1 rounded-md transition" aria-label="Cerrar aviso">
            <x-ui.icon name="x" class="size-4" />
        </button>
    @endif
</div>

