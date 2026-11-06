@echo off
chcp 65001 >nul
title Restauración de Base de Datos - IESP Cap. FAP José Abelardo Quiñones

echo ===============================================================================
echo   ASISTENTE DE RECUPERACIÓN Y RESTAURACIÓN DE BASE DE DATOS
echo   IESP CAP. FAP JOSÉ ABELARDO QUIÑONES - TUMBES
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
echo Asegúrate de tener XAMPP o Laragon instalado.
echo.
pause
exit /b 1

:php_found
echo [OK] Intérprete PHP detectado: %PHP_BIN%
echo.
echo ===============================================================================
echo   LISTA DE RESPALDOS DISPONIBLES EN EL SISTEMA:
echo ===============================================================================
"%PHP_BIN%" artisan backup:restore --list
echo.
echo ===============================================================================
set /p BACKUP_ID="Escribe el ID del respaldo a restaurar (o presiona ENTER para el más reciente): "

if "%BACKUP_ID%"=="" (
    echo [INFO] Restaurando la copia de seguridad más reciente...
    "%PHP_BIN%" artisan backup:restore
) else (
    echo [INFO] Restaurando la copia de seguridad con ID #%BACKUP_ID%...
    "%PHP_BIN%" artisan backup:restore %BACKUP_ID%
)

if errorlevel 1 (
    echo.
    echo ===============================================================================
    echo  [AVISO] No se pudo restaurar la base de datos automáticamente.
    echo  Asegúrate de que MySQL esté activo en el puerto 3306 (XAMPP / Laragon).
    echo ===============================================================================
    echo.
    pause
    exit /b 1
)

echo.
echo [INFO] Limpiando caché del aplicativo...
"%PHP_BIN%" artisan optimize:clear >nul 2>nul

echo.
echo ===============================================================================
echo   ✅ PROCESO DE RESTAURACIÓN COMPLETADO CON ÉXITO
echo ===============================================================================
echo   La base de datos institucional ha sido restablecida en MySQL.
echo   Puedes continuar usando el sistema en tu navegador.
echo ===============================================================================
echo.
pause
