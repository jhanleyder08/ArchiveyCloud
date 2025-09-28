import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Shield, 
    CheckCircle, 
    XCircle,
    Clock,
    TrendingUp,
    FileText,
    Calendar,
    Award,
    Plus,
    AlertCircle,
    Key,
    Users,
    PenTool,
    FileCheck
} from 'lucide-react';

interface EstadisticasAvanzadas {
    firmas: {
        total: number;
        hoy: number;
        este_mes: number;
        validas: number;
        con_certificado: number;
        porcentaje_validez: number;
    };
    certificados: {
        activos: number;
        proximos_vencer: number;
        vencidos: number;
    };
    solicitudes: {
        pendientes: number;
        completadas: number;
        vencidas: number;
    };
    usuario?: {
        certificados_activos: number;
        solicitudes_pendientes: number;
        firmas_realizadas_mes: number;
    };
}

interface SolicitudPendiente {
    id: number;
    titulo: string;
    documento: {
        id: number;
        nombre: string;
    };
    solicitante: {
        id: number;
        name: string;
    };
    prioridad: string;
    fecha_limite: string;
    estado: string;
}

interface MiSolicitud {
    id: number;
    titulo: string;
    documento: {
        id: number;
        nombre: string;
    };
    estado: string;
    progreso?: {
        total: number;
        completadas: number;
        pendientes: number;
        porcentaje: number;
    };
    firmantes: Array<{
        usuario: {
            id: number;
            name: string;
        };
        estado: string;
    }>;
}

interface CertificadoDigital {
    id: number;
    nombre_certificado: string;
    numero_serie: string;
    fecha_vencimiento: string;
    tipo_certificado: string;
    estado: string;
    vigente: boolean;
    dias_restantes?: number;
}

interface Props {
    estadisticas?: EstadisticasAvanzadas;
    solicitudes_pendientes?: SolicitudPendiente[];
    mis_solicitudes?: MiSolicitud[];
    certificados?: CertificadoDigital[];
    certificados_proximos_vencer?: CertificadoDigital[];
}

export default function DashboardFirmas({ 
    estadisticas, 
    solicitudes_pendientes, 
    mis_solicitudes, 
    certificados, 
    certificados_proximos_vencer 
}: Props) {
    const [estadisticasUsuario, setEstadisticasUsuario] = useState(estadisticas?.usuario);

    // Valores por defecto para arrays que pueden ser undefined
    const solicitudesPendientes = solicitudes_pendientes || [];
    const misSolicitudes = mis_solicitudes || [];
    const misCertificados = certificados || [];
    const certificadosProximosVencer = certificados_proximos_vencer || [];

    // Valores por defecto para estadísticas
    const stats = estadisticas || {
        firmas: { total: 0, hoy: 0, este_mes: 0, validas: 0, con_certificado: 0, porcentaje_validez: 0 },
        certificados: { activos: 0, proximos_vencer: 0, vencidos: 0 },
        solicitudes: { pendientes: 0, completadas: 0, vencidas: 0 },
        usuario: { certificados_activos: 0, solicitudes_pendientes: 0, firmas_realizadas_mes: 0 }
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getBadgePrioridad = (prioridad: string) => {
        const colores = {
            'urgente': 'bg-red-500 text-white',
            'alta': 'bg-orange-500 text-white',
            'normal': 'bg-blue-500 text-white',
            'baja': 'bg-gray-500 text-white'
        };
        return colores[prioridad as keyof typeof colores] || 'bg-gray-500 text-white';
    };

    const getBadgeEstado = (estado: string) => {
        const colores = {
            'pendiente': 'bg-yellow-500 text-white',
            'en_proceso': 'bg-blue-500 text-white',
            'completada': 'bg-green-500 text-white',
            'cancelada': 'bg-red-500 text-white',
            'vencida': 'bg-red-600 text-white'
        };
        return colores[estado as keyof typeof colores] || 'bg-gray-500 text-white';
    };

    const getIconoValidez = (valida: boolean, vigente: boolean) => {
        if (!valida) {
            return <XCircle className="w-4 h-4 text-red-600" />;
        }
        if (!vigente) {
            return <Clock className="w-4 h-4 text-yellow-600" />;
        }
        return <CheckCircle className="w-4 h-4 text-green-600" />;
    };

    return (
        <AppLayout>
            <Head title="Dashboard de Firmas Digitales" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="md:flex md:items-center md:justify-between mb-6">
                        <div className="flex-1 min-w-0">
                            <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Dashboard de Firmas Digitales 🔐
                            </h2>
                            <p className="mt-1 text-sm text-gray-500">
                                Sistema PKI avanzado para firma electrónica empresarial
                            </p>
                        </div>
                        <div className="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                            <Link href={route('admin.firmas.solicitudes.crear')}>
                                <Button>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Nueva Solicitud
                                </Button>
                            </Link>
                            <Link href={route('admin.firmas.certificados')}>
                                <Button variant="outline">
                                    <Key className="w-4 h-4 mr-2" />
                                    Certificados
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Alertas importantes */}
                    {certificadosProximosVencer.length > 0 && (
                        <Alert className="mb-6 border-orange-200 bg-orange-50">
                            <AlertCircle className="h-4 w-4 text-orange-600" />
                            <AlertDescription className="text-orange-800">
                                Tienes {certificadosProximosVencer.length} certificado(s) próximo(s) a vencer.
                                <Link href={route('admin.firmas.certificados')} className="ml-2 underline font-medium">
                                    Ver certificados
                                </Link>
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Estadísticas principales */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Firmas Totales</CardTitle>
                                <FileCheck className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.firmas.total}</div>
                                <p className="text-xs text-muted-foreground">
                                    {stats.firmas.hoy} hoy
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Este Mes</CardTitle>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.firmas.este_mes}</div>
                                <p className="text-xs text-muted-foreground">
                                    {estadisticasUsuario?.firmas_realizadas_mes || 0} tuyas
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Firmas Válidas</CardTitle>
                                <Shield className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.firmas.validas}</div>
                                <p className="text-xs text-muted-foreground">
                                    {stats.firmas.porcentaje_validez.toFixed(1)}% validez
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Certificados</CardTitle>
                                <Key className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.certificados.activos}</div>
                                <p className="text-xs text-muted-foreground">
                                    {estadisticasUsuario?.certificados_activos || 0} tuyos
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Contenido principal */}
                    <Tabs defaultValue="pendientes" className="space-y-6">
                        <TabsList>
                            <TabsTrigger value="pendientes">
                                Pendientes de Firma ({solicitudesPendientes.length})
                            </TabsTrigger>
                            <TabsTrigger value="mis-solicitudes">
                                Mis Solicitudes ({misSolicitudes.length})
                            </TabsTrigger>
                            <TabsTrigger value="certificados">
                                Mis Certificados ({misCertificados.length})
                            </TabsTrigger>
                            <TabsTrigger value="stats">
                                Estadísticas
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="pendientes" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Users className="w-5 h-5 mr-2" />
                                        Solicitudes Pendientes de Tu Firma
                                    </CardTitle>
                                    <CardDescription>
                                        Documentos que requieren tu firma digital
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {solicitudesPendientes.length === 0 ? (
                                        <div className="text-center py-8 text-gray-500">
                                            <PenTool className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                            <p>No tienes solicitudes pendientes de firma</p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {solicitudesPendientes.map((solicitud) => (
                                                <div key={solicitud.id} className="border rounded-lg p-4 hover:bg-gray-50">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1">
                                                            <h4 className="font-medium">{solicitud.titulo}</h4>
                                                            <p className="text-sm text-gray-600 mt-1">
                                                                {solicitud.documento.nombre}
                                                            </p>
                                                            <p className="text-xs text-gray-500 mt-1">
                                                                Solicitado por: {solicitud.solicitante.name}
                                                            </p>
                                                        </div>
                                                        <div className="flex items-center space-x-2">
                                                            <Badge className={getBadgePrioridad(solicitud.prioridad)}>
                                                                {solicitud.prioridad}
                                                            </Badge>
                                                            <Badge className={getBadgeEstado(solicitud.estado)}>
                                                                {solicitud.estado}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center justify-between mt-3">
                                                        <p className="text-xs text-gray-500">
                                                            Límite: {formatearFecha(solicitud.fecha_limite)}
                                                        </p>
                                                        <Link href={route('admin.firmas.solicitud', solicitud.id)}>
                                                            <Button size="sm">Ver Solicitud</Button>
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="mis-solicitudes" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <FileText className="w-5 h-5 mr-2" />
                                        Mis Solicitudes de Firma
                                    </CardTitle>
                                    <CardDescription>
                                        Solicitudes que has creado
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {misSolicitudes.length === 0 ? (
                                        <div className="text-center py-8 text-gray-500">
                                            <FileText className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                            <p>No has creado solicitudes de firma</p>
                                            <Link href={route('admin.firmas.solicitudes.crear')} className="mt-4">
                                                <Button>Crear Primera Solicitud</Button>
                                            </Link>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {misSolicitudes.map((solicitud) => (
                                                <div key={solicitud.id} className="border rounded-lg p-4 hover:bg-gray-50">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1">
                                                            <h4 className="font-medium">{solicitud.titulo}</h4>
                                                            <p className="text-sm text-gray-600 mt-1">
                                                                {solicitud.documento.nombre}
                                                            </p>
                                                            {solicitud.progreso && (
                                                                <div className="mt-2">
                                                                    <div className="text-xs text-gray-500 mb-1">
                                                                        Progreso: {solicitud.progreso.completadas}/{solicitud.progreso.total} firmantes
                                                                    </div>
                                                                    <div className="w-full bg-gray-200 rounded-full h-2">
                                                                        <div 
                                                                            className="bg-blue-600 h-2 rounded-full" 
                                                                            style={{ width: `${solicitud.progreso.porcentaje}%` }}
                                                                        ></div>
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center space-x-2">
                                                            <Badge className={getBadgeEstado(solicitud.estado)}>
                                                                {solicitud.estado}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center justify-between mt-3">
                                                        <div className="text-xs text-gray-500">
                                                            {solicitud.firmantes.length} firmante(s)
                                                        </div>
                                                        <Link href={route('admin.firmas.solicitud', solicitud.id)}>
                                                            <Button size="sm" variant="outline">Ver Detalles</Button>
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="certificados" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Key className="w-5 h-5 mr-2" />
                                        Mis Certificados Digitales
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {misCertificados.length === 0 ? (
                                        <div className="text-center py-8 text-gray-500">
                                            <Key className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                            <p>No tienes certificados digitales</p>
                                            <Link href={route('admin.firmas.certificados')} className="mt-4">
                                                <Button>Ver Certificados</Button>
                                            </Link>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {misCertificados.map((certificado) => (
                                                <div key={certificado.id} className="border rounded-lg p-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1">
                                                            <h4 className="font-medium">{certificado.nombre_certificado}</h4>
                                                            <p className="text-sm text-gray-600 mt-1">
                                                                Tipo: {certificado.tipo_certificado}
                                                            </p>
                                                            <p className="text-xs text-gray-500 mt-1">
                                                                Vence: {formatearFecha(certificado.fecha_vencimiento)}
                                                            </p>
                                                        </div>
                                                        <div className="flex items-center space-x-2">
                                                            <Badge variant={certificado.vigente ? "default" : "destructive"}>
                                                                {certificado.vigente ? "Vigente" : "Vencido"}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="stats" className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Estadísticas Generales</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex justify-between">
                                            <span>Total de firmas:</span>
                                            <span className="font-medium">{stats.firmas.total}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Con certificado PKI:</span>
                                            <span className="font-medium">{stats.firmas.con_certificado}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Porcentaje de validez:</span>
                                            <span className="font-medium">{stats.firmas.porcentaje_validez.toFixed(1)}%</span>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Solicitudes</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex justify-between">
                                            <span>Pendientes:</span>
                                            <span className="font-medium">{stats.solicitudes.pendientes}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Completadas:</span>
                                            <span className="font-medium">{stats.solicitudes.completadas}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Vencidas:</span>
                                            <span className="font-medium text-red-600">{stats.solicitudes.vencidas}</span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}
