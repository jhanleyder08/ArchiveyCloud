<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Configuración de Mail ===\n\n";
echo "MAIL_MAILER: " . env('MAIL_MAILER', 'no configurado') . "\n";
echo "MAIL_HOST: " . env('MAIL_HOST', 'no configurado') . "\n";
echo "MAIL_PORT: " . env('MAIL_PORT', 'no configurado') . "\n";
echo "MAIL_ENCRYPTION: " . env('MAIL_ENCRYPTION', 'no configurado') . "\n";
echo "MAIL_VERIFY_PEER: " . env('MAIL_VERIFY_PEER', 'no configurado') . "\n";
echo "MAIL_VERIFY_PEER_NAME: " . env('MAIL_VERIFY_PEER_NAME', 'no configurado') . "\n\n";

echo "=== Configuración de config('mail') ===\n\n";
echo "verify_peer: " . (config('mail.mailers.smtp.stream.ssl.verify_peer') ? 'true' : 'false') . "\n";
echo "verify_peer_name: " . (config('mail.mailers.smtp.stream.ssl.verify_peer_name') ? 'true' : 'false') . "\n";
echo "allow_self_signed: " . (config('mail.mailers.smtp.stream.ssl.allow_self_signed') ? 'true' : 'false') . "\n";
