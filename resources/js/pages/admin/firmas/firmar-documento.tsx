import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { ShieldCheck, FileText, AlertCircle, ArrowLeft } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

interface Props {
    documento: any;
    certificados: any[];
    firmas_existentes: any[];
    opciones_firma: any;
}

export default function FirmarDocumento({ documento, certificados, firmas_existentes, opciones_firma }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        certificado_id: '',
        tipo_firma: opciones_firma?.tipo_defecto || 'PADES',
        nivel_firma: opciones_firma?.nivel_defecto || 'T',
        algoritmo_hash: 'SHA-256',
        incluir_sellado_tiempo: true,
        razon_firma: 'Aprobación de documento',
        ubicacion_firma: 'Bogotá, D.C.',
        politica_firma: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.firmas.documento.procesar', { documento: documento.id }));
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Firmas Digitales', href: '/admin/firmas' },
            { title: 'Firmar Documento', href: '#' },
        ]}>
            <Head title={`Firmar - ${documento.codigo_documento}`} />

            <div className="container mx-auto py-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Firmar Documento</h1>
                        <p className="text-gray-500">Firma digital para {documento.codigo_documento}</p>
                    </div>
                    <Button variant="outline" onClick={() => window.history.back()}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Columna Izquierda - Información del Documento */}
                    <div className="md:col-span-1 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información del Documento</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label className="text-xs text-gray-500">Código</Label>
                                    <p className="font-medium">{documento.codigo_documento}</p>
                                </div>
                                <div>
                                    <Label className="text-xs text-gray-500">Título</Label>
                                    <p className="font-medium">{documento.titulo}</p>
                                </div>
                                <div>
                                    <Label className="text-xs text-gray-500">Versión</Label>
                                    <p className="font-medium">v{documento.version_mayor}.{documento.version_menor}</p>
                                </div>
                                <div>
                                    <Label className="text-xs text-gray-500">Estado</Label>
                                    <Badge variant={documento.estado_firma === 'firmado' ? 'default' : 'secondary'}>
                                        {documento.estado_firma === 'firmado' ? 'Firmado' : 'Pendiente de Firma'}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>

                        {firmas_existentes.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Firmas Existentes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-4">
                                        {firmas_existentes.map((firma: any) => (
                                            <li key={firma.id} className="border-b pb-2 last:border-0 last:pb-0">
                                                <div className="flex items-center gap-2">
                                                    <ShieldCheck className="h-4 w-4 text-green-600" />
                                                    <span className="font-medium text-sm">{firma.usuario?.name}</span>
                                                </div>
                                                <p className="text-xs text-gray-500 ml-6">
                                                    {new Date(firma.fecha_firma).toLocaleString()}
                                                </p>
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Columna Derecha - Formulario de Firma */}
                    <div className="md:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración de Firma</CardTitle>
                                <CardDescription>
                                    Seleccione su certificado y configure los parámetros de la firma digital.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {certificados.length === 0 ? (
                                        <Alert variant="destructive">
                                            <AlertCircle className="h-4 w-4" />
                                            <AlertTitle>Sin certificados</AlertTitle>
                                            <AlertDescription>
                                                No tiene certificados digitales válidos para firmar. Por favor, importe o solicite un certificado.
                                            </AlertDescription>
                                        </Alert>
                                    ) : (
                                        <>
                                            <div className="space-y-2">
                                                <Label htmlFor="certificado">Certificado Digital</Label>
                                                <Select
                                                    value={data.certificado_id}
                                                    onValueChange={(value) => setData('certificado_id', value)}
                                                >
                                                    <SelectTrigger className={errors.certificado_id ? 'border-red-500' : ''}>
                                                        <SelectValue placeholder="Seleccione un certificado..." />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {certificados.map((cert: any) => (
                                                            <SelectItem key={cert.id} value={cert.id.toString()}>
                                                                {cert.subject?.CN || cert.nombre_certificado || 'Certificado sin nombre'} 
                                                                <span className="text-xs text-gray-400 ml-2">
                                                                    (Vence: {new Date(cert.fecha_vencimiento).toLocaleDateString()})
                                                                </span>
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {errors.certificado_id && (
                                                    <p className="text-sm text-red-500">{errors.certificado_id}</p>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="tipo_firma">Tipo de Firma</Label>
                                                    <Select
                                                        value={data.tipo_firma}
                                                        onValueChange={(value) => setData('tipo_firma', value)}
                                                        disabled
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="PADES">PAdES (PDF)</SelectItem>
                                                            <SelectItem value="XADES">XAdES (XML)</SelectItem>
                                                            <SelectItem value="CADES">CAdES (Binario)</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="algoritmo">Algoritmo Hash</Label>
                                                    <Select
                                                        value={data.algoritmo_hash}
                                                        onValueChange={(value) => setData('algoritmo_hash', value)}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="SHA-256">SHA-256</SelectItem>
                                                            <SelectItem value="SHA-384">SHA-384</SelectItem>
                                                            <SelectItem value="SHA-512">SHA-512</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="razon">Razón de la Firma</Label>
                                                <Input
                                                    id="razon"
                                                    value={data.razon_firma}
                                                    onChange={(e) => setData('razon_firma', e.target.value)}
                                                    placeholder="Ej: Aprobación, Revisión, Autoría..."
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="ubicacion">Ubicación</Label>
                                                <Input
                                                    id="ubicacion"
                                                    value={data.ubicacion_firma}
                                                    onChange={(e) => setData('ubicacion_firma', e.target.value)}
                                                    placeholder="Ciudad, País"
                                                />
                                            </div>

                                            <div className="flex items-center space-x-2 pt-2">
                                                <Checkbox
                                                    id="timestamp"
                                                    checked={data.incluir_sellado_tiempo}
                                                    onCheckedChange={(checked) => setData('incluir_sellado_tiempo', checked as boolean)}
                                                />
                                                <Label htmlFor="timestamp" className="font-normal cursor-pointer">
                                                    Incluir sellado de tiempo (Timestamp)
                                                </Label>
                                            </div>

                                            <div className="pt-4 flex justify-end">
                                                <Button 
                                                    type="submit" 
                                                    disabled={processing || !data.certificado_id}
                                                    className="bg-[#2a3d83] hover:bg-[#1e2b5f]"
                                                >
                                                    {processing ? (
                                                        <>
                                                            <span className="animate-spin mr-2">⏳</span>
                                                            Procesando...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <ShieldCheck className="mr-2 h-4 w-4" />
                                                            Firmar Documento
                                                        </>
                                                    )}
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
