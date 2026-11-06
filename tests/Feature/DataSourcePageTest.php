<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataSourcePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_data_sources(): void
    {
        $this->get(route('data-sources.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_data_sources(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('data-sources.index'));

        $response->assertOk()
            ->assertViewIs('data-sources.index')
            ->assertViewHasAll(['overview', 'tables', 'selectedTable']);
    }
}
