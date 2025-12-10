import { Head, Link, router } from '@inertiajs/react';
import { ShieldX, ArrowLeft, Home, AlertTriangle, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';

interface Props {
    title: string;
    message: string;
    requiredPermissions: string[];
    userRole: string;
    isConsulta: boolean;
}

export default function AccessDenied({
    title,
    message,
    requiredPermissions,
    userRole,
    isConsulta,
}: Props) {
    // Función segura para volver atrás
    const handleGoBack = () => {
        // Verificar si hay historial disponible
        if (window.history.length > 1) {
            window.history.back();
        } else {
            // Si no hay historial, ir al dashboard
            router.visit('/dashboard');
        }
    };
    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Acceso Denegado', href: '#' },
        ]}>
            <Head title={title} />

            <div className="min-h-[70vh] flex items-center justify-center p-4">
                <div className="max-w-2xl w-full text-center">
                    {/* Icono animado */}
                    <div className="relative mx-auto w-40 h-40 mb-8">
                        <div className="absolute inset-0 bg-red-100 rounded-full animate-ping opacity-25"></div>
                        <div className="relative w-40 h-40 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center shadow-2xl">
                            <ShieldX className="w-20 h-20 text-white" />
                        </div>
                    </div>

                    {/* Título grande */}
                    <h1 className="text-5xl font-bold text-gray-900 mb-4">
                        {title}
                    </h1>

                    {/* Subtítulo */}
                    <p className="text-2xl text-gray-600 mb-8">
                        {message}
                    </p>

                    {/* Información del rol */}
                    <div className={`rounded-xl p-6 mb-8 ${isConsulta ? 'bg-amber-50 border-2 border-amber-300' : 'bg-red-50 border-2 border-red-200'}`}>
                        <div className="flex items-center justify-center gap-3 mb-4">
                            {isConsulta ? (
                                <Lock className="w-8 h-8 text-amber-600" />
                            ) : (
                                <AlertTriangle className="w-8 h-8 text-red-600" />
                            )}
                            <span className={`text-xl font-semibold ${isConsulta ? 'text-amber-800' : 'text-red-800'}`}>
                                Tu rol actual: <span className="bg-white px-3 py-1 rounded-lg">{userRole}</span>
                            </span>
                        </div>
                        
                        {isConsulta ? (
                            <div className="text-amber-700 space-y-2">
                                <p className="text-lg font-medium">
                                    El rol de Consulta tiene las siguientes restricciones:
                                </p>
                                <ul className="text-left max-w-md mx-auto space-y-1">
                                    <li className="flex items-center gap-2">
                                        <span className="text-red-500">✗</span> No puede crear nuevos registros
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="text-red-500">✗</span> No puede editar información existente
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="text-red-500">✗</span> No puede eliminar registros
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="text-green-500">✓</span> Puede ver y consultar información
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="text-green-500">✓</span> Puede realizar búsquedas básicas
                                    </li>
                                </ul>
                            </div>
                        ) : (
                            <p className="text-red-700">
                                No tienes los permisos necesarios para acceder a esta funcionalidad.
                                <br />
                                Contacta al administrador si necesitas acceso.
                            </p>
                        )}
                    </div>

                    {/* Permisos requeridos (solo para debug/admin) */}
                    {requiredPermissions.length > 0 && (
                        <div className="bg-gray-100 rounded-lg p-4 mb-8 text-sm text-gray-600">
                            <p className="font-medium mb-2">Permisos requeridos:</p>
                            <div className="flex flex-wrap gap-2 justify-center">
                                {requiredPermissions.map((perm, index) => (
                                    <code key={index} className="bg-gray-200 px-2 py-1 rounded text-xs">
                                        {perm}
                                    </code>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Botones de acción */}
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <Button
                            variant="outline"
                            size="lg"
                            onClick={handleGoBack}
                            className="flex items-center gap-2 text-lg px-8"
                        >
                            <ArrowLeft className="w-5 h-5" />
                            Volver Atrás
                        </Button>
                        <Button 
                            asChild 
                            size="lg"
                            className="bg-[#2a3d83] hover:bg-[#1e2b5f] text-lg px-8"
                        >
                            <Link href="/dashboard" className="flex items-center gap-2">
                                <Home className="w-5 h-5" />
                                Ir al Dashboard
                            </Link>
                        </Button>
                    </div>

                    {/* Mensaje de contacto */}
                    <p className="mt-8 text-gray-500 text-sm">
                        ¿Necesitas acceso? Contacta al administrador del sistema para solicitar los permisos necesarios.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
