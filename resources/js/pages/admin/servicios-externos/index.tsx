import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { 
    Mail, 
    MessageSquare, 
    Settings, 
    TrendingUp, 
    Users, 
    Clock, 
    CheckCircle, 
    AlertTriangle,
    Activity,
    BarChart3,
    TestTube,
    RefreshCw,
    Calendar,
    Zap
} from 'lucide-react';

interface Estadisticas {
    email: {
        enviados_hoy: number;
        enviados_semana: number;
        usuarios_con_email: number;
        ultimo_envio: string;
    };
    sms: {
        enviados_hoy: number;
        enviados_semana: number;
        usuarios_con_telefono: number;
        ultimo_envio: string;
    };
    notificaciones: {
        total_pendientes: number;
        criticas_pendientes: number;
        automaticas_hoy: number;
    };
}

interface Configuracion {
    email_habilitado: boolean;
    sms_habilitado: boolean;
    resumen_diario_hora: string;
    throttling_email: number;
    throttling_sms: number;
    ambiente: string;
    mail_driver: string;
    queue_connection: string;
}

interface LogReciente {
    id: number;
    tipo: string;
    usuario: string;
    prioridad: string;
    created_at: string;
    titulo: string;
}

interface Props {
    estadisticas: Estadisticas;
    configuracion: Configuracion;
    logs_recientes: LogReciente[];
}

export default function ServiciosExternosIndex({ estadisticas, configuracion, logs_recientes }: Props) {
    const [refreshing, setRefreshing] = useState(false);

    const handleRefresh = () => {
        setRefreshing(true);
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    };

    const getPrioridadColor = (prioridad: string) => {
        switch (prioridad) {
            case 'critica': return 'destructive';
            case 'alta': return 'secondary';
            case 'media': return 'outline';
            default: return 'outline';
        }
    };

    const getPrioridadIcon = (prioridad: string) => {
        switch (prioridad) {
            case 'critica': return <AlertTriangle className="h-3 w-3" />;
            case 'alta': return <TrendingUp className="h-3 w-3" />;
            default: return <Activity className="h-3 w-3" />;
        }
    };

    const getEstadoServicio = (habilitado: boolean) => {
        return habilitado ? (
            <Badge variant="default" className="bg-green-100 text-green-800">
                <CheckCircle className="h-3 w-3 mr-1" />
                Activo
            </Badge>
        ) : (
            <Badge variant="secondary" className="bg-red-100 text-red-800">
                <AlertTriangle className="h-3 w-3 mr-1" />
                Inactivo
            </Badge>
        );
    };

    return (
        <AppLayout>
            <Head title="Servicios Externos - ArchiveyCloud" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Servicios Externos</h1>
                        <p className="text-gray-600 mt-1">
                            Gestión y monitoreo de servicios de notificaciones por email y SMS
                        </p>
                    </div>
                    <div className="flex space-x-3">
                        <Button
                            onClick={handleRefresh}
                            disabled={refreshing}
                            variant="outline"
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${refreshing ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                        <Link href="/admin/servicios-externos/testing">
                            <Button>
                                <TestTube className="h-4 w-4 mr-2" />
                                Probar Servicios
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Estado de Servicios */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Mail className="h-5 w-5 text-blue-600" />
                                <span>Servicio de Email</span>
                            </CardTitle>
                            <CardDescription>Notificaciones por correo electrónico</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">Estado:</span>
                                {getEstadoServicio(configuracion.email_habilitado)}
                            </div>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="space-y-1">
                                    <p className="text-gray-600">Enviados hoy</p>
                                    <p className="text-2xl font-bold text-blue-600">{estadisticas.email.enviados_hoy}</p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-gray-600">Esta semana</p>
                                    <p className="text-2xl font-bold text-blue-600">{estadisticas.email.enviados_semana}</p>
                                </div>
                            </div>
                            <Separator />
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Usuarios con email:</span>
                                    <span className="font-medium">{estadisticas.email.usuarios_con_email}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Último envío:</span>
                                    <span className="font-medium">{estadisticas.email.ultimo_envio}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Throttling:</span>
                                    <span className="font-medium">{configuracion.throttling_email}/hora</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <MessageSquare className="h-5 w-5 text-green-600" />
                                <span>Servicio de SMS</span>
                            </CardTitle>
                            <CardDescription>Mensajes de texto para alertas críticas</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">Estado:</span>
                                {getEstadoServicio(configuracion.sms_habilitado)}
                            </div>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="space-y-1">
                                    <p className="text-gray-600">Enviados hoy</p>
                                    <p className="text-2xl font-bold text-green-600">{estadisticas.sms.enviados_hoy}</p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-gray-600">Esta semana</p>
                                    <p className="text-2xl font-bold text-green-600">{estadisticas.sms.enviados_semana}</p>
                                </div>
                            </div>
                            <Separator />
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Usuarios con teléfono:</span>
                                    <span className="font-medium">{estadisticas.sms.usuarios_con_telefono}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Último envío:</span>
                                    <span className="font-medium">{estadisticas.sms.ultimo_envio}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Throttling:</span>
                                    <span className="font-medium">{configuracion.throttling_sms}/día</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Métricas de Notificaciones */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Activity className="h-5 w-5 text-purple-600" />
                            <span>Actividad de Notificaciones</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="text-center space-y-2">
                                <div className="text-3xl font-bold text-purple-600">
                                    {estadisticas.notificaciones.total_pendientes}
                                </div>
                                <div className="text-sm text-gray-600">Pendientes Total</div>
                            </div>
                            <div className="text-center space-y-2">
                                <div className="text-3xl font-bold text-red-600">
                                    {estadisticas.notificaciones.criticas_pendientes}
                                </div>
                                <div className="text-sm text-gray-600">Críticas Pendientes</div>
                            </div>
                            <div className="text-center space-y-2">
                                <div className="text-3xl font-bold text-blue-600">
                                    {estadisticas.notificaciones.automaticas_hoy}
                                </div>
                                <div className="text-sm text-gray-600">Automáticas Hoy</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Configuración Actual */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Settings className="h-5 w-5 text-gray-600" />
                                <span>Configuración Actual</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-gray-600">Ambiente:</p>
                                    <Badge variant="outline">{configuracion.ambiente}</Badge>
                                </div>
                                <div>
                                    <p className="text-gray-600">Mail Driver:</p>
                                    <Badge variant="outline">{configuracion.mail_driver}</Badge>
                                </div>
                                <div>
                                    <p className="text-gray-600">Queue:</p>
                                    <Badge variant="outline">{configuracion.queue_connection}</Badge>
                                </div>
                                <div>
                                    <p className="text-gray-600">Resumen diario:</p>
                                    <Badge variant="outline">
                                        <Clock className="h-3 w-3 mr-1" />
                                        {configuracion.resumen_diario_hora}
                                    </Badge>
                                </div>
                            </div>
                            <Separator />
                            <div className="flex justify-end space-x-2">
                                <Link href="/admin/servicios-externos/configuracion">
                                    <Button variant="outline" size="sm">
                                        <Settings className="h-4 w-4 mr-1" />
                                        Configurar
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Calendar className="h-5 w-5 text-orange-600" />
                                <span>Actividad Reciente</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {logs_recientes.length > 0 ? (
                                    logs_recientes.slice(0, 5).map((log) => (
                                        <div key={log.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center space-x-3">
                                                <Badge variant={getPrioridadColor(log.prioridad)} className="text-xs">
                                                    {getPrioridadIcon(log.prioridad)}
                                                    {log.prioridad}
                                                </Badge>
                                                <div>
                                                    <p className="text-sm font-medium truncate max-w-40">
                                                        {log.titulo}
                                                    </p>
                                                    <p className="text-xs text-gray-500">{log.usuario}</p>
                                                </div>
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {log.created_at}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-4 text-gray-500">
                                        <Activity className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p className="text-sm">No hay actividad reciente</p>
                                    </div>
                                )}
                            </div>
                            {logs_recientes.length > 5 && (
                                <div className="mt-4 text-center">
                                    <Link href="/admin/notificaciones">
                                        <Button variant="ghost" size="sm">
                                            Ver todas las notificaciones
                                        </Button>
                                    </Link>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Acciones Rápidas */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Zap className="h-5 w-5 text-yellow-600" />
                            <span>Acciones Rápidas</span>
                        </CardTitle>
                        <CardDescription>
                            Ejecutar tareas administrativas de servicios externos
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-3">
                            <Link href="/admin/servicios-externos/testing">
                                <Button variant="outline">
                                    <TestTube className="h-4 w-4 mr-2" />
                                    Probar Servicios
                                </Button>
                            </Link>
                            <Link href="/admin/servicios-externos/estadisticas">
                                <Button variant="outline">
                                    <BarChart3 className="h-4 w-4 mr-2" />
                                    Ver Estadísticas
                                </Button>
                            </Link>
                            <Link href="/admin/servicios-externos/configuracion">
                                <Button variant="outline">
                                    <Settings className="h-4 w-4 mr-2" />
                                    Configuración
                                </Button>
                            </Link>
                            <Link href="/admin/notificaciones">
                                <Button variant="outline">
                                    <Activity className="h-4 w-4 mr-2" />
                                    Centro de Notificaciones
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
