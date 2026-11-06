<?php

namespace App\Http\Controllers;

use App\Models\DemoBackup;
use App\Services\BackupDemoDatabase;
use App\Services\BackupScheduleManager;
use App\Services\ContinuityMetrics;
use App\Services\DemoBackupWorkflow;
use App\Services\EnvironmentDetector;
use App\Services\FolderPickerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;

class BackupController extends Controller
{
    public function __construct(
        private readonly DemoBackupWorkflow $workflow,
        private readonly ContinuityMetrics $continuityMetrics,
        private readonly EnvironmentDetector $detector,
        private readonly BackupScheduleManager $scheduleManager,
        private readonly FolderPickerService $folderPicker,
    ) {}

    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');

        $backups = DemoBackup::query()
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $metrics = $this->continuityMetrics->summary();
        $detection = $this->detector->detect();
        $scheduleConfig = $this->scheduleManager->getConfig();
        $folderPresets = $this->folderPicker->getQuickPresets();
        $systemDrives = $this->folderPicker->getSystemDrives();

        $restorableBackup = DemoBackup::query()
            ->where('status', 'success')
            ->latest('id')
            ->first();

        return view('backups.index', [
            'backups' => $backups,
            'metrics' => $metrics,
            'restorableBackup' => $restorableBackup,
            'statusFilter' => $statusFilter,
            'detection' => $detection,
            'scheduleConfig' => $scheduleConfig,
            'folderPresets' => $folderPresets,
            'systemDrives' => $systemDrives,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $targetDb = $request->input('database');
        $backup = $this->workflow->createBackup($targetDb);

        $targetUrl = $this->resolveRedirectTarget($request);

        return redirect($targetUrl)
            ->with('backup_status', "Copia de seguridad #{$backup->id} creada exitosamente ({$backup->database_name} · {$backup->formattedSize()}).");
    }

    public function restore(Request $request, DemoBackup $demoBackup): RedirectResponse
    {
        $restoredPath = $this->workflow->restore($demoBackup);

        $targetUrl = $this->resolveRedirectTarget($request);

        return redirect($targetUrl)
            ->with('backup_status', "Recuperación completada en {$demoBackup->restore_duration_ms} ms. Base verificada con éxito.");
    }

    private function resolveRedirectTarget(Request $request): string
    {
        $targetUrl = $request->input('redirect_to');

        if (! empty($targetUrl)) {
            return (string) $targetUrl;
        }

        $previous = url()->previous();

        if ($previous && $previous !== url()->current() && $previous !== url('/')) {
            return $previous;
        }

        return route('arquitectura');
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'frequency' => ['required', 'string', 'in:daily,twice_daily,hourly,every_30_mins,every_15_mins,every_minute'],
            'time' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'database' => ['required', 'string', 'max:100'],
            'retention_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'secondary_path' => ['required', 'string', 'max:255'],
            'storage_destination' => ['nullable', 'string', 'max:255'],
            'folder_structure' => ['nullable', 'string', 'in:single_folder,date_subfolders'],
            'save_format' => ['nullable', 'string', 'in:encrypted,direct_sql,both'],
        ]);

        $this->scheduleManager->saveConfig([
            'enabled' => $request->boolean('enabled'),
            'frequency' => $validated['frequency'],
            'time' => $validated['time'] ?? '02:00',
            'database' => $validated['database'],
            'retention_days' => ! empty($validated['retention_days']) ? (int) $validated['retention_days'] : null,
            'replicate_secondary' => $request->boolean('replicate_secondary'),
            'secondary_path' => $validated['secondary_path'],
            'storage_destination' => $validated['storage_destination'] ?? 'storage/app/backups',
            'folder_structure' => $validated['folder_structure'] ?? 'single_folder',
            'save_format' => $validated['save_format'] ?? 'both',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Configuración de almacenamiento, destinos y programación actualizada.');
    }

    public function runScheduledNow(Request $request): RedirectResponse
    {
        Artisan::call('backup:auto-run', ['--force' => true]);

        return redirect()
            ->back(fallback: route('backups.index'))
            ->with('success', 'Respaldo automático ejecutado exitosamente según el destino y formato configurados.');
    }

    public function browseFolder(Request $request): JsonResponse
    {
        $currentPath = (string) $request->input('current_path', '');
        $selectedPath = $this->folderPicker->openFolderDialog($currentPath);

        if ($selectedPath !== null && $selectedPath !== '') {
            return response()->json([
                'success' => true,
                'path' => $selectedPath,
            ]);
        }

        return response()->json([
            'success' => false,
            'cancelled' => true,
            'message' => 'No se seleccionó ninguna carpeta.',
        ]);
    }

    public function runbook(): View
    {
        $detection = $this->detector->detect();
        $latestBackup = DemoBackup::query()->where('status', 'success')->latest('id')->first();
        $scheduleConfig = $this->scheduleManager->getConfig();

        return view('backups.runbook', [
            'detection' => $detection,
            'latestBackup' => $latestBackup,
            'scheduleConfig' => $scheduleConfig,
        ]);
    }

    public function download(DemoBackup $demoBackup, string $format = 'sql')
    {
        $safeDb = Str::slug($demoBackup->database_name ?? 'mysql');

        if ($format === 'sql') {
            $candidates = [
                storage_path('app/backups/'.basename(str_replace('.backup.enc', '.dump.sql', (string) $demoBackup->artifact_path))),
                storage_path('app/private/demo-backups/'.basename(str_replace('.backup.enc', '.dump.sql', (string) $demoBackup->artifact_path))),
            ];

            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    return response()->download($candidate, "respaldo-{$safeDb}-{$demoBackup->id}.sql");
                }
            }

            // Descifrar y generar en el acto si no está en disco
            $disk = Storage::disk('local');
            if ($disk->exists((string) $demoBackup->artifact_path)) {
                $encrypted = $disk->get((string) $demoBackup->artifact_path);
                $decrypted = gzdecode(Crypt::decryptString($encrypted));

                return response()->streamDownload(fn () => print ($decrypted), "respaldo-{$safeDb}-{$demoBackup->id}.sql");
            }
        } else {
            $encCandidates = [
                storage_path('app/backups/'.basename((string) $demoBackup->artifact_path)),
                storage_path('app/private/demo-backups/'.basename((string) $demoBackup->artifact_path)),
            ];

            foreach ($encCandidates as $candidate) {
                if (file_exists($candidate)) {
                    return response()->download($candidate, "respaldo-cifrado-{$safeDb}-{$demoBackup->id}.enc");
                }
            }
        }

        return redirect()->back()->with('error', 'El archivo solicitado no se encuentra disponible.');
    }

    public function uploadRestore(Request $request): RedirectResponse
    {
        $request->validate([
            'sql_file' => ['required', 'file', 'max:51200'],
        ]);

        $file = $request->file('sql_file');
        $content = file_get_contents($file->getRealPath());

        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database', 'iesp_continuidad_demo');
        $username = config('database.connections.mysql.username', 'root');
        $password = config('database.connections.mysql.password', '');

        try {
            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$database}`;");
            $pdo->exec($content);

            app(ContinuityMetrics::class)->clearCache();
            app(BackupDemoDatabase::class)->clearCache();

            return redirect()
                ->back()
                ->with('backup_status', '¡Archivo SQL subido y restaurado exitosamente en MySQL!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al restaurar archivo SQL: '.$e->getMessage());
        }
    }
}
