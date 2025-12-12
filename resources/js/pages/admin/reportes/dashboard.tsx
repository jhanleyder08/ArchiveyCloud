import React, { useState, useEffect, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
    ResponsiveContainer,
    ComposedChart,
    RadialBarChart,
    RadialBar
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
    BarChart3,
    RefreshCw,
    Download,
    Filter,
    ChevronDown,
    ChevronUp,
    Eye,
    Loader2
} from 'lucide-react';

interface Metricas {
    total_expedientes: number;
    total_documentos: number;
    expedientes_abiertos: number;
    expedientes_cerrados: number;
    documentos_mes_actual: number;
    tamaño_total_gb: number;
    tamaño_total_mb: number;
    tamaño_total_bytes: number;
    unidad_almacenamiento: string;
    tamaño_formateado: string;
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

interface ExpedientePorTipo {
    tipo: string;
    total: number;
}

interface TendenciaExpediente {
    mes: string;
    total: number;
}

interface UsuarioActivo {
    usuario: string;
    email: string;
    total_acciones: number;
}

interface Filtros {
    fecha_inicio: string;
    fecha_fin: string;
    periodo: string;
}

interface Props {
    metricas: Metricas;
    expedientesPorEstado: ExpedientesPorEstado;
    documentosPorTipo: DocumentoPorTipo[];
    seriesMasUsadas: SerieUsada[];
    actividadReciente: ActividadReciente[];
    cumplimientoTrd: CumplimientoTrd;
    estadisticasAlmacenamiento: EstadisticaAlmacenamiento[];
    expedientesPorTipo?: ExpedientePorTipo[];
    tendenciaExpedientes?: TendenciaExpediente[];
    usuariosMasActivos?: UsuarioActivo[];
    filtros?: Filtros;
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

const estadoColors: Record<string, string> = {
    abierto: '#22C55E',
    en_tramite: '#3B82F6',
    tramite: '#3B82F6',
    revision: '#F59E0B',
    cerrado: '#6B7280',
    inactivo: '#6B7280',
    archivado: '#8B5CF6',
    historico: '#8B5CF6',
};

const estadoLabels: Record<string, string> = {
    en_tramite: 'En Trámite',
    activo: 'Activo',
    semiactivo: 'Semi-activo',
    inactivo: 'Inactivo',
    historico: 'Histórico',
    abierto: 'Abierto',
    cerrado: 'Cerrado',
    archivado: 'Archivado',
};

export default function ReportesDashboard({ 
    metricas: initialMetricas, 
    expedientesPorEstado: initialExpedientesPorEstado, 
    documentosPorTipo: initialDocumentosPorTipo, 
    seriesMasUsadas, 
    actividadReciente: initialActividadReciente, 
    cumplimientoTrd,
    estadisticasAlmacenamiento,
    expedientesPorTipo,
    tendenciaExpedientes,
    usuariosMasActivos,
    filtros: initialFiltros
}: Props) {
    // Estados para interactividad
    const [metricas, setMetricas] = useState(initialMetricas);
    const [expedientesPorEstado, setExpedientesPorEstado] = useState(initialExpedientesPorEstado);
    const [documentosPorTipo, setDocumentosPorTipo] = useState(initialDocumentosPorTipo);
    const [actividadReciente, setActividadReciente] = useState(initialActividadReciente);
    const [isLoading, setIsLoading] = useState(false);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [showFilters, setShowFilters] = useState(false);
    const [autoRefresh, setAutoRefresh] = useState(false);
    const [lastUpdate, setLastUpdate] = useState(new Date());
    
    // Filtros
    const [fechaInicio, setFechaInicio] = useState(initialFiltros?.fecha_inicio || new Date(Date.now() - 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
    const [fechaFin, setFechaFin] = useState(initialFiltros?.fecha_fin || new Date().toISOString().split('T')[0]);
    const [periodo, setPeriodo] = useState(initialFiltros?.periodo || '12');

    // Función para refrescar datos
    const refreshData = useCallback(async () => {
        setIsRefreshing(true);
        try {
            const response = await fetch(`/admin/reportes/dashboard/data?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`);
            if (response.ok) {
                const data = await response.json();
                setMetricas(data.metricas);
                setExpedientesPorEstado(data.expedientesPorEstado);
                setDocumentosPorTipo(data.documentosPorTipo);
                setActividadReciente(data.actividadReciente);
                setLastUpdate(new Date());
            }
        } catch (error) {
            console.error('Error refreshing data:', error);
        } finally {
            setIsRefreshing(false);
        }
    }, [fechaInicio, fechaFin]);

    // Auto-refresh cada 30 segundos si está habilitado
    useEffect(() => {
        let interval: NodeJS.Timeout;
        if (autoRefresh) {
            interval = setInterval(refreshData, 30000);
        }
        return () => {
            if (interval) clearInterval(interval);
        };
    }, [autoRefresh, refreshData]);

    // Aplicar filtros
    const applyFilters = () => {
        setIsLoading(true);
        router.get('/admin/reportes/dashboard', {
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            periodo: periodo,
        }, {
            preserveState: true,
            onFinish: () => setIsLoading(false),
        });
    };

    // Procesar datos para gráficos
    const datosExpedientesPorMes = Object.keys(expedientesPorEstado || {}).reduce((acc: any[], estado) => {
        (expedientesPorEstado[estado] || []).forEach(item => {
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

    const datosAlmacenamientoPorMes = (estadisticasAlmacenamiento || []).map(item => ({
        mes: item.mes,
        documentos: item.documentos,
        tamaño_mb: Math.round(item.tamaño_total / (1024 * 1024)),
    }));

    const porcentajeCumplimiento = cumplimientoTrd?.total_series > 0 
        ? Math.round((cumplimientoTrd.series_documentadas / cumplimientoTrd.total_series) * 100) 
        : 0;

    const porcentajeSubseriesCumplimiento = cumplimientoTrd?.total_subseries > 0 
        ? Math.round((cumplimientoTrd.subseries_documentadas / cumplimientoTrd.total_subseries) * 100) 
        : 0;

    // Datos para gráfico radial de cumplimiento
    const datosCumplimiento = [
        { name: 'Series', value: porcentajeCumplimiento, fill: '#2a3d83' },
        { name: 'Subseries', value: porcentajeSubseriesCumplimiento, fill: '#00C49F' },
    ];

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Administración', href: '#' },
            { title: 'Reportes', href: '/admin/reportes' },
            { title: 'Dashboard Ejecutivo', href: '/admin/reportes/dashboard' },
        ]}>
            <Head title="Dashboard Ejecutivo - Reportes" />

            <div className="space-y-6">
                {/* Header con controles interactivos */}
                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between pt-4 gap-4">
                    <div className="flex items-center gap-2">
                        <BarChart3 className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Dashboard Ejecutivo
                        </h1>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {/* Botón de filtros */}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowFilters(!showFilters)}
                            className="flex items-center gap-1"
                        >
                            <Filter className="h-4 w-4" />
                            Filtros
                            {showFilters ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                        </Button>
                        
                        {/* Botón de refrescar */}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={refreshData}
                            disabled={isRefreshing}
                            className="flex items-center gap-1"
                        >
                            <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                        
                        {/* Toggle auto-refresh */}
                        <Button
                            variant={autoRefresh ? "default" : "outline"}
                            size="sm"
                            onClick={() => setAutoRefresh(!autoRefresh)}
                            className={`flex items-center gap-1 ${autoRefresh ? 'bg-[#2a3d83]' : ''}`}
                        >
                            <Activity className="h-4 w-4" />
                            {autoRefresh ? 'Auto ON' : 'Auto OFF'}
                        </Button>
                        
                        {/* Badge de última actualización */}
                        <Badge variant="outline" className="flex items-center space-x-1">
                            <Clock className="h-3 w-3 text-[#2a3d83]" />
                            <span className="text-xs">
                                {lastUpdate.toLocaleTimeString()}
                            </span>
                        </Badge>
                    </div>
                </div>

                {/* Panel de filtros colapsable */}
                {showFilters && (
                    <Card className="bg-white border border-gray-200 shadow-sm">
                        <CardContent className="pt-4">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                <div className="space-y-2">
                                    <Label htmlFor="fecha_inicio">Fecha Inicio</Label>
                                    <Input
                                        id="fecha_inicio"
                                        type="date"
                                        value={fechaInicio}
                                        onChange={(e) => setFechaInicio(e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="fecha_fin">Fecha Fin</Label>
                                    <Input
                                        id="fecha_fin"
                                        type="date"
                                        value={fechaFin}
                                        onChange={(e) => setFechaFin(e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="periodo">Período</Label>
                                    <Select value={periodo} onValueChange={setPeriodo}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar período" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="3">Últimos 3 meses</SelectItem>
                                            <SelectItem value="6">Últimos 6 meses</SelectItem>
                                            <SelectItem value="12">Últimos 12 meses</SelectItem>
                                            <SelectItem value="24">Últimos 24 meses</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button 
                                    onClick={applyFilters} 
                                    disabled={isLoading}
                                    className="bg-[#2a3d83] hover:bg-[#1e2d5f]"
                                >
                                    {isLoading ? (
                                        <>
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                            Aplicando...
                                        </>
                                    ) : (
                                        'Aplicar Filtros'
                                    )}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Métricas principales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Expedientes</p>
                                <p className="text-2xl font-semibold text-gray-900">{metricas.total_expedientes.toLocaleString()}</p>
                                <p className="text-xs text-gray-500 mt-1">
                                    {metricas.expedientes_abiertos} abiertos
                                </p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Archive className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Documentos</p>
                                <p className="text-2xl font-semibold text-gray-900">{metricas.total_documentos.toLocaleString()}</p>
                                <p className="text-xs text-gray-500 mt-1">
                                    +{metricas.documentos_mes_actual} este mes
                                </p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <FileText className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Expedientes Abiertos</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{metricas.expedientes_abiertos}</p>
                                <p className="text-xs text-gray-500 mt-1">
                                    {metricas.total_expedientes > 0 ? Math.round((metricas.expedientes_abiertos / metricas.total_expedientes) * 100) : 0}% del total
                                </p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <CheckCircle className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Expedientes Cerrados</p>
                                <p className="text-2xl font-semibold text-[#2a3d83]">{metricas.expedientes_cerrados}</p>
                                <p className="text-xs text-gray-500 mt-1">
                                    {metricas.total_expedientes > 0 ? Math.round((metricas.expedientes_cerrados / metricas.total_expedientes) * 100) : 0}% del total
                                </p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <Archive className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Almacenamiento</p>
                                <p className="text-2xl font-semibold text-gray-900">{metricas.tamaño_formateado || `${metricas.tamaño_total_gb} GB`}</p>
                                <p className="text-xs text-gray-500 mt-1">
                                    {metricas.total_documentos > 0 ? (metricas.tamaño_total_mb / metricas.total_documentos).toFixed(2) : 0} MB promedio
                                </p>
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <HardDrive className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Cumplimiento TRD</p>
                                <p className="text-2xl font-semibold text-gray-900">{porcentajeCumplimiento}%</p>
                                <Progress value={porcentajeCumplimiento} className="mt-2" />
                            </div>
                            <div className="p-3 bg-blue-100 rounded-full">
                                <BarChart3 className="h-6 w-6 text-[#2a3d83]" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Gráficos principales */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Expedientes por Estado */}
                    <Card className="bg-white border border-gray-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-gray-900">Expedientes por Estado (Últimos 12 meses)</CardTitle>
                            <CardDescription className="text-gray-600">Evolución temporal de los expedientes</CardDescription>
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
                                            stroke={estadoColors[estado] || '#2a3d83'} 
                                            fill={estadoColors[estado] || '#2a3d83'}
                                            fillOpacity={0.6}
                                        />
                                    ))}
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Documentos por Tipo */}
                    <Card className="bg-white border border-gray-200 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-gray-900">Documentos por Tipo</CardTitle>
                            <CardDescription className="text-gray-600">Distribución de tipos documentales</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={(documentosPorTipo || []).slice(0, 6).map(item => ({
                                            ...item,
                                            name: item.tipo_documento
                                        }))}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={(props: any) => props.name && props.percent ? `${props.name}: ${(props.percent * 100).toFixed(0)}%` : ''}
                                        outerRadius={80}
                                        fill="#2a3d83"
                                        dataKey="total"
                                        nameKey="name"
                                    >
                                        {(documentosPorTipo || []).slice(0, 6).map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                    <Legend />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabs con información detallada */}
                <Tabs defaultValue="series" className="space-y-4">
                    <TabsList className="grid w-full grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                        <TabsTrigger value="series">Series</TabsTrigger>
                        <TabsTrigger value="actividad">Actividad</TabsTrigger>
                        <TabsTrigger value="cumplimiento">Cumplimiento</TabsTrigger>
                        <TabsTrigger value="almacenamiento">Almacenamiento</TabsTrigger>
                        <TabsTrigger value="usuarios">Usuarios</TabsTrigger>
                        <TabsTrigger value="tendencias">Tendencias</TabsTrigger>
                    </TabsList>

                    <TabsContent value="series" className="space-y-4">
                        <Card className="bg-white border border-gray-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-gray-900">Series Documentales más Utilizadas</CardTitle>
                                <CardDescription className="text-gray-600">Series con mayor número de expedientes</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {seriesMasUsadas.slice(0, 10).map((serie, index) => (
                                        <div key={serie.id} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <div className="flex items-center space-x-3">
                                                <div className="flex items-center justify-center w-8 h-8 bg-[#2a3d83] text-white rounded-full text-sm font-semibold">
                                                    {index + 1}
                                                </div>
                                                <div>
                                                    <h4 className="font-medium text-gray-900">{serie.codigo}</h4>
                                                    <p className="text-sm text-gray-600">{serie.nombre}</p>
                                                </div>
                                            </div>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-[#2a3d83]">
                                                {serie.expedientes_count} expedientes
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="actividad" className="space-y-4">
                        <Card className="bg-white border border-gray-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-gray-900">Actividad Reciente</CardTitle>
                                <CardDescription className="text-gray-600">Últimas 20 acciones en el sistema</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3 max-h-96 overflow-y-auto">
                                    {actividadReciente.slice(0, 20).map((actividad) => (
                                        <div key={actividad.id} className="flex items-start space-x-3 pb-3 border-b border-gray-200 last:border-b-0">
                                            <div className="flex-shrink-0">
                                                <Activity className="h-4 w-4 text-[#2a3d83] mt-1" />
                                            </div>
                                            <div className="flex-grow">
                                                <div className="flex items-center justify-between">
                                                    <h4 className="text-sm font-medium text-gray-900">{actividad.accion}</h4>
                                                    <span className="text-xs text-gray-500">
                                                        {actividad.fecha_relativa}
                                                    </span>
                                                </div>
                                                <p className="text-sm text-gray-600">
                                                    Por: {actividad.usuario} en {actividad.tabla_afectada}
                                                </p>
                                                {actividad.descripcion && (
                                                    <p className="text-xs text-gray-500 mt-1">
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
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Card className="bg-white border border-gray-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-gray-900">Cumplimiento de Series</CardTitle>
                                    <CardDescription className="text-gray-600">Series documentadas vs. total</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-gray-900 mb-2">
                                        {cumplimientoTrd.series_documentadas} / {cumplimientoTrd.total_series}
                                    </div>
                                    <Progress 
                                        value={cumplimientoTrd.total_series > 0 ? (cumplimientoTrd.series_documentadas / cumplimientoTrd.total_series) * 100 : 0} 
                                        className="mb-2"
                                    />
                                    <p className="text-sm text-gray-600">
                                        {cumplimientoTrd.total_series > 0 ? Math.round((cumplimientoTrd.series_documentadas / cumplimientoTrd.total_series) * 100) : 0}% 
                                        de series tienen expedientes
                                    </p>
                                </CardContent>
                            </Card>

                            <Card className="bg-white border border-gray-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-gray-900">Cumplimiento de Subseries</CardTitle>
                                    <CardDescription className="text-gray-600">Subseries documentadas vs. total</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-gray-900 mb-2">
                                        {cumplimientoTrd.subseries_documentadas} / {cumplimientoTrd.total_subseries}
                                    </div>
                                    <Progress 
                                        value={cumplimientoTrd.total_subseries > 0 ? (cumplimientoTrd.subseries_documentadas / cumplimientoTrd.total_subseries) * 100 : 0} 
                                        className="mb-2"
                                    />
                                    <p className="text-sm text-gray-600">
                                        {cumplimientoTrd.total_subseries > 0 ? Math.round((cumplimientoTrd.subseries_documentadas / cumplimientoTrd.total_subseries) * 100) : 0}% 
                                        de subseries tienen expedientes
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="almacenamiento" className="space-y-4">
                        <Card className="bg-white border border-gray-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-gray-900">Crecimiento de Almacenamiento</CardTitle>
                                <CardDescription className="text-gray-600">Evolución del almacenamiento por mes</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <ComposedChart data={datosAlmacenamientoPorMes}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="mes" />
                                        <YAxis yAxisId="left" />
                                        <YAxis yAxisId="right" orientation="right" />
                                        <Tooltip />
                                        <Legend />
                                        <Bar yAxisId="left" dataKey="documentos" fill="#2a3d83" name="Documentos" />
                                        <Line yAxisId="right" type="monotone" dataKey="tamaño_mb" stroke="#00C49F" strokeWidth={2} name="Tamaño (MB)" />
                                    </ComposedChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab de Usuarios Activos */}
                    <TabsContent value="usuarios" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <Card className="bg-white border border-gray-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-gray-900">Usuarios Más Activos</CardTitle>
                                    <CardDescription className="text-gray-600">Últimos 30 días</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {(usuariosMasActivos || []).map((usuario, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                                <div className="flex items-center space-x-3">
                                                    <div className="flex items-center justify-center w-8 h-8 bg-[#2a3d83] text-white rounded-full text-sm font-semibold">
                                                        {index + 1}
                                                    </div>
                                                    <div>
                                                        <h4 className="font-medium text-gray-900">{usuario.usuario}</h4>
                                                        <p className="text-sm text-gray-600">{usuario.email}</p>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {usuario.total_acciones} acciones
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                        {(!usuariosMasActivos || usuariosMasActivos.length === 0) && (
                                            <p className="text-gray-500 text-center py-4">No hay datos de usuarios activos</p>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="bg-white border border-gray-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-gray-900">Distribución de Actividad</CardTitle>
                                    <CardDescription className="text-gray-600">Por usuario</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={usuariosMasActivos || []} layout="vertical">
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis type="number" />
                                            <YAxis dataKey="usuario" type="category" width={100} tick={{ fontSize: 12 }} />
                                            <Tooltip />
                                            <Bar dataKey="total_acciones" fill="#2a3d83" name="Acciones" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Tab de Tendencias */}
                    <TabsContent value="tendencias" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <Card className="bg-white border border-gray-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-gray-900">Tendencia de Expedientes</CardTitle>
                                    <CardDescription className="text-gray-600">Creación de expedientes por mes</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <AreaChart data={tendenciaExpedientes || []}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="mes" />
                                            <YAxis />
                                            <Tooltip />
                                            <Area 
                                                type="monotone" 
                                                dataKey="total" 
                                                stroke="#2a3d83" 
                                                fill="#2a3d83" 
                                                fillOpacity={0.3}
                                                name="Expedientes"
                                            />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            <Card className="bg-white border border-gray-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-gray-900">Expedientes por Tipo</CardTitle>
                                    <CardDescription className="text-gray-600">Distribución por tipo de expediente</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <PieChart>
                                            <Pie
                                                data={(expedientesPorTipo || []).map((item) => ({
                                                    ...item,
                                                    name: item.tipo || 'Sin tipo',
                                                }))}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={true}
                                                label={(props: any) => props.name && props.percent ? `${props.name}: ${(props.percent * 100).toFixed(0)}%` : ''}
                                                outerRadius={80}
                                                fill="#2a3d83"
                                                dataKey="total"
                                                nameKey="name"
                                            >
                                                {(expedientesPorTipo || []).map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                            <Legend />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Resumen de estadísticas */}
                        <Card className="bg-white border border-gray-200 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-gray-900">Resumen de Tendencias</CardTitle>
                                <CardDescription className="text-gray-600">Indicadores clave de rendimiento</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div className="text-center p-4 bg-blue-50 rounded-lg">
                                        <TrendingUp className="h-8 w-8 text-[#2a3d83] mx-auto mb-2" />
                                        <p className="text-2xl font-bold text-gray-900">
                                            {metricas.documentos_mes_actual}
                                        </p>
                                        <p className="text-sm text-gray-600">Docs. este mes</p>
                                    </div>
                                    <div className="text-center p-4 bg-green-50 rounded-lg">
                                        <CheckCircle className="h-8 w-8 text-green-600 mx-auto mb-2" />
                                        <p className="text-2xl font-bold text-gray-900">
                                            {porcentajeCumplimiento}%
                                        </p>
                                        <p className="text-sm text-gray-600">Cumplimiento TRD</p>
                                    </div>
                                    <div className="text-center p-4 bg-purple-50 rounded-lg">
                                        <Archive className="h-8 w-8 text-purple-600 mx-auto mb-2" />
                                        <p className="text-2xl font-bold text-gray-900">
                                            {metricas.expedientes_abiertos}
                                        </p>
                                        <p className="text-sm text-gray-600">Exp. en trámite</p>
                                    </div>
                                    <div className="text-center p-4 bg-orange-50 rounded-lg">
                                        <HardDrive className="h-8 w-8 text-orange-600 mx-auto mb-2" />
                                        <p className="text-2xl font-bold text-gray-900">
                                            {metricas.tamaño_formateado || `${metricas.tamaño_total_gb} GB`}
                                        </p>
                                        <p className="text-sm text-gray-600">Almacenamiento</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
