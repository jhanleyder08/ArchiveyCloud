import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuLabel,
    DropdownMenuSeparator, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { 
    Bell, 
    BellRing,
    CheckCircle,
    Clock,
    AlertTriangle,
    Calendar,
    Archive,
    Settings,
    User,
    Eye
} from 'lucide-react';

interface NotificacionResumen {
    id: number;
    titulo: string;
    mensaje: string;
    icono: string;
    prioridad: string;
    color_prioridad: string;
    accion_url?: string;
    created_at: string;
    tipo: string;
}

interface NotificacionesData {
    notificaciones: NotificacionResumen[];
    total_no_leidas: number;
}

const iconMap: Record<string, React.ComponentType<any>> = {
    'calendar-x': Calendar,
    'calendar-warning': Calendar,
    'clock-x': Clock,
    'clock-warning': Clock,
    'archive': Archive,
    'check-circle': CheckCircle,
    'file-plus': Archive,
    'user-plus': User,
    'settings': Settings,
    'shield-alert': AlertTriangle,
    'bell': Bell,
};

const prioridadColors: Record<string, string> = {
    baja: 'text-blue-600 bg-blue-50 border-blue-200',
    media: 'text-yellow-600 bg-yellow-50 border-yellow-200',
    alta: 'text-orange-600 bg-orange-50 border-orange-200',
    critica: 'text-red-600 bg-red-50 border-red-200',
};

export default function NotificationsDropdown() {
    const [notificaciones, setNotificaciones] = useState<NotificacionesData>({
        notificaciones: [],
        total_no_leidas: 0
    });
    const [loading, setLoading] = useState(true);
    const [open, setOpen] = useState(false);

    const fetchNotificaciones = async () => {
        try {
            const response = await fetch(route('admin.notificaciones.no-leidas'), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            
            if (response.ok) {
                const data = await response.json();
                setNotificaciones(data);
            }
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
        } finally {
            setLoading(false);
        }
    };

    const marcarComoLeida = async (id: number, event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        
        try {
            const response = await fetch(route('admin.notificaciones.marcar-leida', id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            if (response.ok) {
                // Actualizar el estado local
                setNotificaciones(prev => ({
                    notificaciones: prev.notificaciones.filter(n => n.id !== id),
                    total_no_leidas: Math.max(0, prev.total_no_leidas - 1)
                }));
            }
        } catch (error) {
            console.error('Error al marcar como leída:', error);
        }
    };

    const marcarTodasLeidas = async () => {
        try {
            const response = await fetch(route('admin.notificaciones.marcar-todas-leidas'), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            if (response.ok) {
                setNotificaciones({
                    notificaciones: [],
                    total_no_leidas: 0
                });
            }
        } catch (error) {
            console.error('Error al marcar todas como leídas:', error);
        }
    };

    useEffect(() => {
        fetchNotificaciones();
        
        // Actualizar cada 30 segundos
        const interval = setInterval(fetchNotificaciones, 30000);
        
        return () => clearInterval(interval);
    }, []);

    const formatearTiempo = (fecha: string) => {
        const now = new Date();
        const notifDate = new Date(fecha);
        const diffMs = now.getTime() - notifDate.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) return 'Ahora mismo';
        if (diffMins < 60) return `Hace ${diffMins}m`;
        if (diffHours < 24) return `Hace ${diffHours}h`;
        if (diffDays < 7) return `Hace ${diffDays}d`;
        return notifDate.toLocaleDateString();
    };

    const getIconComponent = (iconName: string) => {
        return iconMap[iconName] || Bell;
    };

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="relative">
                    {notificaciones.total_no_leidas > 0 ? (
                        <BellRing className="h-5 w-5" />
                    ) : (
                        <Bell className="h-5 w-5" />
                    )}
                    {notificaciones.total_no_leidas > 0 && (
                        <Badge 
                            variant="destructive" 
                            className="absolute -top-2 -right-2 h-5 w-5 p-0 text-xs flex items-center justify-center min-w-[20px]"
                        >
                            {notificaciones.total_no_leidas > 99 ? '99+' : notificaciones.total_no_leidas}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>
            
            <DropdownMenuContent align="end" className="w-80 max-h-96">
                <div className="pb-2 px-2 pt-2">
                    <div className="flex items-center justify-between">
                        <DropdownMenuLabel className="text-base px-0">
                            Notificaciones
                        </DropdownMenuLabel>
                        <div className="flex items-center space-x-2">
                            {notificaciones.total_no_leidas > 0 && (
                                <Badge variant="secondary" className="text-xs">
                                    {notificaciones.total_no_leidas} nuevas
                                </Badge>
                            )}
                        </div>
                    </div>
                </div>

                <DropdownMenuSeparator />

                {loading ? (
                    <div className="p-4 text-center text-sm text-gray-500">
                        Cargando notificaciones...
                    </div>
                ) : notificaciones.notificaciones.length === 0 ? (
                    <div className="p-4 text-center">
                        <Bell className="h-8 w-8 text-gray-400 mx-auto mb-2" />
                        <p className="text-sm text-gray-500">
                            No tienes notificaciones nuevas
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="max-h-64 overflow-y-auto">
                            {notificaciones.notificaciones.map((notificacion) => {
                                const IconComponent = getIconComponent(notificacion.icono);
                                return (
                                    <DropdownMenuItem 
                                        key={notificacion.id} 
                                        className="p-3 cursor-pointer focus:bg-gray-50"
                                        asChild
                                    >
                                        <Link 
                                            href={notificacion.accion_url || route('admin.notificaciones.index')}
                                            className="block w-full"
                                        >
                                            <div className="flex items-start space-x-3">
                                                <div className={`flex-shrink-0 p-2 rounded-lg ${
                                                    notificacion.prioridad === 'critica' ? 'bg-red-100' :
                                                    notificacion.prioridad === 'alta' ? 'bg-orange-100' :
                                                    notificacion.prioridad === 'media' ? 'bg-yellow-100' :
                                                    'bg-blue-100'
                                                }`}>
                                                    <IconComponent className={`h-4 w-4 ${notificacion.color_prioridad}`} />
                                                </div>
                                                
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between mb-1">
                                                        <p className="text-sm font-medium text-gray-900 truncate">
                                                            {notificacion.titulo}
                                                        </p>
                                                        <button
                                                            onClick={(e) => marcarComoLeida(notificacion.id, e)}
                                                            className="flex-shrink-0 text-gray-400 hover:text-gray-600 ml-2"
                                                            title="Marcar como leída"
                                                        >
                                                            <CheckCircle className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                    
                                                    <p className="text-xs text-gray-600 line-clamp-2 mb-1">
                                                        {notificacion.mensaje}
                                                    </p>
                                                    
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-xs text-gray-500">
                                                            {formatearTiempo(notificacion.created_at)}
                                                        </span>
                                                        <Badge 
                                                            variant="outline" 
                                                            className={`text-xs ${prioridadColors[notificacion.prioridad]}`}
                                                        >
                                                            {notificacion.prioridad}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            </div>
                                        </Link>
                                    </DropdownMenuItem>
                                );
                            })}
                        </div>

                        <DropdownMenuSeparator />
                        
                        <div className="p-2 space-y-1">
                            {notificaciones.total_no_leidas > 0 && (
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    className="w-full justify-start"
                                    onClick={marcarTodasLeidas}
                                >
                                    <CheckCircle className="h-4 w-4 mr-2" />
                                    Marcar todas como leídas
                                </Button>
                            )}
                            
                            <Button 
                                variant="ghost" 
                                size="sm" 
                                className="w-full justify-start"
                                asChild
                            >
                                <Link href={route('admin.notificaciones.index')}>
                                    <Eye className="h-4 w-4 mr-2" />
                                    Ver todas las notificaciones
                                </Link>
                            </Button>
                        </div>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
