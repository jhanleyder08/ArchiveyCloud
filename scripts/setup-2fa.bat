@echo off
echo ================================
echo  Setup 2FA - ArchiveyCloud
echo ================================
echo.

echo [1/3] Verificando paquetes...
composer show pragmarx/google2fa-qrcode >nul 2>&1
if %errorlevel% neq 0 (
    echo Instalando pragmarx/google2fa-qrcode...
    composer require pragmarx/google2fa-qrcode
) else (
    echo - Paquete ya instalado ✓
)
echo.

echo [2/3] Ejecutando migraciones...
php artisan migrate --force
echo.

echo [3/3] Limpiando cache...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
echo.

echo ================================
echo  Instalacion completada!
echo ================================
echo.
echo Proximos pasos:
echo 1. Configura SMTP en .env para metodo Email
echo 2. Configura Twilio en .env para metodo SMS (opcional)
echo 3. Accede a /two-factor/settings para probar
echo.
echo Documentacion: docs/TWO_FACTOR_AUTHENTICATION.md
echo Inicio rapido: docs/QUICK_START_2FA.md
echo.
pause
