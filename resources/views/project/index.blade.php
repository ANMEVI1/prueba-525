@extends('layouts.app')

@section('title', 'Ficha del Proyecto de Innovación | Continuidad TI')
@section('page_title', 'Ficha del Proyecto')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/90 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <x-ui.badge tone="brand" size="xs">Documentación Oficial</x-ui.badge>
                <span class="text-xs text-slate-400 font-medium">Extraída de alva.docx</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 mt-1">Proyecto de Innovación Tecnológica</h1>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge tone="neutral" size="md">Año Académico {{ $dossier['academic_year'] }}</x-ui.badge>
        </div>
    </div>

    <!-- General Information Card -->
    <x-ui.card>
        <x-slot:title>Información General del Proyecto</x-slot:title>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 text-xs">
            <div class="sm:col-span-2 lg:col-span-3 pb-3 border-b border-slate-100">
                <dt class="font-bold text-slate-400 uppercase tracking-wider">Título del Proyecto</dt>
                <dd class="mt-1 text-sm font-bold text-slate-900 leading-snug">{{ $dossier['title'] }}</dd>
            </div>

            <div>
                <dt class="font-bold text-slate-400 uppercase tracking-wider">Programa de Estudios</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $dossier['program'] }}</dd>
            </div>

            <div>
                <dt class="font-bold text-slate-400 uppercase tracking-wider">Línea de Innovación</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $dossier['innovation_line'] }}</dd>
            </div>

            <div>
                <dt class="font-bold text-slate-400 uppercase tracking-wider">Lugar de Ejecución</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $dossier['location'] }}</dd>
            </div>

            <div>
                <dt class="font-bold text-slate-400 uppercase tracking-wider">Responsable del Proyecto</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $dossier['lead'] }}</dd>
            </div>

            <div>
                <dt class="font-bold text-slate-400 uppercase tracking-wider">Docente Asesor</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $dossier['advisor'] }}</dd>
            </div>

            <div>
                <dt class="font-bold text-slate-400 uppercase tracking-wider">Duración y Fechas</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $dossier['duration'] }}</dd>
            </div>

            <div class="sm:col-span-2 lg:col-span-3 pt-3 border-t border-slate-100 grid sm:grid-cols-2 gap-4">
                <div>
                    <dt class="font-bold text-slate-400 uppercase tracking-wider">Beneficiarios Directos</dt>
                    <dd class="mt-1 text-slate-700 leading-relaxed">{{ $dossier['direct_beneficiaries'] }}</dd>
                </div>
                <div>
                    <dt class="font-bold text-slate-400 uppercase tracking-wider">Beneficiarios Indirectos</dt>
                    <dd class="mt-1 text-slate-700 leading-relaxed">{{ $dossier['indirect_beneficiaries'] }}</dd>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Justification Grid (4 Pillars) -->
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="size-8 rounded-lg bg-sky-50 text-sky-700 font-bold text-xs grid place-items-center mb-3">01</span>
            <h3 class="text-sm font-bold text-slate-900">Justificación Tecnológica</h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                Reemplaza procesos manuales propensos al error por automatización inteligente, empaquetado seguro y cifrado en almacenamiento secundario.
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="size-8 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs grid place-items-center mb-3">02</span>
            <h3 class="text-sm font-bold text-slate-900">Justificación Social</h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                Protege el expediente y notas de la comunidad estudiantil, garantizando que su historial académico nunca sufra pérdidas ni retrasos.
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="size-8 rounded-lg bg-amber-50 text-amber-700 font-bold text-xs grid place-items-center mb-3">03</span>
            <h3 class="text-sm font-bold text-slate-900">Justificación Académica</h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                Aplica los conocimientos de la carrera de Plataformas y Servicios de TI, integrando redes, servidores, PowerShell/Bash y criptografía.
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="size-8 rounded-lg bg-purple-50 text-purple-700 font-bold text-xs grid place-items-center mb-3">04</span>
            <h3 class="text-sm font-bold text-slate-900">Justificación Económica</h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                Solución de código abierto sobre hardware ya existente en el instituto, evitando gastos onerosos en licencias propietarias comerciales.
            </p>
        </div>
    </div>

    <!-- Objectives & Technical Flow -->
    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card>
            <x-slot:title>Objetivos del Proyecto</x-slot:title>
            <x-slot:description>Metas fijadas en el documento de titulación.</x-slot:description>

            <div class="space-y-4 text-xs">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80">
                    <p class="font-bold text-slate-900">Objetivo General</p>
                    <p class="mt-1 text-slate-700 leading-relaxed">
                        Implementar un sistema de respaldos automáticos y recuperación de datos mediante scripts de automatización y herramientas de código abierto para asegurar la continuidad operativa y evitar la pérdida de información crítica en los servidores locales del IESP Cap. FAP José Abelardo Quiñones.
                    </p>
                </div>

                <div class="space-y-2">
                    <p class="font-bold text-slate-900">Objetivos Específicos</p>
                    <ul class="space-y-2 text-slate-600">
                        <li class="flex items-start gap-2">
                            <span class="size-1.5 rounded-full bg-brand-600 mt-1.5 shrink-0"></span>
                            <span>Diseñar la arquitectura de almacenamiento y frecuencia según criticidad de datos.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="size-1.5 rounded-full bg-brand-600 mt-1.5 shrink-0"></span>
                            <span>Desarrollar scripts de automatización (Bash/PowerShell) y tareas programadas (Cron/TaskScheduler).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="size-1.5 rounded-full bg-brand-600 mt-1.5 shrink-0"></span>
                            <span>Implementar el sistema en el servidor local de prueba del instituto.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="size-1.5 rounded-full bg-brand-600 mt-1.5 shrink-0"></span>
                            <span>Validar mediante simulación de fallos (apagones y borrado controlado).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="size-1.5 rounded-full bg-brand-600 mt-1.5 shrink-0"></span>
                            <span>Evaluar los tiempos de recuperación (RTO &lt; 15 min) y la integridad de datos restaurados.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:title>Cronograma de Actividades (4 Meses)</x-slot:title>
            <x-slot:description>Despliegue metodológico de cascada adaptada a infraestructura.</x-slot:description>

            <div class="overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 uppercase text-[10px] text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3 font-bold">Actividad / Etapa</th>
                            <th class="py-2.5 px-2 text-center font-bold">Mes 1</th>
                            <th class="py-2.5 px-2 text-center font-bold">Mes 2</th>
                            <th class="py-2.5 px-2 text-center font-bold">Mes 3</th>
                            <th class="py-2.5 px-2 text-center font-bold">Mes 4</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($dossier['schedule'] as $item)
                            <tr>
                                <td class="py-2.5 px-3 font-semibold text-slate-800">{{ $item['stage'] }}</td>
                                <td class="py-2.5 px-2 text-center">
                                    @if($item['m1']) <span class="size-5 mx-auto rounded bg-brand-600 text-white font-bold grid place-items-center text-[11px]">&check;</span> @else <span class="text-slate-300">&mdash;</span> @endif
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    @if($item['m2']) <span class="size-5 mx-auto rounded bg-brand-600 text-white font-bold grid place-items-center text-[11px]">&check;</span> @else <span class="text-slate-300">&mdash;</span> @endif
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    @if($item['m3']) <span class="size-5 mx-auto rounded bg-brand-600 text-white font-bold grid place-items-center text-[11px]">&check;</span> @else <span class="text-slate-300">&mdash;</span> @endif
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    @if($item['m4']) <span class="size-5 mx-auto rounded bg-brand-600 text-white font-bold grid place-items-center text-[11px]">&check;</span> @else <span class="text-slate-300">&mdash;</span> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Budget Table -->
            <div class="mt-6 pt-4 border-t border-slate-100">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Presupuesto Ejecutado</h4>
                <div class="overflow-x-auto text-xs">
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($dossier['budget'] as $b)
                                <tr>
                                    <td class="py-1.5 text-slate-700">{{ $b['concept'] }}</td>
                                    <td class="py-1.5 text-slate-500 text-center">{{ $b['quantity'] }}</td>
                                    <td class="py-1.5 text-right font-mono font-bold text-slate-900">{{ $b['cost'] }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-bold bg-slate-50 border-t border-slate-200">
                                <td class="py-2 px-2 text-slate-900">Total Inversión</td>
                                <td></td>
                                <td class="py-2 px-2 text-right font-mono text-brand-700">S/. 100.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection

