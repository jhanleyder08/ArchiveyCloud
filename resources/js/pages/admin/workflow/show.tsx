import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    GitBranch, 
    FileText, 
    User, 
    Calendar, 
    Clock, 
    CheckCircle, 
    XCircle, 
    AlertTriangle,
    MessageSquare,
    ArrowRight,
    Shield,
    Users,
    Send,
    Ban
} from 'lucide-react';

interface NivelAprobacion {
    nivel: number;
    usuario: {
        id: number;
        name: string;
        email: string;
        cargo: string;
    } | null;
    es_actual: boolean;
    completado: boolean;
    aprobacion: {
        accion: string;
        comentarios: string;
        fecha: string;
        tiempo_respuesta: number;
    } | null;
}

interface Workflow {
    id: number;
    estado: string;
    progreso: number;
    prioridad: string;
    descripcion: string;
    fecha_solicitud: string;
    fecha_vencimiento: string;
    fecha_aprobacion?: string;
    fecha_rechazo?: string;
    comentarios_finales?: string;
    esta_vencido: boolean;
    requiere_unanime: boolean;
    documento: {
        id: number;
        nombre: string;
        codigo: string;
    };
    solicitante: {
        name: string;
        email: string;
    };
    revisor_actual?: {
        name: string;
        email: string;
    };
    aprobador_final?: {
        name: string;
        email: string;
    };
}

interface Props {
    workflow: Workflow;
    nivelesAprobacion: NivelAprobacion[];
    puedeAprobar: boolean;
    esSolicitante: boolean;
}

export default function WorkflowShow({ workflow, nivelesAprobacion, puedeAprobar, esSolicitante }: Props) {
    const [mostrarCancelar, setMostrarCancelar] = useState(false);

    const getEstadoBadge = (estado: string) => {
        const badges = {
            'borrador': <Badge variant="outline" className="text-gray-600">Borrador</Badge>,
            'pendiente': <Badge variant="outline" className="text-yellow-600">Pendiente</Badge>,
            'en_revision': <Badge className="bg-blue-100 text-blue-800">En Revisión</Badge>,
            'aprobado': <Badge className="bg-green-100 text-green-800">Aprobado</Badge>,
            'rechazado': <Badge variant="destructive">Rechazado</Badge>,
            'cancelado': <Badge variant="outline" className="text-gray-500">Cancelado</Badge>
        };
        return badges[estado as keyof typeof badges] || <Badge variant="outline">{estado}</Badge>;
    };

    const getPrioridadBadge = (prioridad: string) => {
        const badges = {
            'Crítica': <Badge variant="destructive">Crítica</Badge>,
            'Alta': <Badge className="bg-orange-100 text-orange-800">Alta</Badge>,
            'Media': <Badge variant="outline" className="text-blue-600">Media</Badge>,
            'Baja': <Badge variant="outline" className="text-gray-600">Baja</Badge>
        };
        return badges[prioridad as keyof typeof badges] || <Badge variant="outline">{prioridad}</Badge>;
    };

    const getAccionIcon = (accion: string) => {
        switch (accion) {
            case 'Aprobado':
                return <CheckCircle className="w-4 h-4 text-green-600" />;
            case 'Rechazado':
                return <XCircle className="w-4 h-4 text-red-600" />;
            case 'Enviado a Revisión':
                return <Send className="w-4 h-4 text-blue-600" />;
            case 'Delegado':
                return <Users className="w-4 h-4 text-orange-600" />;
            default:
                return <Clock className="w-4 h-4 text-gray-400" />;
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

    const calcularTiempoTranscurrido = (fecha: string) => {
        const ahora = new Date().getTime();
        const fechaWorkflow = new Date(fecha).getTime();
        const diferencia = ahora - fechaWorkflow;
        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        
        if (dias > 0) {
            return `${dias} día${dias > 1 ? 's' : ''} y ${horas} hora${horas > 1 ? 's' : ''}`;
        } else {
            return `${horas} hora${horas > 1 ? 's' : ''}`;
        }
    };

    return (
        <AppLayout>
            <Head title={`Workflow - ${workflow.documento.nombre}`} />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <GitBranch className="w-8 h-8 text-blue-600" />
                            Workflow #{workflow.id}
                        </h1>
                        <p className="text-gray-600 mt-1">
                            {workflow.documento.nombre} - {workflow.documento.codigo}
                        </p>
                    </div>
                    <div className="flex gap-3">
                        {puedeAprobar && (
                            <Link href={`/admin/workflow/${workflow.id}/aprobar`}>
                                <Button className="bg-green-600 hover:bg-green-700">
                                    <CheckCircle className="w-4 h-4 mr-2" />
                                    Revisar Documento
                                </Button>
                            </Link>
                        )}
                        <Link href="/admin/workflow">
                            <Button variant="outline">
                                Volver a Workflows
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Alertas */}
                {workflow.esta_vencido && workflow.estado !== 'aprobado' && workflow.estado !== 'rechazado' && (
                    <Alert className="mb-6 border-red-200 bg-red-50">
                        <AlertTriangle className="h-4 w-4 text-red-600" />
                        <AlertDescription className="text-red-800">
                            <strong>Workflow vencido:</strong> Este documento ha excedido la fecha límite de aprobación.
                        </AlertDescription>
                    </Alert>
                )}

                {puedeAprobar && (
                    <Alert className="mb-6 border-blue-200 bg-blue-50">
                        <CheckCircle className="h-4 w-4 text-blue-600" />
                        <AlertDescription className="text-blue-800">
                            <strong>Acción requerida:</strong> Este documento está pendiente de tu aprobación.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Información Principal */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Detalles del Workflow */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between">
                                    <span className="flex items-center gap-2">
                                        <FileText className="w-5 h-5" />
                                        Información del Workflow
                                    </span>
                                    {getEstadoBadge(workflow.estado)}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                            <User className="w-4 h-4" />
                                            Solicitante
                                        </div>
                                        <p className="font-medium">{workflow.solicitante.name}</p>
                                        <p className="text-sm text-gray-600">{workflow.solicitante.email}</p>
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                            <Calendar className="w-4 h-4" />
                                            Fecha de Solicitud
                                        </div>
                                        <p className="font-medium">{formatearFecha(workflow.fecha_solicitud)}</p>
                                        <p className="text-sm text-gray-600">
                                            Hace {calcularTiempoTranscurrido(workflow.fecha_solicitud)}
                                        </p>
                                    </div>
                                </div>

                                <Separator />

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                            <Shield className="w-4 h-4" />
                                            Prioridad
                                        </div>
                                        {getPrioridadBadge(workflow.prioridad)}
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                            <Clock className="w-4 h-4" />
                                            Vencimiento
                                        </div>
                                        <p className="font-medium">{formatearFecha(workflow.fecha_vencimiento)}</p>
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                            <Users className="w-4 h-4" />
                                            Tipo de Aprobación
                                        </div>
                                        <Badge variant="outline">
                                            {workflow.requiere_unanime ? 'Unánime' : 'Secuencial'}
                                        </Badge>
                                    </div>
                                </div>

                                {workflow.descripcion && (
                                    <>
                                        <Separator />
                                        <div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 mb-2">
                                                <MessageSquare className="w-4 h-4" />
                                                Descripción de la Solicitud
                                            </div>
                                            <div className="bg-gray-50 p-4 rounded-lg">
                                                <p className="text-sm">{workflow.descripcion}</p>
                                            </div>
                                        </div>
                                    </>
                                )}

                                {/* Comentarios finales */}
                                {workflow.comentarios_finales && (
                                    <>
                                        <Separator />
                                        <div>
                                            <div className="flex items-center gap-2 text-sm text-gray-600 mb-2">
                                                <MessageSquare className="w-4 h-4" />
                                                Comentarios Finales
                                            </div>
                                            <div className="bg-gray-50 p-4 rounded-lg">
                                                <p className="text-sm">{workflow.comentarios_finales}</p>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Timeline de Aprobaciones */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ArrowRight className="w-5 h-5" />
                                    Timeline de Aprobaciones
                                </CardTitle>
                                <CardDescription>
                                    Progreso del workflow de aprobación
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="mb-6">
                                    <div className="flex justify-between items-center mb-2">
                                        <span className="text-sm font-medium">Progreso General</span>
                                        <span className="text-sm text-gray-600">{workflow.progreso}%</span>
                                    </div>
                                    <Progress value={workflow.progreso} className="h-3" />
                                </div>

                                <div className="space-y-4">
                                    {nivelesAprobacion.map((nivel, index) => (
                                        <div key={nivel.nivel} className="relative">
                                            {/* Línea conectora */}
                                            {index < nivelesAprobacion.length - 1 && (
                                                <div className="absolute left-6 top-12 w-0.5 h-8 bg-gray-200"></div>
                                            )}
                                            
                                            <div className="flex items-start gap-4">
                                                {/* Icono de estado */}
                                                <div className={`
                                                    w-12 h-12 rounded-full flex items-center justify-center border-2
                                                    ${nivel.completado 
                                                        ? 'bg-green-100 border-green-500' 
                                                        : nivel.es_actual 
                                                            ? 'bg-blue-100 border-blue-500' 
                                                            : 'bg-gray-100 border-gray-300'
                                                    }
                                                `}>
                                                    {nivel.completado ? (
                                                        nivel.aprobacion ? getAccionIcon(nivel.aprobacion.accion) : <CheckCircle className="w-5 h-5 text-green-600" />
                                                    ) : nivel.es_actual ? (
                                                        <Clock className="w-5 h-5 text-blue-600" />
                                                    ) : (
                                                        <span className="text-sm font-medium text-gray-400">{nivel.nivel + 1}</span>
                                                    )}
                                                </div>

                                                {/* Contenido */}
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <h4 className={`font-medium ${nivel.es_actual ? 'text-blue-600' : ''}`}>
                                                                Nivel {nivel.nivel + 1} - {nivel.usuario?.name || 'Usuario no disponible'}
                                                            </h4>
                                                            <p className="text-sm text-gray-600">
                                                                {nivel.usuario?.cargo} - {nivel.usuario?.email}
                                                            </p>
                                                        </div>
                                                        <div className="text-right">
                                                            {nivel.completado && nivel.aprobacion && (
                                                                <>
                                                                    <Badge 
                                                                        variant={nivel.aprobacion.accion === 'Aprobado' ? 'default' : 'destructive'}
                                                                        className="mb-1"
                                                                    >
                                                                        {nivel.aprobacion.accion}
                                                                    </Badge>
                                                                    <p className="text-xs text-gray-500">
                                                                        {formatearFecha(nivel.aprobacion.fecha)}
                                                                    </p>
                                                                    <p className="text-xs text-gray-500">
                                                                        Tiempo: {nivel.aprobacion.tiempo_respuesta}h
                                                                    </p>
                                                                </>
                                                            )}
                                                            {nivel.es_actual && !nivel.completado && (
                                                                <Badge className="bg-blue-100 text-blue-800">
                                                                    Pendiente
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {nivel.aprobacion?.comentarios && (
                                                        <div className="mt-2 p-3 bg-gray-50 rounded-lg">
                                                            <p className="text-sm text-gray-700">{nivel.aprobacion.comentarios}</p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Panel Lateral */}
                    <div className="space-y-6">
                        {/* Acciones */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Acciones</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {puedeAprobar && (
                                    <Link href={`/admin/workflow/${workflow.id}/aprobar`}>
                                        <Button className="w-full bg-green-600 hover:bg-green-700">
                                            <CheckCircle className="w-4 h-4 mr-2" />
                                            Aprobar/Rechazar
                                        </Button>
                                    </Link>
                                )}
                                
                                {esSolicitante && workflow.estado !== 'aprobado' && workflow.estado !== 'rechazado' && workflow.estado !== 'cancelado' && (
                                    <Button 
                                        variant="outline" 
                                        className="w-full text-red-600 border-red-300 hover:bg-red-50"
                                        onClick={() => setMostrarCancelar(true)}
                                    >
                                        <Ban className="w-4 h-4 mr-2" />
                                        Cancelar Workflow
                                    </Button>
                                )}

                                <Link href={`/admin/documentos/${workflow.documento.id}`}>
                                    <Button variant="outline" className="w-full">
                                        <FileText className="w-4 h-4 mr-2" />
                                        Ver Documento
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>

                        {/* Información Actual */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Estado Actual</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-gray-600 mb-1">Estado</div>
                                    {getEstadoBadge(workflow.estado)}
                                </div>

                                {workflow.revisor_actual && (
                                    <div>
                                        <div className="text-sm text-gray-600 mb-1">Revisor Actual</div>
                                        <p className="font-medium">{workflow.revisor_actual.name}</p>
                                        <p className="text-sm text-gray-500">{workflow.revisor_actual.email}</p>
                                    </div>
                                )}

                                {workflow.aprobador_final && (
                                    <div>
                                        <div className="text-sm text-gray-600 mb-1">Aprobador Final</div>
                                        <p className="font-medium">{workflow.aprobador_final.name}</p>
                                        <p className="text-sm text-gray-500">{workflow.aprobador_final.email}</p>
                                    </div>
                                )}

                                {workflow.fecha_aprobacion && (
                                    <div>
                                        <div className="text-sm text-gray-600 mb-1">Fecha de Aprobación</div>
                                        <p className="font-medium text-green-600">{formatearFecha(workflow.fecha_aprobacion)}</p>
                                    </div>
                                )}

                                {workflow.fecha_rechazo && (
                                    <div>
                                        <div className="text-sm text-gray-600 mb-1">Fecha de Rechazo</div>
                                        <p className="font-medium text-red-600">{formatearFecha(workflow.fecha_rechazo)}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Estadísticas */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Estadísticas</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Niveles completados:</span>
                                    <span className="font-medium">
                                        {nivelesAprobacion.filter(n => n.completado).length} / {nivelesAprobacion.length}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Progreso:</span>
                                    <span className="font-medium">{workflow.progreso}%</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-600">Días transcurridos:</span>
                                    <span className="font-medium">
                                        {Math.floor((new Date().getTime() - new Date(workflow.fecha_solicitud).getTime()) / (1000 * 60 * 60 * 24))}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
