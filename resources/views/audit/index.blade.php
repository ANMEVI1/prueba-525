@extends('layouts.app')

@section('title', 'Auditoría de Eventos | Continuidad TI')
@section('page_title', 'Auditoría de Eventos')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <x-ui.badge tone="brand" size="xs">Trazabilidad y Seguridad</x-ui.badge>
                <span class="text-xs text-slate-400 font-medium">Registros Inmutables</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 mt-1">Bitácora de Auditoría</h1>
        </div>

        <x-ui.badge tone="neutral" size="md">
            Total Eventos: {{ $totalEvents }}
        </x-ui.badge>
    </div>

    <!-- Search & Filter Card -->
    <x-ui.card :padding="false">
        <div class="p-5 border-b border-slate-100 bg-white">
            <form method="GET" action="{{ route('audit.index') }}" class="grid gap-3 sm:grid-cols-[1fr_220px_auto]">
                <div class="relative">
                    <x-ui.icon name="search" class="absolute left-3.5 top-3 size-4 text-slate-400" />
                    <input
                        type="search"
                        name="buscar"
                        value="{{ $search }}"
                        placeholder="Buscar por descripción, usuario o IP..."
                        class="w-full rounded-lg border border-slate-300 bg-white pl-10 pr-4 py-2.5 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10"
                    >
                </div>

                <div>
                    <select
                        name="action"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-xs text-slate-900 outline-none transition focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10"
                    >
                        <option value="">Todos los eventos</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action->value }}" @selected($actionFilter === $action->value)>
                                {{ $action->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <x-ui.button type="submit" variant="primary" size="md">
                        Filtrar
                    </x-ui.button>
                    @if ($search !== '' || !empty($actionFilter))
                        <a href="{{ route('audit.index') }}" class="inline-flex items-center justify-center px-3 py-2.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Audit Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3 font-bold whitespace-nowrap">Fecha / Hora</th>
                        <th class="px-5 py-3 font-bold">Tipo de Acción</th>
                        <th class="px-5 py-3 font-bold">Usuario</th>
                        <th class="px-5 py-3 font-bold">Descripción del Evento</th>
                        <th class="px-5 py-3 font-bold whitespace-nowrap">Dirección IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-slate-600 font-mono">
                                {{ $log->created_at?->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <x-ui.badge :tone="$log->action->tone()" size="xs">
                                    {{ $log->action->label() }}
                                </x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-bold text-slate-800">
                                {{ $log->user?->name ?? 'Sistema Automático' }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-800">
                                <p class="font-medium">{{ $log->description }}</p>
                                @if ($log->properties)
                                    <div class="mt-1 font-mono text-[10px] text-slate-500 bg-slate-50 p-1 rounded max-w-lg truncate">
                                        {{ json_encode($log->properties, JSON_UNESCAPED_UNICODE) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-slate-500 font-mono">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                <x-ui.icon name="clock" class="size-10 mx-auto text-slate-300 mb-2" />
                                <p class="font-bold text-slate-700">No se encontraron eventos con los filtros aplicados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="p-4 border-t border-slate-100 bg-white">
                {{ $logs->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection

