@extends('layouts.app')

@section('title', 'Padrón de Estudiantes | Continuidad TI')
@section('page_title', 'Estudiantes')

@section('content')
<div class="space-y-6">

    <!-- Header & Action bar -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <x-ui.badge tone="brand" size="xs">Gestión Académica</x-ui.badge>
                <span class="text-xs text-slate-400 font-medium">Control Institucional</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 mt-1">Padrón de Estudiantes</h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('estudiantes.seed-demo') }}" class="inline">
                @csrf
                <x-ui.button type="submit" variant="outline" size="md" icon="database" title="Inserta 20 estudiantes de prueba directamente en la base de datos MySQL">
                    Cargar 20 Estudiantes Demo
                </x-ui.button>
            </form>

            <!-- Botón que activa la ventana modal -->
            <x-ui.button type="button" data-modal-open="#modal-crear-estudiante" variant="primary" icon="plus" size="md">
                Nuevo Estudiante
            </x-ui.button>
        </div>
    </div>

    <!-- Main Card with Filter & Table -->
    <x-ui.card :padding="false">
        <div class="p-5 border-b border-slate-100 bg-white">
            <form method="GET" action="{{ route('estudiantes.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <x-ui.icon name="search" class="absolute left-3.5 top-3 size-4 text-slate-400" />
                    <input
                        id="buscar"
                        name="buscar"
                        type="search"
                        value="{{ $buscar }}"
                        placeholder="Buscar por DNI, nombres o carrera profesional..."
                        class="w-full rounded-lg border border-slate-300 bg-white pl-10 pr-4 py-2.5 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10"
                    >
                </div>
                <div class="flex items-center gap-2">
                    <x-ui.button type="submit" variant="primary" size="md">
                        Buscar
                    </x-ui.button>
                    @if ($buscar !== '')
                        <a href="{{ route('estudiantes.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3 font-bold whitespace-nowrap">DNI</th>
                        <th class="px-5 py-3 font-bold">Nombres y Apellidos</th>
                        <th class="px-5 py-3 font-bold">Programa / Carrera</th>
                        <th class="px-5 py-3 font-bold">Correo Institucional</th>
                        <th class="px-5 py-3 text-right font-bold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($estudiantes as $estudiante)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap font-mono text-xs font-bold text-slate-700">
                                {{ $estudiante->dni }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-slate-900 text-xs">
                                <a href="{{ route('estudiantes.show', $estudiante) }}" class="hover:text-brand-700 transition">
                                    {{ $estudiante->nombres }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                    {{ $estudiante->carrera }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600 font-mono">
                                {{ $estudiante->correo }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('estudiantes.show', $estudiante) }}" class="px-2.5 py-1 rounded-md text-xs font-semibold text-slate-700 border border-slate-200 hover:bg-slate-100 transition" title="Ver ficha">
                                        Ver
                                    </a>
                                    <a href="{{ route('estudiantes.edit', $estudiante) }}" class="px-2.5 py-1 rounded-md text-xs font-semibold text-brand-700 border border-brand-200 bg-brand-50 hover:bg-brand-100 transition" title="Editar">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('estudiantes.destroy', $estudiante) }}" class="inline" onsubmit="return confirm('¿Confirmas la eliminación del estudiante {{ $estudiante->nombres }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-semibold text-rose-700 border border-rose-200 hover:bg-rose-50 transition cursor-pointer" title="Eliminar">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                <x-ui.icon name="users" class="size-10 mx-auto text-slate-300 mb-2" />
                                <p class="font-bold text-slate-700">No hay estudiantes que coincidan con la búsqueda.</p>
                                <p class="text-xs text-slate-400 mt-1">Verifica el DNI o el nombre ingresado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($estudiantes->hasPages())
            <div class="p-4 border-t border-slate-100 bg-white">
                {{ $estudiantes->links() }}
            </div>
        @endif
    </x-ui.card>

    <!-- Modal Registrar Estudiante (In-place Modal) -->
    <x-ui.modal
        id="modal-crear-estudiante"
        title="Registrar Nuevo Estudiante"
        description="Completa los datos para incorporar al alumno a la base de datos académica institucional."
        size="lg"
        :open="$errors->any() || request()->has('crear')"
    >
        <form method="POST" action="{{ route('estudiantes.store') }}" class="space-y-6">
            @include('estudiantes._form', ['estudiante' => new \App\Models\Estudiante()])

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-5 border-t border-slate-100">
                <button
                    type="button"
                    data-modal-close="#modal-crear-estudiante"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition text-center cursor-pointer"
                >
                    Cancelar
                </button>
                <x-ui.button type="submit" variant="primary" size="md">
                    Guardar Estudiante
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection
