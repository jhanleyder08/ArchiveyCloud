import { ShieldX, ArrowLeft, Home } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface AccessDeniedProps {
    title?: string;
    message?: string;
    showBackButton?: boolean;
    showHomeButton?: boolean;
    backUrl?: string;
}

export default function AccessDenied({
    title = "Acceso Denegado",
    message = "No tienes permisos para realizar esta acción. Tu rol actual solo permite consultar información.",
    showBackButton = true,
    showHomeButton = true,
    backUrl,
}: AccessDeniedProps) {
    return (
        <div className="min-h-[60vh] flex items-center justify-center p-4">
            <div className="max-w-lg w-full text-center">
                {/* Icono grande */}
                <div className="mx-auto w-32 h-32 bg-red-100 rounded-full flex items-center justify-center mb-8 animate-pulse">
                    <ShieldX className="w-16 h-16 text-red-600" />
                </div>

                {/* Título */}
                <h1 className="text-4xl font-bold text-gray-900 mb-4">
                    {title}
                </h1>

                {/* Mensaje */}
                <p className="text-xl text-gray-600 mb-8">
                    {message}
                </p>

                {/* Información adicional */}
                <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-8">
                    <p className="text-amber-800 text-sm">
                        <strong>Rol actual:</strong> Consulta
                        <br />
                        <span className="text-amber-700">
                            Este rol solo permite ver información, no modificarla.
                            Si necesitas permisos adicionales, contacta al administrador.
                        </span>
                    </p>
                </div>

                {/* Botones */}
                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                    {showBackButton && (
                        <Button
                            variant="outline"
                            onClick={() => window.history.back()}
                            className="flex items-center gap-2"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            Volver Atrás
                        </Button>
                    )}
                    {showHomeButton && (
                        <Button asChild className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                            <Link href="/dashboard" className="flex items-center gap-2">
                                <Home className="w-4 h-4" />
                                Ir al Dashboard
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}
