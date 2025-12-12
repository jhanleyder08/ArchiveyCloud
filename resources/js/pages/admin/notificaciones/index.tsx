import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { 
    Bell,
    BellRing,
    Search,
    Filter,
    Eye,
    CheckCircle,
    Clock,
    AlertTriangle,
    XCircle,
    Archive,
    Trash2,
    Calendar,
    User,
    BarChart3,
    Settings,
    RefreshCw,
    ChevronLeft,
    ChevronRight,
    Loader2,
    X
} from 'lucide-react';

interface Notificacion {
    id: number;
    tipo: string;
    titulo: string;
    mensaje: string;
    prioridad: string;
    estado: string;
    accion_url?: string;
    leida_en?: string;
    created_at: string;
    icono: string;
    color_prioridad: string;
    creado_por?: {
        id: number;
        name: string;
    };
}

interface Estadisticas {
    total: number;
    pendientes: number;
    leidas: number;
    criticas: number;
}

interface Props {
    notificaciones: {
        data: Notificacion[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links?: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    estadisticas: Estadisticas;
    filtros: {
        estado?: string;
        tipo?: string;
        prioridad?: string;
    };
}

const prioridadColors: Record<string, string> = {
    baja: 'bg-blue-100 text-blue-800 border-blue-200',
    media: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    alta: 'bg-orange-100 text-orange-800 border-orange-200',
    critica: 'bg-red-100 text-red-800 border-red-200',
};

const estadoColors: Record<string, string> = {
    pendiente: 'bg-gray-100 text-gray-800 border-gray-200',
    leida: 'bg-green-100 text-green-800 border-green-200',
    archivada: 'bg-gray-100 text-gray-600 border-gray-200',
};

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

export default function NotificacionesIndex({ notificaciones, estadisticas, filtros }: Props) {
    const [filtrosForm, setFiltrosForm] = useState({
        estado: filtros.estado || 'todos',
        tipo: filtros.tipo || 'todos',
        prioridad: filtros.prioridad || 'todos',
    });

    const aplicarFiltros = () => {
        const filtrosLimpios = {
            estado: filtrosForm.estado === 'todos' ? '' : filtrosForm.estado,
            tipo: filtrosForm.tipo === 'todos' ? '' : filtrosForm.tipo,
            prioridad: filtrosForm.prioridad === 'todos' ? '' : filtrosForm.prioridad,
        };
        
        router.get('/admin/notificaciones', filtrosLimpios, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const limpiarFiltros = () => {
        setFiltrosForm({
            estado: 'todos',
            tipo: 'todos',
            prioridad: 'todos',
        });
        router.visit('/admin/notificaciones');
    };

    const [isLoading, setIsLoading] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);
    const [notificacionSeleccionada, setNotificacionSeleccionada] = useState<Notificacion | null>(null);

    const verDetalleNotificacion = (notificacion: Notificacion) => {
        if (notificacion.estado === 'pendiente') {
            marcarComoLeida(notificacion.id);
        }
        setNotificacionSeleccionada(notificacion);
        setModalOpen(true);
    };

    const marcarComoLeida = async (id: number) => {
        setIsLoading(true);
        try {
            await fetch(`/admin/notificaciones/${id}/marcar-leida`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            router.reload();
        } catch (error) {
            console.error('Error al marcar como leída:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const marcarTodasLeidas = async () => {
        setIsLoading(true);
        try {
            await fetch('/admin/notificaciones/marcar-todas-leidas', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            router.reload();
        } catch (error) {
            console.error('Error al marcar todas como leídas:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const archivarNotificacion = async (id: number) => {
        setIsLoading(true);
        try {
            await fetch(`/admin/notificaciones/${id}/archivar`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            router.reload();
        } catch (error) {
            console.error('Error al archivar:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const eliminarNotificacion = (id: number) => {
        if (!confirm('¿Estás seguro de eliminar esta notificación?')) {
            return;
        }
        router.delete(`/admin/notificaciones/${id}`);
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getIconComponent = (iconName: string) => {
        const IconComponent = iconMap[iconName] || Bell;
        return IconComponent;
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Administración', href: '#' },
            { title: 'Notificaciones', href: '/admin/notificaciones' },
        ]}>
            <Head title="Notificaciones" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <Bell className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Centro de Notificaciones
                        </h1>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        {estadisticas.pendientes > 0 && (
                            <Button onClick={marcarTodasLeidas} variant="outline">
                                <CheckCircle className="h-4 w-4 mr-2 text-[#2a3d83]" />
                                Marcar todas como leídas
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href="/admin/notificaciones/admin">
                                <Settings className="h-4 w-4 mr-2 text-[#2a3d83]" />
                                Panel Admin
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total</p>
                                <p className="text-2xl font-semibold text-gray-900">{estadisticas.total}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Bell className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Pendientes</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.pendientes}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <BellRing className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Leídas</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.leidas}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <CheckCircle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Críticas</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.criticas}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <AlertTriangle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-lg border p-6">
                    <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="relative flex-1 sm:w-80">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Buscar notificaciones..."
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={filtrosForm.estado} onValueChange={(value) => setFiltrosForm({...filtrosForm, estado: value})}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="todos">Todos los estados</SelectItem>
                                    <SelectItem value="pendiente">Pendientes</SelectItem>
                                    <SelectItem value="leida">Leídas</SelectItem>
                                    <SelectItem value="archivada">Archivadas</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={filtrosForm.prioridad} onValueChange={(value) => setFiltrosForm({...filtrosForm, prioridad: value})}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todas las prioridades" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="todos">Todas las prioridades</SelectItem>
                                    <SelectItem value="baja">Baja</SelectItem>
                                    <SelectItem value="media">Media</SelectItem>
                                    <SelectItem value="alta">Alta</SelectItem>
                                    <SelectItem value="critica">Crítica</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={aplicarFiltros} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                <Search className="h-4 w-4 mr-2" />
                                Aplicar Filtros
                            </Button>
                            <Button variant="outline" onClick={limpiarFiltros}>
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Tabla de Notificaciones */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900"></th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Notificación</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Prioridad</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Estado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Fecha</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {notificaciones.data.length > 0 ? (
                                    notificaciones.data.map((notificacion) => {
                                        const IconComponent = getIconComponent(notificacion.icono);
                                        return (
                                            <tr 
                                                key={notificacion.id} 
                                                className={`hover:bg-gray-50 transition-colors ${notificacion.estado === 'pendiente' ? 'bg-blue-50' : ''}`}
                                            >
                                                <td className="py-4 px-6">
                                                    <div className="p-2 rounded-lg bg-blue-100">
                                                        <IconComponent className="h-4 w-4 text-[#2a3d83]" />
                                                    </div>
                                                </td>
                                                <td className="py-4 px-6">
                                                    <div>
                                                        <p className="font-medium text-gray-900">{notificacion.titulo}</p>
                                                        <p className="text-sm text-gray-500 mt-1">{notificacion.mensaje}</p>
                                                        {notificacion.creado_por && (
                                                            <p className="text-xs text-gray-400 mt-1">
                                                                Por: {notificacion.creado_por.name}
                                                            </p>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-6">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${prioridadColors[notificacion.prioridad]}`}>
                                                        {notificacion.prioridad.charAt(0).toUpperCase() + notificacion.prioridad.slice(1)}
                                                    </span>
                                                </td>
                                                <td className="py-4 px-6">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${estadoColors[notificacion.estado]}`}>
                                                        {notificacion.estado === 'pendiente' ? 'Pendiente' :
                                                         notificacion.estado === 'leida' ? 'Leída' : 'Archivada'}
                                                    </span>
                                                </td>
                                                <td className="py-4 px-6">
                                                    <div className="text-sm text-gray-600">
                                                        {formatearFecha(notificacion.created_at)}
                                                        {notificacion.leida_en && (
                                                            <p className="text-xs text-gray-500">
                                                                Leída: {formatearFecha(notificacion.leida_en)}
                                                            </p>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-6">
                                                    <div className="flex items-center gap-2">
                                                        {notificacion.accion_url ? (
                                                            <button 
                                                                onClick={() => {
                                                                    if (notificacion.estado === 'pendiente') {
                                                                        marcarComoLeida(notificacion.id);
                                                                    }
                                                                    router.visit(notificacion.accion_url!);
                                                                }}
                                                                className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                title="Ver detalle"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </button>
                                                        ) : (
                                                            <button 
                                                                onClick={() => verDetalleNotificacion(notificacion)}
                                                                className="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors"
                                                                title="Ver mensaje"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </button>
                                                        )}
                                                        {notificacion.estado === 'pendiente' && (
                                                            <button 
                                                                onClick={() => marcarComoLeida(notificacion.id)}
                                                                className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                title="Marcar como leída"
                                                            >
                                                                <CheckCircle className="h-4 w-4" />
                                                            </button>
                                                        )}
                                                        {notificacion.estado !== 'archivada' && (
                                                            <button 
                                                                onClick={() => archivarNotificacion(notificacion.id)}
                                                                className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                                title="Archivar"
                                                            >
                                                                <Archive className="h-4 w-4" />
                                                            </button>
                                                        )}
                                                        <button 
                                                            onClick={() => eliminarNotificacion(notificacion.id)}
                                                            className="p-2 rounded-md text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors"
                                                            title="Eliminar"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="py-8 px-6 text-center text-gray-500">
                                            <Bell className="h-12 w-12 text-[#2a3d83] mx-auto mb-4" />
                                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                                No hay notificaciones
                                            </h3>
                                            <p className="text-gray-500">
                                                No tienes notificaciones que coincidan con los criterios seleccionados.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginación */}
                    {notificaciones.last_page > 1 && (
                        <div className="flex items-center justify-between mt-4 pt-4 border-t px-6 pb-4">
                            <p className="text-sm text-gray-600">
                                Mostrando {((notificaciones.current_page - 1) * notificaciones.per_page) + 1} a{' '}
                                {Math.min(notificaciones.current_page * notificaciones.per_page, notificaciones.total)} de{' '}
                                {notificaciones.total} notificaciones
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.visit(`/admin/notificaciones?page=${notificaciones.current_page - 1}`)}
                                    disabled={notificaciones.current_page === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Anterior
                                </Button>
                                <span className="text-sm text-gray-600">
                                    Página {notificaciones.current_page} de {notificaciones.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.visit(`/admin/notificaciones?page=${notificaciones.current_page + 1}`)}
                                    disabled={notificaciones.current_page === notificaciones.last_page}
                                >
                                    Siguiente
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Loading overlay */}
                {isLoading && (
                    <div className="fixed inset-0 bg-black/20 flex items-center justify-center z-50">
                        <div className="bg-white p-4 rounded-lg shadow-lg flex items-center gap-3">
                            <Loader2 className="h-5 w-5 animate-spin text-[#2a3d83]" />
                            <span>Procesando...</span>
                        </div>
                    </div>
                )}

                {/* Modal de detalle de notificación */}
                <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                    <DialogContent className="sm:max-w-[500px] max-h-[85vh] flex flex-col">
                        <DialogHeader className="flex-shrink-0">
                            <DialogTitle className="flex items-center gap-2">
                                <div className="p-2 rounded-lg bg-blue-100">
                                    <Bell className="h-5 w-5 text-[#2a3d83]" />
                                </div>
                                <span className="text-[#2a3d83] line-clamp-2">{notificacionSeleccionada?.titulo}</span>
                            </DialogTitle>
                            <DialogDescription>
                                <div className="flex items-center gap-2 mt-2">
                                    <Badge className={prioridadColors[notificacionSeleccionada?.prioridad || 'media']}>
                                        {notificacionSeleccionada?.prioridad}
                                    </Badge>
                                    <span className="text-sm text-gray-500">
                                        {notificacionSeleccionada?.created_at && formatearFecha(notificacionSeleccionada.created_at)}
                                    </span>
                                </div>
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4 flex-1 overflow-y-auto min-h-0">
                            <div className="bg-gray-50 rounded-lg p-4 border">
                                <p className="text-gray-700 whitespace-pre-wrap text-sm leading-relaxed">
                                    {notificacionSeleccionada?.mensaje}
                                </p>
                            </div>
                        </div>
                        <div className="flex justify-end gap-2 pt-2 border-t flex-shrink-0">
                            <Button variant="outline" onClick={() => setModalOpen(false)}>
                                Cerrar
                            </Button>
                            {notificacionSeleccionada?.accion_url && (
                                <Button 
                                    onClick={() => {
                                        setModalOpen(false);
                                        router.visit(notificacionSeleccionada.accion_url!);
                                    }}
                                    className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                >
                                    <Eye className="h-4 w-4 mr-2" />
                                    Ver Detalle
                                </Button>
                            )}
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
