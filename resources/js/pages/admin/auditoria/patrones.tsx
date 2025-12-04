import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, AlertTriangle, Shield, Eye, Clock, User, Globe, TrendingUp, Activity, Search } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Badge } from '../../../components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

interface PatronSospechoso {
    id: number;
    fecha_hora: string;
    usuario_id: number;
    nivel_riesgo: string;
    detalles: {
        tipo: string;
        cantidad?: number;
        ventana_tiempo?: string;
        riesgo: string;
        [key: string]: any;
    };
    usuario: {
        id: number;
        name: string;
        email: string;
    };
}

interface EstadisticasPatrones {
    total_patrones: number;
    patrones_ultima_semana: number;
    tipos_patrones: Array<{tipo: string, total: number}>;
    distribucion_riesgos: Array<{nivel_riesgo: string, total: number}>;
}

interface AlertasActivas {
    ips_bloqueadas: string[];
    usuarios_suspendidos: string[];
    patrones_criticos: number;
    investigaciones_pendientes: number;
}

interface Props {
    patrones_sospechosos: PatronSospechoso[];
    estadisticas_patrones: EstadisticasPatrones;
    alertas_activas: AlertasActivas;
}

const COLORS = ['#dc2626', '#ea580c', '#d97706', '#16a34a', '#3b82f6'];

export default function AuditoriaPatrones({ patrones_sospechosos, estadisticas_patrones, alertas_activas }: Props) {
    const [filtroRiesgo, setFiltroRiesgo] = useState<string>('all');
    const [filtroTipo, setFiltroTipo] = useState<string>('all');

    const getBadgeVariant = (nivelRiesgo: string) => {
        switch (nivelRiesgo) {
            case 'crítico':
                return 'destructive';
            case 'alto':
                return 'destructive';
            case 'medio':
                return 'default';
            case 'bajo':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getRiskIcon = (nivelRiesgo: string) => {
        switch (nivelRiesgo) {
            case 'crítico':
                return <AlertTriangle className="h-4 w-4 text-red-500" />;
            case 'alto':
                return <AlertTriangle className="h-4 w-4 text-orange-500" />;
            case 'medio':
                return <Clock className="h-4 w-4 text-yellow-500" />;
            case 'bajo':
                return <Shield className="h-4 w-4 text-green-500" />;
            default:
                return <Shield className="h-4 w-4 text-gray-500" />;
        }
    };

    const getTipoIcon = (tipo: string) => {
        const iconMap: Record<string, any> = {
            'accesos_multiples_rapidos': Activity,
            'cambios_masivos': TrendingUp,
            'horarios_inusuales': Clock,
            'ips_sospechosas': Globe,
            'acciones_privilegiadas': Shield,
            'patron_escalada_privilegios': AlertTriangle
        };
        const IconComponent = iconMap[tipo] || Search;
        return <IconComponent className="h-4 w-4" />;
    };

    const getDescripcionTipo = (tipo: string): string => {
        const descripciones: Record<string, string> = {
            'accesos_multiples_rapidos': 'Accesos múltiples rápidos',
            'cambios_masivos': 'Cambios masivos en el sistema',
            'horarios_inusuales': 'Actividad en horarios inusuales',
            'ips_sospechosas': 'IPs sospechosas o prohibidas',
            'acciones_privilegiadas': 'Acciones que requieren privilegios',
            'patron_escalada_privilegios': 'Escalada de privilegios detectada'
        };
        return descripciones[tipo] || tipo.replace('_', ' ');
    };

    const filtrarPatrones = (patrones: PatronSospechoso[]) => {
        return patrones.filter(patron => {
            if (filtroRiesgo !== 'all' && patron.nivel_riesgo !== filtroRiesgo) {
                return false;
            }
            if (filtroTipo !== 'all' && patron.detalles.tipo !== filtroTipo) {
                return false;
            }
            return true;
        });
    };

    const patronesFiltrados = filtrarPatrones(patrones_sospechosos);

    // Preparar datos para gráficos
    const tiposPatronesData = estadisticas_patrones.tipos_patrones.map((item, index) => ({
        name: getDescripcionTipo(item.tipo),
        value: item.total,
        color: COLORS[index % COLORS.length]
    }));

    const distribucionRiesgosData = estadisticas_patrones.distribucion_riesgos.map(item => ({
        name: item.nivel_riesgo,
        value: item.total
    }));

    const tiposUnicos = [...new Set(patrones_sospechosos.map(p => p.detalles.tipo))];

    return (
        <AppLayout>
            <Head title="Patrones Sospechosos - Auditoría Avanzada" />
            
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
                            <h1 className="text-3xl font-bold tracking-tight">Patrones Sospechosos</h1>
                            <p className="text-muted-foreground">
                                Detección automática de comportamientos anómalos y riesgosos
                            </p>
                        </div>
                    </div>
                </div>

                {/* Alertas Críticas */}
                {alertas_activas.patrones_criticos > 0 && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            <strong>¡Atención!</strong> Se han detectado {alertas_activas.patrones_criticos} patrones críticos 
                            en las últimas 24 horas que requieren investigación inmediata.
                        </AlertDescription>
                    </Alert>
                )}

                {/* Estadísticas Principales */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <TrendingUp className="h-8 w-8 text-red-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Patrones</p>
                                    <p className="text-2xl font-bold">{estadisticas_patrones.total_patrones}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Clock className="h-8 w-8 text-orange-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Última Semana</p>
                                    <p className="text-2xl font-bold">{estadisticas_patrones.patrones_ultima_semana}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <AlertTriangle className="h-8 w-8 text-red-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Críticos Activos</p>
                                    <p className="text-2xl font-bold">{alertas_activas.patrones_criticos}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Search className="h-8 w-8 text-blue-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Investigaciones</p>
                                    <p className="text-2xl font-bold">{alertas_activas.investigaciones_pendientes}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="patrones" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="patrones">Patrones Detectados</TabsTrigger>
                        <TabsTrigger value="estadisticas">Estadísticas</TabsTrigger>
                        <TabsTrigger value="alertas">Alertas Activas</TabsTrigger>
                    </TabsList>

                    <TabsContent value="patrones" className="space-y-4">
                        {/* Filtros */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Filtros</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Nivel de Riesgo</label>
                                        <select
                                            value={filtroRiesgo}
                                            onChange={(e) => setFiltroRiesgo(e.target.value)}
                                            className="w-full p-2 border rounded-md"
                                        >
                                            <option value="all">Todos los niveles</option>
                                            <option value="crítico">Crítico</option>
                                            <option value="alto">Alto</option>
                                            <option value="medio">Medio</option>
                                            <option value="bajo">Bajo</option>
                                        </select>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Tipo de Patrón</label>
                                        <select
                                            value={filtroTipo}
                                            onChange={(e) => setFiltroTipo(e.target.value)}
                                            className="w-full p-2 border rounded-md"
                                        >
                                            <option value="all">Todos los tipos</option>
                                            {tiposUnicos.map((tipo) => (
                                                <option key={tipo} value={tipo}>
                                                    {getDescripcionTipo(tipo)}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Lista de Patrones */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Patrones Sospechosos Detectados ({patronesFiltrados.length})</CardTitle>
                                <CardDescription>
                                    Comportamientos anómalos identificados automáticamente
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Riesgo</TableHead>
                                                <TableHead>Tipo</TableHead>
                                                <TableHead>Fecha/Hora</TableHead>
                                                <TableHead>Usuario</TableHead>
                                                <TableHead>Detalles</TableHead>
                                                <TableHead>Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {patronesFiltrados.map((patron) => (
                                                <TableRow key={patron.id}>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            {getRiskIcon(patron.nivel_riesgo)}
                                                            <Badge variant={getBadgeVariant(patron.nivel_riesgo)}>
                                                                {patron.nivel_riesgo}
                                                            </Badge>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            {getTipoIcon(patron.detalles.tipo)}
                                                            <span className="font-medium">
                                                                {getDescripcionTipo(patron.detalles.tipo)}
                                                            </span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            <div>{new Date(patron.fecha_hora).toLocaleDateString()}</div>
                                                            <div className="text-gray-500">
                                                                {new Date(patron.fecha_hora).toLocaleTimeString()}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">{patron.usuario?.name || 'Sistema'}</div>
                                                            <div className="text-sm text-gray-500">{patron.usuario?.email}</div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm space-y-1">
                                                            {patron.detalles.cantidad && (
                                                                <div>Cantidad: <span className="font-medium">{patron.detalles.cantidad}</span></div>
                                                            )}
                                                            {patron.detalles.ventana_tiempo && (
                                                                <div>Ventana: <span className="font-medium">{patron.detalles.ventana_tiempo}</span></div>
                                                            )}
                                                            {patron.detalles.hora && (
                                                                <div>Hora: <span className="font-medium">{patron.detalles.hora}</span></div>
                                                            )}
                                                            {patron.detalles.ip && (
                                                                <div>IP: <span className="font-mono text-xs">{patron.detalles.ip}</span></div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Link href={route('admin.auditoria.show', patron.id)}>
                                                            <Button variant="outline" size="sm">
                                                                <Eye className="h-4 w-4 mr-1" />
                                                                Ver
                                                            </Button>
                                                        </Link>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>

                                {patronesFiltrados.length === 0 && (
                                    <div className="text-center py-8">
                                        <Shield className="h-12 w-12 text-green-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">No hay patrones sospechosos</h3>
                                        <p className="text-gray-600">
                                            {patrones_sospechosos.length === 0 
                                                ? 'No se han detectado patrones sospechosos'
                                                : 'No hay patrones que coincidan con los filtros aplicados'
                                            }
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="estadisticas" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Tipos de Patrones */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Distribución por Tipo de Patrón</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <PieChart>
                                            <Pie
                                                data={tiposPatronesData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                                outerRadius={80}
                                                fill="#8884d8"
                                                dataKey="value"
                                            >
                                                {tiposPatronesData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            {/* Distribución por Riesgo */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Distribución por Nivel de Riesgo</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={distribucionRiesgosData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="name" />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar dataKey="value" fill="#3b82f6" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="alertas" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Alertas de Seguridad */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Estado de Seguridad</CardTitle>
                                    <CardDescription>Medidas de seguridad activas</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between p-3 border rounded-lg">
                                        <div className="flex items-center space-x-2">
                                            <Globe className="h-5 w-5 text-red-500" />
                                            <span className="font-medium">IPs Bloqueadas</span>
                                        </div>
                                        <Badge variant="destructive">{alertas_activas.ips_bloqueadas.length}</Badge>
                                    </div>

                                    <div className="flex items-center justify-between p-3 border rounded-lg">
                                        <div className="flex items-center space-x-2">
                                            <User className="h-5 w-5 text-orange-500" />
                                            <span className="font-medium">Usuarios Suspendidos</span>
                                        </div>
                                        <Badge variant="secondary">{alertas_activas.usuarios_suspendidos.length}</Badge>
                                    </div>

                                    <div className="flex items-center justify-between p-3 border rounded-lg">
                                        <div className="flex items-center space-x-2">
                                            <AlertTriangle className="h-5 w-5 text-red-500" />
                                            <span className="font-medium">Patrones Críticos</span>
                                        </div>
                                        <Badge variant="destructive">{alertas_activas.patrones_criticos}</Badge>
                                    </div>

                                    <div className="flex items-center justify-between p-3 border rounded-lg">
                                        <div className="flex items-center space-x-2">
                                            <Search className="h-5 w-5 text-blue-500" />
                                            <span className="font-medium">Investigaciones Pendientes</span>
                                        </div>
                                        <Badge variant="outline">{alertas_activas.investigaciones_pendientes}</Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Recomendaciones */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Recomendaciones</CardTitle>
                                    <CardDescription>Acciones sugeridas basadas en patrones detectados</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {alertas_activas.patrones_criticos > 0 && (
                                            <Alert>
                                                <AlertTriangle className="h-4 w-4" />
                                                <AlertDescription>
                                                    Revisar inmediatamente los {alertas_activas.patrones_criticos} patrones críticos detectados
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        {estadisticas_patrones.patrones_ultima_semana > 10 && (
                                            <Alert>
                                                <Clock className="h-4 w-4" />
                                                <AlertDescription>
                                                    Considerar ajustar los controles de seguridad debido al aumento de patrones detectados
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        <Alert>
                                            <Shield className="h-4 w-4" />
                                            <AlertDescription>
                                                Revisar y actualizar las políticas de seguridad basadas en los patrones más frecuentes
                                            </AlertDescription>
                                        </Alert>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
