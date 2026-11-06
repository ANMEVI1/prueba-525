<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Acceso al panel de demostración de continuidad institucional.">
    <title>@yield('title') | Continuidad TI</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans text-slate-900 bg-slate-50 antialiased selection:bg-brand-100 selection:text-brand-900">
    <main class="min-h-screen lg:grid lg:grid-cols-[1.1fr_1fr]">

        <!-- Left Brand Banner (Hero) -->
        <section class="relative hidden overflow-hidden bg-navy-950 px-12 py-16 text-white lg:flex lg:flex-col lg:justify-between">
            <!-- Background institutional geometric gradient -->
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-navy-950 via-navy-900 to-brand-900 opacity-90"></div>
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_25%,rgba(14,165,233,0.18),transparent_40%),radial-gradient(circle_at_80%_80%,rgba(3,105,161,0.2),transparent_45%)]"></div>

            <!-- Top Header in Hero -->
            <div class="relative flex items-center gap-3.5">
                <span class="grid size-14 place-items-center rounded-2xl bg-white p-1.5 shadow-md">
                    <img src="{{ asset('insignia.png') }}" alt="Insignia IESP" class="size-full object-contain" width="48" height="48">
                </span>
                <div>
                    <h1 class="text-lg font-extrabold tracking-tight text-white">Continuidad TI</h1>
                    <p class="text-xs text-sky-200 font-medium">IESP Cap. FAP José Abelardo Quiñones</p>
                </div>
            </div>

            <!-- Central Pitch -->
            <div class="relative max-w-lg py-12">
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-400/30 bg-sky-500/10 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-sky-300 mb-6">
                    <x-ui.icon name="shield-check" class="size-4" />
                    Proyecto de Innovación Tecnológica
                </div>
                <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl leading-tight">
                    Respaldo y recuperación de datos para la continuidad operativa.
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-slate-300">
                    Sistema integral desarrollado para automatizar copias de seguridad de las bases de datos académicas institucionales, garantizando alta disponibilidad e integridad criptográfica.
                </p>

                <!-- Value metrics -->
                <div class="mt-8 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-xs">
                        <p class="text-xl font-black text-sky-300">&lt; 15 min</p>
                        <p class="mt-0.5 text-[11px] text-slate-400 font-medium">Meta RTO</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-xs">
                        <p class="text-xl font-black text-emerald-300">AES-256</p>
                        <p class="mt-0.5 text-[11px] text-slate-400 font-medium">Cifrado</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-xs">
                        <p class="text-xl font-black text-amber-300">SHA-256</p>
                        <p class="mt-0.5 text-[11px] text-slate-400 font-medium">Integridad</p>
                    </div>
                </div>
            </div>

            <!-- Footer in Hero -->
            <div class="relative pt-6 border-t border-white/10 text-xs text-slate-400 flex items-center justify-between">
                <span>Plataformas y Servicios de TI · Tumbes, Perú</span>
                <span class="text-slate-400">Año Académico 2026</span>
            </div>
        </section>

        <!-- Right Form Panel -->
        <section class="flex min-h-screen items-center justify-center p-6 sm:p-10 lg:p-12">
            <div class="w-full max-w-md space-y-6">
                <!-- Mobile brand logo -->
                <div class="flex items-center gap-3 lg:hidden pb-4 border-b border-slate-200">
                    <span class="grid size-12 place-items-center rounded-xl bg-white p-1 border border-slate-200 shadow-xs">
                        <img src="{{ asset('insignia.png') }}" alt="Insignia" class="size-full object-contain">
                    </span>
                    <div>
                        <p class="text-base font-bold text-slate-900">Continuidad TI</p>
                        <p class="text-xs text-slate-500">IESP Cap. FAP J.A. Quiñones</p>
                    </div>
                </div>

                @if (session('status'))
                    <x-ui.alert tone="info" :dismissible="true">
                        {{ session('status') }}
                    </x-ui.alert>
                @endif

                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
