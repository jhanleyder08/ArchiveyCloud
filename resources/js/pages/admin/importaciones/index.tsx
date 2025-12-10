import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    Search, 
    Filter, 
    Download, 
    Eye, 
    Play, 
    X, 
    Trash2,
    FileText,
    Calendar,
    User,
    AlertTriangle,
    CheckCircle,
    XCircle,
    Clock,
    MoreHorizontal,
    RefreshCw
} from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Badge } from '../../../components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuLabel, 
    DropdownMenuSeparator, 
    DropdownMenuTrigger 
} from '../../../components/ui/dropdown-menu';
import { Progress } from '../../../components/ui/progress';

interface ImportacionDatos {
    id: number;
    nombre: string;
    descripcion: string;
    tipo: string;
    formato_origen: string;
    estado: string;
    total_registros: number;
    registros_procesados: number;
    registros_exitosos: number;
    registros_fallidos: number;
    porcentaje_avance: number;
    fecha_inicio: string | null;
    fecha_finalizacion: string | null;
    tiempo_procesamiento: number | null;
    mensaje_error: string | null;
    created_at: string;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
    archivos_generados: Array<{
        tipo: string;
        nombre: string;
        archivo: string;
    }>;
}

interface PaginatedData {
    data: ImportacionDatos[];
    links: any[];
    meta?: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
    // Propiedades alternativas (Laravel puede enviar paginación en formato plano)
    current_page?: number;
    from?: number;
    last_page?: number;
    per_page?: number;
    to?: number;
    total?: number;
}

interface Filtros {
    buscar?: string;
    tipo?: string;
    estado?: string;
    usuario_id?: string;
    fecha_desde?: string;
    fecha_hasta?: string;
}

interface Props {
    importaciones: PaginatedData;
    filtros: Filtros;
    tipos: { [key: string]: string };
    estados: { [key: string]: string };
}

const tiposIcons = {
    expedientes: '📁',
    documentos: '📄',
    series: '📚',
    subseries: '📖',
    usuarios: '👥',
    trd: '📋',
    certificados: '🔐',
    mixto: '🔄'
};

const formatosIcons = {
    csv: '📊',
    excel: '📈',
    json: '⚙️',
    xml: '🏷️',
    sql: '🗄️',
    zip: '📦'
};

const estadosConfig = {
    pendiente: { icon: Clock, color: 'bg-yellow-100 text-yellow-800', label: 'Pendiente' },
    procesando: { icon: Play, color: 'bg-blue-100 text-blue-800', label: 'Procesando' },
    completada: { icon: CheckCircle, color: 'bg-green-100 text-green-800', label: 'Completada' },
    fallida: { icon: XCircle, color: 'bg-red-100 text-red-800', label: 'Fallida' },
    cancelada: { icon: AlertTriangle, color: 'bg-gray-100 text-gray-800', label: 'Cancelada' }
};

export default function ImportacionesIndex({ importaciones, filtros, tipos, estados }: Props) {
    const [busqueda, setBusqueda] = useState(filtros.buscar || '');
    const [filtrosLocales, setFiltrosLocales] = useState(filtros);

    const aplicarFiltros = () => {
        router.get('/admin/importaciones/listado', {
            ...filtrosLocales,
            buscar: busqueda
        });
    };

    const limpiarFiltros = () => {
        setBusqueda('');
        setFiltrosLocales({});
        router.get('/admin/importaciones/listado');
    };

    const procesarImportacion = (id: number) => {
        router.post(`/admin/importaciones/${id}/procesar`, {}, {
            onSuccess: () => {
                // Recargar página o mostrar mensaje de éxito
            }
        });
    };

    const cancelarImportacion = (id: number) => {
        if (confirm('¿Estás seguro de que deseas cancelar esta importación?')) {
            router.post(`/admin/importaciones/${id}/cancelar`);
        }
    };

    const eliminarImportacion = (id: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar esta importación? Esta acción no se puede deshacer.')) {
            router.delete(`/admin/importaciones/${id}`);
        }
    };

    const formatearTiempo = (segundos: number | null): string => {
        if (!segundos) return '-';
        
        if (segundos < 60) return `${segundos}s`;
        if (segundos < 3600) {
            const min = Math.floor(segundos / 60);
            const seg = segundos % 60;
            return `${min}m ${seg}s`;
        }
        const horas = Math.floor(segundos / 3600);
        const min = Math.floor((segundos % 3600) / 60);
        return `${horas}h ${min}m`;
    };

    const formatearFecha = (fecha: string): string => {
        return new Date(fecha).toLocaleString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <AppLayout>
            <Head title="Gestión de Importaciones" />
            
            <div className="container mx-auto p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                            <Download className="h-8 w-8 text-blue-600" />
                            Gestión de Importaciones
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Administra todas las importaciones de datos del sistema
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button variant="outline" onClick={() => window.location.reload()}>
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Actualizar
                        </Button>
                        <Button asChild>
                            <Link href="/admin/importaciones/crear">
                                <Download className="h-4 w-4 mr-2" />
                                Nueva Importación
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-5 w-5" />
                            Filtros de Búsqueda
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Buscar</label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Nombre de importación..."
                                        value={busqueda}
                                        onChange={(e) => setBusqueda(e.target.value)}
                                        className="pl-10"
                                        onKeyPress={(e) => e.key === 'Enter' && aplicarFiltros()}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Tipo</label>
                                <Select
                                    value={filtrosLocales.tipo || '_all'}
                                    onValueChange={(value) => setFiltrosLocales(prev => ({ ...prev, tipo: value === '_all' ? undefined : value }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los tipos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_all">Todos los tipos</SelectItem>
                                        {Object.entries(tipos).map(([key, label]) => (
                                            <SelectItem key={key} value={key}>
                                                {tiposIcons[key as keyof typeof tiposIcons]} {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Estado</label>
                                <Select
                                    value={filtrosLocales.estado || '_all'}
                                    onValueChange={(value) => setFiltrosLocales(prev => ({ ...prev, estado: value === '_all' ? undefined : value }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_all">Todos los estados</SelectItem>
                                        {Object.entries(estados).map(([key, label]) => (
                                            <SelectItem key={key} value={key}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Fecha desde</label>
                                <Input
                                    type="date"
                                    value={filtrosLocales.fecha_desde || ''}
                                    onChange={(e) => setFiltrosLocales(prev => ({ ...prev, fecha_desde: e.target.value || undefined }))}
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-3 mt-4">
                            <Button onClick={aplicarFiltros}>
                                <Search className="h-4 w-4 mr-2" />
                                Aplicar Filtros
                            </Button>
                            <Button variant="outline" onClick={limpiarFiltros}>
                                <X className="h-4 w-4 mr-2" />
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Estadísticas rápidas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Total</p>
                                    <p className="text-2xl font-bold">{importaciones?.meta?.total ?? importaciones?.total ?? 0}</p>
                                </div>
                                <FileText className="h-8 w-8 text-blue-600" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">En Proceso</p>
                                    <p className="text-2xl font-bold text-blue-600">
                                        {(importaciones?.data ?? []).filter(i => i.estado === 'procesando').length}
                                    </p>
                                </div>
                                <Play className="h-8 w-8 text-blue-600" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Completadas</p>
                                    <p className="text-2xl font-bold text-green-600">
                                        {(importaciones?.data ?? []).filter(i => i.estado === 'completada').length}
                                    </p>
                                </div>
                                <CheckCircle className="h-8 w-8 text-green-600" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Fallidas</p>
                                    <p className="text-2xl font-bold text-red-600">
                                        {(importaciones?.data ?? []).filter(i => i.estado === 'fallida').length}
                                    </p>
                                </div>
                                <XCircle className="h-8 w-8 text-red-600" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabla de importaciones */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Importaciones</CardTitle>
                        <CardDescription>
                            Mostrando {importaciones?.meta?.from ?? importaciones?.from ?? 0} - {importaciones?.meta?.to ?? importaciones?.to ?? 0} de {importaciones?.meta?.total ?? importaciones?.total ?? 0} importaciones
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Importación</TableHead>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Progreso</TableHead>
                                        <TableHead>Resultados</TableHead>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Tiempo</TableHead>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(importaciones?.data ?? []).map((importacion) => {
                                        const estadoConfig = estadosConfig[importacion.estado as keyof typeof estadosConfig];
                                        const IconoEstado = estadoConfig?.icon || Clock;
                                        
                                        return (
                                            <TableRow key={importacion.id}>
                                                <TableCell>
                                                    <div>
                                                        <Link
                                                            href={`/admin/importaciones/${importacion.id}`}
                                                            className="font-medium hover:underline"
                                                        >
                                                            {importacion.nombre}
                                                        </Link>
                                                        {importacion.descripcion && (
                                                            <p className="text-xs text-muted-foreground mt-1">
                                                                {importacion.descripcion}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-lg">
                                                            {tiposIcons[importacion.tipo as keyof typeof tiposIcons]}
                                                        </span>
                                                        <div>
                                                            <div className="font-medium">{tipos[importacion.tipo]}</div>
                                                            <div className="text-xs text-muted-foreground flex items-center gap-1">
                                                                {formatosIcons[importacion.formato_origen as keyof typeof formatosIcons]}
                                                                {importacion.formato_origen.toUpperCase()}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <Badge className={estadoConfig?.color}>
                                                        <IconoEstado className="h-3 w-3 mr-1" />
                                                        {estadoConfig?.label || importacion.estado}
                                                    </Badge>
                                                    {importacion.mensaje_error && (
                                                        <p className="text-xs text-red-600 mt-1">
                                                            {importacion.mensaje_error.substring(0, 50)}...
                                                        </p>
                                                    )}
                                                </TableCell>

                                                <TableCell>
                                                    {importacion.estado === 'procesando' ? (
                                                        <div className="space-y-1">
                                                            <div className="flex items-center justify-between text-sm">
                                                                <span>{importacion.registros_procesados} / {importacion.total_registros}</span>
                                                                <span className="font-medium">{parseFloat(importacion.porcentaje_avance || 0).toFixed(1)}%</span>
                                                            </div>
                                                            <Progress value={parseFloat(importacion.porcentaje_avance || 0)} className="h-2" />
                                                        </div>
                                                    ) : importacion.estado === 'completada' ? (
                                                        <div className="text-sm">
                                                            <div className="text-green-600">✓ Completada</div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {importacion.total_registros} registros
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <div className="text-sm text-muted-foreground">
                                                            {importacion.total_registros} registros
                                                        </div>
                                                    )}
                                                </TableCell>

                                                <TableCell>
                                                    {importacion.estado === 'completada' && (
                                                        <div className="text-sm">
                                                            <div className="text-green-600">
                                                                ✓ {importacion.registros_exitosos} exitosos
                                                            </div>
                                                            {importacion.registros_fallidos > 0 && (
                                                                <div className="text-red-600">
                                                                    ✗ {importacion.registros_fallidos} fallidos
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                    {importacion.estado === 'fallida' && (
                                                        <div className="text-sm text-red-600">
                                                            Error en procesamiento
                                                        </div>
                                                    )}
                                                </TableCell>

                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <User className="h-4 w-4 text-muted-foreground" />
                                                        <div>
                                                            <div className="font-medium text-sm">{importacion.usuario?.name || 'Usuario no disponible'}</div>
                                                            <div className="text-xs text-muted-foreground">{importacion.usuario?.email || 'Email no disponible'}</div>
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <div className="text-sm">
                                                        {formatearTiempo(importacion.tiempo_procesamiento)}
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <div className="text-sm">
                                                        <div>{formatearFecha(importacion.created_at)}</div>
                                                        {importacion.fecha_finalizacion && (
                                                            <div className="text-xs text-muted-foreground">
                                                                Fin: {formatearFecha(importacion.fecha_finalizacion)}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                                                            
                                                            <DropdownMenuItem asChild>
                                                                <Link href={`/admin/importaciones/${importacion.id}`}>
                                                                    <Eye className="h-4 w-4 mr-2" />
                                                                    Ver detalles
                                                                </Link>
                                                            </DropdownMenuItem>

                                                            {importacion.estado === 'pendiente' && (
                                                                <DropdownMenuItem onClick={() => procesarImportacion(importacion.id)}>
                                                                    <Play className="h-4 w-4 mr-2" />
                                                                    Procesar
                                                                </DropdownMenuItem>
                                                            )}

                                                            {(importacion.estado === 'pendiente' || importacion.estado === 'procesando') && (
                                                                <DropdownMenuItem onClick={() => cancelarImportacion(importacion.id)}>
                                                                    <X className="h-4 w-4 mr-2" />
                                                                    Cancelar
                                                                </DropdownMenuItem>
                                                            )}

                                                            <DropdownMenuSeparator />

                                                            {(importacion.archivos_generados ?? []).map((archivo) => (
                                                                <DropdownMenuItem key={archivo.tipo} asChild>
                                                                    <a href={`/admin/importaciones/${importacion.id}/descargar/${archivo.tipo}`}>
                                                                        <Download className="h-4 w-4 mr-2" />
                                                                        {archivo.nombre}
                                                                    </a>
                                                                </DropdownMenuItem>
                                                            ))}

                                                            {(importacion.estado === 'pendiente' || importacion.estado === 'fallida' || importacion.estado === 'cancelada') && (
                                                                <>
                                                                    <DropdownMenuSeparator />
                                                                    <DropdownMenuItem 
                                                                        onClick={() => eliminarImportacion(importacion.id)}
                                                                        className="text-red-600"
                                                                    >
                                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                                        Eliminar
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>

                            {(importaciones?.data ?? []).length === 0 && (
                                <div className="text-center py-8">
                                    <Download className="h-12 w-12 mx-auto text-muted-foreground opacity-50" />
                                    <h3 className="mt-4 text-lg font-semibold">No hay importaciones</h3>
                                    <p className="text-muted-foreground">
                                        No se encontraron importaciones con los filtros aplicados.
                                    </p>
                                    <Button asChild className="mt-4">
                                        <Link href="/admin/importaciones/crear">
                                            Crear primera importación
                                        </Link>
                                    </Button>
                                </div>
                            )}
                        </div>

                        {/* Paginación */}
                        {(importaciones?.meta?.last_page ?? importaciones?.last_page ?? 1) > 1 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-muted-foreground">
                                    Mostrando {importaciones?.meta?.from ?? importaciones?.from ?? 0} a {importaciones?.meta?.to ?? importaciones?.to ?? 0} de {importaciones?.meta?.total ?? importaciones?.total ?? 0} resultados
                                </div>
                                <div className="flex items-center space-x-2">
                                    {(importaciones?.links ?? []).map((link, index) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? "default" : "outline"}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
