<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sistema de respaldos automáticos y recuperación de datos para garantizar la continuidad operativa en el IESP Cap. FAP José Abelardo Quiñones.">
    <title>@yield('title', $title ?? 'Continuidad TI | IESP Cap. FAP José Abelardo Quiñones')</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans text-slate-900 bg-slate-50 antialiased selection:bg-brand-100 selection:text-brand-900">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">

        <!-- ========================================================= -->
        <!-- SIDEBAR ESCRITORIO                                         -->
        <!-- ========================================================= -->
        <aside class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:w-[280px] bg-navy-950 text-slate-300 border-r border-navy-800 z-30">
            <!-- Brand -->
            <div class="flex items-center gap-3.5 px-6 py-5 border-b border-white/10 bg-navy-950/90 backdrop-blur-xs">
                <div class="size-11 shrink-0 grid place-items-center rounded-xl bg-white p-1 border border-white/20 shadow-xs">
                    <img src="{{ asset('insignia.png') }}" alt="Insignia IESP Cap. FAP José Abelardo Quiñones" class="size-full object-contain" width="44" height="44">
                </div>
                <div class="min-w-0">
                    <a href="{{ route('dashboard') }}" class="block text-sm font-bold text-white tracking-tight hover:text-sky-300 transition truncate">
                        Continuidad TI
                    </a>
                    <p class="text-[11px] text-slate-400 truncate">IESP Cap. FAP J.A. Quiñones</p>
                </div>
            </div>

            <!-- Demo pill notice -->
            <div class="mx-5 mt-4 flex items-center justify-between rounded-xl bg-white/5 px-3 py-2 border border-white/10 text-xs">
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-slate-300 font-medium">Entorno Seguro</span>
                </div>
                <span class="text-[10px] text-slate-400 font-mono">Modo Demo</span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-5 space-y-1 overflow-y-auto" aria-label="Navegación principal">
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Módulos del Sistema</p>

                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dashboard', 'arquitectura') ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <x-ui.icon name="activity" class="size-5 shrink-0 {{ request()->routeIs('dashboard', 'arquitectura') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
                    <span>Panel de Control</span>
                </a>

                <a href="{{ route('backups.index') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('backups.index') ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <x-ui.icon name="shield-check" class="size-5 shrink-0 {{ request()->routeIs('backups.index') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
                    <span class="flex-1">Respaldos y Recuperación</span>
                </a>

                <a href="{{ route('data-sources.index') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('data-sources.*') ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <x-ui.icon name="database" class="size-5 shrink-0 {{ request()->routeIs('data-sources.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
                    <span>Fuentes de Datos</span>
                </a>

                <a href="{{ route('audit.index') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('audit.*') ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <x-ui.icon name="clock" class="size-5 shrink-0 {{ request()->routeIs('audit.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
                    <span>Auditoría de Eventos</span>
                </a>

                <a href="{{ route('estudiantes.index') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('estudiantes.*') ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <x-ui.icon name="users" class="size-5 shrink-0 {{ request()->routeIs('estudiantes.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
                    <span>Estudiantes</span>
                </a>

                <div class="pt-4 pb-2">
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Documentación Técnica</p>
                </div>

                <a href="{{ route('backups.runbook') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('backups.runbook') ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <x-ui.icon name="terminal" class="size-5 shrink-0 {{ request()->routeIs('backups.runbook') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
                    <span>Manual de Restauración</span>
                </a>

                <a href="{{ route('project.index') }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('project.*') ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <x-ui.icon name="file-text" class="size-5 shrink-0 {{ request()->routeIs('project.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
                    <span>Ficha del Proyecto</span>
                </a>
            </nav>

            <!-- Sidebar footer / Institutional credentials -->
            <div class="p-5 border-t border-white/10 bg-navy-900/50 text-xs text-slate-400 space-y-1">
                <p class="font-bold text-slate-200 uppercase tracking-wider text-[10px]">Proyecto de Innovación</p>
                <p class="text-[11px] leading-relaxed text-slate-300 font-medium">Plataformas y Servicios de TI</p>
                <div class="pt-2 text-[10px] space-y-0.5 text-slate-400 border-t border-white/5">
                    <p><span class="text-slate-300">Asesor:</span> Prof. Tec. Juan Gabriel Alva</p>
                    <p><span class="text-slate-300">Responsable:</span> Julio A. Delgado Avila</p>
                </div>
            </div>
        </aside>

        <!-- ========================================================= -->
        <!-- SIDEBAR MOBILE (DRAWER)                                   -->
        <!-- ========================================================= -->
        <div id="mobile-sidebar" class="hidden fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-xs transition-opacity" id="close-sidebar"></div>
            <div class="fixed inset-y-0 left-0 w-72 bg-navy-950 p-6 text-slate-300 flex flex-col shadow-2xl">
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="size-10 grid place-items-center rounded-lg bg-white p-1">
                            <img src="{{ asset('insignia.png') }}" alt="Insignia" class="size-full object-contain">
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Continuidad TI</p>
                            <p class="text-[11px] text-slate-400">IESP J.A. Quiñones</p>
                        </div>
                    </div>
                </div>

                <nav class="mt-6 space-y-1.5 flex-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('dashboard', 'arquitectura') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <x-ui.icon name="activity" class="size-5" /> Panel de Control
                    </a>
                    <a href="{{ route('backups.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('backups.index') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <x-ui.icon name="shield-check" class="size-5" /> Respaldos y Recuperación
                    </a>
                    <a href="{{ route('backups.runbook') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('backups.runbook') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <x-ui.icon name="terminal" class="size-5" /> Manual de Restauración
                    </a>
                    <a href="{{ route('data-sources.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('data-sources.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <x-ui.icon name="database" class="size-5" /> Fuentes de Datos
                    </a>
                    <a href="{{ route('audit.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('audit.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <x-ui.icon name="clock" class="size-5" /> Auditoría de Eventos
                    </a>
                    <a href="{{ route('estudiantes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('estudiantes.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <x-ui.icon name="users" class="size-5" /> Estudiantes
                    </a>
                    <a href="{{ route('project.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('project.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/5' }}">
                        <x-ui.icon name="file-text" class="size-5" /> Ficha del Proyecto
                    </a>
                </nav>

                <div class="pt-4 border-t border-white/10">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg bg-white/10 text-white text-xs font-semibold hover:bg-white/15">
                            <x-ui.icon name="log-out" class="size-4" /> Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- MAIN VIEWPORT                                             -->
        <!-- ========================================================= -->
        <div class="lg:col-start-2 min-w-0 flex flex-col min-h-screen">
            <!-- Header bar -->
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200/90 bg-white/95 px-4 sm:px-8 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <button type="button" id="toggle-sidebar" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none" aria-label="Abrir menú">
                        <x-ui.icon name="menu" class="size-6" />
                    </button>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Centro de Operaciones</span>
                            <span class="text-slate-300">/</span>
                            <span class="text-xs font-semibold text-brand-700">@yield('page_title', $pageTitle ?? 'Panel')</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    <x-ui.badge tone="warning" size="sm" class="hidden sm:inline-flex">
                        Modo Demostración
                    </x-ui.badge>

                    <div class="hidden md:flex items-center gap-2 text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                        <x-ui.icon name="shield-check" class="size-4 text-emerald-600" />
                        <span>RTO &lt; 15 min</span>
                    </div>

                    <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                    <button type="button" data-modal-open="#modal-admin-password" class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition cursor-pointer group text-left" title="Cambiar contraseña de Administrador">
                        <div class="size-8 rounded-full bg-navy-900 text-white grid place-items-center font-bold text-xs ring-2 ring-slate-100 group-hover:ring-brand-500 transition">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden text-left sm:block">
                            <p class="text-xs font-bold text-slate-800 leading-tight group-hover:text-brand-700 transition flex items-center gap-1">
                                {{ auth()->user()->name ?? 'Administrador' }}
                                <x-ui.icon name="key" class="size-3 text-slate-400 group-hover:text-brand-600 transition" />
                            </p>
                            <p class="text-[10px] text-slate-500 font-medium">TI Institucional</p>
                        </div>
                    </button>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition focus:outline-none focus:ring-2 focus:ring-slate-900/10" title="Cerrar sesión">
                            <x-ui.icon name="log-out" class="size-3.5" />
                            <span class="hidden md:inline">Salir</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Main Content Container -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto">
                <!-- Global flash notifications -->
                @if (session('success'))
                    <x-ui.alert tone="success" :dismissible="true">
                        {{ session('success') }}
                    </x-ui.alert>
                @endif

                @if (session('error'))
                    <x-ui.alert tone="danger" :dismissible="true">
                        {{ session('error') }}
                    </x-ui.alert>
                @endif

                @if (session('status'))
                    <x-ui.alert tone="info" :dismissible="true">
                        {{ session('status') }}
                    </x-ui.alert>
                @endif

                @if (session('backup_status'))
                    <x-ui.alert tone="success" :dismissible="true" title="Notificación de Respaldo">
                        {{ session('backup_status') }}
                    </x-ui.alert>
                @endif

                @yield('content')
                {{ $slot ?? '' }}
            </main>

            <!-- Institutional Footer -->
            <footer class="border-t border-slate-200 bg-white px-6 py-4 text-center text-xs text-slate-500">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p class="font-medium text-slate-600">
                        IESP Cap. FAP José Abelardo Quiñones (Tumbes) &copy; {{ date('Y') }}
                    </p>
                    <p class="text-slate-400">
                        Sistema de Respaldos Automáticos y Recuperación de Datos · Plataformas y Servicios de TI
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal: Cambio de Contraseña de Administrador -->
    <x-ui.modal id="modal-admin-password" title="Cambiar Contraseña de Administrador" max-width="md">
        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            @include('auth.partials.password-field', [
                'name' => 'current_password',
                'id' => 'admin_current_password',
                'label' => 'Contraseña Actual',
                'autocomplete' => 'current-password',
                'required' => true,
            ])

            @include('auth.partials.password-field', [
                'name' => 'password',
                'id' => 'admin_password',
                'label' => 'Nueva Contraseña',
                'autocomplete' => 'new-password',
                'required' => true,
            ])

            @include('auth.partials.password-field', [
                'name' => 'password_confirmation',
                'id' => 'admin_password_confirmation',
                'label' => 'Confirmar Nueva Contraseña',
                'autocomplete' => 'new-password',
                'required' => true,
            ])

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
                <x-ui.button type="button" variant="outline" size="sm" data-modal-close>
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" size="sm">
                    Guardar Nueva Contraseña
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    @if ($errors->has('current_password') || $errors->has('password'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.querySelector('#modal-admin-password');
                if (modal) {
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }
            });
        </script>
    @endif
</body>
</html>
