@extends('auth.layout')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="rounded-2xl border border-slate-200/90 bg-white p-7 sm:p-9 shadow-lg shadow-slate-900/5">
        <div class="mb-7">
            <span class="text-xs font-bold uppercase tracking-wider text-brand-700">Acceso Institucional</span>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 mt-1">Iniciar sesión</h1>
            <p class="mt-1 text-sm text-slate-500">
                Ingresa con tus credenciales de soporte o administración TI.
            </p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="username" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">
                    Usuario o Correo
                </label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Admin o correo@instituto.edu.pe"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10"
                >
                @error('username')
                    <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            @include('auth.partials.password-field', [
                'name' => 'password',
                'id' => 'password',
                'label' => 'Contraseña',
                'autocomplete' => 'current-password',
                'required' => true
            ])

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>Recordar sesión</span>
                </label>
                <a href="{{ route('password.request') }}" class="font-semibold text-brand-700 hover:text-brand-900 transition">
                    ¿Olvidaste tu clave?
                </a>
            </div>

            <x-ui.button type="submit" variant="primary" class="w-full mt-2" size="md">
                Ingresar al Centro de Operaciones
            </x-ui.button>
        </form>

        @if (config('auth.registration_enabled'))
            <p class="mt-6 text-center text-xs text-slate-500">
                ¿No tienes acceso?
                <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-900">
                    Registrar cuenta
                </a>
            </p>
        @endif

        @if (config('auth.demo_admin.enabled'))
            <div class="mt-6 rounded-xl border border-sky-100 bg-sky-50/70 p-3.5 text-xs text-sky-950">
                <div class="flex items-center gap-1.5 font-bold text-sky-900">
                    <x-ui.icon name="key" class="size-3.5 text-sky-700" />
                    <span>Credenciales de prueba local</span>
                </div>
                <p class="mt-1 text-slate-600">
                    Usuario: <code class="px-1.5 py-0.5 rounded bg-white font-mono font-bold text-sky-900 border border-sky-200">Admin</code>
                    · Clave: <code class="px-1.5 py-0.5 rounded bg-white font-mono font-bold text-sky-900 border border-sky-200">Admin</code>
                </p>
            </div>
        @endif
    </div>
@endsection
