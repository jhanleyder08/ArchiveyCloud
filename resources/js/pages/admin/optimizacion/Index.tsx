import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { 
    Activity, 
    Cpu, 
    Database, 
    HardDrive, 
    MemoryStick, 
    Server, 
    Settings, 
    Zap,
    Clock,
    CheckCircle,
    XCircle,
    AlertTriangle,
    RefreshCw,
    Play,
    TrendingUp
} from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

interface SystemStatus {
    environment: string;
    debug_mode: boolean;
    cache_driver: string;
    queue_driver: string;
    optimization_enabled: boolean;
    php_version: string;
    laravel_version: string;
    opcache_enabled: boolean;
    redis_available: boolean;
    uptime: string;
}

interface OptimizationHistoryItem {
    timestamp: string;
    success: boolean;
    options: string[];
    exit_code: number;
    user_id: number;
}

interface CacheStatistics {
    driver: string;
    status: string;
    hit_ratio: number;
    memory_usage: string;
    keys_count: string | number;
}

interface HealthStatus {
    status: string;
    response_time: string;
    checks: Array<{
        name: string;
        status: string;
        message?: string;
        response_time?: number;
    }>;
}

interface PageProps {
    systemStatus: SystemStatus;
    optimizationHistory: OptimizationHistoryItem[];
    cacheStatistics: CacheStatistics;
    healthStatus: HealthStatus;
}

export default function OptimizacionIndex({ 
    systemStatus, 
    optimizationHistory, 
    cacheStatistics, 
    healthStatus 
}: PageProps) {
    const [loading, setLoading] = useState(false);
    const [refreshing, setRefreshing] = useState(false);
    const [liveData, setLiveData] = useState({
        systemStatus,
        cacheStatistics,
        healthStatus
    });

    // Auto-refresh cada 30 segundos
    useEffect(() => {
        const interval = setInterval(() => {
            refreshSystemData();
        }, 30000);

        return () => clearInterval(interval);
    }, []);

    const refreshSystemData = async () => {
        setRefreshing(true);
        try {
            const response = await fetch(route('admin.optimizacion.system-status'));
            if (response.ok) {
                const data = await response.json();
                setLiveData({
                    systemStatus: data.system_status,
                    cacheStatistics: data.cache_stats,
                    healthStatus: data.health_status
                });
            }
        } catch (error) {
            console.error('Error refreshing system data:', error);
        } finally {
            setRefreshing(false);
        }
    };

    const runOptimization = async (options: Record<string, boolean> = {}) => {
        setLoading(true);
        try {
            const response = await fetch(route('admin.optimizacion.run-optimization'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(options),
            });

            const result = await response.json();
            
            if (result.success) {
                // Mostrar resultado exitoso
                alert('Optimización ejecutada exitosamente');
                // Recargar la página para mostrar el nuevo historial
                router.reload();
            } else {
                alert('Error durante la optimización: ' + result.message);
            }
        } catch (error) {
            console.error('Error running optimization:', error);
            alert('Error interno del servidor');
        } finally {
            setLoading(false);
        }
    };

    const getStatusColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'healthy':
            case 'ok':
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

    const getStatusIcon = (status: string) => {
        switch (status.toLowerCase()) {
            case 'healthy':
            case 'ok':
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

    // Datos para gráficos
    const pieChartData = liveData.healthStatus.checks.map(check => ({
        name: check.name,
        value: check.status === 'ok' ? 1 : 0,
        status: check.status
    }));

    const COLORS = {
        ok: '#10b981',
        warning: '#f59e0b',
        error: '#ef4444'
    };

    return (
        <AppSidebarLayout>
            <Head title="Optimización del Sistema" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header */}
                    <div className="flex items-center justify-between bg-white p-6 rounded-lg shadow">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Sistema de Optimización y Monitoreo
                        </h2>
                        <Button
                            onClick={refreshSystemData}
                            variant="outline"
                            size="sm"
                            disabled={refreshing}
                            className="flex items-center gap-2"
                        >
                            <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                    </div>
                    
                    {/* Estado general del sistema */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Estado del Sistema</CardTitle>
                                <Server className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center space-x-2">
                                    {getStatusIcon(liveData.healthStatus.status)}
                                    <div className="text-2xl font-bold">
                                        {liveData.healthStatus.status.toUpperCase()}
                                    </div>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {liveData.systemStatus.environment} • PHP {liveData.systemStatus.php_version}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Caché</CardTitle>
                                <Database className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {typeof liveData.cacheStatistics.hit_ratio === 'number' 
                                        ? `${liveData.cacheStatistics.hit_ratio}%` 
                                        : 'N/A'}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Hit Ratio • {liveData.cacheStatistics.driver}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Optimización</CardTitle>
                                <Zap className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    <Badge variant={liveData.systemStatus.optimization_enabled ? "default" : "secondary"}>
                                        {liveData.systemStatus.optimization_enabled ? 'Habilitada' : 'Deshabilitada'}
                                    </Badge>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {liveData.systemStatus.opcache_enabled ? 'OPcache activo' : 'OPcache inactivo'}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Uptime</CardTitle>
                                <Clock className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {liveData.systemStatus.uptime !== 'N/A' 
                                        ? liveData.systemStatus.uptime.replace('up ', '')
                                        : 'N/A'}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Sistema activo
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <Tabs defaultValue="dashboard" className="space-y-6">
                        <TabsList className="grid w-full grid-cols-4">
                            <TabsTrigger value="dashboard">Dashboard</TabsTrigger>
                            <TabsTrigger value="optimization">Optimización</TabsTrigger>
                            <TabsTrigger value="health">Health Checks</TabsTrigger>
                            <TabsTrigger value="history">Historial</TabsTrigger>
                        </TabsList>

                        <TabsContent value="dashboard" className="space-y-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Gráfico de Health Checks */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Activity className="h-5 w-5" />
                                            Estado de Servicios
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <PieChart>
                                                <Pie
                                                    data={[
                                                        { 
                                                            name: 'Saludables', 
                                                            value: liveData.healthStatus.checks.filter(c => c.status === 'ok').length,
                                                            fill: COLORS.ok
                                                        },
                                                        { 
                                                            name: 'Con problemas', 
                                                            value: liveData.healthStatus.checks.filter(c => c.status !== 'ok').length,
                                                            fill: COLORS.error
                                                        }
                                                    ]}
                                                    cx="50%"
                                                    cy="50%"
                                                    outerRadius={80}
                                                    dataKey="value"
                                                    label={({ name, value }) => `${name}: ${value}`}
                                                />
                                                <Tooltip />
                                            </PieChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>

                                {/* Lista de Health Checks */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <CheckCircle className="h-5 w-5" />
                                            Verificaciones del Sistema
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {liveData.healthStatus.checks.map((check, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    {getStatusIcon(check.status)}
                                                    <div>
                                                        <p className="font-medium">{check.name}</p>
                                                        {check.message && (
                                                            <p className="text-sm text-muted-foreground">{check.message}</p>
                                                        )}
                                                    </div>
                                                </div>
                                                <Badge variant={check.status === 'ok' ? 'default' : 'destructive'}>
                                                    {check.status}
                                                </Badge>
                                            </div>
                                        ))}
                                        {liveData.healthStatus.checks.length === 0 && (
                                            <p className="text-center text-muted-foreground py-4">
                                                No hay datos de health checks disponibles
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="optimization" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Zap className="h-5 w-5" />
                                        Ejecutar Optimización
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <Alert>
                                        <AlertTriangle className="h-4 w-4" />
                                        <AlertTitle>Importante</AlertTitle>
                                        <AlertDescription>
                                            La optimización mejorará el rendimiento del sistema pero puede tomar varios minutos.
                                            Se recomienda ejecutar durante períodos de bajo tráfico.
                                        </AlertDescription>
                                    </Alert>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <Button
                                            onClick={() => runOptimization({ dry_run: true })}
                                            disabled={loading}
                                            variant="outline"
                                            className="flex items-center gap-2"
                                        >
                                            <Settings className="h-4 w-4" />
                                            Simular Optimización
                                        </Button>

                                        <Button
                                            onClick={() => runOptimization()}
                                            disabled={loading}
                                            className="flex items-center gap-2"
                                        >
                                            <Play className="h-4 w-4" />
                                            {loading ? 'Ejecutando...' : 'Optimización Completa'}
                                        </Button>

                                        <Button
                                            onClick={() => runOptimization({ skip_cache: true })}
                                            disabled={loading}
                                            variant="outline"
                                            className="flex items-center gap-2"
                                        >
                                            <Database className="h-4 w-4" />
                                            Sin Caché
                                        </Button>

                                        <Button
                                            onClick={() => router.visit(route('admin.optimizacion.cache'))}
                                            variant="outline"
                                            className="flex items-center gap-2"
                                        >
                                            <MemoryStick className="h-4 w-4" />
                                            Gestión de Caché
                                        </Button>
                                    </div>

                                    {loading && (
                                        <div className="space-y-2">
                                            <p className="text-sm text-muted-foreground">Ejecutando optimización...</p>
                                            <Progress value={undefined} className="w-full" />
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="health" className="space-y-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Estado General</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between">
                                                <span>Estado del Sistema:</span>
                                                <Badge className={getStatusColor(liveData.healthStatus.status)}>
                                                    {liveData.healthStatus.status}
                                                </Badge>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <span>Tiempo de Respuesta:</span>
                                                <span className="font-mono text-sm">
                                                    {liveData.healthStatus.response_time}
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <span>Servicios Verificados:</span>
                                                <span className="font-bold">
                                                    {liveData.healthStatus.checks.length}
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Acciones Rápidas</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-2">
                                        <Button
                                            onClick={() => router.visit(route('admin.optimizacion.monitoring'))}
                                            variant="outline"
                                            className="w-full justify-start"
                                        >
                                            <TrendingUp className="h-4 w-4 mr-2" />
                                            Monitoreo Detallado
                                        </Button>
                                        <Button
                                            onClick={() => router.visit(route('admin.optimizacion.backups'))}
                                            variant="outline"
                                            className="w-full justify-start"
                                        >
                                            <HardDrive className="h-4 w-4 mr-2" />
                                            Gestión de Backups
                                        </Button>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="history" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Historial de Optimizaciones</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {optimizationHistory.map((item, index) => (
                                            <div
                                                key={index}
                                                className="flex items-center justify-between p-4 border rounded-lg"
                                            >
                                                <div className="flex items-center space-x-3">
                                                    {item.success ? (
                                                        <CheckCircle className="h-5 w-5 text-green-600" />
                                                    ) : (
                                                        <XCircle className="h-5 w-5 text-red-600" />
                                                    )}
                                                    <div>
                                                        <p className="font-medium">
                                                            {item.success ? 'Optimización exitosa' : 'Optimización fallida'}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {new Date(item.timestamp).toLocaleString()}
                                                        </p>
                                                        {item.options.length > 0 && (
                                                            <p className="text-xs text-muted-foreground">
                                                                Opciones: {item.options.join(', ')}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                                <Badge variant={item.success ? 'default' : 'destructive'}>
                                                    Código: {item.exit_code}
                                                </Badge>
                                            </div>
                                        ))}
                                        {optimizationHistory.length === 0 && (
                                            <p className="text-center text-muted-foreground py-8">
                                                No hay historial de optimizaciones disponible
                                            </p>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
