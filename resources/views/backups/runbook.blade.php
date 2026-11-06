@extends('layouts.app')

@section('title', 'Manual de Restauración (Runbook) | Continuidad TI')
@section('page_title', 'Manual de Restauración')

@section('content')
<div class="space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <x-ui.badge tone="brand" size="xs">Procedimiento Operativo Estandarizado</x-ui.badge>
                <span class="text-xs text-slate-400 font-medium">SOP-TI-01 · Disaster Recovery Runbook</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 mt-1">Manual Paso a Paso de Restauración</h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('backups.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-xs">
                <x-ui.icon name="shield-check" class="size-4 text-slate-500" />
                Historial de Respaldos
            </a>

            @if ($latestBackup && $latestBackup->isRestorable())
                <form method="POST" action="{{ route('backups.restore', $latestBackup) }}" class="inline">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                    <x-ui.button type="submit" variant="primary" icon="rotate-ccw" size="md">
                        Ejecutar Prueba Inmediata (#{{ $latestBackup->id }})
                    </x-ui.button>
                </form>
            @endif
        </div>
    </div>

    <!-- Continuity SLA Metrics -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">RTO Institucional</span>
                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                    <x-ui.icon name="clock" class="size-5" />
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-2">&lt; 15 min</p>
            <p class="text-xs text-emerald-700 font-medium mt-1">Tiempo máximo de restablecimiento</p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">RPO Institucional</span>
                <span class="p-2 rounded-lg bg-brand-50 text-brand-600">
                    <x-ui.icon name="server" class="size-5" />
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-2">&lt; 24 horas</p>
            <p class="text-xs text-brand-700 font-medium mt-1">Respaldo diario de madrugada (02:00)</p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Algoritmo Cifrado</span>
                <span class="p-2 rounded-lg bg-amber-50 text-amber-600">
                    <x-ui.icon name="lock" class="size-5" />
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-2">AES-256</p>
            <p class="text-xs text-slate-500 font-medium mt-1">Modo CBC + HMAC de autenticidad</p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Compresión y Espejo</span>
                <span class="p-2 rounded-lg bg-purple-50 text-purple-600">
                    <x-ui.icon name="hard-drive" class="size-5" />
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-2">GZIP-9</p>
            <p class="text-xs text-purple-700 font-medium mt-1">Replicación secundaria activa</p>
        </div>
    </div>

    <!-- Active Artifact Status -->
    @if ($latestBackup)
        <div class="p-5 rounded-2xl bg-slate-900 text-white shadow-md border border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-xl bg-emerald-500/20 text-emerald-400 grid place-items-center">
                        <x-ui.icon name="shield-check" class="size-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Artefacto Certificado Más Reciente</span>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-medium">ID #{{ $latestBackup->id }}</span>
                        </div>
                        <p class="text-base font-bold text-white mt-0.5">
                            Base: <span class="text-amber-300 font-mono">{{ $latestBackup->database_name }}</span> 
                            <span class="text-xs font-normal text-slate-400">({{ $latestBackup->database_type ? strtoupper($latestBackup->database_type) : 'MYSQL' }})</span>
                        </p>
                    </div>
                </div>

                <div class="text-left sm:text-right text-xs space-y-0.5">
                    <p class="text-slate-400">Generado el <span class="text-white font-semibold">{{ $latestBackup->created_at?->format('d/m/Y H:i:s') }}</span></p>
                    <p class="text-slate-400">Tamaño comprimido cifrado: <span class="text-white font-semibold">{{ $latestBackup->formattedSize() }}</span></p>
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 text-xs">
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-400 block font-medium mb-1">Ruta del artefacto principal:</span>
                    <code class="font-mono text-emerald-300 break-all select-all block">{{ $latestBackup->artifact_path }}</code>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-400 block font-medium mb-1">Huella SHA-256 criptográfica:</span>
                    <code class="font-mono text-amber-300 break-all select-all block">{{ $latestBackup->artifact_sha256 }}</code>
                </div>
            </div>
        </div>
    @endif

    <!-- Step by Step Workflow (Interactive 4 Phases) -->
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <span class="size-2 rounded-full bg-brand-600"></span>
            <h2 class="text-base font-black uppercase tracking-wider text-slate-900">Protocolo de Recuperación en 4 Fases</h2>
        </div>

        <!-- FASE 1 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="p-5 bg-slate-50 border-b border-slate-200/80 flex items-center gap-4">
                <span class="size-8 rounded-full bg-brand-600 text-white font-black text-sm grid place-items-center shrink-0">1</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Fase 1: Verificación de Autenticidad e Integridad (SHA-256)</h3>
                    <p class="text-xs text-slate-500">Comprueba que el archivo cifrado (.enc) no haya sufrido corrupción en disco o alteraciones no autorizadas.</p>
                </div>
            </div>

            <div class="p-6 space-y-4 text-xs text-slate-700">
                <p class="leading-relaxed">
                    Antes de procesar o descifrar cualquier respaldo, es imperativo calcular su hash criptográfico SHA-256 y compararlo contra el manifiesto generado al momento de la extracción. Si las huellas coinciden, el archivo está intacto.
                </p>

                <!-- PowerShell snippet -->
                <div class="space-y-1.5">
                    <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                        <x-ui.icon name="terminal" class="size-3.5 text-brand-600" />
                        Comando en Windows PowerShell:
                    </span>
                    <div class="p-3 rounded-xl bg-slate-900 text-slate-200 font-mono text-[11px] flex items-center justify-between gap-4 overflow-x-auto">
                        <span class="select-all">Get-FileHash -Algorithm SHA256 storage\app\{{ $latestBackup ? $latestBackup->artifact_path : 'backups/backup-*.enc' }} | Format-List</span>
                    </div>
                </div>

                <!-- Bash snippet -->
                <div class="space-y-1.5">
                    <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                        <x-ui.icon name="terminal" class="size-3.5 text-slate-600" />
                        Comando en Linux / Bash:
                    </span>
                    <div class="p-3 rounded-xl bg-slate-900 text-slate-200 font-mono text-[11px] flex items-center justify-between gap-4 overflow-x-auto">
                        <span class="select-all">sha256sum storage/app/{{ $latestBackup ? $latestBackup->artifact_path : 'backups/backup-*.enc' }}</span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 flex items-start gap-3">
                    <x-ui.icon name="check-circle" class="size-4 text-emerald-600 shrink-0 mt-0.5" />
                    <div>
                        <p class="font-bold">Criterio de Aceptación:</p>
                        <p class="mt-0.5">El valor resultante debe coincidir exactamente con el hash registrado en el panel: <code class="font-mono font-bold">{{ $latestBackup?->artifact_sha256 ?? 'SHA-256 registrado' }}</code></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FASE 2 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="p-5 bg-slate-50 border-b border-slate-200/80 flex items-center gap-4">
                <span class="size-8 rounded-full bg-brand-600 text-white font-black text-sm grid place-items-center shrink-0">2</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Fase 2: Descifrado AES-256-CBC y Descompresión GZIP-9</h3>
                    <p class="text-xs text-slate-500">Desencapsulamiento criptográfico utilizando la clave maestra del servidor.</p>
                </div>
            </div>

            <div class="p-6 space-y-4 text-xs text-slate-700">
                <p class="leading-relaxed">
                    El paquete de respaldo se encuentra protegido mediante el cifrado simétrico robusto <strong>AES-256-CBC</strong> y firmado con <strong>HMAC</strong>. La clave de descifrado reside de forma segura en la variable <code class="bg-slate-100 px-1 py-0.5 rounded font-mono font-bold">APP_KEY</code>.
                </p>

                <div class="space-y-1.5">
                    <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                        <x-ui.icon name="terminal" class="size-3.5 text-brand-600" />
                        Ejecución asistida mediante motor PHP 8.3 aislado:
                    </span>
                    <div class="p-3 rounded-xl bg-slate-900 text-slate-200 font-mono text-[11px] flex items-center justify-between gap-4 overflow-x-auto">
                        <span class="select-all">php artisan tinker --execute="\$wf = app(App\Services\DemoBackupWorkflow::class); echo \$wf->restore(App\Models\DemoBackup::latest()->first());"</span>
                    </div>
                </div>

                <p class="text-slate-500">
                    O sencillamente haz clic en el botón <strong>"Restaurar prueba"</strong> del panel web para realizar el descifrado y validación en menos de 100 milisegundos.
                </p>
            </div>
        </div>

        <!-- FASE 3 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="p-5 bg-slate-50 border-b border-slate-200/80 flex items-center gap-4">
                <span class="size-8 rounded-full bg-brand-600 text-white font-black text-sm grid place-items-center shrink-0">3</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Fase 3: Validación de Integridad en Entorno Aislado (Sandbox)</h3>
                    <p class="text-xs text-slate-500">Comprobación del esquema y conteo de registros sin afectar los datos en producción.</p>
                </div>
            </div>

            <div class="p-6 space-y-4 text-xs text-slate-700">
                <p class="leading-relaxed">
                    El sistema nunca sobreescribe la base de datos de producción a ciegas. Primero desempaqueta el contenido en un espacio temporal seguro (<code class="bg-slate-100 px-1 py-0.5 rounded font-mono">storage/app/demo-restores/</code>), donde ejecuta un chequeo de integridad:
                </p>

                <ul class="list-disc pl-5 space-y-1 text-slate-600">
                    <li>Verificación de cabeceras de tablas maestras (<code class="font-mono">estudiantes</code>, <code class="font-mono">asignaturas</code>, <code class="font-mono">matriculas</code>, <code class="font-mono">notas</code>).</li>
                    <li>Comprobación de no-corrupción y lectura de tuplas mediante PDO.</li>
                    <li>Cálculo de la tasa de latencia y tiempo de conmutación (RTO obtenido en milisegundos).</li>
                </ul>

                @if ($latestBackup)
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900">¿Deseas probar la restauración ahora mismo?</p>
                            <p class="text-slate-500 mt-0.5">Se restaurará en sandbox y registrará el tiempo en la auditoría.</p>
                        </div>
                        <form method="POST" action="{{ route('backups.restore', $latestBackup) }}">
                            @csrf
                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                            <x-ui.button type="submit" variant="primary" size="sm" icon="rotate-ccw">
                                Iniciar Restauración Aislada
                            </x-ui.button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <!-- FASE 4 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="p-5 bg-slate-50 border-b border-slate-200/80 flex items-center gap-4">
                <span class="size-8 rounded-full bg-brand-600 text-white font-black text-sm grid place-items-center shrink-0">4</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Fase 4: Conmutación a Producción (Failover & Switch)</h3>
                    <p class="text-xs text-slate-500">Inyección final en el motor de base de datos MySQL o SQLite del servidor.</p>
                </div>
            </div>

            <div class="p-6 space-y-4 text-xs text-slate-700">
                <p class="leading-relaxed">
                    Una vez confirmada la sanidad del archivo en la Fase 3, el técnico procede con la inyección hacia la base de datos institucional en Laragon / XAMPP:
                </p>

                <!-- MySQL Import CLI -->
                <div class="space-y-1.5">
                    <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                        <x-ui.icon name="database" class="size-3.5 text-brand-600" />
                        Inyección en MySQL (Laragon / XAMPP):
                    </span>
                    <div class="p-3 rounded-xl bg-slate-900 text-slate-200 font-mono text-[11px] flex items-center justify-between gap-4 overflow-x-auto">
                        <span class="select-all">mysql -h 127.0.0.1 -P 3306 -u root {{ $scheduleConfig['database'] ?? 'iesp_continuidad_demo' }} &lt; storage\app\demo-restores\restored.sql</span>
                    </div>
                </div>

                <!-- Cache Clean -->
                <div class="space-y-1.5">
                    <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                        <x-ui.icon name="refresh-cw" class="size-3.5 text-slate-600" />
                        Reinicio de caché del aplicativo:
                    </span>
                    <div class="p-3 rounded-xl bg-slate-900 text-slate-200 font-mono text-[11px] flex items-center justify-between gap-4 overflow-x-auto">
                        <span class="select-all">php artisan optimize:clear</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contingency Troubleshooting Matrix -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6 space-y-4">
        <div class="flex items-center gap-2">
            <x-ui.icon name="alert-triangle" class="size-5 text-amber-500" />
            <h3 class="text-base font-bold text-slate-900">Resolución de Incidencias y Plan de Contingencia</h3>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                <p class="font-bold text-slate-900">¿Falla en el disco o almacenamiento principal?</p>
                <p class="text-slate-600 leading-relaxed">
                    El sistema mantiene una réplica espejo secundaria en <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-mono">storage/app/backups/secondary/</code> con su correspondiente archivo <code class="font-mono">.sha256</code>. Copie el archivo desde la ruta secundaria y continúe con la Fase 1.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                <p class="font-bold text-slate-900">¿El servicio MySQL de Laragon/XAMPP no inicia?</p>
                <p class="text-slate-600 leading-relaxed">
                    Verifique que el puerto 3306 no esté ocupado por otra instancia. Abra el panel de Laragon o XAMPP y presione "Iniciar todo", o cambie el puerto en el archivo <code class="font-mono">.env</code> (<code class="font-mono">DB_PORT=3306</code>).
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                <p class="font-bold text-slate-900">¿Error de MAC / HMAC al descifrar?</p>
                <p class="text-slate-600 leading-relaxed">
                    Ocurre si la clave <code class="font-mono">APP_KEY</code> fue alterada o generada de nuevo con <code class="font-mono">key:generate</code>. Asegúrese de restaurar la variable <code class="font-mono">APP_KEY</code> original del archivo de configuración respaldado.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                <p class="font-bold text-slate-900">Contactos y Escalabilidad Institucional</p>
                <p class="text-slate-600 leading-relaxed">
                    <strong>Asesor Técnico:</strong> Prof. Tec. Juan Gabriel Alva Jimenez<br>
                    <strong>Responsable del Proyecto:</strong> Julio Alexander Delgado Avila<br>
                    <strong>Institución:</strong> IESP Cap. FAP José Abelardo Quiñones (Tumbes)
                </p>
            </div>
        </div>
    </div>

</div>
@endsection

