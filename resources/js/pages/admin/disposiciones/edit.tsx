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
    codigo: string;
    titulo: string;
    serie_documental: string;
    ubicacion_fisica: string;
    estado_ciclo_vida: string;
}

interface Documento {
    id: number;
    nombre: string;
    ubicacion_fisica: string;
    expediente: {
        id: number;
        codigo: string;
        titulo: string;
        serie_documental: string;
    };
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface DisposicionFinal {
    id: number;
    tipo_disposicion: string;
    estado: string;
    fecha_propuesta: string;
    justificacion: string;
    observaciones?: string;
    item_afectado: string;
    tipo_disposicion_label: string;
    estado_label: string;
    expediente?: Expediente;
    documento?: Documento;
    responsable?: Usuario | null;
}

interface Props {
    disposicion: DisposicionFinal;
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

export default function DisposicionesEdit({ disposicion, usuarios, errors }: Props) {
    const [busquedaUsuario, setBusquedaUsuario] = useState('');
    const [usuariosFiltrados, setUsuariosFiltrados] = useState<Usuario[]>([]);

    const { data, setData, put, processing, isDirty } = useForm({
        tipo_disposicion: disposicion.tipo_disposicion,
        justificacion: disposicion.justificacion,
        observaciones: disposicion.observaciones || '',
        fecha_propuesta: disposicion.fecha_propuesta.split('T')[0],
        responsable_id: disposicion.responsable?.id?.toString() || '',
    });

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

    const responsableSeleccionado = usuarios.find(user => user.id.toString() === data.responsable_id);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.disposiciones.update', disposicion.id));
    };

    return (
        <AppLayout>
            <Head title={`Editar Disposición #${disposicion.id}`} />
            
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center space-x-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('admin.disposiciones.show', disposicion.id)}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Link>
                    </Button>
                    
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            Editar Disposición #{disposicion.id}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Modificar la propuesta de disposición final
                        </p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Item afectado (solo lectura) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            {disposicion.expediente ? <Archive className="h-5 w-5 text-blue-500" /> : <FileText className="h-5 w-5 text-green-500" />}
                            <span>Item Afectado</span>
                        </CardTitle>
                        <CardDescription>Este campo no puede ser modificado</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Alert className="bg-muted">
                            <AlertDescription>
                                {disposicion.expediente ? (
                                    <div>
                                        <p className="font-medium">{disposicion.expediente.codigo}</p>
                                        <p className="text-sm text-muted-foreground">{disposicion.expediente.titulo}</p>
                                    </div>
                                ) : disposicion.documento ? (
                                    <div>
                                        <p className="font-medium">{disposicion.documento.nombre}</p>
                                        <p className="text-sm text-muted-foreground">
                                            Expediente: {disposicion.documento.expediente.codigo}
                                        </p>
                                    </div>
                                ) : (
                                    <p>Item no disponible</p>
                                )}
                            </AlertDescription>
                        </Alert>
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
                        <div className="space-y-4">
                            <Label>Responsable</Label>
                            
                            {!responsableSeleccionado ? (
                                <>
                                    <div className="relative">
                                        <Search className="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            placeholder="Buscar usuario registrado..."
                                            value={busquedaUsuario}
                                            onChange={(e) => setBusquedaUsuario(e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                    
                                    {busquedaUsuario && usuariosFiltrados.length > 0 && (
                                        <div className="space-y-1 max-h-60 overflow-y-auto border rounded-lg p-2 bg-background shadow-lg">
                                            {usuariosFiltrados.map((usuario) => (
                                                <div
                                                    key={usuario.id}
                                                    className="p-3 rounded-lg cursor-pointer transition-colors hover:bg-primary/10 border border-transparent hover:border-primary"
                                                    onClick={() => {
                                                        setData('responsable_id', usuario.id.toString());
                                                        setBusquedaUsuario('');
                                                    }}
                                                >
                                                    <p className="font-medium">{usuario.name}</p>
                                                    <p className="text-sm text-muted-foreground">{usuario.email}</p>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </>
                            ) : (
                                <Alert className="bg-primary/5 border-primary">
                                    <CheckCircle className="h-4 w-4 text-primary" />
                                    <AlertDescription className="flex items-center justify-between">
                                        <div>
                                            <p className="font-medium">{responsableSeleccionado.name}</p>
                                            <p className="text-sm text-muted-foreground">{responsableSeleccionado.email}</p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => {
                                                setData('responsable_id', '');
                                                setBusquedaUsuario('');
                                            }}
                                        >
                                            Cambiar
                                        </Button>
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
                        <Link href={route('admin.disposiciones.show', disposicion.id)}>
                            Cancelar
                        </Link>
                    </Button>
                    <Button type="submit" disabled={processing || !isDirty}>
                        <Save className="h-4 w-4 mr-2" />
                        {processing ? 'Guardando...' : 'Guardar Cambios'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
