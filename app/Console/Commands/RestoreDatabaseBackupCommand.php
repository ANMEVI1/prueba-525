<?php

namespace App\Console\Commands;

use App\Models\DemoBackup;
use App\Services\DemoBackupWorkflow;
use Illuminate\Console\Command;

class RestoreDatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore {id? : ID del respaldo a restaurar (por defecto el más reciente)} {--list : Listar respaldos disponibles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura una copia de seguridad cifrada o SQL directamente en la base de datos MySQL/MariaDB';

    /**
     * Execute the console command.
     */
    public function handle(DemoBackupWorkflow $workflow): int
    {
        if ($this->option('list')) {
            $backups = DemoBackup::query()->latest('id')->limit(15)->get();
            $this->table(
                ['ID', 'Base de Datos', 'Estado', 'Tamaño', 'Creado El', 'Automatizado'],
                $backups->map(fn ($b) => [
                    $b->id,
                    $b->database_name,
                    $b->status->label(),
                    $b->formattedSize(),
                    $b->created_at?->format('d/m/Y H:i:s'),
                    $b->is_automated ? 'Sí' : 'No',
                ])
            );

            return Command::SUCCESS;
        }

        $id = $this->argument('id');

        /** @var DemoBackup|null $backup */
        $backup = $id
            ? DemoBackup::query()->find($id)
            : DemoBackup::query()->where('status', 'success')->latest('id')->first();

        if (! $backup) {
            $this->error('No se encontró ningún respaldo restaurable.');

            return Command::FAILURE;
        }

        $this->info("Iniciando restauración del Respaldo #{$backup->id} de [{$backup->database_name}]...");
        $this->line('1. Verificando integridad SHA-256...');
        $this->line('2. Descifrando contenido con AES-256...');
        $this->line('3. Descomprimiendo GZIP...');
        $this->line('4. Inyectando datos en MySQL...');

        try {
            $restoredPath = $workflow->restore($backup);
            $backup->refresh();

            $this->info('===========================================================');
            $this->info("✅ ¡Base de datos [{$backup->database_name}] restaurada exitosamente!");
            $this->info("⏱️ Tiempo de recuperación (RTO): {$backup->restore_duration_ms} ms");
            $this->info("📄 Archivo de validación: {$restoredPath}");
            $this->info('===========================================================');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error durante la restauración: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
