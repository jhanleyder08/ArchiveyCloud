# Script Simple para configurar Email en .env
# EDITA ESTA LÍNEA CON TU EMAIL:
$emailUsuario = "TU_EMAIL@gmail.com"  # <-- CAMBIA ESTO

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "CONFIGURANDO EMAIL" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan

if ($emailUsuario -eq "TU_EMAIL@gmail.com") {
    Write-Host ""
    Write-Host "ERROR: Debes editar el archivo y cambiar TU_EMAIL@gmail.com por tu email real" -ForegroundColor Red
    Write-Host ""
    Write-Host "1. Abre: configurar-email-simple.ps1" -ForegroundColor Yellow
    Write-Host "2. Cambia la linea 3: " -ForegroundColor Yellow
    Write-Host '   $emailUsuario = "TU_EMAIL@gmail.com"' -ForegroundColor White
    Write-Host "   por tu email real de Gmail" -ForegroundColor Yellow
    Write-Host "3. Guarda y ejecuta de nuevo" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

$envPath = ".env"

# Verificar si existe .env
if (-not (Test-Path $envPath)) {
    Write-Host "Copiando .env.example a .env..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
}

Write-Host "Actualizando configuracion de email..." -ForegroundColor Yellow

# Leer contenido del .env
$content = Get-Content $envPath -Raw

# Reemplazar configuraciones
$content = $content -replace "MAIL_MAILER=.*", "MAIL_MAILER=smtp"
$content = $content -replace "MAIL_HOST=.*", "MAIL_HOST=smtp.gmail.com"
$content = $content -replace "MAIL_PORT=.*", "MAIL_PORT=587"
$content = $content -replace "MAIL_USERNAME=.*", "MAIL_USERNAME=$emailUsuario"
$content = $content -replace "MAIL_PASSWORD=.*", "MAIL_PASSWORD=dkrnhloiknzfzohw"
$content = $content -replace "MAIL_ENCRYPTION=.*", "MAIL_ENCRYPTION=tls"
$content = $content -replace "MAIL_FROM_ADDRESS=.*", "MAIL_FROM_ADDRESS=$emailUsuario"
$content = $content -replace 'MAIL_FROM_NAME=.*', 'MAIL_FROM_NAME="Archivey Cloud SGDEA"'

# Guardar
Set-Content $envPath -Value $content -NoNewline

Write-Host ""
Write-Host "Configuracion aplicada:" -ForegroundColor Cyan
Write-Host "  EMAIL: $emailUsuario" -ForegroundColor Green
Write-Host ""
Write-Host "Limpiando cache..." -ForegroundColor Yellow

php artisan config:clear
php artisan cache:clear

Write-Host ""
Write-Host "LISTO! Ahora puedes probar el registro" -ForegroundColor Green
Write-Host ""
