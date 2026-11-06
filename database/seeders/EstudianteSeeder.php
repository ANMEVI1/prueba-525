<?php

namespace Database\Seeders;

use App\Models\Estudiante;
use Illuminate\Database\Seeder;

class EstudianteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estudiantes = [
            [
                'dni' => '72345678',
                'nombres' => 'Carlos Raúl Peña Alva',
                'carrera' => 'Plataformas y Servicios de TI',
                'correo' => 'cpena@iesp.edu.pe',
            ],
            [
                'dni' => '73456789',
                'nombres' => 'María Belén Quiñones Ramos',
                'carrera' => 'Desarrollo de Sistemas de Información',
                'correo' => 'mquinones@iesp.edu.pe',
            ],
            [
                'dni' => '74567890',
                'nombres' => 'José Manuel Ñique Flores',
                'carrera' => 'Plataformas y Servicios de TI',
                'correo' => 'jnique@iesp.edu.pe',
            ],
            [
                'dni' => '75678901',
                'nombres' => 'Lucía Gómez Zúñiga',
                'carrera' => 'Contabilidad y Finanzas',
                'correo' => 'lzuniga@iesp.edu.pe',
            ],
            [
                'dni' => '76789012',
                'nombres' => 'Ángel David Fernández Morales',
                'carrera' => 'Mecatrónica Automotriz',
                'correo' => 'afernandez@iesp.edu.pe',
            ],
            [
                'dni' => '77890123',
                'nombres' => 'Sofía Abigail Ortiz Núñez',
                'carrera' => 'Administración de Empresas',
                'correo' => 'sortiz@iesp.edu.pe',
            ],
            [
                'dni' => '78901234',
                'nombres' => 'Cristian Jesús Córdova Ríos',
                'carrera' => 'Plataformas y Servicios de TI',
                'correo' => 'ccordova@iesp.edu.pe',
            ],
            [
                'dni' => '79012345',
                'nombres' => 'Valeria Jimena Cañari Benítez',
                'carrera' => 'Desarrollo de Sistemas de Información',
                'correo' => 'vcanari@iesp.edu.pe',
            ],
        ];

        foreach ($estudiantes as $estudiante) {
            Estudiante::query()->firstOrCreate(
                ['dni' => $estudiante['dni']],
                $estudiante
            );
        }
    }
}
