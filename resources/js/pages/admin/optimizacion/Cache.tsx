import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { 
    Database, 
    Trash2, 
    Flame, 
    Info, 
    TrendingUp, 
    Clock,
    MemoryStick,
    AlertTriangle,
    CheckCircle,
    Activity
} from 'lucide-react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, BarChart, Bar, XAxis, YAxis, CartesianGrid, Legend } from 'recharts';

interface CacheInfo {
    driver: string;
    status: string;
    redis?: {
        version: string;
        connected_clients: number;
        used_memory: string;
        keyspace_hits: number;
        keyspace_misses: number;
        hit_ratio: number;
    };
}

interface CacheStats {
    key: string;
    description: string;
    exists: boolean;
    status: string;
}

interface TTLConfig {
    [key: string]: number;
}

interface PageProps {
    cacheInfo: CacheInfo;
    cacheStats: CacheStats[];
    ttlConfig: TTLConfig;
}

export default function CachePage({ cacheInfo, cacheStats, ttlConfig }: PageProps) {
    const [loading, setLoading] = useState(false);
    const [flushType, setFlushType] = useState('all');
    const [userId, setUserId] = useState('');
    const [pattern, setPattern] = useState('');
    const [showFlushDialog, setShowFlushDialog] = useState(false);

    const warmupCache = async () => {
        setLoading(true);
        try {
            const response = await fetch(route('admin.optimizacion.cache.warmup'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const result = await response.json();
            
            if (result.success) {
                alert(`Warmup exitoso en ${result.execution_time}`);
                router.reload();
            } else {
                alert('Error durante warmup: ' + result.message);
            }
        } catch (error) {
            console.error('Error during cache warmup:', error);
            alert('Error interno del servidor');
        } finally {
            setLoading(false);
        }
    };

    const flushCache = async () => {
        setLoading(true);
        try {
            const body: any = { type: flushType };
            
            if (flushType === 'user' && userId) {
                body.user_id = userId;
            } else if (flushType === 'pattern' && pattern) {
                body.pattern = pattern;
            }

            const response = await fetch(route('admin.optimizacion.cache.flush'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(body),
            });

            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                router.reload();
            } else {
                alert('Error durante limpieza: ' + result.message);
            }
        } catch (error) {
            console.error('Error during cache flush:', error);
            alert('Error interno del servidor');
        } finally {
            setLoading(false);
            setShowFlushDialog(false);
        }
    };

    const formatDuration = (seconds: number): string => {
        if (seconds < 60) {
            return `${seconds}s`;
        } else if (seconds < 3600) {
            const minutes = Math.floor(seconds / 60);
            return `${minutes}m`;
        } else {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`;
        }
    };

    // Datos para gráficos
    const cacheStatusData = cacheStats.map(stat => ({
        name: stat.description,
        cached: stat.exists ? 1 : 0,
        not_cached: stat.exists ? 0 : 1
    }));

    const ttlData = Object.entries(ttlConfig).map(([key, value]) => ({
        name: key.replace('_', ' ').toUpperCase(),
        ttl: value,
        duration: formatDuration(value)
    }));

    const COLORS = ['#10b981', '#ef4444'];

    return (
        <AppSidebarLayout>
            <Head title="Gestión de Caché" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header */}
                    <div className="bg-white p-6 rounded-lg shadow">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Gestión de Caché
                        </h2>
                    </div>
                    
                    {/* Estado del caché */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Driver de Caché</CardTitle>
                                <Database className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{cacheInfo.driver}</div>
                                <Badge 
                                    variant={cacheInfo.status === 'healthy' ? 'default' : 'destructive'}
                                    className="mt-1"
                                >
                                    {cacheInfo.status}
                                </Badge>
                            </CardContent>
                        </Card>

                        {cacheInfo.redis && (
                            <>
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Hit Ratio</CardTitle>
                                        <TrendingUp className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{cacheInfo.redis.hit_ratio}%</div>
                                        <p className="text-xs text-muted-foreground">
                                            {cacheInfo.redis.keyspace_hits.toLocaleString()} hits
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Memoria Usada</CardTitle>
                                        <MemoryStick className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{cacheInfo.redis.used_memory}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Redis v{cacheInfo.redis.version}
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Clientes</CardTitle>
                                        <Activity className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{cacheInfo.redis.connected_clients}</div>
                                        <p className="text-xs text-muted-foreground">
                                            Conectados
                                        </p>
                                    </CardContent>
                                </Card>
                            </>
                        )}
                    </div>

                    <Tabs defaultValue="overview" className="space-y-6">
                        <TabsList className="grid w-full grid-cols-4">
                            <TabsTrigger value="overview">Resumen</TabsTrigger>
                            <TabsTrigger value="management">Gestión</TabsTrigger>
                            <TabsTrigger value="config">Configuración TTL</TabsTrigger>
                            <TabsTrigger value="stats">Estadísticas</TabsTrigger>
                        </TabsList>

                        <TabsContent value="overview" className="space-y-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Estado de cachés */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Database className="h-5 w-5" />
                                            Estado de Cachés Críticos
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <PieChart>
                                                <Pie
                                                    data={[
                                                        { 
                                                            name: 'En Caché', 
                                                            value: cacheStats.filter(s => s.exists).length,
                                                            fill: COLORS[0]
                                                        },
                                                        { 
                                                            name: 'Sin Caché', 
                                                            value: cacheStats.filter(s => !s.exists).length,
                                                            fill: COLORS[1]
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

                                {/* Lista de cachés */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Cachés del Sistema</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {cacheStats.map((stat, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    {stat.exists ? (
                                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                                    ) : (
                                                        <AlertTriangle className="h-4 w-4 text-yellow-600" />
                                                    )}
                                                    <div>
                                                        <p className="font-medium">{stat.description}</p>
                                                        <p className="text-sm text-muted-foreground">{stat.key}</p>
                                                    </div>
                                                </div>
                                                <Badge variant={stat.exists ? 'default' : 'secondary'}>
                                                    {stat.exists ? 'En Caché' : 'No Cacheado'}
                                                </Badge>
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="management" className="space-y-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Warmup */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Flame className="h-5 w-5" />
                                            Precalentamiento de Caché
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <Alert>
                                            <Info className="h-4 w-4" />
                                            <AlertTitle>Warmup de Caché</AlertTitle>
                                            <AlertDescription>
                                                Precalienta los cachés críticos del sistema para mejorar el rendimiento.
                                            </AlertDescription>
                                        </Alert>
                                        <Button
                                            onClick={warmupCache}
                                            disabled={loading}
                                            className="w-full flex items-center gap-2"
                                        >
                                            <Flame className="h-4 w-4" />
                                            {loading ? 'Precalentando...' : 'Precalentar Cachés'}
                                        </Button>
                                    </CardContent>
                                </Card>

                                {/* Limpieza */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Trash2 className="h-5 w-5" />
                                            Limpieza de Caché
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <Alert>
                                            <AlertTriangle className="h-4 w-4" />
                                            <AlertTitle>Precaución</AlertTitle>
                                            <AlertDescription>
                                                La limpieza de caché puede afectar temporalmente el rendimiento.
                                            </AlertDescription>
                                        </Alert>

                                        <Dialog open={showFlushDialog} onOpenChange={setShowFlushDialog}>
                                            <DialogTrigger asChild>
                                                <Button variant="destructive" className="w-full flex items-center gap-2">
                                                    <Trash2 className="h-4 w-4" />
                                                    Limpiar Caché
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogHeader>
                                                    <DialogTitle>Limpiar Caché</DialogTitle>
                                                    <DialogDescription>
                                                        Selecciona el tipo de limpieza de caché que deseas realizar.
                                                    </DialogDescription>
                                                </DialogHeader>
                                                <div className="space-y-4">
                                                    <div className="space-y-2">
                                                        <Label>Tipo de Limpieza</Label>
                                                        <select
                                                            value={flushType}
                                                            onChange={(e) => setFlushType(e.target.value)}
                                                            className="w-full p-2 border rounded-md"
                                                        >
                                                            <option value="all">Todo el caché</option>
                                                            <option value="user">Caché de usuario específico</option>
                                                            <option value="pattern">Por patrón</option>
                                                        </select>
                                                    </div>

                                                    {flushType === 'user' && (
                                                        <div className="space-y-2">
                                                            <Label htmlFor="userId">ID del Usuario</Label>
                                                            <Input
                                                                id="userId"
                                                                value={userId}
                                                                onChange={(e) => setUserId(e.target.value)}
                                                                placeholder="ID del usuario"
                                                                type="number"
                                                            />
                                                        </div>
                                                    )}

                                                    {flushType === 'pattern' && (
                                                        <div className="space-y-2">
                                                            <Label htmlFor="pattern">Patrón</Label>
                                                            <Input
                                                                id="pattern"
                                                                value={pattern}
                                                                onChange={(e) => setPattern(e.target.value)}
                                                                placeholder="user_*, document_*, etc."
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                                <DialogFooter>
                                                    <Button
                                                        variant="outline"
                                                        onClick={() => setShowFlushDialog(false)}
                                                    >
                                                        Cancelar
                                                    </Button>
                                                    <Button
                                                        variant="destructive"
                                                        onClick={flushCache}
                                                        disabled={loading}
                                                    >
                                                        {loading ? 'Limpiando...' : 'Limpiar'}
                                                    </Button>
                                                </DialogFooter>
                                            </DialogContent>
                                        </Dialog>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="config" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Clock className="h-5 w-5" />
                                        Configuración de TTL (Time To Live)
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {ttlData.length > 0 ? (
                                        <>
                                            <ResponsiveContainer width="100%" height={400}>
                                                <BarChart data={ttlData}>
                                                    <CartesianGrid strokeDasharray="3 3" />
                                                    <XAxis 
                                                        dataKey="name" 
                                                        angle={-45}
                                                        textAnchor="end"
                                                        height={100}
                                                    />
                                                    <YAxis />
                                                    <Tooltip 
                                                        formatter={(value: any, name: string) => [
                                                            formatDuration(value), 
                                                            'TTL'
                                                        ]}
                                                        labelFormatter={(label) => `Tipo: ${label}`}
                                                    />
                                                    <Legend />
                                                    <Bar dataKey="ttl" fill="#3b82f6" name="TTL (segundos)" />
                                                </BarChart>
                                            </ResponsiveContainer>
                                            
                                            <div className="mt-6 space-y-3">
                                                {ttlData.map((item, index) => (
                                                    <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                                        <span className="font-medium">{item.name}</span>
                                                        <div className="text-right">
                                                            <div className="font-mono text-sm">{item.duration}</div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {item.ttl.toLocaleString()} segundos
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </>
                                    ) : (
                                        <p className="text-center text-muted-foreground py-8">
                                            No hay configuración TTL disponible
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="stats" className="space-y-4">
                            {cacheInfo.redis ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Métricas de Redis</CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <div className="grid grid-cols-2 gap-4">
                                                <div className="text-center p-3 border rounded-lg">
                                                    <div className="text-2xl font-bold text-green-600">
                                                        {cacheInfo.redis.keyspace_hits.toLocaleString()}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">Cache Hits</div>
                                                </div>
                                                <div className="text-center p-3 border rounded-lg">
                                                    <div className="text-2xl font-bold text-red-600">
                                                        {cacheInfo.redis.keyspace_misses.toLocaleString()}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">Cache Misses</div>
                                                </div>
                                            </div>
                                            <div className="text-center p-3 border rounded-lg">
                                                <div className="text-3xl font-bold text-blue-600">
                                                    {cacheInfo.redis.hit_ratio}%
                                                </div>
                                                <div className="text-sm text-muted-foreground">Hit Ratio</div>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Información del Sistema</CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div className="flex justify-between">
                                                <span>Versión Redis:</span>
                                                <span className="font-mono">{cacheInfo.redis.version}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Memoria Usada:</span>
                                                <span className="font-mono">{cacheInfo.redis.used_memory}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Clientes Conectados:</span>
                                                <span className="font-mono">{cacheInfo.redis.connected_clients}</span>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            ) : (
                                <Card>
                                    <CardContent className="text-center py-8">
                                        <Database className="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                                        <p className="text-muted-foreground">
                                            Estadísticas detalladas solo disponibles para Redis
                                        </p>
                                        <p className="text-sm text-muted-foreground mt-2">
                                            Driver actual: {cacheInfo.driver}
                                        </p>
                                    </CardContent>
                                </Card>
                            )}
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
