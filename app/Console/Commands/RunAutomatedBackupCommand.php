<?php

namespace App\Console\Commands;

use App\Services\BackupScheduleManager;
use App\Services\DemoBackupWorkflow;
use Illuminate\Console\Command;
use Throwable;

class RunAutomatedBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:auto-run
                            {--database= : Nombre específico de la base de datos a respaldar}
                            {--force : Fuerza la ejecución aunque la automatización esté desactivada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta el respaldo automatizado institucional con compresión, cifrado AES-256 y replicación secundaria.';

    public function handle(
        DemoBackupWorkflow $workflow,
        BackupScheduleManager $scheduleManager,
    ): int {
        $this->info('===========================================================');
        $this->info(' Iniciando Tarea Automatizada de Continuidad Operativa TI');
        $this->info(' IESP Cap. FAP José Abelardo Quiñones');
        $this->info('===========================================================');

        $config = $scheduleManager->getConfig();

        if (! $config['enabled'] && ! $this->option('force')) {
            $this->warn('La automatización está desactivada en la configuración. Usa --force para ejecutar.');

            return self::SUCCESS;
        }

        $targetDb = (string) ($this->option('database') ?: $config['database']);
        $this->line("Base de datos objetivo: <fg=cyan>{$targetDb}</>");
        $this->line("Destino configurado: <fg=yellow>{$config['storage_destination']}</>");
        $this->line('Estructura: <fg=yellow>'.($config['folder_structure'] === 'date_subfolders' ? 'Subcarpetas por fecha (YYYY-MM-DD)' : 'Carpeta directa').'</>');
        $this->line('Formato: <fg=yellow>'.strtoupper($config['save_format']).'</>');
        $this->line("Destino configurado: <fg=yellow>{$config['storage_destination']}</>");
        $this->line('Estructura: <fg=yellow>'.($config['folder_structure'] === 'date_subfolders' ? 'Subcarpetas por fecha (YYYY-MM-DD)' : 'Carpeta directa').'</>');
        $this->line('Formato: <fg=yellow>'.strtoupper($config['save_format']).'</>');
        $this->line('Iniciando extracción, compresión GZIP-9 y cifrado AES-256-CBC...');

        try {
            $backup = $workflow->createBackup($targetDb, isAutomated: true);

            $this->info("✅ Respaldo exitoso ID: #{$backup->id}");
            $this->line("   - Base de datos: {$backup->database_name} ({$backup->database_type})");
            $this->line("   - Tamaño cifrado: {$backup->formattedSize()}");
            $this->line("   - Duración: {$backup->backup_duration_ms} ms");
            $this->line("   - SHA-256: {$backup->artifact_sha256}");
            $this->line("   - Almacenamiento primario: {$backup->artifact_path}");
            $this->line("   - Réplica secundaria: {$backup->secondary_path}");

            // Pruning
            $pruned = $scheduleManager->pruneExpiredBackups();
            if ($pruned > 0) {
                $this->comment("   - Depuración: Se purgaron {$pruned} respaldos anteriores a {$config['retention_days']} días.");
            }

            $scheduleManager->markRun(true);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("❌ Error en la ejecución automatizada: {$e->getMessage()}");
            $scheduleManager->markRun(false, $e->getMessage());

            return self::FAILURE;
        }
    }
}
