import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { 
    ArrowLeft, 
    Wrench, 
    Database, 
    Trash2, 
    RefreshCw, 
    Terminal, 
    AlertTriangle,
    CheckCircle,
    XCircle,
    Clock,
    HardDrive,
    Zap,
    Link,
    Settings,
    Eye,
    Activity
} from 'lucide-react';

interface EstadisticasCache {
    cache_size: number;
    cache_hits: number;
    cache_misses: number;
}

interface ComandoDisponible {
    nombre: string;
    descripcion: string;
    comando: string;
    peligroso: boolean;
}

interface Props {
    estadisticas_cache: EstadisticasCache;
    comandos_disponibles: Record<string, ComandoDisponible>;
}

export default function ConfiguracionMantenimiento({ 
    estadisticas_cache, 
    comandos_disponibles 
}: Props) {
    const [executing, setExecuting] = useState<string | null>(null);
    const [commandOutput, setCommandOutput] = useState<string>('');
    const [showOutputDialog, setShowOutputDialog] = useState(false);
    const [systemInfo, setSystemInfo] = useState({
        php_version: '',
        laravel_version: '',
        node_version: '',
        database_size: 0,
        storage_size: 0,
        uptime: '',
        memory_usage: 0,
        disk_usage: 0
    });

    // Estadísticas con valores por defecto
    const stats = estadisticas_cache || {
        cache_size: 0,
        cache_hits: 0,
        cache_misses: 0
    };

    // Comandos con valores por defecto
    const comandos = comandos_disponibles || {};

    const formatBytes = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const getCacheHitRate = () => {
        const total = stats.cache_hits + stats.cache_misses;
        if (total === 0) return 0;
        return Math.round((stats.cache_hits / total) * 100);
    };

    const executeCommand = async (comandoKey: string) => {
        const comando = comandos[comandoKey];
        if (!comando) return;

        setExecuting(comandoKey);
        setCommandOutput('Ejecutando comando...\n');
        
        try {
            router.post(route('admin.configuracion.mantenimiento.comando'), {
                comando: comando.comando
            }, {
                preserveState: false,
                onSuccess: (page) => {
                    setCommandOutput(prev => prev + '\n✓ Comando ejecutado exitosamente!');
                    toast.success(`Comando '${comando.nombre}' ejecutado correctamente`);
                    // Recargar la página para actualizar estadísticas
                    setTimeout(() => router.reload({ only: ['estadisticas_cache'] }), 1000);
                },
                onError: (errors) => {
                    console.error('Command execution errors:', errors);
                    const errorMsg = typeof errors === 'string' ? errors : JSON.stringify(errors);
                    setCommandOutput(prev => prev + '\n✗ Error: ' + errorMsg);
                    toast.error(`Error ejecutando comando: ${comando.nombre}`);
                },
                onFinish: () => {
                    setExecuting(null);
                    setShowOutputDialog(true);
                }
            });
        } catch (error) {
            console.error('Error executing command:', error);
            setCommandOutput(prev => prev + '\n✗ Error inesperado: ' + error);
            toast.error('Error inesperado ejecutando comando');
            setExecuting(null);
        }
    };

    const getCommandIcon = (comandoKey: string) => {
        switch (comandoKey) {
            case 'cache_clear': return <Trash2 className="h-4 w-4" />;
            case 'config_cache': return <Settings className="h-4 w-4" />;
            case 'route_cache': return <RefreshCw className="h-4 w-4" />;
            case 'optimize': return <Zap className="h-4 w-4" />;
            case 'storage_link': return <Link className="h-4 w-4" />;
            default: return <Terminal className="h-4 w-4" />;
        }
    };

    const getCommandVariant = (comando: ComandoDisponible) => {
        if (comando.peligroso) return 'destructive';
        if (comando.comando.includes('clear')) return 'secondary';
        return 'default';
    };

    const refreshSystemInfo = async () => {
        // Simular obtención de información del sistema
        // En un caso real, esto vendría de una API endpoint
        setSystemInfo({
            php_version: '8.2.0',
            laravel_version: '10.0.0',
            node_version: '18.17.0',
            database_size: 1024 * 1024 * 15, // 15MB
            storage_size: 1024 * 1024 * 250, // 250MB
            uptime: '2 días, 14 horas',
            memory_usage: 65,
            disk_usage: 45
        });
    };

    useEffect(() => {
        refreshSystemInfo();
    }, []);

    return (
        <AppSidebarLayout title="Mantenimiento del Sistema">
            <Head title="Mantenimiento del Sistema" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" onClick={() => router.get(route('admin.configuracion.index'))}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                        <div className="space-y-1">
                            <h2 className="text-3xl font-bold tracking-tight">Mantenimiento del Sistema</h2>
                            <p className="text-muted-foreground">
                                Herramientas de optimización, caché y mantenimiento
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={refreshSystemInfo}>
                            <RefreshCw className="mr-2 h-4 w-4" />
                            Actualizar Info
                        </Button>
                        <Button variant="outline" onClick={() => router.reload()}>
                            <RefreshCw className="mr-2 h-4 w-4" />
                            Recargar
                        </Button>
                    </div>
                </div>

                {/* Información del Sistema */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">PHP Version</CardTitle>
                            <Settings className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{systemInfo.php_version}</div>
                            <p className="text-xs text-muted-foreground">Runtime environment</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Laravel Version</CardTitle>
                            <Settings className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{systemInfo.laravel_version}</div>
                            <p className="text-xs text-muted-foreground">Framework version</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Uptime</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{systemInfo.uptime}</div>
                            <p className="text-xs text-muted-foreground">Sistema activo</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Base de Datos</CardTitle>
                            <Database className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatBytes(systemInfo.database_size)}</div>
                            <p className="text-xs text-muted-foreground">Tamaño total</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Estado del Sistema */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Activity className="h-5 w-5" />
                            Estado del Sistema
                        </CardTitle>
                        <CardDescription>
                            Monitoreo en tiempo real de recursos críticos
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">Uso de Memoria</span>
                                <span className="text-sm text-muted-foreground">{systemInfo.memory_usage}%</span>
                            </div>
                            <Progress value={systemInfo.memory_usage} className="w-full" />
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">Uso de Disco</span>
                                <span className="text-sm text-muted-foreground">{systemInfo.disk_usage}%</span>
                            </div>
                            <Progress value={systemInfo.disk_usage} className="w-full" />
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">Hit Rate de Cache</span>
                                <span className="text-sm text-muted-foreground">{getCacheHitRate()}%</span>
                            </div>
                            <Progress value={getCacheHitRate()} className="w-full" />
                        </div>
                    </CardContent>
                </Card>

                {/* Estadísticas de Cache */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Database className="h-5 w-5" />
                            Estadísticas de Cache
                        </CardTitle>
                        <CardDescription>
                            Información sobre el rendimiento del sistema de caché
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-green-600">{stats.cache_hits.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground">Cache Hits</p>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-red-600">{stats.cache_misses.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground">Cache Misses</p>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-blue-600">{formatBytes(stats.cache_size)}</div>
                                <p className="text-xs text-muted-foreground">Tamaño Total</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Comandos de Mantenimiento */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Terminal className="h-5 w-5" />
                            Comandos de Mantenimiento
                        </CardTitle>
                        <CardDescription>
                            Herramientas para optimización y mantenimiento del sistema
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-1 lg:grid-cols-2">
                            {Object.entries(comandos).map(([key, comando]) => (
                                <Card key={key} className={comando.peligroso ? 'border-red-200' : ''}>
                                    <CardContent className="pt-4">
                                        <div className="flex items-center justify-between mb-3">
                                            <div className="flex items-center gap-2">
                                                {getCommandIcon(key)}
                                                <h4 className="font-medium">{comando.nombre}</h4>
                                                {comando.peligroso && (
                                                    <Badge variant="destructive" className="text-xs">
                                                        <AlertTriangle className="h-3 w-3 mr-1" />
                                                        Cuidado
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                        
                                        <p className="text-sm text-muted-foreground mb-4">
                                            {comando.descripcion}
                                        </p>
                                        
                                        <div className="flex items-center justify-between">
                                            <code className="text-xs bg-muted px-2 py-1 rounded">
                                                {comando.comando}
                                            </code>
                                            
                                            {comando.peligroso ? (
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button 
                                                            variant={getCommandVariant(comando)}
                                                            size="sm"
                                                            disabled={executing === key}
                                                        >
                                                            {executing === key ? (
                                                                <RefreshCw className="h-4 w-4 animate-spin mr-2" />
                                                            ) : (
                                                                getCommandIcon(key)
                                                            )}
                                                            Ejecutar
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle className="flex items-center gap-2">
                                                                <AlertTriangle className="h-5 w-5 text-red-500" />
                                                                Confirmar Comando Peligroso
                                                            </DialogTitle>
                                                            <DialogDescription>
                                                                Estás a punto de ejecutar: <strong>{comando.nombre}</strong>
                                                                <br /><br />
                                                                <code className="bg-muted p-2 rounded block mt-2">
                                                                    {comando.comando}
                                                                </code>
                                                                <br />
                                                                {comando.descripcion}
                                                                <br /><br />
                                                                <strong>¿Estás seguro de continuar?</strong>
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <DialogFooter>
                                                            <Button variant="outline">Cancelar</Button>
                                                            <Button
                                                                onClick={() => executeCommand(key)}
                                                                className="bg-red-600 hover:bg-red-700"
                                                            >
                                                                Sí, Ejecutar
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            ) : (
                                                <Button 
                                                    variant={getCommandVariant(comando)}
                                                    size="sm"
                                                    onClick={() => executeCommand(key)}
                                                    disabled={executing === key}
                                                >
                                                    {executing === key ? (
                                                        <RefreshCw className="h-4 w-4 animate-spin mr-2" />
                                                    ) : (
                                                        getCommandIcon(key)
                                                    )}
                                                    Ejecutar
                                                </Button>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {Object.keys(comandos).length === 0 && (
                            <div className="text-center py-8">
                                <Terminal className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                <h3 className="text-lg font-medium mb-2">No hay comandos disponibles</h3>
                                <p className="text-muted-foreground">
                                    No se han configurado comandos de mantenimiento
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Alertas del Sistema */}
                {(systemInfo.memory_usage > 80 || systemInfo.disk_usage > 80 || getCacheHitRate() < 70) && (
                    <Alert className="border-orange-200 bg-orange-50">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            <strong>Advertencias del sistema detectadas:</strong>
                            <ul className="list-disc ml-4 mt-2">
                                {systemInfo.memory_usage > 80 && (
                                    <li>Uso de memoria alto ({systemInfo.memory_usage}%)</li>
                                )}
                                {systemInfo.disk_usage > 80 && (
                                    <li>Uso de disco alto ({systemInfo.disk_usage}%)</li>
                                )}
                                {getCacheHitRate() < 70 && (
                                    <li>Hit rate de cache bajo ({getCacheHitRate()}%)</li>
                                )}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                {/* Dialog de Output */}
                <Dialog open={showOutputDialog} onOpenChange={setShowOutputDialog}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <Terminal className="h-5 w-5" />
                                Resultado del Comando
                            </DialogTitle>
                            <DialogDescription>
                                Output de la ejecución del comando
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <Textarea
                                value={commandOutput}
                                readOnly
                                className="min-h-[200px] font-mono text-sm"
                                placeholder="No hay output disponible..."
                            />
                        </div>
                        <DialogFooter>
                            <Button onClick={() => setShowOutputDialog(false)}>
                                Cerrar
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppSidebarLayout>
    );
}
