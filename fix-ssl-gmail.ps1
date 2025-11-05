# Fix SSL Certificate Verification para Gmail

Write-Host ""
Write-Host "Solucionando problema de verificacion SSL..." -ForegroundColor Yellow
Write-Host ""

$envPath = ".env"
$content = Get-Content $envPath -Raw

# Verificar si ya existen las líneas
if ($content -notmatch "MAIL_VERIFY_PEER") {
    # Agregar al final del archivo
    Add-Content $envPath "`nMAIL_VERIFY_PEER=false"
    Write-Host "Agregada: MAIL_VERIFY_PEER=false" -ForegroundColor Green
} else {
    # Actualizar existente
    $content = $content -replace "(?m)^MAIL_VERIFY_PEER=.*$", "MAIL_VERIFY_PEER=false"
    Set-Content $envPath -Value $content -NoNewline
    Write-Host "Actualizada: MAIL_VERIFY_PEER=false" -ForegroundColor Green
}

$content = Get-Content $envPath -Raw

if ($content -notmatch "MAIL_VERIFY_PEER_NAME") {
    Add-Content $envPath "`nMAIL_VERIFY_PEER_NAME=false"
    Write-Host "Agregada: MAIL_VERIFY_PEER_NAME=false" -ForegroundColor Green
} else {
    $content = $content -replace "(?m)^MAIL_VERIFY_PEER_NAME=.*$", "MAIL_VERIFY_PEER_NAME=false"
    Set-Content $envPath -Value $content -NoNewline
    Write-Host "Actualizada: MAIL_VERIFY_PEER_NAME=false" -ForegroundColor Green
}

Write-Host ""
Write-Host "Limpiando cache..." -ForegroundColor Yellow

php artisan config:clear | Out-Null
php artisan cache:clear | Out-Null
php artisan config:cache | Out-Null

Write-Host ""
Write-Host "SOLUCION APLICADA" -ForegroundColor Green
Write-Host ""
Write-Host "Ahora intenta registrar un usuario nuevamente" -ForegroundColor Cyan
Write-Host ""
