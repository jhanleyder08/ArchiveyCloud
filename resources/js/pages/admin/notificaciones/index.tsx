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
        
        router.get(route('admin.notificaciones.index'), filtrosLimpios, {
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
        router.visit(route('admin.notificaciones.index'));
    };

    const marcarComoLeida = async (id: number) => {
        try {
            await fetch(route('admin.notificaciones.marcar-leida', id), {
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
            await fetch(route('admin.notificaciones.marcar-todas-leidas'), {
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
            await fetch(route('admin.notificaciones.archivar', id), {
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
        <AppLayout>
            <Head title="Notificaciones" />

            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Centro de Notificaciones
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Gestiona tus notificaciones y alertas del sistema
                    </p>
                </div>
                
                <div className="flex items-center space-x-2">
                    {estadisticas.pendientes > 0 && (
                        <Button onClick={marcarTodasLeidas} variant="outline">
                            <CheckCircle className="h-4 w-4 mr-2" />
                            Marcar todas como leídas
                        </Button>
                    )}
                    <Button variant="outline" asChild>
                        <Link href={route('admin.notificaciones.admin')}>
                            <Settings className="h-4 w-4 mr-2" />
                            Panel Admin
                        </Link>
                    </Button>
                </div>
            </div>

            <div className="space-y-6">
                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="flex items-center p-6">
                            <div className="flex items-center">
                                <div className="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                                    <Bell className="h-6 w-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.total}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent className="flex items-center p-6">
                            <div className="flex items-center">
                                <div className="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-lg">
                                    <BellRing className="h-6 w-6 text-yellow-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Pendientes</p>
                                    <p className="text-2xl font-bold text-yellow-700">{estadisticas.pendientes}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex items-center p-6">
                            <div className="flex items-center">
                                <div className="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg">
                                    <CheckCircle className="h-6 w-6 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Leídas</p>
                                    <p className="text-2xl font-bold text-green-700">{estadisticas.leidas}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex items-center p-6">
                            <div className="flex items-center">
                                <div className="flex items-center justify-center w-12 h-12 bg-red-100 rounded-lg">
                                    <AlertTriangle className="h-6 w-6 text-red-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Críticas</p>
                                    <p className="text-2xl font-bold text-red-700">{estadisticas.criticas}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Filter className="h-5 w-5" />
                            <span>Filtros</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="space-y-2">
                                <Label>Estado</Label>
                                <Select value={filtrosForm.estado} onValueChange={(value) => setFiltrosForm({...filtrosForm, estado: value})}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los estados</SelectItem>
                                        <SelectItem value="pendiente">Pendientes</SelectItem>
                                        <SelectItem value="leida">Leídas</SelectItem>
                                        <SelectItem value="archivada">Archivadas</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Tipo</Label>
                                <Select value={filtrosForm.tipo} onValueChange={(value) => setFiltrosForm({...filtrosForm, tipo: value})}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los tipos</SelectItem>
                                        <SelectItem value="expediente_vencido">Expedientes vencidos</SelectItem>
                                        <SelectItem value="expediente_proximo_vencer">Expedientes próximos</SelectItem>
                                        <SelectItem value="prestamo_vencido">Préstamos vencidos</SelectItem>
                                        <SelectItem value="prestamo_proximo_vencer">Préstamos próximos</SelectItem>
                                        <SelectItem value="disposicion_pendiente">Disposiciones pendientes</SelectItem>
                                        <SelectItem value="sistema">Sistema</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Prioridad</Label>
                                <Select value={filtrosForm.prioridad} onValueChange={(value) => setFiltrosForm({...filtrosForm, prioridad: value})}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todas las prioridades</SelectItem>
                                        <SelectItem value="baja">Baja</SelectItem>
                                        <SelectItem value="media">Media</SelectItem>
                                        <SelectItem value="alta">Alta</SelectItem>
                                        <SelectItem value="critica">Crítica</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2 mt-4">
                            <Button onClick={aplicarFiltros}>
                                <Search className="h-4 w-4 mr-2" />
                                Aplicar Filtros
                            </Button>
                            <Button variant="outline" onClick={limpiarFiltros}>
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabla de Notificaciones */}
                <Card>
                    <CardHeader>
                        <CardTitle>Mis Notificaciones ({notificaciones.total})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {notificaciones.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-12"></TableHead>
                                            <TableHead>Notificación</TableHead>
                                            <TableHead>Prioridad</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead>Fecha</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {notificaciones.data.map((notificacion) => {
                                            const IconComponent = getIconComponent(notificacion.icono);
                                            return (
                                                <TableRow 
                                                    key={notificacion.id} 
                                                    className={notificacion.estado === 'pendiente' ? 'bg-blue-50' : ''}
                                                >
                                                    <TableCell>
                                                        <div className={`p-2 rounded-lg ${
                                                            notificacion.prioridad === 'critica' ? 'bg-red-100' :
                                                            notificacion.prioridad === 'alta' ? 'bg-orange-100' :
                                                            notificacion.prioridad === 'media' ? 'bg-yellow-100' :
                                                            'bg-blue-100'
                                                        }`}>
                                                            <IconComponent className={`h-4 w-4 ${notificacion.color_prioridad}`} />
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <p className="font-medium">{notificacion.titulo}</p>
                                                            <p className="text-sm text-gray-500 mt-1">{notificacion.mensaje}</p>
                                                            {notificacion.creado_por && (
                                                                <p className="text-xs text-gray-400 mt-1">
                                                                    Por: {notificacion.creado_por.name}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="outline" className={prioridadColors[notificacion.prioridad]}>
                                                            {notificacion.prioridad.charAt(0).toUpperCase() + notificacion.prioridad.slice(1)}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="outline" className={estadoColors[notificacion.estado]}>
                                                            {notificacion.estado === 'pendiente' ? 'Pendiente' :
                                                             notificacion.estado === 'leida' ? 'Leída' : 'Archivada'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            {formatearFecha(notificacion.created_at)}
                                                            {notificacion.leida_en && (
                                                                <p className="text-xs text-gray-500">
                                                                    Leída: {formatearFecha(notificacion.leida_en)}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex items-center justify-end space-x-2">
                                                            {notificacion.accion_url && (
                                                                <Button variant="outline" size="sm" asChild>
                                                                    <Link href={notificacion.accion_url}>
                                                                        <Eye className="h-4 w-4" />
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                            {notificacion.estado === 'pendiente' && (
                                                                <Button 
                                                                    variant="outline" 
                                                                    size="sm"
                                                                    onClick={() => marcarComoLeida(notificacion.id)}
                                                                >
                                                                    <CheckCircle className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            <Button 
                                                                variant="outline" 
                                                                size="sm"
                                                                onClick={() => archivarNotificacion(notificacion.id)}
                                                            >
                                                                <Archive className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <Bell className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    No hay notificaciones
                                </h3>
                                <p className="text-gray-500">
                                    No tienes notificaciones que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
