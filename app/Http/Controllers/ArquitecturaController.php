<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DemoBackup;
use App\Services\BackupDemoDatabase;
use App\Services\ContinuityMetrics;
use Illuminate\Contracts\View\View;

class ArquitecturaController extends Controller
{
    public function __construct(
        private readonly BackupDemoDatabase $backupDemoDatabase,
        private readonly ContinuityMetrics $continuityMetrics,
    ) {}

    public function show(): View
    {
        $metrics = $this->continuityMetrics->summary();
        $database = $this->backupDemoDatabase->overview();

        $recentBackups = DemoBackup::query()
            ->latest('id')
            ->limit(10)
            ->get();

        $restorableDemoBackup = DemoBackup::query()
            ->where('status', 'success')
            ->latest('id')
            ->first();

        return view('dashboard.index', [
            'metrics' => $metrics,
            'demoDatabase' => $database,
            'recentBackups' => $recentBackups,
            'recentAudits' => AuditLog::query()->with('user')->latest('id')->limit(5)->get(),
            'restorableDemoBackup' => $restorableDemoBackup,
            // Legacy view compatibility variables
            'totalPageViews' => AuditLog::query()->count(),
            'todayPageViews' => AuditLog::query()->whereDate('created_at', today())->count(),
            'demoBackups' => $recentBackups,
            'successfulDemoBackups' => $metrics['successful'],
            'failedDemoBackups' => $metrics['failed'],
            'latestDemoRestore' => $metrics['latestRestore'],
        ]);
    }
}
