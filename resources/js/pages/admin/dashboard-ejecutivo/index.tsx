import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    AreaChart,
    Area,
    LineChart,
    Line,
} from 'recharts';
import {
    TrendingUp,
    TrendingDown,
    Users,
    FileText,
    Folder,
    Database,
    AlertTriangle,
    CheckCircle,
    Clock,
    Activity,
    BarChart3,
    PieChart as PieChartIcon,
    Download,
    RefreshCw,
} from 'lucide-react';

interface MetricasGenerales {
    total_documentos: number;
    total_expedientes: number;
    total_usuarios: number;
    total_series: number;
    almacenamiento_total: number;
    indices_generados: number;
}

interface KPIsCriticos {
    documentos_procesados_semana: number;
    expedientes_creados_semana: number;
    expedientes_vencidos: number;
    expedientes_proximo_vencimiento: number;
    workflows_pendientes: number;
    workflows_vencidos: number;
    prestamos_activos: number;
    prestamos_vencidos: number;
    disposiciones_pendientes: number;
    disposiciones_vencidas: number;
}

interface AlertasCriticas {
    notificaciones_criticas: any[];
    expedientes_urgentes: any[];
    workflows_urgentes: any[];
}

interface Cumplimiento {
    porcentaje_cumplimiento_general: number;
    expedientes_en_regla: number;
    expedientes_con_alertas: number;
    cumplimiento_por_series: any[];
}

interface Props {
    metricas_generales: MetricasGenerales;
    kpis_criticos: KPIsCriticos;
    alertas_criticas: AlertasCriticas;
    cumplimiento: Cumplimiento;
    tendencias: any;
    usuarios_activos: any[];
    distribucion_trabajo: any;
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

export default function DashboardEjecutivo({
    metricas_generales,
    kpis_criticos,
    alertas_criticas,
    cumplimiento,
    tendencias,
    usuarios_activos,
    distribucion_trabajo,
}: Props) {
    const [autoRefresh, setAutoRefresh] = useState(false);
    const [lastRefresh, setLastRefresh] = useState(new Date());


    // Auto-refresh cada 5 minutos si está habilitado
    useEffect(() => {
        let interval: NodeJS.Timeout;
        if (autoRefresh) {
            interval = setInterval(() => {
                window.location.reload();
            }, 300000); // 5 minutos
        }
        return () => clearInterval(interval);
    }, [autoRefresh]);

    const formatNumber = (num: number) => {
        return new Intl.NumberFormat('es-CO').format(num);
    };

    const getCumplimientoColor = (porcentaje: number) => {
        if (porcentaje >= 90) return 'text-green-600';
        if (porcentaje >= 70) return 'text-yellow-600';
        return 'text-red-600';
    };

    const getPrioridadColor = (prioridad: string) => {
        switch (prioridad) {
            case 'critica': return 'destructive';
            case 'alta': return 'destructive';
            case 'media': return 'secondary';
            default: return 'outline';
        }
    };

    return (
        <AppLayout>
            <Head title="Dashboard Ejecutivo" />

            {/* Header personalizado */}
            <div className="bg-white border-b border-gray-200 px-4 py-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            Dashboard Ejecutivo
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Visión integral del sistema de gestión documental
                        </p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <span className="text-xs text-gray-500">
                            Actualizado: {lastRefresh.toLocaleTimeString()}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setAutoRefresh(!autoRefresh)}
                            className={autoRefresh ? 'bg-green-50 border-green-200' : ''}
                        >
                            <RefreshCw className={`h-4 w-4 ${autoRefresh ? 'animate-spin' : ''}`} />
                            {autoRefresh ? 'Auto' : 'Manual'}
                        </Button>
                        <a 
                            href={`/admin/dashboard-ejecutivo/exportar-pdf?t=${Date.now()}`}
                            target="_blank"
                            className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
                        >
                            <Download className="h-4 w-4 mr-2" />
                            Exportar PDF
                        </a>
                    </div>
                </div>
            </div>

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Métricas Generales */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-6 mb-8">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Documentos</CardTitle>
                                <FileText className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(metricas_generales.total_documentos)}</div>
                                <p className="text-xs text-muted-foreground">
                                    +{formatNumber(kpis_criticos.documentos_procesados_semana)} esta semana
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Expedientes</CardTitle>
                                <Folder className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(metricas_generales.total_expedientes)}</div>
                                <p className="text-xs text-muted-foreground">
                                    +{formatNumber(kpis_criticos.expedientes_creados_semana)} esta semana
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Usuarios Activos</CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(metricas_generales.total_usuarios)}</div>
                                <p className="text-xs text-muted-foreground">
                                    {formatNumber(usuarios_activos.length)} productivos este mes
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Series Documentales</CardTitle>
                                <Database className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatNumber(metricas_generales.total_series)}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Almacenamiento</CardTitle>
                                <BarChart3 className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{metricas_generales.almacenamiento_total} GB</div>
                                <p className="text-xs text-muted-foreground">
                                    Proy. 12m: {tendencias.proyeccion_almacenamiento?.proyeccion_12_meses} GB
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Cumplimiento</CardTitle>
                                <CheckCircle className={`h-4 w-4 ${getCumplimientoColor(cumplimiento.porcentaje_cumplimiento_general)}`} />
                            </CardHeader>
                            <CardContent>
                                <div className={`text-2xl font-bold ${getCumplimientoColor(cumplimiento.porcentaje_cumplimiento_general)}`}>
                                    {cumplimiento.porcentaje_cumplimiento_general}%
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Alertas Críticas */}
                    {(kpis_criticos.expedientes_vencidos > 0 || kpis_criticos.workflows_vencidos > 0 || kpis_criticos.prestamos_vencidos > 0) && (
                        <Alert className="mb-8 border-red-200 bg-red-50">
                            <AlertTriangle className="h-4 w-4 text-red-600" />
                            <AlertDescription>
                                <div className="flex items-center space-x-4">
                                    <span className="font-medium text-red-800">Atención requerida:</span>
                                    {kpis_criticos.expedientes_vencidos > 0 && (
                                        <span className="text-red-700">
                                            {kpis_criticos.expedientes_vencidos} expedientes vencidos
                                        </span>
                                    )}
                                    {kpis_criticos.workflows_vencidos > 0 && (
                                        <span className="text-red-700">
                                            {kpis_criticos.workflows_vencidos} workflows vencidos
                                        </span>
                                    )}
                                    {kpis_criticos.prestamos_vencidos > 0 && (
                                        <span className="text-red-700">
                                            {kpis_criticos.prestamos_vencidos} préstamos vencidos
                                        </span>
                                    )}
                                </div>
                            </AlertDescription>
                        </Alert>
                    )}

                    <Tabs defaultValue="resumen" className="space-y-6">
                        <TabsList className="grid w-full grid-cols-5">
                            <TabsTrigger value="resumen">Resumen Ejecutivo</TabsTrigger>
                            <TabsTrigger value="cumplimiento">Cumplimiento</TabsTrigger>
                            <TabsTrigger value="productividad">Productividad</TabsTrigger>
                            <TabsTrigger value="tendencias">Tendencias</TabsTrigger>
                            <TabsTrigger value="alertas">Alertas</TabsTrigger>
                        </TabsList>

                        {/* Tab Resumen Ejecutivo */}
                        <TabsContent value="resumen" className="space-y-6">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* KPIs Críticos */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>KPIs del Sistema</CardTitle>
                                        <CardDescription>Indicadores clave de rendimiento</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm">Workflows Pendientes</span>
                                                    <Badge variant={kpis_criticos.workflows_pendientes > 10 ? 'destructive' : 'secondary'}>
                                                        {kpis_criticos.workflows_pendientes}
                                                    </Badge>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm">Préstamos Activos</span>
                                                    <Badge variant="outline">{kpis_criticos.prestamos_activos}</Badge>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm">Disposiciones Pendientes</span>
                                                    <Badge variant={kpis_criticos.disposiciones_pendientes > 5 ? 'destructive' : 'secondary'}>
                                                        {kpis_criticos.disposiciones_pendientes}
                                                    </Badge>
                                                </div>
                                            </div>
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm">Exp. Próx. Vencimiento</span>
                                                    <Badge variant={kpis_criticos.expedientes_proximo_vencimiento > 0 ? 'secondary' : 'outline'}>
                                                        {kpis_criticos.expedientes_proximo_vencimiento}
                                                    </Badge>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm">Expedientes Vencidos</span>
                                                    <Badge variant={kpis_criticos.expedientes_vencidos > 0 ? 'destructive' : 'outline'}>
                                                        {kpis_criticos.expedientes_vencidos}
                                                    </Badge>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm">Préstamos Vencidos</span>
                                                    <Badge variant={kpis_criticos.prestamos_vencidos > 0 ? 'destructive' : 'outline'}>
                                                        {kpis_criticos.prestamos_vencidos}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Distribución de Trabajo */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Distribución de Expedientes</CardTitle>
                                        <CardDescription>Por estado actual</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={200}>
                                            <PieChart>
                                                <Pie
                                                    data={distribucion_trabajo.expedientes_por_estado}
                                                    cx="50%"
                                                    cy="50%"
                                                    outerRadius={80}
                                                    fill="#8884d8"
                                                    dataKey="total"
                                                    nameKey="estado"
                                                >
                                                    {distribucion_trabajo.expedientes_por_estado.map((entry: any, index: number) => (
                                                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                    ))}
                                                </Pie>
                                                <Tooltip />
                                            </PieChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Crecimiento Mensual */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Tendencia de Crecimiento</CardTitle>
                                    <CardDescription>Documentos y expedientes por mes</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <AreaChart data={tendencias.crecimiento_mensual}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="mes" />
                                            <YAxis />
                                            <Tooltip />
                                            <Area type="monotone" dataKey="documentos" stackId="1" stroke="#8884d8" fill="#8884d8" />
                                            <Area type="monotone" dataKey="expedientes" stackId="1" stroke="#82ca9d" fill="#82ca9d" />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab Cumplimiento */}
                        <TabsContent value="cumplimiento" className="space-y-6">
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Estado General</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-center space-y-4">
                                            <div className={`text-4xl font-bold ${getCumplimientoColor(cumplimiento.porcentaje_cumplimiento_general)}`}>
                                                {cumplimiento.porcentaje_cumplimiento_general}%
                                            </div>
                                            <Progress value={cumplimiento.porcentaje_cumplimiento_general} className="w-full" />
                                            <div className="text-sm text-gray-600">
                                                {cumplimiento.expedientes_en_regla} de {cumplimiento.expedientes_en_regla + cumplimiento.expedientes_con_alertas} expedientes en regla
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card className="col-span-2">
                                    <CardHeader>
                                        <CardTitle>Cumplimiento por Series</CardTitle>
                                        <CardDescription>Top 10 series documentales</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={250}>
                                            <BarChart data={cumplimiento.cumplimiento_por_series}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis 
                                                    dataKey="nombre" 
                                                    angle={-45}
                                                    textAnchor="end"
                                                    height={100}
                                                />
                                                <YAxis />
                                                <Tooltip />
                                                <Bar dataKey="porcentaje" fill="#8884d8" />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        {/* Tab Productividad */}
                        <TabsContent value="productividad" className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Usuarios Más Activos</CardTitle>
                                    <CardDescription>Actividad de los últimos 30 días</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {usuarios_activos.slice(0, 10).map((usuario, index) => (
                                            <div key={usuario.id} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    <div className="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                                                        <span className="text-sm font-medium text-blue-800">#{index + 1}</span>
                                                    </div>
                                                    <div>
                                                        <div className="font-medium">{usuario.name}</div>
                                                        <div className="text-sm text-gray-500">{usuario.email}</div>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="font-medium">
                                                        {parseInt(usuario.documentos_creados) + parseInt(usuario.expedientes_gestionados) + parseInt(usuario.workflows_iniciados)} acciones
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {usuario.documentos_creados} docs, {usuario.expedientes_gestionados} exp, {usuario.workflows_iniciados} wf
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Tab Tendencias */}
                        <TabsContent value="tendencias" className="space-y-6">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Proyección de Almacenamiento</CardTitle>
                                        <CardDescription>Estimación de crecimiento</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            <div className="flex justify-between">
                                                <span>Actual</span>
                                                <span className="font-medium">{tendencias.proyeccion_almacenamiento?.actual_gb} GB</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>3 meses</span>
                                                <span className="font-medium">{tendencias.proyeccion_almacenamiento?.proyeccion_3_meses} GB</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>6 meses</span>
                                                <span className="font-medium">{tendencias.proyeccion_almacenamiento?.proyeccion_6_meses} GB</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>12 meses</span>
                                                <span className="font-medium text-orange-600">
                                                    {tendencias.proyeccion_almacenamiento?.proyeccion_12_meses} GB
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Tipos de Documentos</CardTitle>
                                        <CardDescription>Distribución por extensión</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={200}>
                                            <BarChart data={distribucion_trabajo.documentos_por_tipo}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="extension" />
                                                <YAxis />
                                                <Tooltip />
                                                <Bar dataKey="total" fill="#82ca9d" />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        {/* Tab Alertas */}
                        <TabsContent value="alertas" className="space-y-6">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center space-x-2">
                                            <AlertTriangle className="h-5 w-5 text-red-500" />
                                            <span>Expedientes Urgentes</span>
                                        </CardTitle>
                                        <CardDescription>Próximos a vencer (7 días)</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            {alertas_criticas.expedientes_urgentes.length > 0 ? (
                                                alertas_criticas.expedientes_urgentes.map((expediente) => (
                                                    <div key={expediente.id} className="flex items-center justify-between p-3 border border-orange-200 rounded-lg bg-orange-50">
                                                        <div>
                                                            <div className="font-medium">{expediente.codigo}</div>
                                                            <div className="text-sm text-gray-600">{expediente.asunto}</div>
                                                        </div>
                                                        <div className="text-right">
                                                            <Badge variant="destructive">Urgente</Badge>
                                                            <div className="text-xs text-gray-500 mt-1">
                                                                {expediente.serie_documental?.nombre}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))
                                            ) : (
                                                <div className="text-center py-8 text-gray-500">
                                                    <CheckCircle className="h-12 w-12 mx-auto mb-4 text-green-500" />
                                                    <p>No hay expedientes próximos a vencer</p>
                                                </div>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center space-x-2">
                                            <Clock className="h-5 w-5 text-blue-500" />
                                            <span>Notificaciones Críticas</span>
                                        </CardTitle>
                                        <CardDescription>Requieren atención inmediata</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            {alertas_criticas.notificaciones_criticas.length > 0 ? (
                                                alertas_criticas.notificaciones_criticas.map((notificacion) => (
                                                    <div key={notificacion.id} className="flex items-start space-x-3 p-3 border border-red-200 rounded-lg bg-red-50">
                                                        <AlertTriangle className="h-4 w-4 text-red-500 mt-1" />
                                                        <div className="flex-1">
                                                            <div className="font-medium text-red-800">{notificacion.titulo}</div>
                                                            <div className="text-sm text-red-700">{notificacion.mensaje}</div>
                                                            <div className="text-xs text-red-600 mt-1">
                                                                {notificacion.tipo} • {new Date(notificacion.created_at).toLocaleDateString()}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))
                                            ) : (
                                                <div className="text-center py-8 text-gray-500">
                                                    <CheckCircle className="h-12 w-12 mx-auto mb-4 text-green-500" />
                                                    <p>No hay notificaciones críticas</p>
                                                </div>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}
