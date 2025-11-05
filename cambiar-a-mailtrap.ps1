# Script para cambiar de Gmail a Mailtrap

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  CAMBIANDO A MAILTRAP" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Gmail tiene problemas de certificados SSL en XAMPP/Windows" -ForegroundColor Yellow
Write-Host "Vamos a usar Mailtrap que es mas confiable para desarrollo" -ForegroundColor Yellow
Write-Host ""
Write-Host "PASOS:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Ve a: https://mailtrap.io" -ForegroundColor White
Write-Host "2. Crea una cuenta gratuita" -ForegroundColor White
Write-Host "3. Ve a 'Email Testing' -> 'Inboxes' -> 'My Inbox'" -ForegroundColor White
Write-Host "4. En 'SMTP Settings', selecciona 'Laravel 9+'" -ForegroundColor White
Write-Host "5. Copia las credenciales que te muestra" -ForegroundColor White
Write-Host ""
Write-Host "Presiona Enter cuando tengas las credenciales..." -ForegroundColor Yellow
Read-Host

$envPath = ".env"
$content = Get-Content $envPath -Raw

Write-Host ""
Write-Host "Actualizando .env para Mailtrap..." -ForegroundColor Yellow

# Configurar Mailtrap
$content = $content -replace "(?m)^MAIL_MAILER=.*$", "MAIL_MAILER=smtp"
$content = $content -replace "(?m)^MAIL_HOST=.*$", "MAIL_HOST=sandbox.smtp.mailtrap.io"
$content = $content -replace "(?m)^MAIL_PORT=.*$", "MAIL_PORT=2525"
$content = $content -replace "(?m)^MAIL_ENCRYPTION=.*$", "MAIL_ENCRYPTION=tls"
$content = $content -replace "(?m)^MAIL_FROM_ADDRESS=.*$", 'MAIL_FROM_ADDRESS="noreply@archiveycloud.com"'

Write-Host ""
Write-Host "IMPORTANTE: Debes actualizar estas lineas en tu .env:" -ForegroundColor Red
Write-Host ""
Write-Host "MAIL_USERNAME=TU_USERNAME_DE_MAILTRAP" -ForegroundColor Yellow
Write-Host "MAIL_PASSWORD=TU_PASSWORD_DE_MAILTRAP" -ForegroundColor Yellow
Write-Host ""

Set-Content $envPath -Value $content -NoNewline

Write-Host "Configuracion basica aplicada" -ForegroundColor Green
Write-Host ""
Write-Host "Ahora edita manualmente el .env y actualiza:" -ForegroundColor Cyan
Write-Host "  - MAIL_USERNAME" -ForegroundColor White
Write-Host "  - MAIL_PASSWORD" -ForegroundColor White
Write-Host ""
Write-Host "Luego ejecuta:" -ForegroundColor Yellow
Write-Host "  php artisan config:clear" -ForegroundColor White
Write-Host "  Reinicia el servidor (Ctrl+C y php artisan serve)" -ForegroundColor White
Write-Host ""
