import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    Legend, 
    PieChart, 
    Pie, 
    Cell,
    LineChart,
    Line,
    Area,
    AreaChart,
    ResponsiveContainer
} from 'recharts';
import { 
    FileText, 
    Archive, 
    Activity, 
    TrendingUp, 
    Users, 
    HardDrive,
    Calendar,
    CheckCircle,
    AlertTriangle,
    Clock,
    BarChart3
} from 'lucide-react';

interface Metricas {
    total_expedientes: number;
    total_documentos: number;
    expedientes_abiertos: number;
    expedientes_cerrados: number;
    documentos_mes_actual: number;
    tamaño_total_gb: number;
}

interface ExpedientesPorEstado {
    [estado: string]: Array<{
        mes: string;
        total: number;
    }>;
}

interface DocumentoPorTipo {
    tipo_documento: string;
    total: number;
    tamaño_total: number;
}

interface SerieUsada {
    id: number;
    codigo: string;
    nombre: string;
    expedientes_count: number;
}

interface ActividadReciente {
    id: number;
    usuario: string;
    accion: string;
    tabla_afectada: string;
    descripcion: string;
    fecha: string;
    fecha_relativa: string;
}

interface CumplimientoTrd {
    series_documentadas: number;
    total_series: number;
    subseries_documentadas: number;
    total_subseries: number;
}

interface EstadisticaAlmacenamiento {
    mes: string;
    documentos: number;
    tamaño_total: number;
}

interface Props {
    metricas: Metricas;
    expedientesPorEstado: ExpedientesPorEstado;
    documentosPorTipo: DocumentoPorTipo[];
    seriesMasUsadas: SerieUsada[];
    actividadReciente: ActividadReciente[];
    cumplimientoTrd: CumplimientoTrd;
    estadisticasAlmacenamiento: EstadisticaAlmacenamiento[];
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

const estadoColors: Record<string, string> = {
    abierto: '#22C55E',
    tramite: '#3B82F6',
    revision: '#F59E0B',
    cerrado: '#6B7280',
    archivado: '#8B5CF6',
};

export default function ReportesDashboard({ 
    metricas, 
    expedientesPorEstado, 
    documentosPorTipo, 
    seriesMasUsadas, 
    actividadReciente, 
    cumplimientoTrd,
    estadisticasAlmacenamiento 
}: Props) {
    
    // Procesar datos para gráficos
    const datosExpedientesPorMes = Object.keys(expedientesPorEstado).reduce((acc: any[], estado) => {
        expedientesPorEstado[estado].forEach(item => {
            const existingMonth = acc.find(m => m.mes === item.mes);
            if (existingMonth) {
                existingMonth[estado] = item.total;
            } else {
                acc.push({
                    mes: item.mes,
                    [estado]: item.total,
                });
            }
        });
        return acc;
    }, []).sort((a, b) => a.mes.localeCompare(b.mes));

    const datosAlmacenamientoPorMes = estadisticasAlmacenamiento.map(item => ({
        mes: item.mes,
        documentos: item.documentos,
        tamaño_mb: Math.round(item.tamaño_total / (1024 * 1024)),
    }));

    const porcentajeCumplimiento = Math.round((cumplimientoTrd.series_documentadas / cumplimientoTrd.total_series) * 100);

    return (
        <AppLayout>
            <Head title="Dashboard Ejecutivo - Reportes" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Dashboard Ejecutivo</h1>
                        <p className="text-muted-foreground">Métricas y estadísticas del sistema documental</p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Badge variant="outline" className="flex items-center space-x-1">
                            <Activity className="h-3 w-3" />
                            <span>Tiempo real</span>
                        </Badge>
                    </div>
                </div>

                {/* Métricas principales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Expedientes</CardTitle>
                            <Archive className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metricas.total_expedientes.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                {metricas.expedientes_abiertos} abiertos
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Documentos</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metricas.total_documentos.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                +{metricas.documentos_mes_actual} este mes
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Expedientes Abiertos</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metricas.expedientes_abiertos}</div>
                            <p className="text-xs text-muted-foreground">
                                {Math.round((metricas.expedientes_abiertos / metricas.total_expedientes) * 100)}% del total
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Expedientes Cerrados</CardTitle>
                            <Archive className="h-4 w-4 text-gray-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metricas.expedientes_cerrados}</div>
                            <p className="text-xs text-muted-foreground">
                                {Math.round((metricas.expedientes_cerrados / metricas.total_expedientes) * 100)}% del total
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Almacenamiento</CardTitle>
                            <HardDrive className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metricas.tamaño_total_gb} GB</div>
                            <p className="text-xs text-muted-foreground">
                                {Math.round(metricas.tamaño_total_gb / metricas.total_documentos * 1024)} MB promedio
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Cumplimiento TRD</CardTitle>
                            <BarChart3 className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{porcentajeCumplimiento}%</div>
                            <Progress value={porcentajeCumplimiento} className="mt-1" />
                        </CardContent>
                    </Card>
                </div>

                {/* Gráficos principales */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Expedientes por Estado */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Expedientes por Estado (Últimos 12 meses)</CardTitle>
                            <CardDescription>Evolución temporal de los expedientes</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <AreaChart data={datosExpedientesPorMes}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="mes" />
                                    <YAxis />
                                    <Tooltip />
                                    <Legend />
                                    {Object.keys(expedientesPorEstado).map((estado, index) => (
                                        <Area 
                                            key={estado} 
                                            type="monotone" 
                                            dataKey={estado} 
                                            stackId="1" 
                                            stroke={estadoColors[estado]} 
                                            fill={estadoColors[estado]}
                                            fillOpacity={0.6}
                                        />
                                    ))}
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Documentos por Tipo */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Documentos por Tipo</CardTitle>
                            <CardDescription>Distribución de tipos documentales</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={documentosPorTipo.slice(0, 6)}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="total"
                                    >
                                        {documentosPorTipo.slice(0, 6).map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabs con información detallada */}
                <Tabs defaultValue="series" className="space-y-4">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="series">Series más usadas</TabsTrigger>
                        <TabsTrigger value="actividad">Actividad reciente</TabsTrigger>
                        <TabsTrigger value="cumplimiento">Cumplimiento TRD</TabsTrigger>
                        <TabsTrigger value="almacenamiento">Almacenamiento</TabsTrigger>
                    </TabsList>

                    <TabsContent value="series" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Series Documentales más Utilizadas</CardTitle>
                                <CardDescription>Series con mayor número de expedientes</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {seriesMasUsadas.slice(0, 10).map((serie, index) => (
                                        <div key={serie.id} className="flex items-center justify-between p-3 border rounded">
                                            <div className="flex items-center space-x-3">
                                                <div className="flex items-center justify-center w-8 h-8 bg-primary text-primary-foreground rounded-full text-sm">
                                                    {index + 1}
                                                </div>
                                                <div>
                                                    <h4 className="font-medium">{serie.codigo}</h4>
                                                    <p className="text-sm text-muted-foreground">{serie.nombre}</p>
                                                </div>
                                            </div>
                                            <Badge variant="secondary">
                                                {serie.expedientes_count} expedientes
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="actividad" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Actividad Reciente</CardTitle>
                                <CardDescription>Últimas 20 acciones en el sistema</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3 max-h-96 overflow-y-auto">
                                    {actividadReciente.slice(0, 20).map((actividad) => (
                                        <div key={actividad.id} className="flex items-start space-x-3 pb-3 border-b last:border-b-0">
                                            <div className="flex-shrink-0">
                                                <Activity className="h-4 w-4 text-muted-foreground mt-1" />
                                            </div>
                                            <div className="flex-grow">
                                                <div className="flex items-center justify-between">
                                                    <h4 className="text-sm font-medium">{actividad.accion}</h4>
                                                    <span className="text-xs text-muted-foreground">
                                                        {actividad.fecha_relativa}
                                                    </span>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    Por: {actividad.usuario} en {actividad.tabla_afectada}
                                                </p>
                                                {actividad.descripcion && (
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        {actividad.descripcion}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="cumplimiento" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Cumplimiento de Series</CardTitle>
                                    <CardDescription>Series documentadas vs. total</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold mb-2">
                                        {cumplimientoTrd.series_documentadas} / {cumplimientoTrd.total_series}
                                    </div>
                                    <Progress 
                                        value={(cumplimientoTrd.series_documentadas / cumplimientoTrd.total_series) * 100} 
                                        className="mb-2"
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        {Math.round((cumplimientoTrd.series_documentadas / cumplimientoTrd.total_series) * 100)}% 
                                        de series tienen expedientes
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Cumplimiento de Subseries</CardTitle>
                                    <CardDescription>Subseries documentadas vs. total</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold mb-2">
                                        {cumplimientoTrd.subseries_documentadas} / {cumplimientoTrd.total_subseries}
                                    </div>
                                    <Progress 
                                        value={(cumplimientoTrd.subseries_documentadas / cumplimientoTrd.total_subseries) * 100} 
                                        className="mb-2"
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        {Math.round((cumplimientoTrd.subseries_documentadas / cumplimientoTrd.total_subseries) * 100)}% 
                                        de subseries tienen expedientes
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="almacenamiento" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Crecimiento de Almacenamiento</CardTitle>
                                <CardDescription>Evolución del almacenamiento por mes</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={datosAlmacenamientoPorMes}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="mes" />
                                        <YAxis yAxisId="left" />
                                        <YAxis yAxisId="right" orientation="right" />
                                        <Tooltip />
                                        <Legend />
                                        <Bar yAxisId="left" dataKey="documentos" fill="#8884d8" name="Documentos" />
                                        <Line yAxisId="right" type="monotone" dataKey="tamaño_mb" stroke="#82ca9d" name="Tamaño (MB)" />
                                    </LineChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
