# Operación inicial

Este procedimiento usa datos de prueba. No habilitarlo para fuentes institucionales hasta aprobar rutas, destino, retención, permisos, cifrado y recuperación.

## Interfaz del proyecto

La página `/` de Laravel incluye una prueba funcional, pero estrictamente aislada: **Respaldar base ficticia** procesa solo `backup-system/data/demo-institucional.sqlite`; **Probar recuperación** restaura un respaldo verificado en un archivo SQLite separado. La aplicación comprueba integridad SQLite y hashes SHA-256, comprime con gzip, cifra con la clave `APP_KEY` y registra las ejecuciones en MariaDB de la aplicación. Los artefactos se guardan en `storage/app/private/demo-backups` y las restauraciones en `storage/app/private/demo-restores`.

Estas acciones no leen la base de datos MariaDB de la aplicación como fuente, no modifican la SQLite original y no acceden a servidores o datos institucionales. El cifrado con `APP_KEY` sirve para la demostración local; en producción se deben usar claves custodiadas separadamente. El tiempo medido es de esta pequeña base sintética y no demuestra el RTO institucional. Los scripts PowerShell/Bash documentados abajo siguen siendo independientes del panel.

Para crear una base SQLite separada con registros académicos ficticios, ejecuta `php .\tools\create-demo-database.php` desde `backup-system/`. La salida queda en `data/demo-institucional.sqlite`, no modifica la base Laravel y no contiene información del instituto ni documentos de expedientes. El panel toma exclusivamente este archivo como origen de sus pruebas.

## Preparación

1. Instalar las dependencias descritas en `../arquitectura-y-estandares.md`.
2. Copiar `../../config/backup.config.example.json` a `../../config/backup.config.json`.
3. Sustituir rutas de origen y destino por ubicaciones aprobadas y existentes. El destino debe ser un almacenamiento secundario ya montado/conectado.
4. Configurar uno o más destinatarios públicos age. Guardar la identidad privada de descifrado en un medio/bóveda separados, con acceso restringido; nunca copiarla al repositorio ni al destino.
5. Mantener `enabled` en `false` durante la revisión. Ejecutar validación estructural:

   - Linux: `bash scripts/bash/backup.sh config/backup.config.json --validate-only`
   - Windows: `.\scripts\powershell\Backup-Run.ps1 -ConfigPath .\config\backup.config.json -ValidateOnly`

6. Habilitar la ejecución solo para un conjunto de datos sintéticos y una carpeta destino de prueba. El runner no instala tareas programadas.

## Pruebas de extremo a extremo

Ejecutar desde el workspace aislado en un host de prueba. Los runners de prueba crean su propia configuración y datos sintéticos temporales, generan una clave age temporal y eliminan esos datos al terminar:

- Linux: `bash tests/test-workflow.sh`
- Windows: `.\tests\Test-BackupWorkflow.ps1`

Las pruebas verifican cifrado/descifrado, contenido restaurado, rechazo de sobrescritura, denegación de retención deshabilitada y simulación/aplicación de retención sobre artefactos sintéticos.

## Ejecución manual de prueba

- Linux: `bash scripts/bash/backup.sh config/backup.config.json`
- Windows Server: `.\scripts\powershell\Backup-Run.ps1 -ConfigPath .\config\backup.config.json`

El resultado exitoso genera un archivo `*.tar.gz.age` y un sidecar `*.sha256`. El proceso falla si una fuente no existe, el destino no es accesible, una dependencia falta, otra ejecución mantiene el lock, la compresión/copia/cifra falla o el checksum no coincide.

## Restauración a un destino nuevo

- Linux: `bash recovery/bash/restore.sh <archivo.tar.gz.age> <ruta-identidad-age> <directorio-nuevo>`
- Windows: `.\recovery\powershell\Restore-Backup.ps1 -ArtifactPath <archivo.tar.gz.age> -IdentityPath <ruta-identidad-age> -TargetPath <directorio-nuevo>`

El directorio destino no debe existir previamente. Confirmar manualmente que los archivos restaurados y la aplicación funcionan; el script no publica datos sobre rutas originales ni inicia servicios. Registrar el tiempo de restauración para medir RTO.

## Activación de operación recurrente

Después de ejecutar con éxito pruebas repetidas de respaldo y restauración, configurar el horario aprobado. El programador de Windows se ejecuta como `SYSTEM`; conceder a esa identidad solo el acceso necesario:

- Linux/cron (usuario actual): `bash scripts/bash/install-schedule.sh config/backup.config.json`; quitarlo con `bash scripts/bash/remove-schedule.sh`. Requiere cron con soporte `CRON_TZ`.
- Windows (PowerShell elevado): `.\scripts\powershell\Register-BackupSchedule.ps1 -ConfigPath .\config\backup.config.json`; quitarlo con `.\scripts\powershell\Remove-BackupSchedule.ps1`.

Los instaladores rechazan una configuración deshabilitada o incompleta y no reemplazan tareas existentes. El horario usa la zona local declarada; en Windows el instalador verifica que coincida con la zona del servidor. La tarea inicia con la llegada del equipo si perdió su horario y omite ejecuciones concurrentes. Puede desinstalarse por los comandos anteriores.

La retención usa el último respaldo verificado de cada día durante `daily_copies`, de cada semana ISO (Linux)/semana de lunes a domingo (Windows) durante `weekly_copies`, y de cada mes durante `monthly_copies`; conserva la unión de esas selecciones. En la configuración inicial, los límites son una propuesta sin aprobación institucional. `retention.enabled` permanece en `false`; al habilitarlo, la tarea programada elimina los pares artefacto/checksum antiguos solo después de un respaldo exitoso y de verificar todos los candidatos. Un checksum ausente o incorrecto detiene la poda.

El programador debe notificar fallos mediante un mecanismo institucional externo. La implementación no configura notificación por correo/SIEM ni copia de base de datos. No programar sobre producción hasta confirmar estas integraciones y probar recuperación completa.
