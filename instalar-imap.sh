#!/bin/bash

echo "🔍 Verificando instalación de PHP IMAP..."
echo ""

# Detectar si es XAMPP o instalación nativa
if [ -d "/opt/lampp" ]; then
    echo "✅ XAMPP detectado"
    echo ""
    echo "Para habilitar IMAP en XAMPP:"
    echo "1. Edita el archivo: /opt/lampp/etc/php.ini"
    echo "2. Busca la línea: ;extension=imap"
    echo "3. Quita el punto y coma al inicio: extension=imap"
    echo "4. Reinicia XAMPP: sudo /opt/lampp/lampp restart"
    echo ""
    echo "¿Quieres que lo haga automáticamente? (s/n)"
    read -r respuesta
    
    if [ "$respuesta" = "s" ] || [ "$respuesta" = "S" ]; then
        echo "Habilitando IMAP en php.ini..."
        sudo sed -i 's/;extension=imap/extension=imap/' /opt/lampp/etc/php.ini
        echo "✅ IMAP habilitado"
        echo ""
        echo "Reiniciando XAMPP..."
        sudo /opt/lampp/lampp restart
        echo ""
        echo "✅ Listo! Verifica con: php -m | grep imap"
    fi
else
    echo "📦 Instalación nativa de PHP detectada"
    echo ""
    echo "Instalando php8.3-imap..."
    sudo apt update
    sudo apt install -y php8.3-imap
    
    echo ""
    echo "Reiniciando servicios..."
    if systemctl is-active --quiet apache2; then
        sudo systemctl restart apache2
        echo "✅ Apache reiniciado"
    fi
    
    if systemctl is-active --quiet php8.3-fpm; then
        sudo systemctl restart php8.3-fpm
        echo "✅ PHP-FPM reiniciado"
    fi
    
    echo ""
    echo "✅ Instalación completada"
fi

echo ""
echo "Verificando instalación..."
if php -m | grep -i imap > /dev/null; then
    echo "✅ IMAP está instalado y habilitado"
else
    echo "❌ IMAP aún no está disponible"
    echo "   Verifica manualmente el php.ini o reinicia los servicios"
fi

echo ""
echo "Para verificar manualmente: php -m | grep imap"
