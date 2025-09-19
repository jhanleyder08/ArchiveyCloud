import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Plus, 
    Search, 
    Filter, 
    Eye,
    Clock,
    AlertTriangle,
    CheckCircle,
    BarChart3,
    Calendar,
    User,
    FileText,
    Archive
} from 'lucide-react';

interface Prestamo {
    id: number;
    tipo_prestamo: 'expediente' | 'documento';
    expediente?: {
        numero_expediente: string;
        titulo: string;
        ubicacion_fisica: string;
    };
    documento?: {
        nombre: string;
        expediente: {
            numero_expediente: string;
            titulo: string;
        };
    };
    solicitante: {
        name: string;
        email: string;
    };
    prestamista: {
        name: string;
    };
    motivo: string;
    fecha_prestamo: string;
    fecha_devolucion_esperada: string;
    fecha_devolucion_real?: string;
    estado: 'prestado' | 'devuelto' | 'cancelado';
    estado_devolucion?: 'bueno' | 'dañado' | 'perdido';
    renovaciones: number;
    dias_restantes?: number;
    esta_vencido?: boolean;
}

interface ProximoVencer {
    id: number;
    expediente?: {
        numero_expediente: string;
        titulo: string;
    };
    documento?: {
        nombre: string;
    };
    solicitante: {
        name: string;
    };
    fecha_devolucion_esperada: string;
    dias_restantes: number;
    tipo_prestamo: string;
}

interface Estadisticas {
    total_prestamos: number;
    prestamos_activos: number;
    prestamos_vencidos: number;
    prestamos_este_mes: number;
}

interface Props {
    prestamos: {
        data: Prestamo[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url?: string;
            label: string;
            active: boolean;
        }>;
    };
    estadisticas: Estadisticas;
    proximosVencer: ProximoVencer[];
    filtros: {
        estado?: string;
        tipo_prestamo?: string;
        fecha_inicio?: string;
        fecha_fin?: string;
        solicitante?: string;
    };
}

const estadoColors: Record<string, string> = {
    prestado: 'bg-blue-100 text-blue-800',
    devuelto: 'bg-green-100 text-green-800',
    cancelado: 'bg-gray-100 text-gray-800',
};

const estadoIcons: Record<string, React.ReactNode> = {
    prestado: <Clock className="h-4 w-4" />,
    devuelto: <CheckCircle className="h-4 w-4" />,
    cancelado: <AlertTriangle className="h-4 w-4" />,
};

export default function PrestamosIndex({ prestamos, estadisticas, proximosVencer, filtros }: Props) {
    const { data, setData, get, processing } = useForm({
        estado: filtros.estado || 'todos',
        tipo_prestamo: filtros.tipo_prestamo || 'todos',
        fecha_inicio: filtros.fecha_inicio || '',
        fecha_fin: filtros.fecha_fin || '',
        solicitante: filtros.solicitante || '',
    });

    const aplicarFiltros = () => {
        // Convertir "todos" a cadena vacía para los filtros
        const filtrosLimpios = {
            ...data,
            estado: data.estado === 'todos' ? '' : data.estado,
            tipo_prestamo: data.tipo_prestamo === 'todos' ? '' : data.tipo_prestamo,
        };
        
        router.get(route('admin.prestamos.index'), filtrosLimpios, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const limpiarFiltros = () => {
        router.visit(route('admin.prestamos.index'));
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const obtenerItemPrestado = (prestamo: Prestamo) => {
        if (prestamo.tipo_prestamo === 'expediente' && prestamo.expediente) {
            return `${prestamo.expediente.numero_expediente} - ${prestamo.expediente.titulo}`;
        }
        if (prestamo.tipo_prestamo === 'documento' && prestamo.documento) {
            return prestamo.documento.nombre;
        }
        return 'Item no disponible';
    };

    return (
        <AppLayout>
            <Head title="Préstamos y Consultas" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Préstamos y Consultas</h1>
                        <p className="text-muted-foreground">Gestión de préstamos de expedientes y documentos</p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" asChild>
                            <Link href={route('admin.prestamos.reportes')}>
                                <BarChart3 className="h-4 w-4 mr-2" />
                                Reportes
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('admin.prestamos.create')}>
                                <Plus className="h-4 w-4 mr-2" />
                                Nuevo Préstamo
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Préstamos</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.total_prestamos}</div>
                            <p className="text-xs text-muted-foreground">
                                +{estadisticas.prestamos_este_mes} este mes
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Préstamos Activos</CardTitle>
                            <Clock className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{estadisticas.prestamos_activos}</div>
                            <p className="text-xs text-muted-foreground">
                                En curso actualmente
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Préstamos Vencidos</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{estadisticas.prestamos_vencidos}</div>
                            <p className="text-xs text-muted-foreground">
                                Requieren atención
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Próximos a Vencer</CardTitle>
                            <Calendar className="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-yellow-600">{proximosVencer?.length || 0}</div>
                            <p className="text-xs text-muted-foreground">
                                En los próximos 7 días
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Alertas de próximos a vencer */}
                {proximosVencer && proximosVencer.length > 0 && (
                    <Alert className="border-yellow-200 bg-yellow-50">
                        <AlertTriangle className="h-4 w-4 text-yellow-600" />
                        <AlertDescription>
                            <div className="space-y-2">
                                <p className="font-medium text-yellow-800">
                                    {proximosVencer?.length || 0} préstamos próximos a vencer en los próximos 7 días:
                                </p>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    {proximosVencer?.slice(0, 4).map((prestamo) => (
                                        <div key={prestamo.id} className="text-sm">
                                            <span className="font-medium">
                                                {prestamo.expediente 
                                                    ? `${prestamo.expediente.numero_expediente} - ${prestamo.expediente.titulo}`
                                                    : prestamo.documento?.nombre
                                                }
                                            </span>
                                            <span className="text-yellow-700">
                                                {" "}- {prestamo.solicitante.name} (vence en {prestamo.dias_restantes} días)
                                            </span>
                                        </div>
                                    ))}
                                </div>
                                {proximosVencer && proximosVencer.length > 4 && (
                                    <p className="text-sm text-yellow-700">
                                        Y {(proximosVencer?.length || 0) - 4} más...
                                    </p>
                                )}
                            </div>
                        </AlertDescription>
                    </Alert>
                )}

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Filter className="h-5 w-5" />
                            <span>Filtros</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="estado">Estado</Label>
                                <Select value={data.estado} onValueChange={(value) => setData('estado', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los estados</SelectItem>
                                        <SelectItem value="prestado">Prestado</SelectItem>
                                        <SelectItem value="devuelto">Devuelto</SelectItem>
                                        <SelectItem value="cancelado">Cancelado</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="tipo_prestamo">Tipo</Label>
                                <Select value={data.tipo_prestamo} onValueChange={(value) => setData('tipo_prestamo', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los tipos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los tipos</SelectItem>
                                        <SelectItem value="expediente">Expediente</SelectItem>
                                        <SelectItem value="documento">Documento</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="fecha_inicio">Fecha Inicio</Label>
                                <Input
                                    id="fecha_inicio"
                                    type="date"
                                    value={data.fecha_inicio}
                                    onChange={(e) => setData('fecha_inicio', e.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="fecha_fin">Fecha Fin</Label>
                                <Input
                                    id="fecha_fin"
                                    type="date"
                                    value={data.fecha_fin}
                                    onChange={(e) => setData('fecha_fin', e.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="solicitante">Solicitante</Label>
                                <Input
                                    id="solicitante"
                                    placeholder="Nombre del solicitante"
                                    value={data.solicitante}
                                    onChange={(e) => setData('solicitante', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="flex items-center space-x-2 mt-4">
                            <Button onClick={aplicarFiltros} disabled={processing}>
                                <Search className="h-4 w-4 mr-2" />
                                {processing ? 'Aplicando...' : 'Aplicar Filtros'}
                            </Button>
                            <Button variant="outline" onClick={limpiarFiltros}>
                                Limpiar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabla de préstamos */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Préstamos</CardTitle>
                        <CardDescription>
                            Total: {prestamos?.total || 0} préstamos
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Item</TableHead>
                                    <TableHead>Solicitante</TableHead>
                                    <TableHead>Fecha Préstamo</TableHead>
                                    <TableHead>Fecha Devolución</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Días</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {prestamos?.data && prestamos.data.length > 0 ? (
                                    prestamos.data.map((prestamo) => (
                                        <TableRow key={prestamo.id}>
                                            <TableCell>
                                                <Badge variant="outline" className="capitalize">
                                                    {prestamo.tipo_prestamo === 'expediente' ? (
                                                        <Archive className="h-3 w-3 mr-1" />
                                                    ) : (
                                                        <FileText className="h-3 w-3 mr-1" />
                                                    )}
                                                    {prestamo.tipo_prestamo}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">{obtenerItemPrestado(prestamo)}</p>
                                                    {prestamo.expediente?.ubicacion_fisica && (
                                                        <p className="text-xs text-muted-foreground">
                                                            📍 {prestamo.expediente.ubicacion_fisica}
                                                        </p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">{prestamo.solicitante.name}</p>
                                                    <p className="text-xs text-muted-foreground">{prestamo.solicitante.email}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {formatearFecha(prestamo.fecha_prestamo)}
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <p>{formatearFecha(prestamo.fecha_devolucion_esperada)}</p>
                                                    {prestamo.estado === 'prestado' && prestamo.esta_vencido && (
                                                        <Badge variant="destructive" className="text-xs mt-1">
                                                            Vencido
                                                        </Badge>
                                                    )}
                                                    {prestamo.estado === 'prestado' && !prestamo.esta_vencido && prestamo.dias_restantes !== undefined && prestamo.dias_restantes <= 3 && (
                                                        <Badge variant="outline" className="text-xs mt-1 border-yellow-500 text-yellow-600">
                                                            {prestamo.dias_restantes} días restantes
                                                        </Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge className={`${estadoColors[prestamo.estado]} flex items-center space-x-1`}>
                                                    {estadoIcons[prestamo.estado]}
                                                    <span className="capitalize">{prestamo.estado}</span>
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm">
                                                    {prestamo.fecha_devolucion_real
                                                        ? Math.ceil((new Date(prestamo.fecha_devolucion_real).getTime() - new Date(prestamo.fecha_prestamo).getTime()) / (1000 * 60 * 60 * 24))
                                                        : Math.ceil((new Date().getTime() - new Date(prestamo.fecha_prestamo).getTime()) / (1000 * 60 * 60 * 24))
                                                    } días
                                                    {prestamo.renovaciones > 0 && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {prestamo.renovaciones} renovación(es)
                                                        </p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={route('admin.prestamos.show', prestamo.id)}>
                                                        <Eye className="h-4 w-4 mr-1" />
                                                        Ver
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                                            No se encontraron préstamos con los filtros aplicados.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* Paginación */}
                        {prestamos?.last_page && prestamos.last_page > 1 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-muted-foreground">
                                    Mostrando {prestamos?.data?.length || 0} de {prestamos?.total || 0} resultados
                                </div>
                                <div className="flex items-center space-x-2">
                                    {prestamos?.links?.map((link, index) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? "default" : "outline"}
                                            size="sm"
                                            onClick={() => link.url && router.visit(link.url)}
                                            disabled={!link.url}
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
