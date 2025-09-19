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
import { 
    ArrowLeft, 
    Save, 
    Search,
    Archive,
    FileText,
    User,
    Calendar,
    AlertCircle,
    MapPin,
    CheckCircle
} from 'lucide-react';

interface Expediente {
    id: number;
    numero_expediente: string;
    titulo: string;
    estado_ciclo_vida: string;
    ubicacion_fisica: string;
}

interface Documento {
    id: number;
    nombre: string;
    expediente_id: number;
    ubicacion_fisica: string;
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

export default function PrestamosCreate({ expedientes, documentos, usuarios, errors }: Props) {
    const [busquedaExpediente, setBusquedaExpediente] = useState('');
    const [busquedaDocumento, setBusquedaDocumento] = useState('');
    const [busquedaUsuario, setBusquedaUsuario] = useState('');
    
    const [expedientesFiltrados, setExpedientesFiltrados] = useState<Expediente[]>([]);
    const [documentosFiltrados, setDocumentosFiltrados] = useState<Documento[]>([]);
    const [usuariosFiltrados, setUsuariosFiltrados] = useState<Usuario[]>([]);

    const { data, setData, post, processing, isDirty } = useForm({
        tipo_prestamo: 'expediente' as 'expediente' | 'documento',
        expediente_id: '',
        documento_id: '',
        solicitante_id: '',
        motivo: '',
        fecha_devolucion_esperada: '',
        observaciones: '',
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
            const filtrados = usuarios.filter(user => 
                user.name.toLowerCase().includes(busquedaUsuario.toLowerCase()) ||
                user.email.toLowerCase().includes(busquedaUsuario.toLowerCase())
            ).slice(0, 10);
            setUsuariosFiltrados(filtrados);
        } else {
            setUsuariosFiltrados(usuarios.slice(0, 10));
        }
    }, [busquedaUsuario, usuarios]);

    // Limpiar selección al cambiar tipo
    useEffect(() => {
        setData('expediente_id', '');
        setData('documento_id', '');
    }, [data.tipo_prestamo]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.prestamos.store'));
    };

    const expedienteSeleccionado = expedientes.find(exp => exp.id.toString() === data.expediente_id);
    const documentoSeleccionado = documentos.find(doc => doc.id.toString() === data.documento_id);
    const solicitanteSeleccionado = usuarios.find(user => user.id.toString() === data.solicitante_id);

    // Fecha mínima: mañana
    const fechaMinima = new Date();
    fechaMinima.setDate(fechaMinima.getDate() + 1);
    const fechaMinimaString = fechaMinima.toISOString().split('T')[0];

    return (
        <AppLayout>
            <Head title="Crear Préstamo" />

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center space-x-2">
                            <Link 
                                href={route('admin.prestamos.index')}
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                Préstamos
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Nuevo Préstamo</span>
                        </div>
                        <h1 className="text-2xl font-bold mt-1">Crear Préstamo</h1>
                        <p className="text-muted-foreground">Registra un nuevo préstamo de expediente o documento</p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('admin.prestamos.index')}>
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Cancelar
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing || !isDirty}>
                            <Save className="h-4 w-4 mr-2" />
                            {processing ? 'Creando...' : 'Crear Préstamo'}
                        </Button>
                    </div>
                </div>

                {/* Tipo de préstamo */}
                <Card>
                    <CardHeader>
                        <CardTitle>Tipo de Préstamo</CardTitle>
                        <CardDescription>Selecciona si prestarás un expediente o documento individual</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button
                                type="button"
                                onClick={() => setData('tipo_prestamo', 'expediente')}
                                className={`p-4 border rounded-lg text-left transition-colors ${
                                    data.tipo_prestamo === 'expediente'
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:border-primary/50'
                                }`}
                            >
                                <div className="flex items-center space-x-3">
                                    <Archive className={`h-6 w-6 ${data.tipo_prestamo === 'expediente' ? 'text-primary' : 'text-muted-foreground'}`} />
                                    <div>
                                        <h3 className="font-medium">Expediente Completo</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Prestar un expediente físico completo con todos sus documentos
                                        </p>
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                onClick={() => setData('tipo_prestamo', 'documento')}
                                className={`p-4 border rounded-lg text-left transition-colors ${
                                    data.tipo_prestamo === 'documento'
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:border-primary/50'
                                }`}
                            >
                                <div className="flex items-center space-x-3">
                                    <FileText className={`h-6 w-6 ${data.tipo_prestamo === 'documento' ? 'text-primary' : 'text-muted-foreground'}`} />
                                    <div>
                                        <h3 className="font-medium">Documento Individual</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Prestar un documento específico de un expediente
                                        </p>
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
                            {data.tipo_prestamo === 'expediente' ? <Archive className="h-5 w-5" /> : <FileText className="h-5 w-5" />}
                            <span>Seleccionar {data.tipo_prestamo === 'expediente' ? 'Expediente' : 'Documento'}</span>
                        </CardTitle>
                        <CardDescription>
                            Busca y selecciona el {data.tipo_prestamo} que deseas prestar
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {data.tipo_prestamo === 'expediente' ? (
                            <>
                                <div className="space-y-2">
                                    <Label htmlFor="busqueda_expediente">Buscar Expediente</Label>
                                    <div className="relative">
                                        <Search className="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            id="busqueda_expediente"
                                            placeholder="Buscar por código o nombre del expediente..."
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
                                                <div>
                                                    <h4 className="font-medium">{expediente.numero_expediente}</h4>
                                                    <p className="text-sm text-muted-foreground">{expediente.titulo}</p>
                                                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                                                        <span>Estado: {expediente.estado_ciclo_vida}</span>
                                                        <span className="flex items-center">
                                                            <MapPin className="h-3 w-3 mr-1" />
                                                            {expediente.ubicacion_fisica}
                                                        </span>
                                                    </div>
                                                </div>
                                                {data.expediente_id === expediente.id.toString() && (
                                                    <CheckCircle className="h-5 w-5 text-primary" />
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {expedienteSeleccionado && (
                                    <Alert>
                                        <CheckCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>Expediente seleccionado:</strong> {expedienteSeleccionado.numero_expediente} - {expedienteSeleccionado.titulo}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                {errors.expediente_id && (
                                    <p className="text-sm text-red-500">{errors.expediente_id}</p>
                                )}
                            </>
                        ) : (
                            <>
                                <div className="space-y-2">
                                    <Label htmlFor="busqueda_documento">Buscar Documento</Label>
                                    <div className="relative">
                                        <Search className="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            id="busqueda_documento"
                                            placeholder="Buscar por nombre del documento..."
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
                                                <div>
                                                    <h4 className="font-medium">{documento.nombre}</h4>
                                                    <p className="text-sm text-muted-foreground">
                                                        Expediente: {documento.expediente.numero_expediente} - {documento.expediente.titulo}
                                                    </p>
                                                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                                                        <span className="flex items-center">
                                                            <MapPin className="h-3 w-3 mr-1" />
                                                            {documento.ubicacion_fisica}
                                                        </span>
                                                    </div>
                                                </div>
                                                {data.documento_id === documento.id.toString() && (
                                                    <CheckCircle className="h-5 w-5 text-primary" />
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {documentoSeleccionado && (
                                    <Alert>
                                        <CheckCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>Documento seleccionado:</strong> {documentoSeleccionado.nombre}
                                            <br />
                                            <strong>Expediente:</strong> {documentoSeleccionado.expediente.numero_expediente} - {documentoSeleccionado.expediente.titulo}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                {errors.documento_id && (
                                    <p className="text-sm text-red-500">{errors.documento_id}</p>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>

                {/* Información del préstamo */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <User className="h-5 w-5" />
                            <span>Información del Préstamo</span>
                        </CardTitle>
                        <CardDescription>Datos del solicitante y detalles del préstamo</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Solicitante */}
                        <div className="space-y-2">
                            <Label htmlFor="solicitante">Solicitante *</Label>
                            <div className="relative">
                                <Search className="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Buscar usuario por nombre o email..."
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
                                                data.solicitante_id === usuario.id.toString()
                                                    ? 'bg-primary/10 border border-primary'
                                                    : 'hover:bg-muted'
                                            }`}
                                            onClick={() => {
                                                setData('solicitante_id', usuario.id.toString());
                                                setBusquedaUsuario(usuario.name);
                                            }}
                                        >
                                            <p className="font-medium">{usuario.name}</p>
                                            <p className="text-sm text-muted-foreground">{usuario.email}</p>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {solicitanteSeleccionado && (
                                <Alert>
                                    <CheckCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        <strong>Solicitante:</strong> {solicitanteSeleccionado.name} ({solicitanteSeleccionado.email})
                                    </AlertDescription>
                                </Alert>
                            )}
                            
                            {errors.solicitante_id && (
                                <p className="text-sm text-red-500">{errors.solicitante_id}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Motivo */}
                            <div className="space-y-2">
                                <Label htmlFor="motivo">Motivo del Préstamo *</Label>
                                <Textarea
                                    id="motivo"
                                    placeholder="Describe el motivo o propósito del préstamo..."
                                    value={data.motivo}
                                    onChange={(e) => setData('motivo', e.target.value)}
                                    className={`min-h-[100px] ${errors.motivo ? 'border-red-500' : ''}`}
                                />
                                {errors.motivo && (
                                    <p className="text-sm text-red-500">{errors.motivo}</p>
                                )}
                            </div>

                            {/* Fecha de devolución */}
                            <div className="space-y-2">
                                <Label htmlFor="fecha_devolucion_esperada" className="flex items-center space-x-2">
                                    <Calendar className="h-4 w-4" />
                                    <span>Fecha de Devolución Esperada *</span>
                                </Label>
                                <Input
                                    id="fecha_devolucion_esperada"
                                    type="date"
                                    min={fechaMinimaString}
                                    value={data.fecha_devolucion_esperada}
                                    onChange={(e) => setData('fecha_devolucion_esperada', e.target.value)}
                                    className={errors.fecha_devolucion_esperada ? 'border-red-500' : ''}
                                />
                                {errors.fecha_devolucion_esperada && (
                                    <p className="text-sm text-red-500">{errors.fecha_devolucion_esperada}</p>
                                )}
                            </div>
                        </div>

                        {/* Observaciones */}
                        <div className="space-y-2">
                            <Label htmlFor="observaciones">Observaciones</Label>
                            <Textarea
                                id="observaciones"
                                placeholder="Observaciones adicionales (opcional)"
                                value={data.observaciones}
                                onChange={(e) => setData('observaciones', e.target.value)}
                                className="min-h-[80px]"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Resumen */}
                {(data.expediente_id || data.documento_id) && data.solicitante_id && (
                    <Card className="border-green-200 bg-green-50">
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <CheckCircle className="h-5 w-5 text-green-600" />
                                <span>Resumen del Préstamo</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="flex items-center space-x-2">
                                    <Badge variant="outline">
                                        {data.tipo_prestamo === 'expediente' ? 'Expediente' : 'Documento'}
                                    </Badge>
                                    <span className="font-medium">
                                        {expedienteSeleccionado 
                                            ? `${expedienteSeleccionado.numero_expediente} - ${expedienteSeleccionado.titulo}`
                                            : documentoSeleccionado?.nombre
                                        }
                                    </span>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <User className="h-4 w-4 text-muted-foreground" />
                                    <span>Solicitante: {solicitanteSeleccionado?.name}</span>
                                </div>
                                {data.fecha_devolucion_esperada && (
                                    <div className="flex items-center space-x-2">
                                        <Calendar className="h-4 w-4 text-muted-foreground" />
                                        <span>Devolución esperada: {new Date(data.fecha_devolucion_esperada).toLocaleDateString('es-ES')}</span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Botones de acción */}
                <div className="flex justify-end space-x-2">
                    <Button type="button" variant="outline" asChild>
                        <Link href={route('admin.prestamos.index')}>
                            Cancelar
                        </Link>
                    </Button>
                    <Button type="submit" disabled={processing || !isDirty}>
                        <Save className="h-4 w-4 mr-2" />
                        {processing ? 'Creando...' : 'Crear Préstamo'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
