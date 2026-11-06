<?php

namespace App\Enums;

/**
 * Lifecycle states of a demo backup execution.
 */
enum BackupStatus: string
{
    case Running = 'running';
    case Success = 'success';
    case Failed = 'failed';

    /**
     * Human readable label shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Running => 'En curso',
            self::Success => 'Cifrado y verificado',
            self::Failed => 'Fallido',
        };
    }

    /**
     * Semantic tone consumed by the `x-ui.badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Running => 'warning',
            self::Success => 'success',
            self::Failed => 'danger',
        };
    }

    /**
     * Icon name consumed by the `x-ui.icon` component.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Running => 'clock',
            self::Success => 'shield-check',
            self::Failed => 'x-circle',
        };
    }
}
