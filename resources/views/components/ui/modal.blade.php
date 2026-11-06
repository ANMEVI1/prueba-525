@props([
    'id',
    'title' => null,
    'description' => null,
    'size' => 'lg', // sm, md, lg, xl
    'open' => false,
])

@php
    $sizes = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['lg'];
@endphp

<div
    id="{{ $id }}"
    class="modal-container fixed inset-0 z-50 {{ $open ? '' : 'hidden' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
>
    <!-- Dark blurred backdrop -->
    <div class="modal-backdrop fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"></div>

    <!-- Center wrapper -->
    <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 lg:p-8 flex min-h-full items-center justify-center pointer-events-none">
        <!-- Modal Card -->
        <div class="pointer-events-auto relative w-full {{ $sizeClass }} rounded-2xl bg-white border border-slate-200/90 shadow-2xl transition-all my-8 overflow-hidden">
            @if ($title || $description)
                <div class="flex items-start justify-between gap-4 p-5 sm:p-6 border-b border-slate-100 bg-white">
                    <div>
                        @if ($title)
                            <h2 id="{{ $id }}-title" class="text-xl font-extrabold tracking-tight text-slate-950">
                                {{ $title }}
                            </h2>
                        @endif
                        @if ($description)
                            <p class="mt-1 text-xs text-slate-500 font-medium">
                                {{ $description }}
                            </p>
                        @endif
                    </div>
                    <button
                        type="button"
                        data-modal-close="#{{ $id }}"
                        class="rounded-lg p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition focus:outline-none cursor-pointer"
                        aria-label="Cerrar ventana modal"
                    >
                        <x-ui.icon name="x" class="size-5" />
                    </button>
                </div>
            @endif

            <div class="p-5 sm:p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

