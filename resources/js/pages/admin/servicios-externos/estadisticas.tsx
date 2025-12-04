import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    ArrowLeft, 
    Mail, 
    MessageSquare, 
    BarChart3, 
    TrendingUp,
    Users,
    Calendar,
    RefreshCw,
    Download,
    Activity
} from 'lucide-react';
import {
    AreaChart,
    Area,
    BarChart,
    Bar,
    PieChart,
    Pie,
    Cell,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Legend
} from 'recharts';

interface EstadisticasEmail {
    emails_hoy: number;
    emails_semana: number;
    usuarios_activos_email: number;
}

interface EstadisticasSms {
    sms_hoy: number;
    sms_semana: number;
    usuarios_con_telefono: number;
}

interface TimelineData {
    fecha: string;
    emails: number;
    sms: number;
    notificaciones: number;
}

interface TipoData {
    tipo: string;
    total: number;
}

interface DatosGraficos {
    timeline: TimelineData[];
    por_tipo: TipoData[];
}

interface Props {
    email: EstadisticasEmail;
    sms: EstadisticasSms;
    graficos: DatosGraficos;
}

const COLORS = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4'];

export default function ServiciosExternosEstadisticas({ email, sms, graficos }: Props) {
    const [refreshing, setRefreshing] = useState(false);

    const handleRefresh = () => {
        setRefreshing(true);
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    };

    const handleExport = () => {
        // En una implementación real, esto generaría un CSV o PDF
        console.log('Exportando estadísticas...');
    };

    // Datos para el gráfico de comparación
    const comparisionData = [
        { servicio: 'Email', hoy: email.emails_hoy, semana: email.emails_semana },
        { servicio: 'SMS', hoy: sms.sms_hoy, semana: sms.sms_semana }
    ];

    // Datos para el gráfico de usuarios
    const usuariosData = [
        { name: 'Con Email', value: email.usuarios_activos_email, color: '#3b82f6' },
        { name: 'Con Teléfono', value: sms.usuarios_con_telefono, color: '#10b981' }
    ];

    return (
        <AppLayout>
            <Head title="Estadísticas - Servicios Externos - ArchiveyCloud" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href="/admin/servicios-externos">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Estadísticas de Servicios Externos</h1>
                            <p className="text-gray-600 mt-1">
                                Análisis detallado del rendimiento de email y SMS
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-2">
                        <Button
                            onClick={handleRefresh}
                            disabled={refreshing}
                            variant="outline"
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${refreshing ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                        <Button onClick={handleExport} variant="outline">
                            <Download className="h-4 w-4 mr-2" />
                            Exportar
                        </Button>
                    </div>
                </div>

                {/* Métricas principales */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <Mail className="h-5 w-5 text-blue-600" />
                                <div>
                                    <p className="text-2xl font-bold text-blue-600">{email.emails_hoy}</p>
                                    <p className="text-sm text-gray-600">Emails Hoy</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <MessageSquare className="h-5 w-5 text-green-600" />
                                <div>
                                    <p className="text-2xl font-bold text-green-600">{sms.sms_hoy}</p>
                                    <p className="text-sm text-gray-600">SMS Hoy</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <Users className="h-5 w-5 text-purple-600" />
                                <div>
                                    <p className="text-2xl font-bold text-purple-600">{email.usuarios_activos_email}</p>
                                    <p className="text-sm text-gray-600">Usuarios Email</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center space-x-2">
                                <Calendar className="h-5 w-5 text-orange-600" />
                                <div>
                                    <p className="text-2xl font-bold text-orange-600">
                                        {email.emails_semana + sms.sms_semana}
                                    </p>
                                    <p className="text-sm text-gray-600">Total Semana</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="timeline" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="timeline">Tendencias</TabsTrigger>
                        <TabsTrigger value="comparacion">Comparación</TabsTrigger>
                        <TabsTrigger value="usuarios">Usuarios</TabsTrigger>
                        <TabsTrigger value="tipos">Por Tipo</TabsTrigger>
                    </TabsList>

                    {/* Timeline de actividad */}
                    <TabsContent value="timeline" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <TrendingUp className="h-5 w-5 text-blue-600" />
                                    <span>Tendencias de Envíos (Últimos 7 días)</span>
                                </CardTitle>
                                <CardDescription>
                                    Actividad diaria de emails, SMS y notificaciones automáticas
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="h-80">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <AreaChart data={graficos.timeline}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="fecha" />
                                            <YAxis />
                                            <Tooltip />
                                            <Legend />
                                            <Area 
                                                type="monotone" 
                                                dataKey="emails" 
                                                stackId="1"
                                                stroke="#3b82f6" 
                                                fill="#3b82f6" 
                                                fillOpacity={0.6}
                                                name="Emails"
                                            />
                                            <Area 
                                                type="monotone" 
                                                dataKey="sms" 
                                                stackId="1"
                                                stroke="#10b981" 
                                                fill="#10b981" 
                                                fillOpacity={0.6}
                                                name="SMS"
                                            />
                                            <Area 
                                                type="monotone" 
                                                dataKey="notificaciones" 
                                                stackId="1"
                                                stroke="#f59e0b" 
                                                fill="#f59e0b" 
                                                fillOpacity={0.6}
                                                name="Notificaciones"
                                            />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Comparación Email vs SMS */}
                    <TabsContent value="comparacion" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <BarChart3 className="h-5 w-5 text-purple-600" />
                                    <span>Comparación Email vs SMS</span>
                                </CardTitle>
                                <CardDescription>
                                    Volumen de envíos por servicio
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="h-80">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={comparisionData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="servicio" />
                                            <YAxis />
                                            <Tooltip />
                                            <Legend />
                                            <Bar dataKey="hoy" fill="#3b82f6" name="Hoy" />
                                            <Bar dataKey="semana" fill="#10b981" name="Esta Semana" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                                <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="p-4 bg-blue-50 rounded-lg">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-2">
                                                <Mail className="h-5 w-5 text-blue-600" />
                                                <span className="font-medium">Email</span>
                                            </div>
                                            <Badge variant="outline">Principal</Badge>
                                        </div>
                                        <div className="mt-2 text-sm text-gray-600">
                                            <p>Hoy: {email.emails_hoy} | Semana: {email.emails_semana}</p>
                                            <p>Usuarios activos: {email.usuarios_activos_email}</p>
                                        </div>
                                    </div>
                                    <div className="p-4 bg-green-50 rounded-lg">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-2">
                                                <MessageSquare className="h-5 w-5 text-green-600" />
                                                <span className="font-medium">SMS</span>
                                            </div>
                                            <Badge variant="outline">Crítico</Badge>
                                        </div>
                                        <div className="mt-2 text-sm text-gray-600">
                                            <p>Hoy: {sms.sms_hoy} | Semana: {sms.sms_semana}</p>
                                            <p>Usuarios con teléfono: {sms.usuarios_con_telefono}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Distribución de usuarios */}
                    <TabsContent value="usuarios" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Users className="h-5 w-5 text-orange-600" />
                                    <span>Distribución de Usuarios</span>
                                </CardTitle>
                                <CardDescription>
                                    Usuarios configurados para recibir notificaciones
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div className="h-64">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <PieChart>
                                                <Pie
                                                    data={usuariosData}
                                                    cx="50%"
                                                    cy="50%"
                                                    labelLine={false}
                                                    label={({ name, value }) => `${name}: ${value}`}
                                                    outerRadius={80}
                                                    fill="#8884d8"
                                                    dataKey="value"
                                                >
                                                    {usuariosData.map((entry, index) => (
                                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                                    ))}
                                                </Pie>
                                                <Tooltip />
                                            </PieChart>
                                        </ResponsiveContainer>
                                    </div>
                                    <div className="space-y-4">
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                                <div className="flex items-center space-x-2">
                                                    <Mail className="h-4 w-4 text-blue-600" />
                                                    <span className="font-medium">Usuarios con Email</span>
                                                </div>
                                                <Badge variant="default" className="bg-blue-100 text-blue-800">
                                                    {email.usuarios_activos_email}
                                                </Badge>
                                            </div>
                                            <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                                <div className="flex items-center space-x-2">
                                                    <MessageSquare className="h-4 w-4 text-green-600" />
                                                    <span className="font-medium">Usuarios con Teléfono</span>
                                                </div>
                                                <Badge variant="default" className="bg-green-100 text-green-800">
                                                    {sms.usuarios_con_telefono}
                                                </Badge>
                                            </div>
                                        </div>
                                        <div className="text-sm text-gray-600 space-y-1">
                                            <p><strong>Cobertura Email:</strong> Disponible para todos los usuarios</p>
                                            <p><strong>Cobertura SMS:</strong> Solo usuarios con teléfono configurado</p>
                                            <p><strong>Recomendación:</strong> Completar datos de teléfono para mejor cobertura</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Distribución por tipo */}
                    <TabsContent value="tipos" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Activity className="h-5 w-5 text-red-600" />
                                    <span>Notificaciones por Tipo (Últimos 7 días)</span>
                                </CardTitle>
                                <CardDescription>
                                    Distribución de notificaciones automáticas por categoría
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {graficos.por_tipo.length > 0 ? (
                                    <>
                                        <div className="h-80">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <BarChart data={graficos.por_tipo} layout="horizontal">
                                                    <CartesianGrid strokeDasharray="3 3" />
                                                    <XAxis type="number" />
                                                    <YAxis dataKey="tipo" type="category" width={120} />
                                                    <Tooltip />
                                                    <Bar dataKey="total" fill="#8b5cf6" />
                                                </BarChart>
                                            </ResponsiveContainer>
                                        </div>
                                        <div className="mt-4 space-y-2">
                                            {graficos.por_tipo.map((tipo, index) => (
                                                <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                                                    <span className="text-sm">{tipo.tipo}</span>
                                                    <Badge variant="outline">{tipo.total}</Badge>
                                                </div>
                                            ))}
                                        </div>
                                    </>
                                ) : (
                                    <div className="text-center py-12 text-gray-500">
                                        <Activity className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                        <p>No hay datos de notificaciones por tipo en los últimos 7 días</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
