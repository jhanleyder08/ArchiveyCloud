import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function TestExpedientes() {
    return (
        <AppLayout>
            <Head title="Test Expedientes" />
            <div className="container mx-auto py-6">
                <h1 className="text-2xl font-bold mb-4">🎉 ¡EXPEDIENTES FUNCIONA!</h1>
                <div className="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    ✅ La navegación de expedientes está funcionando correctamente
                </div>
                
                <div className="mt-6">
                    <h2 className="text-lg font-semibold mb-2">Pruebas de navegación:</h2>
                    <div className="space-y-2">
                        <Link 
                            href="/dashboard" 
                            className="block p-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                        >
                            → Ir al Dashboard
                        </Link>
                        <Link 
                            href="/admin/documentos" 
                            className="block p-2 bg-purple-100 text-purple-700 rounded hover:bg-purple-200"
                        >
                            → Ir a Documentos
                        </Link>
                        <button 
                            onClick={() => window.location.href = '/admin/expedientes'}
                            className="block w-full text-left p-2 bg-orange-100 text-orange-700 rounded hover:bg-orange-200"
                        >
                            → Recargar con window.location (backup)
                        </button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
