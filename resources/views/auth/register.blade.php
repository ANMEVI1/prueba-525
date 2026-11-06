@extends('auth.layout')

@section('title', 'Crear cuenta')

@section('content')
    <div class="rounded-2xl border border-slate-200/90 bg-white p-7 sm:p-9 shadow-lg shadow-slate-900/5">
        <div class="mb-7">
            <span class="text-xs font-bold uppercase tracking-wider text-brand-700">Registro</span>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 mt-1">Crear una cuenta</h1>
            <p class="mt-1 text-sm text-slate-500">
                Registra un usuario para interactuar con el entorno de pruebas.
            </p>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Nombre completo</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10">
                @error('name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10">
                @error('email')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            @include('auth.partials.password-field', [
                'name' => 'password',
                'label' => 'Contraseña (mínimo 12 caracteres)',
                'autocomplete' => 'new-password',
                'required' => true
            ])

            @include('auth.partials.password-field', [
                'name' => 'password_confirmation',
                'id' => 'password_confirmation',
                'label' => 'Confirmar contraseña',
                'autocomplete' => 'new-password',
                'required' => true
            ])

            <x-ui.button type="submit" variant="primary" class="w-full mt-2" size="md">
                Crear cuenta de acceso
            </x-ui.button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-500">
            ¿Ya tienes una cuenta?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">
                Iniciar sesión
            </a>
        </p>
    </div>
@endsection
