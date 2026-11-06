<?php

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Extracts SQL dumps from MySQL/MariaDB databases, supporting both
 * native mysqldump CLI binary and a standalone pure-PHP PDO fallback engine.
 */
class MySqlDumper
{
    public function __construct(private readonly EnvironmentDetector $detector) {}

    /**
     * Dumps the specified database and returns the raw SQL content.
     */
    public function dump(string $databaseName): string
    {
        $mysqldumpPath = $this->detector->resolveMysqldumpPath();

        if (is_string($mysqldumpPath) && file_exists($mysqldumpPath)) {
            try {
                return $this->dumpViaCli($mysqldumpPath, $databaseName);
            } catch (Throwable) {
                // CLI failed (e.g. permission or arguments); fall back to PDO engine
            }
        }

        return $this->dumpViaPdo($databaseName);
    }

    private function dumpViaCli(string $binaryPath, string $databaseName): string
    {
        $host = (string) config('database.connections.mysql.host', '127.0.0.1');
        $port = (string) config('database.connections.mysql.port', '3306');
        $user = (string) config('database.connections.mysql.username', 'root');
        $pass = (string) config('database.connections.mysql.password', '');

        $passArg = $pass !== '' ? '--password='.escapeshellarg($pass) : '';

        // Safe command line construction
        $cmd = sprintf(
            '"%s" --host=%s --port=%s --user=%s %s --single-transaction --quick --routines --triggers --skip-comments %s 2>&1',
            $binaryPath,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $passArg,
            escapeshellarg($databaseName)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $sql = implode("\n", $output);

        if ($returnCode !== 0 || str_contains($sql, 'mysqldump: Got error:')) {
            throw new RuntimeException("mysqldump CLI falló: {$sql}");
        }

        if (trim($sql) === '') {
            throw new RuntimeException("mysqldump generó un contenido vacío para '{$databaseName}'.");
        }

        return $sql;
    }

    private function dumpViaPdo(string $databaseName): string
    {
        $host = (string) config('database.connections.mysql.host', '127.0.0.1');
        $port = (int) config('database.connections.mysql.port', 3306);
        $user = (string) config('database.connections.mysql.username', 'root');
        $pass = (string) config('database.connections.mysql.password', '');

        $pdo = null;

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$databaseName};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (Throwable $e) {
            // Try with empty password if configured password fails
            if ($pass !== '') {
                try {
                    $pdo = new PDO(
                        "mysql:host={$host};port={$port};dbname={$databaseName};charset=utf8mb4",
                        'root',
                        '',
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]
                    );
                } catch (Throwable) {
                    throw new RuntimeException("No se pudo conectar a la base de datos MySQL '{$databaseName}': ".$e->getMessage());
                }
            } else {
                throw new RuntimeException("No se pudo conectar a la base de datos MySQL '{$databaseName}': ".$e->getMessage());
            }
        }

        $dump = "-- ===========================================================\n";
        $dump .= "-- Respaldo Institucional IESP Cap. FAP José Abelardo Quiñones\n";
        $dump .= "-- Base de Datos: {$databaseName}\n";
        $dump .= '-- Generado: '.date('Y-m-d H:i:s')."\n";
        $dump .= "-- Motor: PHP PDO Standalone Dumper\n";
        $dump .= "-- ===========================================================\n\n";
        $dump .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $dump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $dump .= "SET NAMES utf8mb4;\n\n";

        $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = $tablesStmt ? $tablesStmt->fetchAll(PDO::FETCH_COLUMN) : [];

        foreach ($tables as $table) {
            $dump .= "-- -----------------------------------------------------------\n";
            $dump .= "-- Estructura para la tabla `{$table}`\n";
            $dump .= "-- -----------------------------------------------------------\n";
            $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt ? $createStmt->fetch(PDO::FETCH_NUM) : null;
            if ($createRow && isset($createRow[1])) {
                $dump .= $createRow[1].";\n\n";
            }

            // Dump data rows
            $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $dataStmt ? $dataStmt->fetchAll(PDO::FETCH_ASSOC) : [];

            if (! empty($rows)) {
                $dump .= "-- Datos para la tabla `{$table}`\n";
                $columns = array_keys($rows[0]);
                $quotedColumns = array_map(fn ($c) => "`{$c}`", $columns);
                $columnsList = implode(', ', $quotedColumns);

                $batch = [];
                foreach ($rows as $row) {
                    $escapedValues = array_map(function ($val) use ($pdo) {
                        if ($val === null) {
                            return 'NULL';
                        }

                        return $pdo->quote((string) $val);
                    }, array_values($row));

                    $batch[] = '('.implode(', ', $escapedValues).')';

                    if (count($batch) >= 100) {
                        $dump .= "INSERT INTO `{$table}` ({$columnsList}) VALUES\n".implode(",\n", $batch).";\n";
                        $batch = [];
                    }
                }

                if (! empty($batch)) {
                    $dump .= "INSERT INTO `{$table}` ({$columnsList}) VALUES\n".implode(",\n", $batch).";\n";
                }

                $dump .= "\n";
            }
        }

        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $dump;
    }
}
