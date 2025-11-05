# Script PowerShell para configurar Email en .env
# Uso: .\configurar-email.ps1

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "CONFIGURACIÓN DE EMAIL PARA GMAIL" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Solicitar email del usuario
$emailUsuario = Read-Host "Ingresa tu email de Gmail (ejemplo@gmail.com)"

if ([string]::IsNullOrWhiteSpace($emailUsuario)) {
    Write-Host "ERROR: Debes ingresar un email válido" -ForegroundColor Red
    exit 1
}

$envPath = ".env"

# Verificar si existe .env
if (-not (Test-Path $envPath)) {
    Write-Host "ERROR: No se encontró el archivo .env" -ForegroundColor Red
    Write-Host "Copiando .env.example a .env..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
}

Write-Host "Actualizando configuración de email..." -ForegroundColor Yellow

# Leer contenido del .env
$content = Get-Content $envPath -Raw

# Reemplazar configuraciones de email
$content = $content -replace "MAIL_MAILER=.*", "MAIL_MAILER=smtp"
$content = $content -replace "MAIL_HOST=.*", "MAIL_HOST=smtp.gmail.com"
$content = $content -replace "MAIL_PORT=.*", "MAIL_PORT=587"
$content = $content -replace "MAIL_USERNAME=.*", "MAIL_USERNAME=$emailUsuario"
$content = $content -replace "MAIL_PASSWORD=.*", "MAIL_PASSWORD=dkrnhloiknzfzohw"
$content = $content -replace "MAIL_ENCRYPTION=.*", "MAIL_ENCRYPTION=tls"
$content = $content -replace "MAIL_FROM_ADDRESS=.*", "MAIL_FROM_ADDRESS=$emailUsuario"
$content = $content -replace 'MAIL_FROM_NAME=.*', 'MAIL_FROM_NAME="Archivey Cloud SGDEA"'

# Guardar cambios
Set-Content $envPath -Value $content

Write-Host ""
Write-Host "✅ Configuración actualizada correctamente" -ForegroundColor Green
Write-Host ""
Write-Host "Configuración aplicada:" -ForegroundColor Cyan
Write-Host "  - MAIL_MAILER: smtp" -ForegroundColor White
Write-Host "  - MAIL_HOST: smtp.gmail.com" -ForegroundColor White
Write-Host "  - MAIL_PORT: 587" -ForegroundColor White
Write-Host "  - MAIL_USERNAME: $emailUsuario" -ForegroundColor White
Write-Host "  - MAIL_PASSWORD: ********** (configurada)" -ForegroundColor White
Write-Host "  - MAIL_ENCRYPTION: tls" -ForegroundColor White
Write-Host "  - MAIL_FROM_ADDRESS: $emailUsuario" -ForegroundColor White
Write-Host ""
Write-Host "Limpiando cache de Laravel..." -ForegroundColor Yellow

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache

Write-Host ""
Write-Host "✅ Cache limpiada" -ForegroundColor Green
Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "CONFIGURACIÓN COMPLETADA" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Para probar el envío de correos:" -ForegroundColor Yellow
Write-Host "  1. Crea un usuario nuevo en /register" -ForegroundColor White
Write-Host "  2. O ejecuta: php artisan email:diagnose --send-test" -ForegroundColor White
Write-Host ""
