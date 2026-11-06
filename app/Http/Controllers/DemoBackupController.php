<?php

namespace App\Http\Controllers;

use App\Models\DemoBackup;
use App\Services\DemoBackupWorkflow;
use Illuminate\Http\RedirectResponse;

class DemoBackupController extends Controller
{
    public function store(DemoBackupWorkflow $workflow): RedirectResponse
    {
        $backup = $workflow->createBackup();

        return redirect()
            ->route('arquitectura')
            ->with('backup_status', "Respaldo ficticio cifrado y verificado. ID: {$backup->id}.");
    }

    public function restore(DemoBackup $demoBackup, DemoBackupWorkflow $workflow): RedirectResponse
    {
        $restoredPath = $workflow->restore($demoBackup);

        return redirect()
            ->route('arquitectura')
            ->with('backup_status', "Recuperación de prueba completada e íntegra. Archivo aislado: {$restoredPath}");
    }
}
