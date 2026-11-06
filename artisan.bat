@echo off
setlocal
set "PHP_DIR=C:\laragon\bin\php\php-8.3.35-Win32-vs16-x64"
set "PATH=%PHP_DIR%;%PATH%"
"%PHP_DIR%\php.exe" "%~dp0artisan" %*
endlocal

