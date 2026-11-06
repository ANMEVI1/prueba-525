<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use PDO;
use Throwable;

/**
 * Detects local server environment (Laragon / XAMPP / MySQL / SQLite),
 * discovers active databases, and resolves dumping tools.
 */
class EnvironmentDetector
{
    private const array SYSTEM_SCHEMAS = [
        'information_schema',
        'performance_schema',
        'mysql',
        'sys',
    ];

    /**
     * @var array{
     *     environment: string,
     *     is_windows: bool,
     *     stack: string,
     *     mysql_available: bool,
     *     mysql_version: string|null,
     *     mysql_host: string,
     *     mysql_port: int,
     *     mysqldump_path: string|null,
     *     databases: list<string>,
     *     sqlite_available: bool,
     *     sqlite_path: string|null,
     * }|null
     */
    private ?array $memoryCache = null;

    private ?string $cachedMysqldumpPath = null;

    private bool $mysqldumpResolved = false;

    /**
     * @return array{
     *     environment: string,
     *     is_windows: bool,
     *     stack: string,
     *     mysql_available: bool,
     *     mysql_version: string|null,
     *     mysql_host: string,
     *     mysql_port: int,
     *     mysqldump_path: string|null,
     *     databases: list<string>,
     *     sqlite_available: bool,
     *     sqlite_path: string|null,
     * }
     */
    public function detect(bool $forceRefresh = false): array
    {
        if (! $forceRefresh && $this->memoryCache !== null) {
            return $this->memoryCache;
        }

        if (! $forceRefresh) {
            $cached = Cache::get('env_detection_summary');
            if (is_array($cached)) {
                return $this->memoryCache = $cached;
            }
        }

        $isWindows = PHP_OS_FAMILY === 'Windows';
        $stack = $this->detectStack();

        $host = (string) config('database.connections.mysql.host', '127.0.0.1');
        $port = (int) config('database.connections.mysql.port', 3306);

        $mysqlAvailable = false;
        $mysqlVersion = null;
        $databases = [];

        if ($this->isPortOpen($host, $port)) {
            $pdo = $this->createPdoConnection($host, $port);

            if ($pdo instanceof PDO) {
                $mysqlAvailable = true;
                $mysqlVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
                $databases = $this->queryUserDatabases($pdo);
            }
        }

        $sqlitePath = config('database.connections.backup_demo.database');
        $sqliteAvailable = is_string($sqlitePath) && file_exists($sqlitePath);

        $result = [
            'environment' => $stack.' ('.PHP_OS.')',
            'is_windows' => $isWindows,
            'stack' => $stack,
            'mysql_available' => $mysqlAvailable,
            'mysql_version' => $mysqlVersion,
            'mysql_host' => $host,
            'mysql_port' => $port,
            'mysqldump_path' => $this->resolveMysqldumpPath(),
            'databases' => $databases,
            'sqlite_available' => $sqliteAvailable,
            'sqlite_path' => $sqliteAvailable ? (string) $sqlitePath : null,
        ];

        Cache::put('env_detection_summary', $result, now()->addMinutes(10));

        return $this->memoryCache = $result;
    }

    public function clearCache(): void
    {
        $this->memoryCache = null;
        $this->cachedMysqldumpPath = null;
        $this->mysqldumpResolved = false;
        Cache::forget('env_detection_summary');
    }

    /**
     * Query and filter out system schemas from MySQL.
     *
     * @return list<string>
     */
    public function getAvailableDatabases(): array
    {
        $detection = $this->detect();

        return $detection['databases'];
    }

    private function detectStack(): string
    {
        if (is_dir('C:\\laragon')) {
            return 'Laragon';
        }

        if (is_dir('C:\\xampp') || is_dir('/opt/lampp')) {
            return 'XAMPP';
        }

        return 'Servidor Estándar';
    }

    private function isPortOpen(string $host, int $port): bool
    {
        try {
            $socket = @fsockopen($host, $port, $errorCode, $errorMsg, 0.05);

            if (is_resource($socket)) {
                fclose($socket);

                return true;
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    private function createPdoConnection(string $host, int $port): ?PDO
    {
        $username = (string) config('database.connections.mysql.username', 'root');
        $password = (string) config('database.connections.mysql.password', '');

        // Try with configured credentials
        try {
            return new PDO(
                "mysql:host={$host};port={$port}",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 1,
                ]
            );
        } catch (Throwable) {
            // Fallback for default Laragon/XAMPP with blank password
            if ($password !== '') {
                try {
                    return new PDO(
                        "mysql:host={$host};port={$port}",
                        'root',
                        '',
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 1,
                        ]
                    );
                } catch (Throwable) {
                    return null;
                }
            }

            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function queryUserDatabases(PDO $pdo): array
    {
        try {
            $statement = $pdo->query('SHOW DATABASES');
            $all = $statement ? $statement->fetchAll(PDO::FETCH_COLUMN) : [];

            $filtered = array_values(array_filter($all, function (string $db): bool {
                return ! in_array(strtolower($db), self::SYSTEM_SCHEMAS, true);
            }));

            sort($filtered, SORT_NATURAL | SORT_FLAG_CASE);

            return $filtered;
        } catch (Throwable) {
            return [];
        }
    }

    public function resolveMysqldumpPath(): ?string
    {
        if ($this->mysqldumpResolved) {
            return $this->cachedMysqldumpPath;
        }

        $this->mysqldumpResolved = true;

        // 1. FAST: Direct Laragon directory check (no child process spawn)
        if (is_dir('C:\\laragon\\bin\\mysql')) {
            $matches = glob('C:\\laragon\\bin\\mysql\\*\\bin\\mysqldump.exe');
            if (! empty($matches) && file_exists($matches[0])) {
                return $this->cachedMysqldumpPath = $matches[0];
            }
        }

        // 2. FAST: Direct XAMPP directory check
        if (file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
            return $this->cachedMysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        }

        // 3. FAST: Unix standard paths
        foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/opt/lampp/bin/mysqldump'] as $path) {
            if (file_exists($path)) {
                return $this->cachedMysqldumpPath = $path;
            }
        }

        // 4. Slow Fallback: PATH lookup via shell_exec (only if not found in known dirs)
        if (PHP_OS_FAMILY === 'Windows') {
            $cmdCheck = @shell_exec('where mysqldump.exe 2>NUL');
            if (is_string($cmdCheck) && trim($cmdCheck) !== '') {
                $lines = explode("\n", trim($cmdCheck));
                $first = trim($lines[0]);
                if (file_exists($first)) {
                    return $this->cachedMysqldumpPath = $first;
                }
            }
        }

        return $this->cachedMysqldumpPath = null;
    }
}
