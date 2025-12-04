# Script para cambiar el driver de correo a LOG
# Esto solucionará el error SSL y los correos se guardarán en storage/logs/laravel.log

$envFile = ".env"

Write-Host "=== Cambiando driver de correo a LOG ===" -ForegroundColor Cyan
Write-Host ""

# Leer el archivo .env
$content = Get-Content $envFile -Raw

# Reemplazar MAIL_MAILER
if ($content -match "MAIL_MAILER=smtp") {
    $content = $content -replace "MAIL_MAILER=smtp", "MAIL_MAILER=log"
    Write-Host "✓ Cambiado MAIL_MAILER de 'smtp' a 'log'" -ForegroundColor Green
} elseif ($content -match "MAIL_MAILER=") {
    $content = $content -replace "MAIL_MAILER=\w+", "MAIL_MAILER=log"
    Write-Host "✓ Cambiado MAIL_MAILER a 'log'" -ForegroundColor Green
} else {
    $content += "`nMAIL_MAILER=log"
    Write-Host "✓ Agregado MAIL_MAILER=log" -ForegroundColor Green
}

# Guardar el archivo
$content | Set-Content $envFile -NoNewline

Write-Host ""
Write-Host "✓ Archivo .env actualizado exitosamente" -ForegroundColor Green
Write-Host ""
Write-Host "Limpiando caché de configuración..." -ForegroundColor Yellow
php artisan config:clear

Write-Host ""
Write-Host "=== ¡Listo! ===" -ForegroundColor Green
Write-Host ""
Write-Host "Ahora:" -ForegroundColor Cyan
Write-Host "1. Los correos se guardarán en: storage/logs/laravel.log" -ForegroundColor White
Write-Host "2. Intenta registrarte nuevamente en: http://127.0.0.1:8000/register" -ForegroundColor White
Write-Host "3. Busca el enlace de verificación en el archivo de log" -ForegroundColor White
Write-Host ""
