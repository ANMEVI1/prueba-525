@extends('layouts.app')

@section('title', 'Respaldos y Recuperación | Continuidad TI')
@section('page_title', 'Respaldos y Recuperación')

@section('content')
<div class="space-y-6">

    <!-- Header Section (Clean, spacious, focused) -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-block size-2 rounded-full bg-emerald-500"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Módulo de Seguridad Institucional</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-950 mt-1">
                Copias de Seguridad
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('backups.runbook') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-xs">
                <x-ui.icon name="terminal" class="size-4 text-brand-600" />
                Manual de Restauración
            </a>

            <button type="button" data-modal-open="#modal-create-backup" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-brand-600 text-white hover:bg-brand-700 transition shadow-xs focus:ring-2 focus:ring-brand-500">
                <x-ui.icon name="plus" class="size-4" />
                Nueva Copia de Seguridad
            </button>
        </div>
    </div>

    <!-- Quick Metrics (Clean, without verbose micro-copy) -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card
            label="Total de Copias"
            :value="$metrics['total']"
            icon="server"
            tone="brand"
        />
        <x-ui.stat-card
            label="Tasa de Integridad"
            :value="($metrics['successRate'] ?? 100) . '%'"
            icon="shield-check"
            tone="success"
        />
        <x-ui.stat-card
            label="Pruebas Realizadas"
            :value="$metrics['restored']"
            icon="rotate-ccw"
            tone="brand"
        />
        <x-ui.stat-card
            label="Tiempo RTO Obtenido"
            :value="$metrics['latestRestoreMs'] ? $metrics['latestRestoreMs'] . ' ms' : '—'"
            icon="clock"
            tone="warning"
        />
    </div>

    <!-- Clean Tab Navigation (Intuitive progressive disclosure) -->
    <div class="flex items-center justify-between border-b border-slate-200">
        <nav class="flex space-x-2" data-tab-group="backup-tabs" aria-label="Secciones de Respaldos">
            <button
                type="button"
                data-tab-button="tab-history"
                class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition bg-brand-600 text-white shadow-xs"
                aria-selected="true"
            >
                <span class="inline-flex items-center gap-2">
                    <x-ui.icon name="server" class="size-4" />
                    Historial de Copias ({{ $metrics['total'] }})
                </span>
            </button>

            <button
                type="button"
                data-tab-button="tab-schedule"
                class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                aria-selected="false"
            >
                <span class="inline-flex items-center gap-2">
                    <x-ui.icon name="clock" class="size-4" />
                    Configuración y Automatización
                    @if ($scheduleConfig['enabled'])
                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    @endif
                </span>
            </button>

            <button
                type="button"
                data-tab-button="tab-server"
                class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                aria-selected="false"
            >
                <span class="inline-flex items-center gap-2">
                    <x-ui.icon name="cpu" class="size-4" />
                    Diagnóstico de Servidor
                </span>
            </button>
        </nav>

        <span class="text-xs text-slate-400 font-medium hidden sm:inline">
            Cifrado AES-256-CBC · Firma HMAC
        </span>
    </div>

    <!-- ============================================================= -->
    <!-- TAB 1: HISTORIAL DE COPIAS (VISTA PRINCIPAL)                  -->
    <!-- ============================================================= -->
    <div id="tab-history" data-tab-panel="backup-tabs" class="space-y-4">
        <x-ui.card :padding="false">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-slate-100 bg-slate-50/60 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-bold text-slate-600">Mostrar:</span>
                    <a href="{{ route('backups.index') }}" class="px-3 py-1 rounded-lg font-semibold transition {{ empty($statusFilter) ? 'bg-brand-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                        Todos ({{ $metrics['total'] }})
                    </a>
                    <a href="{{ route('backups.index', ['status' => 'success']) }}" class="px-3 py-1 rounded-lg font-semibold transition {{ $statusFilter === 'success' ? 'bg-brand-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                        Correctos ({{ $metrics['successful'] }})
                    </a>
                    <a href="{{ route('backups.index', ['status' => 'failed']) }}" class="px-3 py-1 rounded-lg font-semibold transition {{ $statusFilter === 'failed' ? 'bg-brand-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                        Fallidos ({{ $metrics['failed'] }})
                    </a>
                </div>

                <div class="text-xs text-slate-500">
                    Almacenamiento privado local: <code class="font-mono text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">storage/app/backups/</code>
                </div>
            </div>

            <!-- Table of Backups -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5 font-bold">Copia / Registro</th>
                            <th class="px-6 py-3.5 font-bold">Base de Datos</th>
                            <th class="px-6 py-3.5 font-bold">Origen</th>
                            <th class="px-6 py-3.5 font-bold">Tamaño</th>
                            <th class="px-6 py-3.5 font-bold">Estado de Integridad</th>
                            <th class="px-6 py-3.5 font-bold">Copia Espejo</th>
                            <th class="px-6 py-3.5 text-right font-bold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($backups as $backup)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-900">#{{ $backup->id }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $backup->created_at?->format('d/m/Y H:i:s') }}</p>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-800 text-xs">{{ $backup->database_name }}</p>
                                    <span class="inline-block mt-0.5 text-[10px] font-mono uppercase px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $backup->database_type ? strtoupper($backup->database_type) : 'MYSQL' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($backup->is_automated)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                            <x-ui.icon name="clock" class="size-3" /> Madrugada (Auto)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            Manual
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-slate-700">
                                    {{ $backup->formattedSize() }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ui.badge :tone="$backup->status->tone()" size="xs">
                                        {{ $backup->status->label() }}
                                    </x-ui.badge>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($backup->replicated)
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                            <x-ui.icon name="check-circle" class="size-3.5 text-emerald-600" /> Sincronizada
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">Local</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                    @if ($backup->isRestorable())
                                        <form method="POST" action="{{ route('backups.restore', $backup) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                            <x-ui.button type="submit" variant="outline" size="sm" icon="rotate-ccw">
                                                Restaurar
                                            </x-ui.button>
                                        </form>
                                    @elseif ($backup->wasRestored())
                                        <span class="text-xs font-semibold text-emerald-700 inline-flex items-center gap-1">
                                            <x-ui.icon name="check-circle" class="size-4" /> Restaurada ({{ $backup->restore_duration_ms }} ms)
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center text-slate-500">
                                    <x-ui.icon name="server" class="size-10 mx-auto text-slate-300 mb-2" />
                                    <p class="font-bold text-slate-700">No hay copias de seguridad registradas.</p>
                                    <p class="text-xs text-slate-400 mt-1">Pulsa en "Nueva Copia de Seguridad" para generar un respaldo cifrado.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($backups->hasPages())
                <div class="p-4 border-t border-slate-100 bg-white">
                    {{ $backups->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>

    <!-- ============================================================= -->
    <!-- TAB 2: CONFIGURACIÓN DE GUARDADO Y AUTOMATIZACIÓN             -->
    <!-- ============================================================= -->
    <div id="tab-schedule" data-tab-panel="backup-tabs" class="hidden space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-8 shadow-xs max-w-4xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Configuración de Guardado y Automatización</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Destino físico, estructura de carpetas, formatos y frecuencia de ejecución.</p>
                </div>

                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('backups.run-now') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl border border-brand-200 text-brand-700 bg-brand-50 hover:bg-brand-100 transition shadow-xs" title="Prueba el respaldo automático con las opciones actuales">
                            <x-ui.icon name="rotate-ccw" class="size-3.5 text-brand-600" />
                            Ejecutar Respaldo Automático Ahora
                        </button>
                    </form>

                    <x-ui.badge :tone="$scheduleConfig['enabled'] ? 'success' : 'neutral'" size="sm">
                        {{ $scheduleConfig['enabled'] ? 'Activo' : 'Inactivo' }}
                    </x-ui.badge>
                </div>
            </div>

            <form method="POST" action="{{ route('backups.schedule') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                <!-- SECCIÓN 1: DESTINOS DE GUARDADO Y FORMATO -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                        1. Destino de Guardado y Formato
                    </h3>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <!-- Destino Primario Fijo con Explorador de Carpetas -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="form_storage_destination" class="block font-bold text-slate-800 text-xs">
                                    Carpeta de Destino (Ruta Fija)
                                </label>
                                <span class="text-[11px] text-slate-400 font-mono">Disco local / Red</span>
                            </div>

                            <div class="flex rounded-xl shadow-xs overflow-hidden border border-slate-200 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500 bg-white">
                                <input
                                    type="text"
                                    id="form_storage_destination"
                                    name="storage_destination"
                                    value="{{ $scheduleConfig['storage_destination'] ?? 'storage/app/backups' }}"
                                    placeholder="storage/app/backups o D:\Respaldos_TI"
                                    required
                                    class="flex-1 text-xs font-mono px-3.5 py-2.5 text-slate-800 bg-transparent border-0 focus:outline-hidden focus:ring-0"
                                >
                                <button
                                    type="button"
                                    id="btn-browse-folder"
                                    data-modal-open="#modal-folder-picker"
                                    data-action="browse-folder"
                                    data-target-input="#form_storage_destination"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 transition border-l border-slate-200 cursor-pointer active:scale-95"
                                    title="Abrir explorador de archivos para elegir carpeta"
                                >
                                    <x-ui.icon name="folder" class="size-4 text-brand-600" />
                                    <span>Examinar...</span>
                                </button>
                            </div>

                            <!-- Presets Rápidos -->
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-0.5">Rutas rápidas:</span>
                                @foreach ($folderPresets as $preset)
                                    <button
                                        type="button"
                                        data-preset-folder="{{ $preset['path'] }}"
                                        data-target-input="#form_storage_destination"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono bg-slate-100 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-300 text-slate-600 border border-slate-200 transition cursor-pointer"
                                        title="Establecer: {{ $preset['path'] }}"
                                    >
                                        {{ $preset['label'] }}
                                    </button>
                                @endforeach
                            </div>

                            <div id="folder-picker-status" class="hidden mt-2 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-1.5 flex items-center gap-2">
                                <x-ui.icon name="check-circle" class="size-4 text-emerald-600 shrink-0" />
                                <span id="folder-picker-message"></span>
                            </div>
                        </div>

                        <!-- Estructura de Carpetas -->
                        <div>
                            <label for="form_folder_structure" class="block font-bold text-slate-800 text-xs mb-1.5">
                                Organización de Carpetas
                            </label>
                            <select id="form_folder_structure" name="folder_structure" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                <option value="single_folder" {{ ($scheduleConfig['folder_structure'] ?? 'single_folder') === 'single_folder' ? 'selected' : '' }}>
                                    Carpeta directa (todos los respaldos en el destino)
                                </option>
                                <option value="date_subfolders" {{ ($scheduleConfig['folder_structure'] ?? '') === 'date_subfolders' ? 'selected' : '' }}>
                                    Crear subcarpeta por fecha (YYYY-MM-DD)
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <!-- Formato de Guardado -->
                        <div>
                            <label for="form_save_format" class="block font-bold text-slate-800 text-xs mb-1.5">
                                Formato de Exportación
                            </label>
                            <select id="form_save_format" name="save_format" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                <option value="both" {{ ($scheduleConfig['save_format'] ?? 'both') === 'both' ? 'selected' : '' }}>
                                    Ambos: Cifrado institucional (.enc) + Archivo plano (.sql)
                                </option>
                                <option value="encrypted" {{ ($scheduleConfig['save_format'] ?? '') === 'encrypted' ? 'selected' : '' }}>
                                    Solo Cifrado institucional (.enc con AES-256 y GZIP)
                                </option>
                                <option value="direct_sql" {{ ($scheduleConfig['save_format'] ?? '') === 'direct_sql' ? 'selected' : '' }}>
                                    Solo Archivo plano (.sql directo sin cifrar)
                                </option>
                            </select>
                        </div>

                        <!-- Base de Datos Asignada -->
                        <div>
                            <label for="form_database" class="block font-bold text-slate-800 text-xs mb-1.5">
                                Base de Datos Asignada
                            </label>
                            <input
                                type="text"
                                id="form_database"
                                name="database"
                                value="{{ $scheduleConfig['database'] ?? 'iesp_continuidad_demo' }}"
                                required
                                class="w-full text-xs font-mono font-bold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                            >
                        </div>
                    </div>

                    <!-- Replicación Secundaria -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
                        <label for="form_replicate" class="font-bold text-slate-900 text-xs cursor-pointer">
                            Replicar Copia Espejo en Almacenamiento Secundario
                        </label>
                        <input type="checkbox" id="form_replicate" name="replicate_secondary" value="1" {{ $scheduleConfig['replicate_secondary'] ? 'checked' : '' }} class="size-5 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    </div>
                    <input type="hidden" name="secondary_path" value="{{ $scheduleConfig['secondary_path'] ?? 'backups/secondary' }}">
                </div>

                <!-- SECCIÓN 2: AUTOMATIZACIÓN Y TIEMPOS -->
                <div class="space-y-5 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                            2. Frecuencia y Automatización Periódica
                        </h3>
                    </div>

                    <!-- Tarjeta de Ejecución Inmediata (¿Quieres que empiece ya ahora?) -->
                    <div class="p-4 rounded-xl bg-gradient-to-r from-brand-50/80 to-slate-50 border border-brand-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-brand-600 animate-pulse"></span>
                                <h4 class="text-xs font-bold text-brand-950 uppercase tracking-wider">¿Deseas iniciar un respaldo en este instante?</h4>
                            </div>
                            <p class="text-xs text-slate-600">Ejecuta la extracción, compresión cifrada AES-256 y replicación inmediata con los parámetros actuales sin esperar la rutina automática.</p>
                        </div>
                        <button
                            type="submit"
                            formaction="{{ route('backups.run-now') }}"
                            formmethod="POST"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-xs transition active:scale-95 cursor-pointer whitespace-nowrap shrink-0"
                        >
                            <x-ui.icon name="play" class="size-3.5 fill-current" />
                            <span>Iniciar Respaldo Ahora Mismo</span>
                        </button>
                    </div>

                    <!-- Switch de Activación de Rutina Automática -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <label for="form_enabled" class="font-bold text-slate-900 text-xs cursor-pointer block">
                                Activar Rutina Automática en Segundo Plano
                            </label>
                            <span class="text-[11px] text-slate-500">Ejecución desatendida mediante cron / scheduler institucional</span>
                        </div>
                        <input type="checkbox" id="form_enabled" name="enabled" value="1" {{ $scheduleConfig['enabled'] ? 'checked' : '' }} class="size-5 rounded text-brand-600 focus:ring-brand-500 border-slate-300 cursor-pointer">
                    </div>

                    <div class="grid gap-6 sm:grid-cols-3">
                        <!-- Frecuencia -->
                        <div>
                            <label for="form_frequency" class="block font-bold text-slate-800 text-xs mb-1.5">Frecuencia de Ejecución</label>
                            <select id="form_frequency" name="frequency" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                <option value="daily" {{ $scheduleConfig['frequency'] === 'daily' ? 'selected' : '' }}>Diario (Hora fija de madrugada)</option>
                                <option value="twice_daily" {{ $scheduleConfig['frequency'] === 'twice_daily' ? 'selected' : '' }}>Cada 12 horas</option>
                                <option value="hourly" {{ $scheduleConfig['frequency'] === 'hourly' ? 'selected' : '' }}>Cada hora</option>
                                <option value="every_30_mins" {{ $scheduleConfig['frequency'] === 'every_30_mins' ? 'selected' : '' }}>Cada 30 minutos</option>
                                <option value="every_15_mins" {{ $scheduleConfig['frequency'] === 'every_15_mins' ? 'selected' : '' }}>Cada 15 minutos</option>
                                <option value="every_minute" {{ $scheduleConfig['frequency'] === 'every_minute' ? 'selected' : '' }}>Cada minuto (Demostración)</option>
                            </select>
                        </div>

                        <!-- Hora de Ejecución (adaptable según frecuencia) -->
                        <div id="container_execution_time">
                            <label for="form_time" class="block font-bold text-slate-800 text-xs mb-1.5">Hora de Inicio</label>
                            <input
                                type="time"
                                id="form_time"
                                name="time"
                                value="{{ $scheduleConfig['time'] ?? '02:00' }}"
                                class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition"
                            >
                            <span id="time_interval_notice" class="hidden text-[10px] text-slate-400 mt-1 block font-medium">Intervalo periódico activo; no requiere hora fija.</span>
                        </div>

                        <!-- Retención de Copias (Días) con soporte para dejar en blanco -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="form_retention" class="block font-bold text-slate-800 text-xs">Conservar Copias (Días)</label>
                                <span class="text-[10px] text-slate-400">Opcional</span>
                            </div>
                            <input
                                type="number"
                                id="form_retention"
                                name="retention_days"
                                min="0"
                                max="365"
                                value="{{ $scheduleConfig['retention_days'] ?? '' }}"
                                placeholder="Ilimitado (dejar en blanco)"
                                class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                            >
                            <span class="text-[10px] text-slate-400 mt-1 block">En blanco = Sin límite (nunca se borran copias).</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-t border-slate-100">
                    <div class="text-xs text-slate-500 space-y-0.5">
                        <p>Última ejecución: <strong class="text-slate-800">{{ $scheduleConfig['last_run_at'] ? \Carbon\Carbon::parse($scheduleConfig['last_run_at'])->format('d/m/Y H:i:s') : 'Sin registros previos' }}</strong></p>
                        <p class="text-[11px] text-slate-400">
                            Depuración: <strong class="text-slate-700">{{ $scheduleConfig['retention_days'] ? $scheduleConfig['retention_days'].' días de retención' : 'Sin límite (conservar todas)' }}</strong>
                        </p>
                    </div>

                    <x-ui.button type="submit" variant="primary" size="md">
                        Guardar Configuración y Horarios
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- TAB 3: DIAGNÓSTICO DEL SERVIDOR (CLARO Y ACCESIBLE)          -->
    <!-- ============================================================= -->
    <div id="tab-server" data-tab-panel="backup-tabs" class="hidden space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-8 shadow-xs max-w-4xl space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900">Entorno Local y Diagnóstico</h2>

                <x-ui.badge :tone="$detection['mysql_available'] ? 'success' : 'danger'" size="sm">
                    {{ $detection['mysql_available'] ? 'Servidor MySQL Conectado' : 'MySQL No Disponible' }}
                </x-ui.badge>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block">Stack de Desarrollo</span>
                    <p class="text-base font-bold text-slate-900 mt-1">{{ $detection['stack'] }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ PHP_OS }} · PHP 8.3</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block">Servicio de Base de Datos</span>
                    <p class="text-base font-bold text-slate-900 mt-1">127.0.0.1:{{ $detection['mysql_port'] }}</p>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $detection['mysql_version'] ?? 'Inactivo' }}</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block">Herramienta de Extracción</span>
                    <p class="text-base font-bold text-slate-900 mt-1">{{ $detection['mysqldump_path'] ? 'mysqldump binario' : 'Motor PDO Nativo' }}</p>
                    <p class="text-xs text-emerald-600 font-medium mt-0.5">Optimizado DDL + DML</p>
                </div>
            </div>

            <!-- Discovered Databases -->
            <div class="space-y-2 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                    Bases de Datos de Usuario Descubiertas ({{ count($detection['databases']) }})
                </h3>

                <div class="flex flex-wrap gap-2 pt-2">
                    @forelse ($detection['databases'] as $dbName)
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-mono font-medium {{ $dbName === 'iesp_continuidad_demo' ? 'bg-brand-50 text-brand-700 border border-brand-300 font-bold' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                            <x-ui.icon name="database" class="size-3.5 text-slate-400" />
                            {{ $dbName }}
                            @if ($dbName === 'iesp_continuidad_demo')
                                <span class="size-2 rounded-full bg-brand-600" title="Base Principal"></span>
                            @endif
                        </span>
                    @empty
                        <p class="text-xs text-slate-400 italic">No se detectaron bases de datos de usuario en MySQL.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: NUEVA COPIA DE SEGURIDAD (EN LA MISMA INTERFAZ)        -->
    <!-- ============================================================= -->
    <x-ui.modal id="modal-create-backup" title="Generar Nueva Copia de Seguridad" max-width="md">
        <form method="POST" action="{{ route('backups.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

            <div>
                <label for="select_modal_database" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Selecciona la base de datos a respaldar:
                </label>
                <select id="select_modal_database" name="database" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-slate-800 focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    @if ($detection['mysql_available'])
                        <optgroup label="Bases de Datos MySQL (Laragon / XAMPP)">
                            @if (in_array('iesp_continuidad_demo', $detection['databases']))
                                <option value="iesp_continuidad_demo" selected>iesp_continuidad_demo (Base Principal del Proyecto)</option>
                            @endif
                            @foreach ($detection['databases'] as $dbName)
                                @if ($dbName !== 'iesp_continuidad_demo')
                                    <option value="{{ $dbName }}">{{ $dbName }}</option>
                                @endif
                            @endforeach
                        </optgroup>
                    @endif
                    <optgroup label="Entorno Académico Ficticio">
                        <option value="sqlite:demo-institucional" {{ !$detection['mysql_available'] ? 'selected' : '' }}>
                            Base SQLite Aislada (demo-institucional.sqlite)
                        </option>
                    </optgroup>
                </select>
            </div>

            <div class="flex items-center justify-between text-xs font-semibold text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200">
                <span class="flex items-center gap-1.5">
                    <x-ui.icon name="lock" class="size-4 text-emerald-600" />
                    Cifrado AES-256 + HMAC
                </span>
                <span class="text-slate-500 font-mono text-[11px]">GZIP-9 · Espejo OK</span>
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
                <x-ui.button type="button" variant="outline" size="sm" data-modal-close>
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" size="sm" icon="plus">
                    Iniciar Respaldo Inmediato
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================= -->
    <!-- MODAL: SELECTOR Y EXPLORADOR DE CARPETAS                      -->
    <!-- ============================================================= -->
    <x-ui.modal id="modal-folder-picker" title="Seleccionar Carpeta de Destino" max-width="lg">
        <div class="space-y-5">
            <!-- Botones de Acción de Explorador -->
            <div class="grid gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    data-action="browse-folder-native"
                    class="flex items-center gap-3 p-3.5 rounded-xl border border-brand-200 bg-brand-50 hover:bg-brand-100 transition text-left cursor-pointer group"
                >
                    <div class="size-10 rounded-lg bg-brand-600 text-white grid place-items-center shrink-0 shadow-xs">
                        <x-ui.icon name="folder" class="size-5" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-brand-950">Explorador de Windows</p>
                        <p class="text-[11px] text-brand-700">Abre el diálogo nativo del sistema</p>
                    </div>
                </button>

                <button
                    type="button"
                    data-action="browse-folder-html5"
                    class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition text-left cursor-pointer group"
                >
                    <div class="size-10 rounded-lg bg-slate-800 text-white grid place-items-center shrink-0 shadow-xs">
                        <x-ui.icon name="monitor" class="size-5" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-900">Selector del Navegador</p>
                        <p class="text-[11px] text-slate-500">Selecciona carpeta directamente</p>
                    </div>
                </button>
            </div>

            <!-- Unidades Locales Detectadas -->
            <div class="space-y-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                    Unidades de Almacenamiento del Servidor
                </h4>
                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($systemDrives as $drive)
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-white space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                                    <x-ui.icon name="hard-drive" class="size-4 text-slate-500" />
                                    {{ $drive['label'] }}
                                </span>
                                @if ($drive['free_gb'] !== null)
                                    <span class="text-[11px] font-mono text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                        {{ $drive['free_gb'] }} GB libres
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <button
                                    type="button"
                                    data-set-destination="{{ $drive['drive'] }}\Respaldos_TI"
                                    class="text-[11px] font-mono px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-brand-50 hover:text-brand-700 border border-slate-200 text-slate-700 transition cursor-pointer"
                                >
                                    {{ $drive['drive'] }}\Respaldos_TI
                                </button>
                                @if ($drive['drive'] === 'C:')
                                    <button
                                        type="button"
                                        data-set-destination="C:\laragon\backups"
                                        class="text-[11px] font-mono px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-brand-50 hover:text-brand-700 border border-slate-200 text-slate-700 transition cursor-pointer"
                                    >
                                        C:\laragon\backups
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        data-set-destination="{{ $drive['drive'] }}\Backups"
                                        class="text-[11px] font-mono px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-brand-50 hover:text-brand-700 border border-slate-200 text-slate-700 transition cursor-pointer"
                                    >
                                        {{ $drive['drive'] }}\Backups
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-3 text-xs text-slate-500">Unidades detectadas en el sistema.</div>
                    @endforelse
                </div>
            </div>

            <!-- Previsualización de Ruta y Confirmación -->
            <div class="pt-2 border-t border-slate-100 space-y-3">
                <label for="modal_custom_path_input" class="block text-xs font-bold text-slate-700">
                    Ruta a Establecer:
                </label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        id="modal_custom_path_input"
                        value="{{ $scheduleConfig['storage_destination'] ?? 'storage/app/backups' }}"
                        class="flex-1 text-xs font-mono rounded-xl border border-slate-200 px-3.5 py-2 text-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                    >
                    <button
                        type="button"
                        id="btn-apply-modal-path"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-xs transition cursor-pointer"
                    >
                        Aplicar
                    </button>
                </div>
            </div>

            <div class="pt-3 flex items-center justify-end border-t border-slate-100">
                <x-ui.button type="button" variant="outline" size="sm" data-modal-close>
                    Cerrar
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

</div>
@endsection
