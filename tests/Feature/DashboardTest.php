<?php

namespace Tests\Feature;

use App\Models\DemoBackup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard_with_metrics(): void
    {
        $user = User::factory()->create();
        DemoBackup::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertViewIs('dashboard.index')
            ->assertViewHasAll(['metrics', 'demoDatabase', 'recentBackups', 'recentAudits']);
    }
}
