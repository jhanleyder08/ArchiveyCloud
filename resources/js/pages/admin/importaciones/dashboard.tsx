import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { 
    BarChart3, 
    Clock, 
    Download, 
    FileText, 
    Plus, 
    TrendingUp,
    Users,
    Database,
    CheckCircle,
    XCircle,
    AlertTriangle,
    Play,
    Activity
} from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Badge } from '../../../components/ui/badge';
import { Progress } from '../../../components/ui/progress';
import { PieChart, Pie, Cell, AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

interface ImportacionReciente {
    id: number;
    nombre: string;
    tipo: string;
    estado: string;
    porcentaje_avance: number;
    total_registros: number;
    registros_procesados: number;
    registros_exitosos: number;
    registros_fallidos: number;
    created_at: string;
    usuario: {
        name: string;
    };
}

interface ImportacionProcesando {
    id: number;
    nombre: string;
    tipo: string;
    porcentaje_avance: number;
    total_registros: number;
    registros_procesados: number;
    fecha_inicio: string;
    tiempo_transcurrido: number;
    usuario: {
        name: string;
    };
}

interface Estadisticas {
    total: number;
    pendientes: number;
    procesando: number;
    completadas: number;
    fallidas: number;
    hoy: number;
    esta_semana: number;
    este_mes: number;
}

interface EstadisticasPorTipo {
    [key: string]: number;
}

interface Velocidad {
    tiempo_promedio: number;
    registros_por_minuto: number;
}

interface Props {
    estadisticas: Estadisticas;
    estadisticasPorTipo: EstadisticasPorTipo;
    velocidad: Velocidad;
    importacionesRecientes: ImportacionReciente[];
    importacionesProcesando: ImportacionProcesando[];
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

const tiposLabels: { [key: string]: string } = {
    expedientes: 'Expedientes',
    documentos: 'Documentos',
    series: 'Series Documentales',
    subseries: 'Subseries',
    usuarios: 'Usuarios',
    trd: 'TRD',
    certificados: 'Certificados',
    mixto: 'Mixto'
};

const estadosConfig = {
    pendiente: { icon: Clock, color: 'bg-yellow-100 text-yellow-800', label: 'Pendiente' },
    procesando: { icon: Play, color: 'bg-blue-100 text-blue-800', label: 'Procesando' },
    completada: { icon: CheckCircle, color: 'bg-green-100 text-green-800', label: 'Completada' },
    fallida: { icon: XCircle, color: 'bg-red-100 text-red-800', label: 'Fallida' },
    cancelada: { icon: AlertTriangle, color: 'bg-gray-100 text-gray-800', label: 'Cancelada' }
};

export default function ImportacionesDashboard({ 
    estadisticas, 
    estadisticasPorTipo, 
    velocidad, 
    importacionesRecientes, 
    importacionesProcesando 
}: Props) {
    const [autoRefresh, setAutoRefresh] = useState(true);

    // Auto-refresh cada 30 segundos
    useEffect(() => {
        if (!autoRefresh) return;

        const interval = setInterval(() => {
            window.location.reload();
        }, 30000);

        return () => clearInterval(interval);
    }, [autoRefresh]);

    // Datos para gráfico de torta
    const dataTipos = Object.entries(estadisticasPorTipo).map(([tipo, cantidad]) => ({
        name: tiposLabels[tipo] || tipo,
        value: cantidad,
        tipo
    }));

    // Datos para gráfico de área (simulado - en producción vendría del backend)
    const dataAreaSemana = [
        { dia: 'Lun', importaciones: 12 },
        { dia: 'Mar', importaciones: 8 },
        { dia: 'Mié', importaciones: 15 },
        { dia: 'Jue', importaciones: 20 },
        { dia: 'Vie', importaciones: 18 },
        { dia: 'Sáb', importaciones: 5 },
        { dia: 'Dom', importaciones: 3 }
    ];

    const formatearTiempo = (segundos: number): string => {
        if (segundos < 60) return `${segundos}s`;
        if (segundos < 3600) {
            const min = Math.floor(segundos / 60);
            const seg = segundos % 60;
            return `${min}m ${seg}s`;
        }
        const horas = Math.floor(segundos / 3600);
        const min = Math.floor((segundos % 3600) / 60);
        return `${horas}h ${min}m`;
    };

    return (
        <AppLayout>
            <Head title="Sistema de Migración e Importación" />
            
            <div className="container mx-auto p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                            <Download className="h-8 w-8 text-blue-600" />
                            Sistema de Migración e Importación
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Gestiona la importación de datos desde diferentes fuentes y formatos
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <Activity className={`h-4 w-4 ${autoRefresh ? 'text-green-500' : 'text-gray-400'}`} />
                            <span className="text-sm text-muted-foreground">
                                Auto-refresh {autoRefresh ? 'ON' : 'OFF'}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setAutoRefresh(!autoRefresh)}
                            >
                                {autoRefresh ? 'Pausar' : 'Activar'}
                            </Button>
                        </div>
                        <Button asChild>
                            <Link href="/admin/importaciones/crear">
                                <Plus className="h-4 w-4 mr-2" />
                                Nueva Importación
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Estadísticas principales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Importaciones</CardTitle>
                            <Database className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.total}</div>
                            <p className="text-xs text-muted-foreground">
                                +{estadisticas.hoy} hoy
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">En Procesamiento</CardTitle>
                            <Play className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{estadisticas.procesando}</div>
                            <p className="text-xs text-muted-foreground">
                                {estadisticas.pendientes} pendientes
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Completadas</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{estadisticas.completadas}</div>
                            <p className="text-xs text-muted-foreground">
                                {((estadisticas.completadas / estadisticas.total) * 100).toFixed(1)}% del total
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Velocidad Promedio</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {velocidad.registros_por_minuto ? Math.round(velocidad.registros_por_minuto) : 0}
                            </div>
                            <p className="text-xs text-muted-foreground">registros/min</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Gráficos */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Distribución por tipo */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Distribución por Tipo</CardTitle>
                            <CardDescription>
                                Importaciones por tipo de datos
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={dataTipos}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {dataTipos.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Actividad de la semana */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Actividad de la Semana</CardTitle>
                            <CardDescription>
                                Importaciones realizadas por día
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <AreaChart data={dataAreaSemana}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="dia" />
                                    <YAxis />
                                    <Tooltip />
                                    <Area
                                        type="monotone"
                                        dataKey="importaciones"
                                        stroke="#8884d8"
                                        fill="#8884d8"
                                        fillOpacity={0.6}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Importaciones en procesamiento */}
                {importacionesProcesando.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Play className="h-5 w-5 text-blue-600" />
                                Importaciones en Procesamiento
                            </CardTitle>
                            <CardDescription>
                                Estado actual de las importaciones en curso
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {importacionesProcesando.map((importacion) => (
                                    <div key={importacion.id} className="border rounded-lg p-4">
                                        <div className="flex items-center justify-between mb-2">
                                            <div className="flex items-center gap-3">
                                                <Link
                                                    href={`/admin/importaciones/${importacion.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {importacion.nombre}
                                                </Link>
                                                <Badge variant="outline">
                                                    {tiposLabels[importacion.tipo]}
                                                </Badge>
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {importacion.usuario.name}
                                            </div>
                                        </div>
                                        
                                        <div className="space-y-2">
                                            <div className="flex items-center justify-between text-sm">
                                                <span>Progreso: {importacion.registros_procesados} / {importacion.total_registros}</span>
                                                <span className="font-medium">{importacion.porcentaje_avance.toFixed(1)}%</span>
                                            </div>
                                            <Progress value={importacion.porcentaje_avance} className="h-2" />
                                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                <span>Tiempo transcurrido: {formatearTiempo(importacion.tiempo_transcurrido || 0)}</span>
                                                <span>Iniciado: {new Date(importacion.fecha_inicio).toLocaleString()}</span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Importaciones recientes */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            <span className="flex items-center gap-2">
                                <Clock className="h-5 w-5" />
                                Importaciones Recientes
                            </span>
                            <Link href="/admin/importaciones/listado">
                                <Button variant="outline" size="sm">
                                    Ver todas
                                </Button>
                            </Link>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {importacionesRecientes.map((importacion) => {
                                const estadoConfig = estadosConfig[importacion.estado as keyof typeof estadosConfig];
                                const IconoEstado = estadoConfig?.icon || FileText;
                                
                                return (
                                    <div key={importacion.id} className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                        <div className="flex items-center gap-3">
                                            <IconoEstado className="h-4 w-4 text-muted-foreground" />
                                            <div>
                                                <Link
                                                    href={`/admin/importaciones/${importacion.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {importacion.nombre}
                                                </Link>
                                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <span>{tiposLabels[importacion.tipo]}</span>
                                                    <span>•</span>
                                                    <span>{importacion.usuario.name}</span>
                                                    <span>•</span>
                                                    <span>{new Date(importacion.created_at).toLocaleDateString()}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-3">
                                            <div className="text-right text-sm">
                                                {importacion.estado === 'completada' && (
                                                    <>
                                                        <div className="text-green-600 font-medium">
                                                            {importacion.registros_exitosos} exitosos
                                                        </div>
                                                        {importacion.registros_fallidos > 0 && (
                                                            <div className="text-red-600">
                                                                {importacion.registros_fallidos} fallidos
                                                            </div>
                                                        )}
                                                    </>
                                                )}
                                                {importacion.estado === 'procesando' && (
                                                    <div className="text-blue-600 font-medium">
                                                        {importacion.porcentaje_avance.toFixed(1)}%
                                                    </div>
                                                )}
                                            </div>
                                            <Badge className={estadoConfig?.color}>
                                                {estadoConfig?.label || importacion.estado}
                                            </Badge>
                                        </div>
                                    </div>
                                );
                            })}
                            
                            {importacionesRecientes.length === 0 && (
                                <div className="text-center py-8 text-muted-foreground">
                                    <Database className="h-12 w-12 mx-auto mb-3 opacity-50" />
                                    <p>No hay importaciones recientes</p>
                                    <p className="text-sm">Crea tu primera importación para comenzar</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Enlaces rápidos */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card className="hover:shadow-md transition-shadow cursor-pointer">
                        <Link href="/admin/importaciones/crear">
                            <CardHeader className="text-center">
                                <Plus className="h-8 w-8 mx-auto text-blue-600" />
                                <CardTitle className="text-lg">Nueva Importación</CardTitle>
                                <CardDescription>
                                    Crear una nueva importación de datos
                                </CardDescription>
                            </CardHeader>
                        </Link>
                    </Card>

                    <Card className="hover:shadow-md transition-shadow cursor-pointer">
                        <Link href="/admin/importaciones/listado">
                            <CardHeader className="text-center">
                                <BarChart3 className="h-8 w-8 mx-auto text-green-600" />
                                <CardTitle className="text-lg">Ver Todas</CardTitle>
                                <CardDescription>
                                    Gestionar todas las importaciones
                                </CardDescription>
                            </CardHeader>
                        </Link>
                    </Card>

                    <Card className="hover:shadow-md transition-shadow cursor-pointer">
                        <Link href="/admin/documentos">
                            <CardHeader className="text-center">
                                <FileText className="h-8 w-8 mx-auto text-purple-600" />
                                <CardTitle className="text-lg">Ver Documentos</CardTitle>
                                <CardDescription>
                                    Revisar documentos importados
                                </CardDescription>
                            </CardHeader>
                        </Link>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
