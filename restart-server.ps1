# Script para FORZAR reinicio del servidor Laravel

Write-Host ""
Write-Host "======================================" -ForegroundColor Red
Write-Host "  REINICIANDO SERVIDOR FORZOSAMENTE" -ForegroundColor Red
Write-Host "======================================" -ForegroundColor Red
Write-Host ""

Write-Host "Deteniendo procesos de PHP..." -ForegroundColor Yellow

# Matar todos los procesos de php que estén corriendo artisan serve
Get-Process -Name php -ErrorAction SilentlyContinue | Where-Object {
    $_.CommandLine -like "*artisan serve*"
} | Stop-Process -Force -ErrorAction SilentlyContinue

Start-Sleep -Seconds 1

Write-Host "Procesos PHP detenidos" -ForegroundColor Green
Write-Host ""
Write-Host "Limpiando cache..." -ForegroundColor Yellow

php artisan config:clear | Out-Null
php artisan cache:clear | Out-Null

Write-Host "Cache limpiada" -ForegroundColor Green
Write-Host ""
Write-Host "Iniciando servidor en el puerto 8000..." -ForegroundColor Yellow
Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  SERVIDOR REINICIADO" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Accede a: http://127.0.0.1:8000/register" -ForegroundColor White
Write-Host ""

# Iniciar servidor
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd 'd:\xampp\htdocs\PROYECTOS\Nueva carpeta\ArchiveyCloud'; php artisan serve"
