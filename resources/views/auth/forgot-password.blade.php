@extends('auth.layout')

@section('title', 'Recuperar contraseña')

@section('content')
    <div class="rounded-2xl border border-slate-200/90 bg-white p-7 sm:p-9 shadow-lg shadow-slate-900/5">
        <div class="mb-7">
            <span class="text-xs font-bold uppercase tracking-wider text-brand-700">Seguridad</span>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 mt-1">Recuperar contraseña</h1>
            <p class="mt-1 text-sm text-slate-500">
                Escribe tu correo institucional para enviarte un enlace de restablecimiento.
            </p>
        </div>

        @if (config('mail.default') === 'log')
            <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                Modo local activo: el enlace de restablecimiento se genera en <code class="font-mono text-slate-800">storage/logs/laravel.log</code>.
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10">
                @error('email')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <x-ui.button type="submit" variant="primary" class="w-full mt-2" size="md">
                Enviar enlace de recuperación
            </x-ui.button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-500">
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900 inline-flex items-center gap-1">
                &larr; Volver al inicio de sesión
            </a>
        </p>
    </div>
@endsection
