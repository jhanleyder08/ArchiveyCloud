<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Services\EmailCaptureService;
use App\Jobs\CaptureEmailsJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailAccountController extends Controller
{
    /**
     * Listar cuentas de email
     */
    public function index()
    {
        $accounts = EmailAccount::with('serieDocumental')
            ->withCount('captures')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/EmailAccounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Crear cuenta
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:email_accounts',
            'password' => 'required|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'encryption' => 'required|in:ssl,tls,none',
            'protocol' => 'required|in:imap,pop3',
            'auto_capture' => 'boolean',
            'folders' => 'nullable|array',
            'filters' => 'nullable|array',
            'serie_documental_id' => 'nullable|exists:series_documentales,id',
        ]);

        $account = EmailAccount::create($validated);

        return redirect()->back()->with('success', 'Cuenta de email creada correctamente');
    }

    /**
     * Actualizar cuenta
     */
    public function update(Request $request, EmailAccount $emailAccount)
    {
        $validated = $request->validate([
            'nombre' => 'string|max:255',
            'password' => 'nullable|string',
            'host' => 'string',
            'port' => 'integer',
            'encryption' => 'in:ssl,tls,none',
            'protocol' => 'in:imap,pop3',
            'auto_capture' => 'boolean',
            'folders' => 'nullable|array',
            'filters' => 'nullable|array',
            'serie_documental_id' => 'nullable|exists:series_documentales,id',
            'active' => 'boolean',
        ]);

        // Si no se proporciona password, no actualizar
        if (!isset($validated['password'])) {
            unset($validated['password']);
        }

        $emailAccount->update($validated);

        return redirect()->back()->with('success', 'Cuenta actualizada correctamente');
    }

    /**
     * Eliminar cuenta
     */
    public function destroy(EmailAccount $emailAccount)
    {
        $emailAccount->delete();
        return redirect()->back()->with('success', 'Cuenta eliminada correctamente');
    }

    /**
     * Probar conexión
     */
    public function testConnection(EmailAccount $emailAccount)
    {
        // Ejecutar test usando CLI PHP que tiene IMAP
        $command = sprintf(
            'php %s/artisan email:test-connection --account=%d 2>&1',
            base_path(),
            $emailAccount->id
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa',
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => implode("\n", $output) ?: 'Error al probar la conexión',
        ], 400);
    }

    /**
     * Capturar manualmente
     */
    public function capture(Request $request, EmailAccount $emailAccount)
    {
        $limit = $request->integer('limit', 100);

        // Ejecutar comando artisan con el PHP del sistema que sí tiene IMAP
        $command = sprintf(
            'php %s/artisan email:capture --account=%d --limit=%d > /dev/null 2>&1 &',
            base_path(),
            $emailAccount->id,
            $limit
        );
        
        exec($command);

        return response()->json([
            'success' => true,
            'message' => 'Captura iniciada en segundo plano',
        ]);
    }

    /**
     * Ver capturas de una cuenta
     */
    public function captures(EmailAccount $emailAccount)
    {
        $captures = $emailAccount->captures()
            ->with(['documento', 'attachments'])
            ->orderBy('email_date', 'desc')
            ->paginate(20);

        return Inertia::render('admin/EmailAccounts/Captures', [
            'account' => $emailAccount,
            'captures' => $captures,
        ]);
    }

    /**
     * Capturar de todas las cuentas
     */
    public function captureAll()
    {
        CaptureEmailsJob::dispatch(null, 100)
            ->onQueue(config('email_capture.queue.queue_name'));

        return response()->json([
            'success' => true,
            'message' => 'Captura masiva iniciada',
        ]);
    }
}
