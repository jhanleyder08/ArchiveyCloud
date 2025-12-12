import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Database, TrendingUp, Users, Calendar, HardDrive, Star, Archive, FileText, FolderOpen, Clock, AlertTriangle, Shield } from 'lucide-react';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, LineChart, Line, AreaChart, Area } from 'recharts';

interface Estadisticas {
    total_indices: number;
    por_tipo: Record<string, number>;
    por_serie: Record<string, number>;
    documentos_vitales: number;
    documentos_historicos: number;
    tamaño_total: string;
    indices_recientes: number;
    indices_desactualizados: number;
    por_estado_conservacion: Record<string, number>;
    por_nivel_acceso: Record<string, number>;
    indices_por_mes: Array<{
        año: number;
        mes: number;
        total: number;
    }>;
    top_usuarios_indexadores: Array<{
        usuario_indexacion_id: number;
        total: number;
        usuario_indexacion: {
            name: string;
        };
    }>;
    crecimiento_indices: {
        ultima_semana: number;
        ultimo_mes: number;
        ultimo_año: number;
    };
}

interface Props {
    estadisticas: Estadisticas;
}

const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#84CC16', '#F97316'];

export default function IndicesEstadisticas({ estadisticas }: Props) {
    // Preparar datos para gráficos
    const datosPorTipo = Object.entries(estadisticas.por_tipo).map(([tipo, total]) => ({
        name: tipo === 'expediente' ? 'Expedientes' : 'Documentos',
        value: total,
        icon: tipo === 'expediente' ? 'folder' : 'file'
    }));

    const datosPorSerie = Object.entries(estadisticas.por_serie)
        .slice(0, 10)
        .map(([serie, total]) => ({
            name: serie.length > 20 ? serie.substring(0, 20) + '...' : serie,
            total: total
        }));

    const datosConservacion = Object.entries(estadisticas.por_estado_conservacion).map(([estado, total]) => ({
        name: estado.charAt(0).toUpperCase() + estado.slice(1),
        value: total,
        estado: estado
    }));

    const datosNivelAcceso = Object.entries(estadisticas.por_nivel_acceso).map(([nivel, total]) => ({
        name: nivel.charAt(0).toUpperCase() + nivel.slice(1),
        value: total,
        nivel: nivel
    }));

    // Preparar datos de crecimiento mensual
    const datosCrecimiento = estadisticas.indices_por_mes.map(item => ({
        mes: `${item.mes}/${item.año}`,
        total: item.total,
        fecha: new Date(item.año, item.mes - 1)
    })).sort((a, b) => a.fecha.getTime() - b.fecha.getTime());

    const getColorByEstado = (estado: string) => {
        const colores = {
            'excelente': '#10B981',
            'bueno': '#3B82F6',
            'regular': '#F59E0B',
            'malo': '#F97316',
            'critico': '#EF4444'
        };
        return colores[estado as keyof typeof colores] || '#6B7280';
    };

    const getColorByNivel = (nivel: string) => {
        const colores = {
            'publico': '#10B981',
            'restringido': '#F59E0B',
            'confidencial': '#F97316',
            'secreto': '#EF4444'
        };
        return colores[nivel as keyof typeof colores] || '#6B7280';
    };

    const calcularPorcentajeCrecimiento = () => {
        const { ultima_semana, ultimo_mes } = estadisticas.crecimiento_indices;
        if (ultimo_mes === 0) return 0;
        return ((ultima_semana * 4 - ultimo_mes) / ultimo_mes * 100).toFixed(1);
    };

    return (
        <AppLayout>
            <Head title="Estadísticas - Índices Electrónicos" />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <div className="flex items-center gap-3 mb-2">
                            <Link href="/admin/indices">
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Volver
                                </Button>
                            </Link>
                            <Database className="w-8 h-8 text-indigo-600" />
                        </div>
                        <h1 className="text-3xl font-bold text-gray-900">Estadísticas de Índices Electrónicos</h1>
                        <p className="text-gray-600 mt-1">
                            Dashboard completo con métricas y análisis de indexación
                        </p>
                    </div>
                </div>

                {/* Métricas principales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <Database className="h-8 w-8 text-indigo-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Índices</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.total_indices.toLocaleString()}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <TrendingUp className="h-8 w-8 text-green-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Crecimiento Semanal</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.crecimiento_indices.ultima_semana}</p>
                                    <p className="text-xs text-green-600">+{calcularPorcentajeCrecimiento()}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <Star className="h-8 w-8 text-yellow-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Documentos Vitales</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.documentos_vitales.toLocaleString()}</p>
                                    <p className="text-xs text-gray-500">
                                        {((estadisticas.documentos_vitales / estadisticas.total_indices) * 100).toFixed(1)}% del total
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <HardDrive className="h-8 w-8 text-purple-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Tamaño Total</p>
                                    <p className="text-2xl font-bold text-gray-900">{estadisticas.tamaño_total}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Alertas */}
                {estadisticas.indices_desactualizados > 0 && (
                    <Card className="mb-6 border-yellow-200 bg-yellow-50">
                        <CardContent className="p-4">
                            <div className="flex items-center">
                                <AlertTriangle className="h-5 w-5 text-yellow-600 mr-3" />
                                <div>
                                    <p className="text-yellow-800 font-medium">
                                        {estadisticas.indices_desactualizados} índices necesitan actualización
                                    </p>
                                    <p className="text-yellow-700 text-sm">
                                        Estos índices no han sido actualizados en más de 6 meses.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Gráficos principales */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {/* Distribución por tipo */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Database className="w-5 h-5" />
                                Distribución por Tipo
                            </CardTitle>
                            <CardDescription>
                                Índices de expedientes vs documentos
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={datosPorTipo}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }: any) => `${name} ${((percent as number) * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {datosPorTipo.map((_, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Estado de conservación */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Archive className="w-5 h-5" />
                                Estado de Conservación
                            </CardTitle>
                            <CardDescription>
                                Distribución por estado de conservación
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={datosConservacion}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Bar dataKey="value" fill="#3B82F6">
                                        {datosConservacion.map((entry: { name: string; value: number; estado: string }, index: number) => (
                                            <Cell key={`cell-estado-${index}`} fill={getColorByEstado(entry.estado)} />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Crecimiento temporal */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TrendingUp className="w-5 h-5" />
                            Crecimiento de Índices (Últimos 12 meses)
                        </CardTitle>
                        <CardDescription>
                            Evolución temporal de la indexación
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={400}>
                            <AreaChart data={datosCrecimiento}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="mes" />
                                <YAxis />
                                <Tooltip />
                                <Area type="monotone" dataKey="total" stroke="#3B82F6" fill="#3B82F6" fillOpacity={0.3} />
                            </AreaChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {/* Top series documentales */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="w-5 h-5" />
                                Top 10 Series Documentales
                            </CardTitle>
                            <CardDescription>
                                Series con mayor cantidad de índices
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={datosPorSerie} layout="horizontal">
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis type="number" />
                                    <YAxis dataKey="name" type="category" width={100} />
                                    <Tooltip />
                                    <Bar dataKey="total" fill="#10B981" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Niveles de acceso */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Shield className="w-5 h-5" />
                                Distribución por Nivel de Acceso
                            </CardTitle>
                            <CardDescription>
                                Clasificación de seguridad de documentos
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={datosNivelAcceso}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }: any) => `${name} ${((percent as number) * 100).toFixed(0)}%`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {datosNivelAcceso.map((entry: { name: string; value: number; nivel: string }, index: number) => (
                                            <Cell key={`cell-${index}`} fill={getColorByNivel(entry.nivel)} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Top usuarios indexadores */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="w-5 h-5" />
                            Top Usuarios Indexadores
                        </CardTitle>
                        <CardDescription>
                            Usuarios más activos en la indexación
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {estadisticas.top_usuarios_indexadores.slice(0, 10).map((usuario, index) => (
                                <div key={usuario.usuario_indexacion_id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div className="flex items-center gap-3">
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white font-bold ${
                                            index === 0 ? 'bg-yellow-500' : 
                                            index === 1 ? 'bg-gray-400' : 
                                            index === 2 ? 'bg-amber-600' : 'bg-blue-500'
                                        }`}>
                                            {index + 1}
                                        </div>
                                        <div>
                                            <p className="font-medium text-gray-900">{usuario.usuario_indexacion.name}</p>
                                            <p className="text-sm text-gray-500">
                                                {usuario.total} índice{usuario.total !== 1 ? 's' : ''} creado{usuario.total !== 1 ? 's' : ''}
                                            </p>
                                        </div>
                                    </div>
                                    <Badge variant="outline">
                                        {((usuario.total / estadisticas.total_indices) * 100).toFixed(1)}%
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Métricas adicionales */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="w-5 h-5" />
                                Actividad Reciente
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Última semana</span>
                                    <span className="font-medium">{estadisticas.crecimiento_indices.ultima_semana}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Último mes</span>
                                    <span className="font-medium">{estadisticas.crecimiento_indices.ultimo_mes}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Último año</span>
                                    <span className="font-medium">{estadisticas.crecimiento_indices.ultimo_año}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Star className="w-5 h-5" />
                                Información Especial
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Documentos vitales</span>
                                    <Badge className="bg-yellow-100 text-yellow-800">
                                        {estadisticas.documentos_vitales}
                                    </Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Valor histórico</span>
                                    <Badge className="bg-purple-100 text-purple-800">
                                        {estadisticas.documentos_historicos}
                                    </Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">% Especiales</span>
                                    <span className="font-medium">
                                        {(((estadisticas.documentos_vitales + estadisticas.documentos_historicos) / estadisticas.total_indices) * 100).toFixed(1)}%
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <AlertTriangle className="w-5 h-5" />
                                Mantenimiento
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Índices recientes</span>
                                    <Badge className="bg-green-100 text-green-800">
                                        {estadisticas.indices_recientes}
                                    </Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Desactualizados</span>
                                    <Badge className="bg-yellow-100 text-yellow-800">
                                        {estadisticas.indices_desactualizados}
                                    </Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">% Actualizados</span>
                                    <span className="font-medium">
                                        {(((estadisticas.total_indices - estadisticas.indices_desactualizados) / estadisticas.total_indices) * 100).toFixed(1)}%
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
