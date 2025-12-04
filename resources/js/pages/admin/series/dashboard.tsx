import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    FileText, 
    Folder, 
    TrendingUp, 
    Users, 
    Clock, 
    Archive,
    BarChart3,
    RefreshCw,
    AlertTriangle,
    CheckCircle
} from 'lucide-react';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, AreaChart, Area } from 'recharts';

// Interfaces TypeScript
interface EstadisticasSeries {
    total_series: number;
    series_activas: number;
    series_inactivas: number;
    total_subseries: number;
    total_expedientes: number;
    series_por_trd: { trd: string; cantidad: number; color: string }[];
    distribucion_disposicion: { tipo: string; cantidad: number; color: string }[];
    series_mas_usadas: { serie: string; expedientes: number; subseries: number }[];
    actividad_mensual: { mes: string; series_creadas: number; expedientes_creados: number }[];
    tiempos_retencion: { rango: string; cantidad: number }[];
}

interface Props {
    estadisticas: EstadisticasSeries;
}

const COLORS = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];

export default function SeriesDashboard({ estadisticas }: Props) {
    const [refreshing, setRefreshing] = useState(false);

    const handleRefresh = async () => {
        setRefreshing(true);
        try {
            window.location.reload();
        } catch (error) {
            console.error('Error refreshing:', error);
        } finally {
            setTimeout(() => setRefreshing(false), 1000);
        }
    };

    const getDisposicionIcon = (tipo: string) => {
        switch (tipo) {
            case 'conservacion_permanente': return <Archive className="h-4 w-4" />;
            case 'eliminacion': return <AlertTriangle className="h-4 w-4" />;
            case 'transferencia': return <TrendingUp className="h-4 w-4" />;
            default: return <FileText className="h-4 w-4" />;
        }
    };

    const getDisposicionColor = (tipo: string) => {
        switch (tipo) {
            case 'conservacion_permanente': return 'bg-blue-100 text-blue-800';
            case 'eliminacion': return 'bg-red-100 text-red-800';
            case 'transferencia': return 'bg-green-100 text-green-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout>
            <Head title="Dashboard Series Documentales - ArchiveyCloud" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Dashboard Series Documentales</h1>
                        <p className="text-gray-600 mt-1">
                            Análisis y métricas del sistema de series documentales
                        </p>
                    </div>
                    <Button
                        onClick={handleRefresh}
                        disabled={refreshing}
                        variant="outline"
                    >
                        <RefreshCw className={`h-4 w-4 mr-2 ${refreshing ? 'animate-spin' : ''}`} />
                        Actualizar
                    </Button>
                </div>

                {/* Métricas Principales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Series</CardTitle>
                            <FileText className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.total_series}</div>
                            <p className="text-xs text-muted-foreground">
                                {estadisticas.series_activas} activas
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Subseries</CardTitle>
                            <Folder className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.total_subseries}</div>
                            <p className="text-xs text-muted-foreground">
                                Total en el sistema
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Expedientes</CardTitle>
                            <Archive className="h-4 w-4 text-purple-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{estadisticas.total_expedientes}</div>
                            <p className="text-xs text-muted-foreground">
                                Vinculados a series
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Estado Sistema</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {Math.round((estadisticas.series_activas / estadisticas.total_series) * 100)}%
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Series activas
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabs con Análisis Detallado */}
                <Tabs defaultValue="distribucion" className="space-y-4">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="distribucion">Distribución</TabsTrigger>
                        <TabsTrigger value="actividad">Actividad</TabsTrigger>
                        <TabsTrigger value="ranking">Rankings</TabsTrigger>
                        <TabsTrigger value="retencion">Retención</TabsTrigger>
                    </TabsList>

                    {/* Tab: Distribución */}
                    <TabsContent value="distribucion" className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Series por TRD */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Series por TRD</CardTitle>
                                    <CardDescription>Distribución de series por tabla de retención</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <PieChart>
                                            <Pie
                                                data={estadisticas.series_por_trd}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={({ trd, percent }: any) => `${trd}: ${(percent * 100).toFixed(0)}%`}
                                                outerRadius={100}
                                                fill="#8884d8"
                                                dataKey="cantidad"
                                            >
                                                {estadisticas.series_por_trd.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color || COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            {/* Disposición Final */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Tipos de Disposición Final</CardTitle>
                                    <CardDescription>Distribución por tipo de disposición</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {estadisticas.distribucion_disposicion.map((item, index) => (
                                            <div key={index} className="flex items-center justify-between">
                                                <div className="flex items-center space-x-3">
                                                    {getDisposicionIcon(item.tipo)}
                                                    <span className="font-medium">{item.tipo.replace('_', ' ')}</span>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Badge className={getDisposicionColor(item.tipo)}>
                                                        {item.cantidad}
                                                    </Badge>
                                                    <div className="w-20 bg-gray-200 rounded-full h-2">
                                                        <div 
                                                            className="bg-blue-600 h-2 rounded-full"
                                                            style={{
                                                                width: `${(item.cantidad / estadisticas.total_series) * 100}%`
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Tab: Actividad */}
                    <TabsContent value="actividad" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Actividad Mensual</CardTitle>
                                <CardDescription>Creación de series y expedientes por mes</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={400}>
                                    <AreaChart data={estadisticas.actividad_mensual}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="mes" />
                                        <YAxis />
                                        <Tooltip />
                                        <Legend />
                                        <Area 
                                            type="monotone" 
                                            dataKey="series_creadas" 
                                            stackId="1"
                                            stroke="#3B82F6" 
                                            fill="#3B82F6" 
                                            name="Series Creadas"
                                        />
                                        <Area 
                                            type="monotone" 
                                            dataKey="expedientes_creados" 
                                            stackId="1"
                                            stroke="#10B981" 
                                            fill="#10B981" 
                                            name="Expedientes Creados"
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab: Rankings */}
                    <TabsContent value="ranking" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Series Más Utilizadas</CardTitle>
                                <CardDescription>Ranking por número de expedientes y subseries</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {estadisticas.series_mas_usadas.map((serie, index) => (
                                        <div key={index} className="flex items-center justify-between p-4 border rounded-lg">
                                            <div className="flex items-center space-x-3">
                                                <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white font-bold ${
                                                    index === 0 ? 'bg-yellow-500' : 
                                                    index === 1 ? 'bg-gray-400' : 
                                                    index === 2 ? 'bg-amber-600' : 'bg-blue-500'
                                                }`}>
                                                    {index + 1}
                                                </div>
                                                <div>
                                                    <h4 className="font-semibold">{serie.serie}</h4>
                                                    <p className="text-sm text-gray-600">
                                                        {serie.subseries} subseries
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-2xl font-bold text-blue-600">
                                                    {serie.expedientes}
                                                </div>
                                                <div className="text-sm text-gray-600">expedientes</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab: Retención */}
                    <TabsContent value="retencion" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Análisis de Tiempos de Retención</CardTitle>
                                <CardDescription>Distribución de series por rangos de retención</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={400}>
                                    <BarChart data={estadisticas.tiempos_retencion}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="rango" />
                                        <YAxis />
                                        <Tooltip />
                                        <Bar dataKey="cantidad" fill="#3B82F6" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
