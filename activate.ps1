# Activa PHP 8.3 únicamente para la sesión actual de PowerShell
# No modifica variables de entorno globales del sistema ni afecta a otros proyectos
$env:PATH = "C:\laragon\bin\php\php-8.3.35-Win32-vs16-x64;" + $env:PATH
Write-Host "✅ [Alva_Proyecto] Entorno temporal PHP 8.3.35 activado en esta terminal." -ForegroundColor Green
Write-Host "   Versión activa en esta sesión:" -ForegroundColor Gray -NoNewline
Write-Host " $((php -v)[0])" -ForegroundColor Cyan
Write-Host "   Tus otros proyectos y la configuración global de tu equipo siguen intactos." -ForegroundColor DarkGray

