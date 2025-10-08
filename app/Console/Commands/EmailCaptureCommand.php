<?php

namespace App\Console\Commands;

use App\Services\EmailCaptureService;
use Illuminate\Console\Command;

class EmailCaptureCommand extends Command
{
    protected $signature = 'email:capture 
                            {--account= : ID de cuenta específica}
                            {--limit=100 : Número máximo de emails a capturar}';

    protected $description = 'Capturar emails de las cuentas configuradas';

    public function handle(EmailCaptureService $service): int
    {
        $this->info('🔄 Iniciando captura de emails...');

        try {
            if ($accountId = $this->option('account')) {
                $account = \App\Models\EmailAccount::findOrFail($accountId);
                $captured = $service->captureFromAccount($account, (int) $this->option('limit'));
                
                $this->info("✅ Capturados {count($captured)} emails de {$account->email}");
            } else {
                $results = $service->captureFromAllAccounts();
                
                $this->table(
                    ['Cuenta', 'Estado', 'Capturados/Error'],
                    collect($results)->map(fn($r, $email) => [
                        $email,
                        $r['success'] ? '✅ Éxito' : '❌ Error',
                        $r['success'] ? $r['count'] : $r['error']
                    ])->values()
                );
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
