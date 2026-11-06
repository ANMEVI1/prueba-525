<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_audit_logs(): void
    {
        $this->get(route('audit.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_and_filter_audit_logs(): void
    {
        $user = User::factory()->create();

        AuditLog::factory()->create([
            'user_id' => $user->id,
            'action' => AuditAction::Login,
            'description' => 'Inicio de sesión de prueba',
        ]);

        $response = $this->actingAs($user)->get(route('audit.index'));

        $response->assertOk()
            ->assertViewIs('audit.index')
            ->assertSee('Inicio de sesión de prueba');
    }
}
