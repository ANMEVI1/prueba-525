@extends('layouts.app')

@section('title', 'Editar Estudiante | Registro')
@section('page_title', 'Editar Estudiante')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('estudiantes.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
            <x-ui.icon name="arrow-left" class="size-4" /> Volver al padrón de estudiantes
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <span class="text-xs font-bold uppercase tracking-wider text-brand-700">Actualización</span>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-950 mt-1">Editar Estudiante</h1>
            <p class="text-xs text-slate-500 mt-0.5">Modificando los datos de <strong class="text-slate-800">{{ $estudiante->nombres }}</strong>.</p>
        </div>

        <form method="POST" action="{{ route('estudiantes.update', $estudiante) }}" class="space-y-6">
            @method('PUT')
            @include('estudiantes._form')

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="{{ route('estudiantes.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition text-center">
                    Cancelar
                </a>
                <x-ui.button type="submit" variant="primary" size="md">
                    Actualizar Estudiante
                </x-ui.button>
            </div>
        </form>
    </div>
</div>
@endsection
