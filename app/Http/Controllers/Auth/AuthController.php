<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->where('name', $validated['username'])
            ->orWhere('email', $validated['username'])
            ->first();

        if (! $user || ! Auth::attempt([
            'email' => $user->email,
            'password' => $validated['password'],
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'Las credenciales no son válidas.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        $this->auditLogger->record(
            AuditAction::Login,
            "Inicio de sesión correcto del usuario {$user->name}.",
            $user,
            ['email' => $user->email],
            $user,
        );

        return redirect()->intended(route('arquitectura'));
    }

    public function createRegistration(): View
    {
        abort_unless(config('auth.registration_enabled'), 404);

        return view('auth.register');
    }

    public function storeRegistration(Request $request): RedirectResponse
    {
        abort_unless(config('auth.registration_enabled'), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $this->auditLogger->record(
            AuditAction::Login,
            "Registro e inicio de sesión de {$user->name}.",
            $user,
            ['email' => $user->email],
            $user,
        );

        return redirect()->route('arquitectura');
    }

    public function createForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return back()->with('status', 'Si el correo está asociado a una cuenta, recibirás instrucciones para restablecer la contraseña.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function createPasswordReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        return back()
            ->withErrors(['email' => [__($status)]])
            ->withInput($request->only('email'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $this->auditLogger->record(
            AuditAction::PasswordUpdated,
            "Cambio de contraseña exitoso del usuario {$user->name}.",
            $user,
            [],
            $user,
        );

        return back()->with('success', 'Contraseña de administrador actualizada correctamente.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->auditLogger->record(
                AuditAction::Logout,
                "Cierre de sesión del usuario {$user->name}.",
                $user,
                [],
                $user,
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
