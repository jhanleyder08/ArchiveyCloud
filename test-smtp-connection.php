<?php

$host = 'smtp.gmail.com';
$port = 587;
$username = 'cloudarchivey@gmail.com';
$password = 'xapytyvldjciomrb';

echo "=== Prueba de conexión SMTP ===\n\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Username: $username\n";
echo "Encryption: TLS\n\n";

// Intentar conexión básica
$socket = @fsockopen($host, $port, $errno, $errstr, 30);

if (!$socket) {
    echo "❌ Error de conexión: $errstr ($errno)\n";
    exit(1);
}

echo "✅ Conexión TCP exitosa\n";

// Leer respuesta inicial
$response = fgets($socket);
echo "Respuesta del servidor: $response";

// Enviar EHLO
fputs($socket, "EHLO localhost\r\n");
$response = '';
while ($line = fgets($socket)) {
    $response .= $line;
    if (preg_match('/^\d{3} /', $line)) break;
}
echo "\nRespuesta EHLO:\n$response\n";

// Intentar STARTTLS
fputs($socket, "STARTTLS\r\n");
$response = fgets($socket);
echo "Respuesta STARTTLS: $response\n";

// Configurar opciones SSL antes de habilitar TLS
stream_context_set_option($socket, 'ssl', 'verify_peer', false);
stream_context_set_option($socket, 'ssl', 'verify_peer_name', false);
stream_context_set_option($socket, 'ssl', 'allow_self_signed', true);

// Intentar habilitar encriptación TLS
echo "\nIntentando habilitar TLS...\n";
$result = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

if ($result === true) {
    echo "✅ TLS habilitado exitosamente\n";
    
    // Intentar autenticación
    fputs($socket, "EHLO localhost\r\n");
    $response = '';
    while ($line = fgets($socket)) {
        $response .= $line;
        if (preg_match('/^\d{3} /', $line)) break;
    }
    echo "\nRespuesta EHLO después de TLS:\n$response\n";
    
} else {
    echo "❌ Error al habilitar TLS\n";
    echo "Resultado: " . var_export($result, true) . "\n";
    $error = error_get_last();
    echo "Último error: " . print_r($error, true) . "\n";
}

fclose($socket);
