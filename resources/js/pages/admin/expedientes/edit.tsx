import React, { useState, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    FileText, 
    Calendar, 
    MapPin, 
    User, 
    Tag, 
    Settings, 
    Shield,
    Save,
    X,
    ArrowLeft,
    Plus,
    AlertCircle
} from 'lucide-react';

interface Serie {
    id: number;
    codigo: string;
    nombre: string;
    subseries: Subserie[];
}

interface Subserie {
    id: number;
    codigo: string;
    nombre: string;
    serie_id: number;
}

interface Expediente {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string;
    estado: string;
    fecha_apertura: string;
    fecha_cierre?: string;
    ubicacion_fisica: string;
    ubicacion_digital: string;
    responsable: string;
    departamento: string;
    clasificacion_serie: string;
    clasificacion_subserie: string;
    nivel_acceso: string;
    palabras_clave: string[];
    observaciones?: string;
    configuracion: {
        permite_documentos_electronicos: boolean;
        requiere_firma_digital: boolean;
        control_versiones: boolean;
        notificaciones_automaticas: boolean;
    };
}

interface Props {
    expediente: Expediente;
    series: Serie[];
    departamentos: string[];
    responsables: string[];
    errors: Record<string, string>;
}

export default function Edit({ expediente, series, departamentos, responsables, errors }: Props) {
    const [nuevaPalabra, setNuevaPalabra] = useState('');
    const [subseries, setSubseries] = useState<Subserie[]>([]);

    const { data, setData, put, processing, isDirty } = useForm({
        nombre: expediente.nombre || '',
        descripcion: expediente.descripcion || '',
        estado: expediente.estado || 'abierto',
        fecha_apertura: expediente.fecha_apertura ? expediente.fecha_apertura.split('T')[0] : '',
        fecha_cierre: expediente.fecha_cierre ? expediente.fecha_cierre.split('T')[0] : '',
        ubicacion_fisica: expediente.ubicacion_fisica || '',
        responsable: expediente.responsable || '',
        departamento: expediente.departamento || '',
        clasificacion_serie: expediente.clasificacion_serie || '',
        clasificacion_subserie: expediente.clasificacion_subserie || '',
        nivel_acceso: expediente.nivel_acceso || 'publico',
        palabras_clave: expediente.palabras_clave || [],
        observaciones: expediente.observaciones || '',
        permite_documentos_electronicos: expediente.configuracion?.permite_documentos_electronicos ?? true,
        requiere_firma_digital: expediente.configuracion?.requiere_firma_digital ?? false,
        control_versiones: expediente.configuracion?.control_versiones ?? true,
        notificaciones_automaticas: expediente.configuracion?.notificaciones_automaticas ?? true,
    });

    // Cargar subseries cuando cambia la serie
    useEffect(() => {
        if (data.clasificacion_serie) {
            const serieSeleccionada = series.find(s => s.nombre === data.clasificacion_serie);
            setSubseries(serieSeleccionada?.subseries || []);
            
            // Verificar si la subserie actual sigue siendo válida
            const subserieValida = serieSeleccionada?.subseries.some(sub => sub.nombre === data.clasificacion_subserie);
            if (!subserieValida) {
                setData('clasificacion_subserie', '');
            }
        } else {
            setSubseries([]);
            setData('clasificacion_subserie', '');
        }
    }, [data.clasificacion_serie]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.expedientes.update', expediente.id));
    };

    const agregarPalabra = () => {
        if (nuevaPalabra.trim() && !data.palabras_clave.includes(nuevaPalabra.trim())) {
            setData('palabras_clave', [...data.palabras_clave, nuevaPalabra.trim()]);
            setNuevaPalabra('');
        }
    };

    const eliminarPalabra = (palabra: string) => {
        setData('palabras_clave', data.palabras_clave.filter(p => p !== palabra));
    };

    const handleKeyPress = (e: React.KeyEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            agregarPalabra();
        }
    };

    return (
        <AppLayout>
            <Head title={`Editar Expediente: ${expediente.nombre}`} />

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center space-x-2">
                            <Link 
                                href={route('admin.expedientes.index')}
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                Expedientes
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <Link 
                                href={route('admin.expedientes.show', expediente.id)}
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                {expediente.codigo}
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Editar</span>
                        </div>
                        <h1 className="text-2xl font-bold mt-1">Editar Expediente</h1>
                        <p className="text-muted-foreground">Modifica la información del expediente</p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('admin.expedientes.show', expediente.id)}>
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Cancelar
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing || !isDirty}>
                            <Save className="h-4 w-4 mr-2" />
                            {processing ? 'Guardando...' : 'Guardar Cambios'}
                        </Button>
                    </div>
                </div>

                {/* Cambios sin guardar */}
                {isDirty && (
                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            Tienes cambios sin guardar. Asegúrate de guardar antes de salir.
                        </AlertDescription>
                    </Alert>
                )}

                {/* Información General */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <FileText className="h-5 w-5" />
                            <span>Información General</span>
                        </CardTitle>
                        <CardDescription>
                            Información básica del expediente
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="nombre">Nombre del Expediente *</Label>
                                <Input
                                    id="nombre"
                                    value={data.nombre}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    placeholder="Nombre del expediente"
                                    className={errors.nombre ? 'border-red-500' : ''}
                                />
                                {errors.nombre && (
                                    <p className="text-sm text-red-500">{errors.nombre}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="estado">Estado</Label>
                                <Select 
                                    value={data.estado} 
                                    onValueChange={(value) => setData('estado', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="abierto">Abierto</SelectItem>
                                        <SelectItem value="tramite">En Trámite</SelectItem>
                                        <SelectItem value="revision">En Revisión</SelectItem>
                                        <SelectItem value="cerrado">Cerrado</SelectItem>
                                        <SelectItem value="archivado">Archivado</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.estado && (
                                    <p className="text-sm text-red-500">{errors.estado}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="descripcion">Descripción *</Label>
                            <Textarea
                                id="descripcion"
                                value={data.descripcion}
                                onChange={(e) => setData('descripcion', e.target.value)}
                                placeholder="Describe el propósito y contenido del expediente"
                                className={`min-h-[100px] ${errors.descripcion ? 'border-red-500' : ''}`}
                            />
                            {errors.descripcion && (
                                <p className="text-sm text-red-500">{errors.descripcion}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Fechas */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Calendar className="h-5 w-5" />
                            <span>Fechas</span>
                        </CardTitle>
                        <CardDescription>
                            Fechas de apertura y cierre del expediente
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="fecha_apertura">Fecha de Apertura *</Label>
                                <Input
                                    id="fecha_apertura"
                                    type="date"
                                    value={data.fecha_apertura}
                                    onChange={(e) => setData('fecha_apertura', e.target.value)}
                                    className={errors.fecha_apertura ? 'border-red-500' : ''}
                                />
                                {errors.fecha_apertura && (
                                    <p className="text-sm text-red-500">{errors.fecha_apertura}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="fecha_cierre">Fecha de Cierre</Label>
                                <Input
                                    id="fecha_cierre"
                                    type="date"
                                    value={data.fecha_cierre}
                                    onChange={(e) => setData('fecha_cierre', e.target.value)}
                                    className={errors.fecha_cierre ? 'border-red-500' : ''}
                                />
                                {errors.fecha_cierre && (
                                    <p className="text-sm text-red-500">{errors.fecha_cierre}</p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Clasificación Archivística */}
                <Card>
                    <CardHeader>
                        <CardTitle>Clasificación Archivística</CardTitle>
                        <CardDescription>
                            Serie y subserie documental según el CCD
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="clasificacion_serie">Serie Documental *</Label>
                                <Select 
                                    value={data.clasificacion_serie} 
                                    onValueChange={(value) => setData('clasificacion_serie', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona una serie" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {series.map((serie) => (
                                            <SelectItem key={serie.id} value={serie.nombre}>
                                                {serie.codigo} - {serie.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.clasificacion_serie && (
                                    <p className="text-sm text-red-500">{errors.clasificacion_serie}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="clasificacion_subserie">Subserie Documental</Label>
                                <Select 
                                    value={data.clasificacion_subserie} 
                                    onValueChange={(value) => setData('clasificacion_subserie', value)}
                                    disabled={!data.clasificacion_serie || subseries.length === 0}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona una subserie" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {subseries.map((subserie) => (
                                            <SelectItem key={subserie.id} value={subserie.nombre}>
                                                {subserie.codigo} - {subserie.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.clasificacion_subserie && (
                                    <p className="text-sm text-red-500">{errors.clasificacion_subserie}</p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Configuración */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Settings className="h-5 w-5" />
                            <span>Configuración</span>
                        </CardTitle>
                        <CardDescription>
                            Configuración de funcionalidades del expediente
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="flex items-center justify-between p-3 border rounded-lg">
                                <div className="space-y-0.5">
                                    <Label className="text-base">Documentos Electrónicos</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Permite agregar documentos digitales
                                    </p>
                                </div>
                                <Switch
                                    checked={data.permite_documentos_electronicos}
                                    onCheckedChange={(checked) => setData('permite_documentos_electronicos', checked)}
                                />
                            </div>

                            <div className="flex items-center justify-between p-3 border rounded-lg">
                                <div className="space-y-0.5">
                                    <Label className="text-base">Firma Digital</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Requiere firma digital para documentos
                                    </p>
                                </div>
                                <Switch
                                    checked={data.requiere_firma_digital}
                                    onCheckedChange={(checked) => setData('requiere_firma_digital', checked)}
                                />
                            </div>

                            <div className="flex items-center justify-between p-3 border rounded-lg">
                                <div className="space-y-0.5">
                                    <Label className="text-base">Control de Versiones</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Mantiene historial de versiones
                                    </p>
                                </div>
                                <Switch
                                    checked={data.control_versiones}
                                    onCheckedChange={(checked) => setData('control_versiones', checked)}
                                />
                            </div>

                            <div className="flex items-center justify-between p-3 border rounded-lg">
                                <div className="space-y-0.5">
                                    <Label className="text-base">Notificaciones Automáticas</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Envía notificaciones sobre cambios
                                    </p>
                                </div>
                                <Switch
                                    checked={data.notificaciones_automaticas}
                                    onCheckedChange={(checked) => setData('notificaciones_automaticas', checked)}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Responsabilidad y Ubicación */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <User className="h-5 w-5" />
                            <span>Responsabilidad y Ubicación</span>
                        </CardTitle>
                        <CardDescription>
                            Responsable y ubicación física del expediente
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="responsable">Responsable *</Label>
                                <Select 
                                    value={data.responsable} 
                                    onValueChange={(value) => setData('responsable', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el responsable" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {responsables.map((responsable) => (
                                            <SelectItem key={responsable} value={responsable}>
                                                {responsable}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.responsable && (
                                    <p className="text-sm text-red-500">{errors.responsable}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="departamento">Departamento *</Label>
                                <Select 
                                    value={data.departamento} 
                                    onValueChange={(value) => setData('departamento', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el departamento" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {departamentos.map((departamento) => (
                                            <SelectItem key={departamento} value={departamento}>
                                                {departamento}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.departamento && (
                                    <p className="text-sm text-red-500">{errors.departamento}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="ubicacion_fisica">Ubicación Física *</Label>
                                <Input
                                    id="ubicacion_fisica"
                                    value={data.ubicacion_fisica}
                                    onChange={(e) => setData('ubicacion_fisica', e.target.value)}
                                    placeholder="Ej: Archivo Central - Estante A1 - Caja 001"
                                    className={errors.ubicacion_fisica ? 'border-red-500' : ''}
                                />
                                {errors.ubicacion_fisica && (
                                    <p className="text-sm text-red-500">{errors.ubicacion_fisica}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="nivel_acceso">Nivel de Acceso *</Label>
                                <Select 
                                    value={data.nivel_acceso} 
                                    onValueChange={(value) => setData('nivel_acceso', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el nivel de acceso" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="publico">
                                            <div className="flex items-center space-x-2">
                                                <Shield className="h-4 w-4 text-green-600" />
                                                <span>Público</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="restringido">
                                            <div className="flex items-center space-x-2">
                                                <Shield className="h-4 w-4 text-yellow-600" />
                                                <span>Restringido</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="confidencial">
                                            <div className="flex items-center space-x-2">
                                                <Shield className="h-4 w-4 text-red-600" />
                                                <span>Confidencial</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="secreto">
                                            <div className="flex items-center space-x-2">
                                                <Shield className="h-4 w-4 text-red-800" />
                                                <span>Secreto</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.nivel_acceso && (
                                    <p className="text-sm text-red-500">{errors.nivel_acceso}</p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Palabras Clave y Observaciones */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Tag className="h-5 w-5" />
                            <span>Palabras Clave y Observaciones</span>
                        </CardTitle>
                        <CardDescription>
                            Palabras clave para búsqueda y observaciones adicionales
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label>Palabras Clave</Label>
                            <div className="flex space-x-2">
                                <Input
                                    value={nuevaPalabra}
                                    onChange={(e) => setNuevaPalabra(e.target.value)}
                                    onKeyPress={handleKeyPress}
                                    placeholder="Escribe una palabra clave y presiona Enter"
                                    className="flex-1"
                                />
                                <Button 
                                    type="button" 
                                    onClick={agregarPalabra}
                                    disabled={!nuevaPalabra.trim()}
                                    size="sm"
                                >
                                    <Plus className="h-4 w-4" />
                                    Agregar
                                </Button>
                            </div>
                            {data.palabras_clave.length > 0 && (
                                <div className="flex flex-wrap gap-2 mt-2">
                                    {data.palabras_clave.map((palabra, index) => (
                                        <Badge 
                                            key={index} 
                                            variant="secondary"
                                            className="cursor-pointer"
                                            onClick={() => eliminarPalabra(palabra)}
                                        >
                                            {palabra}
                                            <X className="h-3 w-3 ml-1" />
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="observaciones">Observaciones</Label>
                            <Textarea
                                id="observaciones"
                                value={data.observaciones}
                                onChange={(e) => setData('observaciones', e.target.value)}
                                placeholder="Observaciones adicionales sobre el expediente"
                                className="min-h-[100px]"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Botones de acción */}
                <div className="flex justify-end space-x-2">
                    <Button type="button" variant="outline" asChild>
                        <Link href={route('admin.expedientes.show', expediente.id)}>
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
