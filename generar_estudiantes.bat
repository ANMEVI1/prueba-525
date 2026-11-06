@echo off
chcp 65001 >nul
title Generador de Estudiantes de Prueba - IESP Cap. FAP José Abelardo Quiñones

echo ===============================================================================
echo   GENERADOR DINÁMICO DE ESTUDIANTES (+20 ALUMNOS SIN REPETIR)
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

set /p CANTIDAD="¿Cuántos estudiantes deseas generar? (Presiona ENTER para 20): "
if "%CANTIDAD%"=="" set CANTIDAD=20

"%PHP_BIN%" artisan estudiantes:generar-demo %CANTIDAD%

echo.
pause

