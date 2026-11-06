<?php

namespace App\Models;

use App\Enums\BackupStatus;
use Database\Factories\DemoBackupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoBackup extends Model
{
    /** @use HasFactory<DemoBackupFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'database_name',
        'database_type',
        'artifact_path',
        'artifact_sha256',
        'secondary_path',
        'replicated',
        'is_automated',
        'source_sha256',
        'size_bytes',
        'backup_duration_ms',
        'restored_path',
        'restore_duration_ms',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BackupStatus::class,
            'size_bytes' => 'integer',
            'backup_duration_ms' => 'integer',
            'restore_duration_ms' => 'integer',
            'replicated' => 'boolean',
            'is_automated' => 'boolean',
        ];
    }

    /**
     * Whether the backup can be used as a restore point.
     */
    public function isRestorable(): bool
    {
        return $this->status === BackupStatus::Success;
    }

    /**
     * Whether a verified restore was executed from this backup.
     */
    public function wasRestored(): bool
    {
        return $this->restored_path !== null;
    }

    /**
     * Encrypted artifact size formatted in kilobytes.
     */
    public function formattedSize(): string
    {
        return number_format($this->size_bytes / 1024, 1, ',', '.').' KB';
    }
}
