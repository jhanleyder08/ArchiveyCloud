import { useEffect, useState } from 'react';
import { ShieldX, X, Home, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface AccessDeniedModalProps {
    // Props opcionales para personalización
}

export function AccessDeniedModal({}: AccessDeniedModalProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [message, setMessage] = useState<string>('');

    useEffect(() => {
        // Registrar el handler global en window
        const handler = (msg?: string) => {
            setMessage(msg || 'No tienes permisos para realizar esta acción. Tu rol actual solo permite consultar información.');
            setIsOpen(true);
        };

        // Registrar en window para que app.tsx pueda usarlo
        window.__accessDeniedHandler = handler;

        // Cleanup
        return () => {
            window.__accessDeniedHandler = null;
        };
    }, []);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center">
            {/* Overlay oscuro */}
            <div 
                className="absolute inset-0 bg-black/60 backdrop-blur-sm"
                onClick={() => setIsOpen(false)}
            />
            
            {/* Modal */}
            <div className="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden animate-in zoom-in-95 duration-200">
                {/* Botón cerrar */}
                <button
                    onClick={() => setIsOpen(false)}
                    className="absolute top-4 right-4 p-2 rounded-full hover:bg-gray-100 transition-colors z-10"
                >
                    <X className="w-5 h-5 text-gray-500" />
                </button>

                {/* Header con icono */}
                <div className="bg-gradient-to-br from-red-500 to-red-600 p-8 text-center">
                    <div className="mx-auto w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-4 animate-pulse">
                        <ShieldX className="w-12 h-12 text-white" />
                    </div>
                    <h2 className="text-3xl font-bold text-white">
                        Acceso Denegado
                    </h2>
                </div>

                {/* Contenido */}
                <div className="p-6 text-center">
                    <p className="text-lg text-gray-700 mb-6">
                        {message}
                    </p>

                    {/* Información del rol */}
                    <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                        <p className="text-amber-800 text-sm">
                            <strong>Rol de Consulta:</strong> Solo permite ver información, no modificarla.
                            <br />
                            <span className="text-amber-700">
                                Contacta al administrador si necesitas permisos adicionales.
                            </span>
                        </p>
                    </div>

                    {/* Botones */}
                    <div className="flex flex-col sm:flex-row gap-3 justify-center">
                        <Button
                            variant="outline"
                            onClick={() => setIsOpen(false)}
                            className="flex items-center gap-2"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            Cerrar
                        </Button>
                        <Button 
                            asChild 
                            className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                            onClick={() => setIsOpen(false)}
                        >
                            <Link href="/dashboard" className="flex items-center gap-2">
                                <Home className="w-4 h-4" />
                                Ir al Dashboard
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default AccessDeniedModal;
