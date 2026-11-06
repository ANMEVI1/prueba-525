<?php

namespace App\Services;

use App\Models\DemoBackup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Manages backup automation settings, schedule evaluation, and retention pruning.
 */
class BackupScheduleManager
{
    private const string CONFIG_PATH = 'backup_schedule.json';

    /**
     * @var array{
     *     enabled: bool,
     *     frequency: string,
     *     time: string,
     *     database: string,
     *     retention_days: int|null,
     *     replicate_secondary: bool,
     *     secondary_path: string,
     *     storage_destination: string,
     *     folder_structure: string,
     *     save_format: string,
     *     last_run_at: string|null,
     *     last_status: string|null,
     * }|null
     */
    private ?array $cachedConfig = null;

    /**
     * @return array{
     *     enabled: bool,
     *     frequency: string,
     *     time: string,
     *     database: string,
     *     retention_days: int|null,
     *     replicate_secondary: bool,
     *     secondary_path: string,
     *     storage_destination: string,
     *     folder_structure: string,
     *     save_format: string,
     *     last_run_at: string|null,
     *     last_status: string|null,
     * }
     */
    public function getConfig(): array
    {
        if ($this->cachedConfig !== null) {
            return $this->cachedConfig;
        }

        $disk = Storage::disk('local');

        if ($disk->exists(self::CONFIG_PATH)) {
            try {
                $decoded = json_decode($disk->get(self::CONFIG_PATH), true);
                if (is_array($decoded)) {
                    return $this->cachedConfig = array_merge($this->defaultConfig(), $decoded);
                }
            } catch (Throwable) {
                // fall back to default
            }
        }

        return $this->cachedConfig = $this->defaultConfig();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveConfig(array $data): array
    {
        $current = $this->getConfig();
        $updated = array_merge($current, [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'frequency' => (string) ($data['frequency'] ?? 'daily'),
            'time' => (string) ($data['time'] ?? '02:00'),
            'database' => (string) ($data['database'] ?? 'iesp_continuidad_demo'),
            'retention_days' => (isset($data['retention_days']) && $data['retention_days'] !== '' && (int) $data['retention_days'] > 0)
                ? (int) $data['retention_days']
                : null,
            'replicate_secondary' => (bool) ($data['replicate_secondary'] ?? true),
            'secondary_path' => trim((string) ($data['secondary_path'] ?? 'backups/secondary')),
            'storage_destination' => trim((string) ($data['storage_destination'] ?? 'storage/app/backups')),
            'folder_structure' => (string) ($data['folder_structure'] ?? 'single_folder'),
            'save_format' => (string) ($data['save_format'] ?? 'both'),
        ]);

        Storage::disk('local')->put(self::CONFIG_PATH, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $this->cachedConfig = $updated;
    }

    public function markRun(bool $success, ?string $message = null): void
    {
        $config = $this->getConfig();
        $config['last_run_at'] = Carbon::now()->toIso8601String();
        $config['last_status'] = $success ? 'Completado con éxito' : ('Error: '.$message);

        Storage::disk('local')->put(self::CONFIG_PATH, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->cachedConfig = $config;
    }

    /**
     * Delete backups older than configured retention days.
     */
    public function pruneExpiredBackups(): int
    {
        $config = $this->getConfig();
        $retentionDays = $config['retention_days'] ?? null;

        if (empty($retentionDays) || (int) $retentionDays <= 0) {
            return 0; // Sin límite de retención configurado; se conservan todas las copias
        }

        $cutoff = Carbon::now()->subDays((int) $retentionDays);

        $expiredBackups = DemoBackup::query()
            ->where('created_at', '<', $cutoff)
            ->get();

        $deletedCount = 0;
        $disk = Storage::disk('local');

        foreach ($expiredBackups as $backup) {
            if ($backup->artifact_path && $disk->exists($backup->artifact_path)) {
                $disk->delete($backup->artifact_path);
            }
            if ($backup->secondary_path && $disk->exists($backup->secondary_path)) {
                $disk->delete($backup->secondary_path);
            }
            $backup->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultConfig(): array
    {
        return [
            'enabled' => true,
            'frequency' => 'daily',
            'time' => '02:00',
            'database' => 'iesp_continuidad_demo',
            'retention_days' => 30,
            'replicate_secondary' => true,
            'secondary_path' => 'backups/secondary',
            'storage_destination' => 'storage/app/backups',
            'folder_structure' => 'single_folder',
            'save_format' => 'both',
            'last_run_at' => null,
            'last_status' => null,
        ];
    }
}
