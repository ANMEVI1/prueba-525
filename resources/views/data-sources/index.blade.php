@extends('layouts.app')

@section('title', 'Fuentes de Datos Institucionales | Continuidad TI')
@section('page_title', 'Fuentes de Datos')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <x-ui.badge tone="brand" size="xs">Estructura Académica</x-ui.badge>
                <span class="text-xs text-slate-400 font-medium">Bases de Datos Protegidas</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 mt-1">Fuentes de Datos Críticas</h1>
        </div>

        <x-ui.badge :tone="$overview['integrity'] === 'Correcta' ? 'success' : 'warning'" size="md">
            Integridad SQLite: {{ $overview['integrity'] }}
        </x-ui.badge>
    </div>

    <!-- Tables navigation tabs -->
    <div class="flex flex-wrap gap-2 p-1.5 bg-slate-200/70 rounded-xl">
        @foreach ($tables as $key => $label)
            <a href="{{ route('data-sources.index', ['table' => $key]) }}" class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $selectedTable === $key ? 'bg-white text-slate-900 shadow-xs ring-1 ring-slate-900/5' : 'text-slate-600 hover:text-slate-900 hover:bg-white/40' }}">
                <span>{{ $label }}</span>
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] {{ $selectedTable === $key ? 'bg-brand-50 text-brand-700' : 'bg-slate-300/60 text-slate-700' }}">
                    {{ $overview['counts'][$key] ?? 0 }}
                </span>
            </a>
        @endforeach
    </div>

    <!-- Grid: Data Table Preview + Schema Details -->
    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">

        <!-- Table Data Card -->
        <x-ui.card :padding="false">
            <x-slot:title>Muestra de Datos: {{ $tables[$selectedTable] }}</x-slot:title>

            @if (empty($tableData))
                <div class="p-12 text-center text-sm text-slate-500">
                    <x-ui.icon name="database" class="size-10 mx-auto text-slate-300 mb-2" />
                    <p class="font-bold text-slate-700">No hay registros cargados en esta tabla.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                @foreach ($columns as $col)
                                    <th class="px-4 py-3 font-bold whitespace-nowrap">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            @foreach ($tableData as $row)
                                <tr class="hover:bg-slate-50/70 transition">
                                    @foreach ($columns as $col)
                                        <td class="px-4 py-2.5 whitespace-nowrap text-slate-800">
                                            {{ $row[$col] ?? '—' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        <!-- Technical Sidebar -->
        <div class="space-y-6">
            <x-ui.card>
                <h3 class="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100">Parámetros del Motor</h3>
                <dl class="mt-4 space-y-3 text-xs">
                    <div>
                        <dt class="text-slate-400 font-medium">Motor</dt>
                        <dd class="text-slate-800 font-bold">SQLite 3 (WAL mode)</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Ruta del archivo</dt>
                        <dd class="text-slate-800 font-mono text-[11px] break-all bg-slate-50 p-1.5 rounded mt-0.5 border border-slate-200">
                            backup-system/data/demo-institucional.sqlite
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Claves Foráneas</dt>
                        <dd class="text-emerald-700 font-bold">Activadas (PRAGMA foreign_keys = ON)</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Total de Entidades</dt>
                        <dd class="text-slate-800 font-bold">6 tablas relacionales</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Total de Registros Demo</dt>
                        <dd class="text-brand-700 font-bold">{{ array_sum($overview['counts']) }} registros</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <h3 class="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100">Garantía de Integridad</h3>
                <p class="text-xs text-slate-500 mt-3 leading-relaxed">
                    Antes de empaquetar y cifrar el archivo de respaldo, el sistema ejecuta <code class="font-mono text-slate-800 bg-slate-100 px-1 py-0.5 rounded">PRAGMA integrity_check</code>. Si la comprobación falla, el respaldo es abortado inmediatamente.
                </p>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection

