<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_project_dossier(): void
    {
        $this->get(route('project.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_project_dossier(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('project.index'));

        $response->assertOk()
            ->assertViewIs('project.index')
            ->assertSee('IESP Cap. FAP José Abelardo Quiñones')
            ->assertSee('Julio Alexander Delgado Avila')
            ->assertSee('Juan Gabriel Alva Jimenez');
    }
}
