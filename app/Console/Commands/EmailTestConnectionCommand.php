<?php

namespace App\Console\Commands;

use App\Services\EmailCaptureService;
use Illuminate\Console\Command;

class EmailTestConnectionCommand extends Command
{
    protected $signature = 'email:test-connection 
                            {--account= : ID de cuenta a probar}';

    protected $description = 'Probar conexión IMAP a una cuenta de email';

    public function handle(EmailCaptureService $service): int
    {
        try {
            $accountId = $this->option('account');
            
            if (!$accountId) {
                $this->error('Debe especificar el ID de la cuenta con --account=ID');
                return Command::FAILURE;
            }

            $account = \App\Models\EmailAccount::findOrFail($accountId);
            
            $this->info("🔄 Probando conexión a {$account->email}...");
            
            $connection = $service->connect($account);
            if ($connection) {
                imap_close($connection);
                $this->info("✅ Conexión exitosa");
                return Command::SUCCESS;
            }
            
            $this->error("❌ No se pudo establecer la conexión");
            return Command::FAILURE;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
