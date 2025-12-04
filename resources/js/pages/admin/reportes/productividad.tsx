import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import { 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    Legend, 
    LineChart,
    Line,
    Area,
    AreaChart,
    PieChart,
    Pie,
    Cell,
    ResponsiveContainer
} from 'recharts';
import { 
    Users, 
    FileText, 
    Archive, 
    TrendingUp,
    Download,
    Activity,
    Clock,
    Star,
    Award,
    Target
} from 'lucide-react';

interface DocumentoPorUsuario {
    created_by: number;
    total: number;
    creator: {
        id: number;
        name: string;
        email: string;
    };
}

interface ExpedientePorUsuario {
    created_by: number;
    total: number;
    creator: {
        id: number;
        name: string;
        email: string;
    };
}

interface ActividadPorDia {
    fecha: string;
    total_acciones: number;
}

interface AccionFrecuente {
    accion: string;
    total: number;
}

interface Props {
    documentosPorUsuario: DocumentoPorUsuario[];
    expedientesPorUsuario: ExpedientePorUsuario[];
    actividadPorDia: ActividadPorDia[];
    accionesFrecuentes: AccionFrecuente[];
    periodo: string;
    fechaInicio: string;
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

export default function ProductividadReporte({ 
    documentosPorUsuario, 
    expedientesPorUsuario, 
    actividadPorDia, 
    accionesFrecuentes,
    periodo,
    fechaInicio
}: Props) {
    const { data, setData, get, processing } = useForm({
        periodo: periodo
    });

    const aplicarFiltros = () => {
        get(route('admin.reportes.productividad'), {
            data: { periodo: data.periodo },
            preserveState: true
        });
    };

    const exportarReporte = () => {
        // Implementar exportación
        console.log('Exportar reporte de productividad');
    };

    // Calcular estadísticas
    const totalDocumentos = documentosPorUsuario.reduce((acc, item) => acc + item.total, 0);
    const totalExpedientes = expedientesPorUsuario.reduce((acc, item) => acc + item.total, 0);
    const totalActividad = actividadPorDia.reduce((acc, item) => acc + item.total_acciones, 0);
    const promedioActividadDiaria = Math.round(totalActividad / actividadPorDia.length) || 0;

    // Top performers
    const topDocumentCreator = documentosPorUsuario[0];
    const topExpedientCreator = expedientesPorUsuario[0];

    // Datos para gráficos
    const datosActividadDiaria = actividadPorDia.map(item => ({
        fecha: new Date(item.fecha).toLocaleDateString('es-ES', { month: 'short', day: 'numeric' }),
        actividad: item.total_acciones
    }));

    const datosUsuariosTop = documentosPorUsuario.slice(0, 10).map((item, index) => ({
        usuario: item.creator.name,
        documentos: item.total,
        expedientes: expedientesPorUsuario.find(exp => exp.created_by === item.created_by)?.total || 0,
        color: COLORS[index % COLORS.length]
    }));

    return (
        <AppLayout>
            <Head title="Productividad - Reportes" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center space-x-2">
                            <Link 
                                href={route('admin.reportes.dashboard')}
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                Reportes
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Productividad</span>
                        </div>
                        <h1 className="text-2xl font-bold mt-1">Reporte de Productividad</h1>
                        <p className="text-muted-foreground">Análisis de productividad de usuarios en los últimos {periodo} días</p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Select value={data.periodo} onValueChange={(value) => setData('periodo', value)}>
                            <SelectTrigger className="w-40">
                                <SelectValue placeholder="Período" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7">7 días</SelectItem>
                                <SelectItem value="15">15 días</SelectItem>
                                <SelectItem value="30">30 días</SelectItem>
                                <SelectItem value="60">60 días</SelectItem>
                                <SelectItem value="90">90 días</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button onClick={aplicarFiltros} disabled={processing}>
                            {processing ? 'Aplicando...' : 'Aplicar'}
                        </Button>
                        <Button variant="outline" onClick={exportarReporte}>
                            <Download className="h-4 w-4 mr-2" />
                            Exportar
                        </Button>
                    </div>
                </div>

                {/* Métricas principales */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Documentos</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalDocumentos.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                En los últimos {periodo} días
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Expedientes</CardTitle>
                            <Archive className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalExpedientes.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                Creados en el período
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Actividad</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalActividad.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                Acciones realizadas
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Promedio Diario</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{promedioActividadDiaria}</div>
                            <p className="text-xs text-muted-foreground">
                                Acciones por día
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Top Performers */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Award className="h-5 w-5 text-yellow-500" />
                                <span>Usuario Más Productivo - Documentos</span>
                            </CardTitle>
                            <CardDescription>Usuario que ha creado más documentos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {topDocumentCreator ? (
                                <div className="flex items-center justify-between p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <div className="flex items-center space-x-3">
                                        <div className="flex items-center justify-center w-12 h-12 bg-yellow-500 text-white rounded-full">
                                            <Star className="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h3 className="font-semibold">{topDocumentCreator.creator.name}</h3>
                                            <p className="text-sm text-muted-foreground">{topDocumentCreator.creator.email}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-2xl font-bold text-yellow-600">{topDocumentCreator.total}</div>
                                        <p className="text-sm text-muted-foreground">documentos</p>
                                    </div>
                                </div>
                            ) : (
                                <p className="text-muted-foreground">No hay datos disponibles</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Award className="h-5 w-5 text-blue-500" />
                                <span>Usuario Más Productivo - Expedientes</span>
                            </CardTitle>
                            <CardDescription>Usuario que ha creado más expedientes</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {topExpedientCreator ? (
                                <div className="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <div className="flex items-center space-x-3">
                                        <div className="flex items-center justify-center w-12 h-12 bg-blue-500 text-white rounded-full">
                                            <Target className="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h3 className="font-semibold">{topExpedientCreator.creator.name}</h3>
                                            <p className="text-sm text-muted-foreground">{topExpedientCreator.creator.email}</p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-2xl font-bold text-blue-600">{topExpedientCreator.total}</div>
                                        <p className="text-sm text-muted-foreground">expedientes</p>
                                    </div>
                                </div>
                            ) : (
                                <p className="text-muted-foreground">No hay datos disponibles</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Gráficos principales */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Actividad Diaria</CardTitle>
                            <CardDescription>Número de acciones realizadas por día</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <AreaChart data={datosActividadDiaria}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="fecha" />
                                    <YAxis />
                                    <Tooltip />
                                    <Area 
                                        type="monotone" 
                                        dataKey="actividad" 
                                        stroke="#8884d8" 
                                        fill="#8884d8"
                                        fillOpacity={0.6}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Top 10 Usuarios - Documentos</CardTitle>
                            <CardDescription>Usuarios más productivos creando documentos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={datosUsuariosTop}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="usuario" angle={-45} textAnchor="end" height={80} />
                                    <YAxis />
                                    <Tooltip />
                                    <Legend />
                                    <Bar dataKey="documentos" fill="#8884d8" name="Documentos" />
                                    <Bar dataKey="expedientes" fill="#82ca9d" name="Expedientes" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Tablas detalladas */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Ranking de usuarios - Documentos */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Users className="h-5 w-5" />
                                <span>Ranking - Documentos</span>
                            </CardTitle>
                            <CardDescription>Top 15 usuarios por documentos creados</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3 max-h-96 overflow-y-auto">
                                {documentosPorUsuario.slice(0, 15).map((usuario, index) => (
                                    <div key={usuario.created_by} className="flex items-center justify-between p-3 border rounded">
                                        <div className="flex items-center space-x-3">
                                            <div className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold text-white ${
                                                index === 0 ? 'bg-yellow-500' : 
                                                index === 1 ? 'bg-gray-400' : 
                                                index === 2 ? 'bg-amber-600' : 
                                                'bg-gray-300 text-gray-700'
                                            }`}>
                                                {index + 1}
                                            </div>
                                            <div>
                                                <h4 className="font-medium">{usuario.creator.name}</h4>
                                                <p className="text-sm text-muted-foreground">{usuario.creator.email}</p>
                                            </div>
                                        </div>
                                        <Badge variant="secondary">
                                            {usuario.total} documentos
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Ranking de usuarios - Expedientes */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Archive className="h-5 w-5" />
                                <span>Ranking - Expedientes</span>
                            </CardTitle>
                            <CardDescription>Top 15 usuarios por expedientes creados</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3 max-h-96 overflow-y-auto">
                                {expedientesPorUsuario.slice(0, 15).map((usuario, index) => (
                                    <div key={usuario.created_by} className="flex items-center justify-between p-3 border rounded">
                                        <div className="flex items-center space-x-3">
                                            <div className={`flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold text-white ${
                                                index === 0 ? 'bg-yellow-500' : 
                                                index === 1 ? 'bg-gray-400' : 
                                                index === 2 ? 'bg-amber-600' : 
                                                'bg-gray-300 text-gray-700'
                                            }`}>
                                                {index + 1}
                                            </div>
                                            <div>
                                                <h4 className="font-medium">{usuario.creator.name}</h4>
                                                <p className="text-sm text-muted-foreground">{usuario.creator.email}</p>
                                            </div>
                                        </div>
                                        <Badge variant="secondary">
                                            {usuario.total} expedientes
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Acciones más frecuentes */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Activity className="h-5 w-5" />
                            <span>Acciones Más Frecuentes</span>
                        </CardTitle>
                        <CardDescription>Tipos de acciones realizadas con mayor frecuencia</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-3">
                                {accionesFrecuentes.map((accion, index) => (
                                    <div key={accion.accion} className="flex items-center justify-between p-3 border rounded">
                                        <div className="flex items-center space-x-3">
                                            <div className="flex items-center justify-center w-8 h-8 bg-primary text-primary-foreground rounded-full text-sm">
                                                {index + 1}
                                            </div>
                                            <h4 className="font-medium capitalize">{accion.accion.replace('_', ' ')}</h4>
                                        </div>
                                        <Badge variant="outline">
                                            {accion.total} veces
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                            
                            <div>
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie
                                            data={accionesFrecuentes.slice(0, 6)}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ accion, percent }) => `${accion}: ${(percent * 100).toFixed(1)}%`}
                                            outerRadius={80}
                                            fill="#8884d8"
                                            dataKey="total"
                                        >
                                            {accionesFrecuentes.slice(0, 6).map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
