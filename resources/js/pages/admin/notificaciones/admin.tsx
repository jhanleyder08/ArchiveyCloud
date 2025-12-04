import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import { 
    Bell,
    BellRing,
    Search,
    Plus,
    Eye,
    CheckCircle,
    Clock,
    AlertTriangle,
    Archive,
    Trash2,
    Calendar,
    User,
    Users,
    Settings,
    RefreshCw,
    Send,
    BarChart3,
    Filter,
    ChevronLeft,
    ChevronRight
} from 'lucide-react';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

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
    es_automatica: boolean;
    usuario?: Usuario;
    creado_por?: Usuario;
}

interface TipoPopular {
    tipo: string;
    total: number;
}

interface Estadisticas {
    total_sistema: number;
    pendientes_sistema: number;
    usuarios_con_notificaciones: number;
    tipos_populares: TipoPopular[];
}

interface Props {
    notificaciones: {
        data: Notificacion[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    estadisticas: Estadisticas;
    usuarios: Usuario[];
    filtros: {
        usuario_id?: string;
        tipo?: string;
        estado?: string;
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

const tipoLabels: Record<string, string> = {
    expediente_vencido: 'Expediente Vencido',
    expediente_proximo_vencer: 'Expediente Próximo a Vencer',
    prestamo_vencido: 'Préstamo Vencido',
    prestamo_proximo_vencer: 'Préstamo Próximo a Vencer',
    disposicion_pendiente: 'Disposición Pendiente',
    disposicion_aprobada: 'Disposición Aprobada',
    documento_subido: 'Documento Subido',
    usuario_nuevo: 'Usuario Nuevo',
    sistema: 'Sistema',
    seguridad: 'Seguridad',
    general: 'General',
};

export default function NotificacionesAdmin({ notificaciones, estadisticas, usuarios, filtros }: Props) {
    const [filtrosForm, setFiltrosForm] = useState({
        usuario_id: filtros.usuario_id || 'todos',
        tipo: filtros.tipo || 'todos',
        estado: filtros.estado || 'todos',
        prioridad: filtros.prioridad || 'todos',
    });
    const [isLoading, setIsLoading] = useState(false);

    const aplicarFiltros = () => {
        setIsLoading(true);
        const filtrosLimpios: Record<string, string> = {};
        
        if (filtrosForm.usuario_id !== 'todos') filtrosLimpios.usuario_id = filtrosForm.usuario_id;
        if (filtrosForm.tipo !== 'todos') filtrosLimpios.tipo = filtrosForm.tipo;
        if (filtrosForm.estado !== 'todos') filtrosLimpios.estado = filtrosForm.estado;
        if (filtrosForm.prioridad !== 'todos') filtrosLimpios.prioridad = filtrosForm.prioridad;
        
        router.get('/admin/notificaciones/admin', filtrosLimpios, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsLoading(false),
        });
    };

    const limpiarFiltros = () => {
        setFiltrosForm({
            usuario_id: 'todos',
            tipo: 'todos',
            estado: 'todos',
            prioridad: 'todos',
        });
        router.visit('/admin/notificaciones/admin');
    };

    const limpiarAntiguas = async () => {
        if (!confirm('¿Estás seguro de eliminar las notificaciones leídas con más de 30 días?')) {
            return;
        }
        
        router.post('/admin/notificaciones/limpiar-antiguas', {}, {
            onSuccess: () => {
                router.reload();
            },
        });
    };

    const eliminarNotificacion = (id: number) => {
        if (!confirm('¿Estás seguro de eliminar esta notificación?')) {
            return;
        }
        
        router.delete(`/admin/notificaciones/${id}`, {
            preserveScroll: true,
        });
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

    const irAPagina = (url: string | null) => {
        if (url) {
            router.visit(url, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Administración', href: '#' },
            { title: 'Notificaciones', href: '/admin/notificaciones' },
            { title: 'Panel Administrativo', href: '/admin/notificaciones/admin' },
        ]}>
            <Head title="Panel Administrativo - Notificaciones" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between pt-4 gap-4">
                    <div className="flex items-center gap-2">
                        <Settings className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Panel Administrativo de Notificaciones
                        </h1>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" onClick={limpiarAntiguas}>
                            <Trash2 className="h-4 w-4 mr-2 text-red-500" />
                            Limpiar Antiguas
                        </Button>
                        <Button asChild className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                            <Link href="/admin/notificaciones/crear">
                                <Plus className="h-4 w-4 mr-2" />
                                Nueva Notificación
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Estadísticas del Sistema */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card className="bg-white border border-gray-200">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Total en Sistema</p>
                                    <p className="text-2xl font-semibold text-gray-900">{estadisticas.total_sistema.toLocaleString()}</p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <Bell className="h-6 w-6 text-[#2a3d83]" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card className="bg-white border border-gray-200">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Pendientes</p>
                                    <p className="text-2xl font-semibold text-orange-600">{estadisticas.pendientes_sistema.toLocaleString()}</p>
                                </div>
                                <div className="p-3 bg-orange-100 rounded-full">
                                    <BellRing className="h-6 w-6 text-orange-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border border-gray-200">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Usuarios con Notificaciones</p>
                                    <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.usuarios_con_notificaciones}</p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <Users className="h-6 w-6 text-[#2a3d83]" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border border-gray-200">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Tipos Activos</p>
                                    <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.tipos_populares?.length || 0}</p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <BarChart3 className="h-6 w-6 text-[#2a3d83]" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Tipos más populares */}
                {estadisticas.tipos_populares && estadisticas.tipos_populares.length > 0 && (
                    <Card className="bg-white border border-gray-200">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-gray-900">Tipos de Notificaciones más Frecuentes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-3">
                                {estadisticas.tipos_populares.map((tipo, index) => (
                                    <div key={index} className="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg">
                                        <span className="text-sm font-medium text-gray-700">
                                            {tipoLabels[tipo.tipo] || tipo.tipo}
                                        </span>
                                        <Badge variant="secondary">{tipo.total}</Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Filtros */}
                <Card className="bg-white border border-gray-200">
                    <CardContent className="pt-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">Usuario</label>
                                <Select value={filtrosForm.usuario_id} onValueChange={(value) => setFiltrosForm({...filtrosForm, usuario_id: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los usuarios" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los usuarios</SelectItem>
                                        {usuarios.map((usuario) => (
                                            <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                {usuario.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">Tipo</label>
                                <Select value={filtrosForm.tipo} onValueChange={(value) => setFiltrosForm({...filtrosForm, tipo: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los tipos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los tipos</SelectItem>
                                        {Object.entries(tipoLabels).map(([key, label]) => (
                                            <SelectItem key={key} value={key}>{label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">Estado</label>
                                <Select value={filtrosForm.estado} onValueChange={(value) => setFiltrosForm({...filtrosForm, estado: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los estados</SelectItem>
                                        <SelectItem value="pendiente">Pendiente</SelectItem>
                                        <SelectItem value="leida">Leída</SelectItem>
                                        <SelectItem value="archivada">Archivada</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-gray-700">Prioridad</label>
                                <Select value={filtrosForm.prioridad} onValueChange={(value) => setFiltrosForm({...filtrosForm, prioridad: value})}>
                                    <SelectTrigger>
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
                            </div>

                            <div className="flex gap-2">
                                <Button onClick={aplicarFiltros} disabled={isLoading} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                    {isLoading ? <RefreshCw className="h-4 w-4 mr-2 animate-spin" /> : <Filter className="h-4 w-4 mr-2" />}
                                    Filtrar
                                </Button>
                                <Button variant="outline" onClick={limpiarFiltros}>
                                    Limpiar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabla de Notificaciones */}
                <Card className="bg-white border border-gray-200">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-lg font-semibold text-gray-900">
                                Todas las Notificaciones ({notificaciones.total})
                            </CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b">
                                    <tr>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Usuario</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Notificación</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Tipo</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Prioridad</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Estado</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Fecha</th>
                                        <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {notificaciones.data.length > 0 ? (
                                        notificaciones.data.map((notificacion) => (
                                            <tr key={notificacion.id} className="hover:bg-gray-50 transition-colors">
                                                <td className="py-3 px-4">
                                                    <div className="flex items-center gap-2">
                                                        <div className="p-2 bg-blue-100 rounded-full">
                                                            <User className="h-4 w-4 text-[#2a3d83]" />
                                                        </div>
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900">
                                                                {notificacion.usuario?.name || 'Usuario desconocido'}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {notificacion.usuario?.email}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div className="max-w-xs">
                                                        <p className="font-medium text-gray-900 truncate">{notificacion.titulo}</p>
                                                        <p className="text-sm text-gray-500 truncate">{notificacion.mensaje}</p>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <Badge variant="outline" className="text-xs">
                                                        {tipoLabels[notificacion.tipo] || notificacion.tipo}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${prioridadColors[notificacion.prioridad]}`}>
                                                        {notificacion.prioridad.charAt(0).toUpperCase() + notificacion.prioridad.slice(1)}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${estadoColors[notificacion.estado]}`}>
                                                        {notificacion.estado === 'pendiente' ? 'Pendiente' :
                                                         notificacion.estado === 'leida' ? 'Leída' : 'Archivada'}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div className="text-sm text-gray-600">
                                                        {formatearFecha(notificacion.created_at)}
                                                    </div>
                                                    {notificacion.es_automatica && (
                                                        <Badge variant="secondary" className="text-xs mt-1">Automática</Badge>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div className="flex items-center gap-1">
                                                        {notificacion.accion_url && (
                                                            <Link href={notificacion.accion_url}>
                                                                <button className="p-2 rounded-md text-[#2a3d83] hover:bg-blue-50 transition-colors">
                                                                    <Eye className="h-4 w-4" />
                                                                </button>
                                                            </Link>
                                                        )}
                                                        <button 
                                                            onClick={() => eliminarNotificacion(notificacion.id)}
                                                            className="p-2 rounded-md text-red-500 hover:bg-red-50 transition-colors"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={7} className="py-8 px-4 text-center text-gray-500">
                                                <Bell className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                                    No hay notificaciones
                                                </h3>
                                                <p className="text-gray-500">
                                                    No se encontraron notificaciones con los filtros seleccionados.
                                                </p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Paginación */}
                        {notificaciones.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6 pt-4 border-t">
                                <p className="text-sm text-gray-600">
                                    Mostrando {((notificaciones.current_page - 1) * notificaciones.per_page) + 1} a{' '}
                                    {Math.min(notificaciones.current_page * notificaciones.per_page, notificaciones.total)} de{' '}
                                    {notificaciones.total} notificaciones
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => irAPagina(notificaciones.links[0]?.url)}
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
                                        onClick={() => irAPagina(notificaciones.links[notificaciones.links.length - 1]?.url)}
                                        disabled={notificaciones.current_page === notificaciones.last_page}
                                    >
                                        Siguiente
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
