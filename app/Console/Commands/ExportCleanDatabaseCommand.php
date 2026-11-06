<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ExportCleanDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-clean-sql {--output= : Ruta destino del archivo .sql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un volcado SQL universal compatible con XAMPP (phpMyAdmin / MariaDB) y Laragon (MySQL 8) sin corrupción de caracteres UTF-8';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando exportación limpia para XAMPP / phpMyAdmin...');

        $database = config('database.connections.mysql.database', 'iesp_continuidad_demo');
        $outputPath = $this->option('output') ?: base_path('iesp_continuidad_demo.sql');

        /** @var PDO $pdo */
        $pdo = DB::connection('mysql')->getPdo();
        $pdo->exec('SET NAMES utf8mb4');

        $buffer = [];
        $buffer[] = '-- phpMyAdmin SQL Dump';
        $buffer[] = '-- version 5.2.1';
        $buffer[] = '-- https://www.phpmyadmin.net/';
        $buffer[] = '--';
        $buffer[] = '-- Servidor: 127.0.0.1';
        $buffer[] = '-- Tiempo de generación: '.date('d-m-Y H:i:s');
        $buffer[] = '-- Compatibilidad: XAMPP (MariaDB 10.4+) / Laragon (MySQL 8.x) / phpMyAdmin';
        $buffer[] = '-- Arquitectura: IESP Cap. FAP José Abelardo Quiñones - Continuidad TI';
        $buffer[] = '--';
        $buffer[] = '';
        $buffer[] = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";';
        $buffer[] = 'START TRANSACTION;';
        $buffer[] = 'SET time_zone = "+00:00";';
        $buffer[] = '';
        $buffer[] = '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;';
        $buffer[] = '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;';
        $buffer[] = '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;';
        $buffer[] = '/*!40101 SET NAMES utf8mb4 */;';
        $buffer[] = '';
        $buffer[] = '--';
        $buffer[] = '-- Base de datos: `'.$database.'`';
        $buffer[] = '--';
        $buffer[] = 'CREATE DATABASE IF NOT EXISTS `'.$database.'` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;';
        $buffer[] = 'USE `'.$database.'`;';
        $buffer[] = '';
        $buffer[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $buffer[] = '';

        $tablesStmt = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tables = [];
        while ($row = $tablesStmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $this->line("Procesando tabla: {$table}...");

            $buffer[] = '-- --------------------------------------------------------';
            $buffer[] = '--';
            $buffer[] = "-- Estructura de tabla para la tabla `{$table}`";
            $buffer[] = '--';
            $buffer[] = "DROP TABLE IF EXISTS `{$table}`;";

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            $createTableSql = $createStmt[1] ?? '';

            // Limpieza para máxima portabilidad XAMPP/MariaDB
            $createTableSql = preg_replace('/AUTO_INCREMENT=\d+\s*/i', '', $createTableSql);
            $buffer[] = $createTableSql.';';
            $buffer[] = '';

            // Volcado de datos
            $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (! empty($rows)) {
                $buffer[] = '--';
                $buffer[] = "-- Volcado de datos para la tabla `{$table}`";
                $buffer[] = '--';

                $columns = array_keys($rows[0]);
                $quotedColumns = array_map(fn ($c) => "`{$c}`", $columns);
                $columnsList = implode(', ', $quotedColumns);

                $chunkSize = 100;
                $chunks = array_chunk($rows, $chunkSize);

                foreach ($chunks as $chunk) {
                    $valuesList = [];
                    foreach ($chunk as $row) {
                        $escapedValues = [];
                        foreach ($columns as $col) {
                            $val = $row[$col];
                            if ($val === null) {
                                $escapedValues[] = 'NULL';
                            } elseif (is_numeric($val) && ! is_string($val)) {
                                $escapedValues[] = $val;
                            } else {
                                $escapedValues[] = $pdo->quote((string) $val);
                            }
                        }
                        $valuesList[] = '('.implode(', ', $escapedValues).')';
                    }

                    $buffer[] = "INSERT INTO `{$table}` ({$columnsList}) VALUES\n".implode(",\n", $valuesList).';';
                }
                $buffer[] = '';
            }
        }

        $buffer[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $buffer[] = 'COMMIT;';
        $buffer[] = '';
        $buffer[] = '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;';
        $buffer[] = '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;';
        $buffer[] = '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;';
        $buffer[] = '';

        $sqlContent = implode("\n", $buffer);
        file_put_contents($outputPath, $sqlContent);

        $bytes = strlen($sqlContent);
        $kb = round($bytes / 1024, 2);

        $this->info("¡Exportación completada con éxito en [{$outputPath}]! ({$kb} KB)");
        $this->info('Codificación garantizada en UTF-8 puro con soporte para tildes y eñes.');

        return Command::SUCCESS;
    }
}
