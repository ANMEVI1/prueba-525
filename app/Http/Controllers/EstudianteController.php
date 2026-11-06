<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\EstudianteRequest;
use App\Models\Estudiante;
use App\Services\AuditLogger;
use App\Services\BackupDemoDatabase;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $estudiantes = Estudiante::query()
            ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                $query->where(function (Builder $query) use ($buscar): void {
                    $query->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('dni', 'like', "%{$buscar}%")
                        ->orWhere('carrera', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString();

        return view('estudiantes.index', compact('estudiantes', 'buscar'));
    }

    public function create(): View
    {
        return view('estudiantes.create');
    }

    public function store(EstudianteRequest $request): RedirectResponse
    {
        $estudiante = Estudiante::query()->create($request->validated());

        $this->auditLogger->record(
            AuditAction::StudentCreated,
            "Estudiante registrado: {$estudiante->nombres} (DNI {$estudiante->dni}).",
            $estudiante,
            ['dni' => $estudiante->dni, 'carrera' => $estudiante->carrera],
        );

        app(BackupDemoDatabase::class)->clearCache();

        app(BackupDemoDatabase::class)->clearCache();

        return redirect()
            ->route('estudiantes.index')
            ->with('success', "El estudiante {$estudiante->nombres} fue registrado correctamente.");
    }

    public function show(Estudiante $estudiante): View
    {
        return view('estudiantes.show', compact('estudiante'));
    }

    public function edit(Estudiante $estudiante): View
    {
        return view('estudiantes.edit', compact('estudiante'));
    }

    public function update(EstudianteRequest $request, Estudiante $estudiante): RedirectResponse
    {
        $estudiante->update($request->validated());

        $this->auditLogger->record(
            AuditAction::StudentUpdated,
            "Datos actualizados del estudiante: {$estudiante->nombres}.",
            $estudiante,
            ['dni' => $estudiante->dni],
        );

        return redirect()
            ->route('estudiantes.index')
            ->with('success', "El estudiante {$estudiante->nombres} fue actualizado correctamente.");
    }

    public function destroy(Estudiante $estudiante): RedirectResponse
    {
        $nombre = $estudiante->nombres;
        $dni = $estudiante->dni;

        $this->auditLogger->record(
            AuditAction::StudentDeleted,
            "Estudiante eliminado del padrón: {$nombre} (DNI {$dni}).",
            null,
            ['dni' => $dni, 'nombres' => $nombre],
        );

        $estudiante->delete();

        app(BackupDemoDatabase::class)->clearCache();

        app(BackupDemoDatabase::class)->clearCache();

        return redirect()
            ->route('estudiantes.index')
            ->with('success', "El estudiante {$nombre} fue eliminado correctamente.");
    }
}
