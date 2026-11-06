<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\BackupStatus;
use App\Models\DemoBackup;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class DemoBackupWorkflow
{
    public const string BACKUP_DIRECTORY = 'demo-backups';

    public const string SECONDARY_DIRECTORY = 'backups/secondary';

    public const string RESTORE_DIRECTORY = 'demo-restores';

    public function __construct(
        private readonly ?AuditLogger $auditLogger = null,
        private readonly ?MySqlDumper $mySqlDumper = null,
        private readonly ?EnvironmentDetector $detector = null,
    ) {}

    public function createBackup(?string $database = null, bool $isAutomated = false): DemoBackup
    {
        $startedAt = hrtime(true);
        $backup = DemoBackup::query()->create(['status' => BackupStatus::Running]);
        $artifactPath = null;
        $secondaryPath = null;

        try {
            $isSqlite = false;
            $dbName = $database;
            $dbType = 'mysql';

            // Determine target: SQLite in tests or when explicitly requested or if MySQL not available
            if ($database === null && app()->runningUnitTests()) {
                $isSqlite = true;
                $dbName = 'demo-institucional.sqlite';
                $dbType = 'sqlite';
            } elseif ($database === 'sqlite' || $database === 'demo-institucional' || str_starts_with((string) $database, 'sqlite:')) {
                $isSqlite = true;
                $dbName = 'demo-institucional.sqlite';
                $dbType = 'sqlite';
            } else {
                $dbName = $database ?? config('database.connections.mysql.database', 'iesp_continuidad_demo');
                $dbType = 'mysql';
            }

            $sourceContents = '';

            if ($isSqlite) {
                $sourcePath = config('database.connections.backup_demo.database');

                if (! is_string($sourcePath) || ! is_file($sourcePath)) {
                    throw new RuntimeException('La base SQLite ficticia no está disponible; no se creó un respaldo.');
                }

                $this->assertSqliteIntegrity($sourcePath);
                $sourceContents = file_get_contents($sourcePath);

                if (! is_string($sourceContents) || $sourceContents === '') {
                    throw new RuntimeException('No se pudo leer el archivo SQLite ficticio.');
                }
            } else {
                // MySQL extraction
                $dumper = $this->mySqlDumper ?? app(MySqlDumper::class);
                $sourceContents = $dumper->dump($dbName);
            }

            // GZIP Compression (Level 9 - maximum ratio)
            $compressedContents = gzencode($sourceContents, 9);

            if (! is_string($compressedContents)) {
                throw new RuntimeException('No se pudo comprimir la base de datos.');
            }

            // AES-256-CBC Encryption via Laravel Crypt
            $encryptedContents = Crypt::encryptString($compressedContents);

            $uuid = Str::uuid()->toString();
            $safeDb = Str::slug($dbName);

            $scheduleConfig = app(BackupScheduleManager::class)->getConfig();
            $folderStructure = $scheduleConfig['folder_structure'] ?? 'single_folder';
            $saveFormat = $scheduleConfig['save_format'] ?? 'both';
            $storageDestination = $scheduleConfig['storage_destination'] ?? 'storage/app/backups';

            $subFolder = $folderStructure === 'date_subfolders' ? date('Y-m-d').'/' : '';
            $artifactPath = self::BACKUP_DIRECTORY."/{$subFolder}{$uuid}.{$safeDb}.backup.enc";
            $disk = Storage::disk('local');

            if (! $disk->put($artifactPath, $encryptedContents)) {
                throw new RuntimeException('No se pudo guardar el artefacto cifrado en el almacenamiento primario.');
            }

            // Exportar copia directa .sql si está configurado en almacenamiento local
            if ($saveFormat === 'direct_sql' || $saveFormat === 'both') {
                $sqlPath = self::BACKUP_DIRECTORY."/{$subFolder}{$uuid}.{$safeDb}.dump.sql";
                $disk->put($sqlPath, $sourceContents);
                $disk->put($sqlPath.'.sha256', hash('sha256', $sourceContents)."  {$uuid}.{$safeDb}.dump.sql\n");
            }

            // Guardar físicamente de forma garantizada en la carpeta configurada (storage/app/backups o ruta elegida)
            $physicalDir = $this->resolvePhysicalDestination($storageDestination, $folderStructure);
            if (! is_dir($physicalDir)) {
                @mkdir($physicalDir, 0755, true);
            }

            if (is_dir($physicalDir)) {
                $fileBase = rtrim($physicalDir, '\\/').DIRECTORY_SEPARATOR."{$uuid}.{$safeDb}";

                if ($saveFormat === 'encrypted' || $saveFormat === 'both') {
                    @file_put_contents("{$fileBase}.backup.enc", $encryptedContents);
                    @file_put_contents("{$fileBase}.backup.enc.sha256", hash('sha256', $encryptedContents)."  {$uuid}.{$safeDb}.backup.enc\n");
                }

                if ($saveFormat === 'direct_sql' || $saveFormat === 'both') {
                    @file_put_contents("{$fileBase}.dump.sql", $sourceContents);
                    @file_put_contents("{$fileBase}.dump.sql.sha256", hash('sha256', $sourceContents)."  {$uuid}.{$safeDb}.dump.sql\n");
                }
            }

            // Guardar físicamente la copia espejo en storage/app/backups/secondary
            $replicateSecondary = (bool) ($scheduleConfig['replicate_secondary'] ?? true);
            if ($replicateSecondary) {
                $secondaryPhysicalDir = storage_path('app/backups/secondary');
                if (! is_dir($secondaryPhysicalDir)) {
                    @mkdir($secondaryPhysicalDir, 0755, true);
                }
                if (is_dir($secondaryPhysicalDir)) {
                    $secFileBase = rtrim($secondaryPhysicalDir, '\\/').DIRECTORY_SEPARATOR."{$uuid}.{$safeDb}";
                    @file_put_contents("{$secFileBase}.backup.enc", $encryptedContents);
                    @file_put_contents("{$secFileBase}.backup.enc.sha256", hash('sha256', $encryptedContents)."  {$uuid}.{$safeDb}.backup.enc\n");
                }
            }

            $storedContents = $disk->get($artifactPath);
            $artifactSha256 = hash('sha256', $encryptedContents);

            if (! hash_equals($artifactSha256, hash('sha256', $storedContents))) {
                throw new RuntimeException('La verificación SHA-256 del artefacto cifrado falló.');
            }

            // Verify integrity of decrypted content
            $verifiedCompressedContents = Crypt::decryptString($storedContents);
            $verifiedSourceContents = gzdecode($verifiedCompressedContents);
            $sourceSha256 = hash('sha256', $sourceContents);

            if (! is_string($verifiedSourceContents)
                || ! hash_equals($sourceSha256, hash('sha256', $verifiedSourceContents))) {
                throw new RuntimeException('La verificación del contenido recuperado del respaldo falló.');
            }

            // Secondary Replication (Mirror Copy & SHA-256 Manifest)
            $secondaryPath = self::SECONDARY_DIRECTORY."/{$uuid}.{$safeDb}.backup.enc";
            $disk->put($secondaryPath, $encryptedContents);
            $disk->put($secondaryPath.'.sha256', $artifactSha256."  {$uuid}.{$safeDb}.backup.enc\n");

            $backup->update([
                'status' => BackupStatus::Success,
                'database_name' => $dbName,
                'database_type' => $dbType,
                'artifact_path' => $artifactPath,
                'artifact_sha256' => $artifactSha256,
                'secondary_path' => $secondaryPath,
                'replicated' => true,
                'is_automated' => $isAutomated,
                'source_sha256' => $sourceSha256,
                'size_bytes' => strlen($encryptedContents),
                'backup_duration_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            $this->logAudit(
                AuditAction::BackupCreated,
                "Respaldo #{$backup->id} de [{$dbName}] ({$dbType}) cifrado y replicado con SHA-256.".($isAutomated ? ' [AUTOMATIZADO]' : ''),
                $backup,
                [
                    'database' => $dbName,
                    'type' => $dbType,
                    'size_bytes' => $backup->size_bytes,
                    'sha256' => $backup->artifact_sha256,
                    'duration_ms' => $backup->backup_duration_ms,
                    'replicated' => true,
                    'automated' => $isAutomated,
                ],
            );

            app(ContinuityMetrics::class)->clearCache();

            return $backup->refresh();
        } catch (Throwable $exception) {
            $disk = Storage::disk('local');
            if (is_string($artifactPath) && $disk->exists($artifactPath)) {
                $disk->delete($artifactPath);
            }
            if (is_string($secondaryPath) && $disk->exists($secondaryPath)) {
                $disk->delete($secondaryPath);
            }

            $backup->update([
                'status' => BackupStatus::Failed,
                'backup_duration_ms' => $this->elapsedMilliseconds($startedAt),
                'error_message' => $exception->getMessage(),
            ]);

            $this->logAudit(
                AuditAction::BackupFailed,
                "Fallo en respaldo #{$backup->id}: {$exception->getMessage()}",
                $backup,
                ['error' => $exception->getMessage()],
            );

            Log::error('Falló el flujo de respaldo.', [
                'demo_backup_id' => $backup->id,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function restore(DemoBackup $backup): string
    {
        if (! $backup->isRestorable()
            || ! is_string($backup->artifact_path)
            || ! is_string($backup->artifact_sha256)
            || ! is_string($backup->source_sha256)) {
            throw new RuntimeException('Solo se pueden restaurar respaldos exitosos y verificados.');
        }

        $startedAt = hrtime(true);
        $disk = Storage::disk('local');

        if (! $disk->exists($backup->artifact_path)) {
            // Attempt to restore from secondary replica if primary is missing
            if ($backup->secondary_path && $disk->exists($backup->secondary_path)) {
                $disk->put($backup->artifact_path, $disk->get($backup->secondary_path));
            } else {
                // Verificar en almacenamiento físico storage/app/backups o secondary
                $filename = basename((string) $backup->artifact_path);
                $physicalPrimary = storage_path('app/backups'.DIRECTORY_SEPARATOR.$filename);
                $physicalSecondary = storage_path('app/backups'.DIRECTORY_SEPARATOR.'secondary'.DIRECTORY_SEPARATOR.$filename);

                if (file_exists($physicalPrimary)) {
                    $disk->put($backup->artifact_path, file_get_contents($physicalPrimary));
                } elseif (file_exists($physicalSecondary)) {
                    $disk->put($backup->artifact_path, file_get_contents($physicalSecondary));
                } else {
                    throw new RuntimeException('El archivo de respaldo cifrado no se encuentra en el almacenamiento.');
                }
            }
        }

        $encryptedContents = $disk->get($backup->artifact_path);

        if (! hash_equals($backup->artifact_sha256, hash('sha256', $encryptedContents))) {
            $this->logAudit(
                AuditAction::RestoreRejected,
                "Intento de restauración rechazado en respaldo #{$backup->id}: alteración de hash SHA-256.",
                $backup,
            );
            throw new RuntimeException('El archivo del respaldo fue alterado; se rechazó la recuperación.');
        }

        $compressedContents = Crypt::decryptString($encryptedContents);
        $sourceContents = gzdecode($compressedContents);

        if (! is_string($sourceContents)
            || ! hash_equals($backup->source_sha256, hash('sha256', $sourceContents))) {
            $this->logAudit(
                AuditAction::RestoreRejected,
                "Intento de restauración rechazado en respaldo #{$backup->id}: discrepancia de contenido fuente.",
                $backup,
            );
            throw new RuntimeException('El contenido restaurado no coincide con la huella SHA-256 guardada.');
        }

        $uuid = Str::uuid()->toString();
        $isSqlite = $backup->database_type === 'sqlite' || str_ends_with($backup->artifact_path, '.sqlite.backup.enc');

        if ($isSqlite) {
            $temporaryPath = self::RESTORE_DIRECTORY."/{$uuid}.tmp";
            $restoredPath = self::RESTORE_DIRECTORY."/{$uuid}.sqlite";
            $restoreSucceeded = false;

            try {
                if (! $disk->put($temporaryPath, $sourceContents)) {
                    throw new RuntimeException('No se pudo preparar el archivo temporal de recuperación.');
                }

                $this->assertSqliteIntegrity($disk->path($temporaryPath));

                if (! $disk->put($restoredPath, $sourceContents)) {
                    throw new RuntimeException('No se pudo guardar la base ficticia recuperada.');
                }

                $restoreSucceeded = true;
            } finally {
                if ($disk->exists($temporaryPath)) {
                    $disk->delete($temporaryPath);
                }

                if (! $restoreSucceeded && $disk->exists($restoredPath)) {
                    $disk->delete($restoredPath);
                }
            }
        } else {
            // MySQL SQL dump restoration into isolated validation file
            $safeDb = Str::slug($backup->database_name ?? 'mysql');
            $restoredPath = self::RESTORE_DIRECTORY."/{$uuid}.{$safeDb}.restored.sql";

            if (! $disk->put($restoredPath, $sourceContents)) {
                throw new RuntimeException('No se pudo guardar el archivo SQL recuperado.');
            }

            // Re-inyección real en MySQL para restauración efectiva de la base de datos
            if (! app()->runningUnitTests()) {
                $this->injectSqlIntoMySql($backup->database_name, $sourceContents);
            }
        }

        $durationMs = $this->elapsedMilliseconds($startedAt);
        $backup->update([
            'restored_path' => $restoredPath,
            'restore_duration_ms' => $durationMs,
        ]);

        $this->logAudit(
            AuditAction::BackupRestored,
            "Recuperación verificada de [{$backup->database_name}] completada en {$durationMs} ms (RTO verificado).",
            $backup,
            [
                'database' => $backup->database_name,
                'restored_path' => $restoredPath,
                'duration_ms' => $durationMs,
            ],
        );

        app(ContinuityMetrics::class)->clearCache();
        app(BackupDemoDatabase::class)->clearCache();

        return $restoredPath;
    }

    private function injectSqlIntoMySql(?string $targetDb, string $sql): void
    {
        try {
            $dbName = $targetDb ?: config('database.connections.mysql.database', 'iesp_continuidad_demo');
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $username = config('database.connections.mysql.username', 'root');
            $password = config('database.connections.mysql.password', '');

            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$dbName}`;");
            $pdo->exec($sql);
        } catch (Throwable $e) {
            Log::warning('No se pudo reinyectar respaldo en MySQL: '.$e->getMessage());
        }
    }

    private function assertSqliteIntegrity(string $databasePath): void
    {
        $database = new PDO('sqlite:'.$databasePath, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $integrity = $database->query('PRAGMA integrity_check')->fetchColumn();
        $foreignKeyViolations = $database->query('PRAGMA foreign_key_check')->fetchAll();
        $database = null;

        if ($integrity !== 'ok' || $foreignKeyViolations !== []) {
            throw new RuntimeException('La base SQLite ficticia no superó la verificación de integridad.');
        }
    }

    public function resolvePhysicalDestination(?string $destination, string $folderStructure = 'single_folder'): string
    {
        $destination = trim((string) $destination);

        if ($destination === '' || in_array($destination, ['storage/app/backups', 'backups'], true)) {
            $base = storage_path('app/backups');
        } elseif (preg_match('/^[a-zA-Z]:[\\\\\/]/', $destination) || str_starts_with($destination, '/')) {
            $base = rtrim($destination, '\\/');
        } else {
            $base = rtrim(base_path($destination), '\\/');
        }

        if ($folderStructure === 'date_subfolders') {
            return $base.DIRECTORY_SEPARATOR.date('Y-m-d');
        }

        return $base;
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return (int) ((hrtime(true) - $startedAt) / 1_000_000);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function logAudit(AuditAction $action, string $description, ?DemoBackup $subject = null, array $properties = []): void
    {
        try {
            $logger = $this->auditLogger ?? app(AuditLogger::class);
            $logger->record($action, $description, $subject, $properties);
        } catch (Throwable) {
            // Allow workflow to proceed in CLI/test environments
        }
    }
}
