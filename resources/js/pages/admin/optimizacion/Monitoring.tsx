import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Progress } from '@/Components/ui/progress';
import { 
    Activity, 
    Cpu, 
    MemoryStick, 
    HardDrive, 
    Clock,
    Server,
    Database,
    CheckCircle,
    XCircle,
    AlertTriangle,
    RefreshCw,
    TrendingUp,
    Zap
} from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, AreaChart, Area, BarChart, Bar } from 'recharts';

interface HealthCheck {
    name: string;
    status: string;
    message?: string;
    response_time?: number;
    details?: any;
}

interface PerformanceMetrics {
    memory_used: number;
    memory_peak: number;
    memory_limit: number;
    execution_time: number;
    included_files: number;
    database_queries: number;
}

interface SystemInfo {
    php_version: string;
    laravel_version: string;
    server_software: string;
    os: string;
    architecture: string;
    timezone: string;
    locale: string;
}

interface PageProps {
    healthChecks: HealthCheck[];
    performanceMetrics: PerformanceMetrics;
    systemInfo: SystemInfo;
}

export default function MonitoringPage({ healthChecks, performanceMetrics, systemInfo }: PageProps) {
    const [refreshing, setRefreshing] = useState(false);
    const [liveMetrics, setLiveMetrics] = useState<PerformanceMetrics>(performanceMetrics);
    const [liveHealthChecks, setLiveHealthChecks] = useState<HealthCheck[]>(healthChecks);

    // Auto-refresh cada 10 segundos
    useEffect(() => {
        const interval = setInterval(() => {
            refreshMetrics();
        }, 10000);

        return () => clearInterval(interval);
    }, []);

    const refreshMetrics = async () => {
        setRefreshing(true);
        try {
            const response = await fetch(route('admin.optimizacion.system-status'));
            if (response.ok) {
                const data = await response.json();
                setLiveMetrics(data.performance_metrics);
                setLiveHealthChecks(data.health_status.checks);
            }
        } catch (error) {
            console.error('Error refreshing metrics:', error);
        } finally {
            setRefreshing(false);
        }
    };

    const formatBytes = (bytes: number): string => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return `${size.toFixed(2)} ${units[unitIndex]}`;
    };

    const formatTime = (seconds: number): string => {
        if (seconds < 1) {
            return `${(seconds * 1000).toFixed(2)}ms`;
        }
        return `${seconds.toFixed(3)}s`;
    };

    const getMemoryUsagePercentage = (): number => {
        return (liveMetrics.memory_used / liveMetrics.memory_limit) * 100;
    };

    const getStatusIcon = (status: string) => {
        switch (status.toLowerCase()) {
            case 'ok':
            case 'healthy':
                return <CheckCircle className="h-4 w-4 text-green-600" />;
            case 'warning':
                return <AlertTriangle className="h-4 w-4 text-yellow-600" />;
            case 'error':
            case 'failed':
                return <XCircle className="h-4 w-4 text-red-600" />;
            default:
                return <AlertTriangle className="h-4 w-4 text-gray-600" />;
        }
    };

    const getStatusColor = (status: string): string => {
        switch (status.toLowerCase()) {
            case 'ok':
            case 'healthy':
                return 'text-green-600';
            case 'warning':
                return 'text-yellow-600';
            case 'error':
            case 'failed':
                return 'text-red-600';
            default:
                return 'text-gray-600';
        }
    };

    // Datos para gráficos (simulados para demo)
    const performanceHistory = [
        { time: '00:00', memory: 45, cpu: 23, response_time: 120 },
        { time: '00:05', memory: 52, cpu: 28, response_time: 140 },
        { time: '00:10', memory: 48, cpu: 31, response_time: 135 },
        { time: '00:15', memory: 60, cpu: 42, response_time: 180 },
        { time: '00:20', memory: 55, cpu: 38, response_time: 150 },
        { time: '00:25', memory: 58, cpu: 45, response_time: 165 },
    ];

    const healthMetrics = liveHealthChecks.map(check => ({
        name: check.name,
        status: check.status === 'ok' ? 100 : 0,
        response_time: check.response_time || 0
    }));

    return (
        <AppSidebarLayout>
            <Head title="Monitoreo del Sistema" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header */}
                    <div className="flex items-center justify-between bg-white p-6 rounded-lg shadow">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Monitoreo del Sistema
                        </h2>
                        <Button
                            onClick={refreshMetrics}
                            variant="outline"
                            size="sm"
                            disabled={refreshing}
                            className="flex items-center gap-2"
                        >
                            <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                    </div>

                    {/* Métricas principales */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Uso de Memoria</CardTitle>
                                <MemoryStick className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {getMemoryUsagePercentage().toFixed(1)}%
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {formatBytes(liveMetrics.memory_used)} / {formatBytes(liveMetrics.memory_limit)}
                                </p>
                                <Progress value={getMemoryUsagePercentage()} className="mt-2" />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Tiempo de Ejecución</CardTitle>
                                <Clock className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {formatTime(liveMetrics.execution_time)}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Tiempo actual de respuesta
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Consultas BD</CardTitle>
                                <Database className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {liveMetrics.database_queries}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Consultas ejecutadas
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Archivos Incluidos</CardTitle>
                                <Server className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {liveMetrics.included_files}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Archivos PHP cargados
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <Tabs defaultValue="health" className="space-y-6">
                        <TabsList className="grid w-full grid-cols-4">
                            <TabsTrigger value="health">Health Checks</TabsTrigger>
                            <TabsTrigger value="performance">Performance</TabsTrigger>
                            <TabsTrigger value="system">Información del Sistema</TabsTrigger>
                            <TabsTrigger value="charts">Gráficos</TabsTrigger>
                        </TabsList>

                        <TabsContent value="health" className="space-y-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Activity className="h-5 w-5" />
                                            Estado de Servicios
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {liveHealthChecks.map((check, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    {getStatusIcon(check.status)}
                                                    <div>
                                                        <p className="font-medium">{check.name}</p>
                                                        {check.message && (
                                                            <p className="text-sm text-muted-foreground">{check.message}</p>
                                                        )}
                                                        {check.response_time && (
                                                            <p className="text-xs text-muted-foreground">
                                                                Tiempo: {check.response_time}ms
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                                <Badge variant={check.status === 'ok' ? 'default' : 'destructive'}>
                                                    {check.status}
                                                </Badge>
                                            </div>
                                        ))}
                                        {liveHealthChecks.length === 0 && (
                                            <p className="text-center text-muted-foreground py-4">
                                                No hay health checks disponibles
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Resumen de Salud</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            <div className="grid grid-cols-3 gap-4">
                                                <div className="text-center p-3 border rounded-lg">
                                                    <div className="text-2xl font-bold text-green-600">
                                                        {liveHealthChecks.filter(c => c.status === 'ok').length}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">Saludables</div>
                                                </div>
                                                <div className="text-center p-3 border rounded-lg">
                                                    <div className="text-2xl font-bold text-yellow-600">
                                                        {liveHealthChecks.filter(c => c.status === 'warning').length}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">Advertencias</div>
                                                </div>
                                                <div className="text-center p-3 border rounded-lg">
                                                    <div className="text-2xl font-bold text-red-600">
                                                        {liveHealthChecks.filter(c => c.status === 'error' || c.status === 'failed').length}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">Errores</div>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="performance" className="space-y-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Métricas Detalladas</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-3">
                                            <div className="flex justify-between items-center">
                                                <span>Memoria Usada:</span>
                                                <span className="font-mono text-sm">
                                                    {formatBytes(liveMetrics.memory_used)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span>Pico de Memoria:</span>
                                                <span className="font-mono text-sm">
                                                    {formatBytes(liveMetrics.memory_peak)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span>Límite de Memoria:</span>
                                                <span className="font-mono text-sm">
                                                    {formatBytes(liveMetrics.memory_limit)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span>Tiempo de Ejecución:</span>
                                                <span className="font-mono text-sm">
                                                    {formatTime(liveMetrics.execution_time)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span>Archivos Incluidos:</span>
                                                <span className="font-mono text-sm">
                                                    {liveMetrics.included_files.toLocaleString()}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span>Consultas BD:</span>
                                                <span className="font-mono text-sm">
                                                    {liveMetrics.database_queries.toLocaleString()}
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Alertas de Performance</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {getMemoryUsagePercentage() > 80 && (
                                            <div className="flex items-center gap-2 p-3 border border-red-300 rounded-lg bg-red-50">
                                                <AlertTriangle className="h-4 w-4 text-red-600" />
                                                <span className="text-sm text-red-600">
                                                    Uso de memoria alto ({getMemoryUsagePercentage().toFixed(1)}%)
                                                </span>
                                            </div>
                                        )}
                                        
                                        {liveMetrics.execution_time > 2 && (
                                            <div className="flex items-center gap-2 p-3 border border-yellow-300 rounded-lg bg-yellow-50">
                                                <AlertTriangle className="h-4 w-4 text-yellow-600" />
                                                <span className="text-sm text-yellow-600">
                                                    Tiempo de respuesta lento ({formatTime(liveMetrics.execution_time)})
                                                </span>
                                            </div>
                                        )}
                                        
                                        {liveMetrics.database_queries > 50 && (
                                            <div className="flex items-center gap-2 p-3 border border-yellow-300 rounded-lg bg-yellow-50">
                                                <AlertTriangle className="h-4 w-4 text-yellow-600" />
                                                <span className="text-sm text-yellow-600">
                                                    Muchas consultas BD ({liveMetrics.database_queries})
                                                </span>
                                            </div>
                                        )}
                                        
                                        {getMemoryUsagePercentage() <= 80 && liveMetrics.execution_time <= 2 && liveMetrics.database_queries <= 50 && (
                                            <div className="flex items-center gap-2 p-3 border border-green-300 rounded-lg bg-green-50">
                                                <CheckCircle className="h-4 w-4 text-green-600" />
                                                <span className="text-sm text-green-600">
                                                    Sistema funcionando óptimamente
                                                </span>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="system" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Server className="h-5 w-5" />
                                        Información del Sistema
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-3">
                                            <div className="flex justify-between">
                                                <span>Versión PHP:</span>
                                                <span className="font-mono">{systemInfo.php_version}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Versión Laravel:</span>
                                                <span className="font-mono">{systemInfo.laravel_version}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Sistema Operativo:</span>
                                                <span className="font-mono">{systemInfo.os}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Arquitectura:</span>
                                                <span className="font-mono">{systemInfo.architecture}</span>
                                            </div>
                                        </div>
                                        <div className="space-y-3">
                                            <div className="flex justify-between">
                                                <span>Servidor Web:</span>
                                                <span className="font-mono text-sm">{systemInfo.server_software}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Zona Horaria:</span>
                                                <span className="font-mono">{systemInfo.timezone}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Idioma:</span>
                                                <span className="font-mono">{systemInfo.locale}</span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="charts" className="space-y-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Historial de Performance</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <LineChart data={performanceHistory}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="time" />
                                                <YAxis />
                                                <Tooltip />
                                                <Legend />
                                                <Line type="monotone" dataKey="memory" stroke="#3b82f6" name="Memoria %" />
                                                <Line type="monotone" dataKey="cpu" stroke="#ef4444" name="CPU %" />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Tiempo de Respuesta</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <AreaChart data={performanceHistory}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="time" />
                                                <YAxis />
                                                <Tooltip formatter={(value: any) => [`${value}ms`, 'Tiempo de Respuesta']} />
                                                <Area type="monotone" dataKey="response_time" stroke="#10b981" fill="#10b981" fillOpacity={0.3} />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </div>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Estado de Health Checks</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={healthMetrics}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
                                            <YAxis />
                                            <Tooltip formatter={(value: any) => [value === 100 ? 'OK' : 'FALLO', 'Estado']} />
                                            <Bar dataKey="status" fill="#10b981" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
