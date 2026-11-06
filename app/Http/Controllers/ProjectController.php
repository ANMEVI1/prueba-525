<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $dossier = [
            'institution' => 'IESP Cap. FAP José Abelardo Quiñones',
            'location' => 'Tumbes, Perú',
            'academic_year' => '2026',
            'title' => 'Implementación de un sistema de respaldos automáticos y recuperación de datos para garantizar la continuidad operativa en los servidores locales del IESP Cap. FAP José Abelardo Quiñones',
            'program' => 'Plataformas y Servicios de Tecnologías de la Información',
            'innovation_line' => 'Gestión de Infraestructura Tecnológica y Seguridad de Datos',
            'advisor' => 'Prof. Tec. Juan Gabriel Alva Jimenez',
            'lead' => 'Julio Alexander Delgado Avila',
            'duration' => '4 meses (17/08/2026 – 04/12/2026)',
            'direct_beneficiaries' => 'Área de soporte técnico, administración y docentes del instituto.',
            'indirect_beneficiaries' => 'Estudiantes del instituto (al garantizar que la información académica y servicios no se pierdan).',
            'rto_target' => 'Menos de 15 minutos',
            'integrity_target' => '100% verificado mediante hash criptográfico SHA-256 y pruebas SQLite',
            'budget' => [
                ['concept' => 'Materiales (apuntes, guías impresas)', 'quantity' => 'Global', 'cost' => 'S/. 30.00'],
                ['concept' => 'Software (Herramientas libres / Open Source)', 'quantity' => '0 (Free)', 'cost' => 'S/. 0.00'],
                ['concept' => 'Equipos (Uso de PC de prácticas del instituto)', 'quantity' => 'Existente', 'cost' => 'S/. 0.00'],
                ['concept' => 'Servicios (Internet para consultas y descarga de paquetes)', 'quantity' => '1 mes', 'cost' => 'S/. 40.00'],
                ['concept' => 'Otros (Imprevistos)', 'quantity' => 'Global', 'cost' => 'S/. 30.00'],
            ],
            'schedule' => [
                ['stage' => 'Planificación', 'm1' => true, 'm2' => false, 'm3' => false, 'm4' => false],
                ['stage' => 'Diseño de Arquitectura', 'm1' => true, 'm2' => false, 'm3' => false, 'm4' => false],
                ['stage' => 'Desarrollo de Scripts y Automatización', 'm1' => false, 'm2' => true, 'm3' => true, 'm4' => false],
                ['stage' => 'Pruebas y Simulación de Fallas', 'm1' => false, 'm2' => false, 'm3' => true, 'm4' => false],
                ['stage' => 'Implementación y Validación de RTO', 'm1' => false, 'm2' => false, 'm3' => false, 'm4' => true],
            ],
        ];

        return view('project.index', compact('dossier'));
    }
}
