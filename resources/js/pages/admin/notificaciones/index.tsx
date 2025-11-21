import React, { useState } from 'react';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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
    MarkAsUnread,
    Calendar,
    User,
    BarChart3,
    Settings
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

    const marcarComoLeida = async (id: number) => {
        try {
            await fetch(`/admin/notificaciones/${id}/marcar-leida`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            router.reload({ preserveScroll: true });
        } catch (error) {
            console.error('Error al marcar como leída:', error);
        }
    };

    const marcarTodasLeidas = async () => {
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
        }
    };

    const archivarNotificacion = async (id: number) => {
        try {
            await fetch(`/admin/notificaciones/${id}/archivar`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            router.reload({ preserveScroll: true });
        } catch (error) {
            console.error('Error al archivar:', error);
        }
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
                                                        {notificacion.accion_url && (
                                                            <Link href={notificacion.accion_url}>
                                                                <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                                                                    <Eye className="h-4 w-4" />
                                                                </button>
                                                            </Link>
                                                        )}
                                                        {notificacion.estado === 'pendiente' && (
                                                            <button 
                                                                onClick={() => marcarComoLeida(notificacion.id)}
                                                                className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                            >
                                                                <CheckCircle className="h-4 w-4" />
                                                            </button>
                                                        )}
                                                        <button 
                                                            onClick={() => archivarNotificacion(notificacion.id)}
                                                            className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors"
                                                        >
                                                            <Archive className="h-4 w-4" />
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
                </div>
            </div>
        </AppLayout>
    );
}
