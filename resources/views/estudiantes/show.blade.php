@extends('layouts.app')

@section('title', 'Ficha del Estudiante | Continuidad TI')
@section('page_title', 'Ficha de Estudiante')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('estudiantes.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
            <x-ui.icon name="arrow-left" class="size-4" /> Volver al listado
        </a>

        <div class="flex items-center gap-2">
            <x-ui.button href="{{ route('estudiantes.edit', $estudiante) }}" variant="outline" size="sm">
                Editar
            </x-ui.button>
            <form method="POST" action="{{ route('estudiantes.destroy', $estudiante) }}" onsubmit="return confirm('¿Confirmas la eliminación de este estudiante?');" class="inline">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" size="sm">
                    Eliminar
                </x-ui.button>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
        <div class="flex items-center gap-4 pb-6 border-b border-slate-100">
            <div class="size-14 rounded-2xl bg-brand-50 text-brand-700 font-extrabold text-xl grid place-items-center border border-brand-200">
                {{ strtoupper(substr($estudiante->nombres, 0, 1)) }}
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-brand-700">Expediente de Alumno</span>
                <h1 class="text-xl sm:text-2xl font-black text-slate-950 mt-0.5">{{ $estudiante->nombres }}</h1>
                <p class="text-xs text-slate-500 font-medium">Registrado el {{ $estudiante->created_at?->format('d/m/Y') }}</p>
            </div>
        </div>

        <dl class="grid gap-6 sm:grid-cols-2 pt-6 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <dt class="font-bold uppercase tracking-wider text-slate-400">DNI</dt>
                <dd class="mt-1 font-mono font-bold text-base text-slate-900">{{ $estudiante->dni }}</dd>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <dt class="font-bold uppercase tracking-wider text-slate-400">Programa / Carrera</dt>
                <dd class="mt-1 font-bold text-sm text-slate-900 leading-snug">{{ $estudiante->carrera }}</dd>
            </div>

            <div class="sm:col-span-2 p-4 rounded-xl bg-slate-50 border border-slate-100">
                <dt class="font-bold uppercase tracking-wider text-slate-400">Correo Electrónico Institucional</dt>
                <dd class="mt-1 font-mono font-semibold text-sm text-brand-700">{{ $estudiante->correo }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
