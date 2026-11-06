<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArquitecturaPageViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_dashboard_and_architecture_alias(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertOk();
        $this->get('/arquitectura')->assertOk();
        $this->get('/dashboard')->assertOk();
    }

    public function test_architecture_page_shows_real_status_and_recovery_targets(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/');

        $response->assertSee('Insignia')
            ->assertSee('Respaldar Base de Datos')
            ->assertSee('Historial de Respaldos')
            ->assertSee('15 min')
            ->assertSee('SHA-256')
            ->assertSee('AES-256');
    }
}
