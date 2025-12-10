import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, User, Globe, Monitor, Clock, Shield, AlertTriangle, Activity, FileText } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Badge } from '../../../components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Alert, AlertDescription } from '../../../components/ui/alert';

interface PistaAuditoria {
    id: number;
    fecha_hora: string;
    usuario_id: number;
    accion: string;
    modelo?: string;
    descripcion: string;
    resultado: 'exitoso' | 'fallido' | 'bloqueado';
    modulo?: string;
    ip_address: string;
    pais: string;
    dispositivo: string;
    navegador: string;
    user_agent: string;
    hash_integridad: string;
    valores_anteriores?: Record<string, any>;
    valores_nuevos?: Record<string, any>;
    contexto_adicional?: Record<string, any>;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
}

interface EventoRelacionado {
    id: number;
    fecha_hora: string;
    accion: string;
    descripcion: string;
    resultado: string;
    usuario: {
        id: number;
        name: string;
    };
}

interface AnalisisEvento {
    nivel_riesgo: string;
    categoria: string;
    contexto_geografico: {
        pais: string;
        ciudad: string;
        ip: string;
    };
    contexto_tecnico: {
        dispositivo: string;
        navegador: string;
        user_agent: string;
    };
    contexto_temporal: {
        horario: string;
        dia_semana: string;
        es_horario_laboral: boolean;
    };
    eventos_similares: number;
    recomendaciones: string[];
}

interface Props {
    evento: PistaAuditoria;
    eventos_relacionados: EventoRelacionado[];
    analisis: AnalisisEvento;
}

export default function AuditoriaShow({ evento, eventos_relacionados, analisis }: Props) {
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
                return <AlertTriangle className="h-5 w-5 text-red-500" />;
            case 'fallido':
                return <AlertTriangle className="h-5 w-5 text-orange-500" />;
            case 'exitoso':
                return <Shield className="h-5 w-5 text-green-500" />;
            default:
                return <Shield className="h-5 w-5 text-gray-500" />;
        }
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    };

    const formatearJSON = (obj: any) => {
        if (!obj) return 'N/A';
        if (typeof obj === 'string') return obj;
        return JSON.stringify(obj, null, 2);
    };

    return (
        <AppLayout>
            <Head title={`Evento de Auditoría #${evento.id}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={route('admin.auditoria.index')}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver a Auditoría
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Evento de Auditoría #{evento.id}
                            </h1>
                            <p className="text-muted-foreground">
                                {formatearFecha(evento.fecha_hora)}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        {getRiskIcon(evento.resultado)}
                        <Badge variant={getBadgeVariant(evento.resultado)} className="text-base px-3 py-1">
                            {evento.resultado.toUpperCase()}
                        </Badge>
                    </div>
                </div>

                {/* Alertas de Riesgo */}
                {(evento.resultado === 'bloqueado' || evento.resultado === 'fallido') && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            <strong>¡Evento Crítico!</strong> Este evento requiere investigación inmediata 
                            debido a su alto nivel de riesgo para la seguridad del sistema.
                        </AlertDescription>
                    </Alert>
                )}

                {!analisis.contexto_temporal.es_horario_laboral && (
                    <Alert>
                        <Clock className="h-4 w-4" />
                        <AlertDescription>
                            <strong>Horario Inusual:</strong> Este evento ocurrió fuera del horario laboral 
                            ({analisis.contexto_temporal.horario} del {analisis.contexto_temporal.dia_semana}).
                        </AlertDescription>
                    </Alert>
                )}

                {/* Información Principal */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Activity className="h-5 w-5" />
                            <span>Información del Evento</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div className="space-y-3">
                                <h4 className="font-medium text-gray-900">Acción Realizada</h4>
                                <p className="text-lg font-semibold">{evento.accion.replace('_', ' ')}</p>
                                <p className="text-gray-600">{evento.descripcion}</p>
                            </div>

                            <div className="space-y-3">
                                <h4 className="font-medium text-gray-900">Usuario</h4>
                                <div className="flex items-center space-x-2">
                                    <User className="h-4 w-4 text-gray-500" />
                                    <div>
                                        <p className="font-medium">{evento.usuario?.name || 'Sistema'}</p>
                                        <p className="text-sm text-gray-500">{evento.usuario?.email}</p>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <h4 className="font-medium text-gray-900">Modelo Afectado</h4>
                                <div className="flex items-center space-x-2">
                                    <FileText className="h-4 w-4 text-gray-500" />
                                    <span className="font-medium">{evento.modelo || 'N/A'}</span>
                                </div>
                                <p className="text-sm text-gray-600">Categoría: {analisis.categoria}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Tabs defaultValue="contexto" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="contexto">Contexto</TabsTrigger>
                        <TabsTrigger value="tecnico">Información Técnica</TabsTrigger>
                        <TabsTrigger value="relacionados">Eventos Relacionados</TabsTrigger>
                        <TabsTrigger value="analisis">Análisis</TabsTrigger>
                    </TabsList>

                    <TabsContent value="contexto" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Contexto Geográfico */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Globe className="h-5 w-5" />
                                        <span>Contexto Geográfico</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Dirección IP:</span>
                                        <span className="font-mono">{analisis.contexto_geografico.ip}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">País:</span>
                                        <span className="font-medium">{analisis.contexto_geografico.pais}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Ciudad:</span>
                                        <span className="font-medium">{analisis.contexto_geografico.ciudad}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Contexto Temporal */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Clock className="h-5 w-5" />
                                        <span>Contexto Temporal</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Fecha y Hora:</span>
                                        <span className="font-medium">{formatearFecha(evento.fecha_hora)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Día de la Semana:</span>
                                        <span className="font-medium">{analisis.contexto_temporal.dia_semana}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Horario Laboral:</span>
                                        <Badge variant={analisis.contexto_temporal.es_horario_laboral ? 'secondary' : 'destructive'}>
                                            {analisis.contexto_temporal.es_horario_laboral ? 'Sí' : 'No'}
                                        </Badge>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Eventos Similares:</span>
                                        <span className="font-medium">{analisis.eventos_similares}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="tecnico" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Información del Dispositivo */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Monitor className="h-5 w-5" />
                                        <span>Información del Dispositivo</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Tipo de Dispositivo:</span>
                                        <span className="font-medium">{analisis.contexto_tecnico.dispositivo}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Navegador:</span>
                                        <span className="font-medium">{analisis.contexto_tecnico.navegador}</span>
                                    </div>
                                    <div className="mt-3">
                                        <span className="text-gray-600 block mb-2">User Agent:</span>
                                        <div className="bg-gray-50 p-3 rounded text-xs font-mono break-all">
                                            {analisis.contexto_tecnico.user_agent}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Datos Técnicos */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Shield className="h-5 w-5" />
                                        <span>Datos Técnicos</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Hash de Integridad:</span>
                                        <span className="font-mono text-xs">{evento.hash_integridad?.substring(0, 16)}...</span>
                                    </div>
                                    
                                    {evento.contexto_adicional && (
                                        <div>
                                            <span className="text-gray-600 block mb-2">Contexto Adicional:</span>
                                            <div className="bg-gray-50 p-3 rounded">
                                                <pre className="text-xs overflow-x-auto">
                                                    {formatearJSON(evento.contexto_adicional)}
                                                </pre>
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Detalles del Evento */}
                        {evento.detalles && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Detalles del Evento</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="bg-gray-50 p-4 rounded">
                                        <pre className="text-sm overflow-x-auto">
                                            {formatearJSON(evento.detalles)}
                                        </pre>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Metadatos de Cambios */}
                        {evento.metadatos_cambios && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Metadatos de Cambios</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="bg-gray-50 p-4 rounded">
                                        <pre className="text-sm overflow-x-auto">
                                            {formatearJSON(evento.metadatos_cambios)}
                                        </pre>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    <TabsContent value="relacionados" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Eventos Relacionados</CardTitle>
                                <CardDescription>
                                    Eventos del mismo usuario en un período cercano (±2 horas)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {eventos_relacionados.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Fecha/Hora</TableHead>
                                                    <TableHead>Acción</TableHead>
                                                    <TableHead>Descripción</TableHead>
                                                    <TableHead>Riesgo</TableHead>
                                                    <TableHead>Acciones</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {eventos_relacionados.map((eventoRel) => (
                                                    <TableRow key={eventoRel.id}>
                                                        <TableCell>
                                                            <div className="text-sm">
                                                                <div>{new Date(eventoRel.fecha_hora).toLocaleDateString()}</div>
                                                                <div className="text-gray-500">
                                                                    {new Date(eventoRel.fecha_hora).toLocaleTimeString()}
                                                                </div>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="font-medium">
                                                            {eventoRel.accion.replace('_', ' ')}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="max-w-xs truncate" title={eventoRel.descripcion}>
                                                                {eventoRel.descripcion}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge variant={getBadgeVariant(eventoRel.nivel_riesgo)}>
                                                                {eventoRel.nivel_riesgo}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Link href={route('admin.auditoria.show', eventoRel.id)}>
                                                                <Button variant="outline" size="sm">
                                                                    Ver
                                                                </Button>
                                                            </Link>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <Activity className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">No hay eventos relacionados</h3>
                                        <p className="text-gray-600">
                                            No se encontraron otros eventos del mismo usuario en el período cercano.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="analisis" className="space-y-4">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Evaluación de Riesgo */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <AlertTriangle className="h-5 w-5" />
                                        <span>Evaluación de Riesgo</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-gray-600">Nivel de Riesgo:</span>
                                            <Badge variant={getBadgeVariant(analisis.nivel_riesgo)} className="text-base">
                                                {analisis.nivel_riesgo?.toUpperCase() || 'BAJO'}
                                            </Badge>
                                        </div>

                                        <div className="flex items-center justify-between">
                                            <span className="text-gray-600">Categoría:</span>
                                            <span className="font-medium">{analisis.categoria}</span>
                                        </div>

                                        <div className="flex items-center justify-between">
                                            <span className="text-gray-600">Eventos Similares (24h):</span>
                                            <Badge variant="outline">{analisis.eventos_similares}</Badge>
                                        </div>

                                        <div className="flex items-center justify-between">
                                            <span className="text-gray-600">Horario Laboral:</span>
                                            <Badge variant={analisis.contexto_temporal.es_horario_laboral ? 'secondary' : 'destructive'}>
                                                {analisis.contexto_temporal.es_horario_laboral ? 'Sí' : 'No'}
                                            </Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Recomendaciones */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Shield className="h-5 w-5" />
                                        <span>Recomendaciones</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {analisis.recomendaciones.length > 0 ? (
                                        <div className="space-y-3">
                                            {analisis.recomendaciones.map((recomendacion, index) => (
                                                <Alert key={index}>
                                                    <AlertDescription>{recomendacion}</AlertDescription>
                                                </Alert>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-8">
                                            <Shield className="h-12 w-12 text-green-400 mx-auto mb-4" />
                                            <h3 className="text-lg font-medium text-gray-900 mb-2">Sin Recomendaciones</h3>
                                            <p className="text-gray-600">
                                                Este evento no requiere acciones adicionales en este momento.
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
