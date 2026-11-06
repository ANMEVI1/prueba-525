<?php

namespace App\Http\Controllers;

use App\Services\BackupDemoDatabase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataSourceController extends Controller
{
    public function __construct(private readonly BackupDemoDatabase $backupDemoDatabase) {}

    public function index(Request $request): View
    {
        $overview = $this->backupDemoDatabase->overview();
        $selectedTable = $request->query('table', 'estudiantes');

        $allowedTables = [
            'estudiantes' => 'Padrón de Estudiantes',
            'asignaturas' => 'Malla Curricular / Asignaturas',
            'matriculas' => 'Matrículas Académicas',
            'notas' => 'Calificaciones y Rendimiento',
            'asistencias' => 'Control de Asistencias',
            'expedientes' => 'Expedientes Digitales',
        ];

        if (! array_key_exists($selectedTable, $allowedTables)) {
            $selectedTable = 'estudiantes';
        }

        $tableData = [];
        $columns = [];
        $sourceType = 'SQLite Aislamiento';

        try {
            if ($selectedTable === 'estudiantes' && Schema::connection('mysql')->hasTable('estudiantes')) {
                $sampleRows = DB::connection('mysql')->table('estudiantes')->latest('id')->limit(15)->get();
                $sourceType = 'MySQL Principal (Activa)';
                if ($sampleRows->isNotEmpty()) {
                    $columns = array_keys((array) $sampleRows->first());
                    $tableData = $sampleRows->map(fn ($r) => (array) $r)->all();
                    $overview['counts']['estudiantes'] = DB::connection('mysql')->table('estudiantes')->count();
                }
            } elseif ($overview['available']) {
                $connection = DB::connection('backup_demo');
                $sampleRows = $connection->table($selectedTable)->limit(15)->get();

                if ($sampleRows->isNotEmpty()) {
                    $columns = array_keys((array) $sampleRows->first());
                    $tableData = $sampleRows->map(fn ($r) => (array) $r)->all();
                }
            }
        } catch (\Throwable) {
            // Fallback gracefully if MySQL connection fails
            if ($overview['available']) {
                $connection = DB::connection('backup_demo');
                $sampleRows = $connection->table($selectedTable)->limit(15)->get();

                if ($sampleRows->isNotEmpty()) {
                    $columns = array_keys((array) $sampleRows->first());
                    $tableData = $sampleRows->map(fn ($r) => (array) $r)->all();
                }
            }
        }

        return view('data-sources.index', [
            'overview' => $overview,
            'tables' => $allowedTables,
            'selectedTable' => $selectedTable,
            'columns' => $columns,
            'tableData' => $tableData,
            'sourceType' => $sourceType,
        ]);
    }
}
