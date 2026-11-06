<?php

namespace Database\Factories;

use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estudiante>
 */
class EstudianteFactory extends Factory
{
    protected $model = Estudiante::class;

    public function definition(): array
    {
        return [
            'dni' => str_pad((string) fake()->unique()->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'nombres' => fake()->name(),
            'carrera' => fake()->randomElement([
                'Plataformas y Servicios de Tecnologías de la Información',
                'Desarrollo de Sistemas de Información',
                'Contabilidad y Finanzas',
                'Administración de Empresas',
                'Mecatrónica Automotriz',
            ]),
            'correo' => fake()->unique()->safeEmail(),
        ];
    }
}
