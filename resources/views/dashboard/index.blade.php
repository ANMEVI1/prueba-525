@extends('layouts.app')

@section('title', 'Panel de Control | Continuidad TI')
@section('page_title', 'Resumen Ejecutivo')

@section('content')
<div class="space-y-6">

    <!-- Hero Section (Clean, real, dynamic) -->
    <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/90 shadow-xs">
        <div class="max-w-2xl">
            <div class="flex items-center gap-2">
                <span class="inline-block size-2 rounded-full bg-emerald-500"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">IESP Cap. FAP José Abelardo Quiñones</span>
            </div>
            <h1 class="mt-1 text-2xl sm:text-3xl font-black tracking-tight text-slate-950">
                Continuidad Operativa y Respaldos
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="button" data-modal-open="#modal-dashboard-backup" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl bg-brand-600 text-white hover:bg-brand-700 transition shadow-xs focus:ring-2 focus:ring-brand-500">
                <x-ui.icon name="plus" class="size-4" />
                Respaldar Base de Datos
            </button>

            @if ($restorableDemoBackup)
                <form method="POST" action="{{ route('backups.restore', $restorableDemoBackup) }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                    <x-ui.button type="submit" variant="outline" icon="rotate-ccw" size="md">
                        Restaurar Copia
                    </x-ui.button>
                </form>
            @endif
        </div>
    </div>

    <!-- Stat Metrics Grid (Clean, without verbose micro-copy) -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="Respaldos Exitosos"
            :value="$metrics['successful']"
            icon="shield-check"
            tone="success"
        />

        <x-ui.stat-card
            label="Tiempo RTO Obtenido"
            :value="$metrics['latestRestoreMs'] ? $metrics['latestRestoreMs'] . ' ms' : 'Sin medir aún'"
            :description="$metrics['latestRestoreMs'] ? 'Meta establecida: < 15 min' : 'Objetivo institucional: < 15 min'"
            icon="clock"
            :tone="$metrics['latestRestoreMs'] ? 'brand' : 'neutral'"
        />

        <x-ui.stat-card
            label="Tasa de Éxito e Integridad"
            :value="$metrics['successRate'] !== null ? $metrics['successRate'] . '%' : ($metrics['total'] > 0 ? '100%' : 'Sin registros')"
            :description="$metrics['successful'] . ' exitosos de ' . $metrics['total'] . ' intentos'"
            icon="shield-check"
            :tone="($metrics['successRate'] ?? 100) >= 95 ? 'success' : 'danger'"
        />

        <x-ui.stat-card
            label="Base de Datos Activa"
            :value="$detection['databases'][0] ?? ($demoDatabase['available'] ? 'iesp_continuidad_demo' : 'MySQL Activo')"
            icon="database"
            tone="brand"
        />
    </div>

    <!-- Two-column main panels -->
    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">

        <!-- Left: Backups & Data Sources -->
        <div class="space-y-6">

            <!-- Backup Executions Card -->
            <x-ui.card :padding="false">
                <x-slot:title>Historial de Respaldos</x-slot:title>
                <x-slot:action>
                    <a href="{{ route('backups.index') }}" class="text-xs font-bold text-brand-700 hover:text-brand-900">
                        Ver todas ({{ $metrics['total'] }}) &rarr;
                    </a>
                </x-slot:action>

                @if ($recentBackups->isEmpty())
                    <div class="p-8 text-center text-sm text-slate-500">
                        <x-ui.icon name="hard-drive" class="size-10 mx-auto text-slate-300 mb-2" />
                        <p class="font-bold text-slate-700">No hay respaldos registrados.</p>
                        <p class="text-xs text-slate-400 mt-1">Pulsa en “Respaldar Base de Datos” para generar la primera copia.</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($recentBackups as $backup)
                            <li class="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 transition hover:bg-slate-50/70">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">Respaldo #{{ $backup->id }}</span>
                                        <x-ui.badge :tone="$backup->status->tone()" size="xs">
                                            {{ $backup->status->label() }}
                                        </x-ui.badge>
                                        <span class="text-[10px] font-mono uppercase px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ $backup->database_name }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        {{ $backup->created_at?->format('d/m/Y H:i:s') }}
                                        · <span class="font-semibold text-slate-700">{{ $backup->formattedSize() }}</span>
                                        · {{ $backup->backup_duration_ms ? $backup->backup_duration_ms . ' ms' : '—' }}
                                    </p>
                                </div>

                                @if ($backup->isRestorable())
                                    <form method="POST" action="{{ route('backups.restore', $backup) }}">
                                        @csrf
                                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                        <x-ui.button type="submit" variant="outline" size="sm" icon="rotate-ccw">
                                            Restaurar
                                        </x-ui.button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <!-- Data Sources Overview -->
            <x-ui.card :padding="false">
                <x-slot:title>Fuentes de Datos Institucionales</x-slot:title>
                <x-slot:action>
                    <x-ui.badge :tone="!empty($detection['mysql_available']) ? 'success' : 'danger'" size="sm">
                        {{ !empty($detection['mysql_available']) ? 'Servidor Activo (' . ($detection['stack'] ?? 'MySQL') . ')' : 'Servidor Inaccesible' }}
                    </x-ui.badge>
                </x-slot:action>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3 font-bold">Tabla / Entidad</th>
                                <th class="px-5 py-3 font-bold">Motor</th>
                                <th class="px-5 py-3 font-bold">Registros</th>
                                <th class="px-5 py-3 font-bold">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ([
                                'estudiantes' => 'Padrón de Estudiantes',
                                'asignaturas' => 'Malla Curricular / Asignaturas',
                                'matriculas' => 'Matrículas Académicas',
                                'notas' => 'Evaluaciones y Calificaciones',
                                'asistencias' => 'Registro de Asistencias',
                                'expedientes' => 'Expedientes Académicos',
                            ] as $table => $label)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-3.5">
                                        <p class="font-bold text-slate-900 text-xs">{{ $label }}</p>
                                        <span class="font-mono text-[10px] text-slate-400">{{ $table }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-slate-600 font-medium">
                                        {{ $detection['mysql_available'] ? 'MySQL (' . $detection['stack'] . ')' : 'SQLite' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-xs font-bold text-slate-800">
                                        {{ $demoDatabase['counts'][$table] ?? 0 }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <x-ui.badge tone="success" size="xs">
                                            Activo
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Base institucional: <strong class="text-slate-700 font-mono">{{ $scheduleConfig['database'] ?? 'iesp_continuidad_demo' }}</strong></span>
                    <a href="{{ route('data-sources.index') }}" class="font-bold text-brand-700 hover:text-brand-900 inline-flex items-center gap-1">
                        Ver todas las tablas &rarr;
                    </a>
                </div>
            </x-ui.card>
        </div>

        <!-- Right: Recovery plan & Audit preview -->
        <div class="space-y-6">

            <!-- Recovery Plan (Concise & Clean) -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900">Plan de Contingencia</h2>
                    <x-ui.badge tone="neutral" size="sm">RTO &lt; 15 min</x-ui.badge>
                </div>

                <ol class="mt-4 space-y-3.5 text-xs">
                    <li class="flex gap-3">
                        <span class="size-6 shrink-0 rounded-full bg-brand-50 text-brand-700 font-bold grid place-items-center border border-brand-200">1</span>
                        <div>
                            <p class="font-bold text-slate-900">Seleccionar punto de restauración</p>
                            <p class="text-slate-500 mt-0.5">Elegir una copia íntegra con hash SHA-256 verificado.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="size-6 shrink-0 rounded-full bg-brand-50 text-brand-700 font-bold grid place-items-center border border-brand-200">2</span>
                        <div>
                            <p class="font-bold text-slate-900">Descifrado y verificación en sandbox</p>
                            <p class="text-slate-500 mt-0.5">Comprobar datos y consistencia referencial en aislamiento.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="size-6 shrink-0 rounded-full bg-brand-50 text-brand-700 font-bold grid place-items-center border border-brand-200">3</span>
                        <div>
                            <p class="font-bold text-slate-900">Conmutación y reanudación</p>
                            <p class="text-slate-500 mt-0.5">Inyectar en el servidor de producción y reanudar servicios.</p>
                        </div>
                    </li>
                </ol>

                <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('backups.runbook') }}" class="text-xs font-bold text-brand-700 hover:text-brand-900 inline-flex items-center gap-1.5">
                        <x-ui.icon name="terminal" class="size-3.5 text-brand-600" />
                        Abrir Manual de Restauración (Runbook) &rarr;
                    </a>
                </div>
            </x-ui.card>

            <!-- Recent Audit Events Card -->
            <x-ui.card :padding="false">
                <x-slot:title>Auditoría de Eventos</x-slot:title>
                <x-slot:action>
                    <a href="{{ route('audit.index') }}" class="text-xs font-bold text-brand-700 hover:text-brand-900">
                        Ver bitácora &rarr;
                    </a>
                </x-slot:action>

                <ul class="divide-y divide-slate-100">
                    @forelse ($recentAudits as $audit)
                        <li class="p-3.5 hover:bg-slate-50/50 transition flex items-start gap-3">
                            <span class="size-7 shrink-0 rounded-lg border flex items-center justify-center mt-0.5 {{ $audit->action->tone() === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($audit->action->tone() === 'danger' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-50 text-slate-700 border-slate-200') }}">
                                <x-ui.icon :name="$audit->action->icon()" class="size-3.5" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-900 truncate">
                                    {{ $audit->description }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $audit->user?->name ?? 'Sistema' }} &bull; {{ $audit->created_at?->diffForHumans() }}
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="p-6 text-center text-xs text-slate-500">
                            No hay eventos recientes.
                        </li>
                    @endforelse
                </ul>
            </x-ui.card>

            <!-- Technical Security Controls -->
            <x-ui.card>
                <h2 class="text-base font-bold text-slate-900">Estándares Criptográficos</h2>
                <ul class="mt-3 divide-y divide-slate-100 text-xs">
                    <li class="py-2 flex items-center justify-between">
                        <span class="text-slate-600">Cifrado de Datos</span>
                        <code class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold">AES-256-CBC</code>
                    </li>
                    <li class="py-2 flex items-center justify-between">
                        <span class="text-slate-600">Integridad y Autenticidad</span>
                        <code class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold">SHA-256</code>
                    </li>
                    <li class="py-2 flex items-center justify-between">
                        <span class="text-slate-600">Compresión de Archivo</span>
                        <code class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold">GZIP-9</code>
                    </li>
                    <li class="py-2 flex items-center justify-between">
                        <span class="text-slate-600">Entorno del Servidor</span>
                        <span class="font-bold text-slate-800">{{ $detection['stack'] }}</span>
                    </li>
                </ul>
            </x-ui.card>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: RESPALDAR BASE DE DATOS (DINÁMICO DEL ENTORNO ACTUAL)  -->
    <!-- ============================================================= -->
    <x-ui.modal id="modal-dashboard-backup" title="Respaldar Base de Datos" max-width="md">
        <form method="POST" action="{{ route('backups.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

            <!-- Server Environment Detection Badge -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3 text-xs">
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Servidor Detectado</span>
                    <p class="font-bold text-slate-800 mt-0.5">{{ $detection['stack'] }}</p>
                </div>
                <div class="text-right">
                    <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">MySQL</span>
                    <p class="font-bold font-mono text-slate-800 mt-0.5">127.0.0.1:{{ $detection['mysql_port'] }}</p>
                </div>
            </div>

            <!-- Database Selector -->
            <div>
                <label for="dash_select_database" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Selecciona la base de datos a respaldar:
                </label>
                <select id="dash_select_database" name="database" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    @if ($detection['mysql_available'] && !empty($detection['databases']))
                        <optgroup label="Bases de Datos Activas ({{ $detection['stack'] }})">
                            @if (in_array('iesp_continuidad_demo', $detection['databases']))
                                <option value="iesp_continuidad_demo" selected>iesp_continuidad_demo (Base Principal)</option>
                            @endif
                            @foreach ($detection['databases'] as $dbName)
                                @if ($dbName !== 'iesp_continuidad_demo')
                                    <option value="{{ $dbName }}">{{ $dbName }}</option>
                                @endif
                            @endforeach
                        </optgroup>
                    @endif
                    <optgroup label="Bases Secundarias">
                        <option value="sqlite:demo-institucional" {{ empty($detection['databases']) ? 'selected' : '' }}>
                            Base SQLite Local (demo-institucional.sqlite)
                        </option>
                    </optgroup>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">
                    Detecta automáticamente bases en Laragon, XAMPP, MySQL Workbench o consola.
                </p>
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

</div>
@endsection
