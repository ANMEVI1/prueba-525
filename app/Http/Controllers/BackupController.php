<?php

namespace App\Http\Controllers;

use App\Models\DemoBackup;
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
}
