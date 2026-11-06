# Arquitectura y estándares operativos

## 1. Alcance y componentes

La solución se mantiene aislada en `backup-system/` para que los scripts de infraestructura no se ejecuten como parte de Laravel.

| Componente | Responsabilidad |
|---|---|
| `config/` | Configuración versionable de ejemplo y configuración local de ejecución excluida de Git. |
| `scripts/bash/backup.sh` | Respaldo de archivos/carpetas en Linux, compresión gzip, cifrado age y checksum. |
| `scripts/powershell/Backup-Run.ps1` | Respaldo de archivos/carpetas en Windows Server, compresión gzip, cifrado age y checksum. |
| `recovery/` | Restauradores que verifican hash y restauran en una ruta nueva. |
| `docs/manuales/` | Manuales de operación, respuesta a fallos y recuperación ante desastres. |
| `tests/` | Pruebas automatizadas con datos sintéticos y sin acceso a producción. |
| `logs/test/` | Evidencia de ejecución de pruebas no productivas; se excluye de Git. |

Los archivos de respaldo, temporales, estado de ejecución y logs de producción se ubican en volúmenes de datos con ACL/permisos restringidos, no en el workspace.

## 2. Flujo de respaldo

1. Validar configuración, existencia de fuentes, conectividad con el destino, utilidades requeridas y exclusión mutua (lock).
2. Copiar el contenido de las fuentes de archivos a un staging local. Los runners actuales no respaldan bases de datos ni coordinan snapshots; no incluir archivos de bases de datos activas ni asumir consistencia de aplicaciones.
3. Crear un archivo comprimido, cifrarlo antes de transferirlo y calcular un hash criptográfico del artefacto final.
4. Escribirlo en un destino secundario ya montado/conectado. Verificar el SHA-256 del archivo cifrado en el destino. Esto detecta corrupción, pero no sustituye una restauración de prueba.
5. Publicar el artefacto de forma atómica solo tras completar las verificaciones. Registrar el resultado y alertar mediante el mecanismo externo que se implemente para producción.
6. La poda opcional conserva la unión de las últimas copias válidas por día, semana y mes según la configuración. Se mantiene deshabilitada hasta aprobar límites y verificar una simulación con datos sintéticos.

Recomendación de diseño: aplicar la regla 3-2-1-1-0 (múltiples copias, medios distintos, una copia fuera del sitio, una copia offline o inmutable y cero errores de verificación). El destino secundario no debe compartir credenciales administrativas con el servidor respaldado.

## 3. Cifrado y gestión de claves

- Usar cifrado autenticado con `age` (X25519 y ChaCha20-Poly1305) y compresión previa al cifrado. Mantener la clave privada de recuperación offline o en una bóveda con acceso restringido; no guardarla junto al respaldo.
- La configuración versionada puede incluir únicamente destinatarios/identificadores públicos. Nunca incluir claves privadas, contraseñas, tokens ni credenciales.
- Proteger el archivo real de configuración y el acceso a las claves con permisos mínimos. La rotación de claves exige conservar las claves antiguas hasta expirar y verificar todos los respaldos cifrados con ellas.
- Documentar la custodia y el procedimiento de recuperación de claves en un canal controlado, independiente de este repositorio. Sin clave de descifrado probada, un respaldo cifrado no es recuperable.

## 4. Programación, retención y observabilidad

- La configuración de ejemplo propone un respaldo diario a las 22:00. Los instaladores explícitos configuran cron (con `CRON_TZ`) o el Programador de tareas de Windows. El job ejecuta la poda solo después de que el respaldo tenga éxito y `retention.enabled` esté aprobado.
- La retención configurada es una propuesta inicial, no una decisión institucional. Acordar sus límites con los responsables de datos, requisitos regulatorios, capacidad y objetivos de recuperación antes de habilitar borrados.
- Registrar inicio/fin, host, identificador de ejecución, fuentes (sin contenido sensible), tamaño, duración, resultado de compresión/cifrado/verificación/transferencia y errores relevantes.
- Centralizar logs de producción fuera del workspace, limitar su acceso y establecer rotación. No registrar secretos ni contenido de archivos.
- Alertar ante fallos, falta de espacio, destino inaccesible, vencimiento del último respaldo válido, retraso de la tarea o resultado no verificado. Un proceso que termina con error debe reportarse como fallo, no como éxito parcial.
- Los runners actuales registran éxito o error en JSONL y usan locks, pero no integran envío de alertas ni rotación de logs. Configurar un monitor externo antes de producción.

## 5. Estándar de implementación

### Bash

- Iniciar con `#!/usr/bin/env bash` y `set -Eeuo pipefail`; definir `IFS` de forma segura si se requiere.
- Citar todas las expansiones, validar argumentos y configuración, usar arrays para listas de rutas y evitar `eval`.
- Instalar un `trap` para registrar contexto y código de salida en errores; no suprimir fallos con `|| true` ni con redirecciones que oculten errores.
- Usar `flock` u otro mecanismo equivalente para evitar ejecuciones concurrentes. Crear temporales con permisos restrictivos y limpiarlos en `trap`.
- Comprobar el código de salida de cada utilidad externa; no construir comandos desde cadenas de configuración.
- Dependencias del runner: `jq`, `tar`, `gzip`, `age`, `sha256sum`, `flock`, `realpath`, `find` y `cp`.

### PowerShell

- Usar `Set-StrictMode -Version Latest` y `$ErrorActionPreference = 'Stop'`; preferir `try/catch/finally` con errores registrados y código de salida no cero.
- Validar rutas y parámetros antes de operar, usar `-LiteralPath` cuando corresponda y evitar invocar comandos mediante cadenas dinámicas.
- Liberar locks y temporales en `finally`; revisar explícitamente resultados de procesos externos, ya que no todas las versiones propagan automáticamente sus códigos de salida.
- Evitar logs que contengan secretos o rutas/valores sensibles innecesarios.
- Dependencias del runner: `age.exe`, `tar.exe` y `robocopy.exe`. Las copias de carpetas usan `robocopy /COPY:DAT`; no conservan ACL, propietarios, auditoría ni flujos alternativos NTFS.

### Reglas comunes

- Separar configuración, validación, respaldo, cifrado, transferencia, verificación, retención y restauración en funciones/módulos pequeños.
- Validar entradas y dependencias al inicio (fail fast). No usar valores por defecto que oculten una configuración incompleta; no continuar con cifrado, verificación o destino deshabilitados.
- Restringir permisos, usar mínimo privilegio, impedir sobrescrituras accidentales y publicar resultados de manera atómica.
- Usar códigos de salida documentados y logs estructurados con marcas de tiempo y zona horaria. Redactar errores con suficiente contexto operativo, sin exponer secretos.
- La retención solo puede borrar artefactos cuyos checksums son válidos. Ante un checksum ausente o incorrecto, detiene la poda. La aplicación requiere configuración habilitada y bandera explícita; conserva la unión de las selecciones diaria/semanal/mensual.
- Los runners rechazan fuentes que sean enlaces simbólicos o puntos de reanálisis. Solo respaldan archivos/carpetas; no son una solución de imagen del sistema ni realizan exportaciones consistentes de bases de datos. No conservan ACL/permisos extendidos, propietario, auditoría ni flujos alternativos; el alcance es contenido, atributos/fechas básicos y estructura de carpetas.
- El checksum acompaña al artefacto en el mismo destino, por lo que ayuda a detectar corrupción accidental, pero no acredita por sí solo el origen ni protege contra la manipulación conjunta de ambos archivos. `age` autentica el contenido cifrado durante el descifrado.
- Las pruebas deben cubrir rutas inexistentes/no accesibles, falta de espacio, interrupción de red, error de cifrado/descifrado, integridad incorrecta, ejecuciones concurrentes y aplicación de retención. Usar datos sintéticos.

## 6. Recuperación y aceptación del RTO

El procedimiento de recuperación debe identificar el punto de restauración, obtener la clave por el canal de custodia, verificar el artefacto, descifrar y restaurar en el host/volumen objetivo, validar dependencias y comprobar la disponibilidad funcional del servicio. La prioridad es recuperar servicios y datos conforme al orden institucional de criticidad.

Cada prueba registra el tiempo desde la declaración del incidente hasta que el servicio está funcional y validado, junto con el tamaño restaurado, fecha del respaldo, resultado de integridad y fallos. La aceptación requiere demostrar **menos de 900 segundos** en escenarios representativos y repetibles. Si no se alcanza, ajustar arquitectura/capacidad/procedimiento y volver a medir. No medir solo el tiempo de copiar archivos.

Una programación automática puede eliminar intervención rutinaria, pero no reemplaza alertas, revisiones periódicas de restauración, gestión de claves ni aprobación del plan de continuidad. Los scripts iniciales no crean la tarea programada ni ejecutan la retención automáticamente.

## 7. Datos pendientes antes de producción

- Inventario aprobado de servidores, aplicaciones, bases de datos, rutas y dependencias que deben respaldarse.
- El workspace Laravel actual usa SQLite para su base de datos local, pero no se ha confirmado el motor instalado en los servidores institucionales. No ejecutar respaldos de producción de bases de datos hasta identificar motor/versión, ubicación, consistencia requerida, credenciales de mínimo privilegio y método nativo de exportación/restauración. Un adaptador SQLite (API `.backup`), MySQL/MariaDB (`mysqldump`), PostgreSQL (`pg_dump`) o SQL Server debe probarse contra el motor real; ninguno está implementado en esta versión.
- Sistema operativo y versión por servidor; ventanas de mantenimiento y zona horaria.
- Destino secundario, ubicación fuera del sitio, capacidad, conectividad, permisos y controles de inmutabilidad/offline.
- Retención institucional aprobada y clasificación de los datos.
- Destinatarios de cifrado y proceso de custodia/recuperación de claves.
- Contactos y canales de alertas, responsable de operación y orden de recuperación.
- Línea base de volumen y pruebas que demuestren el RTO objetivo.
