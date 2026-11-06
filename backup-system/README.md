# Sistema de respaldos y recuperación

Proyecto de infraestructura independiente de la aplicación Laravel del workspace para el IESP Cap. FAP José Abelardo Quiñones.

## Arquitectura del workspace

```text
backup-system/
|-- config/
|   `-- backup.config.example.json
|-- database/
|   `-- demo-institucional.sql
|-- docs/
|   |-- arquitectura-y-estandares.md
|   `-- manuales/operacion-inicial.md
|-- logs/
|   `-- test/
|-- recovery/
|   |-- bash/restore.sh
|   `-- powershell/Restore-Backup.ps1
|-- scripts/
|   |-- bash/backup.sh, prune.sh, install-schedule.sh
|   `-- powershell/Backup-Run.ps1, Prune-Backups.ps1, Register-BackupSchedule.ps1
|-- tests/
|   |-- Test-BackupWorkflow.ps1
|   |-- Test-Retention.ps1
|   `-- test-workflow.sh
|-- tools/
|   `-- create-demo-database.php
|-- .gitignore
`-- README.md
```

Los datos de respaldo, claves privadas y logs de producción deben permanecer fuera del workspace y del repositorio. `logs/test/` es exclusivamente para pruebas con datos sintéticos; no se deben copiar allí datos institucionales. Los directorios se conservan en Git mediante archivos `.gitkeep` hasta que contengan archivos de proyecto.

## Inicio

La aplicación Laravel presenta el panel en la ruta `/`. En la sesión autenticada, el botón **Respaldar base ficticia** ejecuta una prueba aislada sobre `data/demo-institucional.sqlite`: revisa SQLite, comprime con gzip, cifra con la clave `APP_KEY`, verifica SHA-256 y conserva el historial en MariaDB de la aplicación. **Probar recuperación** descifra una copia válida y crea una base SQLite restaurada distinta, sin sobrescribir la fuente.

Los archivos de prueba quedan en el almacenamiento privado de Laravel, bajo `storage/app/private/demo-backups` y `storage/app/private/demo-restores`. Esta integración usa exclusivamente la SQLite ficticia; no lee la base MariaDB de la aplicación como fuente, no emplea las rutas del archivo de configuración de respaldos ni conecta servidores institucionales. La clave `APP_KEY` debe conservarse para descifrar estas pruebas. Este cifrado local es solo demostrativo y no reemplaza la custodia de claves separadas requerida para producción.

El tiempo de restauración y el hash que muestra el panel corresponden únicamente a esta base SQLite pequeña; no son una medición ni una garantía de RTO institucional. Los scripts PowerShell/Bash y su programación cron/Task Scheduler permanecen como un flujo independiente y no se activan desde el panel.

## Base SQLite demostrativa

Para generar una base local con datos académicos completamente ficticios y comprobar su integridad:

```powershell
php .\tools\create-demo-database.php
```

El archivo se crea en `data/demo-institucional.sqlite`, separado de la base de Laravel y excluido de Git. Incluye estudiantes de ejemplo, carreras, asignaturas, matrículas, notas, asistencias y referencias de expedientes (sin documentos reales). El generador no sobrescribe una base existente; para crearla de nuevo, mueve o renombra manualmente el archivo anterior después de confirmar el destino.

1. Copiar `config/backup.config.example.json` como `config/backup.config.json`.
2. Definir rutas reales y valores `age` públicos; proteger el archivo real de configuración y guardar la identidad privada fuera del servidor de respaldo.
3. Instalar `jq`, `tar`, `gzip`, `age`, `sha256sum`, `flock` y Bash en Linux. En Windows Server se requieren `age.exe`, `tar.exe`, `robocopy.exe` y Windows PowerShell 5.1 o posterior.
4. Validar estructura sin ejecutar respaldos: `bash scripts/bash/backup.sh config/backup.config.json --validate-only` o `.\scripts\powershell\Backup-Run.ps1 -ConfigPath .\config\backup.config.json -ValidateOnly`.
5. Probar retención en Windows con artefactos sintéticos: `.\tests\Test-Retention.ps1`. Las pruebas completas de respaldo/restauración son `bash tests/test-workflow.sh` o `.\tests\Test-BackupWorkflow.ps1`; requieren `age`, `age-keygen` y las herramientas de archivo/checksum de la plataforma.
6. Mantener `"enabled": false` hasta verificar fuentes, destino, permisos, cifrado y una restauración de prueba. Revisar `docs/arquitectura-y-estandares.md`.

La configuración de ejemplo tiene `"enabled": false`, `"retention.enabled": false` y contiene rutas ficticias. No está lista para ejecutar respaldos reales. La retención funciona primero en modo de simulación; el borrado requiere `--apply` y `retention.enabled: true`. La instalación de tareas programadas es explícita y exige configuración habilitada.

## Objetivo de recuperación

El objetivo solicitado es un RTO estrictamente menor a 15 minutos. Es un requisito de aceptación que debe medirse mediante restauraciones completas cronometradas; no puede garantizarse solo con la configuración. El resultado depende, entre otros factores, del volumen, el rendimiento del almacenamiento y la red, las dependencias de la aplicación y el tiempo para validar el servicio.

## Alcance de los scripts iniciales

Los runners realizan respaldos cifrados de archivos y carpetas y generan un manifiesto con las rutas de origen; las herramientas de restauración validan el hash, descifran, validan la estructura y restauran en un destino nuevo. Hay instaladores y desinstaladores explícitos para cron y el Programador de tareas; la retención automática corre solo después de un respaldo exitoso y cuando se habilita en la configuración. No realizan copias consistentes de bases de datos, snapshots ni alertas externas. Se rechazan enlaces simbólicos/reparse points y entradas especiales en archivos de restauración.
