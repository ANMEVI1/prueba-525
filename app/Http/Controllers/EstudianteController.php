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

    public function seedDemo(): RedirectResponse
    {
        $demoStudents = [
            ['dni' => '71000001', 'nombres' => 'Mateo Alejandro Morales Castro', 'carrera' => 'Plataformas y Servicios de Tecnologías de la Información', 'correo' => 'mateo.morales@iesp.edu.pe'],
            ['dni' => '71000002', 'nombres' => 'Valeria Nicole Sánchez Rivas', 'carrera' => 'Desarrollo de Sistemas de Información', 'correo' => 'valeria.sanchez@iesp.edu.pe'],
            ['dni' => '71000003', 'nombres' => 'Joaquín Alonso Herrera Ñáñez', 'carrera' => 'Plataformas y Servicios de Tecnologías de la Información', 'correo' => 'joaquin.herrera@iesp.edu.pe'],
            ['dni' => '71000004', 'nombres' => 'Camila Esperanza Gómez Zúñiga', 'carrera' => 'Contabilidad y Finanzas', 'correo' => 'camila.gomez@iesp.edu.pe'],
            ['dni' => '71000005', 'nombres' => 'Diego Sebastián Mendoza Cárdenas', 'carrera' => 'Mecatrónica Automotriz', 'correo' => 'diego.mendoza@iesp.edu.pe'],
            ['dni' => '71000006', 'nombres' => 'Luciana Belén Flores Peña', 'carrera' => 'Administración de Empresas', 'correo' => 'luciana.flores@iesp.edu.pe'],
            ['dni' => '71000007', 'nombres' => 'Álvaro Gabriel Romero Núñez', 'carrera' => 'Plataformas y Servicios de Tecnologías de la Información', 'correo' => 'alvaro.romero@iesp.edu.pe'],
            ['dni' => '71000008', 'nombres' => 'Fiorella Yamileth Cruz Alva', 'carrera' => 'Desarrollo de Sistemas de Información', 'correo' => 'fiorella.cruz@iesp.edu.pe'],
            ['dni' => '71000009', 'nombres' => 'Rodrigo Andrés Medina Ríos', 'carrera' => 'Mecatrónica Automotriz', 'correo' => 'rodrigo.medina@iesp.edu.pe'],
            ['dni' => '71000010', 'nombres' => 'Andrea Stephanie Benítez Quiñones', 'carrera' => 'Contabilidad y Finanzas', 'correo' => 'andrea.benitez@iesp.edu.pe'],
            ['dni' => '71000011', 'nombres' => 'Sebastián Ignacio Paredes Cañari', 'carrera' => 'Plataformas y Servicios de Tecnologías de la Información', 'correo' => 'sebastian.paredes@iesp.edu.pe'],
            ['dni' => '71000012', 'nombres' => 'Daniela Alejandra Ramos Huamán', 'carrera' => 'Administración de Empresas', 'correo' => 'daniela.ramos@iesp.edu.pe'],
            ['dni' => '71000013', 'nombres' => 'Christian Jesús Espinoza Morales', 'carrera' => 'Desarrollo de Sistemas de Información', 'correo' => 'christian.espinoza@iesp.edu.pe'],
            ['dni' => '71000014', 'nombres' => 'Romina Beatriz Delgado Carranza', 'carrera' => 'Contabilidad y Finanzas', 'correo' => 'romina.delgado@iesp.edu.pe'],
            ['dni' => '71000015', 'nombres' => 'Esteban Darío Salazar Vílchez', 'carrera' => 'Mecatrónica Automotriz', 'correo' => 'esteban.salazar@iesp.edu.pe'],
            ['dni' => '71000016', 'nombres' => 'Melany Alexandra Quispe Tello', 'carrera' => 'Plataformas y Servicios de Tecnologías de la Información', 'correo' => 'melany.quispe@iesp.edu.pe'],
            ['dni' => '71000017', 'nombres' => 'Gonzalo David Castillo Barreto', 'carrera' => 'Desarrollo de Sistemas de Información', 'correo' => 'gonzalo.castillo@iesp.edu.pe'],
            ['dni' => '71000018', 'nombres' => 'Ariana Nicole Vásquez Córdova', 'carrera' => 'Administración de Empresas', 'correo' => 'ariana.vasquez@iesp.edu.pe'],
            ['dni' => '71000019', 'nombres' => 'Fabricio Emmanuel Luna Ñique', 'carrera' => 'Mecatrónica Automotriz', 'correo' => 'fabricio.luna@iesp.edu.pe'],
            ['dni' => '71000020', 'nombres' => 'Génesis Milagros Lozano Chávez', 'carrera' => 'Plataformas y Servicios de Tecnologías de la Información', 'correo' => 'genesis.lozano@iesp.edu.pe'],
        ];

        $inserted = 0;
        foreach ($demoStudents as $student) {
            Estudiante::query()->firstOrCreate(
                ['dni' => $student['dni']],
                $student
            );
            $inserted++;
        }

        app(BackupDemoDatabase::class)->clearCache();

        return redirect()
            ->route('estudiantes.index')
            ->with('success', "¡Se cargaron {$inserted} estudiantes institucionales de prueba directamente en la base de datos!");
    }
}
