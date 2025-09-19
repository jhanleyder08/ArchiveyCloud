import React, { useState, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Save, Search, Archive, FileText, User, Calendar, CheckCircle } from 'lucide-react';

interface Expediente {
    id: number;
    numero_expediente: string;
    titulo: string;
    estado_ciclo_vida: string;
    ubicacion_fisica: string;
    fecha_vencimiento_retencion: string;
    serie_documental: string;
    disposicion_final_recomendada: string;
}

interface Documento {
    id: number;
    nombre: string;
    expediente_id: number;
    expediente: {
        id: number;
        numero_expediente: string;
        titulo: string;
    };
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface Props {
    expedientes: Expediente[];
    documentos: Documento[];
    usuarios: Usuario[];
    errors: Record<string, string>;
}

const tipoDisposicionOptions = [
    { value: 'conservacion_permanente', label: 'Conservación Permanente' },
    { value: 'eliminacion_controlada', label: 'Eliminación Controlada' },
    { value: 'transferencia_historica', label: 'Transferencia Histórica' },
    { value: 'digitalizacion', label: 'Digitalización' },
    { value: 'microfilmacion', label: 'Microfilmación' },
];

export default function DisposicionesCreate({ expedientes, documentos, usuarios, errors }: Props) {
    const [busquedaExpediente, setBusquedaExpediente] = useState('');
    const [busquedaDocumento, setBusquedaDocumento] = useState('');
    const [busquedaUsuario, setBusquedaUsuario] = useState('');
    
    const [expedientesFiltrados, setExpedientesFiltrados] = useState<Expediente[]>([]);
    const [documentosFiltrados, setDocumentosFiltrados] = useState<Documento[]>([]);
    const [usuariosFiltrados, setUsuariosFiltrados] = useState<Usuario[]>([]);

    const { data, setData, post, processing, isDirty } = useForm({
        tipo_item: 'expediente' as 'expediente' | 'documento',
        expediente_id: '',
        documento_id: '',
        tipo_disposicion: '',
        justificacion: '',
        observaciones: '',
        fecha_propuesta: '',
        responsable_id: '',
    });

    // Filtrar expedientes
    useEffect(() => {
        if (busquedaExpediente) {
            const filtrados = expedientes.filter(exp => 
                exp.numero_expediente.toLowerCase().includes(busquedaExpediente.toLowerCase()) ||
                exp.titulo.toLowerCase().includes(busquedaExpediente.toLowerCase())
            ).slice(0, 10);
            setExpedientesFiltrados(filtrados);
        } else {
            setExpedientesFiltrados(expedientes.slice(0, 10));
        }
    }, [busquedaExpediente, expedientes]);

    // Filtrar documentos
    useEffect(() => {
        if (busquedaDocumento) {
            const filtrados = documentos.filter(doc => 
                doc.nombre.toLowerCase().includes(busquedaDocumento.toLowerCase()) ||
                doc.expediente.numero_expediente.toLowerCase().includes(busquedaDocumento.toLowerCase())
            ).slice(0, 10);
            setDocumentosFiltrados(filtrados);
        } else {
            setDocumentosFiltrados(documentos.slice(0, 10));
        }
    }, [busquedaDocumento, documentos]);

    // Filtrar usuarios
    useEffect(() => {
        if (busquedaUsuario) {
            const filtrados = usuarios.filter(usuario => 
                usuario.name.toLowerCase().includes(busquedaUsuario.toLowerCase()) ||
                usuario.email.toLowerCase().includes(busquedaUsuario.toLowerCase())
            ).slice(0, 10);
            setUsuariosFiltrados(filtrados);
        } else {
            setUsuariosFiltrados(usuarios.slice(0, 10));
        }
    }, [busquedaUsuario, usuarios]);

    const expedienteSeleccionado = expedientes.find(exp => exp.id.toString() === data.expediente_id);
    const documentoSeleccionado = documentos.find(doc => doc.id.toString() === data.documento_id);
    const responsableSeleccionado = usuarios.find(user => user.id.toString() === data.responsable_id);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.disposiciones.store'));
    };

    const fechaMinima = new Date().toISOString().split('T')[0];

    return (
        <AppLayout>
            <Head title="Nueva Disposición Final" />
            
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
                            Nueva Disposición Final
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Crear una nueva propuesta de disposición final documental
                        </p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Tipo de Item */}
                <Card>
                    <CardHeader>
                        <CardTitle>Tipo de Item</CardTitle>
                        <CardDescription>Selecciona si la disposición se aplicará a un expediente o documento</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button
                                type="button"
                                onClick={() => setData('tipo_item', 'expediente')}
                                className={`p-4 border rounded-lg text-left transition-colors ${
                                    data.tipo_item === 'expediente'
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:border-primary/50'
                                }`}
                            >
                                <div className="flex items-center space-x-3">
                                    <Archive className={`h-6 w-6 ${data.tipo_item === 'expediente' ? 'text-primary' : 'text-muted-foreground'}`} />
                                    <div>
                                        <h3 className="font-medium">Expediente Completo</h3>
                                        <p className="text-sm text-muted-foreground">Aplicar a expediente completo</p>
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                onClick={() => setData('tipo_item', 'documento')}
                                className={`p-4 border rounded-lg text-left transition-colors ${
                                    data.tipo_item === 'documento'
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:border-primary/50'
                                }`}
                            >
                                <div className="flex items-center space-x-3">
                                    <FileText className={`h-6 w-6 ${data.tipo_item === 'documento' ? 'text-primary' : 'text-muted-foreground'}`} />
                                    <div>
                                        <h3 className="font-medium">Documento Individual</h3>
                                        <p className="text-sm text-muted-foreground">Aplicar a documento específico</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </CardContent>
                </Card>

                {/* Selección del item */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            {data.tipo_item === 'expediente' ? <Archive className="h-5 w-5" /> : <FileText className="h-5 w-5" />}
                            <span>Seleccionar {data.tipo_item === 'expediente' ? 'Expediente' : 'Documento'}</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {data.tipo_item === 'expediente' ? (
                            <>
                                <div className="space-y-2">
                                    <Label>Buscar Expediente</Label>
                                    <div className="relative">
                                        <Search className="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            placeholder="Buscar por código o nombre..."
                                            value={busquedaExpediente}
                                            onChange={(e) => setBusquedaExpediente(e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2 max-h-64 overflow-y-auto">
                                    {expedientesFiltrados.map((expediente) => (
                                        <div
                                            key={expediente.id}
                                            className={`p-3 border rounded-lg cursor-pointer transition-colors ${
                                                data.expediente_id === expediente.id.toString()
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border hover:border-primary/50'
                                            }`}
                                            onClick={() => setData('expediente_id', expediente.id.toString())}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex-1">
                                                    <h4 className="font-medium">{expediente.numero_expediente}</h4>
                                                    <p className="text-sm text-muted-foreground">{expediente.titulo}</p>
                                                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                                                        <span>Serie: {expediente.serie_documental}</span>
                                                        <span>Estado: {expediente.estado_ciclo_vida}</span>
                                                    </div>
                                                </div>
                                                {data.expediente_id === expediente.id.toString() && (
                                                    <CheckCircle className="h-5 w-5 text-primary" />
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {errors.expediente_id && (
                                    <p className="text-sm text-red-500">{errors.expediente_id}</p>
                                )}
                            </>
                        ) : (
                            <>
                                <div className="space-y-2">
                                    <Label>Buscar Documento</Label>
                                    <div className="relative">
                                        <Search className="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            placeholder="Buscar por nombre..."
                                            value={busquedaDocumento}
                                            onChange={(e) => setBusquedaDocumento(e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2 max-h-64 overflow-y-auto">
                                    {documentosFiltrados.map((documento) => (
                                        <div
                                            key={documento.id}
                                            className={`p-3 border rounded-lg cursor-pointer transition-colors ${
                                                data.documento_id === documento.id.toString()
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border hover:border-primary/50'
                                            }`}
                                            onClick={() => setData('documento_id', documento.id.toString())}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex-1">
                                                    <h4 className="font-medium">{documento.nombre}</h4>
                                                    <p className="text-sm text-muted-foreground">
                                                        Expediente: {documento.expediente.numero_expediente}
                                                    </p>
                                                </div>
                                                {data.documento_id === documento.id.toString() && (
                                                    <CheckCircle className="h-5 w-5 text-primary" />
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {errors.documento_id && (
                                    <p className="text-sm text-red-500">{errors.documento_id}</p>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>

                {/* Tipo de Disposición */}
                <Card>
                    <CardHeader>
                        <CardTitle>Tipo de Disposición Final</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {tipoDisposicionOptions.map((opcion) => (
                                <div
                                    key={opcion.value}
                                    className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                                        data.tipo_disposicion === opcion.value
                                            ? 'border-primary bg-primary/5'
                                            : 'border-border hover:border-primary/50'
                                    }`}
                                    onClick={() => setData('tipo_disposicion', opcion.value)}
                                >
                                    <div className="flex items-center space-x-3">
                                        <div className={`w-4 h-4 rounded-full border-2 ${
                                            data.tipo_disposicion === opcion.value 
                                                ? 'bg-primary border-primary' 
                                                : 'border-gray-300'
                                        }`}></div>
                                        <span className="font-medium">{opcion.label}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                        {errors.tipo_disposicion && (
                            <p className="text-sm text-red-500 mt-2">{errors.tipo_disposicion}</p>
                        )}
                    </CardContent>
                </Card>

                {/* Información de la Disposición */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <User className="h-5 w-5" />
                            <span>Información de la Disposición</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Responsable */}
                        <div className="space-y-2">
                            <Label>Responsable *</Label>
                            <div className="relative">
                                <Search className="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Buscar usuario..."
                                    value={busquedaUsuario}
                                    onChange={(e) => setBusquedaUsuario(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            
                            {busquedaUsuario && (
                                <div className="space-y-1 max-h-40 overflow-y-auto border rounded p-2">
                                    {usuariosFiltrados.map((usuario) => (
                                        <div
                                            key={usuario.id}
                                            className={`p-2 rounded cursor-pointer transition-colors ${
                                                data.responsable_id === usuario.id.toString()
                                                    ? 'bg-primary/10 border border-primary'
                                                    : 'hover:bg-muted'
                                            }`}
                                            onClick={() => {
                                                setData('responsable_id', usuario.id.toString());
                                                setBusquedaUsuario(usuario.name);
                                            }}
                                        >
                                            <p className="font-medium">{usuario.name}</p>
                                            <p className="text-sm text-muted-foreground">{usuario.email}</p>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {responsableSeleccionado && (
                                <Alert>
                                    <CheckCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        <strong>Responsable:</strong> {responsableSeleccionado.name}
                                    </AlertDescription>
                                </Alert>
                            )}
                            
                            {errors.responsable_id && (
                                <p className="text-sm text-red-500">{errors.responsable_id}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label className="flex items-center space-x-2">
                                    <Calendar className="h-4 w-4" />
                                    <span>Fecha de Propuesta *</span>
                                </Label>
                                <Input
                                    type="date"
                                    min={fechaMinima}
                                    value={data.fecha_propuesta}
                                    onChange={(e) => setData('fecha_propuesta', e.target.value)}
                                    className={errors.fecha_propuesta ? 'border-red-500' : ''}
                                />
                                {errors.fecha_propuesta && (
                                    <p className="text-sm text-red-500">{errors.fecha_propuesta}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Justificación *</Label>
                                <Textarea
                                    placeholder="Justifica la necesidad..."
                                    value={data.justificacion}
                                    onChange={(e) => setData('justificacion', e.target.value)}
                                    className={`min-h-[100px] ${errors.justificacion ? 'border-red-500' : ''}`}
                                />
                                {errors.justificacion && (
                                    <p className="text-sm text-red-500">{errors.justificacion}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Observaciones</Label>
                            <Textarea
                                placeholder="Observaciones adicionales (opcional)"
                                value={data.observaciones}
                                onChange={(e) => setData('observaciones', e.target.value)}
                                className="min-h-[80px]"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Botones de acción */}
                <div className="flex justify-end space-x-2">
                    <Button type="button" variant="outline" asChild>
                        <Link href={route('admin.disposiciones.index')}>
                            Cancelar
                        </Link>
                    </Button>
                    <Button type="submit" disabled={processing || !isDirty}>
                        <Save className="h-4 w-4 mr-2" />
                        {processing ? 'Creando...' : 'Crear Disposición'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
