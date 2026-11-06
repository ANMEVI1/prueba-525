<?php

namespace App\Console\Commands;

use App\Models\Estudiante;
use App\Services\StudentDemoDictionary;
use Illuminate\Console\Command;

class GenerateDemoStudentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estudiantes:generar-demo {cantidad=20 : Número de estudiantes diferentes a generar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera e inserta dinámicamente estudiantes únicos no repetidos desde un diccionario real peruano';

    /**
     * Execute the console command.
     */
    public function handle(StudentDemoDictionary $dictionary): int
    {
        $count = (int) $this->argument('cantidad');
        if ($count <= 0) {
            $count = 20;
        }

        $this->info("Generando {$count} estudiantes únicos de prueba...");

        $inserted = $dictionary->generateAndInsert($count);
        $total = Estudiante::query()->count();

        $this->table(
            ['DNI', 'Nombres y Apellidos', 'Carrera', 'Correo'],
            array_map(fn ($e) => [
                $e->dni,
                $e->nombres,
                $e->carrera,
                $e->correo,
            ], $inserted)
        );

        $this->info('===========================================================');
        $this->info("✅ ¡{$count} estudiantes generados e insertados en MySQL sin duplicados!");
        $this->info("👥 Total actual en el padrón: {$total} estudiantes.");
        $this->info('===========================================================');

        return Command::SUCCESS;
    }
}
