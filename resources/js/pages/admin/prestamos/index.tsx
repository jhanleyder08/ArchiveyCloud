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
    tipo_solicitante: 'usuario' | 'externo';
    expediente?: {
        codigo: string;
        titulo: string;
        ubicacion_fisica: string;
    };
    documento?: {
        titulo: string;
        expediente?: {
            codigo: string;
            titulo: string;
        };
    };
    solicitante?: {
        name: string;
        email: string;
    };
    datos_solicitante_externo?: {
        nombre_completo: string;
        tipo_documento: string;
        numero_documento: string;
        email: string;
        telefono?: string;
        cargo?: string;
        dependencia?: string;
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
    // Campos calculados del backend
    nombre_solicitante?: string;
    contacto_solicitante?: string;
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
        
        router.get('/admin/prestamos', filtrosLimpios, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const limpiarFiltros = () => {
        router.visit('/admin/prestamos');
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
            return `${prestamo.expediente.codigo} - ${prestamo.expediente.titulo}`;
        }
        if (prestamo.tipo_prestamo === 'documento' && prestamo.documento) {
            return prestamo.documento.titulo;
        }
        return 'Item no disponible';
    };

    const obtenerNombreSolicitante = (prestamo: Prestamo) => {
        if (prestamo.tipo_solicitante === 'usuario' && prestamo.solicitante) {
            return prestamo.solicitante.name;
        }
        if (prestamo.tipo_solicitante === 'externo' && prestamo.datos_solicitante_externo) {
            return prestamo.datos_solicitante_externo.nombre_completo;
        }
        return prestamo.nombre_solicitante || 'Solicitante no disponible';
    };

    const obtenerContactoSolicitante = (prestamo: Prestamo) => {
        if (prestamo.tipo_solicitante === 'usuario' && prestamo.solicitante) {
            return prestamo.solicitante.email;
        }
        if (prestamo.tipo_solicitante === 'externo' && prestamo.datos_solicitante_externo) {
            return prestamo.datos_solicitante_externo.email;
        }
        return prestamo.contacto_solicitante || 'No disponible';
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Administración', href: '#' },
            { title: 'Préstamos y Consultas', href: '/admin/prestamos' },
        ]}>
            <Head title="Préstamos y Consultas" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between pt-4">
                    <div className="flex items-center gap-2">
                        <FileText className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Gestión de Préstamos y Consultas
                        </h1>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" asChild>
                            <Link href="/admin/prestamos/reportes">
                                <BarChart3 className="h-4 w-4 mr-2" />
                                Reportes
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/admin/prestamos/create">
                                <Plus className="h-4 w-4 mr-2" />
                                Nuevo Préstamo
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Estadísticas */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Préstamos</p>
                                <p className="text-2xl font-semibold text-gray-900">{estadisticas.total_prestamos}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Préstamos Activos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.prestamos_activos}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Clock className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Préstamos Vencidos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{estadisticas.prestamos_vencidos}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <AlertTriangle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Próximos a Vencer</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{proximosVencer?.length || 0}</p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Calendar className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
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
                                                {" "}- {prestamo.solicitante?.name || 'Usuario no disponible'} (vence en {prestamo.dias_restantes} días)
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
                <div className="bg-white rounded-lg border p-6">
                    <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div className="relative flex-1 sm:w-80">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    id="solicitante"
                                    placeholder="Buscar por solicitante..."
                                    value={data.solicitante}
                                    onChange={(e) => setData('solicitante', e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Select value={data.estado} onValueChange={(value) => setData('estado', value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los estados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="todos">Todos los estados</SelectItem>
                                    <SelectItem value="prestado">Prestado</SelectItem>
                                    <SelectItem value="devuelto">Devuelto</SelectItem>
                                    <SelectItem value="cancelado">Cancelado</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={data.tipo_prestamo} onValueChange={(value) => setData('tipo_prestamo', value)}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Todos los tipos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="todos">Todos los tipos</SelectItem>
                                    <SelectItem value="expediente">Expediente</SelectItem>
                                    <SelectItem value="documento">Documento</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={aplicarFiltros} disabled={processing} className="bg-[#2a3d83] hover:bg-[#1e2b5f]">
                                <Search className="h-4 w-4 mr-2" />
                                {processing ? 'Aplicando...' : 'Aplicar Filtros'}
                            </Button>
                            <Button variant="outline" onClick={limpiarFiltros}>
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Tabla de préstamos */}
                <div className="bg-white rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Tipo</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Item</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Solicitante</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Fecha Préstamo</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Fecha Devolución</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Estado</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Días</th>
                                    <th className="text-left py-3 px-6 text-sm font-medium text-gray-900">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {prestamos?.data && prestamos.data.length > 0 ? (
                                    prestamos.data.map((prestamo) => (
                                        <tr key={prestamo.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="py-4 px-6">
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-[#2a3d83] capitalize">
                                                    {prestamo.tipo_prestamo === 'expediente' ? (
                                                        <Archive className="h-3 w-3 mr-1" />
                                                    ) : (
                                                        <FileText className="h-3 w-3 mr-1" />
                                                    )}
                                                    {prestamo.tipo_prestamo}
                                                </span>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">{obtenerItemPrestado(prestamo)}</p>
                                                    {prestamo.expediente?.ubicacion_fisica && (
                                                        <p className="text-xs text-gray-500">
                                                            📍 {prestamo.expediente.ubicacion_fisica}
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">{obtenerNombreSolicitante(prestamo)}</p>
                                                    <p className="text-xs text-gray-500">{obtenerContactoSolicitante(prestamo)}</p>
                                                    {prestamo.tipo_solicitante === 'externo' && (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 mt-1">
                                                            Externo
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6 text-sm text-gray-600">
                                                {formatearFecha(prestamo.fecha_prestamo)}
                                            </td>
                                            <td className="py-4 px-6">
                                                <div>
                                                    <p className="text-sm text-gray-600">{formatearFecha(prestamo.fecha_devolucion_esperada)}</p>
                                                    {prestamo.estado === 'prestado' && prestamo.esta_vencido && (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1">
                                                            Vencido
                                                        </span>
                                                    )}
                                                    {prestamo.estado === 'prestado' && !prestamo.esta_vencido && prestamo.dias_restantes !== undefined && prestamo.dias_restantes <= 3 && (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border-yellow-500 text-yellow-600 mt-1">
                                                            {prestamo.dias_restantes} días restantes
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${estadoColors[prestamo.estado]}`}>
                                                    {estadoIcons[prestamo.estado]}
                                                    <span className="ml-1 capitalize">{prestamo.estado}</span>
                                                </span>
                                            </td>
                                            <td className="py-4 px-6">
                                                <div className="text-sm text-gray-900">
                                                    {prestamo.fecha_devolucion_real
                                                        ? Math.ceil((new Date(prestamo.fecha_devolucion_real).getTime() - new Date(prestamo.fecha_prestamo).getTime()) / (1000 * 60 * 60 * 24))
                                                        : Math.ceil((new Date().getTime() - new Date(prestamo.fecha_prestamo).getTime()) / (1000 * 60 * 60 * 24))
                                                    } días
                                                    {prestamo.renovaciones > 0 && (
                                                        <p className="text-xs text-gray-500">
                                                            {prestamo.renovaciones} renovación(es)
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-4 px-6">
                                                <Link href={`/admin/prestamos/${prestamo.id}`}>
                                                    <button className="p-2 rounded-md text-[#2a3d83] hover:text-[#1e2b5f] hover:bg-blue-50 transition-colors">
                                                        <Eye className="h-4 w-4" />
                                                    </button>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={8} className="py-8 px-6 text-center text-gray-500">
                                            No se encontraron préstamos con los filtros aplicados.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Paginación */}
                    {prestamos?.data && prestamos.data.length > 0 && prestamos?.last_page && prestamos.last_page > 1 && (
                        <div className="flex items-center justify-between bg-white border rounded-lg px-6 py-3">
                            <div className="text-sm text-gray-600">
                                Mostrando <span className="font-medium">{prestamos?.data?.length || 0}</span> de{' '}
                                <span className="font-medium">{prestamos?.total || 0}</span> resultados
                            </div>
                            <div className="flex items-center gap-2">
                                {prestamos?.links?.map((link, index) => {
                                    if (link.label.includes('Previous')) {
                                        return (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                preserveState
                                                className={`px-3 py-2 border border-gray-300 rounded-md text-sm font-medium ${
                                                    link.url 
                                                        ? 'text-gray-700 hover:bg-gray-50' 
                                                        : 'text-gray-300 cursor-not-allowed'
                                                }`}
                                            >
                                                Anterior
                                            </Link>
                                        );
                                    }
                                    
                                    if (link.label.includes('Next')) {
                                        return (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                preserveState
                                                className={`px-3 py-2 border border-gray-300 rounded-md text-sm font-medium ${
                                                    link.url 
                                                        ? 'text-gray-700 hover:bg-gray-50' 
                                                        : 'text-gray-300 cursor-not-allowed'
                                                }`}
                                            >
                                                Siguiente
                                            </Link>
                                        );
                                    }

                                    // Number pages
                                    if (!isNaN(Number(link.label))) {
                                        return (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                preserveState
                                                className={`px-3 py-2 rounded-md text-sm font-medium ${
                                                    link.active
                                                        ? 'bg-[#2a3d83] text-white'
                                                        : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                                }`}
                                            >
                                                {link.label}
                                            </Link>
                                        );
                                    }

                                    return null;
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
