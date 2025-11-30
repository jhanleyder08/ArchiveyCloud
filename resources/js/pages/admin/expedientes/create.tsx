import React, { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { 
    ArrowLeft, 
    Save, 
    FolderPlus,
    AlertTriangle,
    Info,
    X
} from 'lucide-react';
import { toast } from 'sonner';

interface Serie {
    id: number;
    codigo: string;
    nombre: string;
}

interface Subserie {
    id: number;
    serie_id: number;
    codigo: string;
    nombre: string;
}

interface TRD {
    id: number;
    codigo: string;
    version: string;
    nombre: string;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
}

interface CreateExpedienteProps {
    opciones: {
        series: Serie[];
        subseries: Subserie[];
        trds: TRD[];
        usuarios: Usuario[];
        tipos_expediente: { value: string; label: string; }[];
        confidencialidad: { value: string; label: string; }[];
        areas_disponibles: { value: string; label: string; }[];
    };
}

interface FormData {
    nombre: string;
    descripcion: string;
    serie_id: string;
    subserie_id: string;
    trd_id: string;
    tipo_expediente: string;
    confidencialidad: string;
    usuario_responsable_id: string;
    area_responsable: string;
    volumen_maximo: number | null;
    ubicacion_fisica: string;
    ubicacion_digital: string;
    palabras_clave: string[];
    acceso_publico: boolean;
    observaciones: string;
}

export default function CreateExpediente({ opciones }: CreateExpedienteProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [palabrasClave, setPalabrasClave] = useState<string[]>([]);
    const [nuevaPalabra, setNuevaPalabra] = useState('');
    const [subseriesFiltradas, setSubseriesFiltradas] = useState<Subserie[]>([]);
    
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        nombre: '',
        descripcion: '',
        serie_id: '',
        subserie_id: '',
        trd_id: '',
        tipo_expediente: 'electronico',
        confidencialidad: 'interna',
        usuario_responsable_id: '',
        area_responsable: '',
        volumen_maximo: 1024,
        ubicacion_fisica: '',
        ubicacion_digital: '',
        palabras_clave: [],
        acceso_publico: false,
        observaciones: '',
    });

    const breadcrumbItems = [
        { title: 'Inicio', href: '/dashboard' },
        { title: 'Administración', href: '/admin' },
        { title: 'Expedientes', href: '/admin/expedientes' },
        { title: 'Crear Expediente', href: '/admin/expedientes/create' },
    ];

    // Filtrar subseries cuando cambia la serie
    useEffect(() => {
        if (data.serie_id && opciones?.subseries) {
            const subseries = opciones.subseries.filter(
                subserie => subserie.serie_id === parseInt(data.serie_id)
            );
            setSubseriesFiltradas(subseries);
        } else {
            setSubseriesFiltradas([]);
            setData('subserie_id', '');
        }
    }, [data.serie_id, opciones]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        post('/admin/expedientes', {
            onSuccess: () => {
                toast.success('Expediente creado exitosamente');
                router.visit('/admin/expedientes');
            },
            onError: (errors) => {
                toast.error('Error al crear el expediente. Revisa los campos marcados.');
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    // Agregar palabra clave
    const agregarPalabraClave = () => {
        if (nuevaPalabra.trim() && !palabrasClave.includes(nuevaPalabra.trim())) {
            const nuevas = [...palabrasClave, nuevaPalabra.trim()];
            setPalabrasClave(nuevas);
            setData('palabras_clave', nuevas);
            setNuevaPalabra('');
        }
    };

    // Eliminar palabra clave
    const eliminarPalabraClave = (palabra: string) => {
        const nuevas = palabrasClave.filter(p => p !== palabra);
        setPalabrasClave(nuevas);
        setData('palabras_clave', nuevas);
    };

    // Obtener la serie seleccionada para mostrar información
    const serieSeleccionada = opciones?.series?.find(s => s.id === parseInt(data.serie_id));
    const subserieSeleccionada = subseriesFiltradas.find(s => s.id === parseInt(data.subserie_id));

    return (
        <AppLayout breadcrumbs={breadcrumbItems}>
            <Head title="Crear Expediente" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <FolderPlus className="h-6 w-6 text-[#2a3d83]" />
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Crear Expediente Electrónico
                        </h1>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => router.visit('/admin/expedientes')}
                        className="flex items-center gap-2"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Volver
                    </Button>
                </div>

                {/* Info Alert */}
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        Un expediente electrónico agrupa documentos relacionados siguiendo la estructura 
                        archivística de series y subseries documentales definidas en las TRD.
                    </AlertDescription>
                </Alert>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Información Básica */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Básica</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="nombre">Nombre del Expediente *</Label>
                                <Input
                                    id="nombre"
                                    type="text"
                                    value={data.nombre}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    placeholder="Nombre descriptivo del expediente"
                                    className={errors.nombre ? 'border-red-500' : ''}
                                    required
                                />
                                {errors.nombre && (
                                    <p className="text-sm text-red-600 mt-1">{errors.nombre}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                    placeholder="Descripción detallada del expediente"
                                    rows={3}
                                    className={errors.descripcion ? 'border-red-500' : ''}
                                />
                                {errors.descripcion && (
                                    <p className="text-sm text-red-600 mt-1">{errors.descripcion}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Clasificación Archivística */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Clasificación Archivística</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="serie_id">Serie Documental *</Label>
                                    <Select 
                                        value={data.serie_id} 
                                        onValueChange={(value) => {
                                            setData('serie_id', value);
                                            setData('subserie_id', '');
                                        }}
                                    >
                                        <SelectTrigger id="serie_id" className={errors.serie_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Seleccionar serie" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.series.map((serie) => (
                                                <SelectItem key={serie.id} value={serie.id.toString()}>
                                                    {serie.codigo} - {serie.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.serie_id && (
                                        <p className="text-sm text-red-600 mt-1">{errors.serie_id}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="subserie_id">Subserie Documental</Label>
                                    <Select 
                                        value={data.subserie_id} 
                                        onValueChange={(value) => setData('subserie_id', value)}
                                        disabled={!data.serie_id || subseriesFiltradas.length === 0}
                                    >
                                        <SelectTrigger id="subserie_id" className={errors.subserie_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder={
                                                !data.serie_id ? "Primero selecciona una serie" :
                                                subseriesFiltradas.length === 0 ? "No hay subseries disponibles" :
                                                "Seleccionar subserie (opcional)"
                                            } />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {subseriesFiltradas.map((subserie) => (
                                                <SelectItem key={subserie.id} value={subserie.id.toString()}>
                                                    {subserie.codigo} - {subserie.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.subserie_id && (
                                        <p className="text-sm text-red-600 mt-1">{errors.subserie_id}</p>
                                    )}
                                </div>
                            </div>

                            {/* Información de la serie seleccionada */}
                            {serieSeleccionada && (
                                <div className="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <p className="text-sm text-blue-800">
                                        <strong>Serie seleccionada:</strong> {serieSeleccionada.codigo} - {serieSeleccionada.nombre}
                                        {subserieSeleccionada && (
                                            <>
                                                <br />
                                                <strong>Subserie seleccionada:</strong> {subserieSeleccionada.codigo} - {subserieSeleccionada.nombre}
                                            </>
                                        )}
                                    </p>
                                </div>
                            )}

                            <div>
                                <Label htmlFor="trd_id">Tabla de Retención Documental</Label>
                                <Select 
                                    value={data.trd_id} 
                                    onValueChange={(value) => setData('trd_id', value)}
                                >
                                    <SelectTrigger id="trd_id">
                                        <SelectValue placeholder="Seleccionar TRD (opcional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="null">Sin TRD específica</SelectItem>
                                        {opciones.trds.map((trd) => (
                                            <SelectItem key={trd.id} value={trd.id.toString()}>
                                                {trd.codigo} v{trd.version} - {trd.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Configuración del Expediente */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuración del Expediente</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="tipo_expediente">Tipo de Expediente *</Label>
                                    <Select 
                                        value={data.tipo_expediente} 
                                        onValueChange={(value) => setData('tipo_expediente', value)}
                                    >
                                        <SelectTrigger id="tipo_expediente" className={errors.tipo_expediente ? 'border-red-500' : ''}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.tipos_expediente.map((tipo) => (
                                                <SelectItem key={tipo.value} value={tipo.value}>
                                                    {tipo.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.tipo_expediente && (
                                        <p className="text-sm text-red-600 mt-1">{errors.tipo_expediente}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="confidencialidad">Confidencialidad *</Label>
                                    <Select 
                                        value={data.confidencialidad} 
                                        onValueChange={(value) => setData('confidencialidad', value)}
                                    >
                                        <SelectTrigger id="confidencialidad" className={errors.confidencialidad ? 'border-red-500' : ''}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.confidencialidad.map((conf) => (
                                                <SelectItem key={conf.value} value={conf.value}>
                                                    {conf.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.confidencialidad && (
                                        <p className="text-sm text-red-600 mt-1">{errors.confidencialidad}</p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="volumen_maximo">Volumen Máximo (MB)</Label>
                                <Input
                                    id="volumen_maximo"
                                    type="number"
                                    min="1"
                                    value={data.volumen_maximo || ''}
                                    onChange={(e) => setData('volumen_maximo', parseInt(e.target.value) || null)}
                                    placeholder="1024"
                                />
                                <p className="text-xs text-gray-500 mt-1">
                                    Límite de almacenamiento para todos los documentos del expediente
                                </p>
                            </div>

                            <div className="flex items-center space-x-2">
                                <input
                                    id="acceso_publico"
                                    type="checkbox"
                                    checked={data.acceso_publico}
                                    onChange={(e) => setData('acceso_publico', e.target.checked)}
                                    className="rounded border-gray-300"
                                />
                                <Label htmlFor="acceso_publico">Permitir acceso público</Label>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Responsabilidad */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Responsabilidad</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="usuario_responsable_id">Usuario Responsable *</Label>
                                    <Select 
                                        value={data.usuario_responsable_id} 
                                        onValueChange={(value) => setData('usuario_responsable_id', value)}
                                    >
                                        <SelectTrigger id="usuario_responsable_id" className={errors.usuario_responsable_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Seleccionar responsable" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.usuarios.map((usuario) => (
                                                <SelectItem key={usuario.id} value={usuario.id.toString()}>
                                                    {usuario.name} ({usuario.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.usuario_responsable_id && (
                                        <p className="text-sm text-red-600 mt-1">{errors.usuario_responsable_id}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="area_responsable">Área Responsable *</Label>
                                    <Input
                                        id="area_responsable"
                                        type="text"
                                        value={data.area_responsable}
                                        onChange={(e) => setData('area_responsable', e.target.value)}
                                        placeholder="Ej: Secretaría General, Recursos Humanos"
                                        className={errors.area_responsable ? 'border-red-500' : ''}
                                        required
                                    />
                                    {errors.area_responsable && (
                                        <p className="text-sm text-red-600 mt-1">{errors.area_responsable}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Ubicación */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Ubicación</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="ubicacion_fisica">Ubicación Física</Label>
                                    <Input
                                        id="ubicacion_fisica"
                                        type="text"
                                        value={data.ubicacion_fisica}
                                        onChange={(e) => setData('ubicacion_fisica', e.target.value)}
                                        placeholder="Ej: Archivo Central, Estante A-1"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="ubicacion_digital">Ubicación Digital</Label>
                                    <Input
                                        id="ubicacion_digital"
                                        type="text"
                                        value={data.ubicacion_digital}
                                        onChange={(e) => setData('ubicacion_digital', e.target.value)}
                                        placeholder="Ej: Servidor/carpeta/ruta"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Palabras Clave */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Palabras Clave</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex gap-2">
                                <Input
                                    value={nuevaPalabra}
                                    onChange={(e) => setNuevaPalabra(e.target.value)}
                                    placeholder="Agregar palabra clave"
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), agregarPalabraClave())}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={agregarPalabraClave}
                                >
                                    Agregar
                                </Button>
                            </div>
                            
                            {palabrasClave.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {palabrasClave.map((palabra) => (
                                        <span
                                            key={palabra}
                                            className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
                                        >
                                            {palabra}
                                            <button
                                                type="button"
                                                onClick={() => eliminarPalabraClave(palabra)}
                                                className="hover:text-blue-600"
                                            >
                                                <X className="h-3 w-3" />
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Observaciones */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Observaciones</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea
                                value={data.observaciones}
                                onChange={(e) => setData('observaciones', e.target.value)}
                                placeholder="Observaciones adicionales sobre el expediente"
                                rows={3}
                            />
                        </CardContent>
                    </Card>

                    {/* Botones de acción */}
                    <div className="flex justify-end gap-3 pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/admin/expedientes')}
                            disabled={processing || isSubmitting}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || isSubmitting}
                            className="bg-[#2a3d83] hover:bg-[#1e2b5f] flex items-center gap-2"
                        >
                            <Save className="h-4 w-4" />
                            {processing || isSubmitting ? 'Creando...' : 'Crear Expediente'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
