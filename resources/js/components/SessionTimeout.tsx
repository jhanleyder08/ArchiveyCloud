import React, { useEffect, useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { AlertTriangle, Clock } from 'lucide-react';

interface SessionTimeoutProps {
    timeoutMinutes?: number;
    warningMinutes?: number;
}

export default function SessionTimeout({ 
    timeoutMinutes = 10, 
    warningMinutes = 2 
}: SessionTimeoutProps) {
    const [timeRemaining, setTimeRemaining] = useState(timeoutMinutes * 60);
    const [showWarning, setShowWarning] = useState(false);
    const [isActive, setIsActive] = useState(true);

    // Convertir segundos a minutos y segundos para mostrar
    const formatTime = (seconds: number) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    // Resetear el timer cuando hay actividad
    const resetTimer = useCallback(() => {
        setTimeRemaining(timeoutMinutes * 60);
        setShowWarning(false);
        setIsActive(true);
    }, [timeoutMinutes]);

    // Manejar logout automático
    const handleAutoLogout = useCallback(() => {
        // Prevenir múltiples ejecuciones
        if (!isActive) return;
        
        setIsActive(false); // Desactivar inmediatamente para evitar bucles
        
        // Intentar logout normal primero, pero si falla (419), redirigir directamente
        router.post('/logout', {}, {
            onSuccess: () => {
                window.location.href = '/login';
            },
            onError: (errors) => {
                // Si hay error 419 (CSRF token expired) o cualquier otro error,
                // redirigir directamente al login
                console.warn('Logout failed, redirecting to login:', errors);
                window.location.href = '/login';
            }
        });
    }, [isActive]);

    // Extender sesión
    const extendSession = useCallback(() => {
        if (!isActive) return; // No hacer nada si ya se desactivó
        
        // Hacer una petición para extender la sesión
        fetch('/extend-session', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        }).then(response => {
            if (response.ok) {
                resetTimer();
            } else if (response.status === 419 || response.status === 401) {
                // Token CSRF expirado o sesión expirada
                handleAutoLogout();
            } else {
                throw new Error('Session extension failed');
            }
        }).catch((error) => {
            console.warn('Session extension failed:', error);
            // Si falla, probablemente la sesión ya expiró
            handleAutoLogout();
        });
    }, [resetTimer, handleAutoLogout, isActive]);

    // Detectar actividad del usuario
    useEffect(() => {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        const resetOnActivity = () => {
            if (!isActive) return;
            resetTimer();
        };

        // Agregar listeners
        events.forEach(event => {
            document.addEventListener(event, resetOnActivity, true);
        });

        // Cleanup
        return () => {
            events.forEach(event => {
                document.removeEventListener(event, resetOnActivity, true);
            });
        };
    }, [resetTimer, isActive]);

    // Timer countdown
    useEffect(() => {
        if (!isActive) return; // No ejecutar timer si no está activo
        
        const interval = setInterval(() => {
            setTimeRemaining(prev => {
                if (!isActive) return prev; // Double check para evitar actualizaciones
                
                const newTime = prev - 1;
                
                // Mostrar advertencia cuando quedan 2 minutos
                if (newTime <= warningMinutes * 60 && newTime > 0 && !showWarning) {
                    setShowWarning(true);
                }
                
                // Auto logout cuando llega a 0
                if (newTime <= 0) {
                    handleAutoLogout();
                    return 0;
                }
                
                return newTime;
            });
        }, 1000);

        return () => clearInterval(interval);
    }, [warningMinutes, showWarning, handleAutoLogout, isActive]);

    // Interceptar respuestas 401 (sesión expirada)
    useEffect(() => {
        const handleUnauthorized = (event: any) => {
            if (event.detail?.response?.status === 401) {
                const data = event.detail.response.data;
                if (data?.session_expired) {
                    setIsActive(false);
                    handleAutoLogout();
                }
            }
        };

        document.addEventListener('inertia:error', handleUnauthorized);
        
        return () => {
            document.removeEventListener('inertia:error', handleUnauthorized);
        };
    }, [handleAutoLogout]);

    return (
        <>
            {/* Warning Dialog */}
            <Dialog open={showWarning} onOpenChange={() => {}}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-orange-600">
                            <AlertTriangle className="h-5 w-5" />
                            Sesión por Expirar
                        </DialogTitle>
                        <DialogDescription>
                            Tu sesión expirará por inactividad en:
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="flex flex-col items-center gap-4 py-4">
                        <div className="flex items-center gap-2 text-2xl font-bold text-red-600">
                            <Clock className="h-6 w-6" />
                            {formatTime(timeRemaining)}
                        </div>
                        
                        <p className="text-sm text-gray-600 text-center">
                            ¿Deseas continuar trabajando?
                        </p>
                        
                        <div className="flex gap-3 w-full">
                            <Button
                                onClick={extendSession}
                                className="flex-1 bg-[#2a3d83] hover:bg-[#1e2b5f]"
                            >
                                Continuar Sesión
                            </Button>
                            <Button
                                onClick={handleAutoLogout}
                                variant="outline"
                                className="flex-1"
                            >
                                Cerrar Sesión
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Indicador de tiempo restante (opcional, solo en desarrollo) */}
            {process.env.NODE_ENV === 'development' && (
                <div className="fixed bottom-4 right-4 bg-gray-800 text-white px-3 py-2 rounded-lg text-sm z-50">
                    Sesión: {formatTime(timeRemaining)}
                </div>
            )}
        </>
    );
}
