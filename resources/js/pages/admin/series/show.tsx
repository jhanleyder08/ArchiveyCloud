import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { 
    FileText, 
    Folder, 
    Clock, 
    Archive,
    Users,
    Edit,
    ArrowLeft,
    Calendar,
    MapPin,
    AlertTriangle,
    CheckCircle,
    BarChart3,
    TrendingUp,
    Activity
} from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

// Interfaces TypeScript
interface TRD {
    id: number;
    codigo: string;
    nombre: string;
}

interface Subserie {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    expedientes_count: number;
    documentos_count: number;
    activa: boolean;
}

interface Expediente {
    id: number;
    numero_expediente: string;
    titulo: string;
    estado_ciclo_vida: string;
    fecha_apertura: string;
    fecha_cierre?: string;
    documentos_count: number;
    tamaño_total: string;
}

interface SerieDetalle {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    trd: TRD;
    tiempo_archivo_gestion: number;
    tiempo_archivo_central: number;
    disposicion_final: string;
    area_responsable: string;
    observaciones?: string;
    activa: boolean;
    created_at: string;
    updated_at: string;
    subseries: Subserie[];
    expedientes_recientes: Expediente[];
    estadisticas: {
        total_subseries: number;
        total_expedientes: number;
        total_documentos: number;
        tamaño_total: string;
        expedientes_por_estado: { estado: string; cantidad: number; color: string }[];
        actividad_mensual: { mes: string; expedientes: number; documentos: number }[];
        distribucion_subseries: { subserie: string; expedientes: number }[];
    };
}

interface Props {
    serie: SerieDetalle;
}

const COLORS = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6'];

export default function SerieShow({ serie }: Props) {
    const getEstadoColor = (estado: string) => {
        switch (estado) {
            case 'tramite': return 'bg-blue-100 text-blue-800';
            case 'gestion': return 'bg-yellow-100 text-yellow-800';
            case 'central': return 'bg-green-100 text-green-800';
            case 'historico': return 'bg-purple-100 text-purple-800';
            case 'eliminado': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getDisposicionColor = (tipo: string) => {
        switch (tipo) {
            case 'conservacion_permanente': return 'text-blue-600';
            case 'eliminacion': return 'text-red-600';
            case 'transferencia': return 'text-green-600';
            default: return 'text-gray-600';
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

    return (
        <AppLayout>
            <Head title={`Serie: ${serie.nombre} - ArchiveyCloud`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href="/admin/series">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{serie.nombre}</h1>
                            <p className="text-gray-600 mt-1">
                                Código: {serie.codigo} • TRD: {serie.trd.nombre}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Badge variant={serie.activa ? 'default' : 'secondary'}>
                            {serie.activa ? (
                                <>
                                    <CheckCircle className="h-3 w-3 mr-1" />
                                    Activa
                                </>
                            ) : (
                                <>
                                    <AlertTriangle className="h-3 w-3 mr-1" />
                                    Inactiva
                                </>
                            )}
                        </Badge>
                        <Link href={`/admin/series/${serie.id}/edit`}>
                            <Button>
                                <Edit className="h-4 w-4 mr-2" />
                                Editar
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Columna Principal */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Información General */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <FileText className="h-5 w-5" />
                                    <span>Información General</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <h4 className="font-semibold mb-2">Descripción</h4>
                                    <p className="text-gray-700">{serie.descripcion}</p>
                                </div>
                                
                                <Separator />

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 className="font-semibold mb-2 flex items-center">
                                            <Clock className="h-4 w-4 mr-2" />
                                            Archivo de Gestión
                                        </h4>
                                        <p className="text-2xl font-bold text-blue-600">
                                            {serie.tiempo_archivo_gestion} años
                                        </p>
                                    </div>
                                    <div>
                                        <h4 className="font-semibold mb-2 flex items-center">
                                            <Archive className="h-4 w-4 mr-2" />
                                            Archivo Central
                                        </h4>
                                        <p className="text-2xl font-bold text-green-600">
                                            {serie.tiempo_archivo_central} años
                                        </p>
                                    </div>
                                </div>

                                <Separator />

                                <div>
                                    <h4 className="font-semibold mb-2 flex items-center">
                                        {getDisposicionIcon(serie.disposicion_final)}
                                        <span className="ml-2">Disposición Final</span>
                                    </h4>
                                    <Badge className={getDisposicionColor(serie.disposicion_final)}>
                                        {serie.disposicion_final.replace('_', ' ').toUpperCase()}
                                    </Badge>
                                </div>

                                <Separator />

                                <div>
                                    <h4 className="font-semibold mb-2 flex items-center">
                                        <Users className="h-4 w-4 mr-2" />
                                        Área Responsable
                                    </h4>
                                    <p className="text-gray-700">{serie.area_responsable}</p>
                                </div>

                                {serie.observaciones && (
                                    <>
                                        <Separator />
                                        <div>
                                            <h4 className="font-semibold mb-2">Observaciones</h4>
                                            <p className="text-gray-600 text-sm">{serie.observaciones}</p>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Tabs con Análisis */}
                        <Tabs defaultValue="expedientes" className="space-y-4">
                            <TabsList className="grid w-full grid-cols-3">
                                <TabsTrigger value="expedientes">Expedientes</TabsTrigger>
                                <TabsTrigger value="subseries">Subseries</TabsTrigger>
                                <TabsTrigger value="actividad">Actividad</TabsTrigger>
                            </TabsList>

                            {/* Tab: Expedientes */}
                            <TabsContent value="expedientes" className="space-y-4">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Expedientes Recientes</CardTitle>
                                        <CardDescription>Últimos expedientes vinculados a esta serie</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            {serie.expedientes_recientes.map((expediente) => (
                                                <div key={expediente.id} className="flex items-center justify-between p-4 border rounded-lg">
                                                    <div className="flex-1">
                                                        <div className="flex items-center space-x-2">
                                                            <h4 className="font-semibold">{expediente.numero_expediente}</h4>
                                                            <Badge className={getEstadoColor(expediente.estado_ciclo_vida)}>
                                                                {expediente.estado_ciclo_vida}
                                                            </Badge>
                                                        </div>
                                                        <p className="text-sm text-gray-600 mt-1">{expediente.titulo}</p>
                                                        <div className="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                            <span className="flex items-center">
                                                                <Calendar className="h-3 w-3 mr-1" />
                                                                {new Date(expediente.fecha_apertura).toLocaleDateString()}
                                                            </span>
                                                            <span className="flex items-center">
                                                                <FileText className="h-3 w-3 mr-1" />
                                                                {expediente.documentos_count} documentos
                                                            </span>
                                                            <span>{expediente.tamaño_total}</span>
                                                        </div>
                                                    </div>
                                                    <Link href={`/admin/expedientes/${expediente.id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            Ver
                                                        </Button>
                                                    </Link>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            {/* Tab: Subseries */}
                            <TabsContent value="subseries" className="space-y-4">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Subseries Documentales</CardTitle>
                                        <CardDescription>Subseries vinculadas a esta serie</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {serie.subseries.map((subserie) => (
                                                <div key={subserie.id} className="p-4 border rounded-lg">
                                                    <div className="flex items-center justify-between mb-2">
                                                        <h4 className="font-semibold">{subserie.codigo}</h4>
                                                        <Badge variant={subserie.activa ? 'default' : 'secondary'}>
                                                            {subserie.activa ? 'Activa' : 'Inactiva'}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-sm font-medium mb-1">{subserie.nombre}</p>
                                                    <p className="text-xs text-gray-600 mb-3">{subserie.descripcion}</p>
                                                    <div className="flex justify-between text-xs text-gray-500">
                                                        <span>{subserie.expedientes_count} expedientes</span>
                                                        <span>{subserie.documentos_count} documentos</span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            {/* Tab: Actividad */}
                            <TabsContent value="actividad" className="space-y-4">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Actividad Mensual</CardTitle>
                                        <CardDescription>Creación de expedientes y documentos</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <AreaChart data={serie.estadisticas.actividad_mensual}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="mes" />
                                                <YAxis />
                                                <Tooltip />
                                                <Legend />
                                                <Area 
                                                    type="monotone" 
                                                    dataKey="expedientes" 
                                                    stackId="1"
                                                    stroke="#3B82F6" 
                                                    fill="#3B82F6" 
                                                    name="Expedientes"
                                                />
                                                <Area 
                                                    type="monotone" 
                                                    dataKey="documentos" 
                                                    stackId="1"
                                                    stroke="#10B981" 
                                                    fill="#10B981" 
                                                    name="Documentos"
                                                />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        </Tabs>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Estadísticas */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <BarChart3 className="h-5 w-5" />
                                    <span>Estadísticas</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-blue-600">
                                        {serie.estadisticas.total_expedientes}
                                    </div>
                                    <div className="text-sm text-gray-600">Expedientes</div>
                                </div>
                                
                                <Separator />
                                
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-green-600">
                                        {serie.estadisticas.total_documentos}
                                    </div>
                                    <div className="text-sm text-gray-600">Documentos</div>
                                </div>
                                
                                <Separator />
                                
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-purple-600">
                                        {serie.estadisticas.total_subseries}
                                    </div>
                                    <div className="text-sm text-gray-600">Subseries</div>
                                </div>
                                
                                <Separator />
                                
                                <div className="text-center">
                                    <div className="text-xl font-bold text-orange-600">
                                        {serie.estadisticas.tamaño_total}
                                    </div>
                                    <div className="text-sm text-gray-600">Almacenamiento</div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Expedientes por Estado */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Estados Expedientes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={200}>
                                    <PieChart>
                                        <Pie
                                            data={serie.estadisticas.expedientes_por_estado}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={40}
                                            outerRadius={80}
                                            paddingAngle={5}
                                            dataKey="cantidad"
                                        >
                                            {serie.estadisticas.expedientes_por_estado.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color || COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="space-y-2 mt-4">
                                    {serie.estadisticas.expedientes_por_estado.map((estado, index) => (
                                        <div key={index} className="flex items-center justify-between">
                                            <div className="flex items-center space-x-2">
                                                <div 
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: estado.color || COLORS[index % COLORS.length] }}
                                                />
                                                <span className="text-sm">{estado.estado}</span>
                                            </div>
                                            <span className="text-sm font-medium">{estado.cantidad}</span>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Información Técnica */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Información Técnica</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Creada:</span>
                                    <span>{new Date(serie.created_at).toLocaleDateString()}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Actualizada:</span>
                                    <span>{new Date(serie.updated_at).toLocaleDateString()}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">ID:</span>
                                    <span>{serie.id}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">TRD ID:</span>
                                    <span>{serie.trd.id}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
