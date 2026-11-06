<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BackupDemoDatabase
{
    /**
     * @var array{
     *     available: bool,
     *     integrity: string,
     *     counts: array<string, int>,
     *     students: list<array{code: string, name: string, career: string}>
     * }|null
     */
    private ?array $memoryOverview = null;

    /**
     * @return array{
     *     available: bool,
     *     integrity: string,
     *     counts: array<string, int>,
     *     students: list<array{code: string, name: string, career: string}>
     * }
     */
    public function overview(bool $forceRefresh = false): array
    {
        if (! $forceRefresh && $this->memoryOverview !== null) {
            return $this->memoryOverview;
        }

        if (! $forceRefresh) {
            $cached = Cache::get('backup_demo_overview');
            if (is_array($cached)) {
                return $this->memoryOverview = $cached;
            }
        }

        $databasePath = config('database.connections.backup_demo.database');

        if (! is_string($databasePath) || ! is_file($databasePath)) {
            return [
                'available' => false,
                'integrity' => 'No generada',
                'counts' => [],
                'students' => [],
            ];
        }

        $connection = DB::connection('backup_demo');
        $tables = [
            'estudiantes',
            'asignaturas',
            'matriculas',
            'notas',
            'asistencias',
            'expedientes',
        ];
        $counts = [];

        foreach ($tables as $table) {
            $counts[$table] = $connection->table($table)->count();
        }

        $students = $connection->table('estudiantes')
            ->join('carreras', 'estudiantes.carrera_id', '=', 'carreras.id')
            ->select([
                'estudiantes.codigo as code',
                'estudiantes.nombres as name',
                'carreras.nombre as career',
            ])
            ->orderBy('estudiantes.codigo')
            ->limit(5)
            ->get()
            ->map(fn (object $student): array => [
                'code' => $student->code,
                'name' => $student->name,
                'career' => $student->career,
            ])
            ->all();

        $integrity = $connection->selectOne('PRAGMA integrity_check')->integrity_check;
        $foreignKeyViolations = $connection->select('PRAGMA foreign_key_check');

        $result = [
            'available' => true,
            'integrity' => $integrity === 'ok' && $foreignKeyViolations === []
                ? 'Correcta'
                : 'Revisar',
            'counts' => $counts,
            'students' => $students,
        ];

        Cache::put('backup_demo_overview', $result, now()->addMinutes(5));

        return $this->memoryOverview = $result;
    }

    public function clearCache(): void
    {
        $this->memoryOverview = null;
        Cache::forget('backup_demo_overview');
    }
}
