import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Shield, CheckCircle, XCircle, AlertTriangle, FileText, Key, User, Calendar, Info } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Badge } from '../../../components/ui/badge';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';

interface Documento {
    id: number;
    nombre: string;
    hash_integridad?: string;
    expediente?: {
        id: number;
        codigo: string;
        nombre: string;
    };
}

interface FirmaDigital {
    id: number;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
    fecha_firma: string;
    hash_firma: string;
    algoritmo_hash: string;
    es_valida: boolean;
    comentario?: string;
    certificado?: {
        id: number;
        nombre_certificado: string;
        numero_serie: string;
        fecha_vencimiento: string;
        emisor: string;
    };
}

interface VerificacionResultado {
    documento_integro: boolean;
    total_firmas: number;
    firmas_validas: number;
    firmas_invalidas: number;
    estado_general: 'valido' | 'invalido' | 'parcial';
    detalles_verificacion: {
        hash_original?: string;
        hash_actual?: string;
        integridad_documento: boolean;
        certificados_validos: number;
        certificados_vencidos: number;
        cadena_confianza: boolean;
    };
    firmas: FirmaDigital[];
    alertas?: string[];
    recomendaciones?: string[];
}

interface Props {
    documento: Documento;
    verificacion: VerificacionResultado;
}

export default function VerificarFirmas({ documento, verificacion }: Props) {
    const [mostrarDetallesTecnicos, setMostrarDetallesTecnicos] = useState(false);

    const getEstadoIcon = (estado: string) => {
        switch (estado) {
            case 'valido':
                return <CheckCircle className="h-6 w-6 text-green-500" />;
            case 'invalido':
                return <XCircle className="h-6 w-6 text-red-500" />;
            case 'parcial':
                return <AlertTriangle className="h-6 w-6 text-yellow-500" />;
            default:
                return <AlertTriangle className="h-6 w-6 text-gray-500" />;
        }
    };

    const getEstadoBadge = (estado: string) => {
        switch (estado) {
            case 'valido':
                return <Badge className="bg-green-100 text-green-800">✓ Válido</Badge>;
            case 'invalido':
                return <Badge className="bg-red-100 text-red-800">✗ Inválido</Badge>;
            case 'parcial':
                return <Badge className="bg-yellow-100 text-yellow-800">⚠ Parcial</Badge>;
            default:
                return <Badge className="bg-gray-100 text-gray-800">? Desconocido</Badge>;
        }
    };

    const getFirmaIcon = (esValida: boolean) => {
        return esValida 
            ? <CheckCircle className="h-4 w-4 text-green-500" />
            : <XCircle className="h-4 w-4 text-red-500" />;
    };

    const formatearFecha = (fecha: string) => {
        return new Date(fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    };

    const esVencido = (fechaVencimiento: string) => {
        return new Date(fechaVencimiento) < new Date();
    };

    return (
        <AppLayout>
            <Head title={`Verificar Firmas - ${documento.nombre}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={route('admin.firmas.dashboard')}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight flex items-center">
                                <Shield className="h-8 w-8 mr-3 text-blue-600" />
                                Verificación de Firmas Digitales
                            </h1>
                            <p className="text-muted-foreground">
                                Validación de autenticidad e integridad de firmas digitales
                            </p>
                        </div>
                    </div>
                </div>

                {/* Resultado General */}
                <Card className="border-2">
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            <div className="flex items-center space-x-3">
                                {getEstadoIcon(verificacion.estado_general)}
                                <span>Estado General de Verificación</span>
                            </div>
                            {getEstadoBadge(verificacion.estado_general)}
                        </CardTitle>
                        <CardDescription>
                            Resultado de la verificación integral del documento y sus firmas digitales
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="text-center">
                                <div className="text-3xl font-bold text-blue-600">
                                    {verificacion.total_firmas}
                                </div>
                                <div className="text-sm text-gray-500">Total de Firmas</div>
                            </div>
                            <div className="text-center">
                                <div className="text-3xl font-bold text-green-600">
                                    {verificacion.firmas_validas}
                                </div>
                                <div className="text-sm text-gray-500">Firmas Válidas</div>
                            </div>
                            <div className="text-center">
                                <div className="text-3xl font-bold text-red-600">
                                    {verificacion.firmas_invalidas}
                                </div>
                                <div className="text-sm text-gray-500">Firmas Inválidas</div>
                            </div>
                        </div>

                        {/* Información del Documento */}
                        <div className="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 className="font-medium flex items-center mb-3">
                                <FileText className="h-4 w-4 mr-2" />
                                Documento Verificado
                            </h4>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="font-medium">Nombre:</span> {documento.nombre}
                                </div>
                                {documento.expediente && (
                                    <div>
                                        <span className="font-medium">Expediente:</span> {documento.expediente.codigo} - {documento.expediente.nombre}
                                    </div>
                                )}
                                <div>
                                    <span className="font-medium">Integridad:</span>
                                    <Badge className={verificacion.documento_integro ? 'bg-green-100 text-green-800 ml-2' : 'bg-red-100 text-red-800 ml-2'}>
                                        {verificacion.documento_integro ? '✓ Íntegro' : '✗ Alterado'}
                                    </Badge>
                                </div>
                                {documento.hash_integridad && (
                                    <div>
                                        <span className="font-medium">Hash:</span>
                                        <span className="font-mono text-xs ml-2">
                                            {documento.hash_integridad.substring(0, 16)}...
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Alertas y Recomendaciones */}
                {(verificacion.alertas?.length > 0 || verificacion.recomendaciones?.length > 0) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {verificacion.alertas?.length > 0 && (
                            <Alert variant="destructive">
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription>
                                    <strong>Alertas Detectadas:</strong>
                                    <ul className="mt-2 list-disc list-inside">
                                        {verificacion.alertas.map((alerta, index) => (
                                            <li key={index}>{alerta}</li>
                                        ))}
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        )}

                        {verificacion.recomendaciones?.length > 0 && (
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    <strong>Recomendaciones:</strong>
                                    <ul className="mt-2 list-disc list-inside">
                                        {verificacion.recomendaciones.map((recomendacion, index) => (
                                            <li key={index}>{recomendacion}</li>
                                        ))}
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        )}
                    </div>
                )}

                {/* Lista de Firmas */}
                <Card>
                    <CardHeader>
                        <CardTitle>Firmas Digitales Detectadas</CardTitle>
                        <CardDescription>
                            Listado detallado de todas las firmas aplicadas al documento
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {verificacion.firmas.length > 0 ? (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Estado</TableHead>
                                            <TableHead>Firmante</TableHead>
                                            <TableHead>Fecha de Firma</TableHead>
                                            <TableHead>Certificado</TableHead>
                                            <TableHead>Hash</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {verificacion.firmas.map((firma) => (
                                            <TableRow key={firma.id}>
                                                <TableCell>
                                                    <div className="flex items-center space-x-2">
                                                        {getFirmaIcon(firma.es_valida)}
                                                        <Badge variant={firma.es_valida ? 'secondary' : 'destructive'}>
                                                            {firma.es_valida ? 'Válida' : 'Inválida'}
                                                        </Badge>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center space-x-2">
                                                        <User className="h-4 w-4 text-gray-500" />
                                                        <div>
                                                            <div className="font-medium">{firma.usuario.name}</div>
                                                            <div className="text-sm text-gray-500">{firma.usuario.email}</div>
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center space-x-2">
                                                        <Calendar className="h-4 w-4 text-gray-500" />
                                                        <span>{formatearFecha(firma.fecha_firma)}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {firma.certificado ? (
                                                        <div className="flex items-center space-x-2">
                                                            <Key className="h-4 w-4 text-gray-500" />
                                                            <div>
                                                                <div className="font-medium">{firma.certificado.nombre_certificado}</div>
                                                                <div className="text-xs text-gray-500">
                                                                    Serie: {firma.certificado.numero_serie}
                                                                </div>
                                                                <div className="text-xs">
                                                                    <Badge variant={esVencido(firma.certificado.fecha_vencimiento) ? 'destructive' : 'outline'}>
                                                                        Vence: {new Date(firma.certificado.fecha_vencimiento).toLocaleDateString()}
                                                                    </Badge>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-gray-500">Sin certificado</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-mono text-xs">
                                                        <div>{firma.algoritmo_hash}</div>
                                                        <div>{firma.hash_firma.substring(0, 16)}...</div>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <Shield className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">No hay firmas digitales</h3>
                                <p className="text-gray-600">
                                    Este documento no tiene firmas digitales aplicadas.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Detalles Técnicos */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            Detalles Técnicos de Verificación
                            <Button 
                                variant="outline" 
                                size="sm"
                                onClick={() => setMostrarDetallesTecnicos(!mostrarDetallesTecnicos)}
                            >
                                {mostrarDetallesTecnicos ? 'Ocultar' : 'Mostrar'} Detalles
                            </Button>
                        </CardTitle>
                    </CardHeader>
                    {mostrarDetallesTecnicos && (
                        <CardContent>
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-3">
                                        <h4 className="font-medium">Verificación de Integridad</h4>
                                        <div className="text-sm space-y-2">
                                            <div>
                                                <span className="font-medium">Estado:</span>
                                                <Badge className={verificacion.detalles_verificacion.integridad_documento ? 'bg-green-100 text-green-800 ml-2' : 'bg-red-100 text-red-800 ml-2'}>
                                                    {verificacion.detalles_verificacion.integridad_documento ? 'Íntegro' : 'Alterado'}
                                                </Badge>
                                            </div>
                                            {verificacion.detalles_verificacion.hash_original && (
                                                <div>
                                                    <span className="font-medium">Hash Original:</span>
                                                    <div className="font-mono text-xs break-all">
                                                        {verificacion.detalles_verificacion.hash_original}
                                                    </div>
                                                </div>
                                            )}
                                            {verificacion.detalles_verificacion.hash_actual && (
                                                <div>
                                                    <span className="font-medium">Hash Actual:</span>
                                                    <div className="font-mono text-xs break-all">
                                                        {verificacion.detalles_verificacion.hash_actual}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-3">
                                        <h4 className="font-medium">Verificación de Certificados</h4>
                                        <div className="text-sm space-y-2">
                                            <div>
                                                <span className="font-medium">Certificados Válidos:</span>
                                                <span className="ml-2 text-green-600">{verificacion.detalles_verificacion.certificados_validos}</span>
                                            </div>
                                            <div>
                                                <span className="font-medium">Certificados Vencidos:</span>
                                                <span className="ml-2 text-red-600">{verificacion.detalles_verificacion.certificados_vencidos}</span>
                                            </div>
                                            <div>
                                                <span className="font-medium">Cadena de Confianza:</span>
                                                <Badge className={verificacion.detalles_verificacion.cadena_confianza ? 'bg-green-100 text-green-800 ml-2' : 'bg-red-100 text-red-800 ml-2'}>
                                                    {verificacion.detalles_verificacion.cadena_confianza ? 'Válida' : 'Inválida'}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    )}
                </Card>

                {/* Acciones */}
                <Card>
                    <CardHeader>
                        <CardTitle>Acciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex space-x-4">
                            <Button onClick={() => window.print()}>
                                Imprimir Reporte
                            </Button>
                            <Button variant="outline">
                                Exportar Verificación
                            </Button>
                            <Link href={route('admin.documentos.show', documento.id)}>
                                <Button variant="outline">
                                    Ver Documento
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
