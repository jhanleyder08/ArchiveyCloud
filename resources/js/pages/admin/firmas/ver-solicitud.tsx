import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, XCircle, Clock, User, FileText, Calendar, AlertTriangle, PenTool, MessageSquare, Key } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Badge } from '../../../components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '../../../components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Textarea } from '../../../components/ui/textarea';
import { Label } from '../../../components/ui/label';
import { Alert, AlertDescription } from '../../../components/ui/alert';

interface Documento {
    id: number;
    nombre: string;
    expediente?: {
        id: number;
        codigo: string;
        nombre: string;
    };
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface FirmanteSolicitud {
    id: number;
    usuario: Usuario;
    orden: number;
    rol: string;
    es_obligatorio: boolean;
    estado: string;
    fecha_firma?: string;
    comentario?: string;
}

interface FirmaDigital {
    id: number;
    usuario: Usuario;
    fecha_firma: string;
    hash_firma: string;
    es_valida: boolean;
    comentario?: string;
}

interface SolicitudFirma {
    id: number;
    titulo: string;
    descripcion: string;
    tipo_flujo: string;
    prioridad: string;
    estado: string;
    fecha_limite: string;
    created_at: string;
    documento: Documento;
    solicitante: Usuario;
    firmantes: FirmanteSolicitud[];
    firmas: FirmaDigital[];
}

interface EstadisticasSolicitud {
    total_firmantes: number;
    firmantes_completados: number;
    firmantes_pendientes: number;
    firmantes_rechazados: number;
    porcentaje_avance: number;
    tiempo_promedio_firma: string;
}

interface CertificadoDigital {
    id: number;
    nombre_certificado: string;
    numero_serie: string;
    fecha_vencimiento: string;
    tipo_certificado: string;
}

interface Props {
    solicitud: SolicitudFirma;
    firmante_actual?: FirmanteSolicitud;
    puede_firmar: boolean;
    progreso: any;
    estadisticas: EstadisticasSolicitud;
}

export default function VerSolicitud({ solicitud, firmante_actual, puede_firmar, progreso, estadisticas }: Props) {
    const [certificados, setCertificados] = useState<CertificadoDigital[]>([]);
    const [showFirmarModal, setShowFirmarModal] = useState(false);
    const [showRechazarModal, setShowRechazarModal] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        certificado_id: '',
        comentario: '',
        razon: ''
    });

    const getBadgeVariant = (estado: string) => {
        switch (estado) {
            case 'completada':
                return 'secondary';
            case 'en_proceso':
                return 'default';
            case 'pendiente':
                return 'outline';
            case 'cancelada':
            case 'vencida':
                return 'destructive';
            default:
                return 'outline';
        }
    };

    const getPrioridadColor = (prioridad: string) => {
        switch (prioridad) {
            case 'urgente':
                return 'text-red-600 bg-red-100';
            case 'alta':
                return 'text-orange-600 bg-orange-100';
            case 'normal':
                return 'text-blue-600 bg-blue-100';
            case 'baja':
                return 'text-gray-600 bg-gray-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    };

    const getEstadoFirmanteIcon = (estado: string) => {
        switch (estado) {
            case 'firmado':
                return <CheckCircle className="h-4 w-4 text-green-500" />;
            case 'rechazado':
                return <XCircle className="h-4 w-4 text-red-500" />;
            case 'pendiente':
                return <Clock className="h-4 w-4 text-yellow-500" />;
            default:
                return <Clock className="h-4 w-4 text-gray-500" />;
        }
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const esVencida = (fechaLimite: string) => {
        return new Date(fechaLimite) < new Date();
    };

    const handleFirmar = () => {
        if (!data.certificado_id) return;

        post(route('admin.firmas.solicitud.firmar', solicitud.id), {
            onSuccess: () => {
                reset();
                setShowFirmarModal(false);
            }
        });
    };

    const handleRechazar = () => {
        if (!data.comentario.trim()) return;

        post(route('admin.firmas.solicitud.rechazar', solicitud.id), {
            onSuccess: () => {
                reset();
                setShowRechazarModal(false);
            }
        });
    };

    const loadCertificados = async () => {
        try {
            const response = await fetch(route('admin.firmas.api.certificados'));
            const certs = await response.json();
            setCertificados(certs);
        } catch (error) {
            console.error('Error cargando certificados:', error);
        }
    };

    React.useEffect(() => {
        if (puede_firmar && showFirmarModal) {
            loadCertificados();
        }
    }, [puede_firmar, showFirmarModal]);

    return (
        <AppLayout>
            <Head title={`Solicitud: ${solicitud.titulo}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={route('admin.firmas.solicitudes')}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{solicitud.titulo}</h1>
                            <p className="text-muted-foreground">
                                Solicitud de firma digital - {formatearFecha(solicitud.created_at)}
                            </p>
                        </div>
                    </div>
                    <Badge variant={getBadgeVariant(solicitud.estado)} className="text-base px-3 py-1">
                        {solicitud.estado.replace('_', ' ').toUpperCase()}
                    </Badge>
                </div>

                {/* Alertas */}
                {esVencida(solicitud.fecha_limite) && solicitud.estado !== 'completada' && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            <strong>¡Solicitud Vencida!</strong> La fecha límite para firmar era el {formatearFecha(solicitud.fecha_limite)}.
                        </AlertDescription>
                    </Alert>
                )}

                {puede_firmar && (
                    <Alert>
                        <PenTool className="h-4 w-4" />
                        <AlertDescription>
                            <strong>Acción Requerida:</strong> Esta solicitud requiere tu firma.
                        </AlertDescription>
                    </Alert>
                )}

                {/* Información Principal */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalles de la Solicitud</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Documento</label>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <FileText className="h-4 w-4 text-gray-500" />
                                            <span className="font-medium">{solicitud.documento.nombre}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Solicitante</label>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <User className="h-4 w-4 text-gray-500" />
                                            <div>
                                                <div className="font-medium">{solicitud.solicitante.name}</div>
                                                <div className="text-sm text-gray-500">{solicitud.solicitante.email}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Prioridad</label>
                                        <div className="mt-1">
                                            <Badge className={getPrioridadColor(solicitud.prioridad)}>
                                                {solicitud.prioridad.toUpperCase()}
                                            </Badge>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Fecha Límite</label>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <Calendar className="h-4 w-4 text-gray-500" />
                                            <span className={esVencida(solicitud.fecha_limite) ? 'text-red-600 font-medium' : ''}>
                                                {formatearFecha(solicitud.fecha_limite)}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {solicitud.descripcion && (
                                    <div>
                                        <label className="text-sm font-medium text-gray-600">Descripción</label>
                                        <p className="mt-1 text-gray-900">{solicitud.descripcion}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div>
                        {/* Estadísticas */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Progreso</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-blue-600">
                                        {estadisticas.porcentaje_avance}%
                                    </div>
                                    <div className="text-sm text-gray-500">Completado</div>
                                </div>

                                <div className="w-full bg-gray-200 rounded-full h-3">
                                    <div 
                                        className="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                                        style={{ width: `${estadisticas.porcentaje_avance}%` }}
                                    ></div>
                                </div>

                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div className="text-center">
                                        <div className="text-lg font-semibold text-green-600">
                                            {estadisticas.firmantes_completados}
                                        </div>
                                        <div className="text-gray-500">Firmados</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-lg font-semibold text-yellow-600">
                                            {estadisticas.firmantes_pendientes}
                                        </div>
                                        <div className="text-gray-500">Pendientes</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Acciones para firmar */}
                        {puede_firmar && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Acciones</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <Dialog open={showFirmarModal} onOpenChange={setShowFirmarModal}>
                                        <DialogTrigger asChild>
                                            <Button className="w-full">
                                                <PenTool className="h-4 w-4 mr-2" />
                                                Firmar Documento
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Firmar Documento</DialogTitle>
                                                <DialogDescription>
                                                    Selecciona un certificado digital para firmar.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="space-y-4">
                                                <div className="space-y-2">
                                                    <Label>Certificado Digital</Label>
                                                    <Select value={data.certificado_id} onValueChange={(value) => setData('certificado_id', value)}>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Selecciona un certificado" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {certificados.map((cert) => (
                                                                <SelectItem key={cert.id} value={cert.id.toString()}>
                                                                    {cert.nombre_certificado}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>

                                                <div className="space-y-2">
                                                    <Label>Comentario (opcional)</Label>
                                                    <Textarea
                                                        placeholder="Comentario sobre la firma..."
                                                        value={data.comentario}
                                                        onChange={(e) => setData('comentario', e.target.value)}
                                                    />
                                                </div>

                                                <div className="flex justify-end space-x-2">
                                                    <Button variant="outline" onClick={() => setShowFirmarModal(false)}>
                                                        Cancelar
                                                    </Button>
                                                    <Button 
                                                        onClick={handleFirmar}
                                                        disabled={processing || !data.certificado_id}
                                                    >
                                                        {processing ? 'Firmando...' : 'Firmar'}
                                                    </Button>
                                                </div>
                                            </div>
                                        </DialogContent>
                                    </Dialog>

                                    <Dialog open={showRechazarModal} onOpenChange={setShowRechazarModal}>
                                        <DialogTrigger asChild>
                                            <Button variant="destructive" className="w-full">
                                                <XCircle className="h-4 w-4 mr-2" />
                                                Rechazar Firma
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Rechazar Firma</DialogTitle>
                                                <DialogDescription>
                                                    Explica el motivo del rechazo.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="space-y-4">
                                                <div className="space-y-2">
                                                    <Label>Motivo del Rechazo</Label>
                                                    <Textarea
                                                        placeholder="Describe el motivo..."
                                                        value={data.comentario}
                                                        onChange={(e) => setData('comentario', e.target.value)}
                                                        required
                                                    />
                                                </div>

                                                <div className="flex justify-end space-x-2">
                                                    <Button variant="outline" onClick={() => setShowRechazarModal(false)}>
                                                        Cancelar
                                                    </Button>
                                                    <Button 
                                                        variant="destructive"
                                                        onClick={handleRechazar}
                                                        disabled={processing || !data.comentario.trim()}
                                                    >
                                                        {processing ? 'Rechazando...' : 'Rechazar'}
                                                    </Button>
                                                </div>
                                            </div>
                                        </DialogContent>
                                    </Dialog>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>

                {/* Firmantes */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Firmantes</CardTitle>
                        <CardDescription>
                            Estado actual de todos los firmantes requeridos
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {solicitud.firmantes
                                .sort((a, b) => a.orden - b.orden)
                                .map((firmante) => (
                                <div 
                                    key={firmante.id} 
                                    className="flex items-center justify-between p-4 border rounded-lg"
                                >
                                    <div className="flex items-center space-x-4">
                                        <div className="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-sm font-medium">
                                            {firmante.orden}
                                        </div>
                                        <div>
                                            <div className="font-medium">{firmante.usuario.name}</div>
                                            <div className="text-sm text-gray-500">{firmante.usuario.email}</div>
                                            <div className="text-xs text-gray-400">
                                                Rol: {firmante.rol} • {firmante.es_obligatorio ? 'Obligatorio' : 'Opcional'}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        {firmante.fecha_firma && (
                                            <div className="text-right text-sm">
                                                <div className="text-gray-500">Firmado el</div>
                                                <div>{formatearFecha(firmante.fecha_firma)}</div>
                                            </div>
                                        )}
                                        <div className="flex items-center space-x-2">
                                            {getEstadoFirmanteIcon(firmante.estado)}
                                            <Badge variant={
                                                firmante.estado === 'firmado' ? 'secondary' :
                                                firmante.estado === 'rechazado' ? 'destructive' : 'outline'
                                            }>
                                                {firmante.estado}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
