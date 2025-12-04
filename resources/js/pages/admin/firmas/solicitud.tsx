import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    FileText, 
    Users, 
    Clock,
    CheckCircle,
    XCircle,
    ArrowLeft,
    PenTool,
    AlertTriangle
} from 'lucide-react';

interface SolicitudFirma {
    id: number;
    titulo: string;
    descripcion: string;
    estado: string;
    prioridad: string;
    tipo_flujo: string;
    fecha_limite: string;
    documento: {
        id: number;
        nombre: string;
        tipo_documento: string;
    };
    solicitante: {
        id: number;
        name: string;
        email: string;
    };
    firmantes: Array<{
        id: number;
        usuario_id: number;
        orden: number;
        estado: string;
        fecha_firma: string | null;
        usuario: {
            id: number;
            name: string;
            email: string;
        };
    }>;
    progreso: {
        total: number;
        completadas: number;
        pendientes: number;
        porcentaje: number;
    };
}

interface Props {
    solicitud: SolicitudFirma;
    puede_firmar: boolean;
    puede_cancelar: boolean;
}

export default function SolicitudDetalle({ solicitud, puede_firmar, puede_cancelar }: Props) {
    const [procesando, setProcesando] = useState(false);

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

    const getBadgePrioridad = (prioridad: string) => {
        const colores = {
            'urgente': 'bg-red-500 text-white',
            'alta': 'bg-orange-500 text-white',
            'normal': 'bg-blue-500 text-white',
            'baja': 'bg-gray-500 text-white'
        };
        return colores[prioridad as keyof typeof colores] || 'bg-gray-500 text-white';
    };

    const getIconoEstadoFirmante = (estado: string) => {
        switch (estado) {
            case 'firmado':
                return <CheckCircle className="w-4 h-4 text-green-600" />;
            case 'rechazado':
                return <XCircle className="w-4 h-4 text-red-600" />;
            case 'pendiente':
                return <Clock className="w-4 h-4 text-yellow-600" />;
            default:
                return <AlertTriangle className="w-4 h-4 text-gray-600" />;
        }
    };

    const firmarDocumento = async () => {
        setProcesando(true);
        try {
            router.post(route('admin.firmas.solicitud.firmar', solicitud.id));
        } finally {
            setProcesando(false);
        }
    };

    const cancelarSolicitud = async () => {
        if (confirm('¿Estás seguro de cancelar esta solicitud?')) {
            setProcesando(true);
            try {
                router.post(route('admin.firmas.solicitud.cancelar', solicitud.id));
            } finally {
                setProcesando(false);
            }
        }
    };

    return (
        <AppLayout>
            <Head title={`Solicitud: ${solicitud.titulo}`} />
            
            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center space-x-3 mb-2">
                            <Link href={route('admin.firmas.solicitudes')}>
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Volver
                                </Button>
                            </Link>
                        </div>
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">{solicitud.titulo}</h1>
                                <p className="text-gray-600 mt-1">Solicitud #{solicitud.id}</p>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Badge className={getBadgeEstado(solicitud.estado)}>
                                    {solicitud.estado}
                                </Badge>
                                <Badge className={getBadgePrioridad(solicitud.prioridad)}>
                                    {solicitud.prioridad}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    {/* Acciones */}
                    {(puede_firmar || puede_cancelar) && (
                        <div className="mb-6 flex space-x-3">
                            {puede_firmar && (
                                <Button onClick={firmarDocumento} disabled={procesando}>
                                    <PenTool className="w-4 h-4 mr-2" />
                                    Firmar Documento
                                </Button>
                            )}
                            {puede_cancelar && (
                                <Button variant="destructive" onClick={cancelarSolicitud} disabled={procesando}>
                                    Cancelar Solicitud
                                </Button>
                            )}
                        </div>
                    )}

                    <Tabs defaultValue="general" className="space-y-6">
                        <TabsList>
                            <TabsTrigger value="general">General</TabsTrigger>
                            <TabsTrigger value="firmantes">Firmantes</TabsTrigger>
                            <TabsTrigger value="documento">Documento</TabsTrigger>
                        </TabsList>

                        <TabsContent value="general">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Información General</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div>
                                            <label className="text-sm font-medium text-gray-500">Solicitante</label>
                                            <p className="text-sm">{solicitud.solicitante.name}</p>
                                            <p className="text-xs text-gray-500">{solicitud.solicitante.email}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-gray-500">Descripción</label>
                                            <p className="text-sm">{solicitud.descripcion || 'Sin descripción'}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-gray-500">Tipo de Flujo</label>
                                            <p className="text-sm">{solicitud.tipo_flujo}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-gray-500">Fecha Límite</label>
                                            <p className="text-sm">{solicitud.fecha_limite ? new Date(solicitud.fecha_limite).toLocaleDateString() : 'Sin límite'}</p>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Progreso</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            <div>
                                                <div className="flex justify-between text-sm mb-2">
                                                    <span>Completado</span>
                                                    <span>{solicitud.progreso.porcentaje}%</span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        className="bg-blue-600 h-2 rounded-full" 
                                                        style={{ width: `${solicitud.progreso.porcentaje}%` }}
                                                    ></div>
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-3 gap-4 text-center">
                                                <div>
                                                    <div className="text-2xl font-bold text-green-600">{solicitud.progreso.completadas}</div>
                                                    <div className="text-xs text-gray-500">Firmadas</div>
                                                </div>
                                                <div>
                                                    <div className="text-2xl font-bold text-yellow-600">{solicitud.progreso.pendientes}</div>
                                                    <div className="text-xs text-gray-500">Pendientes</div>
                                                </div>
                                                <div>
                                                    <div className="text-2xl font-bold text-blue-600">{solicitud.progreso.total}</div>
                                                    <div className="text-xs text-gray-500">Total</div>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="firmantes">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Users className="w-5 h-5 mr-2" />
                                        Firmantes ({solicitud.firmantes.length})
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {solicitud.firmantes.map((firmante) => (
                                            <div key={firmante.id} className="flex items-center justify-between p-4 border rounded-lg">
                                                <div className="flex items-center space-x-3">
                                                    {getIconoEstadoFirmante(firmante.estado)}
                                                    <div>
                                                        <div className="font-medium">{firmante.usuario.name}</div>
                                                        <div className="text-sm text-gray-500">{firmante.usuario.email}</div>
                                                        {solicitud.tipo_flujo === 'secuencial' && (
                                                            <div className="text-xs text-gray-400">Orden: #{firmante.orden}</div>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <Badge className={getBadgeEstado(firmante.estado)}>
                                                        {firmante.estado}
                                                    </Badge>
                                                    {firmante.fecha_firma && (
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            {new Date(firmante.fecha_firma).toLocaleDateString()}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="documento">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <FileText className="w-5 h-5 mr-2" />
                                        Documento
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div className="flex items-start space-x-3">
                                            <FileText className="w-5 h-5 text-blue-600 mt-0.5" />
                                            <div className="flex-1">
                                                <h4 className="font-medium text-blue-900">{solicitud.documento.nombre}</h4>
                                                <p className="text-sm text-blue-700 mt-1">
                                                    Tipo: {solicitud.documento.tipo_documento}
                                                </p>
                                            </div>
                                            <Link href={route('admin.documentos.show', solicitud.documento.id)}>
                                                <Button size="sm" variant="outline">
                                                    Ver Documento
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}
