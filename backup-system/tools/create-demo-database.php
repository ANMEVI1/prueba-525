<?php

declare(strict_types=1);

$projectDirectory = dirname(__DIR__);
$outputDirectory = $projectDirectory.DIRECTORY_SEPARATOR.'data';
$databasePath = $outputDirectory.DIRECTORY_SEPARATOR.'demo-institucional.sqlite';
$schemaPath = $projectDirectory.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'demo-institucional.sql';

if (! extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "Error: PHP debe tener habilitada la extensión pdo_sqlite.\n");
    exit(2);
}

if (! is_file($schemaPath)) {
    fwrite(STDERR, "Error: no se encontró el archivo SQL de demostración.\n");
    exit(2);
}

if (file_exists($databasePath)) {
    fwrite(STDERR, "Error: la base de demostración ya existe y no será sobrescrita: {$databasePath}\n");
    exit(1);
}

if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0700, true) && ! is_dir($outputDirectory)) {
    fwrite(STDERR, "Error: no se pudo crear el directorio de salida.\n");
    exit(1);
}

try {
    $database = new PDO('sqlite:'.$databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $database->exec('PRAGMA foreign_keys = ON');
    $database->beginTransaction();
    $database->exec((string) file_get_contents($schemaPath));
    $database->commit();

    $integrityResult = $database->query('PRAGMA integrity_check')->fetchColumn();
    if ($integrityResult !== 'ok') {
        throw new RuntimeException('La comprobación de integridad SQLite no fue satisfactoria.');
    }

    $foreignKeyViolations = $database->query('PRAGMA foreign_key_check')->fetchAll();
    if ($foreignKeyViolations !== []) {
        throw new RuntimeException('La comprobación encontró relaciones huérfanas.');
    }

    $tableCounts = [];
    foreach (['estudiantes', 'asignaturas', 'matriculas', 'notas', 'asistencias', 'expedientes'] as $table) {
        $tableCounts[$table] = (int) $database->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    $database = null;
    chmod($databasePath, 0600);

    fwrite(STDOUT, "Base SQLite sintética creada: {$databasePath}\n");
    fwrite(STDOUT, "Integridad SQLite: ok; relaciones: válidas.\n");
    foreach ($tableCounts as $table => $count) {
        fwrite(STDOUT, sprintf("%s: %d registros\n", $table, $count));
    }
} catch (Throwable $exception) {
    if (isset($database) && $database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }
    if (is_file($databasePath)) {
        unlink($databasePath);
    }
    fwrite(STDERR, "No se pudo crear la base de demostración: {$exception->getMessage()}\n");
    exit(1);
}
