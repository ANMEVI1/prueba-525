@php
    $fieldId = $id ?? $name;
    $fieldLabel = $label ?? 'Contraseña';
    $fieldAutocomplete = $autocomplete ?? 'current-password';
@endphp

<div>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">
        {{ $fieldLabel }}
    </label>
    <div class="relative">
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="password"
            @if (!empty($required)) required @endif
            autocomplete="{{ $fieldAutocomplete }}"
            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 pr-11 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10"
        >
        <button
            type="button"
            data-password-toggle="{{ $fieldId }}"
            aria-label="Mostrar contraseña"
            aria-pressed="false"
            class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-400 hover:text-slate-700 focus:outline-none transition"
        >
            <span data-eye-open>
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" /></svg>
            </span>
            <span data-eye-closed class="hidden">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 2 20 20" /><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" /><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" /><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" /></svg>
            </span>
        </button>
    </div>
    @error($name)
        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
    @enderror
</div>
