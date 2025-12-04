import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    ArrowLeft, 
    TrendingUp, 
    AlertTriangle,
    CheckCircle,
    Clock,
    FileText,
    Archive,
    Users,
    Calendar,
    BarChart3
} from 'lucide-react';

interface Estadisticas {
    total: number;
    activos: number;
    vencidos: number;
    devueltos: number;
    proximos_vencer: number;
}

interface PrestamoMes {
    mes: string;
    total: number;
}

interface ExpedientePrestado {
    expediente: string;
    total: number;
}

interface UsuarioSolicitante {
    usuario: string;
    email: string;
    total: number;
}

interface PrestamoVencido {
    id: number;
    tipo: string;
    item: string;
    solicitante: string;
    fecha_prestamo: string;
    fecha_devolucion_esperada: string;
    dias_vencido: number;
}

interface Props {
    estadisticas: Estadisticas;
    prestamosPorTipo: Record<string, number>;
    prestamosPorEstado: Record<string, number>;
    prestamosPorMes: PrestamoMes[];
    expedientesMasPrestados: ExpedientePrestado[];
    usuariosMasSolicitan: UsuarioSolicitante[];
    prestamosVencidos: PrestamoVencido[];
    tiempoPromedio: number;
    fechaInicio: string;
    fechaFin: string;
}

export default function PrestamosReportes({
    estadisticas,
    prestamosPorTipo,
    prestamosPorEstado,
    prestamosPorMes,
    expedientesMasPrestados,
    usuariosMasSolicitan,
    prestamosVencidos,
    tiempoPromedio,
}: Props) {
    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const calcularPorcentaje = (valor: number, total: number) => {
        return total > 0 ? ((valor / total) * 100).toFixed(1) : '0';
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Administración', href: '#' },
            { title: 'Préstamos', href: '/admin/prestamos' },
            { title: 'Reportes', href: '/admin/prestamos/reportes' },
        ]}>
            <Head title="Reportes de Préstamos" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/prestamos">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Reportes y Estadísticas</h1>
                            <p className="text-sm text-gray-500">
                                Análisis completo del sistema de préstamos
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" asChild>
                        <a href="/admin/prestamos/reportes/pdf" target="_blank">
                            <BarChart3 className="h-4 w-4 mr-2" />
                            Exportar PDF
                        </a>
                    </Button>
                </div>

                {/* Estadísticas Generales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Total Préstamos</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.total}</p>
                                </div>
                                <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <FileText className="h-6 w-6 text-blue-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Activos</p>
                                    <p className="text-2xl font-bold text-blue-600">{estadisticas.activos}</p>
                                    <p className="text-xs text-gray-500">
                                        {calcularPorcentaje(estadisticas.activos, estadisticas.total)}% del total
                                    </p>
                                </div>
                                <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <Clock className="h-6 w-6 text-blue-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Vencidos</p>
                                    <p className="text-2xl font-bold text-red-600">{estadisticas.vencidos}</p>
                                    <p className="text-xs text-gray-500">
                                        {calcularPorcentaje(estadisticas.vencidos, estadisticas.total)}% del total
                                    </p>
                                </div>
                                <div className="h-12 w-12 bg-red-100 rounded-full flex items-center justify-center">
                                    <AlertTriangle className="h-6 w-6 text-red-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Devueltos</p>
                                    <p className="text-2xl font-bold text-green-600">{estadisticas.devueltos}</p>
                                    <p className="text-xs text-gray-500">
                                        {calcularPorcentaje(estadisticas.devueltos, estadisticas.total)}% del total
                                    </p>
                                </div>
                                <div className="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <CheckCircle className="h-6 w-6 text-green-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Por Vencer</p>
                                    <p className="text-2xl font-bold text-yellow-600">{estadisticas.proximos_vencer}</p>
                                    <p className="text-xs text-gray-500">Próximos 7 días</p>
                                </div>
                                <div className="h-12 w-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <Calendar className="h-6 w-6 text-yellow-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Gráficos y Análisis */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Préstamos por Tipo */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Préstamos por Tipo</CardTitle>
                            <CardDescription>Distribución de expedientes vs documentos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {Object.entries(prestamosPorTipo).map(([tipo, total]) => {
                                    const porcentaje = calcularPorcentaje(total, estadisticas.total);
                                    return (
                                        <div key={tipo}>
                                            <div className="flex items-center justify-between mb-2">
                                                <div className="flex items-center space-x-2">
                                                    {tipo === 'expediente' ? (
                                                        <Archive className="h-4 w-4 text-blue-600" />
                                                    ) : (
                                                        <FileText className="h-4 w-4 text-purple-600" />
                                                    )}
                                                    <span className="text-sm font-medium capitalize">{tipo}</span>
                                                </div>
                                                <span className="text-sm font-bold">{total} ({porcentaje}%)</span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div 
                                                    className={`h-2 rounded-full ${tipo === 'expediente' ? 'bg-blue-600' : 'bg-purple-600'}`}
                                                    style={{ width: `${porcentaje}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Préstamos por Estado */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Préstamos por Estado</CardTitle>
                            <CardDescription>Estado actual de los préstamos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {Object.entries(prestamosPorEstado).map(([estado, total]) => {
                                    const porcentaje = calcularPorcentaje(total, estadisticas.total);
                                    const color = estado === 'prestado' ? 'blue' : estado === 'devuelto' ? 'green' : 'red';
                                    return (
                                        <div key={estado}>
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-sm font-medium capitalize">{estado}</span>
                                                <span className="text-sm font-bold">{total} ({porcentaje}%)</span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div 
                                                    className={`h-2 rounded-full bg-${color}-600`}
                                                    style={{ width: `${porcentaje}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Tendencia Mensual */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <TrendingUp className="h-5 w-5" />
                            <span>Tendencia de Préstamos (Últimos 6 Meses)</span>
                        </CardTitle>
                        <CardDescription>Evolución mensual del número de préstamos</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="h-64 flex items-end justify-between space-x-2">
                            {prestamosPorMes.map((item, index) => {
                                const maxTotal = Math.max(...prestamosPorMes.map(p => p.total));
                                const altura = (item.total / maxTotal) * 100;
                                return (
                                    <div key={index} className="flex-1 flex flex-col items-center">
                                        <div className="w-full flex flex-col items-center justify-end h-full">
                                            <span className="text-xs font-bold mb-1">{item.total}</span>
                                            <div 
                                                className="w-full bg-blue-600 rounded-t transition-all hover:bg-blue-700"
                                                style={{ height: `${altura}%`, minHeight: '20px' }}
                                            />
                                        </div>
                                        <span className="text-xs text-gray-600 mt-2">{item.mes}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                {/* Top Rankings */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Top Expedientes */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Archive className="h-5 w-5" />
                                <span>Expedientes Más Prestados</span>
                            </CardTitle>
                            <CardDescription>Top 10 expedientes con más préstamos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {expedientesMasPrestados.length > 0 ? (
                                    expedientesMasPrestados.map((item, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center space-x-3">
                                                <div className="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                                                    <span className="text-sm font-bold text-blue-600">{index + 1}</span>
                                                </div>
                                                <span className="text-sm font-medium text-gray-900">{item.expediente}</span>
                                            </div>
                                            <span className="text-sm font-bold text-blue-600">{item.total}</span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-500 text-center py-4">No hay datos disponibles</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Top Usuarios */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Users className="h-5 w-5" />
                                <span>Usuarios Más Activos</span>
                            </CardTitle>
                            <CardDescription>Top 10 usuarios con más solicitudes</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {usuariosMasSolicitan.length > 0 ? (
                                    usuariosMasSolicitan.map((item, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center space-x-3">
                                                <div className="flex items-center justify-center w-8 h-8 bg-purple-100 rounded-full">
                                                    <span className="text-sm font-bold text-purple-600">{index + 1}</span>
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">{item.usuario}</p>
                                                    <p className="text-xs text-gray-500">{item.email}</p>
                                                </div>
                                            </div>
                                            <span className="text-sm font-bold text-purple-600">{item.total}</span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-500 text-center py-4">No hay datos disponibles</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Métrica Adicional */}
                <Card>
                    <CardHeader>
                        <CardTitle>Tiempo Promedio de Préstamo</CardTitle>
                        <CardDescription>Duración promedio desde el préstamo hasta la devolución</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-center py-8">
                            <div className="text-center">
                                <p className="text-5xl font-bold text-blue-600">{tiempoPromedio}</p>
                                <p className="text-lg text-gray-600 mt-2">días promedio</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Préstamos Vencidos */}
                {prestamosVencidos.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2 text-red-600">
                                <AlertTriangle className="h-5 w-5" />
                                <span>Préstamos Vencidos - Requieren Atención</span>
                            </CardTitle>
                            <CardDescription>Listado de préstamos que han superado su fecha de devolución</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b">
                                        <tr>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">ID</th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Item</th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Solicitante</th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Fecha Préstamo</th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Debía Devolver</th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Días Vencido</th>
                                            <th className="text-left py-3 px-4 text-sm font-medium text-gray-900">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {prestamosVencidos.map((prestamo) => (
                                            <tr key={prestamo.id} className="hover:bg-gray-50">
                                                <td className="py-3 px-4 text-sm">#{prestamo.id}</td>
                                                <td className="py-3 px-4 text-sm font-medium">{prestamo.item}</td>
                                                <td className="py-3 px-4 text-sm">{prestamo.solicitante}</td>
                                                <td className="py-3 px-4 text-sm">{formatearFecha(prestamo.fecha_prestamo)}</td>
                                                <td className="py-3 px-4 text-sm">{formatearFecha(prestamo.fecha_devolucion_esperada)}</td>
                                                <td className="py-3 px-4">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        {prestamo.dias_vencido} días
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/prestamos/${prestamo.id}`}>
                                                            Ver
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
