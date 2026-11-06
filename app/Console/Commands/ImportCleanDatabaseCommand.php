<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use PDO;

class ImportCleanDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-clean-sql {--force : Sobrescribir la base de datos si ya tiene tablas} {--file= : Archivo SQL a importar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa automáticamente el volcado oficial iesp_continuidad_demo.sql en MySQL/MariaDB';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sqlPath = $this->option('file') ?: base_path('iesp_continuidad_demo.sql');

        if (! file_exists($sqlPath)) {
            $this->error("No se encontró el archivo SQL en: {$sqlPath}");

            return Command::FAILURE;
        }

        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database', 'iesp_continuidad_demo');
        $username = config('database.connections.mysql.username', 'root');
        $password = config('database.connections.mysql.password', '');

        try {
            // Conexión inicial sin seleccionar base de datos para verificar servidor
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]);

            // Verificar si la base de datos ya existe y tiene tablas
            $checkDb = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$database}'")->fetch();

            if ($checkDb && ! $this->option('force')) {
                $tablesCount = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$database}'")->fetchColumn();
                if ($tablesCount > 0) {
                    $this->info("La base de datos [{$database}] ya existe y contiene {$tablesCount} tablas.");
                    $this->info('No es necesario reimportar (usa --force si deseas sobrescribir).');

                    return Command::SUCCESS;
                }
            }

            $this->info("Importando esquema y datos institucionales en [{$database}]...");

            $sqlContent = file_get_contents($sqlPath);
            if ($sqlContent === false) {
                $this->error('No se pudo leer el contenido del archivo SQL.');

                return Command::FAILURE;
            }

            // Ejecutar el SQL completo respetando transacciones y charset
            $pdo->exec($sqlContent);

            $this->info("¡Base de datos [{$database}] importada correctamente con codificación UTF-8!");

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error al conectar o importar en MySQL: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
