import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { 
    ArrowLeft,
    BarChart3,
    PieChart,
    TrendingUp,
    Archive,
    FileCheck,
    Trash2,
    Clock,
    Calendar
} from 'lucide-react';

interface EstadisticasGenerales {
    total_disposiciones: number;
    ejecutadas_este_año: number;
    vencidas_sin_procesar: number;
}

interface DisposicionPorTipo {
    tipo_disposicion: string;
    total: number;
    ejecutadas: number;
}

interface DisposicionPorEstado {
    estado: string;
    total: number;
}

interface TimelineItem {
    mes: string;
    tipo_disposicion: string;
    total: number;
}

interface Props {
    estadisticasGenerales: EstadisticasGenerales;
    disposicionesPorTipo: DisposicionPorTipo[];
    disposicionesPorEstado: DisposicionPorEstado[];
    timelineEjecuciones: TimelineItem[];
}

const tipoLabels: Record<string, string> = {
    conservacion_permanente: 'Conservación Permanente',
    eliminacion_controlada: 'Eliminación Controlada',
    transferencia_historica: 'Transferencia Histórica',
    digitalizacion: 'Digitalización',
    microfilmacion: 'Microfilmación',
};

const estadoLabels: Record<string, string> = {
    pendiente: 'Pendiente',
    en_revision: 'En Revisión',
    aprobado: 'Aprobado',
    rechazado: 'Rechazado',
    ejecutado: 'Ejecutado',
    cancelado: 'Cancelado',
};

const tipoColors: Record<string, string> = {
    conservacion_permanente: 'bg-emerald-500',
    eliminacion_controlada: 'bg-red-500',
    transferencia_historica: 'bg-blue-500',
    digitalizacion: 'bg-indigo-500',
    microfilmacion: 'bg-violet-500',
};

const tipoIcons: Record<string, React.ReactNode> = {
    conservacion_permanente: <Archive className="h-5 w-5" />,
    eliminacion_controlada: <Trash2 className="h-5 w-5" />,
    transferencia_historica: <TrendingUp className="h-5 w-5" />,
    digitalizacion: <FileCheck className="h-5 w-5" />,
    microfilmacion: <FileCheck className="h-5 w-5" />,
};

export default function ReportesDisposiciones({ 
    estadisticasGenerales, 
    disposicionesPorTipo, 
    disposicionesPorEstado, 
    timelineEjecuciones 
}: Props) {
    
    const totalEjecutadas = disposicionesPorTipo.reduce((sum, item) => sum + item.ejecutadas, 0);
    const totalDisposiciones = disposicionesPorTipo.reduce((sum, item) => sum + item.total, 0);

    return (
        <AppLayout>
            <Head title="Reportes de Disposiciones" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/admin/disposiciones">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                                Reportes de Disposiciones Finales
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Estadísticas y análisis del proceso de disposición documental
                            </p>
                        </div>
                    </div>
                </div>

                {/* Estadísticas Generales */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Total Disposiciones</p>
                                    <p className="text-3xl font-bold text-[#2a3d83]">{estadisticasGenerales.total_disposiciones}</p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <BarChart3 className="h-8 w-8 text-[#2a3d83]" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Ejecutadas Este Año</p>
                                    <p className="text-3xl font-bold text-green-600">{estadisticasGenerales.ejecutadas_este_año}</p>
                                </div>
                                <div className="p-3 bg-green-100 rounded-full">
                                    <FileCheck className="h-8 w-8 text-green-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className={estadisticasGenerales.vencidas_sin_procesar > 0 ? 'border-red-200' : ''}>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Vencidas Sin Procesar</p>
                                    <p className={`text-3xl font-bold ${estadisticasGenerales.vencidas_sin_procesar > 0 ? 'text-red-600' : 'text-gray-600'}`}>
                                        {estadisticasGenerales.vencidas_sin_procesar}
                                    </p>
                                </div>
                                <div className={`p-3 rounded-full ${estadisticasGenerales.vencidas_sin_procesar > 0 ? 'bg-red-100' : 'bg-gray-100'}`}>
                                    <Clock className={`h-8 w-8 ${estadisticasGenerales.vencidas_sin_procesar > 0 ? 'text-red-600' : 'text-gray-600'}`} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Disposiciones por Tipo */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <PieChart className="h-5 w-5 text-[#2a3d83]" />
                                <span>Disposiciones por Tipo</span>
                            </CardTitle>
                            <CardDescription>
                                Distribución de disposiciones según su tipo (últimos 12 meses)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {disposicionesPorTipo.length > 0 ? (
                                <div className="space-y-4">
                                    {disposicionesPorTipo.map((item, index) => {
                                        const porcentaje = totalDisposiciones > 0 
                                            ? Math.round((item.total / totalDisposiciones) * 100) 
                                            : 0;
                                        return (
                                            <div key={index} className="space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-2">
                                                        {tipoIcons[item.tipo_disposicion] || <Archive className="h-5 w-5" />}
                                                        <span className="font-medium">
                                                            {tipoLabels[item.tipo_disposicion] || item.tipo_disposicion}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center space-x-2">
                                                        <Badge variant="outline">{item.total} total</Badge>
                                                        <Badge className="bg-green-100 text-green-800">{item.ejecutadas} ejecutadas</Badge>
                                                    </div>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        className={`h-2 rounded-full ${tipoColors[item.tipo_disposicion] || 'bg-gray-500'}`}
                                                        style={{ width: `${porcentaje}%` }}
                                                    />
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    No hay datos de disposiciones en los últimos 12 meses
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Disposiciones por Estado */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <BarChart3 className="h-5 w-5 text-[#2a3d83]" />
                                <span>Disposiciones por Estado</span>
                            </CardTitle>
                            <CardDescription>
                                Estado actual de todas las disposiciones
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {disposicionesPorEstado.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="text-right">Cantidad</TableHead>
                                            <TableHead className="text-right">Porcentaje</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {disposicionesPorEstado.map((item, index) => {
                                            const total = disposicionesPorEstado.reduce((sum, i) => sum + i.total, 0);
                                            const porcentaje = total > 0 ? Math.round((item.total / total) * 100) : 0;
                                            return (
                                                <TableRow key={index}>
                                                    <TableCell>
                                                        <Badge variant="outline">
                                                            {estadoLabels[item.estado] || item.estado}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right font-medium">{item.total}</TableCell>
                                                    <TableCell className="text-right text-gray-500">{porcentaje}%</TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    No hay datos de disposiciones
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Timeline de Ejecuciones */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Calendar className="h-5 w-5 text-[#2a3d83]" />
                            <span>Timeline de Ejecuciones</span>
                        </CardTitle>
                        <CardDescription>
                            Disposiciones ejecutadas en los últimos 12 meses
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {timelineEjecuciones.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Mes</TableHead>
                                        <TableHead>Tipo de Disposición</TableHead>
                                        <TableHead className="text-right">Cantidad</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {timelineEjecuciones.map((item, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="font-medium">{item.mes}</TableCell>
                                            <TableCell>
                                                <Badge variant="outline" className={tipoColors[item.tipo_disposicion]?.replace('bg-', 'border-') || ''}>
                                                    {tipoLabels[item.tipo_disposicion] || item.tipo_disposicion}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">{item.total}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="text-center py-8 text-gray-500">
                                <Calendar className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                                <p>No hay ejecuciones registradas en los últimos 12 meses</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
