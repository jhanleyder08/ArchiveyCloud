import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
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
    Plus, 
    Search,
    Filter,
    Eye,
    CheckCircle,
    Clock,
    AlertTriangle,
    XCircle,
    Archive,
    FileText,
    Calendar,
    User,
    BarChart3,
    AlertCircle as AlertCircleIcon
} from 'lucide-react';

interface DisposicionFinal {
    id: number;
    tipo_disposicion: string;
    estado: string;
    fecha_vencimiento_retencion: string;
    fecha_propuesta: string;
    fecha_aprobacion?: string;
    fecha_ejecucion?: string;
    justificacion: string;
    item_afectado: string;
    tipo_disposicion_label: string;
    estado_label: string;
    expediente?: {
        id: number;
        numero_expediente: string;
        titulo: string;
    };
    documento?: {
        id: number;
        nombre: string;
    };
    responsable: {
        id: number;
        name: string;
    };
    aprobado_por?: {
        id: number;
        name: string;
    };
    dias_para_vencimiento: number;
    esta_vencida: boolean;
}

interface Estadisticas {
    total_disposiciones: number;
    pendientes: number;
    en_revision: number;
    aprobadas: number;
    ejecutadas: number;
    vencidas: number;
}

interface Props {
    disposiciones: {
        data: DisposicionFinal[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    estadisticas: Estadisticas;
    proximasVencer: DisposicionFinal[];
    filtros: {
        tipo_disposicion?: string;
        estado?: string;
        fecha_inicio?: string;
        fecha_fin?: string;
        responsable?: string;
    };
}

const estadoColors: Record<string, string> = {
    pendiente: 'bg-gray-100 text-gray-800 border-gray-200',
    en_revision: 'bg-blue-100 text-blue-800 border-blue-200',
    aprobado: 'bg-green-100 text-green-800 border-green-200',
    rechazado: 'bg-red-100 text-red-800 border-red-200',
    ejecutado: 'bg-purple-100 text-purple-800 border-purple-200',
    cancelado: 'bg-gray-100 text-gray-600 border-gray-200',
};

const tipoColors: Record<string, string> = {
    conservacion_permanente: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    eliminacion_controlada: 'bg-red-100 text-red-800 border-red-200',
    transferencia_historica: 'bg-blue-100 text-blue-800 border-blue-200',
    digitalizacion: 'bg-indigo-100 text-indigo-800 border-indigo-200',
    microfilmacion: 'bg-violet-100 text-violet-800 border-violet-200',
};

const estadoIcons: Record<string, React.ReactNode> = {
    pendiente: <Clock className="h-4 w-4" />,
    en_revision: <Search className="h-4 w-4" />,
    aprobado: <CheckCircle className="h-4 w-4" />,
    rechazado: <XCircle className="h-4 w-4" />,
    ejecutado: <Archive className="h-4 w-4" />,
    cancelado: <AlertTriangle className="h-4 w-4" />,
};

export default function DisposicionesIndex({ disposiciones, estadisticas, proximasVencer, filtros }: Props) {
    const { data, setData, get, processing } = useForm({
        tipo_disposicion: filtros.tipo_disposicion || 'todos',
        estado: filtros.estado || 'todos',
        fecha_inicio: filtros.fecha_inicio || '',
        fecha_fin: filtros.fecha_fin || '',
        responsable: filtros.responsable || '',
    });

    const aplicarFiltros = () => {
        // Convertir "todos" a cadena vacía para los filtros
        const filtrosLimpios = {
            ...data,
            tipo_disposicion: data.tipo_disposicion === 'todos' ? '' : data.tipo_disposicion,
            estado: data.estado === 'todos' ? '' : data.estado,
        };
        
        router.get(route('admin.disposiciones.index'), filtrosLimpios, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const limpiarFiltros = () => {
        router.visit(route('admin.disposiciones.index'));
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    return (
        <AppLayout
            title="Disposiciones Finales"
            renderHeader={() => (
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            Sistema de Disposición Final
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Gestión del ciclo de vida documental y disposiciones finales
                        </p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" asChild>
                            <Link href={route('admin.disposiciones.reportes')}>
                                <BarChart3 className="h-4 w-4 mr-2" />
                                Reportes
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('admin.disposiciones.create')}>
                                <Plus className="h-4 w-4 mr-2" />
                                Nueva Disposición
                            </Link>
                        </Button>
                    </div>
                </div>
            )}
        >
            <Head title="Disposiciones Finales" />

            <div className="space-y-6">
                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <Card>
                        <CardContent className="flex items-center p-6">
                            <div className="flex items-center">
                                <div className="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                                    <Archive className="h-6 w-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.total_disposiciones}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent className="flex items-center p-6">
                            <div className="flex items-center">
                                <div className="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-lg">
                                    <Clock className="h-6 w-6 text-yellow-600" />
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
                                <div className="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                                    <Search className="h-6 w-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">En Revisión</p>
                                    <p className="text-2xl font-bold text-blue-700">{estadisticas.en_revision}</p>
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
                                    <p className="text-sm font-medium text-gray-600">Aprobadas</p>
                                    <p className="text-2xl font-bold text-green-700">{estadisticas.aprobadas}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex items-center p-6">
                            <div className="flex items-center">
                                <div className="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg">
                                    <Archive className="h-6 w-6 text-purple-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Ejecutadas</p>
                                    <p className="text-2xl font-bold text-purple-700">{estadisticas.ejecutadas}</p>
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
                                    <p className="text-sm font-medium text-gray-600">Vencidas</p>
                                    <p className="text-2xl font-bold text-red-700">{estadisticas.vencidas}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Alertas de disposiciones próximas a vencer */}
                {proximasVencer.length > 0 && (
                    <Alert className="border-orange-200 bg-orange-50">
                        <AlertCircleIcon className="h-4 w-4 text-orange-600" />
                        <AlertDescription>
                            <div className="flex items-center justify-between">
                                <div>
                                    <strong className="text-orange-800">Atención:</strong> Hay {proximasVencer.length} disposiciones próximas a vencer o vencidas que requieren atención inmediata.
                                </div>
                                <Button size="sm" variant="outline" className="text-orange-700 border-orange-300 hover:bg-orange-100">
                                    Ver Detalles
                                </Button>
                            </div>
                        </AlertDescription>
                    </Alert>
                )}

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Filter className="h-5 w-5" />
                            <span>Filtros de Búsqueda</span>
                        </CardTitle>
                        <CardDescription>Filtra las disposiciones por tipo, estado, fechas o responsable</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="tipo_disposicion">Tipo de Disposición</Label>
                                <Select value={data.tipo_disposicion} onValueChange={(value) => setData('tipo_disposicion', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los tipos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los tipos</SelectItem>
                                        <SelectItem value="conservacion_permanente">Conservación Permanente</SelectItem>
                                        <SelectItem value="eliminacion_controlada">Eliminación Controlada</SelectItem>
                                        <SelectItem value="transferencia_historica">Transferencia Histórica</SelectItem>
                                        <SelectItem value="digitalizacion">Digitalización</SelectItem>
                                        <SelectItem value="microfilmacion">Microfilmación</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="estado">Estado</Label>
                                <Select value={data.estado} onValueChange={(value) => setData('estado', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="todos">Todos los estados</SelectItem>
                                        <SelectItem value="pendiente">Pendiente</SelectItem>
                                        <SelectItem value="en_revision">En Revisión</SelectItem>
                                        <SelectItem value="aprobado">Aprobado</SelectItem>
                                        <SelectItem value="rechazado">Rechazado</SelectItem>
                                        <SelectItem value="ejecutado">Ejecutado</SelectItem>
                                        <SelectItem value="cancelado">Cancelado</SelectItem>
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
                                <Label htmlFor="responsable">Responsable</Label>
                                <Input
                                    id="responsable"
                                    placeholder="Nombre del responsable"
                                    value={data.responsable}
                                    onChange={(e) => setData('responsable', e.target.value)}
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

                {/* Tabla de Disposiciones */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            <span>Disposiciones Finales ({disposiciones.total})</span>
                            <div className="text-sm text-gray-500">
                                Página {disposiciones.current_page} de {disposiciones.last_page}
                            </div>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {disposiciones.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-16">Estado</TableHead>
                                            <TableHead>Item</TableHead>
                                            <TableHead>Tipo Disposición</TableHead>
                                            <TableHead>Responsable</TableHead>
                                            <TableHead>Fecha Propuesta</TableHead>
                                            <TableHead>Vencimiento</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {disposiciones.data.map((disposicion) => (
                                            <TableRow key={disposicion.id} className={disposicion.esta_vencida ? 'bg-red-50' : ''}>
                                                <TableCell>
                                                    <div className="flex items-center space-x-2">
                                                        <Badge variant="outline" className={estadoColors[disposicion.estado]}>
                                                            <div className="flex items-center space-x-1">
                                                                {estadoIcons[disposicion.estado]}
                                                                <span>{disposicion.estado_label}</span>
                                                            </div>
                                                        </Badge>
                                                        {disposicion.esta_vencida && (
                                                            <AlertTriangle className="h-4 w-4 text-red-500" />
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center space-x-2">
                                                        {disposicion.expediente ? <Archive className="h-4 w-4 text-blue-500" /> : <FileText className="h-4 w-4 text-green-500" />}
                                                        <div>
                                                            <p className="font-medium">
                                                                {disposicion.expediente 
                                                                    ? `${disposicion.expediente.numero_expediente}`
                                                                    : disposicion.documento?.nombre
                                                                }
                                                            </p>
                                                            <p className="text-sm text-gray-500">
                                                                {disposicion.expediente?.titulo}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className={tipoColors[disposicion.tipo_disposicion]}>
                                                        {disposicion.tipo_disposicion_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center space-x-2">
                                                        <User className="h-4 w-4 text-gray-400" />
                                                        <span>{disposicion.responsable.name}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center space-x-2">
                                                        <Calendar className="h-4 w-4 text-gray-400" />
                                                        <span>{formatearFecha(disposicion.fecha_propuesta)}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <div className="flex items-center space-x-2">
                                                            <Calendar className="h-4 w-4 text-gray-400" />
                                                            <span className={disposicion.esta_vencida ? 'text-red-600 font-medium' : ''}>
                                                                {formatearFecha(disposicion.fecha_vencimiento_retencion)}
                                                            </span>
                                                        </div>
                                                        {disposicion.dias_para_vencimiento <= 30 && disposicion.dias_para_vencimiento > 0 && (
                                                            <p className="text-xs text-orange-600">
                                                                Vence en {disposicion.dias_para_vencimiento} días
                                                            </p>
                                                        )}
                                                        {disposicion.esta_vencida && (
                                                            <p className="text-xs text-red-600 font-medium">
                                                                ¡Vencida!
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={route('admin.disposiciones.show', disposicion.id)}>
                                                            <Eye className="h-4 w-4 mr-1" />
                                                            Ver
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <Archive className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    No hay disposiciones finales
                                </h3>
                                <p className="text-gray-500 mb-4">
                                    No se encontraron disposiciones que coincidan con los criterios de búsqueda.
                                </p>
                                <Button asChild>
                                    <Link href={route('admin.disposiciones.create')}>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Crear Primera Disposición
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
