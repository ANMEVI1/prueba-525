<?php

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Inspects the live MySQL/MariaDB server configured in .env and identifies
 * the hosting stack (XAMPP, Laragon, MySQL Server/Workbench, WampServer...)
 * by asking the server itself for its installation directory (@@basedir).
 * No machine-specific paths are hardcoded.
 */
class DatabaseServerInspector
{
    /**
     * @var list<string>
     */
    public const array SYSTEM_SCHEMAS = ['information_schema', 'performance_schema', 'mysql', 'sys'];

    private ?PDO $connection = null;

    /**
     * @var array{available: bool, flavor: string, version: string, stack: string, basedir: string, label: string}|null
     */
    private ?array $profile = null;

    /**
     * Opens (or reuses) a server-level connection without selecting a schema.
     */
    public function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $host = (string) config('database.connections.mysql.host', '127.0.0.1');
        $port = (int) config('database.connections.mysql.port', 3306);

        if (! $this->isPortOpen($host, $port)) {
            throw new RuntimeException("No hay un servidor MySQL/MariaDB escuchando en {$host}:{$port}. Inicia MySQL desde XAMPP, Laragon o MySQL Server.");
        }

        try {
            $this->connection = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                (string) config('database.connections.mysql.username', 'root'),
                (string) config('database.connections.mysql.password', ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                ],
            );
        } catch (Throwable $exception) {
            throw new RuntimeException('No se pudo autenticar en el servidor MySQL: '.$exception->getMessage(), previous: $exception);
        }

        return $this->connection;
    }

    public function isAvailable(): bool
    {
        return $this->profile()['available'];
    }

    /**
     * @return array{available: bool, flavor: string, version: string, stack: string, basedir: string, label: string}
     */
    public function profile(): array
    {
        if ($this->profile !== null) {
            return $this->profile;
        }

        try {
            $row = $this->connection()
                ->query('SELECT VERSION() AS version, @@version_comment AS comment, @@basedir AS basedir')
                ->fetch(PDO::FETCH_ASSOC);

            $rawVersion = (string) ($row['version'] ?? '');
            $comment = (string) ($row['comment'] ?? '');
            $basedir = (string) ($row['basedir'] ?? '');

            $flavor = self::detectFlavor($rawVersion, $comment);
            $version = self::cleanVersion($rawVersion);
            $stack = self::classifyStack($basedir, $flavor);

            return $this->profile = [
                'available' => true,
                'flavor' => $flavor,
                'version' => $version,
                'stack' => $stack,
                'basedir' => $basedir,
                'label' => "{$stack} · {$flavor} {$version}",
            ];
        } catch (Throwable) {
            return $this->profile = [
                'available' => false,
                'flavor' => 'MySQL',
                'version' => '',
                'stack' => 'Sin conexión',
                'basedir' => '',
                'label' => 'Servidor MySQL no disponible',
            ];
        }
    }

    /**
     * @return list<string>
     */
    public function listDatabases(): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $names = $this->connection()->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        $userDatabases = array_values(array_filter(
            array_map('strval', $names),
            fn (string $name): bool => ! self::isSystemSchema($name),
        ));

        sort($userDatabases, SORT_NATURAL | SORT_FLAG_CASE);

        return $userDatabases;
    }

    public function databaseExists(string $database): bool
    {
        $statement = $this->connection()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
        $statement->execute([$database]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function tableCount(string $database): int
    {
        $statement = $this->connection()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?');
        $statement->execute([$database]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Resolves a client binary (mysqldump, mysql) that ships with the running
     * server, guaranteeing version-compatible syntax for that exact engine.
     */
    public function serverBinary(string $binary): ?string
    {
        $basedir = rtrim(str_replace('\\', '/', $this->profile()['basedir']), '/');

        if ($basedir === '') {
            return null;
        }

        foreach (["{$basedir}/bin/{$binary}.exe", "{$basedir}/bin/{$binary}"] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function isApplicationDatabase(string $database): bool
    {
        return strcasecmp($database, (string) config('database.connections.mysql.database')) === 0;
    }

    public static function isSystemSchema(string $database): bool
    {
        return in_array(strtolower($database), self::SYSTEM_SCHEMAS, true);
    }

    public static function isValidDatabaseName(string $database): bool
    {
        return preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $database) === 1 && ! self::isSystemSchema($database);
    }

    public static function detectFlavor(string $version, string $comment = ''): string
    {
        return str_contains(strtolower($version.' '.$comment), 'mariadb') ? 'MariaDB' : 'MySQL';
    }

    public static function cleanVersion(string $version): string
    {
        return preg_match('/^\d+(\.\d+){1,2}/', $version, $matches) === 1 ? $matches[0] : $version;
    }

    public static function classifyStack(string $basedir, string $flavor): string
    {
        $path = strtolower(str_replace('\\', '/', $basedir));

        return match (true) {
            str_contains($path, 'xampp'), str_contains($path, 'lampp') => 'XAMPP',
            str_contains($path, 'laragon') => 'Laragon',
            str_contains($path, 'wamp') => 'WampServer',
            str_contains($path, 'mamp') => 'MAMP',
            str_contains($path, 'mysql server') => 'MySQL Server (Workbench)',
            str_contains($path, 'mariadb') => 'MariaDB Server',
            default => "Servidor {$flavor}",
        };
    }

    private function isPortOpen(string $host, int $port): bool
    {
        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 0.5);

        if (is_resource($socket)) {
            fclose($socket);

            return true;
        }

        return false;
    }
}
