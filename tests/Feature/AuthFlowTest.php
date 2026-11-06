<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads_the_institutional_background_image(): void
    {
        $this->get(route('login'))
            ->assertSee('insignia.png');
    }

    public function test_guest_is_redirected_to_login_for_protected_pages(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));

        $this->get('/arquitectura')
            ->assertRedirect(route('login'));
    }

    public function test_user_can_log_in_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Correct-password-123'),
        ]);

        $this->post(route('login.store'), [
            'username' => $user->email,
            'password' => 'Correct-password-123',
        ])->assertRedirect(route('arquitectura'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate_user(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => $user->email,
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_registration_creates_and_authenticates_a_user_when_enabled(): void
    {
        config(['auth.registration_enabled' => true]);

        $this->post(route('register.store'), [
            'name' => 'Cuenta de demostración',
            'email' => 'demo-registration@example.test',
            'password' => 'Long-Demo-Password-123',
            'password_confirmation' => 'Long-Demo-Password-123',
        ])->assertRedirect(route('arquitectura'));

        $user = User::query()->where('email', 'demo-registration@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('Long-Demo-Password-123', $user->password));
    }

    public function test_registration_is_unavailable_when_disabled(): void
    {
        config(['auth.registration_enabled' => false]);

        $this->get(route('register'))
            ->assertNotFound();

        $this->post(route('register.store'), [])
            ->assertNotFound();
    }

    public function test_forgot_password_sends_a_reset_notification_without_revealing_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);

        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertSessionHas('status', 'Si el correo está asociado a una cuenta, recibirás instrucciones para restablecer la contraseña.');
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'password' => Hash::make('Old-password-123'),
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        $this->assertIsString($token);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'New-password-123',
            'password_confirmation' => 'New-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('New-password-123', $user->fresh()->password));
    }

    public function test_user_can_log_out_and_session_is_cleared(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_authenticated_user_can_update_password_from_modal(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Current-Secret-123'),
        ]);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'Current-Secret-123',
            'password' => 'Brand-New-Secret-456',
            'password_confirmation' => 'Brand-New-Secret-456',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('Brand-New-Secret-456', $user->fresh()->password));
    }

    public function test_password_update_fails_with_invalid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Current-Secret-123'),
        ]);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'Wrong-Password',
            'password' => 'Brand-New-Secret-456',
            'password_confirmation' => 'Brand-New-Secret-456',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $this->assertFalse(Hash::check('Brand-New-Secret-456', $user->fresh()->password));
    }
}
