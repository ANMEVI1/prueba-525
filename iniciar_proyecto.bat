@echo off
chcp 65001 >nul
title IESP Cap. FAP José Abelardo Quiñones - Continuidad TI

echo ===============================================================================
echo   IESP CAP. FAP JOSÉ ABELARDO QUIÑONES - TUMBES
echo   Sistema de Continuidad Operativa y Gestión de Respaldos TI
echo ===============================================================================
echo.

:: 1. Detectar intérprete PHP (PATH, XAMPP, Laragon)
set PHP_BIN=
where php.exe >nul 2>nul
if %errorlevel% equ 0 (
    set PHP_BIN=php.exe
    goto php_found
)

if exist "C:\xampp\php\php.exe" (
    set PHP_BIN=C:\xampp\php\php.exe
    set "PATH=C:\xampp\php;%PATH%"
    goto php_found
)

if exist "D:\xampp\php\php.exe" (
    set PHP_BIN=D:\xampp\php\php.exe
    set "PATH=D:\xampp\php;%PATH%"
    goto php_found
)

if exist "C:\xampp2\php\php.exe" (
    set PHP_BIN=C:\xampp2\php\php.exe
    set "PATH=C:\xampp2\php;%PATH%"
    goto php_found
)

for /d %%D in ("C:\laragon\bin\php\php-*") do (
    if exist "%%D\php.exe" (
        set PHP_BIN=%%D\php.exe
        set "PATH=%%D;%PATH%"
    )
)
if not "%PHP_BIN%"=="" goto php_found

for /d %%D in ("D:\laragon\bin\php\php-*") do (
    if exist "%%D\php.exe" (
        set PHP_BIN=%%D\php.exe
        set "PATH=%%D;%PATH%"
    )
)
if not "%PHP_BIN%"=="" goto php_found

:php_not_found
echo [ERROR] No se encontró el intérprete de PHP en tu computadora.
echo Por favor asegúrate de tener instalado XAMPP (C:\xampp) o Laragon (C:\laragon).
echo.
pause
exit /b 1

:php_found
echo [OK] Intérprete PHP detectado: %PHP_BIN%

:: 1.5. Verificar existencia de la carpeta 'vendor'
if not exist "vendor\autoload.php" (
    echo.
    echo ===============================================================================
    echo  [AVISO] No se encontró la carpeta 'vendor' (librerías del framework Laravel).
    echo ===============================================================================
    echo  Intentando instalar dependencias con Composer automáticamente...
    echo.
    
    where composer >nul 2>nul
    if %errorlevel% equ 0 (
        echo [INFO] Ejecutando: composer install...
        call composer install --no-interaction --prefer-dist
    ) else if exist "composer.phar" (
        echo [INFO] Ejecutando: composer.phar local...
        "%PHP_BIN%" composer.phar install --no-interaction --prefer-dist
    ) else if exist "C:\composer\composer.phar" (
        echo [INFO] Ejecutando: C:\composer\composer.phar...
        "%PHP_BIN%" "C:\composer\composer.phar" install --no-interaction --prefer-dist
    )
    
    if not exist "vendor\autoload.php" (
        echo.
        echo [ERROR] No se pudo encontrar ni generar la carpeta 'vendor'.
        echo.
        echo Soluciones rápidas:
        echo   A) Si tienes el proyecto completo, copia la carpeta 'vendor' dentro de esta carpeta.
        echo   B) Instala Composer (https://getcomposer.org/) y ejecuta 'composer install'.
        echo.
        pause
        exit /b 1
    )
    echo [OK] Librerías de vendor preparadas con éxito.
)

:: 2. Configurar entorno (.env)
if not exist ".env" (
    echo [INFO] Creando archivo de configuración .env desde .env.example...
    copy ".env.example" ".env" >nul
)

:: Verificar APP_KEY
findstr /C:"APP_KEY=" .env >nul
if %errorlevel% equ 0 (
    findstr /C:"APP_KEY=base64:" .env >nul
    if errorlevel 1 (
        echo [INFO] Generando clave de cifrado de aplicación (APP_KEY)...
        "%PHP_BIN%" artisan key:generate --force
    )
)

:: 3. Asegurar estructura de directorios privados
if not exist "storage\app\backups" mkdir "storage\app\backups"
if not exist "storage\app\backups\secondary" mkdir "storage\app\backups\secondary"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\logs" mkdir "storage\logs"

:: 4. Comprobar e inicializar Base de Datos en MySQL (XAMPP / Laragon)
echo [INFO] Verificando conexión e inicialización de la Base de Datos...
"%PHP_BIN%" artisan app:import-clean-sql
if errorlevel 1 (
    echo.
    echo ===============================================================================
    echo  [AVISO] No se pudo conectar al servidor MySQL en 127.0.0.1:3306.
    echo  Por favor realiza lo siguiente:
    echo   1. Abre el Panel de Control de XAMPP (o Laragon).
    echo   2. Inicia el módulo MySQL (puerto 3306).
    echo ===============================================================================
    echo.
    echo Presiona cualquier tecla una vez que MySQL esté iniciado para reintentar...
    pause >nul
    "%PHP_BIN%" artisan app:import-clean-sql
)

:: 5. Limpieza de caché local
"%PHP_BIN%" artisan optimize:clear >nul 2>nul

:: 6. Iniciar Programador Automatizado de Respaldos (Scheduler en segundo plano)
echo [INFO] Arrancando Programador de Respaldos Automáticos en segundo plano...
start "IESP Quiñones - Automatización de Respaldos TI" /min "%PHP_BIN%" artisan schedule:work

echo.
echo ===============================================================================
echo   SISTEMA DE CONTINUIDAD TI PREPARADO Y ACTIVO
echo ===============================================================================
echo   * URL del Sistema:    http://127.0.0.1:8000
echo   * Scheduler Activo:   Automatización en segundo plano (schedule:work)
echo   * Usuario de Acceso:  Admin  (o admin@demo.local)
echo   * Contraseña:         Admin
echo ===============================================================================
echo.
echo Abriendo la aplicación en tu navegador predeterminado...
start http://127.0.0.1:8000
echo.
echo Presiona Ctrl+C en esta ventana para detener el servidor web institucional.
echo.
"%PHP_BIN%" artisan serve --host=127.0.0.1 --port=8000
pause
