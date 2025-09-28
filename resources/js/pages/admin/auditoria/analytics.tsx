import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, TrendingUp, TrendingDown, BarChart3, PieChart as PieChartIcon, Users, Globe, Clock, AlertTriangle } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Badge } from '../../../components/ui/badge';
import { Progress } from '../../../components/ui/progress';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, LineChart, Line, AreaChart, Area } from 'recharts';

interface AnalisisAvanzado {
    resumen_periodo: {
        total_eventos: number;
        promedio_diario: number;
        usuarios_activos: number;
        ips_diferentes: number;
    };
    analisis_riesgos: {
        eventos_criticos: number;
        eventos_alto_riesgo: number;
        patrones_sospechosos: number;
        fallos_autenticacion: number;
    };
    tendencias_actividad: {
        crecimiento_semanal: number;
        horarios_pico: number[];
        dias_mas_activos: string[];
    };
    analisis_usuarios: {
        mas_activos: Array<{usuario_id: number, actividad: number, usuario: {name: string}}>;
        con_mas_riesgos: Array<{usuario_id: number, eventos_riesgo: number, usuario: {name: string}}>;
    };
    analisis_geografico: {
        paises: Array<{pais: string, total: number}>;
        ips_mas_activas: Array<{ip_address: string, total: number}>;
    };
    analisis_temporal: {
        actividad_por_hora: Array<{hora: number, total: number}>;
        actividad_por_dia_semana: Array<{dia: number, total: number}>;
    };
    anomalias_detectadas: {
        accesos_multiples_simultaneos: Array<{usuario_id: number, ip_address: string, total: number}>;
        cambios_masivos: Array<{usuario_id: number, total: number}>;
    };
}

interface Props {
    analisis: AnalisisAvanzado;
    filtros: {
        fecha_inicio: string;
        fecha_fin: string;
        periodo: string;
    };
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];
const RISK_COLORS = ['#dc2626', '#ea580c', '#d97706', '#16a34a'];

export default function AuditoriaAnalytics({ analisis, filtros }: Props) {
    const [periodoSeleccionado, setPeriodoSeleccionado] = useState(filtros.periodo);

    const handlePeriodoChange = (periodo: string) => {
        const params = new URLSearchParams();
        params.set('periodo', periodo);
        window.location.href = route('admin.auditoria.analytics') + '?' + params.toString();
    };

    // Preparar datos para gráficos
    const actividadPorHoraData = analisis.analisis_temporal.actividad_por_hora.map(item => ({
        hora: `${item.hora}:00`,
        eventos: item.total
    }));

    const actividadPorDiaData = analisis.analisis_temporal.actividad_por_dia_semana.map(item => {
        const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        return {
            dia: dias[item.dia - 1] || 'N/A',
            eventos: item.total
        };
    });

    const paisesData = analisis.analisis_geografico.paises.slice(0, 5).map((item, index) => ({
        name: item.pais,
        value: item.total,
        color: COLORS[index % COLORS.length]
    }));

    const usuariosActivosData = analisis.analisis_usuarios.mas_activos.slice(0, 10);
    const usuariosRiesgoData = analisis.analisis_usuarios.con_mas_riesgos.slice(0, 5);

    const calcularPorcentajeRiesgo = () => {
        const total = analisis.resumen_periodo.total_eventos;
        const riesgosos = analisis.analisis_riesgos.eventos_criticos + analisis.analisis_riesgos.eventos_alto_riesgo;
        return total > 0 ? (riesgosos / total) * 100 : 0;
    };

    const getNivelRiesgoColor = (porcentaje: number) => {
        if (porcentaje > 20) return 'text-red-600';
        if (porcentaje > 10) return 'text-orange-600';
        if (porcentaje > 5) return 'text-yellow-600';
        return 'text-green-600';
    };

    return (
        <AppLayout>
            <Head title="Analytics - Auditoría Avanzada" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={route('admin.auditoria.index')}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Analytics de Auditoría</h1>
                            <p className="text-muted-foreground">
                                Análisis avanzado de patrones y tendencias de seguridad
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Select value={periodoSeleccionado} onValueChange={handlePeriodoChange}>
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Seleccionar período" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7d">Últimos 7 días</SelectItem>
                                <SelectItem value="30d">Últimos 30 días</SelectItem>
                                <SelectItem value="90d">Últimos 90 días</SelectItem>
                                <SelectItem value="custom">Personalizado</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Resumen Ejecutivo */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <BarChart3 className="h-8 w-8 text-blue-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Eventos</p>
                                    <p className="text-2xl font-bold">{analisis.resumen_periodo.total_eventos.toLocaleString()}</p>
                                    <p className="text-xs text-gray-500">
                                        {analisis.resumen_periodo.promedio_diario} eventos/día
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Users className="h-8 w-8 text-green-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Usuarios Activos</p>
                                    <p className="text-2xl font-bold">{analisis.resumen_periodo.usuarios_activos}</p>
                                    <p className="text-xs text-gray-500">
                                        Usuarios únicos
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Globe className="h-8 w-8 text-purple-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">IPs Diferentes</p>
                                    <p className="text-2xl font-bold">{analisis.resumen_periodo.ips_diferentes}</p>
                                    <p className="text-xs text-gray-500">
                                        Ubicaciones únicas
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <AlertTriangle className="h-8 w-8 text-red-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Nivel de Riesgo</p>
                                    <p className={`text-2xl font-bold ${getNivelRiesgoColor(calcularPorcentajeRiesgo())}`}>
                                        {calcularPorcentajeRiesgo().toFixed(1)}%
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        Eventos de riesgo
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="riesgos" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="riesgos">Análisis de Riesgos</TabsTrigger>
                        <TabsTrigger value="usuarios">Análisis de Usuarios</TabsTrigger>
                        <TabsTrigger value="geografico">Análisis Geográfico</TabsTrigger>
                        <TabsTrigger value="temporal">Análisis Temporal</TabsTrigger>
                        <TabsTrigger value="anomalias">Anomalías</TabsTrigger>
                    </TabsList>

                    <TabsContent value="riesgos" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Métricas de Riesgo */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Métricas de Seguridad</CardTitle>
                                    <CardDescription>Eventos que requieren atención inmediata</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <AlertTriangle className="h-5 w-5 text-red-500" />
                                            <span className="font-medium">Eventos Críticos</span>
                                        </div>
                                        <Badge variant="destructive">{analisis.analisis_riesgos.eventos_criticos}</Badge>
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <AlertTriangle className="h-5 w-5 text-orange-500" />
                                            <span className="font-medium">Alto Riesgo</span>
                                        </div>
                                        <Badge variant="secondary">{analisis.analisis_riesgos.eventos_alto_riesgo}</Badge>
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <TrendingUp className="h-5 w-5 text-yellow-500" />
                                            <span className="font-medium">Patrones Sospechosos</span>
                                        </div>
                                        <Badge variant="outline">{analisis.analisis_riesgos.patrones_sospechosos}</Badge>
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <Users className="h-5 w-5 text-blue-500" />
                                            <span className="font-medium">Fallos de Autenticación</span>
                                        </div>
                                        <Badge variant="outline">{analisis.analisis_riesgos.fallos_autenticacion}</Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Distribución Geográfica */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Distribución por País</CardTitle>
                                    <CardDescription>Origen geográfico de los eventos</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={250}>
                                        <PieChart>
                                            <Pie
                                                data={paisesData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                                outerRadius={80}
                                                fill="#8884d8"
                                                dataKey="value"
                                            >
                                                {paisesData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="usuarios" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Usuarios Más Activos */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Usuarios Más Activos</CardTitle>
                                    <CardDescription>Top 10 usuarios por actividad</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {usuariosActivosData.map((usuario, index) => (
                                            <div key={usuario.usuario_id} className="flex items-center justify-between">
                                                <div className="flex items-center space-x-3">
                                                    <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <span className="text-sm font-bold text-blue-600">{index + 1}</span>
                                                    </div>
                                                    <div>
                                                        <p className="font-medium">{usuario.usuario?.name || 'Usuario N/A'}</p>
                                                        <p className="text-sm text-gray-500">ID: {usuario.usuario_id}</p>
                                                    </div>
                                                </div>
                                                <Badge variant="outline">{usuario.actividad} eventos</Badge>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Usuarios con Más Riesgos */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Usuarios de Alto Riesgo</CardTitle>
                                    <CardDescription>Usuarios con más eventos de riesgo</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {usuariosRiesgoData.map((usuario, index) => (
                                            <div key={usuario.usuario_id} className="flex items-center justify-between">
                                                <div className="flex items-center space-x-3">
                                                    <div className="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                                        <AlertTriangle className="h-4 w-4 text-red-600" />
                                                    </div>
                                                    <div>
                                                        <p className="font-medium">{usuario.usuario?.name || 'Usuario N/A'}</p>
                                                        <p className="text-sm text-gray-500">ID: {usuario.usuario_id}</p>
                                                    </div>
                                                </div>
                                                <Badge variant="destructive">{usuario.eventos_riesgo} riesgos</Badge>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="geografico" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>IPs Más Activas</CardTitle>
                                <CardDescription>Direcciones IP con mayor actividad</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {analisis.analisis_geografico.ips_mas_activas.slice(0, 10).map((ip, index) => (
                                        <div key={ip.ip_address} className="flex items-center justify-between">
                                            <div className="flex items-center space-x-3">
                                                <div className="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <Globe className="h-4 w-4 text-purple-600" />
                                                </div>
                                                <div>
                                                    <p className="font-mono">{ip.ip_address}</p>
                                                    <p className="text-sm text-gray-500">Rango #{index + 1}</p>
                                                </div>
                                            </div>
                                            <Badge variant="outline">{ip.total} eventos</Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="temporal" className="space-y-4">
                        <div className="grid grid-cols-1 gap-6">
                            {/* Actividad por Hora */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Actividad por Hora del Día</CardTitle>
                                    <CardDescription>Distribución horaria de eventos</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <AreaChart data={actividadPorHoraData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="hora" />
                                            <YAxis />
                                            <Tooltip />
                                            <Area type="monotone" dataKey="eventos" stroke="#8884d8" fill="#8884d8" fillOpacity={0.6} />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            {/* Actividad por Día de la Semana */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Actividad por Día de la Semana</CardTitle>
                                    <CardDescription>Distribución semanal de eventos</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={actividadPorDiaData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="dia" />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar dataKey="eventos" fill="#3b82f6" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="anomalias" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Accesos Múltiples Simultáneos */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Accesos Múltiples Simultáneos</CardTitle>
                                    <CardDescription>Usuarios con alta concurrencia de sesiones</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {analisis.anomalias_detectadas.accesos_multiples_simultaneos.length > 0 ? (
                                        <div className="space-y-3">
                                            {analisis.anomalias_detectadas.accesos_multiples_simultaneos.map((anomalia, index) => (
                                                <div key={index} className="p-3 border rounded-lg bg-yellow-50">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <p className="font-medium">Usuario ID: {anomalia.usuario_id}</p>
                                                            <p className="text-sm text-gray-600">IP: {anomalia.ip_address}</p>
                                                        </div>
                                                        <Badge variant="secondary">{anomalia.total} accesos</Badge>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-center text-gray-500 py-8">No se detectaron anomalías de accesos múltiples</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Cambios Masivos */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Cambios Masivos</CardTitle>
                                    <CardDescription>Usuarios con alta actividad de modificación</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {analisis.anomalias_detectadas.cambios_masivos.length > 0 ? (
                                        <div className="space-y-3">
                                            {analisis.anomalias_detectadas.cambios_masivos.map((anomalia, index) => (
                                                <div key={index} className="p-3 border rounded-lg bg-orange-50">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <p className="font-medium">Usuario ID: {anomalia.usuario_id}</p>
                                                            <p className="text-sm text-gray-600">Alta actividad de modificación</p>
                                                        </div>
                                                        <Badge variant="secondary">{anomalia.total} cambios</Badge>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-center text-gray-500 py-8">No se detectaron anomalías de cambios masivos</p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
