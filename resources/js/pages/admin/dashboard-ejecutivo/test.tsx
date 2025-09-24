import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface Props {
    mensaje?: string;
    usuario?: string;
    timestamp?: string;
}

export default function DashboardEjecutivoTest({ mensaje, usuario, timestamp }: Props) {
    return (
        <AppLayout>
            <Head title="Dashboard Ejecutivo - Test" />
            
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h1 className="text-3xl font-bold text-green-600 mb-4">
                                🎉 ¡Dashboard Ejecutivo Funcionando!
                            </h1>
                            <p className="text-lg mb-4">
                                Si puedes ver este mensaje, significa que:
                            </p>
                            <ul className="list-disc list-inside space-y-2">
                                <li>✅ La navegación está funcionando correctamente</li>
                                <li>✅ El controlador está respondiendo</li>
                                <li>✅ La vista React se está renderizando</li>
                                <li>✅ Los permisos están configurados correctamente</li>
                            </ul>
                            
                            {mensaje && (
                                <div className="mt-6 p-4 bg-green-50 rounded-lg">
                                    <h3 className="text-lg font-semibold text-green-800 mb-2">Datos del Controlador:</h3>
                                    <p className="text-green-700"><strong>Mensaje:</strong> {mensaje}</p>
                                    {usuario && <p className="text-green-700"><strong>Usuario:</strong> {usuario}</p>}
                                    {timestamp && <p className="text-green-700"><strong>Timestamp:</strong> {timestamp}</p>}
                                </div>
                            )}
                            
                            <div className="mt-8 p-4 bg-blue-50 rounded-lg">
                                <h2 className="text-xl font-semibold text-blue-800 mb-2">Próximo paso:</h2>
                                <p className="text-blue-700">
                                    Ahora podemos restaurar la vista completa con todos los gráficos y métricas.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
