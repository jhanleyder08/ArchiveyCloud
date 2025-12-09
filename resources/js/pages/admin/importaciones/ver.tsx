import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    ArrowLeft, 
    Download, 
    Play, 
    X, 
    Trash2, 
    FileText, 
    User, 
    Calendar, 
    Clock, 
    CheckCircle, 
    XCircle, 
    AlertTriangle, 
    Settings,
    Activity
} from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Badge } from '../../../components/ui/badge';
import { Progress } from '../../../components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';

interface ImportacionDatos {
    id: number;
    nombre: string;
    descripcion: string;
    tipo: string;
    formato_origen: string;
    estado: string;
    archivo_origen: string;
    configuracion: any;
    total_registros: number;
    registros_procesados: number;
    registros_exitosos: number;
    registros_fallidos: number;
    porcentaje_avance: number;
    fecha_inicio: string | null;
    fecha_finalizacion: string | null;
    tiempo_procesamiento: number | null;
    mensaje_error: string | null;
    created_at: string;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
    archivos_generados: Array<{
        tipo: string;
        nombre: string;
        archivo: string;
    }>;
}

interface Props {
    importacion: ImportacionDatos;
    archivosGenerados: Array<{ tipo: string; nombre: string; archivo: string }>;
    puedeEditar: boolean;
    puedeEliminar: boolean;
}

const tiposLabels = {
    expedientes: 'Expedientes',
    documentos: 'Documentos',
    series: 'Series Documentales',
    usuarios: 'Usuarios',
    certificados: 'Certificados'
};

const estadosConfig = {
    pendiente: { icon: Clock, color: 'bg-yellow-100 text-yellow-800', label: 'Pendiente' },
    procesando: { icon: Play, color: 'bg-blue-100 text-blue-800', label: 'Procesando' },
    completada: { icon: CheckCircle, color: 'bg-green-100 text-green-800', label: 'Completada' },
    fallida: { icon: XCircle, color: 'bg-red-100 text-red-800', label: 'Fallida' },
    cancelada: { icon: AlertTriangle, color: 'bg-gray-100 text-gray-800', label: 'Cancelada' }
};

export default function VerImportacion({ importacion, archivosGenerados, puedeEditar, puedeEliminar }: Props) {
    const [progreso, setProgreso] = useState(importacion);
    const [autoRefresh, setAutoRefresh] = useState(importacion.estado === 'procesando');

    useEffect(() => {
        if (!autoRefresh || importacion.estado !== 'procesando') return;

        const interval = setInterval(async () => {
            try {
                const response = await fetch(`/admin/importaciones/${importacion.id}/progreso`);
                const data = await response.json();
                setProgreso(prev => ({ ...prev, ...data }));
                
                if (data.estado !== 'procesando') {
                    setAutoRefresh(false);
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error obteniendo progreso:', error);
            }
        }, 5000);

        return () => clearInterval(interval);
    }, [autoRefresh, importacion.id, importacion.estado]);

    const procesarImportacion = () => {
        router.post(`/admin/importaciones/${importacion.id}/procesar`, {}, {
            onSuccess: () => setAutoRefresh(true)
        });
    };

    const cancelarImportacion = () => {
        if (confirm('¿Estás seguro de que deseas cancelar esta importación?')) {
            router.post(`/admin/importaciones/${importacion.id}/cancelar`, {}, {
                onSuccess: () => setAutoRefresh(false)
            });
        }
    };

    const eliminarImportacion = () => {
        if (confirm('¿Estás seguro de que deseas eliminar esta importación?')) {
            router.delete(`/admin/importaciones/${importacion.id}`, {
                onSuccess: () => router.visit('/admin/importaciones/listado')
            });
        }
    };

    const formatearTiempo = (segundos: number | null): string => {
        if (!segundos) return '-';
        if (segundos < 60) return `${segundos}s`;
        const min = Math.floor(segundos / 60);
        const seg = segundos % 60;
        return `${min}m ${seg}s`;
    };

    const formatearFecha = (fecha: string): string => {
        return new Date(fecha).toLocaleString();
    };

    const estadoConfig = estadosConfig[importacion.estado as keyof typeof estadosConfig];
    const IconoEstado = estadoConfig?.icon || Clock;

    return (
        <AppLayout>
            <Head title={`Importación: ${importacion.nombre}`} />
            
            <div className="container mx-auto p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/admin/importaciones/listado">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold">{importacion.nombre}</h1>
                            <p className="text-muted-foreground">{importacion.descripcion || 'Sin descripción'}</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        {autoRefresh && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Activity className="h-4 w-4 text-green-500 animate-pulse" />
                                Auto-actualizando...
                            </div>
                        )}
                        
                        {importacion.estado === 'pendiente' && puedeEditar && (
                            <Button onClick={procesarImportacion}>
                                <Play className="h-4 w-4 mr-2" />
                                Procesar
                            </Button>
                        )}

                        {(importacion.estado === 'pendiente' || importacion.estado === 'procesando') && puedeEditar && (
                            <Button variant="outline" onClick={cancelarImportacion}>
                                <X className="h-4 w-4 mr-2" />
                                Cancelar
                            </Button>
                        )}

                        {puedeEliminar && (
                            <Button variant="destructive" onClick={eliminarImportacion}>
                                <Trash2 className="h-4 w-4 mr-2" />
                                Eliminar
                            </Button>
                        )}
                    </div>
                </div>

                {/* Estado y progreso principal */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between mb-4">
                            <div className="flex items-center gap-3">
                                <IconoEstado className="h-8 w-8" />
                                <div>
                                    <Badge className={`${estadoConfig?.color} text-lg px-3 py-1`}>
                                        {estadoConfig?.label || importacion.estado}
                                    </Badge>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        {tiposLabels[importacion.tipo as keyof typeof tiposLabels]} • {importacion.formato_origen.toUpperCase()}
                                    </p>
                                </div>
                            </div>

                            <div className="text-right">
                                <div className="text-2xl font-bold">
                                    {progreso.registros_procesados} / {progreso.total_registros}
                                </div>
                                <div className="text-sm text-muted-foreground">registros procesados</div>
                            </div>
                        </div>

                        {importacion.estado === 'procesando' && (
                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-sm">
                                    <span>Progreso general</span>
                                    <span className="font-medium">{Number(progreso.porcentaje_avance || 0).toFixed(1)}%</span>
                                </div>
                                <Progress value={Number(progreso.porcentaje_avance || 0)} className="h-3" />
                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                    <span>Iniciado: {progreso.fecha_inicio ? formatearFecha(progreso.fecha_inicio) : '-'}</span>
                                    <span>Tiempo: {formatearTiempo(progreso.tiempo_procesamiento)}</span>
                                </div>
                            </div>
                        )}

                        {importacion.mensaje_error && (
                            <Alert variant="destructive" className="mt-4">
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription>
                                    <strong>Error:</strong> {importacion.mensaje_error}
                                </AlertDescription>
                            </Alert>
                        )}
                    </CardContent>
                </Card>

                <Tabs defaultValue="detalles" className="space-y-6">
                    <TabsList>
                        <TabsTrigger value="detalles">Detalles</TabsTrigger>
                        <TabsTrigger value="estadisticas">Estadísticas</TabsTrigger>
                        <TabsTrigger value="configuracion">Configuración</TabsTrigger>
                        <TabsTrigger value="archivos">Archivos</TabsTrigger>
                    </TabsList>

                    {/* Tab: Detalles */}
                    <TabsContent value="detalles">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="h-5 w-5" />
                                        Información General
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="font-medium">ID:</span>
                                            <p>{importacion.id}</p>
                                        </div>
                                        <div>
                                            <span className="font-medium">Tipo:</span>
                                            <p>{tiposLabels[importacion.tipo as keyof typeof tiposLabels]}</p>
                                        </div>
                                        <div>
                                            <span className="font-medium">Formato:</span>
                                            <p>{importacion.formato_origen.toUpperCase()}</p>
                                        </div>
                                        <div>
                                            <span className="font-medium">Total registros:</span>
                                            <p>{importacion.total_registros.toLocaleString()}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <User className="h-5 w-5" />
                                        Usuario y Fechas
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-3">
                                        <div>
                                            <span className="font-medium text-sm">Creado por:</span>
                                            <div className="flex items-center gap-2 mt-1">
                                                <User className="h-4 w-4 text-muted-foreground" />
                                                <div>
                                                    <p className="font-medium">{importacion.usuario?.name || 'Usuario no disponible'}</p>
                                                    <p className="text-xs text-muted-foreground">{importacion.usuario?.email || 'Email no disponible'}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <span className="font-medium text-sm">Fecha de creación:</span>
                                            <div className="flex items-center gap-2 mt-1">
                                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                                <p>{formatearFecha(importacion.created_at)}</p>
                                            </div>
                                        </div>

                                        {importacion.tiempo_procesamiento && (
                                            <div>
                                                <span className="font-medium text-sm">Tiempo de procesamiento:</span>
                                                <div className="flex items-center gap-2 mt-1">
                                                    <Clock className="h-4 w-4 text-muted-foreground" />
                                                    <p>{formatearTiempo(importacion.tiempo_procesamiento)}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Tab: Estadísticas */}
                    <TabsContent value="estadisticas">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                            <Card>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Total</p>
                                            <p className="text-2xl font-bold">{importacion.total_registros}</p>
                                        </div>
                                        <FileText className="h-8 w-8 text-blue-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Procesados</p>
                                            <p className="text-2xl font-bold text-blue-600">{progreso.registros_procesados}</p>
                                        </div>
                                        <Activity className="h-8 w-8 text-blue-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Exitosos</p>
                                            <p className="text-2xl font-bold text-green-600">{progreso.registros_exitosos}</p>
                                        </div>
                                        <CheckCircle className="h-8 w-8 text-green-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Fallidos</p>
                                            <p className="text-2xl font-bold text-red-600">{progreso.registros_fallidos}</p>
                                        </div>
                                        <XCircle className="h-8 w-8 text-red-600" />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {importacion.estado === 'completada' && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Resumen de Resultados</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-sm font-medium">Tasa de éxito:</p>
                                                <p className="text-2xl font-bold text-green-600">
                                                    {progreso.total_registros > 0 
                                                        ? ((progreso.registros_exitosos / progreso.total_registros) * 100).toFixed(1) 
                                                        : '0.0'}%
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium">Tasa de error:</p>
                                                <p className="text-2xl font-bold text-red-600">
                                                    {progreso.total_registros > 0 
                                                        ? ((progreso.registros_fallidos / progreso.total_registros) * 100).toFixed(1) 
                                                        : '0.0'}%
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <Progress 
                                            value={progreso.total_registros > 0 
                                                ? (progreso.registros_exitosos / progreso.total_registros) * 100 
                                                : 0} 
                                            className="h-4"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    {/* Tab: Configuración */}
                    <TabsContent value="configuracion">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Settings className="h-5 w-5" />
                                    Configuración de Importación
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {importacion.configuracion ? (
                                    <div className="space-y-6">
                                        {importacion.configuracion.mapeo && (
                                            <div>
                                                <h4 className="font-medium mb-3">Mapeo de Campos</h4>
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Campo del Sistema</TableHead>
                                                            <TableHead>Campo del Archivo</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {Object.entries(importacion.configuracion.mapeo).map(([sistema, archivo]) => (
                                                            <TableRow key={sistema}>
                                                                <TableCell className="font-medium">{sistema}</TableCell>
                                                                <TableCell>{archivo as string}</TableCell>
                                                            </TableRow>
                                                        ))}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground">No hay configuración específica disponible.</p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab: Archivos */}
                    <TabsContent value="archivos">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Download className="h-5 w-5" />
                                    Archivos de la Importación
                                </CardTitle>
                                <CardDescription>
                                    Descarga los archivos originales y generados durante el procesamiento
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {/* Archivo original */}
                                    <div className="flex items-center justify-between p-4 border rounded-lg">
                                        <div className="flex items-center gap-3">
                                            <FileText className="h-8 w-8 text-blue-600" />
                                            <div>
                                                <p className="font-medium">Archivo Original</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {importacion.archivo_origen} • {importacion.formato_origen.toUpperCase()}
                                                </p>
                                            </div>
                                        </div>
                                        <Button variant="outline" asChild>
                                            <a href={`/admin/importaciones/${importacion.id}/descargar/original`}>
                                                <Download className="h-4 w-4 mr-2" />
                                                Descargar
                                            </a>
                                        </Button>
                                    </div>

                                    {/* Archivos generados */}
                                    {archivosGenerados.map((archivo) => (
                                        <div key={archivo.tipo} className="flex items-center justify-between p-4 border rounded-lg">
                                            <div className="flex items-center gap-3">
                                                <FileText className="h-8 w-8 text-green-600" />
                                                <div>
                                                    <p className="font-medium">{archivo.nombre}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        Generado durante el procesamiento
                                                    </p>
                                                </div>
                                            </div>
                                            <Button variant="outline" asChild>
                                                <a href={`/admin/importaciones/${importacion.id}/descargar/${archivo.tipo}`}>
                                                    <Download className="h-4 w-4 mr-2" />
                                                    Descargar
                                                </a>
                                            </Button>
                                        </div>
                                    ))}

                                    {archivosGenerados.length === 0 && importacion.estado !== 'pendiente' && (
                                        <div className="text-center py-8 text-muted-foreground">
                                            <FileText className="h-12 w-12 mx-auto mb-3 opacity-50" />
                                            <p>No hay archivos generados disponibles</p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
