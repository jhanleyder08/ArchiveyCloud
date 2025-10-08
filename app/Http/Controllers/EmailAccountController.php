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

        return Inertia::render('Admin/EmailAccounts/Index', [
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
    public function update(Request $request, EmailAccount $account)
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

        $account->update($validated);

        return redirect()->back()->with('success', 'Cuenta actualizada correctamente');
    }

    /**
     * Eliminar cuenta
     */
    public function destroy(EmailAccount $account)
    {
        $account->delete();
        return redirect()->back()->with('success', 'Cuenta eliminada correctamente');
    }

    /**
     * Probar conexión
     */
    public function testConnection(EmailAccount $account)
    {
        try {
            $service = app(EmailCaptureService::class);
            $connection = $service->connect($account);
            $service->disconnect($connection);

            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Capturar manualmente
     */
    public function capture(Request $request, EmailAccount $account)
    {
        $async = $request->boolean('async', true);
        $limit = $request->integer('limit', 100);

        if ($async) {
            CaptureEmailsJob::dispatch($account, $limit)
                ->onQueue(config('email_capture.queue.queue_name'));

            return response()->json([
                'success' => true,
                'message' => 'Captura iniciada en segundo plano',
            ]);
        }

        // Captura síncrona
        $service = app(EmailCaptureService::class);
        $captured = $service->captureFromAccount($account, $limit);

        return response()->json([
            'success' => true,
            'count' => count($captured),
            'message' => "Capturados " . count($captured) . " emails",
        ]);
    }

    /**
     * Ver capturas de una cuenta
     */
    public function captures(EmailAccount $account)
    {
        $captures = $account->captures()
            ->with(['documento', 'attachments'])
            ->orderBy('email_date', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/EmailAccounts/Captures', [
            'account' => $account,
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
