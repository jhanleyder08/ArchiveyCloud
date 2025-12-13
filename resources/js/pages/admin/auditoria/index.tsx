import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Search, Filter, Eye, AlertTriangle, Shield, Clock, User, Globe, Monitor, RefreshCw, BarChart3, TrendingUp, FileText, Briefcase } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Badge } from '../../../components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, LineChart, Line } from 'recharts';

interface PistaAuditoria {
    id: number;
    fecha_hora: string;
    usuario_id: number;
    accion: string;
    modelo: string;
    descripcion: string;
    resultado: string; // exitoso, fallido, bloqueado
    modulo: string;
    ip_address: string;
    pais: string;
    navegador: string;
    dispositivo: string;
    sistema_operativo: string;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Estadisticas {
    total_eventos: number;
    eventos_criticos: number;
    eventos_alto_riesgo: number;
    usuarios_unicos: number;
    ips_unicas: number;
    acciones_mas_frecuentes: Array<{accion: string, total: number}>;
    actividad_por_dia: Array<{fecha: string, total: number}>;
    distribucion_resultados: Array<{resultado: string, total: number}>;
}

interface Props {
    eventos: {
        data: PistaAuditoria[];
        links: any[];
        current_page: number;
        from: number;
        to: number;
        total: number;
        last_page: number;
        per_page: number;
    };
    estadisticas: Estadisticas;
    usuarios: Usuario[];
    acciones: string[];
    filtros: {
        fecha_inicio?: string;
        fecha_fin?: string;
        usuario_id?: string;
        accion?: string;
        resultado?: string;
        modulo?: string;
        ip_address?: string;
        buscar?: string;
    };
    resultados?: string[];
    modulos?: string[];
}

const COLORS = {
    crítico: '#dc2626',
    alto: '#ea580c',
    medio: '#d97706',
    bajo: '#16a34a'
};

const RISK_COLORS = ['#dc2626', '#ea580c', '#d97706', '#16a34a'];

export default function AuditoriaIndex({ eventos, estadisticas, usuarios, acciones, filtros, resultados, modulos }: Props) {
    const [autoRefresh, setAutoRefresh] = useState(false);
    const [isRefreshing, setIsRefreshing] = useState(false);

    const getBadgeVariant = (resultado: string) => {
        switch (resultado) {
            case 'bloqueado':
                return 'destructive';
            case 'fallido':
                return 'destructive';
            case 'exitoso':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getRiskIcon = (resultado: string) => {
        switch (resultado) {
            case 'bloqueado':
                return <AlertTriangle className="h-4 w-4 text-red-500" />;
            case 'fallido':
                return <AlertTriangle className="h-4 w-4 text-orange-500" />;
            case 'exitoso':
                return <Shield className="h-4 w-4 text-green-500" />;
            default:
                return <Shield className="h-4 w-4 text-gray-500" />;
        }
    };

    const getCategoryIcon = (categoria: string) => {
        const iconMap: Record<string, any> = {
            'autenticacion': User,
            'gestion_usuarios': User,
            'gestion_documentos': FileText,
            'gestion_expedientes': Briefcase,
            'seguridad': Shield,
            'sistema': Monitor,
            'general': Globe
        };
        const IconComponent = iconMap[categoria] || Globe;
        return <IconComponent className="h-4 w-4" />;
    };

    const handleFiltrar = (campo: string, valor: string) => {
        router.get(route('admin.auditoria.index'), {
            ...filtros,
            [campo]: valor === 'all' ? '' : valor,
            page: 1
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const handleBuscar = (buscar: string) => {
        router.get(route('admin.auditoria.index'), {
            ...filtros,
            buscar,
            page: 1
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const refreshData = () => {
        setIsRefreshing(true);
        router.reload({ 
            only: ['eventos', 'estadisticas'],
            onFinish: () => setIsRefreshing(false)
        });
    };

    useEffect(() => {
        let interval: NodeJS.Timeout;
        if (autoRefresh) {
            interval = setInterval(refreshData, 30000); // Refresh cada 30 segundos
        }
        return () => {
            if (interval) clearInterval(interval);
        };
    }, [autoRefresh]);

    // Preparar datos para gráficos
    const riskDistributionData = estadisticas.distribucion_resultados?.map(item => ({
        name: item.resultado,
        value: item.total,
        color: item.resultado === 'fallido' ? '#dc2626' : item.resultado === 'bloqueado' ? '#ea580c' : '#16a34a'
    })) || [];

    const activityData = estadisticas.actividad_por_dia?.slice(-7).map(item => ({
        fecha: new Date(item.fecha).toLocaleDateString('es-ES', { month: 'short', day: 'numeric' }),
        eventos: item.total
    })) || [];

    const topActionsData = estadisticas.acciones_mas_frecuentes?.map(item => ({
        accion: item.accion.replace('_', ' '),
        total: item.total
    })) || [];

    return (
        <AppLayout>
            <Head title="Auditoría y Trazabilidad Avanzada" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Auditoría y Trazabilidad Avanzada</h1>
                        <p className="text-muted-foreground">
                            Monitoreo y análisis de actividad del sistema en tiempo real
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        <div className="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="autoRefresh"
                                checked={autoRefresh}
                                onChange={(e) => setAutoRefresh(e.target.checked)}
                                className="w-4 h-4"
                            />
                            <label htmlFor="autoRefresh" className="text-sm">Auto-refresh</label>
                        </div>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={refreshData} 
                            type="button"
                            disabled={isRefreshing}
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${isRefreshing ? 'animate-spin' : ''}`} />
                            {isRefreshing ? 'Actualizando...' : 'Actualizar'}
                        </Button>
                        <Button variant="outline" onClick={() => router.visit(route('admin.auditoria.analytics'))} type="button">
                            <BarChart3 className="h-4 w-4 mr-2" />
                            Analytics
                        </Button>
                        <Button onClick={() => router.visit(route('admin.auditoria.patrones'))} type="button">
                            <TrendingUp className="h-4 w-4 mr-2" />
                            Patrones
                        </Button>
                    </div>
                </div>

                {/* Estadísticas Principales */}
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Search className="h-8 w-8 text-blue-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Eventos</p>
                                    <p className="text-2xl font-bold">{estadisticas.total_eventos.toLocaleString()}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <AlertTriangle className="h-8 w-8 text-red-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Eventos Críticos</p>
                                    <p className="text-2xl font-bold">{estadisticas.eventos_criticos}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Shield className="h-8 w-8 text-orange-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Alto Riesgo</p>
                                    <p className="text-2xl font-bold">{estadisticas.eventos_alto_riesgo}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <User className="h-8 w-8 text-green-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Usuarios Únicos</p>
                                    <p className="text-2xl font-bold">{estadisticas.usuarios_unicos}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center">
                                <Globe className="h-8 w-8 text-purple-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">IPs Únicas</p>
                                    <p className="text-2xl font-bold">{estadisticas.ips_unicas}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="eventos" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="eventos">Eventos</TabsTrigger>
                        <TabsTrigger value="estadisticas">Estadísticas</TabsTrigger>
                        <TabsTrigger value="tendencias">Tendencias</TabsTrigger>
                    </TabsList>

                    <TabsContent value="eventos" className="space-y-4">
                        {/* Filtros */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Filter className="h-5 w-5 mr-2" />
                                    Filtros de Auditoría
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Búsqueda</label>
                                        <div className="relative">
                                            <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                            <Input
                                                placeholder="Buscar eventos..."
                                                defaultValue={filtros.buscar}
                                                className="pl-9"
                                                onChange={(e) => {
                                                    clearTimeout((window as any).searchTimeout);
                                                    (window as any).searchTimeout = setTimeout(() => {
                                                        handleBuscar(e.target.value);
                                                    }, 500);
                                                }}
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Usuario</label>
                                        <Select value={filtros.usuario_id || 'all'} onValueChange={(value) => handleFiltrar('usuario_id', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todos los usuarios" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todos los usuarios</SelectItem>
                                                {usuarios.map((usuario) => (
                                                    <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                        {usuario.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Acción</label>
                                        <Select value={filtros.accion || 'all'} onValueChange={(value) => handleFiltrar('accion', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todas las acciones" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todas las acciones</SelectItem>
                                                {acciones.map((accion) => (
                                                    <SelectItem key={accion} value={accion}>
                                                        {accion.replace('_', ' ')}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Resultado</label>
                                        <Select value={filtros.resultado || 'all'} onValueChange={(value) => handleFiltrar('resultado', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todos los resultados" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todos los resultados</SelectItem>
                                                <SelectItem value="exitoso">Exitoso</SelectItem>
                                                <SelectItem value="fallido">Fallido</SelectItem>
                                                <SelectItem value="bloqueado">Bloqueado</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Fecha Inicio</label>
                                        <Input
                                            type="date"
                                            value={filtros.fecha_inicio || ''}
                                            onChange={(e) => handleFiltrar('fecha_inicio', e.target.value)}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Fecha Fin</label>
                                        <Input
                                            type="date"
                                            value={filtros.fecha_fin || ''}
                                            onChange={(e) => handleFiltrar('fecha_fin', e.target.value)}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Lista de Eventos */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4">
                                <div>
                                    <CardTitle>Eventos de Auditoría ({eventos.total || 0})</CardTitle>
                                    <CardDescription>
                                        Mostrando {eventos.from || 0} a {eventos.to || 0} de {eventos.total || 0} eventos
                                    </CardDescription>
                                </div>
                                {/* Paginación Superior */}
                                {(eventos.last_page || 0) > 1 && (
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => eventos.current_page > 1 && router.get(route('admin.auditoria.index'), { ...filtros, page: eventos.current_page - 1 })}
                                            disabled={eventos.current_page <= 1}
                                        >
                                            ← Anterior
                                        </Button>
                                        <span className="text-sm text-muted-foreground px-2">
                                            Página {eventos.current_page} de {eventos.last_page}
                                        </span>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => eventos.current_page < eventos.last_page && router.get(route('admin.auditoria.index'), { ...filtros, page: eventos.current_page + 1 })}
                                            disabled={eventos.current_page >= eventos.last_page}
                                        >
                                            Siguiente →
                                        </Button>
                                    </div>
                                )}
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Riesgo</TableHead>
                                                <TableHead>Fecha/Hora</TableHead>
                                                <TableHead>Usuario</TableHead>
                                                <TableHead>Acción</TableHead>
                                                <TableHead>Descripción</TableHead>
                                                <TableHead>IP / Ubicación</TableHead>
                                                <TableHead>Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {eventos.data.map((evento) => (
                                                <TableRow key={evento.id}>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            {getRiskIcon(evento.resultado)}
                                                            <Badge variant={getBadgeVariant(evento.resultado)}>
                                                                {evento.resultado}
                                                            </Badge>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            <div>{new Date(evento.fecha_hora).toLocaleDateString()}</div>
                                                            <div className="text-gray-500">
                                                                {new Date(evento.fecha_hora).toLocaleTimeString()}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">{evento.usuario?.name || 'Sistema'}</div>
                                                            <div className="text-sm text-gray-500">{evento.usuario?.email}</div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            {getCategoryIcon(evento.modulo || 'general')}
                                                            <span className="font-medium">{evento.accion.replace('_', ' ')}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="max-w-xs truncate" title={evento.descripcion}>
                                                            {evento.descripcion}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            <div className="font-mono">{evento.ip_address}</div>
                                                            <div className="text-gray-500">
                                                                {evento.pais || 'N/A'}
                                                            </div>
                                                            <div className="text-xs text-gray-400">
                                                                {evento.dispositivo || 'N/A'} • {evento.navegador || 'N/A'}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Button 
                                                            variant="outline" 
                                                            size="sm"
                                                            onClick={() => router.visit(route('admin.auditoria.show', evento.id))}
                                                            type="button"
                                                        >
                                                            <Eye className="h-4 w-4 mr-1" />
                                                            Ver
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>

                                {eventos.data.length === 0 && (
                                    <div className="text-center py-8">
                                        <Search className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">No hay eventos</h3>
                                        <p className="text-gray-600">
                                            {Object.keys(filtros).length > 0 
                                                ? 'No se encontraron eventos con los filtros aplicados'
                                                : 'No hay eventos de auditoría registrados'
                                            }
                                        </p>
                                    </div>
                                )}

                                {/* Paginación Inferior Simplificada */}
                                {(eventos.last_page || 0) > 1 && (
                                    <div className="flex items-center justify-center gap-2 mt-4 pt-4 border-t">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => eventos.current_page > 1 && router.get(route('admin.auditoria.index'), { ...filtros, page: eventos.current_page - 1 })}
                                            disabled={eventos.current_page <= 1}
                                        >
                                            ← Anterior
                                        </Button>
                                        <div className="flex items-center gap-1">
                                            {eventos.current_page > 2 && (
                                                <>
                                                    <Button variant="outline" size="sm" onClick={() => router.get(route('admin.auditoria.index'), { ...filtros, page: 1 })}>1</Button>
                                                    {eventos.current_page > 3 && <span className="px-1 text-muted-foreground">...</span>}
                                                </>
                                            )}
                                            {eventos.current_page > 1 && (
                                                <Button variant="outline" size="sm" onClick={() => router.get(route('admin.auditoria.index'), { ...filtros, page: eventos.current_page - 1 })}>
                                                    {eventos.current_page - 1}
                                                </Button>
                                            )}
                                            <Button variant="default" size="sm">{eventos.current_page}</Button>
                                            {eventos.current_page < eventos.last_page && (
                                                <Button variant="outline" size="sm" onClick={() => router.get(route('admin.auditoria.index'), { ...filtros, page: eventos.current_page + 1 })}>
                                                    {eventos.current_page + 1}
                                                </Button>
                                            )}
                                            {eventos.current_page < eventos.last_page - 1 && (
                                                <>
                                                    {eventos.current_page < eventos.last_page - 2 && <span className="px-1 text-muted-foreground">...</span>}
                                                    <Button variant="outline" size="sm" onClick={() => router.get(route('admin.auditoria.index'), { ...filtros, page: eventos.last_page })}>
                                                        {eventos.last_page}
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => eventos.current_page < eventos.last_page && router.get(route('admin.auditoria.index'), { ...filtros, page: eventos.current_page + 1 })}
                                            disabled={eventos.current_page >= eventos.last_page}
                                        >
                                            Siguiente →
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="estadisticas" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Distribución de Riesgos */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Distribución por Nivel de Riesgo</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <PieChart>
                                            <Pie
                                                data={riskDistributionData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={(props: any) => `${props.name} ${(props.percent * 100).toFixed(0)}%`}
                                                outerRadius={80}
                                                fill="#8884d8"
                                                dataKey="value"
                                            >
                                                {riskDistributionData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            {/* Acciones Más Frecuentes */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Acciones Más Frecuentes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={topActionsData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="accion" />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar dataKey="total" fill="#3b82f6" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="tendencias" className="space-y-4">
                        {/* Actividad por Día */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Actividad por Día (Últimos 7 días)</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={activityData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="fecha" />
                                        <YAxis />
                                        <Tooltip />
                                        <Line type="monotone" dataKey="eventos" stroke="#8884d8" strokeWidth={2} />
                                    </LineChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
