import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Download, Shield, AlertTriangle, CheckCircle, XCircle, Clock, Key, User, Calendar, Activity, FileText, RefreshCw } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Badge } from '../../../components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Label } from '../../../components/ui/label';
import { Textarea } from '../../../components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '../../../components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';

interface CertificadoDigital {
    id: number;
    nombre_certificado: string;
    numero_serie: string;
    emisor: string;
    sujeto: string;
    algoritmo_firma: string;
    longitud_clave: number;
    huella_digital: string;
    fecha_emision: string;
    fecha_vencimiento: string;
    estado: string;
    tipo_certificado: string;
    uso_permitido: string[];
    dias_restantes: number;
    vigente: boolean;
    proximo_a_vencer: boolean;
    razon_revocacion?: string;
    revocado_en?: string;
    info_sujeto?: Record<string, string> | null;
    info_emisor?: Record<string, string> | null;
    usuario: {
        id: number;
        name: string;
        email: string;
    };
    firmas: Array<{
        id: number;
        documento: {
            id: number;
            nombre: string;
        };
        fecha_firma: string;
        valida: boolean;
    }>;
}

interface EstadisticasUso {
    total_firmas: number;
    firmas_validas: number;
    primera_firma?: string;
    ultima_firma?: string;
    dias_desde_emision: number;
    porcentaje_vida_util: number;
}

interface Validez {
    valido: boolean;
    errores: string[];
    advertencias: string[];
}

interface HistorialAuditoria {
    id: number;
    accion: string;
    descripcion?: string;
    contexto_adicional?: Record<string, any> | null;
    created_at: string;
    usuario?: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    certificado: CertificadoDigital;
    estadisticas_uso: EstadisticasUso;
    validez: Validez;
    historial_auditoria: HistorialAuditoria[];
}

export default function CertificadosShow({ certificado, estadisticas_uso, validez, historial_auditoria }: Props) {
    const [showRevocarDialog, setShowRevocarDialog] = useState(false);
    const [showRenovarDialog, setShowRenovarDialog] = useState(false);

    const { data: revocarData, setData: setRevocarData, post: postRevocar, processing: processingRevocar, errors: errorsRevocar } = useForm({
        razon_revocacion: ''
    });

    const { data: renovarData, setData: setRenovarData, post: postRenovar, processing: processingRenovar, errors: errorsRenovar } = useForm({
        nueva_fecha_vencimiento: '',
        mantener_configuracion: true
    });

    const getBadgeVariant = (estado: string) => {
        switch (estado) {
            case 'activo':
                return 'default';
            case 'vencido':
                return 'destructive';
            case 'revocado':
                return 'secondary';
            case 'suspendido':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    const getEstadoIcon = () => {
        if (certificado.estado === 'revocado') {
            return <XCircle className="h-5 w-5 text-red-500" />;
        }
        if (!certificado.vigente) {
            return <AlertTriangle className="h-5 w-5 text-orange-500" />;
        }
        if (certificado.proximo_a_vencer) {
            return <Clock className="h-5 w-5 text-yellow-500" />;
        }
        return <CheckCircle className="h-5 w-5 text-green-500" />;
    };

    const getTipoLabel = (tipo: string) => {
        const tipos: Record<string, string> = {
            'usuario': 'Usuario',
            'servidor': 'Servidor',
            'autoridad_certificadora': 'Autoridad CA',
            'sello_tiempo': 'Sello de Tiempo'
        };
        return tipos[tipo] || tipo;
    };

    const getUsoLabel = (uso: string) => {
        const usos: Record<string, string> = {
            'firma_digital': 'Firma Digital',
            'autenticacion': 'Autenticación',
            'cifrado': 'Cifrado',
            'sello_tiempo': 'Sello de Tiempo'
        };
        return usos[uso] || uso;
    };

    const handleDescargar = (formato: string) => {
        const url = route('admin.certificados.descargar', { certificado: certificado.id, formato });
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', '');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    const handleRevocar = () => {
        postRevocar(route('admin.certificados.revocar', certificado.id), {
            onSuccess: () => {
                setShowRevocarDialog(false);
                router.reload();
            }
        });
    };

    const handleRenovar = () => {
        postRenovar(route('admin.certificados.renovar', certificado.id), {
            onSuccess: () => {
                setShowRenovarDialog(false);
            }
        });
    };

    const getFechaMinima = () => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return tomorrow.toISOString().split('T')[0];
    };

    return (
        <AppLayout>
            <Head title={`Certificado: ${certificado.nombre_certificado}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={route('admin.certificados.index')}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{certificado.nombre_certificado}</h1>
                            <p className="text-muted-foreground">
                                Serie: {certificado.numero_serie}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <Select onValueChange={handleDescargar}>
                            <SelectTrigger className="w-auto">
                                <Download className="h-4 w-4 mr-2" />
                                Descargar
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pem">Formato PEM</SelectItem>
                                <SelectItem value="der">Formato DER</SelectItem>
                                <SelectItem value="crt">Formato CRT</SelectItem>
                            </SelectContent>
                        </Select>
                        
                        {certificado.vigente && (
                            <>
                                <Dialog open={showRenovarDialog} onOpenChange={setShowRenovarDialog}>
                                    <DialogTrigger asChild>
                                        <Button variant="outline">
                                            <RefreshCw className="h-4 w-4 mr-2" />
                                            Renovar
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Renovar Certificado</DialogTitle>
                                            <DialogDescription>
                                                Se creará un nuevo certificado basado en la configuración actual.
                                                El certificado actual será marcado como vencido.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="nueva_fecha_vencimiento">Nueva Fecha de Vencimiento</Label>
                                                <input
                                                    id="nueva_fecha_vencimiento"
                                                    type="date"
                                                    value={renovarData.nueva_fecha_vencimiento}
                                                    onChange={(e) => setRenovarData('nueva_fecha_vencimiento', e.target.value)}
                                                    min={getFechaMinima()}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                />
                                                {errorsRenovar.nueva_fecha_vencimiento && (
                                                    <p className="text-sm text-red-600">{errorsRenovar.nueva_fecha_vencimiento}</p>
                                                )}
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setShowRenovarDialog(false)}>
                                                Cancelar
                                            </Button>
                                            <Button onClick={handleRenovar} disabled={processingRenovar}>
                                                {processingRenovar ? 'Renovando...' : 'Renovar Certificado'}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <Dialog open={showRevocarDialog} onOpenChange={setShowRevocarDialog}>
                                    <DialogTrigger asChild>
                                        <Button variant="destructive">
                                            <XCircle className="h-4 w-4 mr-2" />
                                            Revocar
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Revocar Certificado</DialogTitle>
                                            <DialogDescription>
                                                Esta acción no se puede deshacer. El certificado será marcado como revocado
                                                y todas las firmas realizadas con él serán inválidas.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="razon_revocacion">Razón de Revocación</Label>
                                                <Textarea
                                                    id="razon_revocacion"
                                                    value={revocarData.razon_revocacion}
                                                    onChange={(e) => setRevocarData('razon_revocacion', e.target.value)}
                                                    placeholder="Especifique la razón por la cual se revoca el certificado..."
                                                    rows={3}
                                                />
                                                {errorsRevocar.razon_revocacion && (
                                                    <p className="text-sm text-red-600">{errorsRevocar.razon_revocacion}</p>
                                                )}
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setShowRevocarDialog(false)}>
                                                Cancelar
                                            </Button>
                                            <Button variant="destructive" onClick={handleRevocar} disabled={processingRevocar}>
                                                {processingRevocar ? 'Revocando...' : 'Revocar Certificado'}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </>
                        )}
                    </div>
                </div>

                {/* Estado del Certificado */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-3">
                                {getEstadoIcon()}
                                <div>
                                    <div className="flex items-center space-x-2">
                                        <Badge variant={getBadgeVariant(certificado.estado)} className="capitalize">
                                            {certificado.estado}
                                        </Badge>
                                        <Badge variant="outline">
                                            {getTipoLabel(certificado.tipo_certificado)}
                                        </Badge>
                                    </div>
                                    <p className="text-sm text-gray-600 mt-1">
                                        Propietario: {certificado.usuario.name} ({certificado.usuario.email})
                                    </p>
                                </div>
                            </div>
                            <div className="text-right">
                                <p className="text-sm font-medium">
                                    Vence: {new Date(certificado.fecha_vencimiento).toLocaleDateString()}
                                </p>
                                {certificado.vigente && (
                                    <p className={`text-sm ${certificado.proximo_a_vencer ? 'text-orange-600' : 'text-gray-600'}`}>
                                        {certificado.dias_restantes} días restantes
                                    </p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Alertas de Validez */}
                {!validez.valido && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            <strong>Certificado inválido:</strong>
                            <ul className="list-disc list-inside mt-1">
                                {validez.errores.map((error, index) => (
                                    <li key={index}>{error}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                {validez.advertencias.length > 0 && (
                    <Alert>
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            <strong>Advertencias:</strong>
                            <ul className="list-disc list-inside mt-1">
                                {validez.advertencias.map((advertencia, index) => (
                                    <li key={index}>{advertencia}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <Tabs defaultValue="detalles" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="detalles">Detalles</TabsTrigger>
                        <TabsTrigger value="uso">Estadísticas de Uso</TabsTrigger>
                        <TabsTrigger value="firmas">Firmas Realizadas</TabsTrigger>
                        <TabsTrigger value="auditoria">Auditoría</TabsTrigger>
                    </TabsList>

                    <TabsContent value="detalles" className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Información del Certificado */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Key className="h-5 w-5 mr-2" />
                                        Información del Certificado
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Número de Serie</Label>
                                        <p className="font-mono text-sm break-all">{certificado.numero_serie}</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Algoritmo de Firma</Label>
                                        <p>{certificado.algoritmo_firma} ({certificado.longitud_clave} bits)</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Huella Digital (SHA256)</Label>
                                        <p className="font-mono text-sm break-all">{certificado.huella_digital}</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Fecha de Emisión</Label>
                                        <p>{new Date(certificado.fecha_emision).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Fecha de Vencimiento</Label>
                                        <p>{new Date(certificado.fecha_vencimiento).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Usos Permitidos</Label>
                                        <div className="flex flex-wrap gap-1 mt-1">
                                            {certificado.uso_permitido.map((uso) => (
                                                <Badge key={uso} variant="outline" className="text-xs">
                                                    {getUsoLabel(uso)}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Información del Sujeto y Emisor */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <User className="h-5 w-5 mr-2" />
                                        Sujeto y Emisor
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Sujeto (Subject)</Label>
                                        <p className="text-sm break-all">{certificado.sujeto}</p>
                                        {certificado.info_sujeto && Object.keys(certificado.info_sujeto).length > 0 && (
                                            <div className="mt-2 space-y-1">
                                                {Object.entries(certificado.info_sujeto).map(([key, value]) => (
                                                    <div key={key} className="text-xs">
                                                        <span className="font-medium">{key}:</span> {value}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Emisor (Issuer)</Label>
                                        <p className="text-sm break-all">{certificado.emisor}</p>
                                        {certificado.info_emisor && Object.keys(certificado.info_emisor).length > 0 && (
                                            <div className="mt-2 space-y-1">
                                                {Object.entries(certificado.info_emisor).map(([key, value]) => (
                                                    <div key={key} className="text-xs">
                                                        <span className="font-medium">{key}:</span> {value}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="uso" className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-center">
                                        <p className="text-2xl font-bold">{estadisticas_uso.total_firmas}</p>
                                        <p className="text-sm text-gray-600">Firmas Totales</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-center">
                                        <p className="text-2xl font-bold text-green-600">{estadisticas_uso.firmas_validas}</p>
                                        <p className="text-sm text-gray-600">Firmas Válidas</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-center">
                                        <p className="text-2xl font-bold">{estadisticas_uso.dias_desde_emision}</p>
                                        <p className="text-sm text-gray-600">Días de Uso</p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-center">
                                        <p className="text-2xl font-bold">{Math.round(estadisticas_uso.porcentaje_vida_util)}%</p>
                                        <p className="text-sm text-gray-600">Vida Útil Transcurrida</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {(estadisticas_uso.primera_firma || estadisticas_uso.ultima_firma) && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Historial de Uso</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {estadisticas_uso.primera_firma && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Primera Firma</Label>
                                            <p>{new Date(estadisticas_uso.primera_firma).toLocaleString()}</p>
                                        </div>
                                    )}
                                    {estadisticas_uso.ultima_firma && (
                                        <div>
                                            <Label className="text-sm font-medium text-gray-600">Última Firma</Label>
                                            <p>{new Date(estadisticas_uso.ultima_firma).toLocaleString()}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    <TabsContent value="firmas" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Firmas Realizadas ({certificado.firmas.length})</CardTitle>
                                <CardDescription>
                                    Documentos firmados con este certificado
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {certificado.firmas.length > 0 ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Documento</TableHead>
                                                <TableHead>Fecha</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Acciones</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {certificado.firmas.map((firma) => (
                                                <TableRow key={firma.id}>
                                                    <TableCell>
                                                        <div className="font-medium">{firma.documento.nombre}</div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {new Date(firma.fecha_firma).toLocaleString()}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={firma.valida ? 'default' : 'destructive'}>
                                                            {firma.valida ? 'Válida' : 'Inválida'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Link href={route('admin.documentos.show', firma.documento.id)}>
                                                            <Button variant="outline" size="sm">
                                                                Ver Documento
                                                            </Button>
                                                        </Link>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <div className="text-center py-8">
                                        <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <p className="text-gray-600">No se han realizado firmas con este certificado</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="auditoria" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Activity className="h-5 w-5 mr-2" />
                                    Historial de Auditoría
                                </CardTitle>
                                <CardDescription>
                                    Registro de todas las acciones realizadas sobre este certificado
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {historial_auditoria.length > 0 ? (
                                    <div className="space-y-4">
                                        {historial_auditoria.map((entrada) => (
                                            <div key={entrada.id} className="border-l-2 border-gray-200 pl-4 pb-4">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-2">
                                                        <Badge variant="outline" className="capitalize">
                                                            {entrada.accion}
                                                        </Badge>
                                                        <span className="text-sm text-gray-600">
                                                            por {entrada.usuario?.name || 'Sistema'}
                                                        </span>
                                                    </div>
                                                    <span className="text-xs text-gray-500">
                                                        {new Date(entrada.created_at).toLocaleString()}
                                                    </span>
                                                </div>
                                                {entrada.descripcion && (
                                                    <p className="mt-2 text-sm text-gray-700">{entrada.descripcion}</p>
                                                )}
                                                {entrada.contexto_adicional && Object.keys(entrada.contexto_adicional).length > 0 && (
                                                    <div className="mt-2 text-sm text-gray-600">
                                                        {Object.entries(entrada.contexto_adicional).map(([key, value]) => (
                                                            <div key={key}>
                                                                <span className="font-medium">{key}:</span> {String(value)}
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <Activity className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <p className="text-gray-600">No hay registros de auditoría disponibles</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
