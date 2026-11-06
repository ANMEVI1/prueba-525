<?php

namespace App\Enums;

/**
 * Auditable actions recorded in the activity log.
 */
enum AuditAction: string
{
    case Login = 'auth.login';
    case Logout = 'auth.logout';
    case BackupCreated = 'backup.created';
    case BackupFailed = 'backup.failed';
    case BackupRestored = 'backup.restored';
    case RestoreRejected = 'backup.restore_rejected';
    case StudentCreated = 'student.created';
    case StudentUpdated = 'student.updated';
    case StudentDeleted = 'student.deleted';
    case PasswordUpdated = 'auth.password_updated';

    /**
     * Human readable label shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Login => 'Inicio de sesión',
            self::Logout => 'Cierre de sesión',
            self::BackupCreated => 'Respaldo creado',
            self::BackupFailed => 'Respaldo fallido',
            self::BackupRestored => 'Recuperación verificada',
            self::RestoreRejected => 'Recuperación rechazada',
            self::StudentCreated => 'Estudiante registrado',
            self::StudentUpdated => 'Estudiante actualizado',
            self::StudentDeleted => 'Estudiante eliminado',
            self::PasswordUpdated => 'Contraseña actualizada',
        };
    }

    /**
     * Semantic tone consumed by the `x-ui.badge` component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::BackupCreated, self::BackupRestored, self::StudentCreated, self::PasswordUpdated => 'success',
            self::BackupFailed, self::RestoreRejected, self::StudentDeleted => 'danger',
            self::StudentUpdated => 'brand',
            self::Login, self::Logout => 'neutral',
        };
    }

    /**
     * Icon name consumed by the `x-ui.icon` component.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Login => 'log-in',
            self::Logout => 'log-out',
            self::PasswordUpdated => 'key',
            self::BackupCreated => 'shield-check',
            self::BackupFailed, self::RestoreRejected => 'alert-triangle',
            self::BackupRestored => 'rotate-ccw',
            self::StudentCreated, self::StudentUpdated, self::StudentDeleted => 'users',
        };
    }
}
