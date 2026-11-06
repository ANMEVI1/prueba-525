<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DemoBackup;
use App\Models\Estudiante;
use App\Services\BackupDemoDatabase;
use App\Services\BackupScheduleManager;
use App\Services\ContinuityMetrics;
use App\Services\EnvironmentDetector;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BackupDemoDatabase $backupDemoDatabase,
        private readonly ContinuityMetrics $continuityMetrics,
        private readonly EnvironmentDetector $detector,
        private readonly BackupScheduleManager $scheduleManager,
    ) {}

    public function index(): View
    {
        $metrics = $this->continuityMetrics->summary();
        $database = $this->backupDemoDatabase->overview();
        $detection = $this->detector->detect();
        $scheduleConfig = $this->scheduleManager->getConfig();

        $recentBackups = DemoBackup::query()
            ->latest('id')
            ->limit(5)
            ->get();

        $recentAudits = AuditLog::query()
            ->with('user')
            ->latest('id')
            ->limit(6)
            ->get();

        $restorableDemoBackup = DemoBackup::query()
            ->where('status', 'success')
            ->latest('id')
            ->first();

        return view('dashboard.index', [
            'metrics' => $metrics,
            'demoDatabase' => $database,
            'detection' => $detection,
            'scheduleConfig' => $scheduleConfig,
            'recentBackups' => $recentBackups,
            'recentAudits' => $recentAudits,
            'restorableDemoBackup' => $restorableDemoBackup,
            'totalStudents' => Estudiante::query()->count(),
            // Backwards compatibility variables for old views
            'totalPageViews' => AuditLog::query()->count(),
            'todayPageViews' => AuditLog::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'demoBackups' => $recentBackups,
            'successfulDemoBackups' => $metrics['successful'],
            'failedDemoBackups' => $metrics['failed'],
            'latestDemoRestore' => $metrics['latestRestore'],
        ]);
    }
}
