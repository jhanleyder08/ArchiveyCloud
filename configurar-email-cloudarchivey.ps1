# Configuración automática de Email para cloudarchivey@gmail.com

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "CONFIGURANDO EMAIL DE GMAIL" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

$emailUsuario = "cloudarchivey@gmail.com"
$password = "dkrnhloiknzfzohw"
$envPath = ".env"

# Verificar si existe .env
if (-not (Test-Path $envPath)) {
    Write-Host "Archivo .env no encontrado. Copiando desde .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    Write-Host "Archivo .env creado." -ForegroundColor Green
}

Write-Host "Actualizando configuracion de email en .env..." -ForegroundColor Yellow

# Leer contenido del .env
$content = Get-Content $envPath -Raw

# Reemplazar todas las configuraciones de email
$content = $content -replace "(?m)^MAIL_MAILER=.*$", "MAIL_MAILER=smtp"
$content = $content -replace "(?m)^MAIL_HOST=.*$", "MAIL_HOST=smtp.gmail.com"
$content = $content -replace "(?m)^MAIL_PORT=.*$", "MAIL_PORT=587"
$content = $content -replace "(?m)^MAIL_USERNAME=.*$", "MAIL_USERNAME=$emailUsuario"
$content = $content -replace "(?m)^MAIL_PASSWORD=.*$", "MAIL_PASSWORD=$password"
$content = $content -replace "(?m)^MAIL_ENCRYPTION=.*$", "MAIL_ENCRYPTION=tls"
$content = $content -replace "(?m)^MAIL_FROM_ADDRESS=.*$", "MAIL_FROM_ADDRESS=$emailUsuario"
$content = $content -replace '(?m)^MAIL_FROM_NAME=.*$', 'MAIL_FROM_NAME="Archivey Cloud SGDEA"'

# Guardar cambios
$content | Set-Content $envPath -NoNewline

Write-Host ""
Write-Host "Configuracion actualizada:" -ForegroundColor Green
Write-Host "  MAIL_MAILER: smtp" -ForegroundColor White
Write-Host "  MAIL_HOST: smtp.gmail.com" -ForegroundColor White
Write-Host "  MAIL_PORT: 587" -ForegroundColor White
Write-Host "  MAIL_USERNAME: $emailUsuario" -ForegroundColor White
Write-Host "  MAIL_PASSWORD: ********** (configurada)" -ForegroundColor White
Write-Host "  MAIL_ENCRYPTION: tls" -ForegroundColor White
Write-Host "  MAIL_FROM_ADDRESS: $emailUsuario" -ForegroundColor White
Write-Host ""

Write-Host "Limpiando cache de Laravel..." -ForegroundColor Yellow
Write-Host ""

# Ejecutar comandos de Laravel
& php artisan config:clear 2>&1 | Out-Null
Write-Host "  Cache de configuracion limpiada" -ForegroundColor Green

& php artisan cache:clear 2>&1 | Out-Null
Write-Host "  Cache general limpiada" -ForegroundColor Green

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "CONFIGURACION COMPLETADA" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "El correo de verificacion ahora se enviara desde:" -ForegroundColor White
Write-Host "  $emailUsuario" -ForegroundColor Cyan
Write-Host ""
Write-Host "Para probar:" -ForegroundColor Yellow
Write-Host "  1. Ve a /register y crea un usuario de prueba" -ForegroundColor White
Write-Host "  2. Revisa tu bandeja de Gmail" -ForegroundColor White
Write-Host "  3. O ejecuta: php artisan email:diagnose" -ForegroundColor White
Write-Host ""
