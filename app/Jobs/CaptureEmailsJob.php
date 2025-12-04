<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Services\EmailCaptureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CaptureEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    protected ?EmailAccount $account;
    protected int $limit;

    public function __construct(?EmailAccount $account = null, int $limit = 100)
    {
        $this->account = $account;
        $this->limit = $limit;
    }

    public function handle(EmailCaptureService $service): void
    {
        try {
            if ($this->account) {
                $captured = $service->captureFromAccount($this->account, $this->limit);
                Log::info("Capturados {count($captured)} emails de {$this->account->email}");
            } else {
                $results = $service->captureFromAllAccounts();
                Log::info('Captura masiva completada', $results);
            }
        } catch (\Exception $e) {
            Log::error('Error en captura de emails: ' . $e->getMessage());
            throw $e;
        }
    }
}
