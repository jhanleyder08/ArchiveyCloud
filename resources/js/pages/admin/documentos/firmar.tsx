import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    FileText, 
    Shield, 
    User, 
    Calendar, 
    CheckCircle, 
    AlertTriangle,
    Download,
    ArrowLeft
} from 'lucide-react';

interface Documento {
    id: number;
    nombre: string;
    codigo: string;
    descripcion: string;
    tipo_documental: string;
    formato: string;
    tamaño: number;
    expediente?: {
        codigo: string;
        titulo: string;
    };
    firmado_digitalmente: boolean;
    estado_firma: string;
    total_firmas: number;
    fecha_creacion: string;
}

interface FirmaExistente {
    id: number;
    usuario: string;
    fecha_firma: string;
    motivo: string;
    tipo_firma: string;
    valida: boolean;
    errores: string[];
}

interface Props {
    documento: Documento;
    firmasExistentes: FirmaExistente[];
    puedeFiremar: boolean;
}

export default function FirmarDocumento({ documento, firmasExistentes, puedeFiremar }: Props) {
    const [showConfirmation, setShowConfirmation] = useState(false);
    
    const { data, setData, post, processing, errors } = useForm({
        motivo: '',
        confirmacion: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.confirmacion) {
            setShowConfirmation(true);
            return;
        }
        
        post(route('admin.firmas.firmar', documento.id), {
            onSuccess: () => {
                // Redirigido automáticamente al documento
            }
        });
    };

    const getEstadoFirmaBadge = (estado: string) => {
        const badges = {
            'sin_firmar': <Badge variant="outline" className="text-gray-600">Sin Firmar</Badge>,
            'firmado': <Badge className="bg-green-100 text-green-800">Firmado</Badge>,
            'firma_invalida': <Badge variant="destructive">Firma Inválida</Badge>
        };
        return badges[estado as keyof typeof badges] || <Badge variant="outline">{estado}</Badge>;
    };

    return (
        <AppLayout>
            <Head title={`Firmar Documento - ${documento.nombre}`} />
            
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
                    <span className="text-gray-900">Firmar Digitalmente</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <Shield className="w-8 h-8 text-blue-600" />
                            Firma Digital
                        </h1>
                        <p className="text-gray-600 mt-1">
                            Firmar digitalmente el documento para garantizar su autenticidad e integridad
                        </p>
                    </div>
                    <Link href={`/admin/documentos/${documento.id}`}>
                        <Button variant="outline">
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Volver al Documento
                        </Button>
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Información del Documento */}
                    <div className="lg:col-span-2">
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="w-5 h-5" />
                                    Información del Documento
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Nombre</Label>
                                        <p className="font-medium">{documento.nombre}</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Código</Label>
                                        <p className="font-mono text-sm">{documento.codigo}</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Tipo Documental</Label>
                                        <p>{documento.tipo_documental}</p>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Formato</Label>
                                        <p>{documento.formato}</p>
                                    </div>
                                    {documento.expediente && (
                                        <>
                                            <div>
                                                <Label className="text-sm font-medium text-gray-600">Expediente</Label>
                                                <p>{documento.expediente.codigo}</p>
                                            </div>
                                            <div>
                                                <Label className="text-sm font-medium text-gray-600">Título Expediente</Label>
                                                <p className="text-sm">{documento.expediente.titulo}</p>
                                            </div>
                                        </>
                                    )}
                                </div>
                                
                                {documento.descripcion && (
                                    <div>
                                        <Label className="text-sm font-medium text-gray-600">Descripción</Label>
                                        <p className="text-sm text-gray-700">{documento.descripcion}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Estado Actual de Firmas */}
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CheckCircle className="w-5 h-5" />
                                    Estado de Firma Digital
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-blue-600">{documento.total_firmas}</div>
                                        <div className="text-sm text-gray-600">Total Firmas</div>
                                    </div>
                                    <div className="text-center">
                                        {getEstadoFirmaBadge(documento.estado_firma)}
                                        <div className="text-sm text-gray-600 mt-1">Estado</div>
                                    </div>
                                    <div className="text-center">
                                        {documento.firmado_digitalmente ? (
                                            <CheckCircle className="w-8 h-8 text-green-600 mx-auto" />
                                        ) : (
                                            <AlertTriangle className="w-8 h-8 text-gray-400 mx-auto" />
                                        )}
                                        <div className="text-sm text-gray-600 mt-1">
                                            {documento.firmado_digitalmente ? 'Firmado' : 'Sin Firmar'}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Firmas Existentes */}
                        {firmasExistentes.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Firmas Existentes</CardTitle>
                                    <CardDescription>
                                        Listado de firmas digitales aplicadas a este documento
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {firmasExistentes.map((firma) => (
                                            <div key={firma.id} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex items-center gap-3">
                                                    <User className="w-5 h-5 text-gray-500" />
                                                    <div>
                                                        <div className="font-medium">{firma.usuario}</div>
                                                        <div className="text-sm text-gray-600 flex items-center gap-2">
                                                            <Calendar className="w-4 h-4" />
                                                            {new Date(firma.fecha_firma).toLocaleString()}
                                                        </div>
                                                        {firma.motivo && (
                                                            <div className="text-sm text-gray-500 mt-1">
                                                                {firma.motivo}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    {firma.valida ? (
                                                        <Badge className="bg-green-100 text-green-800">Válida</Badge>
                                                    ) : (
                                                        <Badge variant="destructive">Inválida</Badge>
                                                    )}
                                                    <div className="text-xs text-gray-500 mt-1">
                                                        {firma.tipo_firma}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Formulario de Firma */}
                    <div>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Shield className="w-5 h-5" />
                                    Firmar Documento
                                </CardTitle>
                                <CardDescription>
                                    Complete la información para aplicar su firma digital
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {!puedeFiremar ? (
                                    <Alert>
                                        <AlertTriangle className="h-4 w-4" />
                                        <AlertDescription>
                                            No tiene permisos suficientes para firmar este documento o ya lo ha firmado previamente.
                                        </AlertDescription>
                                    </Alert>
                                ) : (
                                    <form onSubmit={handleSubmit} className="space-y-4">
                                        <div>
                                            <Label htmlFor="motivo">
                                                Motivo de la Firma *
                                            </Label>
                                            <Textarea
                                                id="motivo"
                                                placeholder="Describa el motivo o propósito de su firma digital..."
                                                value={data.motivo}
                                                onChange={(e) => setData('motivo', e.target.value)}
                                                className="mt-1"
                                                rows={4}
                                            />
                                            {errors.motivo && (
                                                <p className="text-red-600 text-sm mt-1">{errors.motivo}</p>
                                            )}
                                        </div>

                                        <div className="space-y-3">
                                            <div className="flex items-start space-x-2">
                                                <Checkbox
                                                    id="confirmacion"
                                                    checked={data.confirmacion}
                                                    onCheckedChange={(checked) => setData('confirmacion', !!checked)}
                                                />
                                                <Label htmlFor="confirmacion" className="text-sm leading-relaxed">
                                                    Confirmo que he revisado el documento y deseo aplicar mi firma digital. 
                                                    Entiendo que esta acción garantiza la autenticidad e integridad del documento.
                                                </Label>
                                            </div>
                                            {errors.confirmacion && (
                                                <p className="text-red-600 text-sm">{errors.confirmacion}</p>
                                            )}
                                        </div>

                                        {showConfirmation && !data.confirmacion && (
                                            <Alert>
                                                <AlertTriangle className="h-4 w-4" />
                                                <AlertDescription>
                                                    Debe confirmar que ha revisado el documento antes de firmar.
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        {errors.error && (
                                            <Alert variant="destructive">
                                                <AlertTriangle className="h-4 w-4" />
                                                <AlertDescription>{errors.error}</AlertDescription>
                                            </Alert>
                                        )}

                                        <Button 
                                            type="submit" 
                                            className="w-full bg-blue-600 hover:bg-blue-700"
                                            disabled={processing || !data.motivo.trim()}
                                        >
                                            {processing ? (
                                                <>
                                                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                                                    Firmando...
                                                </>
                                            ) : (
                                                <>
                                                    <Shield className="w-4 h-4 mr-2" />
                                                    Firmar Digitalmente
                                                </>
                                            )}
                                        </Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>

                        {/* Información Adicional */}
                        <Card className="mt-6">
                            <CardHeader>
                                <CardTitle className="text-sm">Información sobre Firma Digital</CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm text-gray-600">
                                <ul className="space-y-2">
                                    <li>• La firma digital garantiza la autenticidad del documento</li>
                                    <li>• Se registra la fecha, hora y usuario que firmó</li>
                                    <li>• Se genera un hash único del documento</li>
                                    <li>• La firma se puede verificar posteriormente</li>
                                    <li>• Las firmas son permanentes e inmutables</li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
