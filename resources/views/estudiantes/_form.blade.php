@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="dni" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">
            DNI (8 dígitos) <span class="text-rose-500">*</span>
        </label>
        <input
            id="dni"
            name="dni"
            type="text"
            inputmode="numeric"
            maxlength="8"
            value="{{ old('dni', $estudiante->dni ?? '') }}"
            placeholder="Ej. 72345678"
            required
            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-mono text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10 @error('dni') border-rose-400 bg-rose-50/20 @enderror"
        >
        @error('dni')
            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="nombres" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">
            Nombres y Apellidos Completos <span class="text-rose-500">*</span>
        </label>
        <input
            id="nombres"
            name="nombres"
            type="text"
            maxlength="100"
            value="{{ old('nombres', $estudiante->nombres ?? '') }}"
            placeholder="Ej. Ana Torres Morales"
            required
            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10 @error('nombres') border-rose-400 bg-rose-50/20 @enderror"
        >
        @error('nombres')
            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="carrera" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">
            Carrera / Programa de Estudios <span class="text-rose-500">*</span>
        </label>
        <input
            id="carrera"
            name="carrera"
            type="text"
            maxlength="150"
            value="{{ old('carrera', $estudiante->carrera ?? '') }}"
            placeholder="Ej. Plataformas y Servicios de Tecnologías de la Información"
            required
            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10 @error('carrera') border-rose-400 bg-rose-50/20 @enderror"
        >
        @error('carrera')
            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="correo" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">
            Correo Electrónico <span class="text-rose-500">*</span>
        </label>
        <input
            id="correo"
            name="correo"
            type="email"
            maxlength="255"
            value="{{ old('correo', $estudiante->correo ?? '') }}"
            placeholder="Ej. ana.torres@instituto.edu.pe"
            required
            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-mono text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10 @error('correo') border-rose-400 bg-rose-50/20 @enderror"
        >
        @error('correo')
            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
