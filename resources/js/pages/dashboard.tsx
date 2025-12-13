import { useState, useEffect } from 'react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';

import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    FileText,
    Folder,
    Users,
    Database,
    Clock,
    Bell,
    Activity,
    TrendingUp,
    Calendar,
    HardDrive,
    ArrowRight,
    ExternalLink,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

interface DashboardProps {
    metricas: {
        total_documentos: number;
        total_expedientes: number;
        total_usuarios: number;
        total_series: number;
        documentos_hoy: number;
        expedientes_hoy: number;
        documentos_semana: number;
        expedientes_semana: number;
        almacenamiento_mb: number;
        almacenamiento_gb: number;
    };
    actividad_reciente: Array<{
        id: number;
        accion: string;
        descripcion: string;
        fecha: string;
        modulo: string;
    }>;
    notificaciones_pendientes: Array<{
        id: number;
        titulo: string;
        mensaje: string;
        prioridad: string;
        fecha: string;
    }>;
    documentos_recientes: Array<{
        id: number;
        codigo: string;
        titulo: string;
        fecha: string;
        estado: string;
    }>;
    expedientes_recientes: Array<{
        id: number;
        codigo: string;
        titulo: string;
        fecha: string;
        estado: string;
    }>;
    usuario: {
        nombre: string;
        email: string;
        rol: string;
    };
}

export default function Dashboard({
    metricas,
    actividad_reciente,
    notificaciones_pendientes,
    documentos_recientes,
    expedientes_recientes,
    usuario,
}: DashboardProps) {
    const [currentTime, setCurrentTime] = useState(new Date());
    const [currentDate, setCurrentDate] = useState(new Date());

    useEffect(() => {
        const timer = setInterval(() => {
            setCurrentTime(new Date());
            setCurrentDate(new Date());
        }, 1000);

        return () => clearInterval(timer);
    }, []);

    const formatTime = (date: Date) => {
        return date.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    };

    const formatDate = (date: Date) => {
        return date.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const getPrioridadColor = (prioridad: string) => {
        switch (prioridad) {
            case 'critica':
                return 'bg-red-100 text-red-800';
            case 'alta':
                return 'bg-orange-100 text-orange-800';
            case 'media':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-blue-100 text-blue-800';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard - Archivey Cloud" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
                {/* Header con Reloj y Bienvenida */}
                <div className="grid gap-4 md:grid-cols-3">
                    {/* Reloj y Fecha */}
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="h-5 w-5 text-brand-primary" />
                                Hora Actual
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="text-4xl font-bold text-brand-primary">
                                    {formatTime(currentTime)}
                                </div>
                                <div className="text-lg text-muted-foreground capitalize">
                                    {formatDate(currentDate)}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Información del Usuario */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5 text-brand-primary" />
                                Bienvenido
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="font-semibold text-lg">{usuario.nombre}</div>
                                <div className="text-sm text-muted-foreground">{usuario.email}</div>
                                <Badge className="bg-brand-primary text-white">{usuario.rol}</Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Métricas Principales - Clickeables */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Link href={route('admin.documentos.index')} className="group">
                        <Card className="h-full transition-all duration-200 hover:shadow-lg hover:border-brand-primary/50 hover:bg-brand-primary/5 cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Documentos</CardTitle>
                                <div className="flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-brand-primary" />
                                    <ArrowRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{metricas.total_documentos.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground">
                                    {metricas.documentos_hoy} hoy • {metricas.documentos_semana} esta semana
                                </p>
                                <p className="text-xs text-brand-primary mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Click para ver todos →
                                </p>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href={route('admin.expedientes.index')} className="group">
                        <Card className="h-full transition-all duration-200 hover:shadow-lg hover:border-brand-primary/50 hover:bg-brand-primary/5 cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Expedientes</CardTitle>
                                <div className="flex items-center gap-2">
                                    <Folder className="h-4 w-4 text-brand-primary" />
                                    <ArrowRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{metricas.total_expedientes.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground">
                                    {metricas.expedientes_hoy} hoy • {metricas.expedientes_semana} esta semana
                                </p>
                                <p className="text-xs text-brand-primary mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Click para ver todos →
                                </p>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href={route('admin.users.index')} className="group">
                        <Card className="h-full transition-all duration-200 hover:shadow-lg hover:border-brand-primary/50 hover:bg-brand-primary/5 cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Usuarios Activos</CardTitle>
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4 text-brand-primary" />
                                    <ArrowRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{metricas.total_usuarios.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground">
                                    Usuarios en el sistema
                                </p>
                                <p className="text-xs text-brand-primary mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Click para gestionar →
                                </p>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href={route('admin.reportes.almacenamiento')} className="group">
                        <Card className="h-full transition-all duration-200 hover:shadow-lg hover:border-brand-primary/50 hover:bg-brand-primary/5 cursor-pointer">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Almacenamiento</CardTitle>
                                <div className="flex items-center gap-2">
                                    <HardDrive className="h-4 w-4 text-brand-primary" />
                                    <ArrowRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {metricas.almacenamiento_gb > 1 
                                        ? `${metricas.almacenamiento_gb.toFixed(2)} GB`
                                        : `${metricas.almacenamiento_mb.toFixed(2)} MB`}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Espacio utilizado
                                </p>
                                <p className="text-xs text-brand-primary mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Click para ver detalles →
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                {/* Contenido Principal */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {/* Actividad Reciente */}
                    <Card className="lg:col-span-2 group">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <Activity className="h-5 w-5 text-brand-primary" />
                                    Actividad Reciente
                                </CardTitle>
                                <CardDescription>Últimas acciones realizadas</CardDescription>
                            </div>
                            <Link 
                                href={route('admin.auditoria.index')} 
                                className="text-xs text-brand-primary hover:underline flex items-center gap-1 opacity-70 hover:opacity-100 transition-opacity"
                            >
                                Ver todo <ArrowRight className="h-3 w-3" />
                            </Link>
                        </CardHeader>
                        <CardContent>
                            {actividad_reciente.length > 0 ? (
                                <div className="space-y-3">
                                    {actividad_reciente.map((actividad) => (
                                        <div key={actividad.id} className="flex items-start gap-3 border-b pb-3 last:border-0 hover:bg-muted/50 rounded-lg p-2 -mx-2 transition-colors cursor-pointer">
                                            <div className="mt-1 flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary/10">
                                                <Activity className="h-4 w-4 text-brand-primary" />
                                            </div>
                                            <div className="flex-1">
                                                <div className="text-sm font-medium">{actividad.descripcion}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {actividad.modulo} • {actividad.fecha}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-muted-foreground">
                                    No hay actividad reciente
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Notificaciones Pendientes */}
                    <Card className="group">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <Bell className="h-5 w-5 text-brand-primary" />
                                    Notificaciones
                                    {notificaciones_pendientes.length > 0 && (
                                        <Badge variant="destructive" className="ml-2 h-5 w-5 rounded-full p-0 flex items-center justify-center text-xs">
                                            {notificaciones_pendientes.length}
                                        </Badge>
                                    )}
                                </CardTitle>
                                <CardDescription>Pendientes de revisar</CardDescription>
                            </div>
                            <Link 
                                href={route('admin.notificaciones.index')} 
                                className="text-xs text-brand-primary hover:underline flex items-center gap-1 opacity-70 hover:opacity-100 transition-opacity"
                            >
                                Ver todas <ArrowRight className="h-3 w-3" />
                            </Link>
                        </CardHeader>
                        <CardContent>
                            {notificaciones_pendientes.length > 0 ? (
                                <div className="space-y-3">
                                    {notificaciones_pendientes.map((notificacion) => (
                                        <Link 
                                            key={notificacion.id} 
                                            href={route('admin.notificaciones.index')}
                                            className="block space-y-1 border-b pb-3 last:border-0 hover:bg-muted/50 rounded-lg p-2 -mx-2 transition-colors"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex-1">
                                                    <div className="text-sm font-medium">{notificacion.titulo}</div>
                                                    <div className="text-xs text-muted-foreground line-clamp-2">
                                                        {notificacion.mensaje}
                                                    </div>
                                                </div>
                                                <Badge className={getPrioridadColor(notificacion.prioridad)}>
                                                    {notificacion.prioridad}
                                                </Badge>
                                            </div>
                                            <div className="text-xs text-muted-foreground">{notificacion.fecha}</div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-muted-foreground">
                                    No hay notificaciones pendientes
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Documentos y Expedientes Recientes */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Documentos Recientes */}
                    <Card className="group">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="h-5 w-5 text-brand-primary" />
                                    Documentos Recientes
                                </CardTitle>
                                <CardDescription>Últimos documentos creados</CardDescription>
                            </div>
                            <Link 
                                href={route('admin.documentos.index')} 
                                className="text-xs text-brand-primary hover:underline flex items-center gap-1 opacity-70 hover:opacity-100 transition-opacity"
                            >
                                Ver todos <ArrowRight className="h-3 w-3" />
                            </Link>
                        </CardHeader>
                        <CardContent>
                            {documentos_recientes.length > 0 ? (
                                <div className="space-y-3">
                                    {documentos_recientes.map((documento) => (
                                        <Link
                                            key={documento.id}
                                            href={route('admin.documentos.show', documento.id)}
                                            className="block rounded-lg border p-3 transition-all hover:bg-accent hover:shadow-sm hover:border-brand-primary/30"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex-1">
                                                    <div className="text-sm font-medium">{documento.codigo}</div>
                                                    <div className="text-xs text-muted-foreground line-clamp-1">
                                                        {documento.titulo}
                                                    </div>
                                                </div>
                                                <Badge variant="outline" className="text-xs">
                                                    {documento.estado}
                                                </Badge>
                                            </div>
                                            <div className="mt-2 text-xs text-muted-foreground flex items-center justify-between">
                                                <span>{documento.fecha}</span>
                                                <span className="text-brand-primary opacity-0 group-hover:opacity-100 transition-opacity">Ver →</span>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <Link 
                                    href={route('admin.documentos.create')}
                                    className="block text-center py-8 text-muted-foreground hover:text-brand-primary transition-colors"
                                >
                                    <FileText className="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    <p>No hay documentos recientes</p>
                                    <p className="text-xs text-brand-primary mt-2">+ Crear nuevo documento</p>
                                </Link>
                            )}
                        </CardContent>
                    </Card>

                    {/* Expedientes Recientes */}
                    <Card className="group">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <Folder className="h-5 w-5 text-brand-primary" />
                                    Expedientes Recientes
                                </CardTitle>
                                <CardDescription>Últimos expedientes creados</CardDescription>
                            </div>
                            <Link 
                                href={route('admin.expedientes.index')} 
                                className="text-xs text-brand-primary hover:underline flex items-center gap-1 opacity-70 hover:opacity-100 transition-opacity"
                            >
                                Ver todos <ArrowRight className="h-3 w-3" />
                            </Link>
                        </CardHeader>
                        <CardContent>
                            {expedientes_recientes.length > 0 ? (
                                <div className="space-y-3">
                                    {expedientes_recientes.map((expediente) => (
                                        <Link
                                            key={expediente.id}
                                            href={route('admin.expedientes.show', expediente.id)}
                                            className="block rounded-lg border p-3 transition-all hover:bg-accent hover:shadow-sm hover:border-brand-primary/30"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex-1">
                                                    <div className="text-sm font-medium">{expediente.codigo}</div>
                                                    <div className="text-xs text-muted-foreground line-clamp-1">
                                                        {expediente.titulo}
                                                    </div>
                                                </div>
                                                <Badge variant="outline" className="text-xs">
                                                    {expediente.estado}
                                                </Badge>
                                            </div>
                                            <div className="mt-2 text-xs text-muted-foreground flex items-center justify-between">
                                                <span>{expediente.fecha}</span>
                                                <span className="text-brand-primary opacity-0 group-hover:opacity-100 transition-opacity">Ver →</span>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <Link 
                                    href={route('admin.expedientes.create')}
                                    className="block text-center py-8 text-muted-foreground hover:text-brand-primary transition-colors"
                                >
                                    <Folder className="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    <p>No hay expedientes recientes</p>
                                    <p className="text-xs text-brand-primary mt-2">+ Crear nuevo expediente</p>
                                </Link>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
