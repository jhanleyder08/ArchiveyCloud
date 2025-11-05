# Cambiar Gmail a puerto 465 con SSL en lugar de 587 con STARTTLS

Write-Host ""
Write-Host "Cambiando a Puerto 465 con SSL..." -ForegroundColor Yellow
Write-Host ""

$envPath = ".env"
$content = Get-Content $envPath -Raw

# Cambiar puerto y encriptacion
$content = $content -replace "(?m)^MAIL_PORT=.*$", "MAIL_PORT=465"
$content = $content -replace "(?m)^MAIL_ENCRYPTION=.*$", "MAIL_ENCRYPTION=ssl"

Set-Content $envPath -Value $content -NoNewline

Write-Host "Configuracion actualizada:" -ForegroundColor Green
Write-Host "  MAIL_PORT=465" -ForegroundColor White
Write-Host "  MAIL_ENCRYPTION=ssl" -ForegroundColor White
Write-Host ""

Write-Host "Limpiando cache..." -ForegroundColor Yellow
php artisan config:clear | Out-Null

Write-Host ""
Write-Host "LISTO - Ahora reinicia el servidor:" -ForegroundColor Green
Write-Host "  1. Ctrl+C en la terminal del servidor" -ForegroundColor White
Write-Host "  2. php artisan serve" -ForegroundColor White
Write-Host "  3. Prueba el registro nuevamente" -ForegroundColor White
Write-Host ""
