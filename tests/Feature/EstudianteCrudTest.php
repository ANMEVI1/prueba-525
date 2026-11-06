<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstudianteCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_an_estudiante(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('estudiantes.store'), [
            'dni' => '12345678',
            'nombres' => 'Ana Torres',
            'carrera' => 'Ingeniería de Sistemas',
            'correo' => 'ana@example.com',
        ]);

        $response->assertRedirect(route('estudiantes.index'));
        $this->assertDatabaseHas('estudiantes', ['dni' => '12345678']);
    }

    public function test_it_validates_unique_dni_and_email(): void
    {
        $this->actingAs(User::factory()->create());

        Estudiante::create([
            'dni' => '12345678',
            'nombres' => 'Ana Torres',
            'carrera' => 'Ingeniería de Sistemas',
            'correo' => 'ana@example.com',
        ]);

        $response = $this->from(route('estudiantes.create'))->post(route('estudiantes.store'), [
            'dni' => '12345678',
            'nombres' => 'Luis Pérez',
            'carrera' => 'Contabilidad',
            'correo' => 'ana@example.com',
        ]);

        $response->assertRedirect(route('estudiantes.create'))
            ->assertSessionHasErrors(['dni', 'correo']);
    }

    public function test_it_searches_updates_and_deletes_an_estudiante(): void
    {
        $this->actingAs(User::factory()->create());

        $estudiante = Estudiante::create([
            'dni' => '12345678',
            'nombres' => 'Ana Torres',
            'carrera' => 'Ingeniería de Sistemas',
            'correo' => 'ana@example.com',
        ]);

        $this->get(route('estudiantes.index', ['buscar' => '12345678']))
            ->assertOk()
            ->assertSee('Ana Torres');

        $this->put(route('estudiantes.update', $estudiante), [
            'dni' => '12345678',
            'nombres' => 'Ana Actualizada',
            'carrera' => 'Derecho',
            'correo' => 'actualizada@example.com',
        ])->assertRedirect(route('estudiantes.index'));

        $this->assertDatabaseHas('estudiantes', ['nombres' => 'Ana Actualizada']);

        $this->delete(route('estudiantes.destroy', $estudiante))
            ->assertRedirect(route('estudiantes.index'));

        $this->assertDatabaseMissing('estudiantes', ['dni' => '12345678']);
    }
}
