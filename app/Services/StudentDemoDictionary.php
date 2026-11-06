<?php

namespace App\Services;

use App\Models\Estudiante;
use Illuminate\Support\Str;

class StudentDemoDictionary
{
    /**
     * Nombres masculinos comunes y auténticos del Perú.
     *
     * @var list<string>
     */
    private const array NOMBRES_MASCULINOS = [
        'Carlos', 'José', 'Luis', 'Juan', 'Miguel', 'Diego', 'Mateo', 'Sebastián',
        'Alejandro', 'Joaquín', 'Álvaro', 'Rodrigo', 'Christian', 'Fabricio', 'Esteban',
        'Gonzalo', 'Gabriel', 'Leonardo', 'Fernando', 'Javier', 'Adrián', 'Mauricio',
        'Renato', 'Manuel', 'Franco', 'Emilio', 'Vicente', 'Ignacio', 'Darío', 'Rafael',
        'Raúl', 'David', 'César', 'Rubén', 'Víctor', 'Óscar', 'Hugo', 'Felipe',
    ];

    /**
     * Nombres femeninos comunes y auténticos del Perú.
     *
     * @var list<string>
     */
    private const array NOMBRES_FEMENINOS = [
        'María', 'Ana', 'Lucía', 'Rosa', 'Carmen', 'Valeria', 'Camila', 'Luciana',
        'Fiorella', 'Andrea', 'Daniela', 'Romina', 'Melany', 'Ariana', 'Génesis',
        'Sofía', 'Abigail', 'Paola', 'Ximena', 'Natalia', 'Claudia', 'Patricia',
        'Diana', 'Gabriela', 'Alejandra', 'Carolina', 'Fabiola', 'Valeska', 'Jimena',
        'Bianca', 'Adriana', 'Fátima', 'Elena', 'Milagros', 'Isabel', 'Rocío',
    ];

    /**
     * Apellidos auténticos frecuentes en el Perú (Tumbes y nacional).
     *
     * @var list<string>
     */
    private const array APELLIDOS = [
        'Quispe', 'Flores', 'Rodríguez', 'Sánchez', 'García', 'Rojas', 'Díaz',
        'Torres', 'Ramírez', 'Gómez', 'Castillo', 'Morales', 'Ramos', 'Espinoza',
        'Fernández', 'Vargas', 'Alva', 'Quiñones', 'Peña', 'Ñique', 'Zúñiga',
        'Cárdenas', 'Núñez', 'Medina', 'Ríos', 'Benítez', 'Cañari', 'Huamán',
        'Salazar', 'Vílchez', 'Tello', 'Barreto', 'Vásquez', 'Córdova', 'Chávez',
        'Paredes', 'Herrera', 'Mendoza', 'Cruz', 'Lozano', 'Carranza', 'Luna',
        'Castro', 'Rivas', 'Ñáñez', 'Gutiérrez', 'Chunga', 'Fajardo', 'Bances',
        'Olivares', 'Palacios', 'Morán', 'Preciado', 'Zavala', 'Cornejo',
    ];

    /**
     * Carreras oficiales del IESP Cap. FAP José Abelardo Quiñones.
     *
     * @var list<string>
     */
    private const array CARRERAS = [
        'Plataformas y Servicios de Tecnologías de la Información',
        'Desarrollo de Sistemas de Información',
        'Contabilidad y Finanzas',
        'Administración de Empresas',
        'Mecatrónica Automotriz',
    ];

    /**
     * Genera e inserta una cantidad determinada de estudiantes únicos no repetidos.
     *
     * @param  int  $count  Cantidad de estudiantes a generar (por defecto 20)
     * @return list<Estudiante>
     */
    public function generateAndInsert(int $count = 20): array
    {
        // Traer DNIs y correos existentes en memoria para evitar colisiones a nivel de base de datos
        $existingDnis = Estudiante::query()->pluck('dni')->flip()->toArray();
        $existingEmails = Estudiante::query()->pluck('correo')->flip()->toArray();

        $generated = [];
        $careerIndex = 0;
        $careerCount = count(self::CARRERAS);

        for ($i = 0; $i < $count; $i++) {
            // 1. Generar DNI único de 8 dígitos (serie 70M - 79M)
            do {
                $dni = (string) mt_rand(70000000, 79999999);
            } while (isset($existingDnis[$dni]));
            $existingDnis[$dni] = true;

            // 2. Generar nombres y apellidos reales
            $isFemale = (mt_rand(0, 1) === 1);
            $namesPool = $isFemale ? self::NOMBRES_FEMENINOS : self::NOMBRES_MASCULINOS;

            $firstName = $namesPool[array_rand($namesPool)];
            $middleName = $namesPool[array_rand($namesPool)];
            while ($middleName === $firstName) {
                $middleName = $namesPool[array_rand($namesPool)];
            }

            $paterno = self::APELLIDOS[array_rand(self::APELLIDOS)];
            $materno = self::APELLIDOS[array_rand(self::APELLIDOS)];
            while ($materno === $paterno) {
                $materno = self::APELLIDOS[array_rand(self::APELLIDOS)];
            }

            $fullName = "{$firstName} {$middleName} {$paterno} {$materno}";

            // 3. Generar correo institucional único sin colisión
            $cleanFirst = Str::slug($firstName);
            $cleanPaterno = Str::slug($paterno);
            $cleanMaterno = Str::slug($materno);

            $emailBase = "{$cleanFirst}.{$cleanPaterno}";
            $email = "{$emailBase}@iesp.edu.pe";

            $suffix = 1;
            while (isset($existingEmails[$email])) {
                $suffix++;
                $email = "{$cleanFirst}.{$cleanPaterno}{$suffix}@iesp.edu.pe";
            }
            $existingEmails[$email] = true;

            // 4. Asignar carrera equilibrada
            $carrera = self::CARRERAS[$careerIndex % $careerCount];
            $careerIndex++;

            // 5. Guardar en MySQL
            $estudiante = Estudiante::query()->create([
                'dni' => $dni,
                'nombres' => $fullName,
                'carrera' => $carrera,
                'correo' => $email,
            ]);

            $generated[] = $estudiante;
        }

        app(BackupDemoDatabase::class)->clearCache();

        return $generated;
    }
}
