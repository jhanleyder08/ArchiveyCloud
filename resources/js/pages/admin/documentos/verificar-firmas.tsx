import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    Shield, 
    CheckCircle, 
    XCircle,
    AlertTriangle,
    User,
    Calendar,
    Download,
    RefreshCw,
    ArrowLeft,
    FileText,
    Clock
} from 'lucide-react';

interface Documento {
    id: number;
    nombre: string;
    codigo: string;
    expediente?: {
        numero_expediente: string;
        titulo: string;
    };
    firmado_digitalmente: boolean;
    estado_firma: string;
    total_firmas: number;
}

interface Firma {
    id: number;
    usuario: string;
    fecha_firma: string;
    motivo: string;
    tipo_firma: string;
    valida: boolean;
    errores: string[];
    detalles: {
        firmado_por?: string;
        fecha_firma?: string;
        algoritmo?: string;
        tipo_firma?: string;
        motivo?: string;
    };
    vigente: boolean;
}

interface Props {
    documento: Documento;
    firmas: Firma[];
    todasValidas: boolean;
    totalFirmas: number;
}

export default function VerificarFirmas({ documento, firmas, todasValidas, totalFirmas }: Props) {
    const [verificandoFirmas, setVerificandoFirmas] = useState(false);

    const verificarTodasLasFirmas = async () => {
        setVerificandoFirmas(true);
        try {
            // Simular verificación - en implementación real haría llamadas API
            await new Promise(resolve => setTimeout(resolve, 2000));
            window.location.reload();
        } catch (error) {
            console.error('Error verificando firmas:', error);
        } finally {
            setVerificandoFirmas(false);
        }
    };

    const descargarCertificado = () => {
        window.location.href = route('admin.firmas.certificado', documento.id);
    };

    const getEstadoFirmaBadge = (estado: string) => {
        const badges = {
            'sin_firmar': <Badge variant="outline" className="text-gray-600">Sin Firmar</Badge>,
            'firmado': <Badge className="bg-green-100 text-green-800">Firmado</Badge>,
            'firma_invalida': <Badge variant="destructive">Firma Inválida</Badge>
        };
        return badges[estado as keyof typeof badges] || <Badge variant="outline">{estado}</Badge>;
    };

    const getIconoValidez = (valida: boolean) => {
        return valida ? (
            <CheckCircle className="w-5 h-5 text-green-600" />
        ) : (
            <XCircle className="w-5 h-5 text-red-600" />
        );
    };

    return (
        <AppLayout>
            <Head title={`Verificar Firmas - ${documento.nombre}`} />
            
            <div className="container mx-auto py-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600 mb-6">
                    <Link href="/admin/documentos" className="hover:text-blue-600">
                        Documentos
                    </Link>
                    <span>/</span>
                    <Link href={`/admin/documentos/${documento.id}`} className="hover:text-blue-600">
                        {documento.codigo}
                    </Link>
                    <span>/</span>
                    <span className="text-gray-900">Verificar Firmas</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <Shield className="w-8 h-8 text-blue-600" />
                            Verificación de Firmas Digitales
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Estado y validez de las firmas digitales aplicadas al documento
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Button
                            variant="outline"
                            onClick={verificarTodasLasFirmas}
                            disabled={verificandoFirmas}
                        >
                            {verificandoFirmas ? (
                                <>
                                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                    Verificando...
                                </>
                            ) : (
                                <>
                                    <RefreshCw className="w-4 h-4 mr-2" />
                                    Verificar Nuevamente
                                </>
                            )}
                        </Button>
                        <Link href={`/admin/documentos/${documento.id}`}>
                            <Button variant="outline">
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Volver al Documento
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Resumen del Estado */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <Card>
                        <CardContent className="p-6 text-center">
                            <FileText className="w-8 h-8 mx-auto text-blue-600 mb-2" />
                            <div className="text-2xl font-bold">{totalFirmas}</div>
                            <div className="text-sm text-gray-600">Total Firmas</div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent className="p-6 text-center">
                            {todasValidas ? (
                                <CheckCircle className="w-8 h-8 mx-auto text-green-600 mb-2" />
                            ) : (
                                <XCircle className="w-8 h-8 mx-auto text-red-600 mb-2" />
                            )}
                            <div className="text-sm font-medium">
                                {todasValidas ? 'Todas Válidas' : 'Algunas Inválidas'}
                            </div>
                            <div className="text-sm text-gray-600">Estado Global</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6 text-center">
                            {getEstadoFirmaBadge(documento.estado_firma)}
                            <div className="text-sm text-gray-600 mt-2">Estado Documento</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6 text-center">
                            <Button
                                onClick={descargarCertificado}
                                variant="outline"
                                size="sm"
                                disabled={totalFirmas === 0}
                            >
                                <Download className="w-4 h-4 mr-2" />
                                Certificado
                            </Button>
                            <div className="text-sm text-gray-600 mt-2">Descargar</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Información del Documento */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="w-5 h-5" />
                            Información del Documento
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <div className="text-sm font-medium text-gray-600">Nombre</div>
                                <div className="font-medium">{documento.nombre}</div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-gray-600">Código</div>
                                <div className="font-mono text-sm">{documento.codigo}</div>
                            </div>
                            {documento.expediente && (
                                <div>
                                    <div className="text-sm font-medium text-gray-600">Expediente</div>
                                    <div className="text-sm">{documento.expediente.numero_expediente}</div>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Listado de Firmas */}
                {firmas.length > 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Firmas Digitales Aplicadas</CardTitle>
                            <CardDescription>
                                Detalle de cada firma digital y su estado de verificación
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-6">
                                {firmas.map((firma, index) => (
                                    <div key={firma.id} className="border rounded-lg p-4">
                                        <div className="flex items-start justify-between mb-4">
                                            <div className="flex items-start gap-3">
                                                {getIconoValidez(firma.valida)}
                                                <div>
                                                    <div className="font-medium text-lg">
                                                        Firma #{index + 1}
                                                    </div>
                                                    <div className="flex items-center gap-2 text-sm text-gray-600">
                                                        <User className="w-4 h-4" />
                                                        {firma.usuario}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                {firma.valida ? (
                                                    <Badge className="bg-green-100 text-green-800">
                                                        Válida
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="destructive">
                                                        Inválida
                                                    </Badge>
                                                )}
                                                {firma.vigente !== undefined && (
                                                    <div className="text-xs text-gray-500 mt-1">
                                                        {firma.vigente ? 'Vigente' : 'Expirada'}
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <div className="text-sm font-medium text-gray-600 flex items-center gap-1">
                                                    <Calendar className="w-4 h-4" />
                                                    Fecha de Firma
                                                </div>
                                                <div className="text-sm">
                                                    {new Date(firma.fecha_firma).toLocaleString()}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-sm font-medium text-gray-600">Tipo de Firma</div>
                                                <div className="text-sm capitalize">{firma.tipo_firma}</div>
                                            </div>
                                        </div>

                                        {firma.motivo && (
                                            <div className="mb-4">
                                                <div className="text-sm font-medium text-gray-600">Motivo</div>
                                                <div className="text-sm text-gray-700">{firma.motivo}</div>
                                            </div>
                                        )}

                                        {/* Detalles técnicos */}
                                        {firma.detalles && Object.keys(firma.detalles).length > 0 && (
                                            <div className="bg-gray-50 rounded p-3 mb-4">
                                                <div className="text-sm font-medium text-gray-600 mb-2">
                                                    Detalles Técnicos
                                                </div>
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                                                    {firma.detalles.algoritmo && (
                                                        <div>
                                                            <span className="font-medium">Algoritmo:</span> {firma.detalles.algoritmo}
                                                        </div>
                                                    )}
                                                    {firma.detalles.tipo_firma && (
                                                        <div>
                                                            <span className="font-medium">Tipo:</span> {firma.detalles.tipo_firma}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        )}

                                        {/* Errores de validación */}
                                        {!firma.valida && firma.errores.length > 0 && (
                                            <Alert variant="destructive">
                                                <AlertTriangle className="h-4 w-4" />
                                                <AlertDescription>
                                                    <div className="font-medium mb-1">Errores de Validación:</div>
                                                    <ul className="text-sm space-y-1">
                                                        {firma.errores.map((error, errorIndex) => (
                                                            <li key={errorIndex}>• {error}</li>
                                                        ))}
                                                    </ul>
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-12 text-center">
                            <Shield className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                Sin Firmas Digitales
                            </h3>
                            <p className="text-gray-600 mb-6">
                                Este documento aún no tiene firmas digitales aplicadas.
                            </p>
                            <Link href={`/admin/documentos/${documento.id}/firmar`}>
                                <Button>
                                    <Shield className="w-4 h-4 mr-2" />
                                    Firmar Documento
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                )}

                {/* Información sobre verificación */}
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle className="text-sm flex items-center gap-2">
                            <Clock className="w-4 h-4" />
                            Información sobre Verificación
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm text-gray-600">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 className="font-medium text-gray-900 mb-2">Proceso de Verificación</h4>
                                <ul className="space-y-1">
                                    <li>• Se verifica la integridad del documento</li>
                                    <li>• Se valida el hash de cada firma</li>
                                    <li>• Se confirma la identidad del firmante</li>
                                    <li>• Se revisa la vigencia temporal</li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-medium text-gray-900 mb-2">Estados Posibles</h4>
                                <ul className="space-y-1">
                                    <li>• <strong>Válida:</strong> Firma correcta y vigente</li>
                                    <li>• <strong>Inválida:</strong> Firma comprometida o alterada</li>
                                    <li>• <strong>Expirada:</strong> Firma fuera del período de validez</li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
