import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Search, Filter, Plus, Eye, Clock, Calendar, User, FileText, AlertTriangle, CheckCircle, XCircle } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Badge } from '../../../components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';

interface Documento {
    id: number;
    nombre: string;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Firmante {
    usuario: Usuario;
    estado: string;
    rol: string;
    orden: number;
    es_obligatorio: boolean;
}

interface SolicitudFirma {
    id: number;
    titulo: string;
    descripcion: string;
    tipo_flujo: string;
    prioridad: string;
    estado: string;
    fecha_limite: string;
    created_at: string;
    progreso?: {
        total: number;
        completadas: number;
        pendientes: number;
        porcentaje: number;
    };
    documento: Documento;
    solicitante: Usuario;
    firmantes: Firmante[];
}

interface Props {
    solicitudes: {
        data: SolicitudFirma[];
        links: any[];
        meta: any;
    };
    filtros: {
        vista?: string;
        estado?: string;
        prioridad?: string;
        buscar?: string;
    };
    estados: Record<string, string>;
}

export default function SolicitudesIndex({ solicitudes, filtros, estados }: Props) {
    const [vista, setVista] = useState(filtros.vista || 'general');

    const getBadgeVariant = (estado: string) => {
        switch (estado) {
            case 'completada':
                return 'secondary';
            case 'en_proceso':
                return 'default';
            case 'pendiente':
                return 'outline';
            case 'cancelada':
            case 'vencida':
                return 'destructive';
            default:
                return 'outline';
        }
    };

    const getPrioridadColor = (prioridad: string) => {
        switch (prioridad) {
            case 'urgente':
                return 'text-red-600 bg-red-100';
            case 'alta':
                return 'text-orange-600 bg-orange-100';
            case 'normal':
                return 'text-blue-600 bg-blue-100';
            case 'baja':
                return 'text-gray-600 bg-gray-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    };

    const getEstadoIcon = (estado: string) => {
        switch (estado) {
            case 'completada':
                return <CheckCircle className="h-4 w-4 text-green-500" />;
            case 'en_proceso':
                return <Clock className="h-4 w-4 text-blue-500" />;
            case 'pendiente':
                return <AlertTriangle className="h-4 w-4 text-yellow-500" />;
            case 'cancelada':
            case 'vencida':
                return <XCircle className="h-4 w-4 text-red-500" />;
            default:
                return <Clock className="h-4 w-4 text-gray-500" />;
        }
    };

    const handleFiltrar = (campo: string, valor: string) => {
        const nuevaUrl = route('admin.firmas.solicitudes', {
            ...filtros,
            [campo]: valor === 'all' ? '' : valor,
            page: 1
        });
        router.get(nuevaUrl);
    };

    const handleBuscar = (buscar: string) => {
        const nuevaUrl = route('admin.firmas.solicitudes', {
            ...filtros,
            buscar,
            page: 1
        });
        router.get(nuevaUrl);
    };

    const handleCambiarVista = (nuevaVista: string) => {
        setVista(nuevaVista);
        const nuevaUrl = route('admin.firmas.solicitudes', {
            ...filtros,
            vista: nuevaVista,
            page: 1
        });
        router.get(nuevaUrl);
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const esVencida = (fechaLimite: string) => {
        return new Date(fechaLimite) < new Date();
    };

    return (
        <AppLayout>
            <Head title="Solicitudes de Firma" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Solicitudes de Firma</h1>
                        <p className="text-muted-foreground">
                            Gestiona las solicitudes de firma digital de documentos
                        </p>
                    </div>
                    <Link href={route('admin.firmas.solicitudes.crear')}>
                        <Button>
                            <Plus className="h-4 w-4 mr-2" />
                            Nueva Solicitud
                        </Button>
                    </Link>
                </div>

                {/* Tabs de Vista */}
                <Tabs value={vista} onValueChange={handleCambiarVista}>
                    <TabsList>
                        <TabsTrigger value="general">Todas</TabsTrigger>
                        <TabsTrigger value="pendientes">Pendientes de Firmar</TabsTrigger>
                        <TabsTrigger value="mis_solicitudes">Mis Solicitudes</TabsTrigger>
                    </TabsList>

                    <TabsContent value={vista} className="space-y-4">
                        {/* Filtros */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Filter className="h-5 w-5 mr-2" />
                                    Filtros
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Búsqueda</label>
                                        <div className="relative">
                                            <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                            <Input
                                                placeholder="Buscar solicitudes..."
                                                defaultValue={filtros.buscar}
                                                className="pl-9"
                                                onChange={(e) => {
                                                    clearTimeout((window as any).searchTimeout);
                                                    (window as any).searchTimeout = setTimeout(() => {
                                                        handleBuscar(e.target.value);
                                                    }, 500);
                                                }}
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Estado</label>
                                        <Select value={filtros.estado || 'all'} onValueChange={(value) => handleFiltrar('estado', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todos los estados" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todos los estados</SelectItem>
                                                {Object.entries(estados).map(([valor, nombre]) => (
                                                    <SelectItem key={valor} value={valor}>
                                                        {nombre}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Prioridad</label>
                                        <Select value={filtros.prioridad || 'all'} onValueChange={(value) => handleFiltrar('prioridad', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todas las prioridades" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todas las prioridades</SelectItem>
                                                <SelectItem value="urgente">Urgente</SelectItem>
                                                <SelectItem value="alta">Alta</SelectItem>
                                                <SelectItem value="normal">Normal</SelectItem>
                                                <SelectItem value="baja">Baja</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="flex items-end">
                                        <Button variant="outline" onClick={() => window.location.reload()}>
                                            Limpiar Filtros
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Lista de Solicitudes */}
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    Solicitudes ({solicitudes.meta?.total || 0})
                                </CardTitle>
                                <CardDescription>
                                    Mostrando {solicitudes.meta?.from || 0} a {solicitudes.meta?.to || 0} de {solicitudes.meta?.total || 0} solicitudes
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Título</TableHead>
                                                <TableHead>Documento</TableHead>
                                                <TableHead>Solicitante</TableHead>
                                                <TableHead>Prioridad</TableHead>
                                                <TableHead>Progreso</TableHead>
                                                <TableHead>Fecha Límite</TableHead>
                                                <TableHead>Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {solicitudes.data.map((solicitud) => (
                                                <TableRow key={solicitud.id}>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            {getEstadoIcon(solicitud.estado)}
                                                            <Badge variant={getBadgeVariant(solicitud.estado)}>
                                                                {estados[solicitud.estado] || solicitud.estado}
                                                            </Badge>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">{solicitud.titulo}</div>
                                                            {solicitud.descripcion && (
                                                                <div className="text-sm text-gray-500 truncate max-w-xs">
                                                                    {solicitud.descripcion}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            <FileText className="h-4 w-4 text-gray-500" />
                                                            <span className="font-medium">{solicitud.documento.nombre}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            <User className="h-4 w-4 text-gray-500" />
                                                            <span>{solicitud.solicitante.name}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge className={getPrioridadColor(solicitud.prioridad)}>
                                                            {solicitud.prioridad}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        {solicitud.progreso && (
                                                            <div className="space-y-1">
                                                                <div className="text-sm">
                                                                    {solicitud.progreso.completadas}/{solicitud.progreso.total} firmantes
                                                                </div>
                                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                                    <div 
                                                                        className="bg-blue-600 h-2 rounded-full" 
                                                                        style={{ width: `${solicitud.progreso.porcentaje}%` }}
                                                                    ></div>
                                                                </div>
                                                                <div className="text-xs text-gray-500">
                                                                    {solicitud.progreso.porcentaje}%
                                                                </div>
                                                            </div>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            <div className={esVencida(solicitud.fecha_limite) ? 'text-red-600 font-medium' : ''}>
                                                                {formatearFecha(solicitud.fecha_limite)}
                                                            </div>
                                                            {esVencida(solicitud.fecha_limite) && (
                                                                <Badge variant="destructive" className="text-xs">
                                                                    Vencida
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            <Link href={route('admin.firmas.solicitud', solicitud.id)}>
                                                                <Button variant="outline" size="sm">
                                                                    <Eye className="h-4 w-4 mr-1" />
                                                                    Ver
                                                                </Button>
                                                            </Link>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>

                                {solicitudes.data.length === 0 && (
                                    <div className="text-center py-8">
                                        <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">No hay solicitudes</h3>
                                        <p className="text-gray-600 mb-4">
                                            {Object.keys(filtros).length > 0 
                                                ? 'No se encontraron solicitudes con los filtros aplicados'
                                                : 'No hay solicitudes de firma registradas'
                                            }
                                        </p>
                                        <Link href={route('admin.firmas.solicitudes.crear')}>
                                            <Button>
                                                <Plus className="h-4 w-4 mr-2" />
                                                Crear Primera Solicitud
                                            </Button>
                                        </Link>
                                    </div>
                                )}

                                {/* Paginación */}
                                {(solicitudes.meta?.last_page || 0) > 1 && (
                                    <div className="flex items-center justify-between mt-6">
                                        <div className="text-sm text-gray-700">
                                            Mostrando {solicitudes.meta?.from || 0} a {solicitudes.meta?.to || 0} de {solicitudes.meta?.total || 0} resultados
                                        </div>
                                        <div className="flex space-x-1">
                                            {(solicitudes.links || []).map((link: any, index: number) => (
                                                <Button
                                                    key={index}
                                                    variant={link.active ? 'default' : 'outline'}
                                                    size="sm"
                                                    onClick={() => link.url && router.get(link.url)}
                                                    disabled={!link.url}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
