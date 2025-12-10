import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Upload, Key, Shield, Calendar, User, Cpu, Lock } from 'lucide-react';
import AppLayout from '../../../layouts/app/app-sidebar-layout';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Checkbox } from '../../../components/ui/checkbox';
import { Textarea } from '../../../components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Alert, AlertDescription } from '../../../components/ui/alert';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface TipoCertificado {
    value: string;
    label: string;
}

interface UsoPermitido {
    value: string;
    label: string;
}

interface AlgoritmoFirma {
    value: string;
    label: string;
}

interface Props {
    usuarios: Usuario[];
    tipos_certificado: TipoCertificado[];
    usos_permitidos: UsoPermitido[];
    algoritmos_firma: AlgoritmoFirma[];
}

export default function CertificadosCreate({ usuarios, tipos_certificado, usos_permitidos, algoritmos_firma }: Props) {
    const [metodoCreacion, setMetodoCreacion] = useState<'generar' | 'importar'>('generar');
    const [usosSeleccionados, setUsosSeleccionados] = useState<string[]>(['firma_digital']);

    const { data, setData, post, processing, errors, reset } = useForm({
        usuario_id: '',
        nombre_certificado: '',
        tipo_certificado: 'usuario',
        algoritmo_firma: 'RSA',
        longitud_clave: 2048,
        uso_permitido: ['firma_digital'],
        fecha_vencimiento: '',
        archivo_certificado: null as File | null,
        archivo_clave_publica: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Actualizar los usos seleccionados antes de enviar
        const submitData = {
            ...data,
            uso_permitido: usosSeleccionados
        };

        router.post(route('admin.certificados.store'), submitData, {
            forceFormData: true,
            onSuccess: () => {
                reset();
            }
        });
    };

    const handleUsoChange = (uso: string, checked: boolean) => {
        if (checked) {
            setUsosSeleccionados([...usosSeleccionados, uso]);
        } else {
            setUsosSeleccionados(usosSeleccionados.filter(u => u !== uso));
        }
    };

    const getFechaMinima = () => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return tomorrow.toISOString().split('T')[0];
    };

    const getFechaMaxima = () => {
        const maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() + 10);
        return maxDate.toISOString().split('T')[0];
    };

    const getTipoDescripcion = (tipo: string) => {
        const descripciones: Record<string, string> = {
            'usuario': 'Certificado para identificación y firma de usuarios individuales',
            'servidor': 'Certificado para servicios de servidor y aplicaciones',
            'autoridad_certificadora': 'Certificado de Autoridad Certificadora (CA)',
            'sello_tiempo': 'Certificado para servicios de sellado de tiempo'
        };
        return descripciones[tipo] || '';
    };

    return (
        <AppLayout>
            <Head title="Crear Certificado Digital" />
            
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
                            <h1 className="text-3xl font-bold tracking-tight">Crear Certificado Digital</h1>
                            <p className="text-muted-foreground">
                                Generar o importar un nuevo certificado digital PKI
                            </p>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Tabs value={metodoCreacion} onValueChange={(value) => setMetodoCreacion(value as 'generar' | 'importar')}>
                        <TabsList className="grid w-full grid-cols-2">
                            <TabsTrigger value="generar" className="flex items-center">
                                <Key className="h-4 w-4 mr-2" />
                                Generar Certificado
                            </TabsTrigger>
                            <TabsTrigger value="importar" className="flex items-center">
                                <Upload className="h-4 w-4 mr-2" />
                                Importar Certificado
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="generar" className="space-y-6">
                            <Alert>
                                <Shield className="h-4 w-4" />
                                <AlertDescription>
                                    Se generará automáticamente un nuevo par de claves RSA y el certificado correspondiente.
                                    El certificado será auto-firmado y válido para los usos seleccionados.
                                </AlertDescription>
                            </Alert>
                        </TabsContent>

                        <TabsContent value="importar" className="space-y-6">
                            <Alert>
                                <Upload className="h-4 w-4" />
                                <AlertDescription>
                                    Importe un certificado existente en formato PEM, DER o CRT.
                                    Opcionalmente puede proporcionar la clave pública correspondiente.
                                </AlertDescription>
                            </Alert>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Archivos del Certificado</CardTitle>
                                    <CardDescription>
                                        Seleccione los archivos del certificado a importar
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="archivo_certificado">
                                            Archivo de Certificado *
                                        </Label>
                                        <Input
                                            id="archivo_certificado"
                                            type="file"
                                            accept=".crt,.cer,.pem,.der"
                                            onChange={(e) => setData('archivo_certificado', e.target.files?.[0] || null)}
                                            className={errors.archivo_certificado ? 'border-red-500' : ''}
                                        />
                                        {errors.archivo_certificado && (
                                            <p className="text-sm text-red-600">{errors.archivo_certificado}</p>
                                        )}
                                        <p className="text-sm text-gray-600">
                                            Formatos soportados: .crt, .cer, .pem, .der (máximo 2MB)
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="archivo_clave_publica">
                                            Archivo de Clave Pública (opcional)
                                        </Label>
                                        <Input
                                            id="archivo_clave_publica"
                                            type="file"
                                            accept=".key,.pem"
                                            onChange={(e) => setData('archivo_clave_publica', e.target.files?.[0] || null)}
                                            className={errors.archivo_clave_publica ? 'border-red-500' : ''}
                                        />
                                        {errors.archivo_clave_publica && (
                                            <p className="text-sm text-red-600">{errors.archivo_clave_publica}</p>
                                        )}
                                        <p className="text-sm text-gray-600">
                                            Formatos soportados: .key, .pem (máximo 2MB)
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>

                    {/* Información Básica */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <User className="h-5 w-5 mr-2" />
                                Información Básica
                            </CardTitle>
                            <CardDescription>
                                Configure la información básica del certificado
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="usuario_id">Usuario Propietario *</Label>
                                    <Select 
                                        value={data.usuario_id} 
                                        onValueChange={(value) => setData('usuario_id', value)}
                                    >
                                        <SelectTrigger className={errors.usuario_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Seleccionar usuario" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {usuarios.map((usuario) => (
                                                <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                    {usuario.name} ({usuario.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.usuario_id && (
                                        <p className="text-sm text-red-600">{errors.usuario_id}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="nombre_certificado">Nombre del Certificado *</Label>
                                    <Input
                                        id="nombre_certificado"
                                        value={data.nombre_certificado}
                                        onChange={(e) => setData('nombre_certificado', e.target.value)}
                                        placeholder="Ej: Certificado Firma Digital Juan Pérez"
                                        className={errors.nombre_certificado ? 'border-red-500' : ''}
                                    />
                                    {errors.nombre_certificado && (
                                        <p className="text-sm text-red-600">{errors.nombre_certificado}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="tipo_certificado">Tipo de Certificado *</Label>
                                <Select 
                                    value={data.tipo_certificado} 
                                    onValueChange={(value) => setData('tipo_certificado', value)}
                                >
                                    <SelectTrigger className={errors.tipo_certificado ? 'border-red-500' : ''}>
                                        <SelectValue placeholder="Seleccionar tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {tipos_certificado.map((tipo) => (
                                            <SelectItem key={tipo.value} value={tipo.value}>
                                                {tipo.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {data.tipo_certificado && (
                                    <p className="text-sm text-gray-600">
                                        {getTipoDescripcion(data.tipo_certificado)}
                                    </p>
                                )}
                                {errors.tipo_certificado && (
                                    <p className="text-sm text-red-600">{errors.tipo_certificado}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="fecha_vencimiento">Fecha de Vencimiento *</Label>
                                <Input
                                    id="fecha_vencimiento"
                                    type="date"
                                    value={data.fecha_vencimiento}
                                    onChange={(e) => setData('fecha_vencimiento', e.target.value)}
                                    min={getFechaMinima()}
                                    max={getFechaMaxima()}
                                    className={errors.fecha_vencimiento ? 'border-red-500' : ''}
                                />
                                {errors.fecha_vencimiento && (
                                    <p className="text-sm text-red-600">{errors.fecha_vencimiento}</p>
                                )}
                                <p className="text-sm text-gray-600">
                                    El certificado será válido desde hoy hasta la fecha seleccionada (máximo 10 años)
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Configuración Criptográfica */}
                    {metodoCreacion === 'generar' && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Cpu className="h-5 w-5 mr-2" />
                                    Configuración Criptográfica
                                </CardTitle>
                                <CardDescription>
                                    Configure los parámetros criptográficos del certificado
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="algoritmo_firma">Algoritmo de Firma *</Label>
                                        <Select 
                                            value={data.algoritmo_firma} 
                                            onValueChange={(value) => setData('algoritmo_firma', value)}
                                        >
                                            <SelectTrigger className={errors.algoritmo_firma ? 'border-red-500' : ''}>
                                                <SelectValue placeholder="Seleccionar algoritmo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {algoritmos_firma.map((algoritmo) => (
                                                    <SelectItem key={algoritmo.value} value={algoritmo.value}>
                                                        {algoritmo.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.algoritmo_firma && (
                                            <p className="text-sm text-red-600">{errors.algoritmo_firma}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="longitud_clave">Longitud de Clave *</Label>
                                        <Select 
                                            value={data.longitud_clave.toString()} 
                                            onValueChange={(value) => setData('longitud_clave', parseInt(value))}
                                        >
                                            <SelectTrigger className={errors.longitud_clave ? 'border-red-500' : ''}>
                                                <SelectValue placeholder="Seleccionar longitud" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="1024">1024 bits (Básico)</SelectItem>
                                                <SelectItem value="2048">2048 bits (Recomendado)</SelectItem>
                                                <SelectItem value="4096">4096 bits (Alta Seguridad)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.longitud_clave && (
                                            <p className="text-sm text-red-600">{errors.longitud_clave}</p>
                                        )}
                                        <p className="text-sm text-gray-600">
                                            Mayor longitud = mayor seguridad pero menor velocidad
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Usos Permitidos */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Lock className="h-5 w-5 mr-2" />
                                Usos Permitidos
                            </CardTitle>
                            <CardDescription>
                                Seleccione los usos autorizados para este certificado
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {usos_permitidos.map((uso) => (
                                    <div key={uso.value} className="flex items-center space-x-3">
                                        <Checkbox
                                            id={`uso_${uso.value}`}
                                            checked={usosSeleccionados.includes(uso.value)}
                                            onCheckedChange={(checked) => handleUsoChange(uso.value, checked as boolean)}
                                        />
                                        <Label htmlFor={`uso_${uso.value}`} className="text-sm font-medium">
                                            {uso.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            {errors.uso_permitido && (
                                <p className="text-sm text-red-600 mt-2">{errors.uso_permitido}</p>
                            )}
                            {usosSeleccionados.length === 0 && (
                                <p className="text-sm text-orange-600 mt-2">
                                    Debe seleccionar al menos un uso permitido
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Botones de Acción */}
                    <div className="flex items-center justify-between">
                        <Link href={route('admin.certificados.index')}>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </Link>
                        <Button 
                            type="submit" 
                            disabled={processing || usosSeleccionados.length === 0}
                            className="min-w-[120px]"
                        >
                            {processing ? 'Procesando...' : (metodoCreacion === 'generar' ? 'Generar Certificado' : 'Importar Certificado')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
