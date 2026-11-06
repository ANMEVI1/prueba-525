<?php

namespace App\Http\Requests;

use App\Models\Estudiante;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Estudiante|null $estudiante */
        $estudiante = $this->route('estudiante');

        return [
            'dni' => [
                'required',
                'digits:8',
                Rule::unique('estudiantes', 'dni')->ignore($estudiante),
            ],
            'nombres' => ['required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'carrera' => ['required', 'string', 'max:150'],
            'correo' => [
                'required',
                'email',
                'max:255',
                Rule::unique('estudiantes', 'correo')->ignore($estudiante),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.digits' => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.unique' => 'Ya existe un estudiante registrado con este DNI.',
            'nombres.required' => 'El nombre completo es obligatorio.',
            'nombres.regex' => 'Los nombres solo pueden contener letras y espacios.',
            'carrera.required' => 'La carrera es obligatoria.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.unique' => 'Este correo electrónico ya está registrado por otro estudiante.',
        ];
    }
}
