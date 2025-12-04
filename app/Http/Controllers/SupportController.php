<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Mail\SupportTicket;

class SupportController extends Controller
{
    /**
     * Handle the support form submission.
     */
    public function store(Request $request)
    {
        // Validate the form data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000'
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no puede exceder los 255 caracteres.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede exceder los 255 caracteres.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede exceder los 2000 caracteres.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Send the support email
            Mail::to('cloudarchivey@gmail.com')->send(new SupportTicket([
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'submitted_at' => now()->format('Y-m-d H:i:s')
            ]));

            return redirect()->back()->with('success', 
                'Tu ticket de soporte ha sido enviado exitosamente. Nos pondremos en contacto contigo pronto.'
            );

        } catch (\Exception $e) {
            \Log::error('Error sending support email: ' . $e->getMessage());
            
            return redirect()->back()->with('error', 
                'Hubo un error al enviar tu ticket de soporte. Por favor intenta nuevamente.'
            );
        }
    }
}
