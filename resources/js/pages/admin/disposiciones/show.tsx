import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { 
    ArrowLeft, 
    Edit,
    CheckCircle,
    Clock,
    AlertTriangle,
    XCircle,
    Archive,
    FileText,
    User,
    Calendar,
    MapPin,
    Eye,
    Download,
    History
} from 'lucide-react';

interface DisposicionFinal {
    id: number;
    tipo_disposicion: string;
    estado: string;
    fecha_vencimiento_retencion: string;
    fecha_propuesta: string;
    fecha_aprobacion?: string;
    fecha_ejecucion?: string;
    fecha_rechazo?: string;
    justificacion: string;
    observaciones?: string;
    observaciones_aprobacion?: string;
    observaciones_rechazo?: string;
    item_afectado: string;
    tipo_disposicion_label: string;
    estado_label: string;
    expediente?: {
        id: number;
        numero_expediente: string;
        titulo: string;
        serie_documental: string;
        ubicacion_fisica: string;
        estado_ciclo_vida: string;
        anos_retencion_archivo_central: number;
        anos_retencion_archivo_historico: number;
    };
    documento?: {
        id: number;
        nombre: string;
        ubicacion_fisica: string;
        expediente: {
            id: number;
            numero_expediente: string;
            titulo: string;
            serie_documental: string;
        };
    };
    responsable: {
        id: number;
        name: string;
        email: string;
    };
    aprobado_por?: {
        id: number;
        name: string;
        email: string;
    };
    rechazado_por?: {
        id: number;
        name: string;
        email: string;
    };
    dias_para_vencimiento: number;
    esta_vencida: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    disposicion: DisposicionFinal;
}

const estadoColors: Record<string, string> = {
    pendiente: 'bg-gray-100 text-gray-800 border-gray-200',
    en_revision: 'bg-blue-100 text-blue-800 border-blue-200',
    aprobado: 'bg-green-100 text-green-800 border-green-200',
    rechazado: 'bg-red-100 text-red-800 border-red-200',
    ejecutado: 'bg-purple-100 text-purple-800 border-purple-200',
    cancelado: 'bg-gray-100 text-gray-600 border-gray-200',
};

const tipoColors: Record<string, string> = {
    conservacion_permanente: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    eliminacion_controlada: 'bg-red-100 text-red-800 border-red-200',
    transferencia_historica: 'bg-blue-100 text-blue-800 border-blue-200',
    digitalizacion: 'bg-indigo-100 text-indigo-800 border-indigo-200',
    microfilmacion: 'bg-violet-100 text-violet-800 border-violet-200',
};

const estadoIcons: Record<string, React.ReactNode> = {
    pendiente: <Clock className="h-4 w-4" />,
    en_revision: <Eye className="h-4 w-4" />,
    aprobado: <CheckCircle className="h-4 w-4" />,
    rechazado: <XCircle className="h-4 w-4" />,
    ejecutado: <Archive className="h-4 w-4" />,
    cancelado: <AlertTriangle className="h-4 w-4" />,
};

export default function DisposicionShow({ disposicion }: Props) {
    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatearFechaCorta = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    return (
        <AppLayout>
            <Head title={`Disposición Final #${disposicion.id}`} />
            
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center space-x-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('admin.disposiciones.index')}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Link>
                    </Button>
                    
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            Disposición Final #{disposicion.id}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Detalles de la propuesta de disposición final
                        </p>
                    </div>
                </div>

                <div className="flex items-center space-x-2">
                    {disposicion.estado === 'pendiente' && (
                        <>
                            <Button variant="outline" size="sm">
                                <Edit className="h-4 w-4 mr-2" />
                                Editar
                            </Button>
                            <Button variant="outline" size="sm">
                                Enviar a Revisión
                            </Button>
                        </>
                    )}
                    
                    {disposicion.estado === 'en_revision' && (
                        <>
                            <Button variant="outline" size="sm">
                                <XCircle className="h-4 w-4 mr-2" />
                                Rechazar
                            </Button>
                            <Button size="sm">
                                <CheckCircle className="h-4 w-4 mr-2" />
                                Aprobar
                            </Button>
                        </>
                    )}

                    {disposicion.estado === 'aprobado' && (
                        <Button size="sm">
                            <Archive className="h-4 w-4 mr-2" />
                            Ejecutar
                        </Button>
                    )}

                    <Button variant="outline" size="sm">
                        <Download className="h-4 w-4 mr-2" />
                        Exportar PDF
                    </Button>
                </div>
            </div>

            <div className="space-y-6">
                {/* Estado y alertas */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Badge variant="outline" className={`${estadoColors[disposicion.estado]} text-base py-2 px-4`}>
                            <div className="flex items-center space-x-2">
                                {estadoIcons[disposicion.estado]}
                                <span>{disposicion.estado_label}</span>
                            </div>
                        </Badge>

                        <Badge variant="outline" className={`${tipoColors[disposicion.tipo_disposicion]} text-base py-2 px-4`}>
                            {disposicion.tipo_disposicion_label}
                        </Badge>

                        {disposicion.esta_vencida && (
                            <Badge variant="outline" className="bg-red-100 text-red-800 border-red-200 text-base py-2 px-4">
                                <AlertTriangle className="h-4 w-4 mr-1" />
                                ¡Vencida!
                            </Badge>
                        )}
                    </div>

                    <div className="text-sm text-gray-500">
                        Creada: {formatearFecha(disposicion.created_at)}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Información principal */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Item afectado */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    {disposicion.expediente ? <Archive className="h-5 w-5 text-blue-500" /> : <FileText className="h-5 w-5 text-green-500" />}
                                    <span>Item Afectado</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {disposicion.expediente ? (
                                    <div className="space-y-4">
                                        <div>
                                            <h3 className="font-semibold text-lg">{disposicion.expediente.numero_expediente}</h3>
                                            <p className="text-gray-600 mt-1">{disposicion.expediente.titulo}</p>
                                        </div>
                                        
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span className="font-medium text-gray-500">Serie Documental:</span>
                                                <p>{disposicion.expediente.serie_documental}</p>
                                            </div>
                                            <div>
                                                <span className="font-medium text-gray-500">Estado del Expediente:</span>
                                                <p>{disposicion.expediente.estado_ciclo_vida}</p>
                                            </div>
                                            <div>
                                                <span className="font-medium text-gray-500">Ubicación Física:</span>
                                                <p className="flex items-center">
                                                    <MapPin className="h-4 w-4 mr-1 text-gray-400" />
                                                    {disposicion.expediente.ubicacion_fisica}
                                                </p>
                                            </div>
                                            <div>
                                                <span className="font-medium text-gray-500">Vencimiento de Retención:</span>
                                                <p className={`flex items-center ${disposicion.esta_vencida ? 'text-red-600 font-medium' : ''}`}>
                                                    <Calendar className="h-4 w-4 mr-1 text-gray-400" />
                                                    {formatearFechaCorta(disposicion.fecha_vencimiento_retencion)}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4 text-sm bg-gray-50 p-4 rounded-lg">
                                            <div>
                                                <span className="font-medium text-gray-500">Años en Archivo Central:</span>
                                                <p className="font-semibold">{disposicion.expediente.anos_retencion_archivo_central} años</p>
                                            </div>
                                            <div>
                                                <span className="font-medium text-gray-500">Años en Archivo Histórico:</span>
                                                <p className="font-semibold">{disposicion.expediente.anos_retencion_archivo_historico} años</p>
                                            </div>
                                        </div>

                                        <div className="flex justify-end">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('admin.expedientes.show', disposicion.expediente.id)}>
                                                    <Eye className="h-4 w-4 mr-2" />
                                                    Ver Expediente
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <div>
                                            <h3 className="font-semibold text-lg">{disposicion.documento?.nombre}</h3>
                                            <p className="text-gray-600 mt-1">
                                                Expediente: {disposicion.documento?.expediente.numero_expediente} - {disposicion.documento?.expediente.titulo}
                                            </p>
                                        </div>
                                        
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span className="font-medium text-gray-500">Serie Documental:</span>
                                                <p>{disposicion.documento?.expediente.serie_documental}</p>
                                            </div>
                                            <div>
                                                <span className="font-medium text-gray-500">Ubicación Física:</span>
                                                <p className="flex items-center">
                                                    <MapPin className="h-4 w-4 mr-1 text-gray-400" />
                                                    {disposicion.documento?.ubicacion_fisica}
                                                </p>
                                            </div>
                                            <div className="col-span-2">
                                                <span className="font-medium text-gray-500">Vencimiento de Retención:</span>
                                                <p className={`flex items-center ${disposicion.esta_vencida ? 'text-red-600 font-medium' : ''}`}>
                                                    <Calendar className="h-4 w-4 mr-1 text-gray-400" />
                                                    {formatearFechaCorta(disposicion.fecha_vencimiento_retencion)}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex justify-end space-x-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('admin.expedientes.show', disposicion.documento?.expediente.id)}>
                                                    <Archive className="h-4 w-4 mr-2" />
                                                    Ver Expediente
                                                </Link>
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('admin.documentos.show', disposicion.documento?.id)}>
                                                    <FileText className="h-4 w-4 mr-2" />
                                                    Ver Documento
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Justificación y observaciones */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Justificación y Observaciones</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <h4 className="font-medium text-gray-700 mb-2">Justificación</h4>
                                    <p className="text-gray-600 bg-gray-50 p-3 rounded-lg">
                                        {disposicion.justificacion}
                                    </p>
                                </div>

                                {disposicion.observaciones && (
                                    <div>
                                        <h4 className="font-medium text-gray-700 mb-2">Observaciones Generales</h4>
                                        <p className="text-gray-600 bg-gray-50 p-3 rounded-lg">
                                            {disposicion.observaciones}
                                        </p>
                                    </div>
                                )}

                                {disposicion.observaciones_aprobacion && (
                                    <div>
                                        <h4 className="font-medium text-green-700 mb-2">Observaciones de Aprobación</h4>
                                        <p className="text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
                                            {disposicion.observaciones_aprobacion}
                                        </p>
                                    </div>
                                )}

                                {disposicion.observaciones_rechazo && (
                                    <div>
                                        <h4 className="font-medium text-red-700 mb-2">Observaciones de Rechazo</h4>
                                        <p className="text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
                                            {disposicion.observaciones_rechazo}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Panel lateral */}
                    <div className="space-y-6">
                        {/* Información de fechas */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Calendar className="h-5 w-5" />
                                    <span>Cronología</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <span className="font-medium text-gray-500">Fecha de Propuesta:</span>
                                    <p className="text-sm">{formatearFechaCorta(disposicion.fecha_propuesta)}</p>
                                </div>

                                {disposicion.fecha_aprobacion && (
                                    <div>
                                        <span className="font-medium text-green-600">Fecha de Aprobación:</span>
                                        <p className="text-sm">{formatearFechaCorta(disposicion.fecha_aprobacion)}</p>
                                    </div>
                                )}

                                {disposicion.fecha_rechazo && (
                                    <div>
                                        <span className="font-medium text-red-600">Fecha de Rechazo:</span>
                                        <p className="text-sm">{formatearFechaCorta(disposicion.fecha_rechazo)}</p>
                                    </div>
                                )}

                                {disposicion.fecha_ejecucion && (
                                    <div>
                                        <span className="font-medium text-purple-600">Fecha de Ejecución:</span>
                                        <p className="text-sm">{formatearFechaCorta(disposicion.fecha_ejecucion)}</p>
                                    </div>
                                )}

                                <div>
                                    <span className="font-medium text-gray-500">Vencimiento de Retención:</span>
                                    <p className={`text-sm ${disposicion.esta_vencida ? 'text-red-600 font-medium' : ''}`}>
                                        {formatearFechaCorta(disposicion.fecha_vencimiento_retencion)}
                                    </p>
                                    {disposicion.dias_para_vencimiento <= 30 && disposicion.dias_para_vencimiento > 0 && (
                                        <p className="text-xs text-orange-600 mt-1">
                                            Vence en {disposicion.dias_para_vencimiento} días
                                        </p>
                                    )}
                                    {disposicion.esta_vencida && (
                                        <p className="text-xs text-red-600 font-medium mt-1">
                                            ¡Esta disposición está vencida!
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Información de usuarios */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <User className="h-5 w-5" />
                                    <span>Usuarios Involucrados</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <span className="font-medium text-gray-500">Responsable:</span>
                                    <div className="flex items-center space-x-2 mt-1">
                                        <User className="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p className="text-sm font-medium">{disposicion.responsable.name}</p>
                                            <p className="text-xs text-gray-500">{disposicion.responsable.email}</p>
                                        </div>
                                    </div>
                                </div>

                                {disposicion.aprobado_por && (
                                    <div>
                                        <span className="font-medium text-green-600">Aprobado por:</span>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <CheckCircle className="h-4 w-4 text-green-500" />
                                            <div>
                                                <p className="text-sm font-medium">{disposicion.aprobado_por.name}</p>
                                                <p className="text-xs text-gray-500">{disposicion.aprobado_por.email}</p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {disposicion.rechazado_por && (
                                    <div>
                                        <span className="font-medium text-red-600">Rechazado por:</span>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <XCircle className="h-4 w-4 text-red-500" />
                                            <div>
                                                <p className="text-sm font-medium">{disposicion.rechazado_por.name}</p>
                                                <p className="text-xs text-gray-500">{disposicion.rechazado_por.email}</p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Historial de cambios */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <History className="h-5 w-5" />
                                    <span>Historial</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="text-sm">
                                    <div className="flex items-center space-x-2">
                                        <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <span className="font-medium">Creada</span>
                                    </div>
                                    <p className="text-xs text-gray-500 ml-4">
                                        {formatearFecha(disposicion.created_at)}
                                    </p>
                                </div>

                                {disposicion.updated_at !== disposicion.created_at && (
                                    <div className="text-sm">
                                        <div className="flex items-center space-x-2">
                                            <div className="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                            <span className="font-medium">Última modificación</span>
                                        </div>
                                        <p className="text-xs text-gray-500 ml-4">
                                            {formatearFecha(disposicion.updated_at)}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
