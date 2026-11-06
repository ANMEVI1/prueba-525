<?php

namespace Database\Factories;

use App\Enums\BackupStatus;
use App\Models\DemoBackup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DemoBackup>
 */
class DemoBackupFactory extends Factory
{
    protected $model = DemoBackup::class;

    public function definition(): array
    {
        return [
            'status' => BackupStatus::Success,
            'artifact_path' => 'demo-backups/'.Str::uuid().'.backup.enc',
            'artifact_sha256' => hash('sha256', Str::random(32)),
            'source_sha256' => hash('sha256', Str::random(32)),
            'size_bytes' => fake()->numberBetween(3500, 8000),
            'backup_duration_ms' => fake()->numberBetween(35, 120),
            'restored_path' => null,
            'restore_duration_ms' => null,
            'error_message' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BackupStatus::Failed,
            'artifact_path' => null,
            'error_message' => 'Fallo simulado de lectura o integridad de origen.',
        ]);
    }

    public function restored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BackupStatus::Success,
            'restored_path' => 'demo-restores/'.Str::uuid().'.sqlite',
            'restore_duration_ms' => fake()->numberBetween(25, 80),
        ]);
    }
}
